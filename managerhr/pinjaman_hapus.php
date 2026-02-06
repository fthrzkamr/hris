<?php
	include("sess_check.php");
	
	$id = $_GET['id'];	
	
	// Hapus data angsuran terkait (jika ada)
	$sql_angsuran = "DELETE FROM angsuran_pinjaman WHERE id_pinjaman='$id'";
	mysqli_query($conn, $sql_angsuran);
	
	// Hapus data pinjaman
	$sql = "DELETE FROM pinjaman WHERE id_pinjaman='$id'";
	$ress = mysqli_query($conn, $sql);
	
	if($ress) {
		header("location: pinjaman.php?act=delete&msg=success");
	} else {
		header("location: pinjaman.php?act=delete&msg=error");
	}
?>
