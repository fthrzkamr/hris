<?php
	include("sess_check.php");
	
	// query database memperbarui data pada database
	if(isset($_POST['perbarui'])) {
		$id			        	=$_POST['id_bagian'];
		$nama_bagian		    = $_POST['nama_bagian'];
		$created_at				= $_POST['created_at'];		
		$sql = "UPDATE bagian SET

			id_bagian					='". $id ."',
			nama_bagian					='". $nama_bagian	 ."',
			created_at					='". $created_at ."'

			WHERE id_bagian		='". $id ."'";
		$ress = mysqli_query($conn, $sql);
		header("location: bagian.php?act=update&msg=success");
			
		}