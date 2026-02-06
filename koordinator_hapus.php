<?php
	include("sess_check.php");
		$id = $_GET['id_koordinator'];	
		$sql = "DELETE FROM koordinator WHERE id_koordinator='". $id ."'";
		$ress = mysqli_query($conn, $sql);
		header("location: koordinator.php?act=delete&msg=success");
?>