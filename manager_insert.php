<?php
	include("sess_check.php");
   
        $id	        = '';
		$nama_manager	= $_POST['nama_manager'];
		$created_at		= date('Y-m-d');
		$akses          = $_POST['akses'];       
		// end database baru


		$sqlcek = "SELECT * FROM manager WHERE id_manager='$id'";
		$resscek = mysqli_query($conn, $sqlcek);
		$rowscek = mysqli_num_rows($resscek);
		if($rowscek<1){
			$sql="INSERT INTO manager(nama_manager,created_at,akses)
				VALUES('$nama_manager','$created_at','$akses')";
			$ress = mysqli_query($conn, $sql);
			// var_dump($ress);
			// exit();
			if($ress){
				echo "<script>alert('Tambah Manager Berhasil!');</script>";
				echo "<script type='text/javascript'> document.location = 'manager.php'; </script>";
			}else{
				echo("Error description: " . mysqli_error($conn));
				echo "<script>alert('Ops, terjadi kesalahan. Silahkan coba lagi.');</script>";
				echo "<script type='text/javascript'> document.location = 'manager_tambah.php'; </script>";
			}
		}else{
			header("location: manager_tambah.php?act=add&msg=double");	
		}
?>