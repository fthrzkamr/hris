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
function _setting_value($row)
{
    // Prefer time values when present (nilai_waktu), otherwise monetary (nominal_rp) if non-zero,
    // then numeric value (nilai_angka). This matches the schema where time settings store
    // the jam masuk/pulang in `nilai_waktu` while nominal_rp/nilai_angka may be zero.
    if (isset($row['nilai_waktu']) && $row['nilai_waktu'] !== null && $row['nilai_waktu'] !== '') {
        return $row['nilai_waktu'];
    }
    if (isset($row['nominal_rp']) && $row['nominal_rp'] !== null && $row['nominal_rp'] !== '' && floatval($row['nominal_rp']) != 0.0) {
        return (int) $row['nominal_rp'];
    }
    if (isset($row['nilai_angka']) && $row['nilai_angka'] !== null && $row['nilai_angka'] !== '') {
        return (int) $row['nilai_angka'];
    }
    return null;
}

try {
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
    // If a specific JAM_MASUK_KURIR exists in DB (time), copy it to the canonical key.
    if (isset($settings['JAM_MASUK_KURIR']) && $settings['JAM_MASUK_KURIR'] !== null && $settings['JAM_MASUK_KURIR'] !== '') {
        $settings['JAM_MASUK_STANDAR'] = $settings['JAM_MASUK_KURIR'];
    }
    // Ensure monthly base makan exists (display only) — default provided above
    if (!isset($settings['UANG_MAKAN_BULANAN'])) {
        $settings['UANG_MAKAN_BULANAN'] = 300000;
    }

    // DEBUGGING: Ensure all settings are present
    $required_settings = ['JAM_MASUK_STANDAR', 'DENDA_TELAT_PER_MENIT', 'POTONGAN_MAKAN_PER_HARI', 'BATAS_ATAS_BONUS_TITIK', 'RATE_PER_TITIK_LEBIH', 'UANG_MAKAN_BULANAN', 'RATE_LEMBUR_OPERASIONAL', 'RATE_LEMBUR_AMBIL_BARANG', 'RATE_LEMBUR_LAINNYA', 'BONUS_FULL_HADIR', 'BONUS_FULL_HADIR_MOTOR'];
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
         insentif_titik, denda_telat, uang_makan, uang_lembur, grand_total_harian, is_cuti, keterangan_cuti, 
         lembur_operasional, lembur_ambil_barang, lembur_lainnya, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
        jam_absen=VALUES(jam_absen), jam_masuk=VALUES(jam_masuk), jam_pulang=VALUES(jam_pulang), 
        jenis_tugas=VALUES(jenis_tugas), total_aktual_titik=VALUES(total_aktual_titik), 
        target_titik=VALUES(target_titik), is_hadir=VALUES(is_hadir), is_late=VALUES(is_late), 
        menit_terlambat=VALUES(menit_terlambat), insentif_titik=VALUES(insentif_titik), 
        denda_telat=VALUES(denda_telat), uang_makan=VALUES(uang_makan), uang_lembur=VALUES(uang_lembur), 
        grand_total_harian=VALUES(grand_total_harian), is_cuti=VALUES(is_cuti), keterangan_cuti=VALUES(keterangan_cuti), 
        lembur_operasional=VALUES(lembur_operasional), lembur_ambil_barang=VALUES(lembur_ambil_barang), lembur_lainnya=VALUES(lembur_lainnya), 
        updated_at=NOW()";
    $insert_absensi_stmt = mysqli_prepare($conn, $insert_absensi_sql);
    if (!$insert_absensi_stmt)
        throw new Exception('Prepare insert absensi failed: ' . mysqli_error($conn));

    // 3. Load Excel spreadsheet and AGGREGATE by (npp, periode)
    $spreadsheet = IOFactory::load($file_tmp);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    $highestColumnIndex = Coordinate::columnIndexFromString($worksheet->getHighestColumn());

    // Default column map (fallback if header names not provided)
    // NOTE: Lembur and cuti data are now queried from database tables, not from Excel
    $colMap = [
        'npp' => 'A',
        'tanggal' => 'B',
        'jam' => 'C',
        'jenis_tugas' => 'D',
        'aktual' => 'E',
        'target' => 'F'
    ];

    // If header row exists, detect columns by header text (flexible names)
    for ($ci = 1; $ci <= $highestColumnIndex; $ci++) {
        $colLetter = Coordinate::stringFromColumnIndex($ci);
        $hdr = strtolower(trim((string) $worksheet->getCell($colLetter . '1')->getValue()));
        if ($hdr === '')
            continue;
        $norm = preg_replace('/[^a-z0-9]/', '', $hdr);
        if (strpos($norm, 'npp') !== false)
            $colMap['npp'] = $colLetter;
        elseif (strpos($norm, 'tanggal') !== false || strpos($norm, 'date') !== false)
            $colMap['tanggal'] = $colLetter;
        elseif (strpos($norm, 'jam') === 0 || strpos($norm, 'time') !== false)
            $colMap['jam'] = $colLetter;
        elseif (strpos($norm, 'jenistugas') !== false || strpos($norm, 'jenis') !== false)
            $colMap['jenis_tugas'] = $colLetter;
        elseif (strpos($norm, 'aktual') !== false || strpos($norm, 'actual') !== false)
            $colMap['aktual'] = $colLetter;
        elseif (strpos($norm, 'target') !== false)
            $colMap['target'] = $colLetter;
    }

    $success_count = 0;
    $error_count = 0;
    $error_messages = [];

    // Accumulator for monthly aggregation: key = "npp|YYYY-MM"
    $monthly_data = [];

    // STEP 3a: Read all Excel rows and accumulate by (npp, periode)
    for ($r = 2; $r <= $highestRow; $r++) {
        $npp = trim((string) $worksheet->getCell('A' . $r)->getValue());
        $tanggal_raw = $worksheet->getCell('B' . $r)->getValue();
        $jam_raw = trim((string) $worksheet->getCell('C' . $r)->getValue());
        $jenis_tugas = trim((string) $worksheet->getCell('D' . $r)->getValue());
        $aktual_raw = $worksheet->getCell('E' . $r)->getValue();
        $target_raw = $worksheet->getCell('F' . $r)->getValue();

        if ($npp === '')
            continue;

        // Parse tanggal (Excel date numbers or various text formats like "1 December 2025", "2025-12-01", "12/01/2025", etc.)
        $tanggal = null;
        if ($tanggal_raw === null || $tanggal_raw === '') {
            $error_messages[] = "Baris $r: Tanggal kosong";
            $error_count++;
            continue;
        }

        // If Excel serial/date number, convert via PhpSpreadsheet
        if (is_numeric($tanggal_raw)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject($tanggal_raw);
                $tanggal = $dt->format('Y-m-d');
            } catch (Exception $e) {
                // fallback to strtotime on string representation
                $ts = strtotime((string) $tanggal_raw);
                if ($ts === false) {
                    $error_messages[] = "Baris $r: Format tanggal tidak dikenali ($tanggal_raw)";
                    $error_count++;
                    continue;
                }
                $tanggal = date('Y-m-d', $ts);
            }
        } else {
            $txt = trim((string) $tanggal_raw);

            // Normalize common Indonesian month names to English to support '1 December 2025' and '1 Desember 2025'
            $month_map = [
                'Januari' => 'January',
                'Februari' => 'February',
                'Maret' => 'March',
                'April' => 'April',
                'Mei' => 'May',
                'Juni' => 'June',
                'Juli' => 'July',
                'Agustus' => 'August',
                'September' => 'September',
                'Oktober' => 'October',
                'November' => 'November',
                'Desember' => 'December'
            ];
            $txt_norm = str_ireplace(array_keys($month_map), array_values($month_map), $txt);

            // Try several explicit formats first (day month year variations)
            $formats = ['j F Y', 'd F Y', 'j M Y', 'd M Y', 'Y-m-d', 'd-m-Y', 'm/d/Y', 'd/m/Y'];
            foreach ($formats as $fmt) {
                $dt = DateTime::createFromFormat($fmt, $txt_norm);
                if ($dt !== false) {
                    $tanggal = $dt->format('Y-m-d');
                    break;
                }
            }

            // Last resort: strtotime on normalized string
            if ($tanggal === null) {
                $ts = strtotime($txt_norm);
                if ($ts === false) {
                    $error_messages[] = "Baris $r: Format tanggal tidak dikenali ($tanggal_raw)";
                    $error_count++;
                    continue;
                }
                $tanggal = date('Y-m-d', $ts);
            }
        }

        $periode = date('Y-m', strtotime($tanggal)); // Extract YYYY-MM

        // tambahkan definisi tanggal_str agar query cuti/lembur menerima nilai yang benar
        $tanggal_str = $tanggal;

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
                    $tsj = strtotime((string) $jam_raw);
                    if ($tsj !== false)
                        $jam_masuk = date('H:i:s', $tsj);
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
                'total_menit_telat' => 0,
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
        $keterangan_cuti = '';

        // === DATABASE-DRIVEN: Query cuti from database (not from Excel) ===
        // Use direct query (works even if mysqlnd isn't enabled) and trim values
        $npp_q = mysqli_real_escape_string($conn, trim($npp));
        $tgl_q = mysqli_real_escape_string($conn, $tanggal_str);
        $cuti_sql = "SELECT keterangan, stt_cuti, tgl_awal, tgl_akhir FROM cuti 
            WHERE npp = '$npp_q' 
              AND '$tgl_q' BETWEEN tgl_awal AND tgl_akhir 
              AND stt_cuti != 'Rejected'";
        $cuti_keterangan_array = [];
        $cuti_rs = mysqli_query($conn, $cuti_sql);
        if ($cuti_rs) {
            while ($cuti_row = mysqli_fetch_assoc($cuti_rs)) {
                // debug: uncomment to collect details $_SESSION['debug'][] = $cuti_row;
                if (!empty($cuti_row['keterangan'])) $cuti_keterangan_array[] = trim($cuti_row['keterangan']);
                else $cuti_keterangan_array[] = '(' . ($cuti_row['stt_cuti'] ?? 'Unknown') . ')';
            }
            mysqli_free_result($cuti_rs);
        } else {
            // If query fails, capture error for troubleshooting
            $error_messages[] = "Baris $r: Query cuti gagal: " . mysqli_error($conn);
        }
        if (count($cuti_keterangan_array) > 0) {
            $is_cuti = 1;
            $keterangan_cuti = implode('; ', $cuti_keterangan_array);
        }

        // === DATABASE-DRIVEN: Query lembur from database (not from Excel) ===
        // Query lembur table: get all lembur records for this npp and date, status = 'Approved' only
        $lembur_operasional = 0;
        $lembur_ambil = 0;
        $lembur_lain = 0;

        $lembur_stmt = mysqli_prepare($conn, "SELECT tujuan_lembur, SUM(jumlah) as total FROM lembur WHERE npp = ? AND DATE(tgl_lembur) = ? AND status = 'Approved' GROUP BY tujuan_lembur");
        if ($lembur_stmt) {
            mysqli_stmt_bind_param($lembur_stmt, 'ss', $npp, $tanggal_str);
            mysqli_stmt_execute($lembur_stmt);
            $lembur_result = mysqli_stmt_get_result($lembur_stmt);
            while ($lembur_row = mysqli_fetch_assoc($lembur_result)) {
                $tujuan = strtolower(trim($lembur_row['tujuan_lembur']));
                $jumlah = intval($lembur_row['total']);
                // Map tujuan_lembur to categories (handle typo: 'Oprasional' vs 'Operasional')
                if (strpos($tujuan, 'operasional') !== false || strpos($tujuan, 'oprasional') !== false) {
                    $lembur_operasional += $jumlah;
                } elseif (strpos($tujuan, 'ambil') !== false || strpos($tujuan, 'barang') !== false) {
                    $lembur_ambil += $jumlah;
                } else {
                    $lembur_lain += $jumlah;
                }
            }
            mysqli_stmt_close($lembur_stmt);
        }

        // Calculate lembur payment using rates from settings
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

                // Exempt specific NPPs from late penalty (business rule)
                // Add other NPPs to this array to exempt them as needed
                $exempt_npp = array('22910033');
                if (in_array($npp, $exempt_npp, true)) {
                    $denda_telat_harian = 0;
                    $is_late = 0;
                    $menit_terlambat = 0;
                }
            }
        } else {
            // ABSENT: is_cuti already determined from database query above
            if ($is_cuti) {
                // On CUTI: treat like alpha for makan potongan (preserve is_cuti flag)
                $is_hadir = 0;
                $uang_makan_harian = -intval($settings['POTONGAN_MAKAN_PER_HARI']); // Penalty (negative) for cuti
            } else {
                // Tidak masuk tanpa cuti/sakit (alpha) -> LOGIKA ALPHA DIHILANGKAN
                // tidak ada potongan/uang makan otomatis, dan tidak dihitung ke hari apapun
                $is_hadir = 0;
                $uang_makan_harian = 0;
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
            'ssssssiiiiidddddisiii',
            $npp,
            $tanggal,
            $jam_absen_str,
            $jam_masuk,
            $jam_pulang,
            $jenis_tugas,
            $aktual,
            $target,
            $is_hadir,
            $is_late,
            $menit_terlambat,
            $insentif_titik_harian,
            $denda_telat_harian,
            $uang_makan_harian,
            $uang_lembur_harian,
            $grand_total_harian,
            $is_cuti,
            $keterangan_cuti,
            $lembur_operasional,
            $lembur_ambil,
            $lembur_lain
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

        // Akumulasi uang makan: HANYA potongan (negatif), TIDAK akumulasi allowance harian
        // Karena allowance bulanan adalah fix 300rb, hanya potongan alpha yang dikurangi
        if ($uang_makan_harian < 0) {
            $monthly_data[$key]['total_makan'] += $uang_makan_harian; // Akumulasi potongan (negatif)
        }

        if ($is_hadir) {
            $monthly_data[$key]['hari_hadir']++;
            if ($is_late) {
                $monthly_data[$key]['hari_telat']++;
                $monthly_data[$key]['total_menit_telat'] += $menit_terlambat; // Akumulasi menit keterlambatan
            }
        } elseif ($is_cuti) {
            $monthly_data[$key]['hari_cuti']++;
        } else {
            // alpha diabaikan (diminta hilangkan logika hari alpha)
        }
    }

    // lembur_stmt removed (we used detailed lembur query)
    mysqli_stmt_close($insert_absensi_stmt);

    // STEP 3b: Calculate monthly bonus and insert aggregated records (single upsert)
    $upsert_sql = "INSERT INTO transaksi_insentif_kurir 
        (npp, periode, total_titik, target_titik, bonus_insentif_titik, bonus_insentif_full_masuk, denda_telat, akumulasi_telat, potongan_makan, uang_makan, uang_lembur, jumlah_dibayarkan, hari_hadir, hari_telat, hari_cuti, hari_alpha, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
        total_titik=VALUES(total_titik), target_titik=VALUES(target_titik), bonus_insentif_titik=VALUES(bonus_insentif_titik),
        bonus_insentif_full_masuk=VALUES(bonus_insentif_full_masuk), denda_telat=VALUES(denda_telat), akumulasi_telat=VALUES(akumulasi_telat),
        potongan_makan=VALUES(potongan_makan), uang_makan=VALUES(uang_makan), uang_lembur=VALUES(uang_lembur),
        jumlah_dibayarkan=VALUES(jumlah_dibayarkan), hari_hadir=VALUES(hari_hadir), hari_telat=VALUES(hari_telat), hari_cuti=VALUES(hari_cuti), hari_alpha=VALUES(hari_alpha), updated_at=NOW()";
    $upsert_stmt = mysqli_prepare($conn, $upsert_sql);
    if (!$upsert_stmt)
        throw new Exception('Prepare insert failed: ' . mysqli_error($conn));

    // Cache employee cabang to avoid repeated queries
    $emp_cabang_cache = array();

    foreach ($monthly_data as $key => $data) {
        $npp = $data['npp'];
        $periode = $data['periode'];
        $total_titik = intval($data['total_titik']);
        $target_titik = intval($data['target_titik']);
        $total_lembur = intval($data['total_lembur']);
        $total_denda = intval($data['total_denda']);
        $total_makan = intval($data['total_makan']);

        $hari_hadir = intval($data['hari_hadir']);
        $hari_telat = intval($data['hari_telat']);
        $hari_cuti = intval($data['hari_cuti']);
        // hari_alpha tidak lagi dipakai (diminta dihilangkan), tetap 0 untuk kompatibilitas kolom
        $hari_alpha = 0;
        $akumulasi_menit_telat = isset($data['total_menit_telat']) ? intval($data['total_menit_telat']) : 0;

        // 1. BONUS TITIK
        $bonus_titik = 0;
        $selisih = $total_titik - $target_titik;
        if ($selisih > 0) {
            $batas_bonus_bulanan = intval($settings['BATAS_ATAS_BONUS_TITIK']);
            $rate_bonus = intval($settings['RATE_PER_TITIK_LEBIH']);
            $titik_bonus = min($selisih, $batas_bonus_bulanan);
            $bonus_titik = $titik_bonus * $rate_bonus;
        }

        // Resolve tipe per NPP (kategori_npp preferred, fallback employee)
        $tipe = null;
        $q = mysqli_query($conn, "SELECT tipe FROM kategori_npp WHERE npp='" . mysqli_real_escape_string($conn, $npp) . "' LIMIT 1");
        if ($q && mysqli_num_rows($q) > 0) {
            $qr = mysqli_fetch_assoc($q);
            $tipe = $qr['tipe'];
        }
        if (empty($tipe)) {
            $qe = mysqli_query($conn, "SELECT jabatan, nama_bagian FROM employee WHERE npp='" . mysqli_real_escape_string($conn, $npp) . "' LIMIT 1");
            if ($qe && mysqli_num_rows($qe) > 0) {
                $er = mysqli_fetch_assoc($qe);
                $jab = strtolower($er['jabatan'] ?? '');
                $nb = strtolower($er['nama_bagian'] ?? '');
                if (strpos($jab, 'driver') !== false || strpos($jab, 'sopir') !== false || strpos($nb, 'mobil') !== false || strpos($jab, 'mobil') !== false) {
                    $tipe = 'mobil';
                } else {
                    $tipe = 'motor';
                }
            } else {
                $tipe = 'motor';
            }
        }

        // 2. BONUS FULL HADIR (per tipe)
        $bonus_full_hadir = 0;
        if ($hari_cuti === 0 && $hari_telat === 0) {
            if ($tipe === 'motor') {
                $bonus_full_hadir = intval($settings['BONUS_FULL_HADIR_MOTOR']);
            } else {
                $bonus_full_hadir = intval($settings['BONUS_FULL_HADIR']);
            }
        }

        // UANG MAKAN BULANAN
        $uang_makan_base = intval($settings['UANG_MAKAN_BULANAN']);

        // Cabang-specific override: if employee.cabang exactly equals "Cibinong"
        // then use UANG_MAKANAN_BULANAN_CIBINONG when available in settings.
        if (!isset($emp_cabang_cache[$npp])) {
            $qe2 = mysqli_query($conn, "SELECT cabang FROM employee WHERE npp='" . mysqli_real_escape_string($conn, $npp) . "' LIMIT 1");
            if ($qe2 && mysqli_num_rows($qe2) > 0) {
                $er2 = mysqli_fetch_assoc($qe2);
                $emp_cabang_cache[$npp] = $er2['cabang'];
            } else {
                $emp_cabang_cache[$npp] = null;
            }
        }
        if ($emp_cabang_cache[$npp] === 'Cibinong' && isset($settings['UANG_MAKANAN_BULANAN_CIBINONG'])) {
            $uang_makan_base = intval($settings['UANG_MAKANAN_BULANAN_CIBINONG']);
        }

        $potongan_makan = abs($total_makan);
        $uang_makan_final = $uang_makan_base + $total_makan;
        $total_makan = $uang_makan_final;

        // Calculate total payment
        $bonus_insentif = $bonus_titik + $bonus_full_hadir;
        // Policy: if employee has any hadir days, always pay the monthly uang makan (guaranteed).
        // Apply denda only against other components (bonus + lembur). This prevents denda from
        // reducing the guaranteed uang makan to negative.
        if ($hari_hadir > 0) {
            $other_net = $bonus_insentif + $total_lembur - $total_denda;
            $jumlah_dibayarkan = $uang_makan_final + max(0, $other_net);
        } else {
            // If no hadir days, keep previous behavior
            $jumlah_dibayarkan = $total_makan + $bonus_insentif + $total_lembur - $total_denda;
        }

        // Bind and execute
        if (
            !mysqli_stmt_bind_param(
                $upsert_stmt,
                'ssiiiiiiiiiiiiii',
                $npp,
                $periode,
                $total_titik,
                $target_titik,
                $bonus_titik,
                $bonus_full_hadir,
                $total_denda,
                $akumulasi_menit_telat,
                $potongan_makan,
                $total_makan,
                $total_lembur,
                $jumlah_dibayarkan,
                $hari_hadir,
                $hari_telat,
                $hari_cuti,
                $hari_alpha
            )
        ) {
            $error_messages[] = "NPP $npp periode $periode: Gagal bind: " . mysqli_error($conn);
            $error_count++;
            continue;
        }

        if (!mysqli_stmt_execute($upsert_stmt)) {
            $error_messages[] = "NPP $npp periode $periode: Gagal menyimpan: " . mysqli_stmt_error($upsert_stmt);
            $error_count++;
            continue;
        }

        $success_count++;
        $hari_kerja = count($data['dates']);

        // Format messages
        $bonus_parts = [];
        if ($bonus_titik > 0) {
            $selisih_display = $total_titik - $target_titik;
            $titik_paid = min($selisih_display, intval($settings['BATAS_ATAS_BONUS_TITIK']));
            $bonus_parts[] = "Bonus Titik (+{$selisih_display} titik, dibayar {$titik_paid} titik) = " . number_format($bonus_titik);
        }
        if ($bonus_full_hadir > 0)
            $bonus_parts[] = "Bonus Full Hadir = " . number_format($bonus_full_hadir);
        if (empty($bonus_parts))
            $bonus_parts[] = "Tidak ada bonus";
        $bonus_info = implode(" | ", $bonus_parts);

        $makan_info = number_format($uang_makan_base);
        if ($potongan_makan > 0) {
            $makan_info .= " - " . number_format($potongan_makan) . " (" . ($hari_cuti > 0 ? "{$hari_cuti} cuti" : "") . ") = " . number_format($total_makan);
        }

        $error_messages[] = "✓ NPP $npp periode $periode: {$hari_kerja} hari | Hadir={$hari_hadir} Telat={$hari_telat} Cuti={$hari_cuti} | Titik Aktual={$total_titik} Target={$target_titik} | Makan={$makan_info} | {$bonus_info} | TOTAL BAYAR=" . number_format($jumlah_dibayarkan);
    }

    mysqli_stmt_close($upsert_stmt);

    if ($success_count > 0) {
        $_SESSION['alert_type'] = "success";
        $_SESSION['alert_message'] = "Berhasil memproses $success_count transaksi bulanan! Data harian tersimpan di absensi_kurir.";
        if ($error_count > 0)
            $_SESSION['alert_message'] .= " ($error_count gagal)";
    } else {
        $_SESSION['alert_type'] = "danger";
        $_SESSION['alert_message'] = "Tidak ada data yang berhasil diproses!";
    }
    if (!empty($error_messages))
        $_SESSION['alert_details'] = implode("<br>", $error_messages);

} catch (Exception $e) {
    $_SESSION['alert_type'] = "danger";
    $_SESSION['alert_message'] = "Error: " . $e->getMessage();
    $_SESSION['alert_details'] = $e->__toString();
}

header("Location: insentif_kurir_list.php");
exit();
?>