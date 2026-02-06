<?php
	include("sess_check.php");
   
        $id	                = '';
		$nama_bagian	= $_POST['nama_bagian'];
		$created_at		= date('Y-m-d');
		// end database baru


		$sqlcek = "SELECT * FROM bagian WHERE id_bagian='$id'";
		$resscek = mysqli_query($conn, $sqlcek);
		$rowscek = mysqli_num_rows($resscek);
		if($rowscek<1){
			$sql="INSERT INTO bagian(nama_bagian,created_at)
				VALUES('$nama_bagian','$created_at')";
			$ress = mysqli_query($conn, $sql);
			// var_dump($ress);
			// exit();
			if($ress){
				echo "<script>alert('Tambah Bagian Berhasil!');</script>";
				echo "<script type='text/javascript'> document.location = 'bagian.php'; </script>";
			}else{
				echo("Error description: " . mysqli_error($conn));
				echo "<script>alert('Ops, terjadi kesalahan. Silahkan coba lagi.');</script>";
				echo "<script type='text/javascript'> document.location = 'bagian_tambah.php'; </script>";
			}
		}else{
			header("location: bagian_tambah.php?act=add&msg=double");	
		}
?>