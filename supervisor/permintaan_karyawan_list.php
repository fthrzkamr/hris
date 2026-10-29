<?php
include("sess_check.php");
$pagedesc = 'Daftar Permintaan Karyawan';
include("layout_top.php");

// Check if user is Manager or Leader
if($sess_jabatan !== 'Manager' && $sess_jabatan !== 'Leader') {
    header("location: index.php?error=access_denied");
    exit();
}

// DB connection already included in sess_check.php
// Ensure table exists
$createTable = "CREATE TABLE IF NOT EXISTS permintaan_karyawan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_dokumen VARCHAR(50),
    revisi VARCHAR(20),
    tanggal_dokumen DATE,
    jabatan VARCHAR(255),
    tgl_mulai DATE,
    jumlah_dibutuhkan INT,
    untuk VARCHAR(255),
    jumlah_sekarang INT,
    alasan TEXT,
    gender VARCHAR(20),
    usia VARCHAR(50),
    pendidikan VARCHAR(50),
    jurusan VARCHAR(255),
    pengalaman VARCHAR(50),
    tinggi VARCHAR(20),
    berat VARCHAR(20),
    rentang_gaji VARCHAR(100),
    lain_lain TEXT,
    created_by VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($conn, $createTable)) {
    // If table creation fails, show error (silently continue as table might already exist)
    // error_log("Table creation error: " . mysqli_error($conn));
}

// Ensure created_by column exists (for old tables)
$checkColumn = "SHOW COLUMNS FROM permintaan_karyawan LIKE 'created_by'";
$colResult = mysqli_query($conn, $checkColumn);
if ($colResult && mysqli_num_rows($colResult) == 0) {
    // Column doesn't exist, add it
    mysqli_query($conn, "ALTER TABLE permintaan_karyawan ADD COLUMN created_by VARCHAR(20)");
    mysqli_query($conn, "ALTER TABLE permintaan_karyawan ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    mysqli_query($conn, "ALTER TABLE permintaan_karyawan ADD INDEX idx_created_by (created_by)");
}

$message = '';
// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delid = intval($_POST['permintaan_id'] ?? 0);
    if ($delid > 0) {
        // Only allow deleting own records
        $check_stmt = mysqli_prepare($conn, "SELECT created_by FROM permintaan_karyawan WHERE id = ?");
        if ($check_stmt === false) {
            $message = 'Database error: ' . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($check_stmt, 'i', $delid);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $check_row = mysqli_fetch_assoc($check_result);
        
            if ($check_row && $check_row['created_by'] == $sess_mngid) {
                $dstmt = mysqli_prepare($conn, "DELETE FROM permintaan_karyawan WHERE id = ?");
                if ($dstmt === false) {
                    $message = 'Database error: ' . mysqli_error($conn);
                } else {
                    mysqli_stmt_bind_param($dstmt, 'i', $delid);
                    $dok = mysqli_stmt_execute($dstmt);
                    if ($dok) {
                        $message = 'Permintaan berhasil dihapus.';
                    } else {
                        $message = 'Gagal menghapus permintaan: ' . mysqli_error($conn);
                    }
                }
            } else {
                $message = 'Anda tidak memiliki izin untuk menghapus data ini.';
            }
        }
    } else {
        $message = 'ID permintaan tidak valid.';
    }
}

// Fetch list - only show own records
$sql = "SELECT p.*, (SELECT status FROM permintaan_pengajuan WHERE id_permintaan = p.id ORDER BY id DESC LIMIT 1) AS status FROM permintaan_karyawan p WHERE p.created_by = ? ORDER BY p.id DESC";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt === false) {
    die('Database error: ' . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, 's', $sess_mngid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
?>
<style>
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

    #dataTables {
        font-size: 13px;
    }

    #dataTables td {
        padding: 8px 6px;
        vertical-align: middle;
    }

    .btn-group-action .btn {
        margin-right: 3px;
        margin-bottom: 3px;
    }

    .status-badge { padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; display: inline-block; }
    .status-diajukan { background: #fcf8e3; color: #8a6d3b; border: 1px solid #faebcc; }
    .status-disetujui { background: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
    .status-ditolak { background: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
    .status-belum { background: #d9edf7; color: #31708f; border: 1px solid #bce8f1; }

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

        .btn-sm {
            padding: 3px 8px;
            font-size: 11px;
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
                    <a href="form_permintaan_karyawan.php" class="btn btn-success">
                        <i class="fa fa-plus"></i> Buat Permintaan Baru
                    </a>
                </div>
            </div>

            <!-- Data Table Panel -->
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa fa-table"></i> Daftar Permintaan Karyawan Saya
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover" id="dataTables">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No. Dokumen</th>
                                    <th>Jabatan</th>
                                    <th>Tanggal Dokumen</th>
                                    <th>Jumlah Dibutuhkan</th>
                                    <th>Untuk</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($res)): 
                                ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($row['no_dokumen']); ?></td>
                                        <td><?php echo htmlspecialchars($row['jabatan']); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($row['tanggal_dokumen'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['jumlah_dibutuhkan']); ?> orang</td>
                                        <td><?php echo htmlspecialchars($row['untuk']); ?></td>
                                        <td>
                                            <?php 
                                            $status = $row['status'] ?? 'BELUM DIAJUKAN';
                                            $badge_class = 'status-belum';
                                            if ($status == 'DIAJUKAN') $badge_class = 'status-diajukan';
                                            if ($status == 'DISETUJUI') $badge_class = 'status-disetujui';
                                            if ($status == 'DITOLAK') $badge_class = 'status-ditolak';
                                            ?>
                                            <span class="status-badge <?php echo $badge_class; ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                        <td class="btn-group-action">
                                            <a class="btn btn-info btn-sm" href="permintaan_karyawan_detail.php?id=<?php echo $row['id']; ?>">
                                                <i class="fa fa-eye"></i> Lihat
                                            </a>

                                            <!-- Delete form -->
                                            <form method="post" class="form-delete" style="display:inline">
                                                <input type="hidden" name="permintaan_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="button" class="btn btn-danger btn-sm btn-delete">
                                                    <i class="fa fa-trash"></i> Hapus
                                                </button>
                                            </form>
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
            order: [[3, 'desc']], // Sort by Tanggal Dokumen (descending)
            columnDefs: [
                { orderable: false, targets: [0, 6] }, // Disable sorting on No and Aksi
                { className: "text-center", targets: [0, 4, 6] } // Center align
            ],
            dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
                '<"row"<"col-sm-12"tr>>' +
                '<"row"<"col-sm-5"i><"col-sm-7"p>>'
        });

        // SweetAlert confirmation for Delete
        $(document).on('click', '.btn-delete', function (e) {
            e.preventDefault();
            var btn = $(this);
            var form = btn.closest('form');
            Swal.fire({
                title: 'Hapus permintaan?',
                text: 'Data akan dihapus permanen. Lanjutkan?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6'
            }).then(function(result){
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>

<?php include 'layout_bottom.php'; ?>
