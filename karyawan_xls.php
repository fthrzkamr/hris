<?php
	include("sess_check.php");

    header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=Karyawan DFM .xls");

	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");

$sql = "SELECT 
            B.nama_bagian,
            B.id,
            C.id AS koordinator_id,
            D.id AS manager_id,
            A.npp,
            A.nama_emp, 
            A.telp_emp, 
            A.jml_cuti,
            A.aktif, 
            A.status_karyawan,
            A.jk_emp,
            A.alamat,
            A.nomor_ktp,
            A.kota_lahir,
            A.alamat_tinggal_sekarang,
            A.tanggal_lahir,
            A.pendidikan_terakhir,
            A.nama_institusi,
            A.tanggal_masuk_karyawan,
            A.norek_mandiri,
            A.status_karyawan,
            A.nama_pasangan,
            A.pekerjaan,
            A.agama,
            A.nomor_tlp,
            A.nama_anak,
            A.jabatan,
            A.status_ptkp,
            A.jurusan,
            A.nomor_npwp,
            A.bpjs_kesehatan,
            A.nomor_emrg_pr,
            A.nomor_emrg_kd,
            A.gol_darah,
            A.status_kawin,
            A.nomor_kk,
            A.nama_bank,
            A.cabang,
            A.nomor_bpjs_ktr,
            A.status_karyawan,
            C.nama_koordinator,
            D.nama_manager
        FROM employee AS A 
        LEFT JOIN bagian AS B ON A.nama_bagian = B.id 
        LEFT JOIN koordinator AS C ON A.nama_koordinator = C.id 
        LEFT JOIN manager AS D ON A.nama_manager = D.id 
        ORDER BY A.nama_emp ASC";

	$query = mysqli_query($conn,$sql);
	// deskripsi halaman
	$pagedesc = "Laporan Data Karyawan ";
	$pagetitle = str_replace(" ", "_", $pagedesc)
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="">
	<meta name="author" content="">

	<title><?php echo $pagetitle ?></title>

	<link href="libs/images/dua.png" rel="icon" type="images/x-icon">


	<!-- Bootstrap Core CSS -->
	<link href="libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

	<!-- Custom CSS -->
	<link href="dist/css/offline-font.css" rel="stylesheet">
	<link href="dist/css/custom-report.css" rel="stylesheet">

	<!-- Custom Fonts -->
	<link href="libs/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
	
	<!-- jQuery -->
	<script src="libs/jquery/dist/jquery.min.js"></script>


</head>

<body>

	<section id="body-of-report">
		<div class="container-fluid">
			<h4><center>LAPORAN DATA KARYAWAN DUAFARMA GROUP</center></h4>
			<br />
			<table border="1" class="table table-bordered">
				<thead>
					<tr>
						<th>No</th>
						<th>ID Karyawan</th>
					    <th>Nama Karyawan</th>
					    <th>Jenis Kelamin</th>
					    <th>Nomor Telepon</th>
					    <th>Alamat</th>
					    <th>Alamat Tingal Sekarang</th>
					    <th>Kota Lahir</th>
					    <th>Tanggal Lahir</th>
					    <th>Pendidikan Terakhir</th>
					    <th>Nama Institusi Pendidikan</th>
					    <th>Jurusan</th>
                        <th>Nomor KTP</th>
                        <th>Nomor KK</th>
                        <th>Golongan Darah</th>
                        <th>Status Kawin</th>
                        <th>Nama Pasangan</th>
                        <th>Nomor Telepon</th>
                        <th>Pekerjaan</th>
                        <th>Agama</th>
                        <th>Nomor Emergensi 1</th>
                        <th>Nomor Emergensi 2</th>
                        <th>Bagian</th>
                        <th>Nama Koordinator</th>
                        <th>Nama Manager</th>
                        <th>Status PTKP</th>
                        <th>Status Karyawan</th>
                        <th>Cabang</th>
                        <th>Nomor Rekening</th>
                        <th>Nama Bank</th>
                        <th>Jumlah Cuti</th>
                        <th> Nomor NPWP</th>
                        <th>Nomor BPJS Kesehatan</th>
                        <th>Nomor BPSJS Ketenagaan Kerjaan</th>
                        <th>Tanggal Masuk Karyawan</th>
					</tr>
				</thead>
				<tbody >
					<?php
						$i=1;
						while($data = mysqli_fetch_array($query)) {
							echo '<tr>';
							echo '<td class="text-center">'. $i .'</td>';
							echo '<td>'. $data['npp'] .'</td>';
							echo '<td>'. $data['nama_emp'] .'</td>';
							echo '<td>'. $data['jk_emp'] .'</td>';
							echo '<td>'. $data['telp_emp'] .'</td>';
							echo '<td>'. $data['alamat'] .'</td>';
							echo '<td>'. $data['alamat_tinggal_sekarang'] .'</td>';
							echo '<td>'. $data['kota_lahir'] .'</td>';
							echo '<td>'. $data['tanggal_lahir'] .'</td>';
							echo '<td>'. $data['pendidikan_terakhir'] .'</td>';
							echo '<td>'. $data['nama_institusi'] .'</td>';
							echo '<td>'. $data['jurusan'] .'</td>';
							echo '<td>'. $data['nomor_ktp'] .'</td>';
							echo '<td>'. $data['nomor_kk'] .'</td>';
							echo '<td>'. $data['gol_darah'] .'</td>';
							echo '<td>'. $data['status_kawin'] .'</td>';
							echo '<td>'. $data['nama_pasangan'] .'</td>';
							echo '<td>'. $data['nomor_tlp'] .'</td>';
														echo '<td>'. $data['pekerjaan'] .'</td>';
							echo '<td>'. $data['agama'] .'</td>';
echo '<td>'. $data['nomor_emrg_pr'] .'</td>';
							echo '<td>'. $data['nomor_emrg_kd'] .'</td>';
							echo '<td>'. $data['nama_bagian'] .'</td>';
							echo '<td>'. $data['nama_koordinator'] .'</td>';
							echo '<td>'. $data['nama_manager'] .'</td>';
							echo '<td>'. $data['status_ptkp'] .'</td>';
							echo '<td>'. $data['status_karyawan'] .'</td>';
							echo '<td>'. $data['cabang'] .'</td>';
						   echo '<td>'. $data['norek_mandiri'] .'</td>';
						   echo '<td>'. $data['nama_bank'] .'</td>';
						   echo '<td>'. $data['jml_cuti'] .'</td>';
						   echo '<td>'. $data['nomor_npwp'] .'</td>';
						   echo '<td>'. $data['nomor_bpjs_ktr'] .'</td>';
						   echo '<td>'. $data['bpjs_kesehatan'] .'</td>';
							echo '<td>'. $data['tanggal_masuk_karyawan'] .'</td>';
							echo '</tr>';
							$i++;
						}
					?>
				</tbody>
			</table>
			<br />
		</div><!-- /.container -->
	</section>

	<script type="text/javascript">
		$(document).ready(function() {
			window.print();
		});
	</script>

	<!-- Bootstrap Core JavaScript -->
	<script src="libs/bootstrap/dist/js/bootstrap.min.js"></script>
	<!-- jTebilang JavaScript -->
	<script src="libs/jTerbilang/jTerbilang.js"></script>

</body>
</html>