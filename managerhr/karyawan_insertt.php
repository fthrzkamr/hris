<?php
	include("sess_check.php");

		$id				= $sess_admid;
		$npp			= $_POST['npp'];
		$nama			= $_POST['nama'];
		$jk				= $_POST['jk'];
		$telp			= $_POST['telp'];
		// $divisi			= $_POST['divisi'];
		$jml			= $_POST['jml'];
		$alamat			= $_POST['alamat'];
		$hak_akses 		= "karyawan";
		$foto			= substr($_FILES["foto"]["name"],-5);
		$newfoto 		= "foto".$npp.$foto;

		// FIELD database baru
		$nomorktp		= $_POST['nomor_ktp'];
		$kotalahir		= $_POST['kota_lahir'];
		$alamattinggal	= $_POST['alamat_tinggal_sekarang'];
		$tgllahir		= $_POST['tanggal_lahir'];
		$pendidikan		= $_POST['pendidikan_terakhir'];
		$namainstitusi	= $_POST['nama_institusi'];
		$tglmasuk		= $_POST['tanggal_masuk_karyawan'];
		$norekmandiri	= $_POST['norek_mandiri'];
		// end database baru

		$tgl			 = date('Y-m-d');
		$aktif			 = "Aktif";
		$cabang			= $_POST['cabang'];
		$status_rem 	= "N/A";
		$statuskawin	= $_POST['status_kawin'];
		$namapasangan	= $_POST['nama_pasangan'];
		$pekerjaan		= $_POST['pekerjaan'];
		$nomortlp		= $_POST['nomor_tlp'];
		$namaanak		= $_POST['nama_anak'];
		$namakoordinator= $_POST['nama_koordinator'];
		$namamanager	= $_POST['nama_manager'];
		$nama_bagian	= $_POST['nama_bagian'];
		$kesehatan		= "0";

		$sqlcek = "SELECT * FROM employee WHERE npp='$npp'";
		$resscek = mysqli_query($conn, $sqlcek);
		$rowscek = mysqli_num_rows($resscek);
		if($rowscek<1){
			$sql="INSERT INTO employee(npp,nama_emp,jk_emp,telp_emp,alamat,hak_akses,jml_cuti,password,foto_emp,nomor_ktp,kota_lahir,alamat_tinggal_sekarang,tanggal_lahir,pendidikan_terakhir,nama_institusi,tanggal_masuk_karyawan,norek_mandiri,aktif,id_adm,cabang,status_rem,status_kawin,nama_pasangan,pekerjaan,nomor_tlp,nama_anak,nama_koordinator,nama_manager,nama_bagian,kesehatan)
				VALUES('$npp','$nama','$jk','$telp','$alamat','$hak_akses','$jml','$npp','$newfoto','$nomorktp','$kotalahir','$alamattinggal','$tgllahir','$pendidikan','$namainstitusi','$tglmasuk','$norekmandiri','$aktif','$id','$cabang','$status_rem','$statuskawin','$namapasangan','$pekerjaan','$nomortlp','$namaanak','$namakoordinator','$namamanager','$nama_bagian','$kesehatan')";
			$ress = mysqli_query($conn, $sql);
			// var_dump($sql);
			// exit();
			if($ress){
				move_uploaded_file($_FILES["foto"]["tmp_name"],"foto/".$newfoto);
				echo "<script>alert('Tambah Karyawan Berhasil!');</script>";
				echo "<script type='text/javascript'> document.location = 'karyawann.php'; </script>";
			}else{
				echo("Error description: " . mysqli_error($conn));
				echo "<script>alert('Ops, terjadi kesalahan. Silahkan coba lagi.');</script>";
				echo "<script type='text/javascript'> document.location = 'karyawan_tambahh.php'; </script>";
			}
		}else{
			header("location: karyawan_tambahh.php?act=add&msg=double");	
		}
?>