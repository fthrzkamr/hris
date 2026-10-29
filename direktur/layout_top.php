<?php
// Setting tanggal
$haries = ["Sunday" => "Minggu", "Monday" => "Senin", "Tuesday" => "Selasa", "Wednesday" => "Rabu", "Thursday" => "Kamis", "Friday" => "Jum'at", "Saturday" => "Sabtu"];
$bulans = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

$hari_ini = $haries[date("l")];
$bulan_ini = $bulans[date("n")];
$tanggal = date("d");
$tahun = date("Y");

$id = $sess_mngid;

// Hitung Notifikasi Approval Cuti & Lembur sesuai Koordinator yang sedang login
$id_koor = '-1';
$count_pending_cuti = 0;
$count_pending_lembur = 0;
$count_pending_mobil = 0;

if (isset($sess_mngid) && isset($conn)) {
    $nama_esc = mysqli_real_escape_string($conn, trim($sess_mngname ?? ''));
    if ($res_k = mysqli_query($conn, "SELECT id_koordinator FROM koordinator WHERE npp='$sess_mngid' OR ((npp='' OR npp IS NULL) AND TRIM(LOWER(nama_koordinator))=LOWER('$nama_esc')) LIMIT 1")) {
        if ($row_k = mysqli_fetch_assoc($res_k)) {
            $id_koor = $row_k['id_koordinator'];
        }
    }

    if ($id_koor != '-1') {
        $res_cuti = mysqli_query($conn, "SELECT COUNT(*) as total FROM cuti JOIN employee ON cuti.npp=employee.npp WHERE cuti.stt_cuti='Menunggu Approval' AND employee.nama_koordinator='$id_koor'");
        if ($res_cuti) {
            $count_pending_cuti = (int)mysqli_fetch_assoc($res_cuti)['total'];
        }

        $res_lembur = mysqli_query($conn, "SELECT COUNT(*) as total FROM lembur JOIN employee ON lembur.npp=employee.npp WHERE (lembur.status LIKE '%Menunggu%' OR lembur.status LIKE '%Pending%') AND employee.nama_koordinator='$id_koor'");
        if ($res_lembur) {
            $count_pending_lembur = (int)mysqli_fetch_assoc($res_lembur)['total'];
        }
    }

    $res_mobil = mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman_mobil WHERE status = 'Menunggu'");
    if ($res_mobil) {
        $count_pending_mobil = (int)mysqli_fetch_assoc($res_mobil)['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HRIS DF Group - <?php echo $pagedesc; ?></title>
    <link href="libs/images/dua.png" rel="icon" type="images/x-icon">
    <link href="libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="libs/metisMenu/dist/metisMenu.min.css" rel="stylesheet">
    <link href="libs/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet">
    <link href="libs/datatables-responsive/css/dataTables.responsive.css" rel="stylesheet">
    <link href="dist/css/sb-admin-2.css" rel="stylesheet">
    <link href="dist/css/offline-font.css" rel="stylesheet">
    <link href="dist/css/custom.css" rel="stylesheet">
    <link href="libs/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <script src="libs/jquery/dist/jquery.min.js"></script>
</head>
<body>
    <div id="wrapper">
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
                        <?php if (!empty($row_sess['foto_emp'])): ?>
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
                            <h4>HRIS<br> <b>DF Group</b></h4>
                            <h5 class="text-muted"><i class="fa fa-calendar fa-fw"></i> <?php echo "$hari_ini, $tanggal $bulan_ini $tahun"; ?></h5>
                        </li>
                        <li <?php echo (isset($pagedesc) && $pagedesc == "Beranda") ? 'class="active"' : ''; ?>>
                            <a href="index.php"><i class="fa fa-home fa-fw"></i> Beranda</a>
                        </li>

                        <!-- Perjalanan Dinas Menu -->
                        <li <?php echo (isset($menuparent) && $menuparent == "perjalanan_dinas") ? 'class="active"' : ''; ?>>
                            <a href="#"><i class="fa fa-plane fa-fw"></i> Perjalanan Dinas<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li>
                                    <a href="form_perjalanan_dinas.php"><i class="fa fa-plus fa-fw"></i> Buat Pengajuan Baru</a>
                                </li>
                                <li>
                                    <a href="perjalanan_dinas_list.php"><i class="fa fa-list fa-fw"></i> Daftar Pengajuan Saya</a>
                                </li>
                            </ul>
                        </li>

                        <!-- Pengajuan Training Menu -->
                        <li <?php echo (isset($menuparent) && $menuparent == "training") ? 'class="active"' : ''; ?>>
                            <a href="#"><i class="fa fa-graduation-cap fa-fw"></i> Pengajuan Training<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li>
                                    <a href="form_pengajuan_training.php"><i class="fa fa-plus fa-fw"></i> Buat Pengajuan Baru</a>
                                </li>
                                <li>
                                    <a href="training_status.php"><i class="fa fa-list fa-fw"></i> Status Pengajuan Saya</a>
                                </li>
                            </ul>
                        </li>

                        <!-- Persetujuan Cuti Menu -->
                        <li <?php echo (isset($menuparent) && $menuparent == "approval") ? 'class="active"' : ''; ?>>
                            <a href="#"><i class="fa fa-calendar fa-fw"></i> Persetujuan Cuti<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="app_wait.php">Menunggu Approval</a></li>
                                <li><a href="app.php">Approved</a></li>
                                <li><a href="app_all.php">Semua Data</a></li>
                            </ul>
                        </li>

                        <!-- Persetujuan Lembur Menu -->
                        <li <?php echo (isset($menuparent) && $menuparent == "approval_lembur") ? 'class="active"' : ''; ?>>
                            <a href="#"><i class="fa fa-clock-o fa-fw"></i> Persetujuan Lembur<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="lembur_wait.php">Menunggu Approval</a></li>
                            </ul>
                        </li>

                        <!-- Menu Request Slip Gaji -->
                        <li <?php echo (isset($menuparent) && $menuparent == "gaji") ? 'class="active"' : ''; ?>>
                            <a href="#"><i class="fa fa-download fa-fw"></i> Slip Gaji<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li>
                                    <a href="request_slip_gaji.php"><i class="fa fa-file-text-o fa-fw"></i> Request Slip Gaji</a>
                                </li>
                                <li>
                                    <a href="request_slip_gaji_list.php"><i class="fa fa-list fa-fw"></i> Daftar Request Saya</a>
                                </li>
                            </ul>
                        </li>

                        <?php if (isset($row_sess['status_karyawan']) && $row_sess['status_karyawan'] != 'Magang'): ?>
                        <li>
                            <a href="pinjaman_saya.php"><i class="fa fa-money fa-fw"></i> Pinjaman</a>
                        </li>
                        <?php endif; ?>

                        <?php
                        if (isset($pagedesc) && $pagedesc == "Peminjaman Mobil") {
                            echo '<li><a href="peminjaman_mobil.php" class="active"><i class="fa fa-car fa-fw"></i> Peminjaman Mobil</a></li>';
                        } else {
                            echo '<li><a href="peminjaman_mobil.php"><i class="fa fa-car fa-fw"></i> Peminjaman Mobil</a></li>';
                        }
                        ?>

                        <li>
                            <a href="booking_ruang_meeting.php" class="<?php echo (isset($pagedesc) && $pagedesc == 'Booking Ruang Meeting') ? 'active' : ''; ?>"><i class="fa fa-building fa-fw"></i> Booking Ruang Meeting</a>
                        </li>



                        <?php if (isset($sess_jabatan) && ($sess_jabatan == 'Manager' || $sess_jabatan == 'Leader')): ?>
                        <li <?php echo (isset($menuparent) && $menuparent == "permintaan_karyawan") ? 'class="active"' : ''; ?>>
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

<!-- Smart Badges via JS -->
<script>
$(document).ready(function() {
    function addBadge(href, count) {
        if (count > 0) {
            $('a[href="' + href + '"]').append(' <span class="badge" style="background-color: #d9534f; float: right; margin-top: 2px;">' + count + '</span>');
        }
    }

    function addParentBadge(text, count) {
        if (count > 0) {
            $('ul#side-menu > li > a').filter(function() {
                return $(this).text().indexOf(text) > -1;
            }).find('span.fa.arrow').before(' <span class="badge" style="background-color: #d9534f; margin-left: 5px;">' + count + '</span> ');
        }
    }

    addBadge('app_wait.php', <?php echo (int)$count_pending_cuti; ?>);
    addBadge('lembur_wait.php', <?php echo (int)$count_pending_lembur; ?>);
    addBadge('peminjaman_mobil_app.php', <?php echo (int)$count_pending_mobil; ?>);

    addParentBadge('Persetujuan Cuti', <?php echo (int)$count_pending_cuti; ?>);
    addParentBadge('Persetujuan Lembur', <?php echo (int)$count_pending_lembur; ?>);
    if ($('a[href="peminjaman_mobil_app.php"]').length > 0) {
        addParentBadge('Peminjaman Mobil', <?php echo (int)$count_pending_mobil; ?>);
    }
});
</script>
