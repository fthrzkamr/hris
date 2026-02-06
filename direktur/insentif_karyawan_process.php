<?php
session_start();
include("sess_check.php");
include("dist/config/koneksi.php");

// Check if user is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Check if file is uploaded
if (!isset($_FILES['excel_file'])) {
    $_SESSION['alert_type'] = "danger";
    $_SESSION['alert_message'] = "Tidak ada file yang diupload!";
    header("Location: insentif_karyawan_upload.php");
    exit();
}

// Load PhpSpreadsheet library
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

$file = $_FILES['excel_file'];
$file_name = $file['name'];
$file_tmp = $file['tmp_name'];
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Validate file extension
if (!in_array($file_ext, ['xls', 'xlsx'])) {
    $_SESSION['alert_type'] = "danger";
    $_SESSION['alert_message'] = "Format file tidak valid! Gunakan file .xls atau .xlsx";
    header("Location: insentif_karyawan_upload.php");
    exit();
}

try {
    // Load spreadsheet
    $spreadsheet = IOFactory::load($file_tmp);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    
    $success_count = 0;
    $error_count = 0;
    $error_messages = [];
    
    // Start from row 2 (skip header)
    for ($row = 2; $row <= $highestRow; $row++) {
        $npp = trim($worksheet->getCell('A' . $row)->getValue());
        $tanggal_raw = $worksheet->getCell('B' . $row)->getValue();
        $jam_masuk_raw = $worksheet->getCell('C' . $row)->getValue();
        $jam_pulang_raw = $worksheet->getCell('D' . $row)->getValue();
        
        // Skip empty rows
        if (empty($npp)) {
            continue;
        }
        
        // Convert tanggal to date format
        if (is_numeric($tanggal_raw)) {
            // Excel date format
            $tanggal = Date::excelToDateTimeObject($tanggal_raw)->format('Y-m-d');
        } else {
            // Text date format
            $tanggal = date('Y-m-d', strtotime($tanggal_raw));
        }
        
        // Convert jam_masuk to time format
        $jam_masuk = null;
        if (!empty($jam_masuk_raw)) {
            if (is_numeric($jam_masuk_raw)) {
                // Excel time format (decimal)
                $jam_masuk = Date::excelToDateTimeObject($jam_masuk_raw)->format('H:i:s');
            } else {
                // Text time format
                $jam_masuk = date('H:i:s', strtotime($jam_masuk_raw));
            }
        }
        
        // Convert jam_pulang to time format (can be null)
        $jam_pulang = null;
        if (!empty($jam_pulang_raw)) {
            if (is_numeric($jam_pulang_raw)) {
                // Excel time format (decimal)
                $jam_pulang = Date::excelToDateTimeObject($jam_pulang_raw)->format('H:i:s');
            } else {
                // Text time format
                $jam_pulang = date('H:i:s', strtotime($jam_pulang_raw));
            }
        }
        
        // Validate NPP exists in employee table
        $check_npp = mysqli_query($conn, "SELECT npp FROM employee WHERE npp = '$npp'");
        if (mysqli_num_rows($check_npp) == 0) {
            $error_messages[] = "Baris $row: NPP $npp tidak ditemukan di database karyawan";
            $error_count++;
            continue;
        }
        
        // Calculate durasi kerja (work duration)
        $durasi_kerja = null;
        if ($jam_masuk && $jam_pulang) {
            $masuk_time = strtotime($jam_masuk);
            $pulang_time = strtotime($jam_pulang);
            $durasi_seconds = $pulang_time - $masuk_time;
            
            if ($durasi_seconds > 0) {
                $hours = floor($durasi_seconds / 3600);
                $minutes = floor(($durasi_seconds % 3600) / 60);
                $durasi_kerja = sprintf("%02d:%02d:00", $hours, $minutes);
            }
        }
        
        // Determine status absensi
        $status_absensi = 'Hadir';
        $jam_kerja_normal = '08:00:00';
        $jam_pulang_normal = '17:00:00';
        
        if ($jam_masuk) {
            if ($jam_masuk > $jam_kerja_normal) {
                $status_absensi = 'Terlambat';
            }
            
            if ($jam_pulang) {
                if ($jam_pulang < $jam_pulang_normal) {
                    $status_absensi = 'Pulang Awal';
                }
            }
        } else {
            $status_absensi = 'Tidak Hadir';
        }
        
        // Prepare SQL values
        $jam_masuk_sql = $jam_masuk ? "'$jam_masuk'" : "NULL";
        $jam_pulang_sql = $jam_pulang ? "'$jam_pulang'" : "NULL";
        $durasi_kerja_sql = $durasi_kerja ? "'$durasi_kerja'" : "NULL";
        
        // Insert or update data
        $sql = "INSERT INTO absensi_karyawan 
                (npp, tanggal, jam_masuk, jam_pulang, durasi_kerja, status_absensi) 
                VALUES 
                ('$npp', '$tanggal', $jam_masuk_sql, $jam_pulang_sql, $durasi_kerja_sql, '$status_absensi')
                ON DUPLICATE KEY UPDATE
                jam_masuk = VALUES(jam_masuk),
                jam_pulang = VALUES(jam_pulang),
                durasi_kerja = VALUES(durasi_kerja),
                status_absensi = VALUES(status_absensi),
                updated_at = CURRENT_TIMESTAMP";
        
        if (mysqli_query($conn, $sql)) {
            $success_count++;
        } else {
            $error_messages[] = "Baris $row: " . mysqli_error($conn);
            $error_count++;
        }
    }
    
    // Set success message
    if ($success_count > 0) {
        $_SESSION['alert_type'] = "success";
        $_SESSION['alert_message'] = "Berhasil memproses $success_count data absensi karyawan!";
        
        if ($error_count > 0) {
            $_SESSION['alert_message'] .= " ($error_count data gagal)";
        }
    } else {
        $_SESSION['alert_type'] = "danger";
        $_SESSION['alert_message'] = "Tidak ada data yang berhasil diproses!";
    }
    
    // Add error details if any
    if (!empty($error_messages)) {
        $_SESSION['alert_details'] = implode("<br>", $error_messages);
    }
    
} catch (Exception $e) {
    $_SESSION['alert_type'] = "danger";
    $_SESSION['alert_message'] = "Error: " . $e->getMessage();
}

header("Location: insentif_karyawan_list.php");
exit();
?>
