<?php
	include("sess_check.php");

    header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=Karyawan.xls");

	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");

	$sql = "SELECT employee.* FROM  employee WHERE employee.npp";
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
	<meta name="author" content="universitas pamulang">

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

	<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
	<![endif]-->
</head>

<body>

	<section id="body-of-report">
		<div class="container-fluid">
			<h4><center>LAPORAN DATA KARYAWAN</center></h4>
			<br />
			<table border="1" class="table table-bordered">
				<thead>
					<tr>
						<th>No</th>
						<th>ID Karyawan</th>
						<th>Nama Karyawan</th>
						<th>Tanggal Masuk Kayawan</th>
						<th>Tanggal Lahir</th>
						<th>Nomor KTP</th>
						<th>Alamat KTP</th>
						<th>Alamat Tinggal Sekarang</th>
						<th>Kota Lahir</th>
						<th>Pendidikan Terakhir</th>
						<th>Nama Institusi Pendidikan Terakhir</th>
						<th>Nomor Rekening Mandiri (Aktif)</th>
						<th>Jenis Kelamin</th>
						<th>Nomor Handphone</th>
						<th>Nama Koordinator</th>
						<th>Nama Manager</th>
						<th>Bagian</th>
						<th>Jumlah Cuti</th>
						<th>Cabang</th>
						<th>Status Karyawan</th>
						<th>Status Pernikahan</th>
						<th>Nama Pasangan Suami/Istri</th>
						<th>Pekerjaan Suami/Istri</th>
						<th>Nomor Handphone</th>
						<th>Nama Anak</th>
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
							echo '<td>'. $data['tanggal_masuk_karyawan'] .'</td>';
							echo '<td>'. $data['tanggal_lahir'] .'</td>';
							echo '<td>'. $data['nomor_ktp'] .'</td>';
							echo '<td>'. $data['alamat'] .'</td>';
							echo '<td>'. $data['alamat_tinggal_sekarang'] .'</td>';
							echo '<td>'. $data['kota_lahir'] .'</td>';
							echo '<td>'. $data['pendidikan_terakhir'] .'</td>';
							echo '<td>'. $data['nama_institusi'] .'</td>';
							echo '<td>'. $data['norek_mandiri'] .'</td>';
							echo '<td>'. $data['jk_emp'] .'</td>';
							echo '<td>'. $data['telp_emp'] .'</td>';
							echo '<td>'. $data['nama_koordinator'] .'</td>';
							echo '<td>'. $data['nama_manager'] .'</td>';
							echo '<td>'. $data['nama_bagian'] .'</td>';
							echo '<td>'. $data['jml_cuti'] .'</td>';
							echo '<td>'. $data['cabang'] .'</td>';
							echo '<td>'. $data['aktif'] .'</td>';
							echo '<td>'. $data['status_kawin'] .'</td>';
							echo '<td>'. $data['nama_pasangan'] .'</td>';
							echo '<td>'. $data['pekerjaan'] .'</td>';
							echo '<td>'. $data['nomor_tlp'] .'</td>';
							echo '<td>'. $data['nama_anak'] .'</td>';
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