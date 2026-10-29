<?php
	include("sess_check.php");

if (isset($row_sess['status_karyawan']) && $row_sess['status_karyawan'] == 'Magang') {
	header("location: index.php");
	exit;
}

    header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=Data_Pinjaman_Karyawan.xls");

	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");

	$sql = "SELECT p.id_pinjaman, p.npp, e.nama_emp, p.tanggal_pengajuan, p.jumlah_pinjaman, 
	               p.tenor, p.cicilan_per_bulan, p.tanggal_lunas, p.status, p.keterangan
            FROM pinjaman p
            JOIN employee e ON p.npp = e.npp
            ORDER BY p.tanggal_pengajuan DESC";
	$query = mysqli_query($conn,$sql);
	
	// deskripsi halaman
	$pagedesc = "Laporan Data Pinjaman Karyawan";
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
			<h4><center>LAPORAN DATA PINJAMAN KARYAWAN</center></h4>
			<br />
			<table border="1" class="table table-bordered">
				<thead>
					<tr>
						<th>No</th>
						<th>ID Pinjaman</th>
						<th>NPP</th>
						<th>Nama Karyawan</th>
						<th>Tanggal Pengajuan</th>
						<th>Jumlah Pinjaman</th>
						<th>Tenor (Bulan)</th>
						<th>Cicilan per Bulan</th>
						<th>Tanggal Lunas</th>
						<th>Sisa Pinjaman</th>
						<th>Sisa Tenor</th>
						<th>Status</th>
						<th>Keterangan</th>
					</tr>
				</thead>
				<tbody>
					<?php
						$i=1;
						while($data = mysqli_fetch_array($query)) {
							// Hitung total angsuran yang sudah dibayar
							$id_pinjaman = $data['id_pinjaman'];
							$sql_angsuran = "SELECT 
											    SUM(CASE WHEN status = 'dibayar' THEN jumlah_angsuran ELSE 0 END) AS total_dibayar,
											    COUNT(CASE WHEN status = 'dibayar' THEN 1 END) AS bulan_dibayar
											FROM angsuran_pinjaman 
											WHERE id_pinjaman = '$id_pinjaman'";
							$res_angsuran = mysqli_query($conn, $sql_angsuran);
							$angsuran = mysqli_fetch_assoc($res_angsuran);
							
							$total_dibayar = $angsuran['total_dibayar'] ?? 0;
							$bulan_dibayar = $angsuran['bulan_dibayar'] ?? 0;

							// Hitung sisa pinjaman
							$sisa_pinjaman = $data['jumlah_pinjaman'] - $total_dibayar;
							
							// Hitung sisa tenor
							$sisa_tenor = $data['tenor'] - $bulan_dibayar;

							echo '<tr>';
							echo '<td class="text-center">'. $i .'</td>';
							echo '<td>'. $data['id_pinjaman'] .'</td>';
							echo '<td>'. $data['npp'] .'</td>';
							echo '<td>'. $data['nama_emp'] .'</td>';
							echo '<td>'. format_tanggal($data['tanggal_pengajuan']) .'</td>';
							echo '<td>'. format_rupiah($data['jumlah_pinjaman']) .'</td>';
							echo '<td class="text-center">'. $data['tenor'] .'</td>';
							echo '<td>'. format_rupiah($data['cicilan_per_bulan']) .'</td>';
							echo '<td>'. format_tanggal($data['tanggal_lunas']) .'</td>';
							echo '<td>'. format_rupiah($sisa_pinjaman) .'</td>';
							echo '<td class="text-center">'. $sisa_tenor .'x</td>';
							echo '<td class="text-center">'. ucfirst($data['status']) .'</td>';
							echo '<td>'. $data['keterangan'] .'</td>';
							echo '</tr>';
							$i++;
						}
					?>
				</tbody>
			</table>
		</div>
	</section>
</body>
</html>
