<?php
include("sess_check.php");
$pagedesc = 'Daftar Perjalanan Dinas';
$menuparent = 'perjalanan_dinas';
include("layout_top.php");

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
// Handle ajukan action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajukan') {
    $pid = intval($_POST['perjalanan_id'] ?? 0);
    $user = isset($sess_admname) ? $sess_admname : 'SYSTEM';
    $npp = isset($sess_admuser) ? $sess_admuser : null;
    $status = 'DIAJUKAN';
    $stmt = mysqli_prepare($conn, "INSERT INTO perjalanan_pengajuan (id_perjalanan, npp, pengaju, tanggal_pengajuan, status) VALUES (?,?,?,NOW(),?)");
    mysqli_stmt_bind_param($stmt, 'isss', $pid, $npp, $user, $status);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok) {
        $message = 'Pengajuan berhasil dikirim.';
    } else {
        $message = 'Gagal mengirim pengajuan: ' . mysqli_error($conn);
    }
}

// Fetch list with latest status and approval info
$sql = "SELECT p.id, p.no_dokumen, p.nama, p.departemen, p.tanggal_perjalanan, p.kota_tujuan, p.tanggal_dokumen, p.budget_total,
    pg.status,
    pg.approval_hr,
    pg.approval_direktur,
    pg.tanggal_pengajuan
    FROM perjalanan_dinas p
    LEFT JOIN perjalanan_pengajuan pg ON pg.id_perjalanan = p.id
    ORDER BY p.id DESC";
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
            <!-- Action Panel -->
            <!-- <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-cog"></i> Aksi
                </div>
                <div class="panel-body">
                    <a href="form_perjalanan_dinas.php" class="btn btn-success">
                        <i class="fa fa-plus"></i> Buat Perjalanan Dinas Baru
                    </a>
                </div>
            </div> -->

            <!-- Data Table Panel -->
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa fa-table"></i> Daftar Perjalanan Dinas
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover" id="dataTables">
                            <thead>
                                <tr>
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
                                    if ($status == 'DIAJUKAN') $status_class = 'status-diajukan';
                                    elseif ($status == 'APPROVED_HR') $status_class = 'status-approved-hr';
                                    elseif ($status == 'DISETUJUI') $status_class = 'status-disetujui';
                                    elseif ($status == 'DITOLAK') $status_class = 'status-ditolak';
                                    
                                    $approval_hr = $row['approval_hr'] ?? null;
                                    $approval_direktur = $row['approval_direktur'] ?? null;
                                    
                                    // Can approve if status is DIAJUKAN or APPROVED_HR
                                    $can_approve = in_array($status, ['DIAJUKAN', 'APPROVED_HR']);
                                ?>
                                    <tr>
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
                                            <?php if ($approval_hr == 'APPROVED'): ?>
                                                <span class="approval-badge approval-ok"><i class="fa fa-check"></i> Approved</span>
                                            <?php elseif ($approval_hr == 'REJECTED'): ?>
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
                                            <?php if ($can_approve): ?>
                                                <a class="btn btn-success btn-sm" href="perjalanan_dinas_approve.php?id=<?php echo $row['id']; ?>" 
                                                   title="Approve & Input Nominal">
                                                    <i class="fa fa-check-square-o"></i> Approve
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
            order: [[6, 'desc']], // Sort by Tanggal Pengajuan column (descending)
            columnDefs: [
                { orderable: false, targets: [0, 10] }, // Disable sorting on No and Aksi
                { className: "text-center", targets: [0, 7, 8, 9, 10] } // Center align specific columns
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
                text: 'Perjalanan dinas akan dikirim untuk proses persetujuan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, ajukan',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function(result){
                if (result.isConfirmed) {
                    // submit the original form (regular POST)
                    form.submit();
                }
            });
        });
    });
</script>

<?php include 'layout_bottom.php'; ?>
