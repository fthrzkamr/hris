<?php
include("sess_check.php");
include("../dist/config/koneksi.php");

// Check if file is uploaded
if (!isset($_FILES['excel_file'])) {
    $_SESSION['alert_type'] = "danger";
    $_SESSION['alert_message'] = "Tidak ada file yang diupload!";
    header("Location: insentif_kurir_upload.php");
    exit();
}

// Load PhpSpreadsheet
require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$file = $_FILES['excel_file'];
$file_name = $file['name'];
$file_tmp = $file['tmp_name'];
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Check PHP upload errors
if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['alert_type'] = 'danger';
    $_SESSION['alert_message'] = 'Upload gagal: Kesalahan pada proses upload file.';
    $phpFileUploadErrors = array(
        UPLOAD_ERR_INI_SIZE => 'File terlalu besar (UPLOAD_ERR_INI_SIZE).',
        UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (UPLOAD_ERR_FORM_SIZE).',
        UPLOAD_ERR_PARTIAL => 'File hanya ter-upload sebagian (UPLOAD_ERR_PARTIAL).',
        UPLOAD_ERR_NO_FILE => 'Tidak ada file yang dipilih (UPLOAD_ERR_NO_FILE).',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara hilang (UPLOAD_ERR_NO_TMP_DIR).',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis ke disk (UPLOAD_ERR_CANT_WRITE).',
        UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP (UPLOAD_ERR_EXTENSION).',
    );
    $code = $file['error'];
    $_SESSION['alert_details'] = isset($phpFileUploadErrors[$code]) ? $phpFileUploadErrors[$code] : ('Unknown upload error: ' . $code);
    header("Location: insentif_kurir_upload.php");
    exit();
}

// Validate file extension
if (!in_array($file_ext, ['xls', 'xlsx'])) {
    $_SESSION['alert_type'] = "danger";
    $_SESSION['alert_message'] = "Format file tidak valid! Gunakan file .xls atau .xlsx";
    header("Location: insentif_kurir_upload.php");
    exit();
}

// Helper to extract setting value
function _setting_value($row) {
    // Prefer monetary value if present (nominal_rp), otherwise numeric value, then time value.
    if (isset($row['nominal_rp']) && $row['nominal_rp'] !== null && $row['nominal_rp'] !== '' && floatval($row['nominal_rp']) != 0.0) {
        return (int)$row['nominal_rp'];
    }
    if (isset($row['nilai_angka']) && $row['nilai_angka'] !== null && $row['nilai_angka'] !== '') {
        return (int)$row['nilai_angka'];
    }
    if (isset($row['nilai_waktu']) && $row['nilai_waktu'] !== null && $row['nilai_waktu'] !== '') {
        return $row['nilai_waktu'];
    }
    return null;
}

try {
    // ========================================
    // 1. Load settings with GUARANTEED defaults (SAFETY)
    // ========================================
    $settings = [
        'RATE_LEMBUR_OPERASIONAL' => 30000,
        'RATE_LEMBUR_AMBIL_BARANG' => 50000,
        'RATE_LEMBUR_LAINNYA' => 30000,
        'JAM_MASUK_STANDAR' => '09:15:00',
        'DENDA_TELAT_PER_MENIT' => 1000,
        'POTONGAN_MAKAN_PER_HARI' => 15000,
        'UANG_MAKAN_BULANAN' => 300000,
        'BATAS_ATAS_BONUS_TITIK' => 25,
        'RATE_PER_TITIK_LEBIH' => 20000,
        'BONUS_FULL_HADIR' => 250000,
    ];

    // Override with database values if available
    $rs = mysqli_query($conn, "SELECT nama_variabel, nilai_angka, nominal_rp, nilai_waktu FROM pengaturan_insentif_kurir");
    if ($rs) {
        while ($r = mysqli_fetch_assoc($rs)) {
            $k = strtoupper(trim($r['nama_variabel']));
            $v = _setting_value($r);
            if ($v !== null && $k !== '') {
                $settings[$k] = $v; // Override default with DB value
            }
        }
    }
    // Map alternate/legacy variable names to canonical keys used in code
    // DB might store JAM_MASUK_KURIR but code expects JAM_MASUK_STANDAR
    if (isset($settings['JAM_MASUK_KURIR']) && !empty($settings['JAM_MASUK_KURIR'])) {
        $settings['JAM_MASUK_STANDAR'] = $settings['JAM_MASUK_KURIR'];
    }
    // Ensure monthly base makan exists (display only) — default provided above
    if (!isset($settings['UANG_MAKAN_BULANAN'])) {
        $settings['UANG_MAKAN_BULANAN'] = 300000;
    }
    
    // DEBUGGING: Ensure all settings are present
    $required_settings = ['JAM_MASUK_STANDAR', 'DENDA_TELAT_PER_MENIT', 'POTONGAN_MAKAN_PER_HARI', 'BATAS_ATAS_BONUS_TITIK', 'RATE_PER_TITIK_LEBIH', 'UANG_MAKAN_BULANAN', 'RATE_LEMBUR_OPERASIONAL', 'RATE_LEMBUR_AMBIL_BARANG', 'RATE_LEMBUR_LAINNYA', 'BONUS_FULL_HADIR'];
    foreach ($required_settings as $key) {
        if (!isset($settings[$key]) || $settings[$key] === null) {
            throw new Exception("Setting '$key' tidak ditemukan atau bernilai NULL!");
        }
    }

    // 2. Prepare statements
    // NOTE: We will read `lembur` counts and `is_cuti` directly from the uploaded Excel file
    // instead of querying the existing (inconsistent) tables. This improves auditability
    // and avoids double-counting when DB records are not reliable.

    // Insert daily row into absensi_kurir WITH ALL CALCULATED FIELDS (audit trail)
    $insert_absensi_sql = "INSERT INTO absensi_kurir 
        (npp, tanggal_absen, jam_absen, jam_masuk, jam_pulang, jenis_tugas, 
         total_aktual_titik, target_titik, is_hadir, is_late, menit_terlambat, 
         insentif_titik, denda_telat, uang_makan, uang_lembur, grand_total_harian, is_cuti, 
         lembur_operasional, lembur_ambil_barang, lembur_lainnya, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
        jam_absen=VALUES(jam_absen), jam_masuk=VALUES(jam_masuk), jam_pulang=VALUES(jam_pulang), 
        jenis_tugas=VALUES(jenis_tugas), total_aktual_titik=VALUES(total_aktual_titik), 
        target_titik=VALUES(target_titik), is_hadir=VALUES(is_hadir), is_late=VALUES(is_late), 
        menit_terlambat=VALUES(menit_terlambat), insentif_titik=VALUES(insentif_titik), 
        denda_telat=VALUES(denda_telat), uang_makan=VALUES(uang_makan), uang_lembur=VALUES(uang_lembur), 
        grand_total_harian=VALUES(grand_total_harian), is_cuti=VALUES(is_cuti), 
        lembur_operasional=VALUES(lembur_operasional), lembur_ambil_barang=VALUES(lembur_ambil_barang), lembur_lainnya=VALUES(lembur_lainnya), 
        updated_at=NOW()";
    $insert_absensi_stmt = mysqli_prepare($conn, $insert_absensi_sql);
    if (!$insert_absensi_stmt) throw new Exception('Prepare insert absensi failed: ' . mysqli_error($conn));

    // 3. Load Excel spreadsheet and AGGREGATE by (npp, periode)
    $spreadsheet = IOFactory::load($file_tmp);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    $highestColumnIndex = Coordinate::columnIndexFromString($worksheet->getHighestColumn());

    // Default column map (fallback if header names not provided)
    $colMap = [
        'npp' => 'A', 'tanggal' => 'B', 'jam' => 'C', 'jenis_tugas' => 'D',
        'aktual' => 'E', 'target' => 'F', 'lembur_operasional' => 'G',
        'lembur_ambil' => 'H', 'lembur_lain' => 'I', 'is_cuti' => 'J'
    ];

    // If header row exists, detect columns by header text (flexible names)
    for ($ci = 1; $ci <= $highestColumnIndex; $ci++) {
        $colLetter = Coordinate::stringFromColumnIndex($ci);
        $hdr = strtolower(trim((string)$worksheet->getCell($colLetter . '1')->getValue()));
        if ($hdr === '') continue;
        $norm = preg_replace('/[^a-z0-9]/', '', $hdr);
        if (strpos($norm, 'npp') !== false) $colMap['npp'] = $colLetter;
        elseif (strpos($norm, 'tanggal') !== false || strpos($norm, 'date') !== false) $colMap['tanggal'] = $colLetter;
        elseif (strpos($norm, 'jam') === 0 || strpos($norm, 'time') !== false) $colMap['jam'] = $colLetter;
        elseif (strpos($norm, 'jenistugas') !== false || strpos($norm, 'jenis') !== false) $colMap['jenis_tugas'] = $colLetter;
        elseif (strpos($norm, 'aktual') !== false || strpos($norm, 'actual') !== false) $colMap['aktual'] = $colLetter;
        elseif (strpos($norm, 'target') !== false) $colMap['target'] = $colLetter;
        elseif (strpos($norm, 'operasional') !== false) $colMap['lembur_operasional'] = $colLetter;
        elseif (strpos($norm, 'ambil') !== false || strpos($norm, 'pickup') !== false) $colMap['lembur_ambil'] = $colLetter;
        elseif (strpos($norm, 'lain') !== false || strpos($norm, 'lainnya') !== false) $colMap['lembur_lain'] = $colLetter;
        elseif (strpos($norm, 'cuti') !== false || strpos($norm, 'iscuti') !== false || strpos($norm, 'izin') !== false) $colMap['is_cuti'] = $colLetter;
    }

    $success_count = 0;
    $error_count = 0;
    $error_messages = [];
    
    // Accumulator for monthly aggregation: key = "npp|YYYY-MM"
    $monthly_data = [];

    // STEP 3a: Read all Excel rows and accumulate by (npp, periode)
    for ($r = 2; $r <= $highestRow; $r++) {
        $npp = trim((string)$worksheet->getCell('A' . $r)->getValue());
        $tanggal_raw = $worksheet->getCell('B' . $r)->getValue();
        $jam_raw = trim((string)$worksheet->getCell('C' . $r)->getValue());
        $jenis_tugas = trim((string)$worksheet->getCell('D' . $r)->getValue());
        $aktual_raw = $worksheet->getCell('E' . $r)->getValue();
        $target_raw = $worksheet->getCell('F' . $r)->getValue();

        if ($npp === '') continue;

        // Parse tanggal (Excel date or text like MM/DD/YYYY or YYYY-MM-DD)
        $tanggal = null;
        if ($tanggal_raw === null || $tanggal_raw === '') {
            $error_messages[] = "Baris $r: Tanggal kosong";
            $error_count++;
            continue;
        }
        if (is_numeric($tanggal_raw)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject($tanggal_raw);
                $tanggal = $dt->format('Y-m-d');
            } catch (Exception $e) {
                $ts = strtotime((string)$tanggal_raw);
                if ($ts === false) {
                    $error_messages[] = "Baris $r: Format tanggal tidak dikenali ($tanggal_raw)";
                    $error_count++;
                    continue;
                }
                $tanggal = date('Y-m-d', $ts);
            }
        } else {
            $ts = strtotime((string)$tanggal_raw);
            if ($ts === false) {
                $error_messages[] = "Baris $r: Format tanggal tidak dikenali ($tanggal_raw)";
                $error_count++;
                continue;
            }
            $tanggal = date('Y-m-d', $ts);
        }
        
        $periode = date('Y-m', strtotime($tanggal)); // Extract YYYY-MM

        // Parse jam_absen (extract jam_masuk and jam_pulang)
        $jam_masuk = null;
        $jam_pulang = null;
        if ($jam_raw !== '') {
            if (is_numeric($jam_raw)) {
                // Excel time format
                try {
                    $jd = ExcelDate::excelToDateTimeObject($jam_raw);
                    $jam_masuk = $jd->format('H:i:s');
                } catch (Exception $e) {
                    $tsj = strtotime((string)$jam_raw);
                    if ($tsj !== false) $jam_masuk = date('H:i:s', $tsj);
                }
            } else {
                // Text format: "08:49 16:02" or "08:49"
                $parts = explode(' ', $jam_raw);
                $jam_masuk_str = isset($parts[0]) && trim($parts[0]) !== '' ? trim($parts[0]) : null;
                $jam_pulang_str = isset($parts[1]) && trim($parts[1]) !== '' && trim($parts[1]) !== '00:00' ? trim($parts[1]) : null;
                
                if ($jam_masuk_str !== null) {
                    $tsj = strtotime($jam_masuk_str);
                    if ($tsj !== false) {
                        $jam_masuk = date('H:i:s', $tsj);
                    }
                }
                if ($jam_pulang_str !== null) {
                    $tsj = strtotime($jam_pulang_str);
                    if ($tsj !== false) {
                        $jam_pulang = date('H:i:s', $tsj);
                    }
                }
            }
        }

        // Parse numeric titik
        $aktual = intval($aktual_raw);
        $target = intval($target_raw);

        // Validate employee exists (once per NPP)
        $key = $npp . '|' . $periode;
        if (!isset($monthly_data[$key])) {
            $chk = mysqli_prepare($conn, "SELECT npp FROM employee WHERE npp = ? LIMIT 1");
            mysqli_stmt_bind_param($chk, 's', $npp);
            mysqli_stmt_execute($chk);
            mysqli_stmt_store_result($chk);
            if (mysqli_stmt_num_rows($chk) == 0) {
                $error_messages[] = "Baris $r: NPP $npp tidak ditemukan";
                $error_count++;
                mysqli_stmt_close($chk);
                continue;
            }
            mysqli_stmt_close($chk);
            
            // Initialize accumulator
            $monthly_data[$key] = [
                'npp' => $npp,
                'periode' => $periode,
                'total_titik' => 0,
                'target_titik' => 0,
                'hari_hadir' => 0,
                'hari_alpha' => 0,
                'hari_cuti' => 0,
                'hari_telat' => 0,
                'total_lembur' => 0,
                'total_denda' => 0,
                'total_makan' => 0,
                'dates' => [] // track which dates we've seen
            ];
        }

        // Skip if this tanggal was already processed (duplicate in Excel)
        if (in_array($tanggal, $monthly_data[$key]['dates'])) {
            $error_messages[] = "Baris $r: Data untuk NPP $npp tanggal $tanggal sudah ada (duplikat), dilewati";
            continue;
        }
        $monthly_data[$key]['dates'][] = $tanggal;

        // ============================================================
        // STEP ETL 1: CALCULATE DAILY FINANCIAL COMPONENTS
        // ============================================================
        
        // Initialize daily variables
        $is_hadir = 0;
        $is_late = 0;
        $menit_terlambat = 0;
        $denda_telat_harian = 0;
        $uang_makan_harian = 0;
        $uang_lembur_harian = 0;
        $insentif_titik_harian = 0;
        $is_cuti = 0;

        // Read lembur counts and cuti flag from Excel (columns detected above)
        $lembur_operasional = intval($worksheet->getCell($colMap['lembur_operasional'] . $r)->getValue() ?? 0);
        $lembur_ambil = intval($worksheet->getCell($colMap['lembur_ambil'] . $r)->getValue() ?? 0);
        $lembur_lain = intval($worksheet->getCell($colMap['lembur_lain'] . $r)->getValue() ?? 0);

        $rate_op = intval($settings['RATE_LEMBUR_OPERASIONAL']);
        $rate_ambil = intval($settings['RATE_LEMBUR_AMBIL_BARANG']);
        $rate_lain = intval($settings['RATE_LEMBUR_LAINNYA']);

        $uang_lembur_harian = ($lembur_operasional * $rate_op) + ($lembur_ambil * $rate_ambil) + ($lembur_lain * $rate_lain);

        // Check attendance status and calculate penalties/allowances
        if ($jam_masuk !== null) {
            // PRESENT (Hadir)
            $is_hadir = 1;
            $uang_makan_harian = intval($settings['POTONGAN_MAKAN_PER_HARI']); // Allowance
            
            // Check if late
            $jam_standar_str = $settings['JAM_MASUK_STANDAR'];
            $waktu_batas = strtotime($jam_standar_str);
            $waktu_masuk = strtotime($jam_masuk);
            
            if ($waktu_masuk > $waktu_batas) {
                $is_late = 1;
                $selisih_detik = $waktu_masuk - $waktu_batas;
                $menit_terlambat = floor($selisih_detik / 60);
                $rate_denda = intval($settings['DENDA_TELAT_PER_MENIT']);
                $denda_telat_harian = ($menit_terlambat * $rate_denda);
            }
        } else {
            // ABSENT: determine cuti from Excel column (if provided)
            $is_cuti_excel = 0;
            $val_cuti = trim((string)$worksheet->getCell($colMap['is_cuti'] . $r)->getValue());
            if ($val_cuti !== '') {
                $v = strtolower($val_cuti);
                if (in_array($v, ['1','y','yes','true','cuti','izin'])) $is_cuti_excel = 1;
            }

            if ($is_cuti_excel) {
                // On CUTI: no allowance, no penalties
                $is_cuti = 1;
                $uang_makan_harian = 0;
            } else {
                // ALPHA (absent without leave): apply penalty
                $is_hadir = 0;
                $uang_makan_harian = -intval($settings['POTONGAN_MAKAN_PER_HARI']); // Penalty (negative)
            }
        }

        // NOTE: Bonus titik dihitung BULANAN (bukan harian), jadi insentif_titik_harian = 0
        // Harian hanya mencatat total_aktual_titik dan target_titik untuk agregasi bulanan
        $insentif_titik_harian = 0;

        // Calculate grand total for this day (TANPA bonus titik harian)
        $grand_total_harian = $uang_makan_harian + $uang_lembur_harian - $denda_telat_harian;

        // ============================================================
        // STEP ETL 2: SAVE DAILY RECORD TO absensi_kurir (WITH ALL CALCULATIONS)
        // ============================================================
        $jam_absen_str = $jam_raw; // Keep original format from Excel
        mysqli_stmt_bind_param(
            $insert_absensi_stmt,
            'ssssssiiiiidddddiiii',
            $npp, $tanggal, $jam_absen_str, $jam_masuk, $jam_pulang, $jenis_tugas,
            $aktual, $target, $is_hadir, $is_late, $menit_terlambat,
            $insentif_titik_harian, $denda_telat_harian, $uang_makan_harian,
            $uang_lembur_harian, $grand_total_harian, $is_cuti,
            $lembur_operasional, $lembur_ambil, $lembur_lain
        );
        if (!mysqli_stmt_execute($insert_absensi_stmt)) {
            $error_messages[] = "Baris $r: Gagal menyimpan ke absensi_kurir: " . mysqli_stmt_error($insert_absensi_stmt);
            // Continue processing for aggregation even if insert fails
        }

        // ============================================================
        // STEP ETL 3: ACCUMULATE FOR MONTHLY AGGREGATION
        // ============================================================
        $monthly_data[$key]['total_titik'] += $aktual;
        $monthly_data[$key]['target_titik'] += $target;
        $monthly_data[$key]['total_lembur'] += $uang_lembur_harian;
        $monthly_data[$key]['total_denda'] += $denda_telat_harian;
        $monthly_data[$key]['total_makan'] += $uang_makan_harian;
        
        if ($is_hadir) {
            $monthly_data[$key]['hari_hadir']++;
            if ($is_late) {
                $monthly_data[$key]['hari_telat']++;
            }
        } elseif ($is_cuti) {
            $monthly_data[$key]['hari_cuti']++;
        } else {
            $monthly_data[$key]['hari_alpha']++;
        }
    }

    // lembur_stmt removed (we used detailed lembur query)
    mysqli_stmt_close($insert_absensi_stmt);

    // STEP 3b: Calculate monthly bonus and insert aggregated records
    $upsert_sql = "INSERT INTO transaksi_insentif_kurir 
        (npp, periode, total_titik, target_titik, bonus_insentif_titik, bonus_insentif_full_masuk, denda_telat, uang_makan, uang_lembur, jumlah_dibayarkan, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
        total_titik=VALUES(total_titik), target_titik=VALUES(target_titik), bonus_insentif_titik=VALUES(bonus_insentif_titik),
        bonus_insentif_full_masuk=VALUES(bonus_insentif_full_masuk), denda_telat=VALUES(denda_telat), 
        uang_makan=VALUES(uang_makan), uang_lembur=VALUES(uang_lembur),
        jumlah_dibayarkan=VALUES(jumlah_dibayarkan), updated_at=NOW()";
    $upsert_stmt = mysqli_prepare($conn, $upsert_sql);
    if (!$upsert_stmt) throw new Exception('Prepare insert failed: ' . mysqli_error($conn));

    foreach ($monthly_data as $key => $data) {
        $npp = $data['npp'];
        $periode = $data['periode'];
        $total_titik = $data['total_titik'];
        $target_titik = $data['target_titik'];
        $total_lembur = $data['total_lembur'];
        $total_denda = $data['total_denda'];
        $total_makan = $data['total_makan'];
        
        // CALCULATE MONTHLY BONUS based on total achievement
        // 1. Bonus Titik: BATAS_ATAS_BONUS_TITIK (25) adalah batas TOTAL BULAN, bukan per hari!
        $bonus_titik = 0;
        $selisih = $total_titik - $target_titik;
        
        if ($selisih > 0) {
            $batas_bonus_bulanan = intval($settings['BATAS_ATAS_BONUS_TITIK']); // Max 25 titik per bulan
            $rate_bonus = intval($settings['RATE_PER_TITIK_LEBIH']); // 20000 per titik
            $titik_bonus = min($selisih, $batas_bonus_bulanan); // Cap di 25
            $bonus_titik = $titik_bonus * $rate_bonus; // Max 25 × 20,000 = 500,000/bulan
        }
        
        // 2. Bonus Full Hadir: Hanya jika tidak ada alpha *dan* tidak ada telat sebulan
        $bonus_full_hadir = 0;
        $hari_alpha = $data['hari_alpha'];
        $hari_telat = isset($data['hari_telat']) ? $data['hari_telat'] : 0;
        if ($hari_alpha == 0 && $hari_telat == 0) {
            $bonus_full_hadir = intval($settings['BONUS_FULL_HADIR']); // 250,000
        }
        
        // Total bonus = bonus titik + bonus full hadir
        $bonus_insentif = $bonus_titik + $bonus_full_hadir;

        // Clamp monthly meal allowance to configured UANG_MAKAN_BULANAN
        $uang_makan_bulanan = intval($settings['UANG_MAKAN_BULANAN']);
        $original_total_makan = $total_makan;
        if ($total_makan > $uang_makan_bulanan) {
            $total_makan = $uang_makan_bulanan;
        }

        // Calculate total payment
        $jumlah_dibayarkan = $total_makan + $bonus_insentif + $total_lembur - $total_denda;
        
        // Bind and execute
        if (!mysqli_stmt_bind_param(
            $upsert_stmt,
            'ssiiiiiiii',
            $npp,
            $periode,
            $total_titik,
            $target_titik,
            $bonus_titik,
            $bonus_full_hadir,
            $total_denda,
            $total_makan,
            $total_lembur,
            $jumlah_dibayarkan
        )) {
            $error_messages[] = "NPP $npp periode $periode: Gagal bind: " . mysqli_error($conn);
            $error_count++;
            continue;
        }

        if (!mysqli_stmt_execute($upsert_stmt)) {
            $error_messages[] = "NPP $npp periode $periode: Gagal menyimpan: " . mysqli_stmt_error($upsert_stmt);
            $error_count++;
        } else {
            $success_count++;
            $hari_kerja = count($data['dates']);
            $hari_alpha = $data['hari_alpha'];
            $hari_cuti = $data['hari_cuti'];
            $bonus_info = "Bonus: ";
            if ($bonus_titik > 0) $bonus_info .= "Titik=" . number_format($bonus_titik);
            if ($bonus_full_hadir > 0) $bonus_info .= ($bonus_titik > 0 ? " + " : "") . "Full Hadir=" . number_format($bonus_full_hadir);
            if ($bonus_insentif == 0) $bonus_info .= "0";
            $makan_info = number_format($original_total_makan);
            if ($original_total_makan != $total_makan) $makan_info .= " -> " . number_format($total_makan);
            $error_messages[] = "✓ NPP $npp periode $periode: {$hari_kerja} hari (Hadir={$data['hari_hadir']}, Telat={$data['hari_telat']}, Alpha={$hari_alpha}, Cuti={$hari_cuti}), Total Titik={$total_titik}, Target={$target_titik}, Makan={$makan_info}, {$bonus_info}, Total Bayar=".number_format($jumlah_dibayarkan);
        }
    }

    mysqli_stmt_close($upsert_stmt);

    if ($success_count > 0) {
        $_SESSION['alert_type'] = "success";
        $_SESSION['alert_message'] = "Berhasil memproses $success_count transaksi bulanan! Data harian tersimpan di absensi_kurir.";
        if ($error_count > 0) $_SESSION['alert_message'] .= " ($error_count gagal)";
    } else {
        $_SESSION['alert_type'] = "danger";
        $_SESSION['alert_message'] = "Tidak ada data yang berhasil diproses!";
    }
    if (!empty($error_messages)) $_SESSION['alert_details'] = implode("<br>", $error_messages);

} catch (Exception $e) {
    $_SESSION['alert_type'] = "danger";
    $_SESSION['alert_message'] = "Error: " . $e->getMessage();
    $_SESSION['alert_details'] = $e->__toString();
}

header("Location: insentif_kurir_list.php");
exit();
?>
