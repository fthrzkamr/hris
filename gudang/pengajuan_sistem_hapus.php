<?php
    include("sess_check.php");

    $id = $_GET['no_pengajuan']; // Mengambil parameter no_pengajuan dari URL

    $sql = "DELETE FROM pengajuan_sistem WHERE no_pengajuan='". $id ."'"; // Menggunakan $id untuk query
    $ress = mysqli_query($conn, $sql);

    if($ress) {
        header("location: pengajuan_sistem.php?act=delete&msg=success");
    } else {
        echo "Error: Data tidak dapat dihapus.";
    }
?>
