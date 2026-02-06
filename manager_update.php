<?php
	include("sess_check.php");
	
	// query database memperbarui data pada database
	if(isset($_POST['perbarui'])) {
		$id			        	= $_POST['id_manager'];
		$nama_manager			= $_POST['nama_manager'];
		$created_at				= $_POST['created_at'];
		$akses					= $_POST['akses'];
		
		$sql = "UPDATE manager SET

			id_manager					='". $id ."',
			nama_manager				='". $nama_manager ."',
			created_at					='". $created_at ."',
			akses   					='". $akses ."'

			WHERE id_manager	    	='". $id ."'";
		$ress = mysqli_query($conn, $sql);
		header("location: manager.php?act=update&msg=success");
			
		}
	

?>