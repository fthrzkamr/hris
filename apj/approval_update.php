<?php
include("sess_check.php");

$no         = $_POST['no'];
$aksi       = $_POST['aksi'];
$reject     = $_POST['reject'];
$tgl_awal   = $_POST['tgl_awal'];
$tgl_akhir  = $_POST['tgl_akhir'];

$start  = new DateTime($tgl_awal);
$finish = new DateTime($tgl_akhir);
$int    = $start->diff($finish);
$durasi = $int->days + 1;

$stt = "";
$null = 0;

if ($aksi == "2") {  // Jika cuti ditolak
    $stt = "Rejected";

    // Ambil informasi cuti yang diajukan
    $sql_cuti = "SELECT npp, durasi, tipe_cuti FROM cuti WHERE no_cuti='$no'";
    $res_cuti = mysqli_query($conn, $sql_cuti);
    $data_cuti = mysqli_fetch_array($res_cuti);

    $npp = $data_cuti['npp'];
    $durasi_sebelumnya = $data_cuti['durasi'];
    $tipe_cuti = $data_cuti['tipe_cuti'];

    // Hanya kembalikan saldo cuti jika cutinya adalah "Cuti Tahunan"
    if ($tipe_cuti == "cuti tahunan") {
        // Ambil jumlah cuti yang tersisa
        $sql_pgw = "SELECT jml_cuti FROM employee WHERE npp='$npp'";
        $res_pgw = mysqli_query($conn, $sql_pgw);
        $data_pgw = mysqli_fetch_array($res_pgw);

        $jml_cuti_tersisa = $data_pgw['jml_cuti'];

        // Kembalikan durasi cuti ke saldo cuti karyawan
        $total_cuti_baru = $jml_cuti_tersisa + $durasi_sebelumnya;

        // Update saldo cuti karyawan
        $sql_update_employee = "UPDATE employee SET jml_cuti='$total_cuti_baru' WHERE npp='$npp'";
        mysqli_query($conn, $sql_update_employee);
    }

    // Update status cuti ke "Rejected" dan kosongkan approval
    $sql_update_cuti = "UPDATE cuti SET
                        stt_cuti='$stt',
                        lead_app='$null',
                        spv_app='$null',
                        mng_app='$null',
                        ket_reject='$reject'
                        WHERE no_cuti='$no'";
    mysqli_query($conn, $sql_update_cuti);

    header("location: app_wait.php?act=update&msg=success");
} else {  // Jika cuti disetujui
    $stt = "Approved";
    $num = 1;

    // Cek status sebelumnya apakah "Rejected"
    $sql_check = "SELECT stt_cuti, npp, tipe_cuti FROM cuti WHERE no_cuti='$no'";
    $res_check = mysqli_query($conn, $sql_check);
    $data_check = mysqli_fetch_array($res_check);

    $npp = $data_check['npp'];
    $tipe_cuti = $data_check['tipe_cuti'];
    $status_sebelumnya = $data_check['stt_cuti'];

    if ($tipe_cuti == "cuti tahunan") {
        // Ambil jumlah cuti yang tersisa
        $sql_pgw = "SELECT jml_cuti FROM employee WHERE npp='$npp'";
        $res_pgw = mysqli_query($conn, $sql_pgw);
        $data_pgw = mysqli_fetch_array($res_pgw);

        $jml_cuti_tersisa = $data_pgw['jml_cuti'];

        // Jika sebelumnya "Rejected", pastikan saldo dikurangi kembali
        if ($status_sebelumnya == "Rejected") {
            $total_cuti_baru = $jml_cuti_tersisa - $durasi;
        } else {
            $total_cuti_baru = $jml_cuti_tersisa - $durasi;
        }

        // Update saldo cuti karyawan
        $sql_update_employee = "UPDATE employee SET jml_cuti='$total_cuti_baru' WHERE npp='$npp'";
        mysqli_query($conn, $sql_update_employee);
    }

    // Update status cuti menjadi "Approved"
    $sql = "UPDATE cuti SET
            stt_cuti='$stt',
            tgl_awal='$tgl_awal',
            tgl_akhir='$tgl_akhir',
            durasi='$durasi',
            hrd_app='$num'
            WHERE no_cuti='$no'";
    mysqli_query($conn, $sql);

    header("location: app_wait.php?act=update&msg=success");
}
?>
