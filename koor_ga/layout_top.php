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




// Ambil id koordinator dengan logika fallback yang sama seperti di app_wait.php
$id_koor_notif = '-1';
if (isset($conn)) {
    $k_query = mysqli_query($conn, "SELECT id_koordinator FROM koordinator WHERE npp='$sess_mngid' OR nama_koordinator = (SELECT nama_emp FROM employee WHERE npp='$sess_mngid')");
    if ($k_query && $k_row = mysqli_fetch_assoc($k_query)) {
        $id_koor_notif = $k_row['id_koordinator'];
    }
}

// Notifikasi Approval Cuti (samakan kondisi dengan app_wait.php)
$count_pending_cuti = 0;
if (isset($conn) && $id_koor_notif != '-1') {
    $res_cuti = mysqli_query($conn, "SELECT COUNT(*) as total FROM cuti JOIN employee ON cuti.npp=employee.npp WHERE cuti.stt_cuti LIKE '%Menunggu Approval%' AND employee.nama_koordinator='$id_koor_notif'");
    if ($res_cuti) $count_pending_cuti = (int) mysqli_fetch_assoc($res_cuti)['total'];
}

// Notifikasi Approval Lembur (samakan kondisi dengan lembur_wait.php)
$count_pending_lembur = 0;
if (isset($conn) && $id_koor_notif != '-1') {
    $res_lembur = mysqli_query($conn, "SELECT COUNT(*) as total FROM lembur JOIN employee ON lembur.npp=employee.npp WHERE lembur.status LIKE '%Menunggu%' AND employee.nama_koordinator='$id_koor_notif'");
    if ($res_lembur) $count_pending_lembur = (int) mysqli_fetch_assoc($res_lembur)['total'];
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
    <link href="libs/font-awesome/css/font-awesome.min.css" rel="stylesheet">
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
                        <!-- Training Menu -->
                        <?php
                        if (isset($menuparent) && $menuparent == "training") {
                            echo '<li class="active">';
                        } else {
                            echo '<li>';
                        }
                        ?>
                            <a href="#"><i class="fa fa-graduation-cap fa-fw"></i> Pengajuan Training<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li>
                                    <a href="form_pengajuan_training.php">
                                        <i class="fa fa-plus fa-fw"></i> Buat Pengajuan Baru
                                    </a>
                                </li>
                                <li>
                                    <a href="training_status.php">
                                        <i class="fa fa-list fa-fw"></i> Status Pengajuan Saya
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        <li class="<?php echo (isset($menuparent) && $menuparent == 'approval') ? 'active' : ''; ?>">
                            <a href="#"><i class="fa fa-calendar fa-fw"></i> Persetujuan Cuti<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="app_wait.php" class="<?php echo ($pagedesc == 'Waiting Approval') ? 'active' : ''; ?>">Menunggu Approval</a></li>
                                <li><a href="app.php" class="<?php echo ($pagedesc == 'Approved') ? 'active' : ''; ?>">Approved</a></li>
                                <li><a href="app_all.php" class="<?php echo ($pagedesc == 'Semua Data') ? 'active' : ''; ?>">Semua Data</a></li>
                            </ul>
                        </li>
                        <li class="<?php echo (isset($menuparent) && $menuparent == 'approval') ? 'active' : ''; ?>">
                            <a href="#"><i class="fa fa-clock-o fa-fw"></i> Persetujuan Lembur<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="lembur_wait.php" class="<?php echo ($pagedesc == 'Waiting Approval') ? 'active' : ''; ?>">Menunggu Approval</a></li>
                                <li><a href="lembur_app.php" class="<?php echo ($pagedesc == 'Approved') ? 'active' : ''; ?>">Approved</a></li>
                                <li><a href="lembur_all.php" class="<?php echo ($pagedesc == 'Semua Data') ? 'active' : ''; ?>">Semua Data</a></li>
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
                        
						<?php if (isset($row_sess['status_karyawan']) && $row_sess['status_karyawan'] != 'Magang'): ?>
<li>
                            <a href="pinjaman.php"><i class="fa fa-group fa-fw"></i> Pinjaman</a>
                        </li>
<?php endif; ?>
                        <?php
                        if (isset($pagedesc) && $pagedesc == "Peminjaman Mobil") {
                            echo '<li><a href="peminjaman_mobil.php" class="active"><i class="fa fa-car fa-fw"></i> Peminjaman Mobil</a>';
                        } else {
                            echo '<li><a href="peminjaman_mobil.php"><i class="fa fa-car fa-fw"></i> Peminjaman Mobil</a>';
                        }
                        ?>
                        </li>
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



<!-- Smart Badges via JS to avoid PHP Syntax Errors -->
<script>
$(document).ready(function() {
    function addBadge(href, count) {
        if(count > 0) {
            $('a[href="' + href + '"]').append(' <span class="badge" style="background-color: #d9534f; float: right; margin-top: 2px;">' + count + '</span>');
        }
    }
    
    function addParentBadge(text, count) {
        if(count > 0) {
            $('ul#side-menu > li > a').filter(function() {
                return $(this).text().indexOf(text) > -1;
            }).find('span.fa.arrow').before(' <span class="badge" style="background-color: #d9534f; margin-left: 5px;">' + count + '</span> ');
        }
    }

    addBadge('app_wait.php', <?php echo $count_pending_cuti; ?>);
    addBadge('lembur_wait.php', <?php echo $count_pending_lembur; ?>);
    
    addParentBadge('Persetujuan Cuti', <?php echo $count_pending_cuti; ?>);
    addParentBadge('Persetujuan Lembur', <?php echo $count_pending_lembur; ?>);
});
</script>
</body>
</html>

