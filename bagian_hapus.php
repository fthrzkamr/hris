<?php
	include("sess_check.php");
		$id = $_GET['id_bagian'];	
		$sql = "DELETE FROM bagian WHERE id_bagian='". $id ."'";
		$ress = mysqli_query($conn, $sql);
		header("location: bagian.php?act=delete&msg=success");
?>