<?php
include("sess_check.php");

$npp						= $_POST['npp'];
$nama_emp					= $_POST['nama_emp'];
$jk_emp						= $_POST['jk_emp'];
$nomor_ktp					= $_POST['nomor_ktp'];
$alamat						= $_POST['alamat'];
$alamat_tinggal_sekarang	= $_POST['alamat_tinggal_sekarang'];
$telp_emp						= $_POST['telp_emp'];
$nama_pasangan				= $_POST['nama_pasangan'];
$nomor_tlp					= $_POST['nomor_tlp'];


	$sql = "UPDATE employee SET 
	
				nama_emp					= '". $nama_emp ."',
				nomor_ktp					= '". $nomor_ktp ."',
				jk_emp						= '". $jk_emp ."',

				alamat						= '". $alamat ."',
				alamat_tinggal_sekarang		= '". $alamat_tinggal_sekarang ."',
				telp_emp						= '". $telp_emp ."',
				nama_pasangan				= '". $nama_pasangan ."',
				nomor_tlp					= '". $nomor_tlp ."'

				WHERE npp='". $npp ."'";

	$ress = mysqli_query($conn, $sql);
	if($ress){
		echo "<script>alert('Ubah data Berhasil!');</script>";
		echo "<script type='text/javascript'> document.location = 'index.php'; </script>";
	}else{
		echo("Error description: " . mysqli_error($conn));
		echo "<script>alert('Ops, terjadi kesalahan. Silahkan coba lagi.');</script>";
		echo "<script type='text/javascript'> document.location = 'ubah_foto.php'; </script>";
	}
?>