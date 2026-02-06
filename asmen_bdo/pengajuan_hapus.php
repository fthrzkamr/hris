<?php
	include("sess_check.php");
		$id = $_GET['no_pengajuan'];	
		$sql = "DELETE FROM pengajuan_sistem WHERE no_pengajuan='" . $id . "'";
		$ress = mysqli_query($conn, $sql);
		header("location: pengajuan_wait.php?act=delete&msg=success");
?>