<?php
	include("sess_check.php");
		$id = $_GET['id_rmbs'];	
		$sql = "DELETE FROM rembes WHERE id_rmbs='". $id ."'";
		$ress = mysqli_query($conn, $sql);
		header("location: reimburse_waitapp.php?act=delete&msg=success");
?>