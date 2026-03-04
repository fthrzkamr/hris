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
	
	// Update status to Aktif
	$sql = "UPDATE employee SET aktif = 'Aktif', status_karyawan = 'Kontrak', status_karyawan_baru = 1 WHERE npp = '$npp'";
	
	if(mysqli_query($conn, $sql)) {
		echo '<script>alert("Calon karyawan berhasil diapprove. Akun sudah aktif."); window.location="calon_karyawan_edit.php?npp=' . $npp . '";</script>';
	} else {
		echo '<script>alert("Gagal mengapprove: ' . mysqli_error($conn) . '"); window.location="calon_karyawan_list.php";</script>';
	}
?>