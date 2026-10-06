<?php
include("sess_check.php");

$npp = $_POST['npp'];
$nama_karyawan = $_POST['nama_karyawan'];
$tujuan_lembur = $_POST['tujuan_lembur'];
// Jika ada sub-kategori yang dikirim (form JS mungkin sudah mengganti nilai tujuan), gunakan itu
$sub_kategori_lembur = '';
if (isset($_POST['sub_kategori_lembur']) && trim($_POST['sub_kategori_lembur']) !== '') {
	$sub_kategori_lembur = trim($_POST['sub_kategori_lembur']);
	$tujuan_lembur = 'Lembur ' . $sub_kategori_lembur;

	// Simpan sub-kategori ke tabel tujuan_lembur jika belum ada
	if (!empty($sub_kategori_lembur) && isset($conn)) {
		$sub_esc = mysqli_real_escape_string($conn, $sub_kategori_lembur);
		$check_sql = "SELECT * FROM tujuan_lembur WHERE nama_tujuan = '$sub_esc'";
		$res_check = mysqli_query($conn, $check_sql);
		if ($res_check && mysqli_num_rows($res_check) == 0) {
			$insert_tujuan_sql = "INSERT INTO tujuan_lembur (nama_tujuan, created_at) VALUES ('$sub_esc', NOW())";
			if (!mysqli_query($conn, $insert_tujuan_sql)) {
				error_log('tujuan_lembur insert error: ' . mysqli_error($conn));
			}
		}
	}
}
$cabang = $_POST['cabang'];
$tgl_lembur = $_POST['tgl_lembur'];
$jam_mulai_lembur = $_POST['jam_mulai_lembur'];
$jam_berakhir_lembur = $_POST['jam_berakhir_lembur'];
$nama_koordinator = $_POST['nama_koordinator'];
$alasan_lembur = $_POST['alasan_lembur'];
$status = "Menunggu Approval";
$jumlah = $_POST['jumlah'];

$reject = "kosong";



$id = date('dmYHis');
$sqlcek = "SELECT * FROM lembur WHERE npp='$npp'";
$resscek = mysqli_query($conn, $sqlcek);
$rowscek = mysqli_fetch_array($resscek);


if ($npp) {
	$sql = "INSERT INTO lembur (id_lmbr,npp, nama_karyawan, tujuan_lembur, cabang, tgl_lembur, jam_mulai_lembur, jam_berakhir_lembur, nama_koordinator,alasan_lembur,status,jumlah,reject) 
            VALUES ('$id','$npp','$nama_karyawan','$tujuan_lembur','$cabang','$tgl_lembur','$jam_mulai_lembur','$jam_berakhir_lembur','$nama_koordinator','$alasan_lembur','$status','$jumlah','$reject')";
	$query = mysqli_query($conn, $sql);
	// var_dump($query);
	// exit();


	if ($query) {
		echo "<script type='text/javascript'>
						alert('Pengajuan Lembur berhasil!'); 
						document.location = 'lembur_waitapp.php'; 
					</script>";

	} else {
		error_log('lembur insert error: ' . mysqli_error($conn));
		echo "<script type='text/javascript'>
						alert('Terjadi kesalahan, silahkan coba lagi!.'); 
						document.location = 'lembur_create.php'; 
					</script>";
	}
} else {
	header("location: lembur_insert.php?act=add&msg=double");

}
?>