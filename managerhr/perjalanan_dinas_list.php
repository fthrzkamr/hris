<?php
include("sess_check.php");
$pagedesc = 'Daftar Perjalanan Dinas';
$menuparent = 'approval';
include("layout_top.php");

// DB connection
include __DIR__ . '/dist/config/koneksi.php';

// ensure pengajuan table exists
$createPengajuan = "CREATE TABLE IF NOT EXISTS perjalanan_pengajuan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_perjalanan INT NOT NULL,
    npp VARCHAR(50),
    pengaju VARCHAR(100),
    tanggal_pengajuan DATETIME,
    status VARCHAR(50),
    catatan TEXT,
    FOREIGN KEY (id_perjalanan) REFERENCES perjalanan_dinas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $createPengajuan);

$message = '';
// Handle delete action (hapus perjalanan dinas)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delid = intval($_POST['perjalanan_id'] ?? 0);
    if ($delid > 0) {
        $dstmt = mysqli_prepare($conn, "DELETE FROM perjalanan_dinas WHERE id = ?");
        mysqli_stmt_bind_param($dstmt, 'i', $delid);
        $dok = mysqli_stmt_execute($dstmt);
        if ($dok) {
            $message = 'Perjalanan dinas berhasil dihapus.';
        } else {
            $message = 'Gagal menghapus perjalanan dinas: ' . mysqli_error($conn);
        }
    } else {
        $message = 'ID perjalanan dinas tidak valid.';
    }
}
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

// Fetch list with latest status
$sql = "SELECT p.id, p.no_dokumen, p.nama, p.departemen, p.tanggal_perjalanan, p.kota_tujuan, p.tanggal_dokumen,
    (SELECT status FROM perjalanan_pengajuan WHERE id_perjalanan = p.id ORDER BY id DESC LIMIT 1) AS status
    FROM perjalanan_dinas p
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
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-cog"></i> Aksi
                </div>
                <div class="panel-body">
                    <a href="../form_perjalanan_dinas.php" class="btn btn-success">
                        <i class="fa fa-plus"></i> Buat Perjalanan Dinas Baru
                    </a>
                </div>
            </div>

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
                                    <th>Tanggal Perjalanan</th>
                                    <th>Kota Tujuan</th>
                                    <th>Tanggal Dokumen</th>
                                    <th>Status Pengajuan</th>
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
                                    elseif ($status == 'DISETUJUI') $status_class = 'status-disetujui';
                                    elseif ($status == 'DITOLAK') $status_class = 'status-ditolak';
                                ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($row['no_dokumen']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($row['departemen']); ?></td>
                                        <td><?php echo htmlspecialchars($row['tanggal_perjalanan']); ?></td>
                                        <td><?php echo htmlspecialchars($row['kota_tujuan']); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($row['tanggal_dokumen'])); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                        <td class="btn-group-action">
                                            <?php if (empty($row['status'])): ?>
                                                <form method="post" class="form-ajukan" style="display:inline">
                                                    <input type="hidden" name="perjalanan_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="action" value="ajukan">
                                                    <button type="button" class="btn btn-primary btn-sm btn-ajukan">
                                                        <i class="fa fa-paper-plane"></i> Ajukan
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-default btn-sm" disabled>
                                                    <i class="fa fa-check"></i> Sudah Diajukan
                                                </button>
                                            <?php endif; ?>

                                            <!-- Delete form (submit via JS confirm) -->
                                            <form method="post" class="form-delete" style="display:inline">
                                                <input type="hidden" name="perjalanan_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="button" class="btn btn-danger btn-sm btn-delete">
                                                    <i class="fa fa-trash"></i> Hapus
                                                </button>
                                            </form>

                                            <a class="btn btn-info btn-sm" href="perjalanan_dinas_detail.php?id=<?php echo $row['id']; ?>">
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
            order: [[6, 'desc']], // Sort by Tanggal Dokumen column (descending)
            columnDefs: [
                { orderable: false, targets: [0, 8] }, // Disable sorting on No and Aksi
                { className: "text-center", targets: [0, 7, 8] } // Center align specific columns
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
                    form.submit();
                }
            });
        });

        // SweetAlert confirmation for Delete
        $(document).on('click', '.btn-delete', function (e) {
            e.preventDefault();
            var btn = $(this);
            var form = btn.closest('form');
            Swal.fire({
                title: 'Hapus perjalanan dinas?',
                text: 'Data perjalanan dinas akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
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
