<?php
	include("sess_check.php");
		$id = $_GET['id_lmbr'];	
		$sql = "DELETE FROM lembur WHERE id_lmbr='". $id ."'";
		$ress = mysqli_query($conn, $sql);
		header("location: lembur_waitapp.php?act=delete&msg=success");
?>