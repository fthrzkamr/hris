<?php
$pagedesc = 'Daftar Perjalanan Dinas ';
$menuparent = 'perjalanan_dinas';
include("sess_check.php");
include("layout_top.php");

// DB connection - adjust path based on your config location
include __DIR__ . '/../dist/config/koneksi.php';

// Get NPP from session
$npp_user = isset($sess_mngid) ? $sess_mngid : (isset($sess_admid) ? $sess_admid : '');

if (empty($npp_user)) {
    echo '<div class="alert alert-danger">Session tidak valid. Silakan login kembali.</div>';
    include("layout_bottom.php");
    exit;
}

// ensure pengajuan table exists
$createPengajuan = "CREATE TABLE IF NOT EXISTS perjalanan_pengajuan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_perjalanan INT NOT NULL,
    npp VARCHAR(50),
    pengaju VARCHAR(100),
    tanggal_pengajuan DATETIME,
    status VARCHAR(50),
    catatan TEXT,
    approver VARCHAR(100),
    tanggal_approval DATETIME,
    FOREIGN KEY (id_perjalanan) REFERENCES perjalanan_dinas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $createPengajuan);

$message = '';
$messageType = 'info';

// Check for success message from redirect
if (isset($_GET['success'])) {
    $message = $_GET['success'];
    $messageType = 'success';
}

// Fetch list with latest status - filtered by current user's NPP
$npp_esc = mysqli_real_escape_string($conn, $npp_user);
$sql = "SELECT p.id, p.no_dokumen, p.nama, p.departemen, p.tanggal_perjalanan, p.kota_tujuan, 
        p.kota_asal, p.jumlah_hari, p.budget_total, p.tanggal_dokumen, p.created_at,
        (SELECT status FROM perjalanan_pengajuan WHERE id_perjalanan = p.id ORDER BY id DESC LIMIT 1) AS status,
        (SELECT tanggal_pengajuan FROM perjalanan_pengajuan WHERE id_perjalanan = p.id ORDER BY id DESC LIMIT 1) AS tanggal_pengajuan
    FROM perjalanan_dinas p
    WHERE p.npp = '" . $npp_esc . "'
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
        white-space: nowrap;
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

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fa fa-info-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-12">
            <!-- Data Table Panel -->
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa fa-table"></i> Daftar Perjalanan Dinas Saya
                </div>
                <div class="panel-body">
                    <?php if (mysqli_num_rows($res) == 0): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> Anda belum memiliki pengajuan perjalanan dinas. 
                            <a href="form_perjalanan_dinas.php" class="alert-link">Buat pengajuan baru</a>.
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover" id="dataTables">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No. Dokumen</th>
                                    <th>Tanggal Dibuat</th>
                                    <th>Kota Tujuan</th>
                                    <th>Tanggal Perjalanan</th>
                                    <th>Jumlah Hari</th>
                                    <th>Estimasi Biaya</th>
                                    <th>Status</th>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                mysqli_data_seek($res, 0); // Reset result pointer
                                while ($row = mysqli_fetch_assoc($res)): 
                                    $status = $row['status'] ?? 'BELUM DIAJUKAN';
                                    $status_class = 'status-belum';
                                    if ($status == 'DIAJUKAN') $status_class = 'status-diajukan';
                                    elseif ($status == 'DISETUJUI') $status_class = 'status-disetujui';
                                    elseif ($status == 'DITOLAK') $status_class = 'status-ditolak';
                                    
                                    $tgl_perjalanan = $row['tanggal_perjalanan'] ? date('d-m-Y', strtotime($row['tanggal_perjalanan'])) : '<em>Fleksibel</em>';
                                    $tgl_pengajuan = $row['tanggal_pengajuan'] ? date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])) : '-';
                                    $budget_formatted = 'Rp ' . number_format((float)$row['budget_total'], 0, ',', '.');
                                ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['no_dokumen']); ?></strong></td>
                                        <td><?php echo date('d-m-Y', strtotime($row['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['kota_tujuan']); ?></td>
                                        <td><?php echo $tgl_perjalanan; ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($row['jumlah_hari']); ?> hari</td>
                                        <td class="text-right"><?php echo $budget_formatted; ?></td>
                                        <td class="text-center">
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $tgl_pengajuan; ?></td>
                                        <td class="btn-group-action text-center">
                                            <a class="btn btn-info btn-sm" href="perjalanan_dinas_detail.php?id=<?php echo $row['id']; ?>">
                                                <i class="fa fa-eye"></i> Detail
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
    </div>
</div>

<script>
    $(document).ready(function() {
        <?php if (mysqli_num_rows($res) > 0): ?>
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
            order: [[2, 'desc']], // Sort by Tanggal Dibuat column (descending)
            columnDefs: [
                { orderable: false, targets: [0, 9] }, // Disable sorting on No and Aksi
                { className: "text-center", targets: [0, 5, 7, 9] } // Center align specific columns
            ],
            dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
                '<"row"<"col-sm-12"tr>>' +
                '<"row"<"col-sm-5"i><"col-sm-7"p>>'
        });
        <?php endif; ?>

        // Enable tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>

<?php include("layout_bottom.php"); ?>
