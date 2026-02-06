<?php
	include("sess_check.php");
	
	// query database memperbarui data pada database
	if(isset($_POST['perbarui'])) {
		$npplama				= $_POST['npplama'];
		$npp					= $_POST['npp'];
		$nama					= $_POST['nama'];
		$jml					= $_POST['jml'];
		$jk						= $_POST['jk'];
		$telp					= $_POST['telp'];
		$divisi					= $_POST['divisi'];
		$jabatan				= $_POST['jabatan'];
		$alamat					= $_POST['alamat'];
		$akses					= $_POST['akses'];

		$cekfoto				= $_FILES["foto"]["name"];
		$nomorktp				= $_POST['nomor_ktp'];
		$kotalahir				= $_POST['kota_lahir'];
		$alamattinggal			= $_POST['alamat_tinggal_sekarang'];
		$tanggallahir			= $_POST['tanggal_lahir'];
		$pendidikan				= $_POST['pendidikan_terakhir'];
		$institusi				= $_POST['nama_institusi'];
		$tanggalmasuk			= $_POST['tanggal_masuk karyawan'];
		$bpjs					= $_POST['status_bpjs'];
		$asuransi				= $_POST['asuransi_lain'];
		$norek					= $_POST['norek_mandiri'];
		
		$kesehatan				= $_POST['kesehatan'];
		$kacamata				= $_POST['kacamata'];
		$pass					= $_POST['password'];
		$hak_akses				= "Pegawai";
		$status_rem				= $_POST['status_rem'];

		if($npp != ""){
			$sqlcek = "SELECT * FROM employee WHERE npp='$npp'";
			$ress = mysqli_query($conn, $sqlcek);
			$rows = mysqli_num_rows($ress);
			
			if($rows < 1){
				if($cekfoto != ""){
					$foto = substr($_FILES["foto"]["name"], -5);
					$newfoto = "foto".$npp.$foto;				
					move_uploaded_file($_FILES["foto"]["tmp_name"], "foto/".$newfoto);

					$sql = "UPDATE employee SET
						npp = '$npp',
						nama_emp = '$nama',
						hak_akses = '$hak_akses',
						status_rem = '$status_rem',
						kesehatan = '$kesehatan',
						plafond = '$kesehatan',
						kacamata = '$kacamata',
						plafond_kacamata = '$kacamata', 
						foto = '$newfoto'
						WHERE npp = '$npplama'";
				} else {
					$sql = "UPDATE employee SET
						npp = '$npp',
						nama_emp = '$nama',
						hak_akses = '$hak_akses',
						status_rem = '$status_rem',
						kesehatan = '$kesehatan',
						plafond = '$kesehatan',
						kacamata = '$kacamata',
						plafond_kacamata = '$kacamata' 
						WHERE npp = '$npplama'";
				}
				$ress = mysqli_query($conn, $sql);
				header("location: karyawan.php?act=update&msg=success");
			} else {
				header("location: karyawan_edit.php?npp=$npplama&act=add&msg=double");			
			}
		} else {
			if($cekfoto != ""){
				$foto = substr($_FILES["foto"]["name"], -5);
				$newfoto = "foto".$npplama.$foto;				
				move_uploaded_file($_FILES["foto"]["tmp_name"], "foto/".$newfoto);

				$sql = "UPDATE employee SET
					nama_emp = '$nama',
					hak_akses = '$hak_akses',
					status_rem = '$status_rem',
					kesehatan = '$kesehatan',
					plafond = '$kesehatan',
					kacamata = '$kacamata',
					plafond_kacamata = '$kacamata',
					foto = '$newfoto'
					WHERE npp = '$npplama'";
			} else {
				$sql = "UPDATE employee SET
					nama_emp = '$nama',
					hak_akses = '$hak_akses',
					status_rem = '$status_rem',
					kesehatan = '$kesehatan',
					plafond = '$kesehatan',
					kacamata = '$kacamata',
					plafond_kacamata = '$kacamata' 
					WHERE npp = '$npplama'";
			}
			$ress = mysqli_query($conn, $sql);
			header("location: karyawan.php?act=update&msg=success");
		}
	}
?>
