<?php
include("sess_check.php");

$id_pengajuan = $_POST['id_pengajuan'];
$aksi = $_POST['aksi'];
$reject_reason = isset($_POST['reject_reason']) ? $_POST['reject_reason'] : '';
$approved_by = $sess_mngname; // dari session managerhr
$approved_date = date('Y-m-d H:i:s');

if($aksi == "Rejected"){
    $sql = "UPDATE pengajuan_training SET
            status='Rejected',
            approved_by='". $approved_by ."',
            approved_date='". $approved_date ."',
            reject_reason='". $reject_reason ."'
            WHERE id_pengajuan='". $id_pengajuan ."'";
    $ress = mysqli_query($conn, $sql);
    
    if($ress){
        echo "<script type='text/javascript'>
                alert('Pengajuan training telah ditolak.'); 
                document.location = 'training_wait.php'; 
            </script>";
    }else{
        echo "<script type='text/javascript'>
                alert('Terjadi kesalahan: " . mysqli_error($conn) . "'); 
                document.location = 'training_review.php?id=$id_pengajuan'; 
            </script>";
    }
    
}else if($aksi == "Approved"){
    $sql = "UPDATE pengajuan_training SET
            status='Approved',
            approved_by='". $approved_by ."',
            approved_date='". $approved_date ."'
            WHERE id_pengajuan='". $id_pengajuan ."'";
    $ress = mysqli_query($conn, $sql);
    
    if($ress){
        echo "<script type='text/javascript'>
                alert('Pengajuan training telah disetujui.'); 
                document.location = 'training_wait.php'; 
            </script>";
    }else{
        echo "<script type='text/javascript'>
                alert('Terjadi kesalahan: " . mysqli_error($conn) . "'); 
                document.location = 'training_review.php?id=$id_pengajuan'; 
            </script>";
    }
}else{
    echo "<script type='text/javascript'>
            alert('Status tidak valid!'); 
            document.location = 'training_wait.php'; 
        </script>";
}
?>
