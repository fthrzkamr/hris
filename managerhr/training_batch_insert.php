<?php
include("sess_check.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: training_batch_add.php');
    exit;
}

// Get data from form (SAME training details for MULTIPLE employees)
$npp_arr = $_POST['npp'] ?? [];
$judul_training = $_POST['judul_training'] ?? '';
$tujuan_training = $_POST['tujuan_training'] ?? '';
$penyelenggara = $_POST['penyelenggara'] ?? '';
$lokasi_training = $_POST['lokasi_training'] ?? '';
$tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
$tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
$has_budget = isset($_POST['has_budget']) ? $_POST['has_budget'] : 0;
$budget_total = floatval($_POST['budget_total'] ?? 0);
$rincian_items = $_POST['rincian_item'] ?? [];
$rincian_nilai = $_POST['rincian_nilai'] ?? [];

// Validate
if (empty($npp_arr) || count($npp_arr) == 0) {
    $_SESSION['pesan'] = 'Pilih minimal 1 karyawan!';
    $_SESSION['type_pesan'] = 'danger';
    header('Location: training_batch_add.php');
    exit;
}

if (empty($judul_training) || empty($penyelenggara) || empty($lokasi_training) || empty($tanggal_mulai) || empty($tanggal_selesai)) {
    $_SESSION['pesan'] = 'Semua field wajib harus diisi!';
    $_SESSION['type_pesan'] = 'danger';
    header('Location: training_batch_add.php');
    exit;
}

// Get HR info from session
$added_by = $sess_mngname ?? 'Manager HR';
$tanggal_pengajuan = date('Y-m-d H:i:s');
$approved_date = date('Y-m-d H:i:s');

// Escape strings for SQL
$judul_training = mysqli_real_escape_string($conn, $judul_training);
$tujuan_training = mysqli_real_escape_string($conn, $tujuan_training);
$penyelenggara = mysqli_real_escape_string($conn, $penyelenggara);
$lokasi_training = mysqli_real_escape_string($conn, $lokasi_training);

$success_count = 0;
$error_count = 0;
$errors = [];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Loop through each selected employee
    foreach ($npp_arr as $npp) {
        $npp_escaped = mysqli_real_escape_string($conn, $npp);
        
        // Insert into pengajuan_training
        $sql_insert = "INSERT INTO pengajuan_training (
            npp, 
            judul_training, 
            tujuan_training, 
            penyelenggara, 
            lokasi_training, 
            tanggal_mulai, 
            tanggal_selesai, 
            budget_total, 
            tanggal_pengajuan,
            status,
            approved_by,
            approved_date
        ) VALUES (
            '$npp_escaped',
            '$judul_training',
            '$tujuan_training',
            '$penyelenggara',
            '$lokasi_training',
            '$tanggal_mulai',
            '$tanggal_selesai',
            '$budget_total',
            '$tanggal_pengajuan',
            'Completed',
            '$added_by',
            '$approved_date'
        )";
        
        if (mysqli_query($conn, $sql_insert)) {
            $last_id = mysqli_insert_id($conn);
            
            // Insert rincian budget if exists (same rincian for all employees)
            if ($has_budget && !empty($rincian_items) && is_array($rincian_items)) {
                for ($j = 0; $j < count($rincian_items); $j++) {
                    if (!empty($rincian_items[$j])) {
                        $item_name = mysqli_real_escape_string($conn, $rincian_items[$j]);
                        $item_nilai = floatval($rincian_nilai[$j] ?? 0);
                        
                        $sql_rincian = "INSERT INTO training_rincian (
                            id_pengajuan,
                            nama_item,
                            nilai
                        ) VALUES (
                            '$last_id',
                            '$item_name',
                            '$item_nilai'
                        )";
                        
                        if (!mysqli_query($conn, $sql_rincian)) {
                            $errors[] = "NPP $npp - Rincian item: " . mysqli_error($conn);
                        }
                    }
                }
            }
            
            $success_count++;
        } else {
            $error_count++;
            $errors[] = "NPP $npp: " . mysqli_error($conn);
        }
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Set success message
    if ($success_count > 0) {
        $_SESSION['pesan'] = "Berhasil menambahkan training untuk $success_count karyawan dengan status Completed!";
        $_SESSION['type_pesan'] = 'success';
    }
    
    if ($error_count > 0) {
        $_SESSION['pesan'] = ($success_count > 0 ? $_SESSION['pesan'] . " " : "") . "Namun ada $error_count karyawan yang gagal. Detail: " . implode(' | ', $errors);
        $_SESSION['type_pesan'] = 'warning';
    }
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    $_SESSION['pesan'] = 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage() . '. Data telah di-rollback.';
    $_SESSION['type_pesan'] = 'danger';
}

// Redirect to list
header('Location: training_list.php');
exit;
?>
