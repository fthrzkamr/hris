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

	$id=$sess_mngid;	
	$sql_g = "SELECT * FROM employee WHERE npp='$id'";
	$ress_g = mysqli_query($conn, $sql_g);
	$res = mysqli_fetch_array($ress_g);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="">
	<meta name="author" content="">

	<title>HRIS PT. Dua Farma Group- <?php echo $pagedesc ?></title>

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
				<!-- <div class="sidebar-nav navbar-collapse">
                   
					<ul >
                        <li class="sidebar-search">
							<h4><b>HRIS</b> <br> <b>PT. Dua Farma Mahakarsa</b></h4>
						</li>
						
                       
						
						
	                </ul>
					
                </div>
		 -->
            <ul class="nav navbar-top-links navbar-right">
				<li class="dropdown dropdown-right">
					
					<a class="dropdown-toggle" data-toggle="dropdown" href="#">
						<!-- <h5 class="text-muted"><i class="fa fa-calendar fa-fw"></i>&nbsp;<?php echo $hari_ini.", ".$tanggal." ".$bulan_ini." ".$tahun ?></h5> -->

						<img src="../foto/<?php echo $res['foto_emp']?>" width="20px"></i>&nbsp;<?php echo ucfirst($sess_mngname); ?>&nbsp;<i class="fa fa-caret-down"></i>
					</a>
					<ul class="dropdown-menu dropdown-user">
						<li><a href="pengaturan.php"><i class="fa fa-gear fa-fw"></i>&nbsp;Pengaturan Akun</a></li>
						<li class="divider"></li>
						<li><a href="ubah_foto.php"><i class="fa fa-photo fa-fw"></i>&nbsp;Ubah Data Karyawan</a></li>
						<li class="divider"></li>
						<li><a href="logout.php"><i class="fa fa-sign-out fa-fw"></i> Keluar</a></li>
					</ul><!-- /.dropdown-user -->
					<!-- <li><a href="index.php"><i class="fa fa-sign-out fa-fw"></i> Beranda</a></li> -->
				</li><!-- /.dropdown -->
			</ul><!-- /.navbar-top-links -->
			<div class="navbar-default sidebar" role="navigation">
                <div class="sidebar-nav navbar-collapse">
                    <ul class="nav" id="side-menu">
					<li class="sidebar-search">
							<h4>HRIS<br> <b>PT. Dua Farma Group </b></h4>
							<h5 class="text-muted"><i class="fa fa-calendar fa-fw"></i>&nbsp;<?php echo $hari_ini.", ".$tanggal." ".$bulan_ini." ".$tahun ?></h5>
						</li>
						<?php
							if($pagedesc == "Beranda") {
								echo '<li><a href="index.php" class="active"><i class="fa fa-home fa-fw"></i>&nbsp;Beranda</a></li>';
							}
							else {
								echo '<li><a href="index.php"><i class="fa fa-home fa-fw"></i>&nbsp;Beranda</a></li>';
							}
							
							// Perjalanan Dinas Menu
							if($pagedesc == "Form Perjalanan Dinas") {
								echo '<li><a href="form_perjalanan_dinas.php" class="active"><i class="fa fa-plane fa-fw"></i>&nbsp;Perjalanan Dinas</a></li>';
							}
							else {
								echo '<li><a href="form_perjalanan_dinas.php"><i class="fa fa-plane fa-fw"></i>&nbsp;Perjalanan Dinas</a></li>';
							}
							
							if(isset($menuparent) && $menuparent == "master") {
								echo '<li class="active">';
							}
							else {
								echo '<li>';
							}
						?>
                        <!-- open <li> tag generated with php, see line 134-139 -->
						
						</li>
						<?php
							if(isset($menuparent) && $menuparent == "approval") {
								echo '<li class="active">';
							}
							else {
								echo '<li>';
							}
						?>
                        <!-- open <li> tag generated with php, see line 155-160 -->
						


							<?php
							if(isset($menuparent) && $menuparent == "approval") {
								echo '<li class="active">';
							}
							else {
								echo '<li>';
							}
						?>
						<a href="#"><i class="fa fa-download fa-fw"></i>&nbsp; Persetujuan Cuti<span class="fa arrow"></span></a>
							<ul class="nav nav-second-level">
								<?php
									if($pagedesc == "Waiting Approval") {
										echo '<li><a href="app_wait.php" class="active">Menunggu Approval</a></li>';
									}
									else {
										echo '<li><a href="app_wait.php">Menunggu Approval</a></li>';
									}
									if($pagedesc == "Approved") {
										echo '<li><a href="app.php" class="active">Approved</a></li>';
									}
									else {
										echo '<li><a href="app.php">Approved</a></li>';
									}
									if($pagedesc == "Semua Data") {
										echo '<li><a href="app_all.php" class="active">Semua Data</a></li>';
									}
									else {
										echo '<li><a href="app_all.php">Semua Data</a></li>';
									}
								?>
							</ul><!-- /.nav-second-level -->


							<?php
							if(isset($menuparent) && $menuparent == "approval") {
								echo '<li class="active">';
							}
							else {
								echo '<li>';
							}
						?>

						<a href="#"><i class="fa fa-download fa-fw"></i>&nbsp; Persetujuan Lembur<span class="fa arrow"></span></a>
							<ul class="nav nav-second-level">
								<?php
									if($pagedesc == "Waiting Approval") {
										echo '<li><a href="lembur_wait.php" class="active">Menunggu Approval</a></li>';
									}
									else {
										echo '<li><a href="lembur_wait.php">Data Lemburan</a></li>';
									}
									if($pagedesc == "Approved") {
										echo '';
									}
									else {
										echo '<li><a href="app.php"></a></li>';
									}
									if($pagedesc == "Semua Data") {
										echo '';
									}
									else {
										echo '';
									}
								?>
							</ul><!-- /.nav-second-level -->

							<?php
							if(isset($menuparent) && $menuparent == "approval") {
								echo '<li class="active">';
							}
							else {
								echo '<li>';
							}
						?>

						<a href="#"><i class="fa fa-download fa-fw"></i>&nbsp;Lembur Bersamaan<span class="fa arrow"></span></a>
							<ul class="nav nav-second-level">
								<?php
									if($pagedesc == "Waiting Approval") {
										echo '<li><a href="lembur_wait_bersamaan.php" class="active">Menunggu Approval</a></li>';
									}
									else {
										echo '<li><a href="lembur_wait_bersamaan.php">Data Lemburan</a></li>';
									}
									if($pagedesc == "Approved") {
										echo '';
									}
									else {
										echo '<li><a href="app.php"></a></li>';
									}
									if($pagedesc == "Semua Data") {
										echo '';
									}
									else {
										echo '';
									}
								?>
							</ul><!-- /.nav-second-level -->


							<?php
								if(isset($menuparent) && $menuparent == "approval") {
									echo '<li class="active">';
								}
								else {
									echo '<li>';
								}
							?>

						<a href="#"><i class="fa fa-download fa-fw"></i>&nbsp;Persetujuan Sistem<span class="fa arrow"></span></a>	
							<ul class="nav nav-second-level">
								<?php
									if($pagedesc == "Waiting Approval") {
										echo '<li><a href="pengajuan_wait.php" class="active">Menunggu Approval</a></li>';
									}
									else {
										echo '<li><a href="pengajuan_wait.php">Pengajuan Sistem</a></li>';
									}
									if($pagedesc == "Approved") {
										echo '';
									}
									else {
										echo '<li><a href="app.php"></a></li>';
									}
									if($pagedesc == "Semua Data") {
										echo '';
									}
									else {
										echo '';
									}
								?>
							</ul><!-- /.nav-second-level -->


							<?php
								if(isset($menuparent) && $menuparent == "approval") {
									echo '<li class="active">';
								}
								else {
									echo '<li>';
								}
							?>
                        <!-- open <li> tag generated with php, see line 155-160 -->
							
		
                        <!-- open <li> tag generated with php, see line 155-160 -->
							
						</li>
						<?php if ($sess_jabatan == 'Manager' || $sess_jabatan == 'Leader'): ?>
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
						<?php endif; ?>
	                </ul>
                </div>
                <!-- /.sidebar-collapse -->
            </div>

            
            <!-- /.navbar-static-side -->
        </nav>