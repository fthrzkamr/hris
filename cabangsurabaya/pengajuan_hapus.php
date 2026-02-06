<?php
	include("sess_check.php");
		$id = $_GET['id_pengajuan'];	
		$sql = "DELETE FROM pengajuan_sistem WHERE id_pengajuan='". $id ."'";
		$ress = mysqli_query($conn, $sql);
		header("location: pengajuan_wait.php?act=delete&msg=success");
?>