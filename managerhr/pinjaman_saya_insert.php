<?php
include("sess_check.php");

if (isset($row_sess['status_karyawan']) && $row_sess['status_karyawan'] == 'Magang') {
	header("location: index.php");
	exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Prefer NPP from session for security
    $npp                = $sess_mngid;
    $jumlah_pinjaman    = str_replace(".", "", $_POST['jumlah_pinjaman']);
    $tenor              = $_POST['tenor'];
    
    // Handle manual tenor input
    if ($tenor === 'manual' && isset($_POST['tenor_manual'])) {
        $tenor = $_POST['tenor_manual'];
    }
    
    $tanggal_pengajuan  = $_POST['tanggal_pengajuan'];
    $keterangan         = $_POST['keterangan'];
    $status             = 'menunggu';

    // Hitung cicilan per bulan
    $cicilan = $jumlah_pinjaman / $tenor;

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

    // Simpan ke database dengan status 'menunggu' (tanggal_lunas is NULL until approved)
    $sql = "INSERT INTO pinjaman (
                id_pinjaman, npp, tanggal_pengajuan, jumlah_pinjaman, tenor, cicilan_per_bulan, keterangan, status, tanggal_lunas
            ) VALUES (
                '$id_pinjaman', '$npp', '$tanggal_pengajuan', '$jumlah_pinjaman', '$tenor', '$cicilan', '$keterangan', '$status', NULL
            )";

    $res = mysqli_query($conn, $sql);

    if ($res) {
        // Redirect to pinjaman_saya.php with success
        header("Location: pinjaman_saya.php?act=add&msg=success");
        exit;
    } else {
        echo "Gagal menyimpan data: " . mysqli_error($conn);
    }
}
?>
