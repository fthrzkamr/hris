<?php
	include("sess_check.php");
   
    	$npp	                = $_POST['npp'];
		$nama_karyawan	        = $_POST['nama_karyawan'];
		$nama_bagian	  		= $_POST['nama_bagian'];
		$pengajuan_dibutuhkan   = $_POST['pengajuan_dibutuhkan'];       
		$pengajuan_manfaat      = $_POST['pengajuan_manfaat'];       
		// end database baru


		$sqlcek = "SELECT * FROM employee WHERE npp='$npp'";
		$resscek = mysqli_query($conn, $sqlcek);
		$rowscek = mysqli_num_rows($resscek);
		if($npp){
			$sql="INSERT INTO pengajuan_sistem(npp,nama_karyawan,nama_bagian,pengajuan_dibutuhkan,pengajuan_manfaat)
				VALUES('$npp','$nama_karyawan','$nama_bagian','$pengajuan_dibutuhkan','$pengajuan_manfaat')";
			$ress = mysqli_query($conn, $sql);
			// var_dump($ress);
			// exit();
			if($ress){
				echo "<script>alert('Tambah Pengajuan Berhasil!');</script>";
				echo "<script type='text/javascript'> document.location = 'pengajuan_wait.php'; </script>";
			}else{
				echo("Error description: " . mysqli_error($conn));
				echo "<script>alert('Ops, terjadi kesalahan. Silahkan coba lagi.');</script>";
				echo "<script type='text/javascript'> document.location = 'pengajuan_create.php'; </script>";
			}
		}else{
			header("location: pengajuan_create.php?act=add&msg=double");	
		}
?>