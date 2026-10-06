<?php
// Memulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah session 'cabangbali' ada atau tidak
if (!isset($_SESSION['cabangbali']) || empty($_SESSION['cabangbali'])) {
    session_destroy();
    header("location: ../login.php?login=false");
    exit();
}

// Ambil NPP dari session
$chk_sess = $_SESSION['cabangbali'];

// Memanggil file koneksi
include("dist/config/koneksi.php");
include("dist/config/library.php");

// Debugging: Cek apakah session benar-benar menyimpan NPP
if (empty($chk_sess)) {
    die("Error: Session 'cabangbali' tidak memiliki nilai NPP.");
}

// Query untuk mendapatkan data karyawan berdasarkan NPP
$sql_sess = "SELECT * FROM employee WHERE npp = ?";
$stmt = mysqli_prepare($conn, $sql_sess);
mysqli_stmt_bind_param($stmt, "s", $chk_sess);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Jika data tidak ditemukan, logout paksa
if (mysqli_num_rows($result) == 0) {
    session_destroy();
    header("location: ../login.php?login=false");
    exit();
}

// Ambil data pengguna
$row_sess = mysqli_fetch_array($result);
$sess_mngid = $row_sess['npp'];
$sess_mngname = $row_sess['nama_emp'];
$sess_jabatan = $row_sess['jabatan'];
?>
