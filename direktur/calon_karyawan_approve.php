<?php
	include("sess_check.php");
	
	$npp = mysqli_real_escape_string($conn, $_GET['npp']);
	
	// Check if employee exists and is pending
	$sql_check = "SELECT * FROM employee WHERE npp = '$npp' AND status_karyawan = 'Calon Karyawan' AND aktif = 'Menunggu Review'";
	$res_check = mysqli_query($conn, $sql_check);
	
	if(mysqli_num_rows($res_check) == 0) {
		echo '<script>alert("Data tidak ditemukan atau sudah diapprove"); window.location="calon_karyawan_list.php";</script>';
		exit;
	}
	
	$data_check = mysqli_fetch_array($res_check);
	$tanggal_lahir = $data_check['tanggal_lahir'];
	$foto_emp_lama = $data_check['foto_emp'];
	
	// Generate NPP baru otomatis: kombinasi tahun masuk (2 digit), tahun lahir (2 digit), 4 digit urutan karyawan terakhir
	$tahun_masuk_2digit = date('y'); // Contoh: 26 untuk 2026
	$tahun_lahir_2digit = !empty($tanggal_lahir) ? date('y', strtotime($tanggal_lahir)) : '00';
	$prefix = $tahun_masuk_2digit . $tahun_lahir_2digit;
	
	// Dapatkan urutan karyawan terakhir dengan prefix tersebut
	$query_last_npp = mysqli_query($conn, "SELECT npp FROM employee WHERE npp LIKE '{$prefix}%' ORDER BY npp DESC LIMIT 1");
	if ($query_last_npp && mysqli_num_rows($query_last_npp) > 0) {
		$last_npp = mysqli_fetch_assoc($query_last_npp)['npp'];
		$urutan = intval(substr($last_npp, 4)) + 1;
	} else {
		$urutan = 1;
	}
	
	$new_npp = $prefix . str_pad($urutan, 4, '0', STR_PAD_LEFT);
	
	// Rename file foto karyawan jika ada
	$dir_prefix = file_exists("../dist/config/koneksi.php") ? "../" : "";
	$new_foto_emp = $foto_emp_lama;
	if (!empty($foto_emp_lama) && file_exists($dir_prefix . $foto_emp_lama)) {
		$file_extension = strtolower(pathinfo($foto_emp_lama, PATHINFO_EXTENSION));
		$new_foto_filename = 'foto' . $new_npp . '.' . $file_extension;
		$new_foto_path = 'foto/karyawan/' . $new_foto_filename;
		if (rename($dir_prefix . $foto_emp_lama, $dir_prefix . $new_foto_path)) {
			$new_foto_emp = $new_foto_path;
		}
	}
	
	// Update status ke Aktif, NPP baru, password baru (sama dengan NPP), dan foto baru
	$sql = "UPDATE employee SET 
				npp = '$new_npp',
				password = '$new_npp',
				aktif = 'Aktif', 
				status_karyawan = 'Kontrak',
				status_karyawan_baru = 1,
				foto_emp = '$new_foto_emp'
			WHERE npp = '$npp'";
	
	if(mysqli_query($conn, $sql)) {
		echo '<script>alert("Calon karyawan berhasil diapprove. Akun sudah aktif."); window.location="calon_karyawan_edit.php?npp=' . $new_npp . '";</script>';
	} else {
		echo '<script>alert("Gagal mengapprove: ' . mysqli_error($conn) . '"); window.location="calon_karyawan_list.php";</script>';
	}
?>