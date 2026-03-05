<?php
// Setting tanggal
$haries = [
    "Sunday" => "Minggu", "Monday" => "Senin", "Tuesday" => "Selasa",
    "Wednesday" => "Rabu", "Thursday" => "Kamis", "Friday" => "Jum'at", "Saturday" => "Sabtu"
];
$bulans = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

// Tanggal hari ini
$hari_ini = $haries[date("l")];
$bulan_ini = $bulans[date("n")];
$tanggal = date("d");
$bulan = date("m");
$tahun = date("Y");
$id = $sess_mngid;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HRIS PT. Dua Farma Group - <?php echo $pagedesc; ?></title>
    <link href="libs/images/dua.png" rel="icon" type="images/x-icon">
    <link href="libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="libs/metisMenu/dist/metisMenu.min.css" rel="stylesheet">
    <link href="libs/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet">
    <link href="libs/datatables-responsive/css/dataTables.responsive.css" rel="stylesheet">
    <link href="dist/css/sb-admin-2.css" rel="stylesheet">
    <link href="dist/css/offline-font.css" rel="stylesheet">
    <link href="dist/css/custom.css" rel="stylesheet">
    <link href="libs/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <script src="libs/jquery/dist/jquery.min.js"></script>
</head>
<body>
    <div id="wrapper">
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
            <ul class="nav navbar-top-links navbar-right">
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <img src="../foto/<?php echo $res['foto_emp']; ?>" width="20px">&nbsp;
                        <?php echo ucfirst($sess_mngname); ?>&nbsp;<i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-user">
                        <li><a href="pengaturan.php"><i class="fa fa-gear fa-fw"></i> Pengaturan Akun</a></li>
                        <li class="divider"></li>
                        <li><a href="ubah_foto.php"><i class="fa fa-photo fa-fw"></i> Ubah Data Karyawan</a></li>
                        <li class="divider"></li>
                        <li><a href="logout.php"><i class="fa fa-sign-out fa-fw"></i> Keluar</a></li>
                    </ul>
                </li>
            </ul>
            <div class="navbar-default sidebar" role="navigation">
                <div class="sidebar-nav navbar-collapse">
                    <ul class="nav" id="side-menu">
                        <li class="sidebar-search">
                            <h4>HRIS<br><b>PT. Dua Farma Group</b></h4>
                            <h5 class="text-muted"><i class="fa fa-calendar fa-fw"></i> <?php echo "$hari_ini, $tanggal $bulan_ini $tahun"; ?></h5>
                        </li>
                        <li><a href="index.php" class="<?php echo ($pagedesc == 'Beranda') ? 'active' : ''; ?>">
                            <i class="fa fa-home fa-fw"></i> Beranda</a>
                        </li>
                        
                        <!-- Perjalanan Dinas Menu -->
                        <?php
                        if (isset($menuparent) && $menuparent == "perjalanan_dinas") {
                            echo '<li class="active">';
                        } else {
                            echo '<li>';
                        }
                        ?>
                            <a href="#"><i class="fa fa-plane fa-fw"></i> Perjalanan Dinas<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li>
                                    <a href="form_perjalanan_dinas.php">
                                        <i class="fa fa-plus fa-fw"></i> Buat Pengajuan Baru
                                    </a>
                                </li>
                                <li>
                                    <a href="perjalanan_dinas_list.php">
                                        <i class="fa fa-list fa-fw"></i> Daftar Pengajuan Saya
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        <li class="<?php echo (isset($menuparent) && $menuparent == 'approval') ? 'active' : ''; ?>">
                            <a href="#"><i class="fa fa-download fa-fw"></i> Persetujuan Cuti <span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="app_wait.php" class="<?php echo ($pagedesc == 'Waiting Approval') ? 'active' : ''; ?>">Menunggu Approval</a></li>
                                <li><a href="app.php" class="<?php echo ($pagedesc == 'Approved') ? 'active' : ''; ?>">Approved</a></li>
                                <li><a href="app_all.php" class="<?php echo ($pagedesc == 'Semua Data') ? 'active' : ''; ?>">Semua Data</a></li>
                            </ul>
                        </li>
                        <li class="<?php echo (isset($menuparent) && $menuparent == 'approval') ? 'active' : ''; ?>">
                            <a href="#"><i class="fa fa-download fa-fw"></i> Persetujuan Lembur <span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="lembur_wait.php" class="<?php echo ($pagedesc == 'Waiting Approval') ? 'active' : ''; ?>">Menunggu Approval</a></li>
                            </ul>
                        </li>
                        <li class="<?php echo (isset($menuparent) && $menuparent == 'approval') ? 'active' : ''; ?>">
                            <a href="#"><i class="fa fa-download fa-fw"></i> Lembur Bersamaan <span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="lembur_wait_bersamaan.php" class="<?php echo ($pagedesc == 'Waiting Approval') ? 'active' : ''; ?>">Menunggu Approval</a></li>
                            </ul>
                        </li>                        
                        <!-- Menu Request Slip Gaji -->
                        <?php
                        if (isset($menuparent) && $menuparent == "gaji") {
                            echo '<li class="active">';
                        } else {
                            echo '<li>';
                        }
                        ?>
                            <a href="#"><i class="fa fa-download fa-fw"></i> Slip Gaji<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li>
                                    <a href="request_slip_gaji.php">
                                        <i class="fa fa-file-text-o fa-fw"></i> Request Slip Gaji
                                    </a>
                                </li>
                                <li>
                                    <a href="request_slip_gaji_list.php">
                                        <i class="fa fa-list fa-fw"></i> Daftar Request Saya
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
						<li>
                            <a href="gaji.php"><i class="fa fa-group fa-fw"></i> Informasi Gaji</a>
                        </li>
                        <?php if($sess_jabatan == 'Manager' || $sess_jabatan == 'Leader'): ?>
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
            </div>
        </nav>
    </div>
</body>
</html>
