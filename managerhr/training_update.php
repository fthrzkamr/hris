<?php
include("sess_check.php");

$id_pengajuan = mysqli_real_escape_string($conn, $_POST['id_pengajuan'] ?? '');
$aksi = $_POST['aksi'] ?? '';
$reject_reason = isset($_POST['reject_reason']) ? mysqli_real_escape_string($conn, $_POST['reject_reason']) : '';
$approved_by = $sess_mngname; // dari session
$approved_date = date('Y-m-d H:i:s');

if(empty($id_pengajuan) || empty($aksi)){
    $_SESSION['pesan'] = 'Data tidak lengkap.';
    $_SESSION['type_pesan'] = 'warning';
    header('Location: training_wait.php');
    exit;
}

if($aksi == "Rejected"){
    $sql = "UPDATE pengajuan_training SET
            status='Rejected',
            approved_by='". $approved_by ."',
            approved_date='". $approved_date ."',
            reject_reason='". $reject_reason ."'
            WHERE id_pengajuan='". $id_pengajuan ."'";
    $ress = mysqli_query($conn, $sql);
    
    if($ress){
        $_SESSION['pesan'] = 'Pengajuan training telah ditolak.';
        $_SESSION['type_pesan'] = 'danger';
    } else {
        $_SESSION['pesan'] = 'Terjadi kesalahan saat memproses penolakan.';
        $_SESSION['type_pesan'] = 'danger';
    }
    header('Location: training_wait.php');
    exit;
    
} else if($aksi == "Approved"){
    $sql = "UPDATE pengajuan_training SET
            status='Approved',
            approved_by='". $approved_by ."',
            approved_date='". $approved_date ."'
            WHERE id_pengajuan='". $id_pengajuan ."'";
    $ress = mysqli_query($conn, $sql);
    
    if($ress){
        $_SESSION['pesan'] = 'Pengajuan training telah disetujui.';
        $_SESSION['type_pesan'] = 'success';
    } else {
        $_SESSION['pesan'] = 'Terjadi kesalahan saat menyetujui pengajuan.';
        $_SESSION['type_pesan'] = 'danger';
    }
    header('Location: training_wait.php');
    exit;
} else {
    $_SESSION['pesan'] = 'Status tidak valid!';
    $_SESSION['type_pesan'] = 'warning';
    header('Location: training_wait.php');
    exit;
}
?>
