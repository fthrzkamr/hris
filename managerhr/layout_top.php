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
if ($res_notif) {
    $row_notif = mysqli_fetch_array($res_notif);
    $count_pending_training = $row_notif['total'];
}

// Notifikasi Calon Karyawan Pending
$count_pending_calon = 0;
$sql_calon = "SELECT COUNT(*) as total FROM employee WHERE status_karyawan = 'Calon Karyawan' AND aktif = 'Menunggu Review'";
$res_calon = mysqli_query($conn, $sql_calon);
if ($res_calon) {
    $row_calon = mysqli_fetch_array($res_calon);
    $count_pending_calon = $row_calon['total'];
}

// Notifikasi Perjalanan Dinas Pending Approval
$count_pending_perjalanan = 0;
$sql_perjalanan = "SELECT COUNT(*) as total FROM perjalanan_pengajuan p2
    INNER JOIN (
        SELECT id_perjalanan, MAX(id) AS mid FROM perjalanan_pengajuan GROUP BY id_perjalanan
    ) m ON p2.id_perjalanan = m.id_perjalanan AND p2.id = m.mid
    WHERE p2.status IN ('DIAJUKAN', 'APPROVED_HR', 'REALISASI_DIAJUKAN', 'REALISASI_VERIFIED_HR')";
$res_perjalanan = mysqli_query($conn, $sql_perjalanan);
if ($res_perjalanan) {
    $row_perjalanan = mysqli_fetch_array($res_perjalanan);
    $count_pending_perjalanan = $row_perjalanan['total'];
}

// Count pending request slip gaji (untuk badge notifikasi pada menu Approval)
$count_pending_slip = 0;
if (isset($conn)) {
    $sql_count_slip = "SELECT COUNT(*) as total FROM request_slip_gaji WHERE status = 'pending'";
    $result_count_slip = mysqli_query($conn, $sql_count_slip);
    if ($result_count_slip) {
        $count_pending_slip = mysqli_fetch_assoc($result_count_slip)['total'];
    }
}

// Notifikasi Slip Gaji Approved (untuk ManagerHR)
$count_approved_slip = 0;
$sql_slip = "SELECT COUNT(*) as total FROM request_slip_gaji WHERE status = 'approved'";
$res_slip = mysqli_query($conn, $sql_slip);
if ($res_slip) {
    $row_slip = mysqli_fetch_array($res_slip);
    $count_approved_slip = $row_slip['total'];
}
// Notifikasi Peminjaman Mobil
$count_pending_mobil = 0;
if (isset($conn)) {
    $res_mobil = mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman_mobil WHERE status = 'Menunggu'");
    if ($res_mobil) {
        $count_pending_mobil = mysqli_fetch_assoc($res_mobil)['total'];
    }
}

// Notifikasi Pinjaman Pending
$count_pending_pinjaman = 0;
if (isset($conn)) {
    $res_pinjaman = mysqli_query($conn, "SELECT COUNT(*) as total FROM pinjaman WHERE status = 'menunggu'");
    if ($res_pinjaman) {
        $count_pending_pinjaman = mysqli_fetch_assoc($res_pinjaman)['total'];
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
    $tahun_notif = (int)date('Y') - 1;
    $res_reimb = mysqli_query($conn, "SELECT COUNT(*) as total FROM rembes WHERE rembes.status LIKE '%Menunggu%' AND YEAR(rembes.tanggal_pemeriksaan) >= $tahun_notif");
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HRIS DF Group - <?php echo $pagedesc; ?></title>

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
                <a class="navbar-brand hidden-xs" href="index.php">
                    <img src="../libs/images/dua.png" alt="brand" width="32" class="float-left image-brand">
                    <div class="float-right">&nbsp;<strong>DF Group </strong></div>
                    <div class="clear-both"></div>
                </a>
            </div><!-- /.navbar-header -->

            <ul class="nav navbar-top-links navbar-right">
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <?php if(!empty($row_sess['foto_emp'])): ?>
                            <img src="../foto/<?php echo htmlspecialchars($row_sess['foto_emp']); ?>" width="20px" style="border-radius:50%; vertical-align:middle;" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                            <i class="fa fa-user-circle" style="font-size:20px; vertical-align:middle; display:none; color:#aaa;"></i>
                        <?php else: ?>
                            <i class="fa fa-user-circle" style="font-size:20px; vertical-align:middle; color:#aaa;"></i>
                        <?php endif; ?>
                        &nbsp;<?php echo ucfirst($sess_mngname); ?>&nbsp;<i class="fa fa-caret-down"></i>
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
                        <li><a href="kalender_cuti.php" class="<?php echo ($pagedesc == 'Kalender Cuti') ? 'active' : ''; ?>">
                                <i class="fa fa-calendar fa-fw"></i> Kalender Cuti</a>
                        </li>
                        <?php
                        if (isset($pagedesc) && ($pagedesc == "Data Karyawan" || $pagedesc == "Struktur Organisasi")) {
                            echo '<li class="active">';
                        } else {
                            echo '<li>';
                        }
                        ?>
                            <a href="#"><i class="fa fa-database fa-fw"></i> Master Data<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="data_karyawan.php" class="<?php echo ($pagedesc == 'Data Karyawan') ? 'active' : ''; ?>"><i class="fa fa-list fa-fw"></i> Data Karyawan</a></li>
                                <li><a href="struktur_organisasi.php" class="<?php echo ($pagedesc == 'Struktur Organisasi') ? 'active' : ''; ?>"><i class="fa fa-sitemap fa-fw"></i> Struktur Organisasi</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="#"><i class="fa fa-info-circle fa-fw"></i> Informasi Karyawan<span
                                    class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="tunjangan.php"
                                        class="<?php echo ($pagedesc == 'Data Karyawan') ? 'active' : ''; ?>">Data
                                        Karyawan</a></li>
                            </ul>
                        </li>

                        <!-- Menu Calon Karyawan -->
                        <li>
                            <a href="calon_karyawan_list.php">
                                <i class="fa fa-user fa-fw"></i> Calon Karyawan
                                <?php if ($count_pending_calon > 0) { ?>
                                    <span class="badge"
                                        style="background-color: #d9534f; margin-left: 5px;"><?php echo $count_pending_calon; ?></span>
                                <?php } ?>
                            </a>
                        </li>
                        <li>
                            <a href="test_calon_list.php" class="<?php echo (isset($pagedesc) && $pagedesc == 'Hasil Test Calon') ? 'active' : ''; ?>">
                                <i class="fa fa-pencil-square-o fa-fw"></i> Hasil Test Calon
                            </a>
                        </li>

                        <li>
                            <a href="#"><i class="fa fa-sliders fa-fw"></i> Pengaturan Limit<span
                                    class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="karyawan.php"
                                        class="<?php echo ($pagedesc == 'Data Karyawan') ? 'active' : ''; ?>">Data
                                        Karyawan</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="#"><i class="fa fa-medkit fa-fw"></i> Persetujuan Reimbursement
                                <?php if (isset($count_pending_reimburse) && $count_pending_reimburse > 0) { ?>
                                    <span class="badge" style="background-color: #d9534f; margin-left: 5px;"><?php echo $count_pending_reimburse; ?></span>
                                <?php } ?>
                                <span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="reimburse_wait.php" class="<?php echo ($pagedesc == 'Waiting Approval') ? 'active' : ''; ?>">Approval Reimbursement
                                    <?php if (isset($count_pending_reimburse) && $count_pending_reimburse > 0) { ?>
                                        <span class="badge" style="background-color: #d9534f; float: right;"><?php echo $count_pending_reimburse; ?></span>
                                    <?php } ?>
                                </a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="#"><i class="fa fa-eye fa-fw"></i> Persetujuan Kacamata
                                <?php if (isset($count_pending_kacamata) && $count_pending_kacamata > 0) { ?>
                                    <span class="badge" style="background-color: #d9534f; margin-left: 5px;"><?php echo $count_pending_kacamata; ?></span>
                                <?php } ?>
                                <span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="kacamata_wait.php" class="<?php echo ($pagedesc == 'Waiting Approval') ? 'active' : ''; ?>">Approval Kacamata
                                    <?php if (isset($count_pending_kacamata) && $count_pending_kacamata > 0) { ?>
                                        <span class="badge" style="background-color: #d9534f; float: right;"><?php echo $count_pending_kacamata; ?></span>
                                    <?php } ?>
                                </a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="#"><i class="fa fa-calendar fa-fw"></i> Persetujuan Cuti
                                <?php if (isset($count_pending_cuti) && $count_pending_cuti > 0) { ?>
                                    <span class="badge" style="background-color: #d9534f; margin-left: 5px;"><?php echo $count_pending_cuti; ?></span>
                                <?php } ?>
                                <span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="app_wait.php" class="<?php echo ($pagedesc == 'Waiting Approval') ? 'active' : ''; ?>">Approval Cuti
                                    <?php if (isset($count_pending_cuti) && $count_pending_cuti > 0) { ?>
                                        <span class="badge" style="background-color: #d9534f; float: right;"><?php echo $count_pending_cuti; ?></span>
                                    <?php } ?>
                                </a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="#"><i class="fa fa-clock-o fa-fw"></i> Persetujuan Lembur
                                <?php if (isset($count_pending_lembur) && $count_pending_lembur > 0) { ?>
                                    <span class="badge" style="background-color: #d9534f; margin-left: 5px;"><?php echo $count_pending_lembur; ?></span>
                                <?php } ?>
                                <span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="lembur_wait.php" class="<?php echo ($pagedesc == 'Waiting Approval') ? 'active' : ''; ?>">Menunggu Approval
                                    <?php if (isset($count_pending_lembur) && $count_pending_lembur > 0) { ?>
                                        <span class="badge" style="background-color: #d9534f; float: right;"><?php echo $count_pending_lembur; ?></span>
                                    <?php } ?>
                                </a></li>
                            </ul>
                        </li>

                        <!-- Menu Permintaan Karyawan -->
                        <?php
                        if (isset($menuparent) && $menuparent == "permintaan_karyawan") {
                            echo '<li class="active">';
                        } else {
                            echo '<li>';
                        }
                        ?>
                        <a href="permintaan_karyawan_list.php"><i class="fa fa-users fa-fw"></i> Approval Permintaan Karyawan
                            <?php if (isset($count_pending_permintaan) && $count_pending_permintaan > 0) { ?>
                                <span class="badge" style="background-color: #d9534f; float: right;"><?php echo $count_pending_permintaan; ?></span>
                            <?php } ?>
                        </a>
                        </li>

                        <!-- Menu Training -->
                        <?php
                        if (isset($menuparent) && $menuparent == "training") {
                            echo '<li class="active">';
                        } else {
                            echo '<li>';
                        }
                        ?>
                        <a href="#"><i class="fa fa-graduation-cap fa-fw"></i> Pengajuan Training
                            <?php if (isset($count_pending_training) && $count_pending_training > 0) { ?>
                                <span class="badge" style="background-color: #d9534f; margin-left: 5px;"><?php echo $count_pending_training; ?></span>
                            <?php } ?>
                            <span class="fa arrow"></span></a>
                        <ul class="nav nav-second-level">
                            <li><a href="training_wait.php"><i class="fa fa-check-square-o fa-fw"></i> Menunggu Approval
                                    <?php if (isset($count_pending_training) && $count_pending_training > 0) { ?>
                                        <span class="badge" style="background-color: #d9534f; float: right;"><?php echo $count_pending_training; ?></span>
                                    <?php } ?>
                                </a></li>
                            <li><a href="training_list.php"><i class="fa fa-list fa-fw"></i> Semua Pengajuan Staf</a></li>
                            <li class="divider"></li>
                            <li><a href="form_pengajuan_training.php"><i class="fa fa-plus fa-fw"></i> Buat Pengajuan Baru</a></li>
                            <li><a href="training_status.php"><i class="fa fa-user fa-fw"></i> Status Pengajuan Saya</a></li>
                        </ul>
                        </li>



                        <?php if (isset($row_sess['status_karyawan']) && $row_sess['status_karyawan'] != 'Magang'): ?>
<li>
                            <a href="#"><i class="fa fa-money fa-fw"></i> Pinjaman
                                <?php if ($count_pending_pinjaman > 0) { ?>
                                    <span class="badge" style="background-color: #d9534f; margin-left: 5px;"><?php echo $count_pending_pinjaman; ?></span>
                                <?php } ?>
                                <span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="pinjaman_saya.php">Pinjaman Saya</a></li>
                                <li><a href="pinjaman.php" class="<?php echo ($pagedesc == 'Data Pinjaman') ? 'active' : ''; ?>">Approval Pinjaman
                                    <?php if ($count_pending_pinjaman > 0) { ?>
                                        <span class="badge" style="background-color: #d9534f; float: right;"><?php echo $count_pending_pinjaman; ?></span>
                                    <?php } ?>
                                </a></li>
                            </ul>
                        </li>
<?php endif; ?>
                        
                        <!-- Menu Peminjaman Mobil -->
                        <?php
                            $badge_mobil_parent = ($count_pending_mobil > 0) ? ' <span class="badge" style="background-color: #d9534f; margin-left: 5px;">' . $count_pending_mobil . '</span>' : '';
                            $badge_mobil_child = ($count_pending_mobil > 0) ? ' <span class="badge" style="background-color: #d9534f; float: right;">' . $count_pending_mobil . '</span>' : '';

                            if (isset($row_sess['status_karyawan']) && $row_sess['status_karyawan'] != 'Magang') {
                            echo '<li ' . (isset($pagedesc) && ($pagedesc == "Peminjaman Mobil" || $pagedesc == "Approval Peminjaman Mobil") ? 'class="active"' : '') . '>';
                            echo '<a href="#"><i class="fa fa-car fa-fw"></i> Peminjaman Mobil' . $badge_mobil_parent . '<span class="fa arrow"></span></a>';
                            echo '<ul class="nav nav-second-level">';
                            echo '<li><a href="peminjaman_mobil.php" class="' . (isset($pagedesc) && $pagedesc == 'Peminjaman Mobil' ? 'active' : '') . '">Pengajuan</a></li>';
                            echo '<li><a href="peminjaman_mobil_app.php" class="' . (isset($pagedesc) && $pagedesc == 'Approval Peminjaman Mobil' ? 'active' : '') . '">Approval' . $badge_mobil_child . '</a></li></ul></li>';
                            }
                        ?>

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
                                <a href="perjalanan_dinas_list.php"
                                    class="<?php echo ($pagedesc == 'Daftar Perjalanan Dinas') ? 'active' : ''; ?>">
                                    <i class="fa fa-check-square-o fa-fw"></i> Persetujuan HR
                                    <?php echo $badge_perjalanan; ?>
                                </a>
                            </li>
                            <li class="divider"></li>
                            <li><a href="form_perjalanan_dinas.php"><i class="fa fa-plus fa-fw"></i> Buat Pengajuan Baru</a></li>
                            <li><a href="perjalanan_dinas_saya.php"><i class="fa fa-user fa-fw"></i> Daftar Pengajuan Saya</a></li>
                        </ul>
                        </li>

                        <!-- Menu Daftar Slip Gaji untuk ManagerHR -->
                        <!-- Menu Approval Request Slip Gaji untuk HR/Admin -->
                        <?php
                        $badge_slip = ($count_pending_slip > 0) ? ' <span class="badge" style="background-color: #d9534f;">' . $count_pending_slip . '</span>' : '';
                        if (isset($pagedesc) && $pagedesc == "Approval Request Slip Gaji") {
                            echo '<li><a href="request_slip_gaji_approval_list.php" class="active"><i class="fa fa-file-text-o fa-fw"></i>&nbsp;Approval Slip Gaji' . $badge_slip . '</a></li>';
                        } else {
                            echo '<li><a href="request_slip_gaji_approval_list.php"><i class="fa fa-file-text-o fa-fw"></i>&nbsp;Approval Slip Gaji' . $badge_slip . '</a></li>';
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
                            ?>
                        </ul><!-- /.nav-second-level -->
                        <li>
                            <a href="#"><i class="fa fa-file-text fa-fw"></i> Laporan<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="laporan.php"
                                        class="<?php echo ($pagedesc == 'Laporan Data Cuti') ? 'active' : ''; ?>">Laporan Cuti</a></li>
                                <li><a href="reimburse_report_stat.php"
                                        class="<?php echo ($pagedesc == 'Laporan Reimbursement') ? 'active' : ''; ?>">Laporan Reimbursement</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
