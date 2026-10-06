<?php
include("sess_check.php");
if (isset($row_sess['status_karyawan']) && $row_sess['status_karyawan'] == 'Magang') {
	header("location: index.php");
	exit;
}
$pagedesc = "Peminjaman Mobil";
include("layout_top.php");
$path_prefix = "../";
include("../peminjaman_mobil_core.php");
include("layout_bottom.php");
?>
