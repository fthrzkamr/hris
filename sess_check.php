<?php
session_start();

$chk_sess = $_SESSION['admin'] ?? null;
if (empty($chk_sess)) {
    header("Location: login.php?login=false");
    exit();
}

include("dist/config/koneksi.php");
include("dist/config/library.php");

$chk_sess_esc = mysqli_real_escape_string($conn, $chk_sess);
$sql_sess = "SELECT * FROM employee WHERE npp='$chk_sess_esc'";
$ress_sess = mysqli_query($conn, $sql_sess);
$row_sess = $ress_sess ? mysqli_fetch_assoc($ress_sess) : null;

if (!$row_sess) {
    header("Location: login.php?login=false");
    exit();
}

$sess_admid = $row_sess['npp'];
$sess_admuser = $row_sess['npp'];
$sess_admname = $row_sess['nama_emp'];
$sess_jabatan = $row_sess['jabatan'] ?? null;
?>
