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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>HRIS Dua farma Group- <?php echo $pagedesc ?></title>

    <!-- <link href="libs/images/dua.png" rel="icon" type="images/x-icon"> -->

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

    <script src="libs/jquery/dist/jquery.min.js"></script>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/fullcalendar/dist/fullcalendar.min.css">
    <script src="assets/fullcalendar/jquery.min.js"></script>
    <script src="assets/fullcalendar/moment.js"></script>
    <script src="assets/fullcalendar/dist/fullcalendar.min.js"></script>
    <script src="assets/script.js"></script>



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
            </div><!-- /.navbar-header -->
            <ul class="nav navbar-top-links navbar-right">
                <li class="dropdown dropdown-right">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="fa fa-user fa-fw"></i>&nbsp;<?php echo ucfirst($sess_mngname); ?>&nbsp;<i
                            class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-user">
                        <li><a href="pengaturan.php"><i class="fa fa-gear fa-fw"></i>&nbsp;Pengaturan Akun</a></li>
                        <li class="divider"></li>
                        <li><a href="ubah_foto.php"><i class="fa fa-photo fa-fw"></i>&nbsp;Ubah Data Karyawan</a></li>
                        <li class="divider"></li>
                        <li><a href="logout.php"><i class="fa fa-sign-out fa-fw"></i> Keluar</a></li>
                    </ul><!-- /.dropdown-user -->
                </li><!-- /.dropdown -->
            </ul><!-- /.navbar-top-links -->

            <div class="navbar-default sidebar" role="navigation">
                <div class="sidebar-nav navbar-collapse">
                    <ul class="nav" id="side-menu">
                        <li class="sidebar-search">
                            <h4>HRIS<br> <b>Dua Farma Group </b></h4>
                            <h5 class="text-muted"><i
                                    class="fa fa-calendar fa-fw"></i>&nbsp;<?php echo $hari_ini . ", " . $tanggal . " " . $bulan_ini . " " . $tahun ?>
                            </h5>
                        </li>
                        <li><a href="index.php" class="<?php echo ($pagedesc == 'Beranda') ? 'active' : ''; ?>">
                                <i class="fa fa-home fa-fw"></i> Beranda</a>
                        </li>
                        <li>
                            <a href="#"><i class="fa fa-group fa-fw"></i> Informasi Karyawan<span
                                    class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="karyawann.php"
                                        class="<?php echo ($pagedesc == 'Data Karyawan') ? 'active' : ''; ?>">Data
                                        Karyawan</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="#"><i class="fa fa-group fa-fw"></i> Pengaturan Limit<span
                                    class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="karyawan.php"
                                        class="<?php echo ($pagedesc == 'Data Karyawan') ? 'active' : ''; ?>">Data
                                        Karyawan</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="#"><i class="fa fa-download fa-fw"></i> Persetujuan Reimbursement<span
                                    class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="reimburse_wait.php"
                                        class="<?php echo ($pagedesc == 'Waiting Approval') ? 'active' : ''; ?>">Approval
                                        Reimbursement</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="#"><i class="fa fa-download fa-fw"></i> Persetujuan Kacamata<span
                                    class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="kacamata_wait.php"
                                        class="<?php echo ($pagedesc == 'Waiting Approval') ? 'active' : ''; ?>">Approval
                                        Kacamata</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="#"><i class="fa fa-download fa-fw"></i> Persetujuan Cuti<span
                                    class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="app_wait.php"
                                        class="<?php echo ($pagedesc == 'Waiting Approval') ? 'active' : ''; ?>">Approval
                                        Cuti</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="#"><i class="fa fa-download fa-fw"></i> Persetujuan Lembur<span
                                    class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="lembur_wait.php"
                                        class="<?php echo ($pagedesc == 'Waiting Approval') ? 'active' : ''; ?>">Menunggu
                                        Approval</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="#"><i class="fa fa-download fa-fw"></i> Pinjaman<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="pinjaman.php"
                                        class="<?php echo ($pagedesc == 'Waiting Approval') ? 'active' : ''; ?>">Informasi
                                        Pinjaman</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="pengajuan_sistem.php"><i class="fa fa-download fa-fw"></i> Pengajuan Sistem</a>
                        </li>

                        <!-- Insentif menu -->
                        <?php
                        if (isset($menuparent) && $menuparent == "insentif") {
                            echo '<li class="active">';
                        } else {
                            echo '<li>';
                        }
                        ?>
                        <a href="#"><i class="fa fa-money fa-fw"></i>&nbsp;Insentif<span class="fa arrow"></span></a>
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
                            if ($pagedesc == "Upload Absensi Karyawan") {
                                echo '<li><a href="insentif_karyawan_upload.php" class="active">Upload Absensi Karyawan</a></li>';
                            } else {
                                echo '<li><a href="insentif_karyawan_upload.php">Upload Absensi Karyawan</a></li>';
                            }
                            if ($pagedesc == "Daftar Absensi Karyawan") {
                                echo '<li><a href="insentif_karyawan_list.php" class="active">Daftar Absensi Karyawan</a></li>';
                            } else {
                                echo '<li><a href="insentif_karyawan_list.php">Daftar Absensi Karyawan</a></li>';
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