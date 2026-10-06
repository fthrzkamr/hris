<?php
	include("sess_check.php");
	
	if (isset($_POST['perbarui'])) {
		$npplama					= $_POST['npplama'];
		$npp						= trim($_POST['npp']);
		$nama						= $_POST['nama'];
		$jml						= $_POST['jml'];
		$jk							= $_POST['jk'];
		$telp						= $_POST['telp'];
		$jabatan					= $_POST['jabatan'];
		$alamat						= $_POST['alamat'];
		$nomorktp					= $_POST['nomor_ktp'];
		$kotalahir					= $_POST['kota_lahir'];
		$alamattinggal				= $_POST['alamat_tinggal_sekarang'];
		$tanggallahir				= $_POST['tanggal_lahir'];
		$pendidikan					= $_POST['pendidikan_terakhir'];
		$institusi					= $_POST['nama_institusi'];
		$tanggalmasuk				= $_POST['tanggal_masuk_karyawan'];
		$norek						= $_POST['norek_mandiri'];
		$aktif						= $_POST['aktif'];
		$cabang						= $_POST['cabang'];
		$nama_koordinator			= $_POST['nama_koordinator'];
		$nama_manager				= $_POST['nama_manager'];
		$nama_bagian				= $_POST['nama_bagian'];
		$status_kawin				= $_POST['status_kawin'];
		$nama_pasangan				= $_POST['nama_pasangan'];
		$pekerjaan					= $_POST['pekerjaan'];
		$nomor_tlp					= $_POST['nomor_tlp'];
		$nama_anak					= $_POST['nama_anak'];
		$status_ptkp				= $_POST['status_ptkp'];
		$jurusan					= $_POST['jurusan'];
		$nomor_npwp					= $_POST['nomor_npwp'];
		$bpjs_kesehatan				= $_POST['bpjs_kesehatan'];
		$nomor_bpjs_ktr				= $_POST['nomor_bpjs_ktr'];
		$nama_bank					= $_POST['nama_bank'];
		$agama						= $_POST['agama'];
		$nomor_emrg_pr				= $_POST['nomor_emrg_pr'];
		$nomor_emrg_kd				= $_POST['nomor_emrg_kd'];
		$gol_darah					= $_POST['gol_darah'];
		$nomor_kk					= $_POST['nomor_kk'];
		$status_karyawan			= $_POST['status_karyawan'];

		$cekfoto					= $_FILES["foto"]["name"];

		// Jika NPP baru dikosongkan, gunakan NPP lama
		if (empty($npp)) {
			$npp = $npplama;
		}

		// Cek apakah NPP baru sudah dipakai karyawan LAIN
		if ($npp != $npplama) {
			$sqlcek = "SELECT * FROM employee WHERE npp='$npp' AND npp != '$npplama'";
			$ress = mysqli_query($conn, $sqlcek);
			if (mysqli_num_rows($ress) > 0) {
				header("location: karyawan_edit.php?npp=$npplama&act=add&msg=double");
				exit();
			}
		}

		// Ambil hak_akses yang sudah ada dari database agar tidak berubah saat update
		$q_akses = mysqli_query($conn, "SELECT hak_akses FROM employee WHERE npp='$npplama'");
		$r_akses = mysqli_fetch_assoc($q_akses);
		$hak_akses = $r_akses ? $r_akses['hak_akses'] : 'Pegawai';

		// Proses foto
		if ($cekfoto != "") {
			$foto = substr($_FILES["foto"]["name"], -5);
			$newfoto = "foto" . $npplama . $foto;
			move_uploaded_file($_FILES["foto"]["tmp_name"], "foto/" . $newfoto);
			$foto_sql = ", foto_emp='$newfoto'";
		} else {
			$foto_sql = "";
		}

		$sql = "UPDATE employee SET
			npp='$npp',
			nama_emp='$nama',
			hak_akses='$hak_akses',
			jk_emp='$jk',
			telp_emp='$telp',
			alamat='$alamat',
			jml_cuti='$jml',
			nomor_ktp='$nomorktp',
			kota_lahir='$kotalahir',
			alamat_tinggal_sekarang='$alamattinggal',
			tanggal_lahir='$tanggallahir',
			pendidikan_terakhir='$pendidikan',
			nama_institusi='$institusi',
			tanggal_masuk_karyawan='$tanggalmasuk',
			norek_mandiri='$norek',
			aktif='$aktif',
			cabang='$cabang',
			nama_koordinator='$nama_koordinator',
			nama_manager='$nama_manager',
			nama_bagian='$nama_bagian',
			status_kawin='$status_kawin',
			nama_pasangan='$nama_pasangan',
			pekerjaan='$pekerjaan',
			nomor_tlp='$nomor_tlp',
			nama_anak='$nama_anak',
			jabatan='$jabatan',
			status_ptkp='$status_ptkp',
			jurusan='$jurusan',
			nomor_npwp='$nomor_npwp',
			bpjs_kesehatan='$bpjs_kesehatan',
			nomor_bpjs_ktr='$nomor_bpjs_ktr',
			nama_bank='$nama_bank',
			agama='$agama',
			nomor_emrg_pr='$nomor_emrg_pr',
			nomor_emrg_kd='$nomor_emrg_kd',
			gol_darah='$gol_darah',
			nomor_kk='$nomor_kk',
			status_karyawan='$status_karyawan'
			$foto_sql
			WHERE npp='$npplama'";

		$ress = mysqli_query($conn, $sql);
		$affected = mysqli_affected_rows($conn);

		if ($ress && $affected >= 0) {
			header("location: karyawan.php?act=update&msg=success");
		} else {
			die("Update gagal: " . mysqli_error($conn) . "<br>SQL: " . $sql);
		}
	}
?>
