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
$tahun = date("Y");

// Hitung notifikasi training yang sudah diproses (Approved/Rejected)
$sql_notif_training = "SELECT COUNT(*) as total FROM pengajuan_training 
                       WHERE npp = '$sess_mngid' 
                       AND status IN ('Approved', 'Rejected')";
$res_notif_training = mysqli_query($conn, $sql_notif_training);
$row_notif_training = mysqli_fetch_array($res_notif_training);
$count_training_notif = $row_notif_training['total'];
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
                        <i class="fa fa-user fa-fw"></i> <?php echo ucfirst($sess_mngname); ?> <i class="fa fa-caret-down"></i>
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
                                <i class="fa fa-calendar fa-fw"></i> <?php echo "$hari_ini, $tanggal $bulan_ini $tahun"; ?>
                            </h5>
                        </li>
                        <li><a href="index.php" class="<?php echo ($pagedesc == 'Beranda') ? 'active' : ''; ?>">
                            <i class="fa fa-home fa-fw"></i> Beranda</a>
                        </li>

                        <!-- Menu Training -->
                        <?php
                        if (isset($menuparent) && $menuparent == "training") {
                            echo '<li class="active">';
                        } else {
                            echo '<li>';
                        }
                        $badge_training = ($count_training_notif > 0) ? ' <span class="badge" style="background-color: #f0ad4e;">' . $count_training_notif . '</span>' : '';
                        ?>
                            <a href="#"><i class="fa fa-download fa-fw"></i> Training<?php echo $badge_training; ?><span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li>
                                    <a href="form_pengajuan_training.php">
                                        <i class="fa fa-plus fa-fw"></i> Buat Pengajuan
                                    </a>
                                </li>
                                <li>
                                    <a href="training_status.php">
                                        <i class="fa fa-list fa-fw"></i> Status Pengajuan<?php echo $badge_training; ?>
                                    </a>
                                </li>
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

                        <!-- Insentif menu -->
                        <?php
                        if (isset($sess_bagian) && $sess_bagian == 18) {
                            if (isset($menuparent) && $menuparent == "insentif") {
                                echo '<li class="active">';
                            } else {
                                echo '<li>';
                            }
                            ?>
                            <a href="insentif_kurir_list.php"><i class="fa fa-download fa-fw"></i>&nbsp;Insentif Kurir</a>
                            </li>
                            <?php
                        }
                        ?>

                        <!-- Form Permintaan Karyawan (Only for Manager and Leader) -->
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
            </div>
        </nav>
    </div>
</body>
</html>
