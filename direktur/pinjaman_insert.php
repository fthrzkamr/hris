<?php
include("sess_check.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $npp                = $_POST['npp'];
    $jumlah_pinjaman    = str_replace(".", "", $_POST['jumlah_pinjaman']);
    $tenor              = $_POST['tenor'];
    $tanggal_pengajuan  = $_POST['tanggal_pengajuan'];
    $keterangan         = $_POST['keterangan'];
    $status             = 'aktif';

    // Hitung cicilan per bulan
    $cicilan = $jumlah_pinjaman / $tenor;

    // Hitung tanggal lunas
    $tanggal_lunas = date('Y-m-d', strtotime("+$tenor months", strtotime($tanggal_pengajuan)));

    // 1. Ambil nomor urut terakhir
    $queryLast = mysqli_query($conn, "SELECT MAX(id_pinjaman) as max_id FROM pinjaman");
    $dataLast = mysqli_fetch_array($queryLast);
    $lastId = $dataLast['max_id'];

    // Ekstrak nomor urut terakhir
    if ($lastId != '') {
        $parts = explode('/', $lastId);
        $lastNumber = intval($parts[1]) + 1;
    } else {
        $lastNumber = 1;
    }

    $urutBaru = str_pad($lastNumber, 3, '0', STR_PAD_LEFT); // 001, 002, dst
    $last4Npp = substr($npp, -4); // 4 digit terakhir dari NPP
    $id_pinjaman = "PJM/$urutBaru/$last4Npp";

    // Simpan ke database
    $sql = "INSERT INTO pinjaman (
                id_pinjaman, npp, tanggal_pengajuan, jumlah_pinjaman, tenor, cicilan_per_bulan, keterangan, status, tanggal_lunas
            ) VALUES (
                '$id_pinjaman', '$npp', '$tanggal_pengajuan', '$jumlah_pinjaman', '$tenor', '$cicilan', '$keterangan', '$status', '$tanggal_lunas'
            )";

    $res = mysqli_query($conn, $sql);

    if ($res) {
        header("Location: pinjaman.php?act=add&msg=success");
    } else {
        echo "Gagal menyimpan data: " . mysqli_error($conn);
    }
}
?>
2