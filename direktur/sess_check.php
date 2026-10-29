<?php
session_start();

if (!isset($_SESSION['direktur']) || empty($_SESSION['direktur'])) {
    header("Location: ../login.php?login=false");
    exit();
}

$chk_sess = $_SESSION['direktur'];

include("dist/config/koneksi.php");
include("dist/config/library.php");

$chk_sess_esc = mysqli_real_escape_string($conn, $chk_sess);
$sql_sess = "SELECT * FROM employee WHERE npp='$chk_sess_esc'";
$ress_sess = mysqli_query($conn, $sql_sess);
$row_sess = mysqli_fetch_array($ress_sess);

$sess_mngid = $row_sess['npp'];
$sess_mngname = $row_sess['nama_emp'];
$sess_jabatan = $row_sess['jabatan'];
