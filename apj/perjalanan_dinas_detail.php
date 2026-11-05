<?php
include("sess_check.php");
$pagedesc = 'Detail Perjalanan Dinas';
$menuparent = 'perjalanan_dinas';
include("layout_top.php");

// DB connection
if (is_file(__DIR__ . '/dist/config/koneksi.php')) {
    include_once __DIR__ . '/dist/config/koneksi.php';
} else {
    include_once __DIR__ . '/../dist/config/koneksi.php';
}
if (is_file(__DIR__ . '/dist/config/library.php')) {
    include_once __DIR__ . '/dist/config/library.php';
} elseif (is_file(__DIR__ . '/../dist/config/library.php')) {
    include_once __DIR__ . '/../dist/config/library.php';
}

// Get NPP from session for security check
$npp_user = $sess_mngid ?? ($sess_kryid ?? ($sess_spvid ?? ($sess_admid ?? ($sess_dirid ?? ($_SESSION['npp'] ?? '')))));

// Get ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo '<div id="page-wrapper"><div class="alert alert-danger" style="margin-top:20px;">ID perjalanan tidak valid.</div></div>';
    include("layout_bottom.php");
    exit;
}

// Pastikan biaya luar kota / uang harian sudah ter-generate otomatis jika belum ada
if (function_exists('auto_generate_biaya_luar_kota')) {
    auto_generate_biaya_luar_kota($conn, $id);
}

// Fetch main record
$npp_esc = mysqli_real_escape_string($conn, $npp_user);
$stmt = mysqli_prepare($conn, "SELECT p.*, e.nama_bank, e.norek_mandiri as no_rekening, COALESCE(pr.revision_count, 0) as revision_count FROM perjalanan_dinas p LEFT JOIN employee e ON p.npp = e.npp LEFT JOIN (SELECT perjalanan_id, MAX(revision_count) as revision_count FROM perjalanan_rincian GROUP BY perjalanan_id) pr ON p.id = pr.perjalanan_id WHERE p.id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo '<div id="page-wrapper"><div class="alert alert-danger" style="margin-top:20px;">Data perjalanan dinas tidak ditemukan.</div></div>';
    include("layout_bottom.php");
    exit;
}

// Cek hak akses: Pengaju utama, rekan peserta di perjalanan_karyawan, atau atasan/HR
$chk_peserta = mysqli_query($conn, "SELECT id FROM perjalanan_karyawan WHERE perjalanan_id = " . (int)$id . " AND npp = '" . $npp_esc . "' LIMIT 1");
$is_peserta = ($chk_peserta && mysqli_num_rows($chk_peserta) > 0);
$is_admin = !empty($_SESSION['managerhr']) || !empty($_SESSION['direktur']) || !empty($_SESSION['admin']);

if ($data['npp'] !== $npp_user && !$is_peserta && !$is_admin) {
    echo '<div id="page-wrapper"><div class="alert alert-danger" style="margin-top:20px;">Anda tidak memiliki akses untuk melihat pengajuan perjalanan dinas ini.</div></div>';
    include("layout_bottom.php");
    exit;
}

// Ambil daftar seluruh peserta perjalanan dinas (Pengaju + Rekan) beserta info rekening dari tabel employee
$q_peserta = mysqli_query($conn, "SELECT pk.*, e.nama_bank, e.norek_mandiri AS no_rekening FROM perjalanan_karyawan pk LEFT JOIN employee e ON pk.npp = e.npp WHERE pk.perjalanan_id = " . (int)$id . " ORDER BY pk.is_pemohon DESC, pk.id ASC");
$peserta_items = [];
while ($row_p = mysqli_fetch_assoc($q_peserta)) {
    $peserta_items[] = $row_p;
}
if (empty($peserta_items)) {
    $peserta_items[] = [
        'npp' => $data['npp'],
        'nama' => $data['nama'],
        'departemen' => $data['departemen'],
        'is_pemohon' => 1,
        'nama_bank' => $data['nama_bank'] ?? 'Mandiri',
        'no_rekening' => $data['no_rekening'] ?? '-'
    ];
}

// Fetch rincian biaya
$stmt_rincian = mysqli_prepare($conn, "SELECT * FROM perjalanan_rincian WHERE perjalanan_id = ? ORDER BY nomor ASC, id ASC");
mysqli_stmt_bind_param($stmt_rincian, 'i', $id);
mysqli_stmt_execute($stmt_rincian);
$result_rincian = mysqli_stmt_get_result($stmt_rincian);
$rincian_items = [];
while ($row = mysqli_fetch_assoc($result_rincian)) {
    $rincian_items[] = $row;
}

// Kelompokkan rincian per karyawan agar tampilan rapi & terstruktur
$grouped_rincian = [];
foreach ($peserta_items as $p) {
    $grouped_rincian[$p['npp']] = [
        'peserta' => $p,
        'items' => []
    ];
}
foreach ($rincian_items as $it) {
    $target_npp = !empty($it['npp_karyawan']) ? trim($it['npp_karyawan']) : $data['npp'];
    if (!isset($grouped_rincian[$target_npp])) {
        $grouped_rincian[$target_npp] = [
            'peserta' => [
                'npp' => $target_npp,
                'nama' => !empty($it['nama_karyawan']) ? $it['nama_karyawan'] : 'Peserta',
                'departemen' => '-',
                'is_pemohon' => ($target_npp === $data['npp'] ? 1 : 0)
            ],
            'items' => []
        ];
    }
    $grouped_rincian[$target_npp]['items'][] = $it;
}

// Hitung subtotal per peserta & lengkapi info rekening jika belum ada
foreach ($grouped_rincian as $g_npp => &$grp) {
    $sub_b = 0;
    foreach ($grp['items'] as $it_calc) {
        $q_c = max(1, (int)($it_calc['qty'] ?? 1));
        $n_c = (float)($it_calc['nominal'] ?? 0);
        $p_c = (float)($it_calc['perkiraan'] ?? 0);
        $used_c = $n_c > 0 ? $n_c : $p_c;
        $sub_b += ($used_c * $q_c);
    }
    $grp['subtotal_budget'] = $sub_b;

    if (!isset($grp['peserta']['no_rekening'])) {
        $npp_lookup = mysqli_real_escape_string($conn, $g_npp);
        $q_emp_bank = mysqli_query($conn, "SELECT nama_bank, norek_mandiri AS no_rekening FROM employee WHERE npp = '$npp_lookup' LIMIT 1");
        $r_emp_bank = $q_emp_bank ? mysqli_fetch_assoc($q_emp_bank) : null;
        $grp['peserta']['nama_bank'] = $r_emp_bank['nama_bank'] ?? 'Mandiri';
        $grp['peserta']['no_rekening'] = $r_emp_bank['no_rekening'] ?? '-';
    }
}
unset($grp);

// Hitung ulang total anggaran aktual dari rincian agar selalu sinkron
$calculated_grand_total = 0;
$calculated_estimasi_total = 0;
foreach ($rincian_items as $item) {
    $q_item = max(1, (int)($item['qty'] ?? 1));
    $n_item = (float)($item['nominal'] ?? 0);
    $p_item = (float)($item['perkiraan'] ?? 0);
    $calculated_estimasi_total += ($p_item * $q_item);
    $calculated_grand_total += ($n_item > 0 ? ($n_item * $q_item) : ($p_item * $q_item));
}
if ($calculated_grand_total > 0 && abs((float)$data['budget_total'] - $calculated_grand_total) > 0.01) {
    $data['budget_total'] = $calculated_grand_total;
    mysqli_query($conn, "UPDATE perjalanan_dinas SET budget_total = '" . (float)$calculated_grand_total . "' WHERE id = " . (int)$id);
}

// Fetch status pengajuan
$stmt_status = mysqli_prepare($conn, "SELECT * FROM perjalanan_pengajuan WHERE id_perjalanan = ? ORDER BY id DESC LIMIT 1");
mysqli_stmt_bind_param($stmt_status, 'i', $id);
mysqli_stmt_execute($stmt_status);
$result_status = mysqli_stmt_get_result($stmt_status);
$submission = mysqli_fetch_assoc($result_status);

$is_realisasi_active = $submission && in_array($submission['status'], ['DISETUJUI', 'REALISASI_DIAJUKAN', 'REALISASI_VERIFIED_HR', 'REALISASI_SELESAI', 'REALISASI_DITOLAK']) && !empty($rincian_items[0]['nominal_realisasi']) && (float)$rincian_items[0]['nominal_realisasi'] > 0;
if ($submission && in_array($submission['status'], ['REALISASI_DIAJUKAN', 'REALISASI_VERIFIED_HR', 'REALISASI_SELESAI', 'REALISASI_DITOLAK'])) {
    $is_realisasi_active = true;
}
$prefix = is_file(__DIR__ . '/dist/config/koneksi.php') ? '' : '../';

// Status Label & Class
$status = $submission['status'] ?? 'DIAJUKAN';
$status_badge_class = 'label-default';
$status_text = $status;

if ($status == 'DIAJUKAN') {
    $status_badge_class = 'label-warning';
    $status_text = 'Menunggu Persetujuan Manager HR';
} elseif ($status == 'APPROVED_MANAGER_HR') {
    $status_badge_class = 'label-info';
    $status_text = 'Disetujui HR (Menunggu Persetujuan Direktur)';
} elseif ($status == 'DISETUJUI') {
    $status_badge_class = 'label-success';
    $status_text = 'Disetujui Sepenuhnya (Approved)';
} elseif ($status == 'DITOLAK') {
    $status_badge_class = 'label-danger';
    $status_text = 'Pengajuan Ditolak';
} elseif ($status == 'REALISASI_DIAJUKAN') {
    $status_badge_class = 'label-primary';
    $status_text = 'Realisasi Diajukan (Menunggu Verifikasi HR)';
} elseif ($status == 'REALISASI_VERIFIED_HR') {
    $status_badge_class = 'label-info';
    $status_text = 'Realisasi Diverifikasi HR';
} elseif ($status == 'REALISASI_SELESAI') {
    $status_badge_class = 'label-success';
    $status_text = 'Realisasi Selesai';
} elseif ($status == 'REALISASI_DITOLAK') {
    $status_badge_class = 'label-danger';
    $status_text = 'Realisasi Ditolak';
}

// Logo Path
$logo_src = '';
if (is_file(__DIR__ . '/foto/logo-dua.webp')) {
    $logo_src = 'foto/logo-dua.webp';
} elseif (is_file(__DIR__ . '/../foto/logo-dua.webp')) {
    $logo_src = '../foto/logo-dua.webp';
}
?>

<style>
    /* Styling Kertas Dokumen Resmi */
    .document-paper {
        background: #fff;
        border: 1px solid #cfd8dc;
        box-shadow: 0 3px 12px rgba(0,0,0,0.07);
        border-radius: 6px;
        padding: 28px 32px;
        margin-bottom: 35px;
        color: #222;
        font-size: 13px;
    }

    .doc-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #2c3e50;
        padding-bottom: 14px;
        margin-bottom: 18px;
    }

    .doc-header-logo {
        width: 150px;
        text-align: left;
    }

    .doc-header-logo img {
        max-height: 48px;
        width: auto;
    }

    .doc-header-title {
        flex: 1;
        text-align: center;
        padding: 0 12px;
    }

    .doc-header-title h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #111;
        letter-spacing: 0.5px;
    }

    .doc-header-title h4 {
        margin: 4px 0 0;
        font-size: 13px;
        font-weight: 600;
        color: #444;
        letter-spacing: 0.3px;
    }

    .doc-meta-box {
        border: 1px solid #555;
        border-radius: 3px;
        padding: 7px 12px;
        font-size: 11.5px;
        width: 195px;
        background: #fafbfc;
        line-height: 1.55;
    }

    /* Tabel Detail Info Perjalanan */
    table.table-info-trip {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    table.table-info-trip td {
        padding: 7px 12px;
        vertical-align: middle;
        font-size: 12.5px;
        border: 1px solid #d6dce1;
    }

    table.table-info-trip td.label-col {
        background-color: #f4f7f9;
        font-weight: 600;
        color: #2c3e50;
        width: 18%;
    }

    /* Tabel Rincian Anggaran */
    table.table-rincian-doc {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
        margin-bottom: 18px;
    }

    table.table-rincian-doc th,
    table.table-rincian-doc td {
        border: 1px solid #b0bec5;
        padding: 7px 10px;
        font-size: 12.5px;
        vertical-align: middle;
    }

    table.table-rincian-doc thead th {
        background-color: #2e6da4;
        color: #fff;
        text-align: center;
        font-weight: 600;
        font-size: 12px;
        letter-spacing: 0.2px;
        padding: 8px 10px;
    }

    table.table-rincian-doc tr.group-header-utama td {
        background-color: #eaf2f8;
        border-top: 2px solid #2e6da4;
        padding: 8px 12px;
        font-weight: 700;
        color: #1f4e78;
        font-size: 13px;
    }

    table.table-rincian-doc tr.group-header-rekan td {
        background-color: #f2f8fb;
        border-top: 2px solid #31708f;
        padding: 8px 12px;
        font-weight: 700;
        color: #245870;
        font-size: 13px;
    }

    table.table-rincian-doc tr.subtotal-peserta-row td {
        background-color: #f9fbfd;
        font-weight: 700;
        color: #333;
        border-bottom: 2px solid #b0bec5;
        padding: 7px 10px;
    }

    table.table-rincian-doc tr.grand-total-row td {
        background-color: #edf4fb;
        font-weight: 700;
        font-size: 13.5px;
        color: #1b4f72;
        border-top: 2px solid #2e6da4;
        border-bottom: 2px solid #2e6da4;
        padding: 10px;
    }

    .num-col {
        font-family: 'Consolas', 'Courier New', monospace;
        white-space: nowrap;
    }

    .badge-role {
        display: inline-block;
        padding: 2px 8px;
        font-size: 10.5px;
        font-weight: 600;
        border-radius: 3px;
        margin-right: 6px;
        text-transform: uppercase;
    }

    .badge-role-utama {
        background-color: #2e6da4;
        color: #fff;
    }

    .badge-role-rekan {
        background-color: #31708f;
        color: #fff;
    }

    /* Tabel Tanda Tangan */
    table.table-signatures {
        width: 100%;
        margin-top: 22px;
        border-collapse: collapse;
        table-layout: fixed;
    }

    table.table-signatures td {
        text-align: center;
        border: 1px solid #b0bec5;
        padding: 7px 6px;
        font-size: 11.5px;
        vertical-align: top;
    }

    table.table-signatures td.sign-title {
        background-color: #f4f7f9;
        font-weight: 600;
        color: #2c3e50;
        height: 28px;
        vertical-align: middle;
    }

    table.table-signatures td.sign-space {
        height: 68px;
        vertical-align: middle;
    }

    /* Status Tracking Card */
    .status-tracking-card {
        background: #fdfefe;
        border: 1px solid #bce8f1;
        border-left: 4px solid #2e6da4;
        border-radius: 4px;
        padding: 14px 18px;
        margin-bottom: 18px;
    }

    .status-tracking-card h5 {
        margin: 0 0 6px;
        font-weight: bold;
        color: #2e6da4;
        font-size: 14px;
    }

    /* Print View Settings */
    @page {
        size: A4 portrait;
        margin: 10mm;
    }

    @media print {
        #wrapper > nav,
        .navbar,
        .navbar-default,
        .sidebar,
        .footer-bottom,
        .no-print,
        .print-controls,
        .status-tracking-card,
        .page-header {
            display: none !important;
        }

        #page-wrapper {
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            width: 100% !important;
            background: #fff !important;
        }

        .document-paper {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }

        table.table-rincian-doc thead th {
            background-color: #e6e6e6 !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        table.table-rincian-doc tr.group-header-utama td,
        table.table-rincian-doc tr.group-header-rekan td {
            background-color: #f0f0f0 !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .badge-role {
            border: 1px solid #333 !important;
            color: #000 !important;
            background: transparent !important;
        }

        table.table-rincian-doc th,
        table.table-rincian-doc td,
        table.table-info-trip td,
        table.table-signatures td {
            border: 1px solid #333 !important;
        }

        .table-signatures {
            page-break-inside: avoid !important;
        }
    }
</style>

<div id="page-wrapper">
    <!-- ACTION BAR / HEADER -->
    <div class="row no-print" style="margin-top: 15px; margin-bottom: 15px;">
        <div class="col-sm-6 col-xs-12">
            <h3 style="margin: 0; font-size: 20px; font-weight: bold; color: #2e6da4;">
                Detail Perjalanan Dinas
            </h3>
        </div>
        <div class="col-sm-6 col-xs-12 text-right">
            <a href="perjalanan_dinas_list.php" class="btn btn-default btn-sm">
                <i class="fa fa-arrow-left"></i> Kembali ke Daftar
            </a>
            <?php if (!empty($_SESSION['managerhr']) && in_array($status, ['DIAJUKAN', 'APPROVED_HR', 'REALISASI_DIAJUKAN', 'REALISASI_VERIFIED_HR'])): ?>
                <a href="perjalanan_dinas_approve.php?id=<?php echo $id; ?>" class="btn btn-warning btn-sm" style="margin-left: 5px; font-weight: bold;">
                    <i class="fa fa-check-square-o"></i> Proses Persetujuan HR
                </a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-primary btn-sm" style="margin-left: 5px;">
                <i class="fa fa-print"></i> Cetak Dokumen
            </button>
            <?php 
            $can_delete = in_array($status, ['DIAJUKAN', 'BELUM DIAJUKAN', 'DITOLAK']) && ($data['npp'] === $npp_user || $is_admin);
            if ($can_delete): 
            ?>
                <a href="perjalanan_dinas_hapus.php?id=<?php echo $id; ?>" 
                   class="btn btn-danger btn-sm" 
                   style="margin-left: 5px;" 
                   onclick="return confirm('Apakah Anda yakin ingin menghapus pengajuan perjalanan dinas no. <?php echo htmlspecialchars($data['no_dokumen']); ?> ini?');">
                    <i class="fa fa-trash"></i> Hapus Pengajuan
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- STATUS TRACKING CARD (Hanya Tampil di Layar, Sembunyi Saat Print) -->
    <div class="status-tracking-card no-print">
        <div class="row" style="display: flex; align-items: center; flex-wrap: wrap;">
            <div class="col-md-7 col-sm-12">
                <h5>
                    Status Pengajuan: 
                    <span class="label <?php echo $status_badge_class; ?>" style="font-size: 12px; margin-left: 4px; padding: 4px 8px;">
                        <?php echo htmlspecialchars($status_text); ?>
                    </span>
                </h5>
                <div style="font-size: 12.5px; color: #555; line-height: 1.65;">
                    Diajukan oleh: <strong><?php echo htmlspecialchars($submission['pengaju'] ?? $data['nama']); ?></strong> 
                    pada <?php echo date('d-m-Y H:i', strtotime($submission['tanggal_pengajuan'] ?? $data['tanggal_dokumen'])); ?>
                    
                    <?php if (!empty($submission['approver_manager_hr'])): ?>
                        <br>Approval HR: <strong><?php echo htmlspecialchars($submission['approver_manager_hr']); ?></strong> 
                        (<?php echo htmlspecialchars($submission['approval_manager_hr'] ?? 'APPROVED'); ?>)
                        pada <?php echo !empty($submission['tanggal_approval_manager_hr']) ? date('d-m-Y H:i', strtotime($submission['tanggal_approval_manager_hr'])) : '-'; ?>
                        <?php if (!empty($submission['catatan_manager_hr'])): ?>
                            <br><em>Catatan HR: <?php echo htmlspecialchars($submission['catatan_manager_hr']); ?></em>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($submission['approver_direktur'])): ?>
                        <br>Approval Direktur: <strong><?php echo htmlspecialchars($submission['approver_direktur']); ?></strong> 
                        (<?php echo htmlspecialchars($submission['approval_direktur'] ?? 'APPROVED'); ?>)
                        pada <?php echo !empty($submission['tanggal_approval_direktur']) ? date('d-m-Y H:i', strtotime($submission['tanggal_approval_direktur'])) : '-'; ?>
                        <?php if (!empty($submission['catatan_direktur'])): ?>
                            <br><em>Catatan Direktur: <?php echo htmlspecialchars($submission['catatan_direktur']); ?></em>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-5 col-sm-12 text-right">
                <div style="font-size: 12px; color: #666;">Total Anggaran Disetujui:</div>
                <div class="num-col" style="font-size: 22px; font-weight: bold; color: #2e6da4;">
                    Rp <?php echo number_format((float)$data['budget_total'], 0, ',', '.'); ?>
                </div>
                <?php if ($calculated_estimasi_total > 0 && abs($calculated_estimasi_total - (float)$data['budget_total']) > 0.01): ?>
                    <div style="font-size: 11.5px; color: #777; margin-top: 2px;">
                        Total Estimasi Pengaju Awal: <strong>Rp <?php echo number_format($calculated_estimasi_total, 0, ',', '.'); ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- LEMBAR DOKUMEN CETAK RESMI (FORM ANGGARAN PERJALANAN BISNIS) -->
    <div class="document-paper">
        <!-- HEADER DOKUMEN -->
        <div class="doc-header">
            <div class="doc-header-logo">
                <?php if (!empty($logo_src)): ?>
                    <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Logo Perusahaan">
                <?php else: ?>
                    <strong style="font-size: 16px; color: #2e6da4;">DF GROUP</strong>
                <?php endif; ?>
            </div>
            <div class="doc-header-title">
                <h3>PT DUA FARMA MAHAKARSA</h3>
                <h4>FORM ANGGARAN PERJALANAN BISNIS (DINAS)</h4>
            </div>
            <div class="doc-meta-box">
                <div><strong>No. Dokumen:</strong> <?php echo htmlspecialchars($data['no_dokumen']); ?></div>
                <div><strong>Revisi:</strong> <?php echo htmlspecialchars($data['revision_count'] ?? 0); ?></div>
                <div><strong>Tanggal:</strong> <?php echo date('d-m-Y', strtotime($data['tanggal_dokumen'])); ?></div>
            </div>
        </div>

        <!-- TABEL INFORMASI PERJALANAN -->
        <table class="table-info-trip">
            <tr>
                <td class="label-col">Pengaju Utama (PIC)</td>
                <td style="width: 34%;">: <strong><?php echo htmlspecialchars($data['nama']); ?></strong> (<?php echo htmlspecialchars($data['npp']); ?>)</td>
                <td class="label-col">Departemen</td>
                <td>: <?php echo htmlspecialchars($data['departemen']); ?></td>
            </tr>
            <?php if (!empty($peserta_items) && count($peserta_items) > 1): ?>
            <tr>
                <td class="label-col">Rekan Peserta</td>
                <td colspan="3">: 
                    <?php 
                    $rekan_labels = [];
                    foreach ($peserta_items as $pi) {
                        if (!$pi['is_pemohon']) {
                            $dept_str = !empty($pi['departemen']) ? ' - ' . htmlspecialchars($pi['departemen']) : '';
                            $rekan_labels[] = '<strong>' . htmlspecialchars($pi['nama']) . '</strong> (' . htmlspecialchars($pi['npp']) . $dept_str . ')';
                        }
                    }
                    echo implode(', ', $rekan_labels);
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="label-col">Tanggal Perjalanan</td>
                <td>: <?php echo $data['tanggal_perjalanan'] ? date('d-m-Y', strtotime($data['tanggal_perjalanan'])) : '<em>Fleksibel</em>'; ?></td>
                <td class="label-col">Jumlah Hari</td>
                <td>: <strong><?php echo (int)$data['jumlah_hari']; ?> hari</strong></td>
            </tr>
            <tr>
                <td class="label-col">Kota Asal - Tujuan</td>
                <td colspan="3">: <?php echo htmlspecialchars($data['kota_asal'] ?: '-'); ?> &rarr; <strong><?php echo htmlspecialchars($data['kota_tujuan']); ?></strong></td>
            </tr>
            <tr>
                <td class="label-col">Tujuan / Alasan Dinas</td>
                <td colspan="3">: <?php echo nl2br(htmlspecialchars($data['tujuan'])); ?></td>
            </tr>
        </table>

        <!-- TABEL RINCIAN ANGGARAN & BIAYA (DIKELOMPOKKAN PER KARYAWAN) -->
        <?php if ($is_realisasi_active): ?>
            <!-- TABEL VIEW REALISASI -->
            <table class="table-rincian-doc">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th>Keterangan Biaya</th>
                        <th style="width: 6%;">Qty</th>
                        <th style="width: 14%;">Nominal Budget</th>
                        <th style="width: 15%;">Total Budget</th>
                        <th style="width: 14%;">Nominal Realisasi</th>
                        <th style="width: 15%;">Total Realisasi</th>
                        <th style="width: 18%;">Bukti / Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_budget_sum = 0;
                    $total_realisasi_sum = 0;
                    foreach ($grouped_rincian as $g_npp => $group):
                        $p_info = $group['peserta'];
                        $is_lead = !empty($p_info['is_pemohon']);
                        $row_cls = $is_lead ? 'group-header-utama' : 'group-header-rekan';
                        $badge_cls = $is_lead ? 'badge-role-utama' : 'badge-role-rekan';
                        $role_label = $is_lead ? 'Pengaju Utama (PIC)' : 'Rekan Dinas';
                        $dept_info = !empty($p_info['departemen']) && $p_info['departemen'] !== '-' ? ' - ' . htmlspecialchars($p_info['departemen']) : '';
                        $sub_budget_p = 0;
                        $sub_real_p = 0;
                    ?>
                        <tr class="<?php echo $row_cls; ?>">
                            <td colspan="8">
                                <span class="badge-role <?php echo $badge_cls; ?>"><?php echo $role_label; ?></span>
                                <?php echo htmlspecialchars($p_info['nama']); ?> (<?php echo htmlspecialchars($p_info['npp']); ?><?php echo $dept_info; ?>)
                            </td>
                        </tr>
                        <?php if (!empty($group['items'])): 
                            $no_item = 1;
                            foreach ($group['items'] as $item):
                                $qty = max(1, (int)($item['qty'] ?? 1));
                                $nom_budget = (float)($item['nominal'] ?? 0);
                                $perk_budget = (float)($item['perkiraan'] ?? 0);
                                $used_budget = $nom_budget > 0 ? $nom_budget : $perk_budget;
                                $line_budget = $used_budget * $qty;
                                $sub_budget_p += $line_budget;
                                $total_budget_sum += $line_budget;

                                $nom_real = (float)($item['nominal_realisasi'] ?? 0);
                                $line_real = isset($item['total_realisasi']) && $item['total_realisasi'] !== null && (float)$item['total_realisasi'] > 0 ? (float)$item['total_realisasi'] : ($nom_real * $qty);
                                $sub_real_p += $line_real;
                                $total_realisasi_sum += $line_real;
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $no_item++; ?></td>
                                <td><strong><?php echo htmlspecialchars($item['ket']); ?></strong></td>
                                <td class="text-center"><?php echo $qty; ?></td>
                                <td class="text-right num-col">Rp <?php echo number_format($used_budget, 0, ',', '.'); ?></td>
                                <td class="text-right num-col">Rp <?php echo number_format($line_budget, 0, ',', '.'); ?></td>
                                <td class="text-right num-col" style="color: #245870; font-weight: 600;">Rp <?php echo number_format($nom_real, 0, ',', '.'); ?></td>
                                <td class="text-right num-col" style="color: #245870; font-weight: 700;">Rp <?php echo number_format($line_real, 0, ',', '.'); ?></td>
                                <td>
                                    <?php if (!empty($item['bukti_realisasi'])): 
                                        $buktis = json_decode($item['bukti_realisasi'] ?? '', true) ?: [];
                                        foreach ($buktis as $b):
                                            if (strpos($b, 'uploads/bukti_realisasi/') !== 0) continue; ?>
                                            <a href="<?php echo htmlspecialchars($prefix . $b); ?>" target="_blank" class="no-print" style="margin-right: 5px;">
                                                <img src="<?php echo htmlspecialchars($prefix . $b); ?>" style="width: 30px; height: 30px; object-fit: cover; border: 1px solid #ccc; border-radius: 3px;">
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($item['keterangan'] ?: '-'); ?>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted"><em>Belum ada rincian biaya untuk karyawan ini.</em></td>
                            </tr>
                        <?php endif; ?>
                        <tr class="subtotal-peserta-row">
                            <td colspan="4" class="text-right">Subtotal Budget <?php echo htmlspecialchars($p_info['nama']); ?>:</td>
                            <td class="text-right num-col">Rp <?php echo number_format($sub_budget_p, 0, ',', '.'); ?></td>
                            <td class="text-right" style="color: #245870;">Subtotal Realisasi:</td>
                            <td class="text-right num-col" style="color: #245870;">Rp <?php echo number_format($sub_real_p, 0, ',', '.'); ?></td>
                            <td></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="grand-total-row">
                        <td colspan="4" class="text-right">GRAND TOTAL BUDGET:</td>
                        <td class="text-right num-col">Rp <?php echo number_format($total_budget_sum, 0, ',', '.'); ?></td>
                        <td class="text-right" style="color: #245870;">GRAND TOTAL REALISASI:</td>
                        <td class="text-right num-col" style="color: #245870;">Rp <?php echo number_format($total_realisasi_sum, 0, ',', '.'); ?></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <?php 
            $selisih = $total_realisasi_sum - $total_budget_sum;
            if ($total_realisasi_sum > 0): 
            ?>
                <div class="alert alert-info no-print" style="margin-top: 10px; padding: 10px 14px; font-size: 13px;">
                    <strong>Rekapitulasi Selisih:</strong> 
                    <?php 
                    if ($selisih < 0) {
                        echo 'Kurang Bayar (Sisa Uang Muka dikembalikan ke Perusahaan): <strong>Rp ' . number_format(abs($selisih), 0, ',', '.') . '</strong>';
                    } elseif ($selisih > 0) {
                        echo 'Lebih Bayar (Reimbursement dari Perusahaan ke Karyawan): <strong>Rp ' . number_format($selisih, 0, ',', '.') . '</strong>';
                    } else {
                        echo 'Sesuai Budget (Tidak ada selisih biaya)';
                    }
                    ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- TABEL VIEW ANGGARAN PENGAJUAN / STANDARD (DIKELOMPOKKAN PER KARYAWAN) -->
            <table class="table-rincian-doc">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 6%;">No</th>
                        <th rowspan="2" style="width: 38%;">Keterangan Biaya</th>
                        <th colspan="4">Rincian Anggaran Biaya (Rp)</th>
                    </tr>
                    <tr>
                        <th style="width: 15%;">Estimasi Pengaju</th>
                        <th style="width: 8%;">Qty</th>
                        <th style="width: 16%;">Nominal Disetujui</th>
                        <th style="width: 17%;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_estimasi_all = 0;
                    $total_disetujui_all = 0;
                    if (!empty($rincian_items)):
                        foreach ($grouped_rincian as $g_npp => $group):
                            $p_info = $group['peserta'];
                            $is_lead = !empty($p_info['is_pemohon']);
                            $row_cls = $is_lead ? 'group-header-utama' : 'group-header-rekan';
                            $badge_cls = $is_lead ? 'badge-role-utama' : 'badge-role-rekan';
                            $role_label = $is_lead ? 'Pengaju Utama (PIC)' : 'Rekan Dinas';
                            $dept_info = !empty($p_info['departemen']) && $p_info['departemen'] !== '-' ? ' - ' . htmlspecialchars($p_info['departemen']) : '';
                            $sub_estimasi_peserta = 0;
                            $sub_disetujui_peserta = 0;
                    ?>
                        <!-- BARIS HEADER KARYAWAN -->
                        <tr class="<?php echo $row_cls; ?>">
                            <td colspan="6">
                                <span class="badge-role <?php echo $badge_cls; ?>"><?php echo $role_label; ?></span>
                                <?php echo htmlspecialchars($p_info['nama']); ?> (<?php echo htmlspecialchars($p_info['npp']); ?><?php echo $dept_info; ?>)
                            </td>
                        </tr>
                        <?php if (!empty($group['items'])): 
                            $no_urut = 1;
                            foreach ($group['items'] as $item):
                                $qty = max(1, (int)($item['qty'] ?? 1));
                                $nom = (float)($item['nominal'] ?? 0);
                                $perk = (float)($item['perkiraan'] ?? 0);
                                $used_nom = $nom > 0 ? $nom : $perk;
                                $line_estimasi = $perk * $qty;
                                $line_total = $used_nom * $qty;

                                $sub_estimasi_peserta += $line_estimasi;
                                $sub_disetujui_peserta += $line_total;
                                $total_estimasi_all += $line_estimasi;
                                $total_disetujui_all += $line_total;

                                $is_revised = ($nom > 0 && $perk > 0 && abs($nom - $perk) > 0.01);
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $no_urut++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['ket']); ?></strong>
                                </td>
                                <td class="text-right num-col" style="color: #555;">
                                    Rp <?php echo number_format($perk, 0, ',', '.'); ?>
                                </td>
                                <td class="text-center" style="font-weight: 600;"><?php echo $qty; ?></td>
                                <td class="text-right num-col">
                                    <?php if ($nom > 0): ?>
                                        <strong style="<?php echo $is_revised ? 'color: #1f618d;' : ''; ?>">
                                            Rp <?php echo number_format($nom, 0, ',', '.'); ?>
                                        </strong>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 11px;"><em>(Menunggu HR)</em></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right num-col" style="font-weight: 700; color: #1f4e78;">
                                    Rp <?php echo number_format($line_total, 0, ',', '.'); ?>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted" style="padding: 10px;">
                                    <em>Belum ada rincian biaya untuk karyawan ini.</em>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <!-- SUBTOTAL PER KARYAWAN -->
                        <tr class="subtotal-peserta-row">
                            <td colspan="5" class="text-right">
                                Subtotal Biaya <?php echo htmlspecialchars($p_info['nama']); ?>:
                            </td>
                            <td class="text-right num-col" style="color: #1f4e78;">
                                Rp <?php echo number_format($sub_disetujui_peserta, 0, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php 
                        endforeach;
                    else: ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 15px;">Tidak ada rincian anggaran.</td>
                        </tr>
                    <?php endif; ?>
                    <!-- GRAND TOTAL -->
                    <tr class="grand-total-row">
                        <td colspan="5" class="text-right">GRAND TOTAL ANGGARAN DISETUJUI:</td>
                        <td class="text-right num-col">
                            Rp <?php echo number_format($total_disetujui_all > 0 ? $total_disetujui_all : (float)$data['budget_total'], 0, ',', '.'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- INFORMASI TRANSFER REKENING PER KARYAWAN -->
        <div style="margin-top: 14px;">
            <div style="font-size: 12.5px; font-weight: 700; color: #2c3e50; margin-bottom: 6px;">
                Informasi Transfer Rekening Peserta:
            </div>
            <table class="table-rincian-doc" style="margin-top: 0; margin-bottom: 12px;">
                <thead>
                    <tr>
                        <th style="width: 6%;">No</th>
                        <th style="width: 36%;">Nama Penerima / Karyawan</th>
                        <th style="width: 16%;">Bank</th>
                        <th style="width: 22%;">No. Rekening</th>
                        <th style="width: 20%;">Nominal Transfer (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no_rek = 1;
                    foreach ($grouped_rincian as $g_npp => $group):
                        $p_rek = $group['peserta'];
                        $bank_emp = !empty($p_rek['nama_bank']) ? $p_rek['nama_bank'] : 'Mandiri';
                        $norek_emp = !empty($p_rek['no_rekening']) ? $p_rek['no_rekening'] : '-';
                        $nom_tf = (float)($group['subtotal_budget'] ?? 0);
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $no_rek++; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($p_rek['nama']); ?></strong>
                            <span style="color: #666; font-size: 11.5px;">(<?php echo htmlspecialchars($p_rek['npp']); ?>)</span>
                        </td>
                        <td class="text-center"><?php echo htmlspecialchars($bank_emp); ?></td>
                        <td class="text-center num-col"><strong><?php echo htmlspecialchars($norek_emp); ?></strong></td>
                        <td class="text-right num-col" style="font-weight: 700; color: #1f4e78;">
                            Rp <?php echo number_format($nom_tf, 0, ',', '.'); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- CATATAN KETENTUAN PERJALANAN DINAS -->
        <div style="margin-top: 12px; font-size: 11px; color: #444; border-top: 1px dashed #ccc; padding-top: 8px;">
            <strong>Ketentuan Perjalanan Dinas:</strong>
            <ol style="margin: 4px 0 0 18px; padding: 0; line-height: 1.5;">
                <li>Setelah perjalanan dinas selesai, formulir realisasi beserta bukti/nota/struk fisik wajib diserahkan ke HRD &amp; Finance paling lambat H+3 setelah tanggal kepulangan.</li>
                <li>Uang harian/luar kota dihitung otomatis sesuai standar plafon jabatan perusahaan.</li>
                <li>Jika terdapat kebutuhan penambahan biaya tak terduga, harap segera menginformasikan ke Manager HRD dan Finance.</li>
            </ol>
        </div>
    </div>
</div>

<?php include("layout_bottom.php"); ?>
