<?php
include("sess_check.php");

$no = $_POST['no'];
$aksi = $_POST['aksi'];
$reject = $_POST['reject'];
$stt = "";

// Ambil data pengajuan berdasarkan ID
$sql = "SELECT * FROM kacamata WHERE id_kacamata='$no'";
$query = mysqli_query($conn, $sql);
$data = mysqli_fetch_array($query);

if ($data) {
    $npp = $data['npp'];
    $total_kwintansi = $data['total_kwintansi'];

    // Ambil limit kacamata saat ini
    $sql_cek = "SELECT kacamata FROM employee WHERE npp='$npp'";
    $res_cek = mysqli_query($conn, $sql_cek);
    $row_cek = mysqli_fetch_array($res_cek);
    $limit_sekarang = $row_cek['kacamata'];

    // Jika pengajuan direject
    if ($aksi == "2") {
        $stt = "Rejected";

        // Kembalikan limit kacamata (80% dari total kwitansi)
        $limit_baru = $limit_sekarang + ($total_kwintansi * 0.80);
        $update = "UPDATE employee SET kacamata='$limit_baru' WHERE npp='$npp'";
        mysqli_query($conn, $update);

        // Update status pengajuan
        $sql = "UPDATE kacamata SET
                status='$stt',
                reject='$reject'
                WHERE id_kacamata='$no'";
        mysqli_query($conn, $sql);

    // Jika pengajuan diubah menjadi Approved setelah direject
    } else if ($aksi == "1" && $data['status'] == "Rejected") {
        $stt = "Approved";

        // Potong kembali limit kacamata
        $limit_baru = $limit_sekarang - ($total_kwintansi * 0.80);
        $update = "UPDATE employee SET kacamata='$limit_baru' WHERE npp='$npp'";
        mysqli_query($conn, $update);

        // Update status pengajuan
        $sql = "UPDATE kacamata SET
                status='$stt',
                reject=''
                WHERE id_kacamata='$no'";
        mysqli_query($conn, $sql);
    
    // Jika langsung disetujui tanpa pernah direject
    } else {
        $stt = "Approved";

        $sql = "UPDATE kacamata SET
                status='$stt'
                WHERE id_kacamata='$no'";
        mysqli_query($conn, $sql);
    }

    header("location: kacamata_wait.php?act=update&msg=success");
}
?>
