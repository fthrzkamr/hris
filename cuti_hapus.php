<?php
	include("sess_check.php");
		$id = $_GET['no_cuti'];	
		$sql = "DELETE FROM cuti WHERE no_cuti='". $id ."'";
		$ress = mysqli_query($conn, $sql);
		header("location: cuti_waitapp.php?act=delete&msg=success");
?>