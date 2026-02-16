<?php
include("sess_check.php");
include("../dist/config/koneksi.php");

// Check if file is uploaded
if (!isset($_FILES['excel_file'])) {
    $_SESSION['alert_type'] = 'danger';
    $_SESSION['alert_message'] = 'Tidak ada file yang diupload!';
    header('Location: insentif_karyawan_upload.php');
    exit();
}

// Load PhpSpreadsheet library
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

$file = $_FILES['excel_file'];
$file_tmp = $file['tmp_name'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Check PHP upload errors
if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['alert_type'] = 'danger';
    $_SESSION['alert_message'] = 'Upload gagal: Kesalahan pada proses upload file.';
    header('Location: insentif_karyawan_upload.php');
    exit();
}

if (!in_array($ext, ['xls','xlsx'])) {
    $_SESSION['alert_type'] = 'danger';
    $_SESSION['alert_message'] = 'Format file tidak valid. Gunakan .xls atau .xlsx';
    header('Location: insentif_karyawan_upload.php');
    exit();
}

// Business constants
$JAM_STANDAR_MASUK = '09:15:00';
$JAM_STANDAR_KELUAR = '16:00:00';
$DENDA_PER_MENIT = 1000;
$UANG_MAKAN_FULL = 300000;
$INSENTIF_FULL_MASUK = 100000;
$RATE_TITIK_TAMBAHAN = 20000;
$RATE_LEMBUR_OPERASIONAL = 30000;
$RATE_LEMBUR_AMBIL_BARANG = 50000;
$RATE_LEMBUR_LAINNYA = 30000;

try {
    $spreadsheet = IOFactory::load($file_tmp);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();

    $success = 0;
    $errors = [];
    
    // Monthly aggregator: key = "npp|YYYY-MM"
    $monthly_data = [];

    // Read daily data from Excel (rows 2+)
    for ($r = 2; $r <= $highestRow; $r++) {
        $npp = trim((string)$sheet->getCell('A'.$r)->getValue());
        if ($npp === '') continue;

        $tanggal_raw = $sheet->getCell('B'.$r)->getValue();
        $jam_absen_raw = trim((string)$sheet->getCell('C'.$r)->getValue());
        $jenis_tugas = trim((string)$sheet->getCell('D'.$r)->getValue());
        $aktual_raw = $sheet->getCell('E'.$r)->getValue();
        $target_raw = $sheet->getCell('F'.$r)->getValue();
        $lembur_ops_raw = $sheet->getCell('G'.$r)->getValue();
        $lembur_ab_raw = $sheet->getCell('H'.$r)->getValue();
        $lembur_lain_raw = $sheet->getCell('I'.$r)->getValue();

        // Parse tanggal
        $tanggal = null;
        if ($tanggal_raw === null || $tanggal_raw === '') {
            $errors[] = "Baris $r: tanggal kosong";
            continue;
        }
        if (is_numeric($tanggal_raw)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject($tanggal_raw);
                $tanggal = $dt->format('Y-m-d');
            } catch (Exception $e) {
                $ts = strtotime((string)$tanggal_raw);
                if ($ts === false) {
                    $errors[] = "Baris $r: format tanggal tidak dikenali";
                    continue;
                }
                $tanggal = date('Y-m-d', $ts);
            }
        } else {
            $ts = strtotime((string)$tanggal_raw);
            if ($ts === false) {
                $errors[] = "Baris $r: format tanggal tidak dikenali";
                continue;
            }
            $tanggal = date('Y-m-d', $ts);
        }

        $periode = date('Y-m', strtotime($tanggal));

        // Parse Jam_Absen: "08:21 16:27"
        $jam_masuk = null;
        $jam_keluar = null;
        if ($jam_absen_raw !== '') {
            $parts = preg_split('/\s+/', $jam_absen_raw);
            if (isset($parts[0]) && trim($parts[0]) !== '') {
                $tsm = strtotime(trim($parts[0]));
                if ($tsm !== false) $jam_masuk = date('H:i:s', $tsm);
            }
            if (isset($parts[1]) && trim($parts[1]) !== '') {
                $tsk = strtotime(trim($parts[1]));
                if ($tsk !== false) $jam_keluar = date('H:i:s', $tsk);
            }
        }

        $aktual = intval($aktual_raw);
        $target = intval($target_raw);
        $lembur_ops = intval($lembur_ops_raw);
        $lembur_ab = intval($lembur_ab_raw);
        $lembur_lain = intval($lembur_lain_raw);

        // Validate employee
        $key = $npp . '|' . $periode;
        if (!isset($monthly_data[$key])) {
            $chk = mysqli_prepare($conn, "SELECT npp FROM employee WHERE npp = ? LIMIT 1");
            mysqli_stmt_bind_param($chk, 's', $npp);
            mysqli_stmt_execute($chk);
            mysqli_stmt_store_result($chk);
            if (mysqli_stmt_num_rows($chk) == 0) {
                $errors[] = "Baris $r: NPP $npp tidak ditemukan";
                mysqli_stmt_close($chk);
                continue;
            }
            mysqli_stmt_close($chk);

            $monthly_data[$key] = [
                'npp' => $npp,
                'periode' => $periode,
                'total_titik' => 0,
                'target_titik' => 0,
                'total_denda' => 0,
                'total_uang_makan' => 0,
                'total_insentif_full' => 0,
                'total_lembur_ops' => 0,
                'total_lembur_ab' => 0,
                'total_lembur_lain' => 0,
                'dates' => []
            ];
        }

        if (in_array($tanggal, $monthly_data[$key]['dates'])) {
            $errors[] = "Baris $r: NPP $npp tanggal $tanggal duplikat, dilewati";
            continue;
        }
        $monthly_data[$key]['dates'][] = $tanggal;

        // Accumulate titik
        $monthly_data[$key]['total_titik'] += $aktual;
        $monthly_data[$key]['target_titik'] += $target;

        // Calculate daily values
        $denda_harian = 0;
        $uang_makan_harian = 0;
        $insentif_full_harian = 0;

        if ($jam_masuk !== null && $jam_keluar !== null) {
            // Check Hadir_Full
            $masuk_ts = strtotime($jam_masuk);
            $keluar_ts = strtotime($jam_keluar);
            $standar_masuk_ts = strtotime($JAM_STANDAR_MASUK);
            $standar_keluar_ts = strtotime($JAM_STANDAR_KELUAR);
            
            $hadir_full = ($masuk_ts <= $standar_masuk_ts && $keluar_ts >= $standar_keluar_ts);
            
            if ($hadir_full) {
                $uang_makan_harian = $UANG_MAKAN_FULL;
                $insentif_full_harian = $INSENTIF_FULL_MASUK;
            }

            // Calculate Telat
            if ($masuk_ts > $standar_masuk_ts) {
                $selisih_detik = $masuk_ts - $standar_masuk_ts;
                $menit_telat = floor($selisih_detik / 60);
                $denda_harian = $menit_telat * $DENDA_PER_MENIT;
            }
        }

        $monthly_data[$key]['total_denda'] += $denda_harian;
        $monthly_data[$key]['total_uang_makan'] += $uang_makan_harian;
        $monthly_data[$key]['total_insentif_full'] += $insentif_full_harian;

        // Accumulate lembur
        $monthly_data[$key]['total_lembur_ops'] += ($lembur_ops * $RATE_LEMBUR_OPERASIONAL);
        $monthly_data[$key]['total_lembur_ab'] += ($lembur_ab * $RATE_LEMBUR_AMBIL_BARANG);
        $monthly_data[$key]['total_lembur_lain'] += ($lembur_lain * $RATE_LEMBUR_LAINNYA);
    }

    // Insert aggregated monthly records
    $upsert_sql = "INSERT INTO transaksi_insentif_kurir 
        (npp, periode, total_titik, target_titik, bonus_insentif, denda_telat, potongan_makan, uang_lembur, jumlah_dibayarkan, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
        total_titik=VALUES(total_titik), target_titik=VALUES(target_titik), bonus_insentif=VALUES(bonus_insentif),
        denda_telat=VALUES(denda_telat), potongan_makan=VALUES(potongan_makan), uang_lembur=VALUES(uang_lembur),
        jumlah_dibayarkan=VALUES(jumlah_dibayarkan), updated_at=NOW()";
    $upsert_stmt = mysqli_prepare($conn, $upsert_sql);
    if (!$upsert_stmt) throw new Exception('Prepare failed: ' . mysqli_error($conn));

    foreach ($monthly_data as $key => $data) {
        $npp = $data['npp'];
        $periode = $data['periode'];
        $total_titik = $data['total_titik'];
        $target_titik = $data['target_titik'];
        
        // Calculate Insentif Titik Tambahan (monthly)
        $insentif_titik = 0;
        if ($total_titik > $target_titik) {
            $selisih = $total_titik - $target_titik;
            $insentif_titik = $selisih * $RATE_TITIK_TAMBAHAN;
        }

        // Total bonus = Uang Makan + Insentif Full + Insentif Titik
        $bonus_insentif = $data['total_uang_makan'] + $data['total_insentif_full'] + $insentif_titik;
        
        // Total lembur
        $uang_lembur = $data['total_lembur_ops'] + $data['total_lembur_ab'] + $data['total_lembur_lain'];
        
        $denda_telat = $data['total_denda'];
        $potongan_makan = 0; // not used in this business logic
        
        // Total = (Uang_Makan + Insentif_Full + Insentif_Titik + Lembur) - Denda
        $jumlah_dibayarkan = $bonus_insentif + $uang_lembur - $denda_telat;

        if (!mysqli_stmt_bind_param(
            $upsert_stmt,
            'ssiiiiiii',
            $npp,
            $periode,
            $total_titik,
            $target_titik,
            $bonus_insentif,
            $denda_telat,
            $potongan_makan,
            $uang_lembur,
            $jumlah_dibayarkan
        )) {
            $errors[] = "NPP $npp periode $periode: Bind error - " . mysqli_error($conn);
            continue;
        }

        if (!mysqli_stmt_execute($upsert_stmt)) {
            $errors[] = "NPP $npp periode $periode: DB error - " . mysqli_stmt_error($upsert_stmt);
        } else {
            $success++;
            $hari_kerja = count($data['dates']);
            $errors[] = "✓ NPP $npp periode $periode: {$hari_kerja} hari, Bonus=Rp".number_format($bonus_insentif).", Lembur=Rp".number_format($uang_lembur);
        }
    }

    mysqli_stmt_close($upsert_stmt);

    if ($success > 0) {
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = "Berhasil memproses $success transaksi insentif karyawan!";
    } else {
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_message'] = 'Tidak ada data yang berhasil diproses.';
    }
    if (!empty($errors)) $_SESSION['alert_details'] = implode("<br>", $errors);

} catch (Exception $e) {
    $_SESSION['alert_type'] = 'danger';
    $_SESSION['alert_message'] = 'Error: ' . $e->getMessage();
    $_SESSION['alert_details'] = $e->__toString();
}

header('Location: insentif_karyawan_list.php');
exit();
?>
