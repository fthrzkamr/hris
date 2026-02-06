<?php
	include("sess_check.php");
	
	// query database memperbarui data pada database
	if(isset($_POST['perbarui'])) {
		$id			        	=$_POST['id_koordinator'];
		$nama_koordinator		= $_POST['nama_koordinator'];
		$created_at				= $_POST['created_at'];
		$akses					= $_POST['akses'];
		
		$sql = "UPDATE koordinator SET

			id_koordinator							='". $id ."',
			nama_koordinator			='". $nama_koordinator ."',
			created_at					='". $created_at ."',
			akses   					='". $akses ."'

			WHERE id_koordinator		='". $id ."'";
		$ress = mysqli_query($conn, $sql);
		header("location: koordinator.php?act=update&msg=success");
			
		}
	

?>