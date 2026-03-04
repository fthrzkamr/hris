<?php
// Setting tanggal
$haries = [
    "Sunday" => "Minggu",
    "Monday" => "Senin",
    "Tuesday" => "Selasa",
    "Wednesday" => "Rabu",
    "Thursday" => "Kamis",
    "Friday" => "Jum'at",
    "Saturday" => "Sabtu"
];
$bulans = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

// Tanggal hari ini
$hari_ini = $haries[date("l")];
$bulan_ini = $bulans[date("n")];
$tanggal = date("d");
$tahun = date("Y");

// Notifikasi Training Pending
$count_pending_training = 0;
$sql_notif = "SELECT COUNT(*) as total FROM pengajuan_training WHERE status IS NULL OR status = 'Pending'";
$res_notif = mysqli_query($conn, $sql_notif);
if($res_notif){
    $row_notif = mysqli_fetch_array($res_notif);
    $count_pending_training = $row_notif['total'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HRIS PT. Dua Farma Group - <?php echo $pagedesc; ?></title>

    <!-- Stylesheets -->
    <link href="libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="libs/metisMenu/dist/metisMenu.min.css" rel="stylesheet">
    <link href="libs/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet">
    <link href="libs/datatables-responsive/css/dataTables.responsive.css" rel="stylesheet">
    <link href="dist/css/sb-admin-2.css" rel="stylesheet">
    <link href="dist/css/offline-font.css" rel="stylesheet">
    <link href="dist/css/custom.css" rel="stylesheet">
    <link href="libs/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <!-- jQuery -->
    <script src="libs/jquery/dist/jquery.min.js"></script>
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
            </div>

            <ul class="nav navbar-top-links navbar-right">
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="fa fa-user fa-fw"></i> <?php echo ucfirst($sess_mngname); ?> <i
                            class="fa fa-caret-down"></i>
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
                            <h5 class="text-muted">
                                <i class="fa fa-calendar fa-fw"></i>
                                <?php echo "$hari_ini, $tanggal $bulan_ini $tahun"; ?>
                            </h5>
                        </li>
                        <li><a href="index.php" class="<?php echo ($pagedesc == 'Beranda') ? 'active' : ''; ?>">
                                <i class="fa fa-home fa-fw"></i> Beranda</a>
                        </li>

                        <li>
                            <a href="#"><i class="fa fa-group fa-fw"></i> Informasi Karyawan<span
                                    class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="tunjangan.php"
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

                        <!-- Menu Training -->
                        <?php
                        if (isset($menuparent) && $menuparent == "training") {
                            echo '<li class="active">';
                        } else {
                            echo '<li>';
                        }
                        ?>
                            <a href="#"><i class="fa fa-download fa-fw"></i> Pengajuan Training
                            <?php if($count_pending_training > 0){ ?>
                                <span class="badge" style="background-color: #d9534f; margin-left: 5px;"><?php echo $count_pending_training; ?></span>
                            <?php } ?>
                            <span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="training_wait.php">Menunggu Approval
                                <?php if($count_pending_training > 0){ ?>
                                    <span class="badge" style="background-color: #d9534f;"><?php echo $count_pending_training; ?></span>
                                <?php } ?>
                                </a></li>
                                <li><a href="training_list.php">Semua Pengajuan</a></li>
                            </ul>
                        </li>

                        <!-- Permintaan Karyawan Menu -->
                        <li>
                            <a href="permintaan_karyawan_list.php"><i class="fa fa-check-square-o fa-fw"></i>Approval Permintaan Karyawan</a>
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
                            <a href="pengajuan_sistem.php"><i class="fa fa-download fa-fw"></i> Informasi Gaji</a>
                        </li>

                        <!-- <?php
                        // Link to Pengajuan Karyawan
                        if (
                            isset($pagedesc) && $pagedesc == "Pengajuan Karyawan"
                        ) {
                            echo '<li><a href="permintaan_karyawan_list.php" class="active"><i class="fa fa-download fa-fw"></i>&nbsp;Pengajuan Karyawan</a></li>';
                        } else {
                            echo '<li><a href="permintaan_karyawan_list.php"><i class="fa fa-download fa-fw"></i>&nbsp;Pengajuan Karyawan</a></li>';
                        }
                        ?> -->

                        <!-- <?php if ($sess_jabatan == 'Manager' || $sess_jabatan == 'Leader'): ?>
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
                        <?php endif; ?> -->

                        <!-- Perjalanan Dinas Menu -->
                        <?php
                        if (isset($pagedesc) && $pagedesc == "Daftar Perjalanan Dinas") {
                            echo '<li><a href="perjalanan_dinas_list.php" class="active"><i class="fa fa-download fa-fw"></i>&nbsp;Perjalanan Dinas</a></li>';
                        } else {
                            echo '<li><a href="perjalanan_dinas_list.php"><i class="fa fa-download fa-fw"></i>&nbsp;Perjalanan Dinas</a></li>';
                        }
                        ?>

                        <!-- Insentif menu -->
                        <?php
                        if (isset($menuparent) && $menuparent == "insentif") {
                            echo '<li class="active">';
                        } else {
                            echo '<li>';
                        }
                        ?>
                        <a href="#"><i class="fa fa-download fa-fw"></i>&nbsp;Insentif<span class="fa arrow"></span></a>
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

                    </ul>
                </div>
            </div>
        </nav>
    </div>
</body>

</html>