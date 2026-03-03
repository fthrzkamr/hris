<?php
include("sess_check.php");
$pagedesc = 'Approval Permintaan Karyawan';
$menuparent = 'approval';
include("layout_top.php");

// DB connection
include __DIR__ . '/dist/config/koneksi.php';

// Fetch all records (both pending and processed)
$sql = "SELECT p.id, p.no_dokumen, p.jabatan, p.tanggal_dokumen, p.jumlah_dibutuhkan,
    pe.id as pengajuan_id, pe.status, pe.status_app_direktur, pe.pengaju, pe.tanggal_pengajuan
    FROM permintaan_karyawan p
    INNER JOIN permintaan_pengajuan pe ON pe.id_permintaan = p.id
    ORDER BY pe.tanggal_pengajuan DESC";
$res = mysqli_query($conn, $sql);

// Check for query error
if (!$res) {
    $error_msg = mysqli_error($conn);
}
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

    #dataTables {
        font-size: 13px;
    }

    #dataTables td {
        padding: 8px 6px;
        vertical-align: middle;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .status-pending {
        background-color: #fcf8e3;
        color: #8a6d3b;
    }

    .status-approved {
        background-color: #dff0d8;
        color: #3c763d;
    }

    .status-rejected {
        background-color: #f2dede;
        color: #a94442;
    }

    .status-waiting {
        background-color: #d9edf7;
        color: #31708f;
    }

    .btn-group-action .btn {
        margin-right: 3px;
        margin-bottom: 3px;
    }

    @media screen and (max-width: 767px) {
        #dataTables {
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

    <?php include("layout_alert.php"); ?>

    <?php if (isset($error_msg)): ?>
    <div class="row">
        <div class="col-lg-12">
            <div class="alert alert-danger">
                <strong>Error Database:</strong> <?php echo htmlspecialchars($error_msg); ?>
                <br><small>Pastikan tabel permintaan_karyawan dan permintaan_pengajuan sudah ada di database.</small>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa fa-list"></i> Daftar Permintaan Karyawan
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover" id="dataTables">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No. Dokumen</th>
                                    <th>Jabatan</th>
                                    <th>Jumlah</th>
                                    <th>Tanggal Dokumen</th>
                                    <th>Pengaju</th>
                                    <!-- <th>Status Manager</th> -->
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                if ($res):
                                while ($row = mysqli_fetch_assoc($res)): 
                                ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($row['no_dokumen']); ?></td>
                                        <td><?php echo htmlspecialchars($row['jabatan']); ?></td>
                                        <td><?php echo htmlspecialchars($row['jumlah_dibutuhkan']); ?> orang</td>
                                        <td><?php echo date('d-m-Y', strtotime($row['tanggal_dokumen'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['pengaju']); ?></td>
                                        <!-- <td>
                                            <?php 
                                            $status = $row['status'];
                                            if ($status == 'DIAJUKAN') {
                                                echo '<span class="status-badge status-pending">Menunggu</span>';
                                            } elseif ($status == 'DISETUJUI') {
                                                echo '<span class="status-badge status-approved">Disetujui</span>';
                                            } elseif ($status == 'DITOLAK') {
                                                echo '<span class="status-badge status-rejected">Ditolak</span>';
                                            }
                                            ?>
                                        </td> -->
                                        <td>
                                            <?php 
                                            $status_direktur = $row['status_app_direktur'];
                                            if (empty($status_direktur) && $status == 'DISETUJUI') {
                                                echo '<span class="status-badge status-waiting">Menunggu</span>';
                                            } elseif ($status_direktur == 'DISETUJUI') {
                                                echo '<span class="status-badge status-approved">Disetujui</span>';
                                            } elseif ($status_direktur == 'DITOLAK') {
                                                echo '<span class="status-badge status-rejected">Ditolak</span>';
                                            } elseif ($status == 'DITOLAK') {
                                                echo '<span class="text-muted">-</span>';
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group-action">
                                                <a href="permintaan_karyawan_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-xs btn-primary">
                                                    <i class="fa fa-eye"></i> Detail
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#dataTables').DataTable({
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
            language: {
                lengthMenu: "Tampilkan _MENU_ data per halaman",
                zeroRecords: "Tidak ada data",
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
            order: [[4, 'desc']],
            columnDefs: [
                { orderable: false, targets: [0, 7] },
                { className: "text-center", targets: [0, 3, 4, 7] }
            ]
        });
    });
</script>

<?php include 'layout_bottom.php'; ?>
