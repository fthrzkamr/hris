<?php
include("sess_check.php");

$nama_koordinator = mysqli_real_escape_string($conn, trim($_POST['nama_koordinator'] ?? ''));
$npp              = mysqli_real_escape_string($conn, trim($_POST['npp'] ?? ''));
$created_at       = date('Y-m-d');
$akses            = mysqli_real_escape_string($conn, trim($_POST['akses'] ?? ''));

// Jika NPP kosong, cari otomatis dari tabel employee berdasarkan nama
if ($npp === '' && $nama_koordinator !== '') {
    $q_emp = mysqli_query($conn, "SELECT npp FROM employee WHERE TRIM(LOWER(nama_emp)) = LOWER('$nama_koordinator') LIMIT 1");
    if ($q_emp && $r_emp = mysqli_fetch_assoc($q_emp)) {
        $npp = $r_emp['npp'];
    }
}

$sql = "INSERT INTO koordinator(nama_koordinator, created_at, akses, npp)
        VALUES('$nama_koordinator', '$created_at', '$akses', '$npp')";
$ress = mysqli_query($conn, $sql);

if ($ress) {
    echo "<script>alert('Tambah Koordinator Berhasil!'); document.location = 'koordinator.php';</script>";
} else {
    echo "Error description: " . mysqli_error($conn);
    echo "<script>alert('Ops, terjadi kesalahan. Silahkan coba lagi.'); document.location = 'koordinator_tambah.php';</script>";
}
?>
