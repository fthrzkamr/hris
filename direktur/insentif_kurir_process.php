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
    header("Location: insentif_kurir_upload.php");
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
    header("Location: insentif_kurir_upload.php");
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
        $periode_raw = $worksheet->getCell('B' . $row)->getValue();
        $total_titik = intval($worksheet->getCell('C' . $row)->getValue());
        $target_titik = intval($worksheet->getCell('D' . $row)->getValue());
        
        // Skip empty rows
        if (empty($npp)) {
            continue;
        }
        
        // Convert periode to date format
        if (is_numeric($periode_raw)) {
            // Excel date format
            $periode = Date::excelToDateTimeObject($periode_raw)->format('Y-m-d');
        } else {
            // Text date format
            $periode = date('Y-m-d', strtotime($periode_raw));
        }
        
        // Validate NPP exists in employee table
        $check_npp = mysqli_query($conn, "SELECT npp FROM employee WHERE npp = '$npp'");
        if (mysqli_num_rows($check_npp) == 0) {
            $error_messages[] = "Baris $row: NPP $npp tidak ditemukan di database karyawan";
            $error_count++;
            continue;
        }
        
        // Calculate percentage
        if ($target_titik > 0) {
            $persentase_pencapaian = ($total_titik / $target_titik) * 100;
        } else {
            $persentase_pencapaian = 0;
        }
        
        // Determine status
        $status_target = ($total_titik >= $target_titik) ? 'Tercapai' : 'Tidak Tercapai';
        
        // Insert or update data
        $sql = "INSERT INTO insentif_kurir 
                (npp, periode, total_titik, target_titik, persentase_pencapaian, status_target) 
                VALUES 
                ('$npp', '$periode', $total_titik, $target_titik, $persentase_pencapaian, '$status_target')
                ON DUPLICATE KEY UPDATE
                total_titik = VALUES(total_titik),
                target_titik = VALUES(target_titik),
                persentase_pencapaian = VALUES(persentase_pencapaian),
                status_target = VALUES(status_target),
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
        $_SESSION['alert_message'] = "Berhasil memproses $success_count data insentif kurir!";
        
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

header("Location: insentif_kurir_list.php");
exit();
?>
