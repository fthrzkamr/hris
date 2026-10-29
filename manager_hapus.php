<?php
	include("sess_check.php");
		$id = $_GET['id_manager'];	
		$sql = "DELETE FROM manager WHERE id_manager='". $id ."'";
		$ress = mysqli_query($conn, $sql);
		header("location: manager.php?act=delete&msg=success");
?>