<?php
	include("sess_check.php");

    header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=reimburse.xls");

	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");

	$Sql = "SELECT rembes.*, employee.* FROM rembes, employee WHERE rembes.npp=employee.npp 
			ORDER BY rembes.tanggal_pemeriksaan DESC";
	$query = mysqli_query($conn,$Sql);
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
	<meta name="author" content="testing">

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
			<h4><center>LAPORAN DATA REIMBURSE</center></h4>
			<br />
			<table border="1" class="table table-bordered">
				<thead>
					<tr>
						<th>No</th>
					     <th>NIK</th>
						<th>Nama Karyawan</th>
						<th>Nama Fasilitas Kesehatan</th>
						<th>Tgl Pemeriksaan</th>
						<th>Total Kwitansi</th>
						<th>Total Rembes</th>
						<th>Sisa Limit</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody >
					<?php
						$i=1;
						while($data = mysqli_fetch_array($query)) {
                            $rembes = $data['total_kwitansi'];
                            $a		=(80 / 100);
                            $p		= $rembes * $a ;
							echo '<tr>';
							echo '<td class="text-center">'. $i .'</td>';
							echo '<td>'. $data['npp'] .'</td>';
							echo '<td>'. $data['nama_emp'] .'</td>';
							echo '<td>'. $data['nama_dokter'] .'</td>';
							echo '<td>'. $data['tanggal_pemeriksaan'] .'</td>';
							echo '<td>'. $data['total_kwitansi'] .'</td>';
                            echo '<td class="text-center">'. $p .'</td>';
                            echo '<td>'. $data['kesehatan'] .'</td>';
							echo '<td>'. $data['status'] .'</td>';
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