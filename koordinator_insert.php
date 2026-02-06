<?php
	include("sess_check.php");
   
        $id	        = '';
		$nama_koordinator	= $_POST['nama_koordinator'];
		$created_at		= date('Y-m-d');
		$akses          = $_POST['akses'];       
		// end database baru


		$sqlcek = "SELECT * FROM koordinator WHERE id_koordinator='$id'";
		$resscek = mysqli_query($conn, $sqlcek);
		$rowscek = mysqli_num_rows($resscek);
		if($rowscek<1){
			$sql="INSERT INTO koordinator(nama_koordinator,created_at,akses)
				VALUES('$nama_koordinator','$created_at','$akses')";
			$ress = mysqli_query($conn, $sql);
			// var_dump($ress);
			// exit();
			if($ress){
				echo "<script>alert('Tambah Koordinator Berhasil!');</script>";
				echo "<script type='text/javascript'> document.location = 'koordinator.php'; </script>";
			}else{
				echo("Error description: " . mysqli_error($conn));
				echo "<script>alert('Ops, terjadi kesalahan. Silahkan coba lagi.');</script>";
				echo "<script type='text/javascript'> document.location = 'koordinator_tambah.php'; </script>";
			}
		}else{
			header("location: koordinator_tambah.php?act=add&msg=double");	
		}
?>