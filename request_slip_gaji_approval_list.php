<?php
include("sess_check.php");

// Deskripsi halaman
$pagedesc = "Approval Request Slip Gaji";
include("layout_top.php");

// Bulan dalam bahasa Indonesia
$bulan_list = [
    "01" => "Januari",
    "02" => "Februari",
    "03" => "Maret",
    "04" => "April",
    "05" => "Mei",
    "06" => "Juni",
    "07" => "Juli",
    "08" => "Agustus",
    "09" => "September",
    "10" => "Oktober",
    "11" => "November",
    "12" => "Desember"
];

// Filter status
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'pending';

// Ambil semua request slip gaji
$sql = "SELECT r.*, e.nama_bagian, b.nama_bagian as nama_bagian_join, e.cabang 
    FROM request_slip_gaji r
    LEFT JOIN employee e ON r.npp = e.npp
    LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
    WHERE r.status = ?
    ORDER BY r.tanggal_request DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $filter_status);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Count pending requests
$sql_count = "SELECT COUNT(*) as total FROM request_slip_gaji WHERE status = 'pending'";
$result_count = mysqli_query($conn, $sql_count);
$count_pending = mysqli_fetch_assoc($result_count)['total'];
?>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">

        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">
                    Approval Request Slip Gaji
                    <?php if ($count_pending > 0) { ?>
                        <span class="badge" style="background-color: #d9534f;"><?php echo $count_pending; ?></span>
                    <?php } ?>
                </h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <!-- Info Panel -->
        <!-- <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle fa-fw"></i> <strong>Perhatian:</strong> Pastikan data gaji karyawan di sistem sudah tersedia sebelum menyetujui request. Karyawan hanya dapat mendownload slip gaji setelah data gaji tersedia di system.
                </div>
            </div>
        </div> -->

        <!-- Filter Status -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="btn-group" role="group">
                            <a href="?status=pending"
                                class="btn btn-<?php echo ($filter_status == 'pending') ? 'warning' : 'default'; ?>">
                                <i class="fa fa-clock-o fa-fw"></i> Pending (<?php echo $count_pending; ?>)
                            </a>
                            <a href="?status=approved"
                                class="btn btn-<?php echo ($filter_status == 'approved') ? 'success' : 'default'; ?>">
                                <i class="fa fa-check fa-fw"></i> Disetujui
                            </a>
                            <a href="?status=rejected"
                                class="btn btn-<?php echo ($filter_status == 'rejected') ? 'danger' : 'default'; ?>">
                                <i class="fa fa-times fa-fw"></i> Ditolak
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Request -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-list fa-fw"></i> Daftar Request Slip Gaji - Status:
                        <strong><?php echo ucfirst($filter_status); ?></strong>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="dataTables">
                                <thead>
                                    <tr>
                                        <th class="text-center" width="5%">No</th>
                                        <th class="text-center">NPP</th>
                                        <th class="text-center">Nama Karyawan</th>
                                        <th class="text-center">Bagian</th>
                                        <th class="text-center">Cabang</th>
                                        <th class="text-center">Periode</th>
                                        <th class="text-center">Tanggal Request</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center" width="15%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (mysqli_num_rows($result) > 0) {
                                        $no = 1;
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $periode = $bulan_list[$row['bulan']] . " " . $row['tahun'];
                                            $tanggal_request = date('d/m/Y H:i', strtotime($row['tanggal_request']));

                                            // Status badge
                                            if ($row['status'] == 'approved') {
                                                $status_badge = '<span class="label label-success">Disetujui</span>';
                                            } elseif ($row['status'] == 'rejected') {
                                                $status_badge = '<span class="label label-danger">Ditolak</span>';
                                            } else {
                                                $status_badge = '<span class="label label-warning">Pending</span>';
                                            }
                                            ?>
                                            <tr>
                                                <td class="text-center"><?php echo $no++; ?></td>
                                                <td class="text-center"><?php echo $row['npp']; ?></td>
                                                <td><?php echo $row['nama_karyawan']; ?></td>
                                                <td class="text-center"><?php echo $row['nama_bagian_join'] ?: '-'; ?></td>
                                                <td class="text-center"><?php echo $row['cabang'] ?: '-'; ?></td>
                                                <td class="text-center"><strong><?php echo $periode; ?></strong></td>
                                                <td class="text-center"><?php echo $tanggal_request; ?></td>
                                                <td class="text-center"><?php echo $status_badge; ?></td>
                                                <td class="text-center">
                                                    <?php if ($row['status'] == 'pending') { ?>
                                                        <button type="button" class="btn btn-xs btn-success"
                                                            onclick="approveRequest(<?php echo $row['id_request']; ?>)">
                                                            <i class="fa fa-check fa-fw"></i> Approve
                                                        </button>
                                                        <button type="button" class="btn btn-xs btn-danger"
                                                            onclick="rejectRequest(<?php echo $row['id_request']; ?>, '<?php echo $row['npp']; ?>', '<?php echo $row['nama_karyawan']; ?>', '<?php echo $periode; ?>')">
                                                            <i class="fa fa-times fa-fw"></i> Tolak
                                                        </button>
                                                    <?php } elseif ($row['status'] == 'approved') { ?>
                                                        <?php /*
                                                        <a href="request_slip_gaji_xls.php?id=<?php echo $row['id_request']; ?>"
                                                            class="btn btn-xs btn-success" target="_blank"
                                                            title="Export Slip Gaji ke Excel">
                                                            <i class="fa fa-file-excel-o fa-fw"></i> XLS
                                                        </a>
                                                        <a href="request_slip_gaji_download.php?id=<?php echo $row['id_request']; ?>"
                                                            class="btn btn-xs btn-danger" target="_blank"
                                                            title="Download Slip Gaji PDF">
                                                            <i class="fa fa-file-pdf-o fa-fw"></i> PDF
                                                        </a>
                                                        <br>
                                                        */ ?>
                                                        <small class="text-muted">Disetujui:
                                                            <?php echo $row['approved_by']; ?></small>
                                                    <?php } else { ?>
                                                        <span class="text-muted"><em>Ditolak</em></span>
                                                        <?php if (!empty($row['keterangan_reject'])) { ?>
                                                            <br>
                                                            <button type="button" class="btn btn-xs btn-default"
                                                                onclick="alert('<?php echo addslashes($row['keterangan_reject']); ?>')">
                                                                <i class="fa fa-info-circle"></i> Lihat Alasan
                                                            </button>
                                                        <?php } ?>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php
                                        }
                                    } else {
                                        ?>
                                        <tr>
                                            <td colspan="9" class="text-center">
                                                <em>Tidak ada data request dengan status <?php echo $filter_status; ?>.</em>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.container-fluid -->
</div><!-- /#page-wrapper -->

<!-- Hidden form for approve action -->
<form method="post" action="request_slip_gaji_approval.php" id="approveForm" style="display:none;">
    <input type="hidden" name="id_request" id="approve_id">
    <input type="hidden" name="action" value="approve">
</form>

<!-- Modal Reject -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="request_slip_gaji_approval.php">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-times-circle"></i> Tolak Request Slip Gaji</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_request" id="reject_id">
                    <input type="hidden" name="action" value="reject">
                    <p>Apakah Anda yakin ingin menolak request slip gaji ini?</p>
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">NPP</th>
                            <td id="reject_npp"></td>
                        </tr>
                        <tr>
                            <th>Nama Karyawan</th>
                            <td id="reject_nama"></td>
                        </tr>
                        <tr>
                            <th>Periode</th>
                            <td id="reject_periode"></td>
                        </tr>
                    </table>
                    <div class="form-group">
                        <label>Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="keterangan_reject" rows="3" required
                            placeholder="Masukkan alasan penolakan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-times fa-fw"></i> Tolak Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function approveRequest(id) {
        if (!confirm('Setujui request slip gaji ini?')) {
            return false;
        }

        document.getElementById('approve_id').value = id;
        document.getElementById('approveForm').submit();
    }

    function rejectRequest(id, npp, nama, periode) {
        document.getElementById('reject_id').value = id;
        document.getElementById('reject_npp').innerText = npp;
        document.getElementById('reject_nama').innerText = nama;
        document.getElementById('reject_periode').innerText = periode;
        $('#rejectModal').modal('show');
    }
</script>

<?php include("layout_bottom.php"); ?>
