<?php
include("sess_check.php");
include __DIR__ . '/dist/config/koneksi.php';

$pagedesc = 'Daftar Perjalanan Dinas';
$menuparent = 'perjalanan_dinas';
include("layout_top.php");

// pastikan apakah user current adalah Manager HR atau Admin
$is_managerhr = (isset($sess_mngid) && !empty($sess_mngid)) || (isset($sess_admid) && !empty($sess_admid));

// DB connection
include __DIR__ . '/dist/config/koneksi.php';

// ensure pengajuan table exists with approval columns
$createPengajuan = "CREATE TABLE IF NOT EXISTS perjalanan_pengajuan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_perjalanan INT NOT NULL,
    npp VARCHAR(50),
    pengaju VARCHAR(100),
    tanggal_pengajuan DATETIME,
    status VARCHAR(50),
    catatan TEXT,
    approval_hr VARCHAR(50),
    approver_hr VARCHAR(100),
    tanggal_approval_hr DATETIME,
    catatan_hr TEXT,
    approval_manager_hr VARCHAR(50),
    approver_manager_hr VARCHAR(100),
    tanggal_approval_manager_hr DATETIME,
    catatan_manager_hr TEXT,
    approval_direktur VARCHAR(50),
    approver_direktur VARCHAR(100),
    tanggal_approval_direktur DATETIME,
    catatan_direktur TEXT,
    FOREIGN KEY (id_perjalanan) REFERENCES perjalanan_dinas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $createPengajuan);

// Add approval columns if not exist - check first to avoid errors
$checkColumns = mysqli_query($conn, "SHOW COLUMNS FROM perjalanan_pengajuan LIKE 'approval_hr'");
if (mysqli_num_rows($checkColumns) == 0) {
    // Columns don't exist, add them
    $alterQueries = [
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN approval_hr VARCHAR(50)",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN approver_hr VARCHAR(100)",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN tanggal_approval_hr DATETIME",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN catatan_hr TEXT",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN approval_manager_hr VARCHAR(50)",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN approver_manager_hr VARCHAR(100)",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN tanggal_approval_manager_hr DATETIME",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN catatan_manager_hr TEXT",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN approval_direktur VARCHAR(50)",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN approver_direktur VARCHAR(100)",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN tanggal_approval_direktur DATETIME",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN catatan_direktur TEXT"
    ];
    
    foreach ($alterQueries as $query) {
        @mysqli_query($conn, $query); // Suppress errors if column already exists
    }
}

$message = '';
// Handle ajukan action (HR mengajukan ke Manager HR)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajukan') {
    $perjalanan_id = intval($_POST['id'] ?? 0);
    $nominals = $_POST['nominal'] ?? []; // expect array [rincian_id => nominal]
    $pengaju_npp = $sess_admid ?? $sess_mngid ?? $_SESSION['admin'] ?? null;
    $pengaju_name = $sess_admname ?? $sess_mngname ?? ($_SESSION['admin'] ?? 'HR');

    if ($perjalanan_id > 0) {
        mysqli_begin_transaction($conn);
        try {
            // update rincian jika dikirim nominal oleh HR
            if (!empty($nominals) && is_array($nominals)) {
                foreach ($nominals as $rid => $val) {
                    $rid = intval($rid);
                    $val_clean = preg_replace('/[^0-9\.]/','',$val);
                    $nominal_new = $val_clean === '' ? '0' : $val_clean;

                    // ambil qty lama
                    $stmtOld = mysqli_prepare($conn, "SELECT qty FROM perjalanan_rincian WHERE id = ?");
                    mysqli_stmt_bind_param($stmtOld, 'i', $rid);
                    mysqli_stmt_execute($stmtOld);
                    $resOld = mysqli_stmt_get_result($stmtOld);
                    $rowOld = mysqli_fetch_assoc($resOld);
                    $qty = (float)($rowOld['qty'] ?? 1);

                    $total_new = number_format((float)$nominal_new * $qty, 2, '.', '');
                    $upd = mysqli_prepare($conn, "UPDATE perjalanan_rincian SET nominal = ?, total = ? WHERE id = ?");
                    mysqli_stmt_bind_param($upd, 'ssi', $nominal_new, $total_new, $rid);
                    mysqli_stmt_execute($upd);
                }

                // rekalkulasi budget_total
                $stmtSum = mysqli_prepare($conn, "SELECT SUM(CAST(REPLACE(total,',','') AS DECIMAL(20,2))) AS s FROM perjalanan_rincian WHERE perjalanan_id = ?");
                mysqli_stmt_bind_param($stmtSum, 'i', $perjalanan_id);
                mysqli_stmt_execute($stmtSum);
                $resSum = mysqli_stmt_get_result($stmtSum);
                $sumRow = mysqli_fetch_assoc($resSum);
                $budget_total = (float)($sumRow['s'] ?? 0);

                $updBudget = mysqli_prepare($conn, "UPDATE perjalanan_dinas SET budget_total = ? WHERE id = ?");
                mysqli_stmt_bind_param($updBudget, 'di', $budget_total, $perjalanan_id);
                mysqli_stmt_execute($updBudget);
            }

            // cari pengajuan terakhir untuk perjalanan ini
            $stmtSel = mysqli_prepare($conn, "SELECT id FROM perjalanan_pengajuan WHERE id_perjalanan = ? ORDER BY id DESC LIMIT 1 FOR UPDATE");
            mysqli_stmt_bind_param($stmtSel, 'i', $perjalanan_id);
            mysqli_stmt_execute($stmtSel);
            $resSel = mysqli_stmt_get_result($stmtSel);
            $last = mysqli_fetch_assoc($resSel);

            $tgl_now = date('Y-m-d H:i:s');
            $status = 'DIAJUKAN';

            if ($last && !empty($last['id'])) {
                $pid = (int)$last['id'];
                $updPeng = mysqli_prepare($conn, "UPDATE perjalanan_pengajuan SET npp = ?, pengaju = ?, tanggal_pengajuan = ?, status = ?, approval_manager_hr = NULL, approver_manager_hr = NULL, tanggal_approval_manager_hr = NULL, catatan_manager_hr = NULL WHERE id = ?");
                mysqli_stmt_bind_param($updPeng, 'ssssi', $pengaju_npp, $pengaju_name, $tgl_now, $status, $pid);
                mysqli_stmt_execute($updPeng);
            } else {
                $ins = mysqli_prepare($conn, "INSERT INTO perjalanan_pengajuan (id_perjalanan, npp, pengaju, tanggal_pengajuan, status) VALUES (?,?,?,?,?)");
                mysqli_stmt_bind_param($ins, 'issss', $perjalanan_id, $pengaju_npp, $pengaju_name, $tgl_now, $status);
                mysqli_stmt_execute($ins);
            }

            mysqli_commit($conn);
            $message = "Pengajuan berhasil dikirim.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = "Gagal mengajukan: " . $e->getMessage();
        }
    } else {
        $message = "ID perjalanan tidak valid.";
    }
}

// Fetch list with latest status and approval info (gunakan latest pengajuan per perjalanan)
$sql = "SELECT p.id, p.no_dokumen, p.nama, p.departemen, p.tanggal_perjalanan, p.kota_tujuan, p.tanggal_dokumen, p.budget_total,
    pg.status,
    pg.approval_manager_hr,
    pg.approval_direktur,
    pg.tanggal_pengajuan
    FROM perjalanan_dinas p
    LEFT JOIN (
        SELECT p2.*
        FROM perjalanan_pengajuan p2
        INNER JOIN (
            SELECT id_perjalanan, MAX(id) AS mid FROM perjalanan_pengajuan GROUP BY id_perjalanan
        ) m ON p2.id_perjalanan = m.id_perjalanan AND p2.id = m.mid
    ) pg ON pg.id_perjalanan = p.id
    ORDER BY CASE WHEN pg.status = 'DIAJUKAN' THEN 0 WHEN pg.status = 'REALISASI_DIAJUKAN' THEN 1 WHEN pg.status = 'BELUM DIAJUKAN' OR pg.status IS NULL THEN 2 ELSE 3 END ASC, p.id DESC";
$res = mysqli_query($conn, $sql);
?>
<style>
    /* Styling untuk meningkatkan readability */
    #dataTables tbody tr:hover {
        background-color: #f5f5f5 !important;
    }

    #dataTables thead tr th {
        background-color: #337ab7;
        color: white;
        font-weight: bold;
        vertical-align: middle;
        font-size: 12px;
        padding: 10px 6px;
    }

    .table-bordered>thead>tr>th {
        border-bottom-width: 2px;
    }

    /* Responsive table adjustments */
    #dataTables {
        font-size: 13px;
    }

    #dataTables td {
        padding: 8px 6px;
        vertical-align: middle;
    }

    /* Status badge styling */
    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .status-belum {
        background-color: #d9edf7;
        color: #31708f;
    }

    .status-diajukan {
        background-color: #fcf8e3;
        color: #8a6d3b;
    }

    .status-disetujui {
        background-color: #dff0d8;
        color: #3c763d;
    }

    .status-ditolak {
        background-color: #f2dede;
        color: #a94442;
    }

    .status-approved-hr {
        background-color: #d9edf7;
        color: #31708f;
    }

    .approval-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 2px;
        font-size: 10px;
        font-weight: bold;
        margin: 2px;
    }

    .approval-ok {
        background-color: #dff0d8;
        color: #3c763d;
    }

    .approval-pending {
        background-color: #fcf8e3;
        color: #8a6d3b;
    }

    .approval-reject {
        background-color: #f2dede;
        color: #a94442;
    }

    .approval-revisi {
        background-color: #d9edf7;
        color: #31708f;
    }

    /* DataTables custom styling */
    .dataTables_wrapper .dataTables_length select {
        padding: 4px;
        margin: 0 5px;
    }

    .dataTables_wrapper .dataTables_filter input {
        margin-left: 5px;
        padding: 4px;
    }

    .dataTables_wrapper .dataTables_info {
        padding-top: 8px;
        font-size: 12px;
    }

    .dataTables_wrapper .dataTables_paginate {
        padding-top: 8px;
    }

    /* Action buttons */
    .btn-group-action .btn {
        margin-right: 3px;
        margin-bottom: 3px;
    }

    /* Mobile responsiveness */
    @media screen and (max-width: 767px) {
        #dataTables {
            font-size: 11px;
        }

        #dataTables thead tr th {
            font-size: 10px;
            padding: 8px 4px;
        }

        #dataTables td {
            padding: 6px 4px;
        }

        .panel-heading {
            font-size: 13px;
        }

        .btn-sm {
            padding: 3px 8px;
            font-size: 11px;
        }

        .table-responsive {
            border: 0;
            margin-bottom: 15px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    }

    @media screen and (max-width: 480px) {
        h1.page-header {
            font-size: 20px;
        }
    }
</style>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                <?php echo $pagedesc; ?>
            </h1>
        </div>
    </div>

    <?php
    include("layout_alert.php");
    
    if ($message): ?>
        <div class="alert alert-info alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-12">
            <!-- Data Table Panel -->
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa fa-table"></i> Daftar Perjalanan Dinas
                </div>
                <div class="panel-body">
                    <div style="margin-bottom: 15px;">
                        <a href="perjalanan_dinas_export_xls.php" class="btn btn-success btn-sm" style="background-color: #5cb85c !important; border-color: #4cae4c !important; color: #fff !important; opacity: 1 !important;"><i class="fa fa-file-excel-o"></i> Export Excel</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover" id="dataTables">
                            <thead>
                                <tr>
                                    <th style="display:none"></th>
                                    <th>No</th>
                                    <th>No. Dokumen</th>
                                    <th>Nama</th>
                                    <th>Departemen</th>
                                    <th>Kota Tujuan</th>
                                    <th>Budget</th>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Status</th>
                                    <th>Approval HR</th>
                                    <th>Approval Direktur</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($res)): 
                                    $status = $row['status'] ?? 'BELUM DIAJUKAN';
                                    $status_class = 'status-belum';
                                    if ($status == 'DIAJUKAN' || $status == 'REALISASI_DIAJUKAN' || $status == 'REALISASI_VERIFIED_HR') $status_class = 'status-diajukan';
                                    elseif ($status == 'APPROVED_MANAGER_HR' || $status == 'DISETUJUI' || $status == 'REALISASI_SELESAI') $status_class = 'status-disetujui';
                                    elseif ($status == 'DITOLAK' || $status == 'REALISASI_DITOLAK') $status_class = 'status-ditolak';
                                    elseif ($status == 'REVISI') $status_class = 'status-revisi';
                                    
                                    $approval_manager_hr = $row['approval_manager_hr'] ?? null;
                                    $approval_direktur = $row['approval_direktur'] ?? null;
                                    
                                    // Can approve (oleh Manager HR / Direktur) jika status adalah DIAJUKAN atau APPROVED_MANAGER_HR
                                    $can_approve = in_array($status, ['DIAJUKAN', 'APPROVED_MANAGER_HR']);
                                    $sort_priority = ($status === 'DIAJUKAN') ? 0 : (($status === 'REALISASI_DIAJUKAN') ? 1 : (($status === 'BELUM DIAJUKAN' || empty($status)) ? 2 : 3));
                                ?>
                                    <tr>
                                        <td style="display:none"><?php echo $sort_priority; ?></td>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['no_dokumen']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($row['departemen']); ?></td>
                                        <td><?php echo htmlspecialchars($row['kota_tujuan']); ?></td>
                                        <td class="text-right">Rp <?php echo number_format((float)$row['budget_total'], 0, ',', '.'); ?></td>
                                        <td><?php echo $row['tanggal_pengajuan'] ? date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])) : '-'; ?></td>
                                        <td class="text-center">
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($approval_manager_hr == 'APPROVED'): ?>
                                                <span class="approval-badge approval-ok"><i class="fa fa-check"></i> Approved</span>
                                            <?php elseif ($approval_manager_hr == 'REVISI'): ?>
                                                <span class="approval-badge approval-revisi"><i class="fa fa-edit"></i> Revisi</span>
                                            <?php elseif ($approval_manager_hr == 'REJECTED'): ?>
                                                <span class="approval-badge approval-reject"><i class="fa fa-times"></i> Rejected</span>
                                            <?php else: ?>
                                                <span class="approval-badge approval-pending"><i class="fa fa-clock-o"></i> Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($approval_direktur == 'APPROVED'): ?>
                                                <span class="approval-badge approval-ok"><i class="fa fa-check"></i> Approved</span>
                                            <?php elseif ($approval_direktur == 'REJECTED'): ?>
                                                <span class="approval-badge approval-reject"><i class="fa fa-times"></i> Rejected</span>
                                            <?php else: ?>
                                                <span class="approval-badge approval-pending"><i class="fa fa-clock-o"></i> Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="btn-group-action text-center">
                                            <?php 
                                            // Tampilkan tombol Ajukan hanya untuk status yang belum diajukan
                                            if (in_array($status, ['BELUM DIAJUKAN'])): ?>
                                                <a class="btn btn-primary btn-sm" 
                                                   href="perjalanan_dinas_approve.php?id=<?php echo (int)$row['id']; ?>" 
                                                   title="Isi nominal & ajukan ke Manager HR">
                                                    <i class="fa fa-send"></i> Ajukan
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($status == 'DIAJUKAN' && $is_managerhr): ?>
                                                <a class="btn btn-success btn-sm" href="perjalanan_dinas_review.php?id=<?php echo $row['id']; ?>" 
                                                   title="Review & Approve (Admin)">
                                                    <i class="fa fa-check-square-o"></i> Review
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($status == 'REALISASI_DIAJUKAN'): ?>
                                                 <a class="btn btn-warning btn-sm" href="perjalanan_dinas_approve.php?id=<?php echo $row['id']; ?>" 
                                                    title="Verifikasi Laporan Realisasi">
                                                     <i class="fa fa-check-circle-o"></i> Verifikasi Realisasi
                                                 </a>
                                             <?php endif; ?>
                                            
                                            <a class="btn btn-info btn-sm" href="perjalanan_dinas_detail.php?id=<?php echo $row['id']; ?>" 
                                               title="Lihat Detail">
                                                <i class="fa fa-eye"></i> Lihat
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#dataTables').DataTable({
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
            columnDefs: [
                { visible: false, targets: [0] },
                { 
                    targets: 1, 
                    orderable: false, 
                    searchable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                { orderable: false, targets: [11] },
                { className: "text-center", targets: [1, 8, 9, 10, 11] }
            ],
            dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
                '<"row"<"col-sm-12"tr>>' +
                '<"row"<"col-sm-5"i><"col-sm-7"p>>'
        });

        // Enable tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // SweetAlert confirmation for Ajukan
        $(document).on('click', '.btn-ajukan', function (e) {
            e.preventDefault();
            var btn = $(this);
            var form = btn.closest('form');
            Swal.fire({
                title: 'Ajukan perjalanan dinas?',
                text: 'Perjalanan dinas akan dikirim ke Manager HR untuk proses review.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, ajukan',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function(result){
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>

<?php include 'layout_bottom.php'; ?>
