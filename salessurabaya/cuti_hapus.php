<?php
include("sess_check.php");

$id = $_GET['no_cuti'];

// Ambil data cuti sebelum dihapus
$sql_get = "SELECT npp, durasi FROM cuti WHERE no_cuti = '$id'";
$ress_get = mysqli_query($conn, $sql_get);
$data = mysqli_fetch_assoc($ress_get);

if ($data) {
    $npp = $data['npp']; // ID pegawai
    $durasi = $data['durasi']; // Lama cuti dalam hari

    // Kembalikan jumlah cuti pegawai di tabel employee
    $sql_update = "UPDATE employee SET jml_cuti = jml_cuti + $durasi WHERE npp = '$npp'";
    mysqli_query($conn, $sql_update);

    // Hapus data cuti
    $sql_delete = "DELETE FROM cuti WHERE no_cuti = '$id'";
    mysqli_query($conn, $sql_delete);
}

header("location: cuti_app.php?act=delete&msg=success");
exit();
?>
