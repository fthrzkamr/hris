<?php
// setting tanggal
$haries = array("Sunday" => "Minggu", "Monday" => "Senin", "Tuesday" => "Selasa", "Wednesday" => "Rabu", "Thursday" => "Kamis", "Friday" => "Jum'at", "Saturday" => "Sabtu");
$bulans = array("", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember");
$bulans_count = count($bulans);
// tanggal bulan dan tahun hari ini
$hari_ini = $haries[date("l")];
$bulan_ini = $bulans[date("n")];
$tanggal = date("d");
$bulan = date("m");
$tahun = date("Y");

// Count pending request slip gaji untuk badge notifikasi
$count_pending_slip = 0;
if (isset($conn)) {
	$sql_count_slip = "SELECT COUNT(*) as total FROM request_slip_gaji WHERE status = 'pending'";
	$result_count_slip = mysqli_query($conn, $sql_count_slip);
	if ($result_count_slip) {
		$count_pending_slip = mysqli_fetch_assoc($result_count_slip)['total'];
	}
}

// Count calon karyawan menunggu review untuk badge notifikasi
$count_pending_calon = 0;
if (isset($conn)) {
	$sql_calon = "SELECT COUNT(*) as total FROM employee WHERE status_karyawan = 'Calon Karyawan' AND aktif = 'Menunggu Review'";
	$result_calon = mysqli_query($conn, $sql_calon);
	if ($result_calon) {
		$count_pending_calon = mysqli_fetch_assoc($result_calon)['total'];
	}
}

// Count perjalanan dinas pending approval untuk badge notifikasi
$count_pending_perjalanan = 0;
if (isset($conn)) {
	$sql_perjalanan = "SELECT COUNT(*) as total FROM perjalanan_pengajuan p2
		INNER JOIN (
			SELECT id_perjalanan, MAX(id) AS mid FROM perjalanan_pengajuan GROUP BY id_perjalanan
		) m ON p2.id_perjalanan = m.id_perjalanan AND p2.id = m.mid
		WHERE p2.status IN ('DIAJUKAN', 'APPROVED_HR', 'REALISASI_DIAJUKAN', 'REALISASI_VERIFIED_HR')";	
	$result_perjalanan = mysqli_query($conn, $sql_perjalanan);
	if ($result_perjalanan) {
		$count_pending_perjalanan = mysqli_fetch_assoc($result_perjalanan)['total'];
	}
}

// Count training pending approval untuk badge notifikasi
$count_pending_training = 0;
if (isset($conn)) {
	$sql_training = "SELECT COUNT(*) as total FROM pengajuan_training WHERE status IS NULL OR status = 'Pending'";
	$result_training = mysqli_query($conn, $sql_training);
	if ($result_training) {
		$count_pending_training = mysqli_fetch_assoc($result_training)['total'];
	}
}



// Notifikasi Approval Baru (Dengan Filter Skop Dinamis)
$scope_filter = "";
if (isset($sess_mngid) && isset($conn)) {
    $id_koor = '-1'; $id_man = '-1'; $id_lead = '-1';
    
    if ($res_k = mysqli_query($conn, "SELECT id_koordinator FROM koordinator WHERE npp='$sess_mngid'")) {
        if ($row_k = mysqli_fetch_assoc($res_k)) $id_koor = $row_k['id_koordinator'];
    }
    if ($res_m = mysqli_query($conn, "SELECT id_manager FROM manager WHERE npp='$sess_mngid'")) {
        if ($row_m = mysqli_fetch_assoc($res_m)) $id_man = $row_m['id_manager'];
    }
    if ($res_l = mysqli_query($conn, "SELECT id_leader FROM leader WHERE npp='$sess_mngid'")) {
        if ($row_l = mysqli_fetch_assoc($res_l)) $id_lead = $row_l['id_leader'];
    }
    
    if ($id_koor != '-1') $scope_filter = " AND employee.nama_koordinator='$id_koor' ";
    elseif ($id_man != '-1') $scope_filter = " AND employee.nama_manager='$id_man' ";
    elseif ($id_lead != '-1') $scope_filter = " AND employee.nama_leader='$id_lead' ";
}

$count_pending_cuti = 0;
if (isset($conn)) {
    $res_cuti = mysqli_query($conn, "SELECT COUNT(*) as total FROM cuti JOIN employee ON cuti.npp=employee.npp WHERE (cuti.stt_cuti LIKE '%Menunggu%' OR cuti.stt_cuti LIKE '%Pending%') $scope_filter");
    if ($res_cuti) $count_pending_cuti = mysqli_fetch_assoc($res_cuti)['total'];
}

$count_pending_lembur = 0;
if (isset($conn)) {
    $res_lembur = mysqli_query($conn, "SELECT COUNT(*) as total FROM lembur JOIN employee ON lembur.npp=employee.npp WHERE (lembur.status LIKE '%Menunggu%' OR lembur.status LIKE '%Pending%') $scope_filter");
    if ($res_lembur) $count_pending_lembur = mysqli_fetch_assoc($res_lembur)['total'];
}

$count_pending_reimburse = 0;
if (isset($conn)) {
    $res_reimb = mysqli_query($conn, "SELECT COUNT(*) as total FROM rembes JOIN employee ON rembes.npp=employee.npp WHERE (rembes.status LIKE '%Menunggu%' OR rembes.status LIKE '%Pending%') $scope_filter");
    if ($res_reimb) $count_pending_reimburse = mysqli_fetch_assoc($res_reimb)['total'];
}

$count_pending_kacamata = 0;
if (isset($conn)) {
    $res_kaca = mysqli_query($conn, "SELECT COUNT(*) as total FROM kacamata JOIN employee ON kacamata.npp=employee.npp WHERE (kacamata.status LIKE '%Menunggu%' OR kacamata.status LIKE '%Pending%') $scope_filter");
    if ($res_kaca) $count_pending_kacamata = mysqli_fetch_assoc($res_kaca)['total'];
}

$count_pending_permintaan = 0;
if (isset($conn)) {
    $res_permintaan = mysqli_query($conn, "SELECT COUNT(*) as total FROM permintaan_karyawan JOIN employee ON permintaan_karyawan.npp=employee.npp WHERE (permintaan_karyawan.status LIKE '%Menunggu%' OR permintaan_karyawan.status LIKE '%Pending%') $scope_filter");
    if ($res_permintaan) $count_pending_permintaan = mysqli_fetch_assoc($res_permintaan)['total'];
}

$count_pending_mobil = 0;
if (isset($conn)) {
    $res_mobil = mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman_mobil WHERE status = 'Menunggu'");
    if ($res_mobil) $count_pending_mobil = mysqli_fetch_assoc($res_mobil)['total'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="">
	<meta name="author" content="">

	<title>HRIS DF Group <?php echo $pagedesc ?></title>

	<link href="libs/images/dua.png" rel="icon" type="images/x-icon">

	<!-- Bootstrap Core CSS -->
	<link href="libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

	<!-- MetisMenu CSS -->
	<link href="libs/metisMenu/dist/metisMenu.min.css" rel="stylesheet">

	<!-- DataTables CSS -->
	<link href="libs/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet">

	<!-- DataTables Responsive CSS -->
	<link href="libs/datatables-responsive/css/dataTables.responsive.css" rel="stylesheet">

	<!-- Custom CSS -->
	<link href="dist/css/sb-admin-2.css" rel="stylesheet">
	<link href="dist/css/offline-font.css" rel="stylesheet">
	<link href="dist/css/custom.css" rel="stylesheet">

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

	<div id="wrapper">

		<!-- Navigation -->
		<nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand hidden-xs" href="index.php">
					<img src="libs/images/dua.png" alt="brand" width="32" class="float-left image-brand">
					<div class="float-right">&nbsp;<strong>DF Group </strong></div>
					<div class="clear-both"></div>
				</a>
				<a class="navbar-brand visible-xs" href="index.php">
					<img src="libs/images/dua.png" alt="brand" width="32" class="float-left image-brand">
					<div class="float-right">&nbsp;<strong>DF Group</strong></div>
					<div class="clear-both"></div>
				</a>
			</div><!-- /.navbar-header -->

			<ul class="nav navbar-top-links navbar-right">
				<li class="dropdown dropdown-right">
					<a class="dropdown-toggle" data-toggle="dropdown" href="#">
						<i class="fa fa-user fa-fw"></i>&nbsp;<?php echo ucfirst($sess_admname); ?>&nbsp;<i
							class="fa fa-caret-down"></i>
					</a>
					<ul class="dropdown-menu dropdown-user">
						<li><a href="pengaturan.php"><i class="fa fa-gear fa-fw"></i>&nbsp;Pengaturan Akun</a></li>
						<li class="divider"></li>
						<li><a href="logout.php"><i class="fa fa-sign-out fa-fw"></i> Keluar</a></li>
					</ul><!-- /.dropdown-user -->
				</li><!-- /.dropdown -->
			</ul><!-- /.navbar-top-links -->

			<div class="navbar-default sidebar" role="navigation">
				<div class="sidebar-nav navbar-collapse">
					<ul class="nav" id="side-menu">
						<li class="sidebar-search">
							<h4>HRIS<br> <b>DF Group </b></h4>
							<h5 class="text-muted"><i
									class="fa fa-calendar fa-fw"></i>&nbsp;<?php echo $hari_ini . ", " . $tanggal . " " . $bulan_ini . " " . $tahun ?>
							</h5>
						</li>
						<?php
						if ($pagedesc == "Beranda") {
							echo '<li><a href="index.php" class="active"><i class="fa fa-home fa-fw"></i>&nbsp;Beranda</a></li>';
						} else {
							echo '<li><a href="index.php"><i class="fa fa-home fa-fw"></i>&nbsp;Beranda</a></li>';
						}
						
						// Menu Calon Karyawan dengan badge notifikasi
						if ($pagedesc == "Data Calon Karyawan") {
							echo '<li><a href="calon_karyawan_list.php" class="active"><i class="fa fa-user-plus fa-fw"></i>&nbsp;Calon Karyawan';
							if($count_pending_calon > 0) {
								echo ' <span class="badge" style="background-color: #d9534f; margin-left: 5px;">' . $count_pending_calon . '</span>';
							}
							echo '</a></li>';
						} else {
							echo '<li><a href="calon_karyawan_list.php"><i class="fa fa-user fa-fw"></i>&nbsp;Calon Karyawan';
							if($count_pending_calon > 0) {
								echo ' <span class="badge" style="background-color: #d9534f; margin-left: 5px;">' . $count_pending_calon . '</span>';
							}
							echo '</a></li>';
						}

						// Menu Hasil Test Calon Karyawan
						if ($pagedesc == "Hasil Test Calon") {
							echo '<li><a href="test_calon_list.php" class="active"><i class="fa fa-pencil-square-o fa-fw"></i>&nbsp;Hasil Test Calon</a></li>';
						} else {
							echo '<li><a href="test_calon_list.php"><i class="fa fa-pencil-square-o fa-fw"></i>&nbsp;Hasil Test Calon</a></li>';
						}
						
						if (isset($menuparent) && $menuparent == "master") {
							echo '<li class="active">';
						} else {
							echo '<li>';
						}
						?>
						<!-- open <li> tag generated with php, see line 134-139 -->
						<a href="#"><i class="fa fa-database fa-fw"></i>&nbsp;Master Data <span
								class="fa arrow"></span></a>
						<ul class="nav nav-second-level">
							<?php
							if ($pagedesc == "Data Karyawan") {
								echo '<li><a href="karyawan.php" class="active">Data Karyawan</a></li>';
							} else {
								echo '<li><a href="karyawan.php">Data Karyawan</a></li>';
							}
							if ($pagedesc == "Data Manager") {
								echo '<li><a href="manager.php" class="active">Data Manager</a></li>';
							} else {
								echo '<li><a href="manager.php" >Data Manager</a></li>';
							}
							if ($pagedesc == "Data Koordinator") {
								echo '<li><a href="koordinator.php" class="active">Data Koordinator</a></li>';
							} else {
								echo '<li><a href="koordinator.php">Data Koordinator</a></li>';
							}
							if ($pagedesc == "Data Bagian") {
								echo '<li><a href="bagian.php" class="active">Data Bagian</a></li>';
							} else {
								echo '<li><a href="bagian.php">Data Bagian</a></li>';
							}

							?>
						</ul><!-- /.nav-second-level -->
						</li>

						<!-- Pengajuan karyawan menu -->
						<?php
						// Link to Pengajuan Karyawan
						if (
							isset($pagedesc) && $pagedesc == "Pengajuan Karyawan"
						) {
							echo '<li><a href="permintaan_karyawan_list.php" class="active"><i class="fa fa-users fa-fw"></i>&nbsp;Pengajuan Karyawan</a></li>';
						} else {
							echo '<li><a href="permintaan_karyawan_list.php"><i class="fa fa-users fa-fw"></i>&nbsp;Pengajuan Karyawan</a></li>';
						}
						?>

						<!-- Form Permintaan Karyawan Baru -->

                        <li>
                            <a href="#"><i class="fa fa-users fa-fw"></i> Permintaan Karyawan<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li>
                                    <a href="form_permintaan_karyawan.php"><i class="fa fa-plus-circle"></i> Buat Permintaan Baru</a>
                                </li>
                                <li>
                                    <a href="permintaan_karyawan_list.php"><i class="fa fa-list"></i> Daftar Permintaan Saya</a>
                                </li>
                            </ul>
                        </li>
						<?php
						if (isset($menuparent) && $menuparent == "approval") {
							echo '<li class="active">';
						} else {
							echo '<li>';
						}
						?>

						<!-- Perjalanan Dinas Menu -->
						<?php
						if (isset($menuparent) && $menuparent == "perjalanan_dinas") {
							echo '<li class="active">';
						} else {
							echo '<li>';
						}
						$badge_perjalanan = ($count_pending_perjalanan > 0) ? ' <span class="badge" style="background-color: #d9534f; margin-left: 5px;">' . $count_pending_perjalanan . '</span>' : '';
						?>
							<a href="#"><i class="fa fa-plane fa-fw"></i> Perjalanan Dinas<?php echo $badge_perjalanan; ?><span class="fa arrow"></span></a>
							<ul class="nav nav-second-level">
								<li>
									<a href="perjalanan_dinas_list.php" class="<?php echo ($pagedesc == 'Daftar Perjalanan Dinas') ? 'active' : ''; ?>">
										<i class="fa fa-check-square-o fa-fw"></i> Daftar Perjalanan Dinas
										<?php echo $badge_perjalanan; ?>
									</a>
								</li>
								<li class="divider"></li>
								<li><a href="form_perjalanan_dinas.php"><i class="fa fa-plus fa-fw"></i> Buat Pengajuan Baru</a></li>
								<li><a href="perjalanan_dinas_saya.php"><i class="fa fa-user fa-fw"></i> Daftar Pengajuan Saya</a></li>
							</ul>
						</li>

						<!-- Menu Training -->
						<?php
						if (isset($menuparent) && $menuparent == "training") {
							echo '<li class="active">';
						} else {
							echo '<li>';
						}
						$badge_training = ($count_pending_training > 0) ? ' <span class="badge" style="background-color: #d9534f; margin-left: 5px;">' . $count_pending_training . '</span>' : '';
						?>
							<a href="#"><i class="fa fa-graduation-cap fa-fw"></i> Pengajuan Training<?php echo $badge_training; ?><span class="fa arrow"></span></a>
							<ul class="nav nav-second-level">
								<li><a href="training_list.php" class="<?php echo ($pagedesc == 'Daftar Training') ? 'active' : ''; ?>"><i class="fa fa-check-square-o fa-fw"></i> Approval Training<?php echo $badge_training; ?></a></li>
								<li class="divider"></li>
								<li><a href="form_pengajuan_training.php"><i class="fa fa-plus fa-fw"></i> Buat Pengajuan Baru</a></li>
								<li><a href="training_status.php"><i class="fa fa-user fa-fw"></i> Status Pengajuan Saya</a></li>
							</ul>
						</li>

						<!-- Insentif menu -->
						<?php
						if (isset($menuparent) && $menuparent == "insentif") {
							echo '<li class="active">';
						} else {
							echo '<li>';
						}
						?>
						<a href="#"><i class="fa fa-gift fa-fw"></i>&nbsp;Insentif<span class="fa arrow"></span></a>
						<ul class="nav nav-second-level">
							<?php
							if ($pagedesc == "Upload Insentif Kurir") {
								echo '<li><a href="insentif_kurir_upload.php" class="active">Upload Insentif Kurir</a></li>';
							} else {
								echo '<li><a href="insentif_kurir_upload.php">Upload Insentif Kurir</a></li>';
							}
							if ($pagedesc == "Daftar Insentif Kurir") {
								echo '<li><a href="insentif_kurir_list.php" class="active">Daftar Insentif Kurir</a></li>';
							} else {
								echo '<li><a href="insentif_kurir_list.php">Daftar Insentif Kurir</a></li>';
							}
							// if ($pagedesc == "Pengaturan Insentif Kurir") {
							//     echo '<li><a href="pengaturan_insentif_kurir.php" class="active">Pengaturan Insentif Kurir</a></li>';
							// } else {
							//     echo '<li><a href="pengaturan_insentif_kurir.php">Pengaturan Insentif Kurir</a></li>';
							// }
							?>
						</ul><!-- /.nav-second-level -->
						</li>

						<!-- Menu Slip Gaji (Pengajuan + Approval) untuk HR/Admin -->
						<?php
						$badge_slip = ($count_pending_slip > 0) ? ' <span class="badge" style="background-color: #d9534f;">' . $count_pending_slip . '</span>' : '';
						echo (isset($menuparent) && $menuparent == "gaji") ? '<li class="active">' : '<li>';
						?>
							<a href="#"><i class="fa fa-download fa-fw"></i>&nbsp;Slip Gaji<span class="fa arrow"></span></a>
							<ul class="nav nav-second-level">
								<?php
								if (isset($pagedesc) && $pagedesc == "Request Slip Gaji") {
									echo '<li><a href="request_slip_gaji.php" class="active"><i class="fa fa-file-text-o fa-fw"></i> Pengajuan Slip Gaji</a></li>';
								} else {
									echo '<li><a href="request_slip_gaji.php"><i class="fa fa-file-text-o fa-fw"></i> Pengajuan Slip Gaji</a></li>';
								}
								if (isset($pagedesc) && $pagedesc == "Daftar Request Slip Gaji Saya") {
									echo '<li><a href="request_slip_gaji_list_saya.php" class="active"><i class="fa fa-list fa-fw"></i> Daftar Request Saya</a></li>';
								} else {
									echo '<li><a href="request_slip_gaji_list_saya.php"><i class="fa fa-list fa-fw"></i> Daftar Request Saya</a></li>';
								}
								if (isset($pagedesc) && $pagedesc == "Approval Request Slip Gaji") {
									echo '<li><a href="request_slip_gaji_approval_list.php" class="active"><i class="fa fa-check-square-o fa-fw"></i> Approval Slip Gaji' . $badge_slip . '</a></li>';
								} else {
									echo '<li><a href="request_slip_gaji_approval_list.php"><i class="fa fa-check-square-o fa-fw"></i> Approval Slip Gaji' . $badge_slip . '</a></li>';
								}
								if (isset($pagedesc) && $pagedesc == "Daftar Slip Gaji Approved") {
									echo '<li><a href="request_slip_gaji_list.php" class="active"><i class="fa fa-file-excel-o fa-fw"></i> Daftar Slip Gaji (Semua)</a></li>';
								} else {
									echo '<li><a href="request_slip_gaji_list.php"><i class="fa fa-file-excel-o fa-fw"></i> Daftar Slip Gaji (Semua)</a></li>';
								}
								?>
							</ul><!-- /.nav-second-level -->
						</li>

						<!-- Menu Pinjaman -->
						<?php
						if (isset($pagedesc) && $pagedesc == "Pengajuan Pinjaman") {
							echo '<li><a href="pinjaman_saya.php" class="active"><i class="fa fa-money fa-fw"></i>&nbsp;Pinjaman Saya</a></li>';
						} else {
							echo '<li><a href="pinjaman_saya.php"><i class="fa fa-money fa-fw"></i>&nbsp;Pinjaman Saya</a></li>';
						}
						?>

						<!-- Menu Peminjaman Mobil -->
						<?php
						if (isset($pagedesc) && $pagedesc == "Peminjaman Mobil") {
							echo '<li><a href="peminjaman_mobil.php" class="active"><i class="fa fa-car fa-fw"></i>&nbsp;Peminjaman Mobil</a></li>';
						} else {
							echo '<li><a href="peminjaman_mobil.php"><i class="fa fa-car fa-fw"></i>&nbsp;Peminjaman Mobil</a></li>';
						}
						?>


						<?php
						if (isset($menuparent) && $menuparent == "laporan") {
							echo '<li class="active">';
						} else {
							echo '<li>';
						}
						?>
						<!-- open <li> tag generated with php, see line 155-160 -->
						<a href="#"><i class="fa fa-file-text-o fa-fw"></i>&nbsp;Laporan<span class="fa arrow"></span></a>
						<ul class="nav nav-second-level">
							<?php
							if ($pagedesc == "Laporan") {
								echo '<li><a href="laporan.php" class="active">Laporan Cuti</a></li>';
							} else {
								echo '<li><a href="laporan.php">Laporan Cuti</a></li>';
							}
							if ($pagedesc == "Laporan Lemburan") {
								echo '<li><a href="laporan_lembur.php" class="active">Laporan Lemburan</a></li>';
							} else {
								echo '<li><a href="laporan_lembur.php">Laporan Lemburan</a></li>';
							}
							?>
						</ul><!-- /.nav-second-level -->
						</li>
					</ul>
				</div>
				<!-- /.sidebar-collapse -->
			</div>
			<!-- /.navbar-static-side -->
		</nav>