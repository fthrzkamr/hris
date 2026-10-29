<?php
	include("sess_check.php");

		$id				= $sess_mngid;
		$npp			= $_POST['npp'];
		$nama			= $_POST['nama'];
		$jk				= $_POST['jk'];
		$telp			= $_POST['telp'];
		$divisi			= $_POST['divisi'];
		$jabatan		= $_POST['jabatan'];
		$akses			= $_POST['akses'];
		$jml			= $_POST['jml'];
		$alamat			= $_POST['alamat'];
		$foto			= substr($_FILES["foto"]["name"],-5);
		$newfoto 		= "foto".$npp.$foto;

		// FIELD database baru
		$nomorktp						= $_POST['nomor_ktp'];
		$kotalahir						= $_POST['kota_lahir'];
		$alamattinggal					= $_POST['alamat_tinggal_sekarang'];
		$tgllahir						= $_POST['tanggal_lahir'];
		$pendidikan						= $_POST['pendidikan_terakhir'];
		$namainstitusi					= $_POST['nama_institusi'];
		$tglmasuk						= $_POST['tanggal_masuk_karyawan'];
		$status							= $_POST['status_bpjs'];
		$asuransilain					= $_POST['asuransi_lain'];
		$norekmandiri					= $_POST['norek_mandiri'];
		
		// end database baru

		$tgl 							= date('Y-m-d');
		$aktif 							= "Aktif";
		$no_tlp_emergency				= $_POST['no_tlp_emergency'];
		$status_alamat					= $_POST['status_alamat'];
		// baru - buat
		$cabang							= $_POST['cabang'];
		$nama_panggilan					= $_POST['nama_panggilan'];
		$nama_pasangan_kawin			= $_POST['nama_pasangan_kawin'];
		$tgl_pasangan_Kawin 			= $_POST['tgl_pasangan_kawin'];
		$kontak_emergensi				= $_POST['kontak_emergensi'];
		$hubungan						= $_POST['hubungan'];

		$sqlcek = "SELECT * FROM employee WHERE npp='$npp'";
		$resscek = mysqli_query($conn, $sqlcek);
		$rowscek = mysqli_num_rows($resscek);
		if($rowscek<1){
			$sql="INSERT INTO employee(npp,nama_emp,jk_emp,telp_emp,divisi,jabatan,alamat,hak_akses,jml_cuti,password,foto_emp,nomor_ktp,kota_lahir,alamat_tinggal_sekarang,tanggal_lahir,pendidikan_terakhir,nama_institusi,tanggal_masuk_karyawan,status_bpjs,asuransi_lain,norek_mandiri,active,id_adm,no_tlp_emergency,status_alamat,cabang,nama_panggilan,nama_pasangan_kawin,tgl_lahir_pasangan,kontak_emergensi,hubungan)
				VALUES('$npp','$nama','$jk','$telp','$divisi','$jabatan','$alamat','$akses','$jml','$npp','$newfoto','$nomorktp','$kotalahir','$alamattinggal','$tgllahir','$pendidikan','$namainstitusi','$tglmasuk','$status','$asuransilain','$norekmandiri','$aktif','$id','$no_tlp_emergency','$status_alamat','$cabang','$nama_panggilan','$nama_pasangan_kawin','$tgl_pasangan_kawin','$kontak_emergensi','$hubungan')";
			$ress = mysqli_query($conn, $sql);
			if($ress){
				move_uploaded_file($_FILES["foto"]["tmp_name"],"../foto/".$newfoto);
				echo "<script>alert('Tambah Karyawan Berhasil!');</script>";
				echo "<script type='text/javascript'> document.location = 'data_karyawan.php'; </script>";
			}else{
				echo("Error description: " . mysqli_error($conn));
				echo "<script>alert('Ops, terjadi kesalahan. Silahkan coba lagi.');</script>";
				echo "<script type='text/javascript'> document.location = 'data_karyawan_tambah.php'; </script>";
			}
		}else{
			header("location: data_karyawan_tambah.php?act=add&msg=double");	
		}
?>
