<?php
$pagedesc = 'Persetujuan Perjalanan Dinas';
$menuparent = 'perjalanan_dinas';
include("sess_check.php");
include("layout_top.php");

// DB connection
if (is_file(__DIR__ . '/dist/config/koneksi.php')) {
    include_once __DIR__ . '/dist/config/koneksi.php';
} else {
    include_once __DIR__ . '/../dist/config/koneksi.php';
}

// Get NPP from session
$npp_user = $sess_mngid ?? ($sess_admid ?? ($sess_kryid ?? ($sess_spvid ?? ($sess_dirid ?? ($_SESSION['admin'] ?? ($_SESSION['managerhr'] ?? ($_SESSION['npp'] ?? '')))))));

if (empty($npp_user)) {
    echo '<div id="page-wrapper"><div class="alert alert-danger" style="margin-top:20px;">Session tidak valid. Silakan login kembali.</div></div>';
    include("layout_bottom.php");
    exit;
}

$message = '';
$messageType = 'info';

if (isset($_GET['success'])) {
    $message = $_GET['success'];
    $messageType = 'success';
} elseif (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $messageType = 'success';
} elseif (isset($_GET['error'])) {
    $message = $_GET['error'];
    $messageType = 'danger';
}

$tab_active = isset($_GET['tab']) ? $_GET['tab'] : 'pending';

$npp_esc = mysqli_real_escape_string($conn, $npp_user);

// 1. Query Pengajuan Menunggu Persetujuan Manager HR
$sql_pending = "SELECT p.id, p.no_dokumen, p.nama, p.npp, p.departemen, p.tanggal_perjalanan, p.kota_tujuan, 
        p.kota_asal, p.jumlah_hari, p.budget_total, p.tanggal_dokumen, p.created_at,
        (SELECT GROUP_CONCAT(CONCAT(pk.nama, CASE WHEN pk.is_pemohon = 1 THEN ' (Pengaju)' ELSE '' END) ORDER BY pk.is_pemohon DESC SEPARATOR ', ') 
         FROM perjalanan_karyawan pk WHERE pk.perjalanan_id = p.id) AS daftar_peserta,
        pp.status, pp.tanggal_pengajuan, pp.pengaju, pp.catatan
    FROM perjalanan_dinas p
    INNER JOIN (
        SELECT p1.id_perjalanan, p1.status, p1.tanggal_pengajuan, p1.pengaju, p1.catatan
        FROM perjalanan_pengajuan p1
        INNER JOIN (
            SELECT id_perjalanan, MAX(id) AS max_id FROM perjalanan_pengajuan GROUP BY id_perjalanan
        ) p_max ON p1.id_perjalanan = p_max.id_perjalanan AND p1.id = p_max.max_id
    ) pp ON p.id = pp.id_perjalanan
    WHERE pp.status IN ('DIAJUKAN', 'APPROVED_HR', 'REALISASI_DIAJUKAN', 'REALISASI_VERIFIED_HR')
    ORDER BY pp.tanggal_pengajuan ASC, p.id DESC";
$res_pending = mysqli_query($conn, $sql_pending);
$count_pending = $res_pending ? mysqli_num_rows($res_pending) : 0;

// 2. Query Semua Pengajuan Perjalanan Dinas Perusahaan (Riwayat)
$sql_all = "SELECT p.id, p.no_dokumen, p.nama, p.npp, p.departemen, p.tanggal_perjalanan, p.kota_tujuan, 
        p.kota_asal, p.jumlah_hari, p.budget_total, p.tanggal_dokumen, p.created_at,
        (SELECT GROUP_CONCAT(CONCAT(pk.nama, CASE WHEN pk.is_pemohon = 1 THEN ' (Pengaju)' ELSE '' END) ORDER BY pk.is_pemohon DESC SEPARATOR ', ') 
         FROM perjalanan_karyawan pk WHERE pk.perjalanan_id = p.id) AS daftar_peserta,
        COALESCE(pp.status, 'BELUM DIAJUKAN') AS status, 
        pp.tanggal_pengajuan, pp.pengaju, pp.catatan
    FROM perjalanan_dinas p
    LEFT JOIN (
        SELECT p1.id_perjalanan, p1.status, p1.tanggal_pengajuan, p1.pengaju, p1.catatan
        FROM perjalanan_pengajuan p1
        INNER JOIN (
            SELECT id_perjalanan, MAX(id) AS max_id FROM perjalanan_pengajuan GROUP BY id_perjalanan
        ) p_max ON p1.id_perjalanan = p_max.id_perjalanan AND p1.id = p_max.max_id
    ) pp ON p.id = pp.id_perjalanan
    ORDER BY p.id DESC";
$res_all = mysqli_query($conn, $sql_all);
$count_all = $res_all ? mysqli_num_rows($res_all) : 0;

// 3. Query Pengajuan Milik User / User sebagai Peserta
$sql_saya = "SELECT p.id, p.no_dokumen, p.nama, p.npp, p.departemen, p.tanggal_perjalanan, p.kota_tujuan, 
        p.kota_asal, p.jumlah_hari, p.budget_total, p.tanggal_dokumen, p.created_at,
        (SELECT GROUP_CONCAT(CONCAT(pk.nama, CASE WHEN pk.is_pemohon = 1 THEN ' (Pengaju)' ELSE '' END) ORDER BY pk.is_pemohon DESC SEPARATOR ', ') 
         FROM perjalanan_karyawan pk WHERE pk.perjalanan_id = p.id) AS daftar_peserta,
        COALESCE(pp.status, 'BELUM DIAJUKAN') AS status, 
        pp.tanggal_pengajuan, pp.pengaju, pp.catatan
    FROM perjalanan_dinas p
    LEFT JOIN (
        SELECT p1.id_perjalanan, p1.status, p1.tanggal_pengajuan, p1.pengaju, p1.catatan
        FROM perjalanan_pengajuan p1
        INNER JOIN (
            SELECT id_perjalanan, MAX(id) AS max_id FROM perjalanan_pengajuan GROUP BY id_perjalanan
        ) p_max ON p1.id_perjalanan = p_max.id_perjalanan AND p1.id = p_max.max_id
    ) pp ON p.id = pp.id_perjalanan
    WHERE p.npp = '" . $npp_esc . "'
       OR p.id IN (SELECT perjalanan_id FROM perjalanan_karyawan WHERE npp = '" . $npp_esc . "')
    ORDER BY p.id DESC";
$res_saya = mysqli_query($conn, $sql_saya);
$count_saya = $res_saya ? mysqli_num_rows($res_saya) : 0;

// Tentukan tab aktif jika tidak diset secara eksplisit
if (!isset($_GET['tab'])) {
    $tab_active = ($count_pending > 0) ? 'pending' : 'semua';
}
?>
<style>
    .nav-tabs>li.active>a, .nav-tabs>li.active>a:focus, .nav-tabs>li.active>a:hover {
        font-weight: bold;
        border-top: 3px solid #337ab7;
    }
    .table-hover tbody tr:hover {
        background-color: #f9f9f9 !important;
    }
    .table thead tr th,
    table.dataTable thead tr th,
    #dataTablesPending thead tr th,
    #dataTablesAll thead tr th,
    #dataTablesSaya thead tr th {
        background-color: #337ab7 !important;
        color: #ffffff !important;
        font-weight: bold;
        vertical-align: middle;
        font-size: 12px;
        padding: 10px 8px;
    }
    .table td {
        padding: 8px;
        vertical-align: middle;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .status-diajukan {
        background-color: #fcf8e3;
        color: #8a6d3b;
        border: 1px solid #faebcc;
    }
    .status-disetujui {
        background-color: #dff0d8;
        color: #3c763d;
        border: 1px solid #d6e9c6;
    }
    .status-ditolak {
        background-color: #f2dede;
        color: #a94442;
        border: 1px solid #ebccd1;
    }
    .status-belum {
        background-color: #f5f5f5;
        color: #777;
        border: 1px solid #ddd;
    }
    .btn-action-group .btn {
        margin: 2px 1px;
    }
</style>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><i class="fa fa-check-square-o"></i> Persetujuan Perjalanan Dinas</h1>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fa fa-info-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-12">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 15px;">
                <li role="presentation" class="<?php echo ($tab_active === 'pending') ? 'active' : ''; ?>">
                    <a href="#tabPending" aria-controls="tabPending" role="tab" data-toggle="tab">
                        <i class="fa fa-bell text-danger"></i> <strong>Menunggu Persetujuan HR</strong>
                        <?php if ($count_pending > 0): ?>
                            <span class="badge" style="background-color: #d9534f; margin-left: 4px;"><?php echo $count_pending; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li role="presentation" class="<?php echo ($tab_active === 'semua') ? 'active' : ''; ?>">
                    <a href="#tabSemua" aria-controls="tabSemua" role="tab" data-toggle="tab">
                        <i class="fa fa-list"></i> <strong>Semua Pengajuan</strong>
                        <span class="badge" style="background-color: #777; margin-left: 4px;"><?php echo $count_all; ?></span>
                    </a>
                </li>
                <li role="presentation" class="<?php echo ($tab_active === 'saya') ? 'active' : ''; ?>">
                    <a href="#tabSaya" aria-controls="tabSaya" role="tab" data-toggle="tab">
                        <i class="fa fa-user"></i> <strong>Pengajuan Saya</strong>
                        <span class="badge" style="background-color: #337ab7; margin-left: 4px;"><?php echo $count_saya; ?></span>
                    </a>
                </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
                <!-- TAB 1: MENUNGGU PERSETUJUAN -->
                <div role="tabpanel" class="tab-pane <?php echo ($tab_active === 'pending') ? 'active' : ''; ?>" id="tabPending">
                    <div class="panel panel-primary">
                        <div class="panel-heading" style="background-color: #d9534f; border-color: #d43f3a; color: white;">
                            <i class="fa fa-clock-o"></i> Daftar Pengajuan Menunggu Persetujuan HR
                        </div>
                        <div class="panel-body">
                            <?php if ($count_pending == 0): ?>
                                <div class="alert alert-success" style="margin-bottom: 0;">
                                    <i class="fa fa-check-circle"></i> <strong>Bagus!</strong> Saat ini tidak ada pengajuan perjalanan dinas yang menunggu persetujuan Anda.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="dataTablesPending" style="width:100%;">
                                        <thead>
                                            <tr>
                                                <th style="width:30px;" class="text-center">No</th>
                                                <th style="width:110px;">No. Dokumen</th>
                                                <th>Peserta & Departemen</th>
                                                <th style="width:120px;">Kota Tujuan</th>
                                                <th style="width:110px;">Tgl Perjalanan</th>
                                                <th style="width:70px;" class="text-center">Durasi</th>
                                                <th style="width:120px;" class="text-right">Anggaran</th>
                                                <th style="width:110px;" class="text-center">Status</th>
                                                <th style="width:120px;">Tgl Pengajuan</th>
                                                <th style="width:170px;" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no = 1;
                                            while ($row = mysqli_fetch_assoc($res_pending)): 
                                                $status = $row['status'];
                                                $status_class = 'status-diajukan';
                                                $tgl_perjalanan = $row['tanggal_perjalanan'] ? date('d-m-Y', strtotime($row['tanggal_perjalanan'])) : '<em>Fleksibel</em>';
                                                $tgl_pengajuan = $row['tanggal_pengajuan'] ? date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])) : '-';
                                                $budget_formatted = 'Rp ' . number_format((float)$row['budget_total'], 0, ',', '.');
                                                $is_realisasi = in_array($status, ['REALISASI_DIAJUKAN', 'REALISASI_VERIFIED_HR']);
                                            ?>
                                                <tr>
                                                    <td class="text-center"><?php echo $no++; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($row['no_dokumen']); ?></strong></td>
                                                    <td>
                                                        <span class="label label-primary" style="font-size:10px;"><i class="fa fa-user"></i> Pengaju: <?php echo htmlspecialchars($row['nama']); ?></span>
                                                        <span class="label label-default" style="font-size:10px;"><?php echo htmlspecialchars($row['departemen']); ?></span>
                                                        <?php if (!empty($row['daftar_peserta']) && strpos($row['daftar_peserta'], ',') !== false): ?>
                                                            <div style="font-size:11px; color:#555; margin-top:3px;">
                                                                <i class="fa fa-users text-primary"></i> <strong>Rombongan:</strong> <?php echo htmlspecialchars($row['daftar_peserta']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><strong><?php echo htmlspecialchars($row['kota_tujuan']); ?></strong></td>
                                                    <td><?php echo $tgl_perjalanan; ?></td>
                                                    <td class="text-center"><?php echo (int)$row['jumlah_hari']; ?> hari</td>
                                                    <td class="text-right" style="font-weight:bold; color:#2e6da4; font-family: Consolas, monospace;"><?php echo $budget_formatted; ?></td>
                                                    <td class="text-center">
                                                        <span class="status-badge <?php echo $status_class; ?>">
                                                            <?php echo htmlspecialchars($status); ?>
                                                        </span>
                                                    </td>
                                                    <td><small><?php echo $tgl_pengajuan; ?></small></td>
                                                    <td class="text-center btn-action-group">
                                                        <a class="btn <?php echo $is_realisasi ? 'btn-primary' : 'btn-warning'; ?> btn-sm" 
                                                           href="perjalanan_dinas_approve.php?id=<?php echo $row['id']; ?>" 
                                                           style="font-weight:bold;">
                                                            <i class="fa <?php echo $is_realisasi ? 'fa-money' : 'fa-check-square-o'; ?>"></i> 
                                                            <?php echo $is_realisasi ? 'Approve Realisasi' : 'Proses Persetujuan'; ?>
                                                        </a>
                                                        <a class="btn btn-info btn-sm" href="perjalanan_dinas_detail.php?id=<?php echo $row['id']; ?>" title="Lihat Detail">
                                                            <i class="fa fa-eye"></i> Detail
                                                        </a>
                                                        <a class="btn btn-danger btn-sm" href="perjalanan_dinas_hapus.php?id=<?php echo $row['id']; ?>&from=pending" 
                                                           onclick="return confirm('Apakah Anda yakin ingin menghapus pengajuan perjalanan dinas no. <?php echo htmlspecialchars($row['no_dokumen']); ?> ini?');" 
                                                           title="Hapus Pengajuan">
                                                            <i class="fa fa-trash"></i> Hapus
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: SEMUA PENGAJUAN (RIWAYAT) -->
                <div role="tabpanel" class="tab-pane <?php echo ($tab_active === 'semua') ? 'active' : ''; ?>" id="tabSemua">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <i class="fa fa-list"></i> Semua Riwayat Pengajuan Perjalanan Dinas Perusahaan
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="dataTablesAll" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th style="width:30px;" class="text-center">No</th>
                                            <th style="width:110px;">No. Dokumen</th>
                                            <th>Peserta & Departemen</th>
                                            <th style="width:120px;">Kota Tujuan</th>
                                            <th style="width:110px;">Tgl Perjalanan</th>
                                            <th style="width:70px;" class="text-center">Durasi</th>
                                            <th style="width:120px;" class="text-right">Anggaran</th>
                                            <th style="width:110px;" class="text-center">Status</th>
                                            <th style="width:120px;">Tgl Pengajuan</th>
                                            <th style="width:160px;" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        while ($row = mysqli_fetch_assoc($res_all)): 
                                            $status = $row['status'];
                                            $status_class = 'status-belum';
                                            if (in_array($status, ['DIAJUKAN', 'REALISASI_DIAJUKAN', 'REALISASI_VERIFIED_HR'])) $status_class = 'status-diajukan';
                                            elseif (in_array($status, ['DISETUJUI', 'APPROVED_MANAGER_HR', 'REALISASI_SELESAI'])) $status_class = 'status-disetujui';
                                            elseif (in_array($status, ['DITOLAK', 'REALISASI_DITOLAK'])) $status_class = 'status-ditolak';
                                            
                                            $tgl_perjalanan = $row['tanggal_perjalanan'] ? date('d-m-Y', strtotime($row['tanggal_perjalanan'])) : '<em>Fleksibel</em>';
                                            $tgl_pengajuan = $row['tanggal_pengajuan'] ? date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])) : '-';
                                            $budget_formatted = 'Rp ' . number_format((float)$row['budget_total'], 0, ',', '.');
                                            $can_approve = in_array($status, ['DIAJUKAN', 'APPROVED_HR', 'REALISASI_DIAJUKAN', 'REALISASI_VERIFIED_HR']);
                                        ?>
                                            <tr>
                                                <td class="text-center"><?php echo $no++; ?></td>
                                                <td><strong><?php echo htmlspecialchars($row['no_dokumen']); ?></strong></td>
                                                <td>
                                                    <span class="label label-primary" style="font-size:10px;"><i class="fa fa-user"></i> <?php echo htmlspecialchars($row['nama']); ?></span>
                                                    <span class="label label-default" style="font-size:10px;"><?php echo htmlspecialchars($row['departemen']); ?></span>
                                                    <?php if (!empty($row['daftar_peserta']) && strpos($row['daftar_peserta'], ',') !== false): ?>
                                                        <div style="font-size:11px; color:#555; margin-top:3px;">
                                                            <i class="fa fa-users text-primary"></i> <?php echo htmlspecialchars($row['daftar_peserta']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['kota_tujuan']); ?></td>
                                                <td><?php echo $tgl_perjalanan; ?></td>
                                                <td class="text-center"><?php echo (int)$row['jumlah_hari']; ?> hari</td>
                                                <td class="text-right" style="font-family: Consolas, monospace;"><?php echo $budget_formatted; ?></td>
                                                <td class="text-center">
                                                    <span class="status-badge <?php echo $status_class; ?>">
                                                        <?php echo htmlspecialchars($status); ?>
                                                    </span>
                                                </td>
                                                <td><small><?php echo $tgl_pengajuan; ?></small></td>
                                                <td class="text-center btn-action-group">
                                                    <?php if ($can_approve): ?>
                                                        <a class="btn btn-warning btn-sm" href="perjalanan_dinas_approve.php?id=<?php echo $row['id']; ?>" style="font-weight:bold;">
                                                            <i class="fa fa-check-square-o"></i> Persetujuan
                                                        </a>
                                                    <?php endif; ?>
                                                    <a class="btn btn-info btn-sm" href="perjalanan_dinas_detail.php?id=<?php echo $row['id']; ?>" title="Lihat Detail">
                                                        <i class="fa fa-eye"></i> Detail
                                                    </a>
                                                    <?php if (in_array($status, ['DIAJUKAN', 'BELUM DIAJUKAN', 'DITOLAK'])): ?>
                                                        <a class="btn btn-danger btn-sm" href="perjalanan_dinas_hapus.php?id=<?php echo $row['id']; ?>&from=semua" 
                                                           onclick="return confirm('Apakah Anda yakin ingin menghapus pengajuan perjalanan dinas no. <?php echo htmlspecialchars($row['no_dokumen']); ?> ini?');" 
                                                           title="Hapus Pengajuan">
                                                            <i class="fa fa-trash"></i> Hapus
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: PENGAJUAN SAYA -->
                <div role="tabpanel" class="tab-pane <?php echo ($tab_active === 'saya') ? 'active' : ''; ?>" id="tabSaya">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <i class="fa fa-user"></i> Riwayat Pengajuan Perjalanan Dinas Saya
                            <a href="form_perjalanan_dinas.php" class="btn btn-xs btn-success pull-right" style="margin-top:-2px;">
                                <i class="fa fa-plus"></i> Buat Pengajuan Baru
                            </a>
                        </div>
                        <div class="panel-body">
                            <?php if ($count_saya == 0): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> Anda belum memiliki riwayat pengajuan perjalanan dinas pribadi. 
                                    <a href="form_perjalanan_dinas.php" class="alert-link">Buat pengajuan baru di sini</a>.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="dataTablesSaya" style="width:100%;">
                                        <thead>
                                            <tr>
                                                <th style="width:30px;" class="text-center">No</th>
                                                <th style="width:110px;">No. Dokumen</th>
                                                <th>Peran / Peserta</th>
                                                <th style="width:120px;">Kota Tujuan</th>
                                                <th style="width:110px;">Tgl Perjalanan</th>
                                                <th style="width:70px;" class="text-center">Durasi</th>
                                                <th style="width:120px;" class="text-right">Anggaran</th>
                                                <th style="width:110px;" class="text-center">Status</th>
                                                <th style="width:120px;">Tgl Pengajuan</th>
                                                <th style="width:150px;" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no = 1;
                                            while ($row = mysqli_fetch_assoc($res_saya)): 
                                                $status = $row['status'];
                                                $status_class = 'status-belum';
                                                if (in_array($status, ['DIAJUKAN', 'REALISASI_DIAJUKAN', 'REALISASI_VERIFIED_HR'])) $status_class = 'status-diajukan';
                                                elseif (in_array($status, ['DISETUJUI', 'APPROVED_MANAGER_HR', 'REALISASI_SELESAI'])) $status_class = 'status-disetujui';
                                                elseif (in_array($status, ['DITOLAK', 'REALISASI_DITOLAK'])) $status_class = 'status-ditolak';
                                                
                                                $tgl_perjalanan = $row['tanggal_perjalanan'] ? date('d-m-Y', strtotime($row['tanggal_perjalanan'])) : '<em>Fleksibel</em>';
                                                $tgl_pengajuan = $row['tanggal_pengajuan'] ? date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])) : '-';
                                                $budget_formatted = 'Rp ' . number_format((float)$row['budget_total'], 0, ',', '.');
                                                $is_pemohon = ($row['npp'] === $npp_user);
                                            ?>
                                                <tr>
                                                    <td class="text-center"><?php echo $no++; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($row['no_dokumen']); ?></strong></td>
                                                    <td>
                                                        <?php if ($is_pemohon): ?>
                                                            <span class="label label-primary" style="font-size:10px;"><i class="fa fa-user"></i> Pengaju Utama</span>
                                                            <div style="font-weight:bold; margin-top:2px;"><?php echo htmlspecialchars($row['nama']); ?></div>
                                                        <?php else: ?>
                                                            <span class="label label-info" style="font-size:10px;"><i class="fa fa-user-plus"></i> Rekan Dinas</span>
                                                            <div style="font-size:12px; margin-top:2px;">Diajukan: <strong><?php echo htmlspecialchars($row['nama']); ?></strong></div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($row['daftar_peserta']) && strpos($row['daftar_peserta'], ',') !== false): ?>
                                                            <div style="font-size:11px; color:#555; margin-top:2px;">
                                                                <i class="fa fa-users"></i> <?php echo htmlspecialchars($row['daftar_peserta']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['kota_tujuan']); ?></td>
                                                    <td><?php echo $tgl_perjalanan; ?></td>
                                                    <td class="text-center"><?php echo (int)$row['jumlah_hari']; ?> hari</td>
                                                    <td class="text-right" style="font-family: Consolas, monospace;"><?php echo $budget_formatted; ?></td>
                                                    <td class="text-center">
                                                        <span class="status-badge <?php echo $status_class; ?>">
                                                            <?php echo htmlspecialchars($status); ?>
                                                        </span>
                                                    </td>
                                                    <td><small><?php echo $tgl_pengajuan; ?></small></td>
                                                    <td class="text-center btn-action-group">
                                                        <?php if ($is_pemohon && in_array($status, ['APPROVED_MANAGER_HR', 'DISETUJUI', 'REALISASI_DITOLAK'])): ?>
                                                            <a class="btn btn-success btn-sm btnRealisasiModal" href="#" data-id="<?php echo $row['id']; ?>" title="Upload Realisasi">
                                                                <i class="fa fa-upload"></i> Realisasi
                                                            </a>
                                                        <?php endif; ?>
                                                        <a class="btn btn-info btn-sm" href="perjalanan_dinas_detail.php?id=<?php echo $row['id']; ?>" title="Lihat Detail">
                                                            <i class="fa fa-eye"></i> Detail
                                                        </a>
                                                        <?php if ($is_pemohon && in_array($status, ['DIAJUKAN', 'BELUM DIAJUKAN', 'DITOLAK'])): ?>
                                                            <a class="btn btn-danger btn-sm" href="perjalanan_dinas_hapus.php?id=<?php echo $row['id']; ?>&from=saya" 
                                                               onclick="return confirm('Apakah Anda yakin ingin menghapus pengajuan perjalanan dinas no. <?php echo htmlspecialchars($row['no_dokumen']); ?> ini?');" 
                                                               title="Hapus Pengajuan">
                                                                <i class="fa fa-trash"></i> Hapus
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        var dtConfig = {
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
            language: {
                lengthMenu: "Tampilkan _MENU_ data per halaman",
                zeroRecords: "Data tidak ditemukan",
                info: "Menampilkan halaman _PAGE_ dari _PAGES_",
                infoEmpty: "Tidak ada data yang tersedia",
                infoFiltered: "(difilter dari _MAX_ total data)",
                search: "Cari:",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                }
            },
            order: [[0, 'asc']]
        };

        if ($('#dataTablesPending').length > 0) {
            $('#dataTablesPending').DataTable(dtConfig);
        }
        if ($('#dataTablesAll').length > 0) {
            $('#dataTablesAll').DataTable(dtConfig);
        }
        if ($('#dataTablesSaya').length > 0) {
            $('#dataTablesSaya').DataTable(dtConfig);
        }

        $(document).on('click', '.btnRealisasiModal', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            $('#realisasi_id_perjalanan').val(id);
            $('#realisasiModalBody').html('<div class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin fa-2x"></i> Memuat data...</div>');
            $('#realisasiModal').modal('show');
            
            $.get('get_realisasi_items.php', { id: id }, function(html) {
                $('#realisasiModalBody').html(html);
            }).fail(function() {
                $('#realisasiModalBody').html('<div class="alert alert-danger">Gagal memuat rincian perjalanan dinas.</div>');
            });
        });
    });
</script>

<!-- Modal Realisasi -->
<div id="realisasiModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <form action="submit_realisasi.php" method="post" enctype="multipart/form-data" id="realisasiForm">
            <input type="hidden" name="id_perjalanan" id="realisasi_id_perjalanan">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-upload"></i> Laporan Realisasi Perjalanan Dinas</h4>
                </div>
                <div class="modal-body" id="realisasiModalBody">
                    <div class="text-center" style="padding: 20px;">
                        <i class="fa fa-spinner fa-spin fa-2x"></i> Memuat data...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success" onclick="return confirm('Apakah Anda yakin data realisasi sudah benar dan siap dikirim?');">
                        <i class="fa fa-paper-plane"></i> Kirim Laporan Realisasi
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include("layout_bottom.php"); ?>
