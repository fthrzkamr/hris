<?php
include("sess_check.php");

// Deskripsi halaman
$pagedesc = "Daftar Request Slip Gaji";
$menuparent = "gaji";
include("layout_top.php");

// Ambil NPP user yang login
$npp = $_SESSION['manager'];

// Bulan dalam bahasa Indonesia
$bulan_list = [
    "01" => "Januari", "02" => "Februari", "03" => "Maret",
    "04" => "April", "05" => "Mei", "06" => "Juni",
    "07" => "Juli", "08" => "Agustus", "09" => "September",
    "10" => "Oktober", "11" => "November", "12" => "Desember"
];

// Ambil data request milik user yang login saja (KEAMANAN PENTING!)
$sql = "SELECT * FROM request_slip_gaji 
        WHERE npp = ? 
        ORDER BY tanggal_request DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $npp);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">

        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Daftar Request Slip Gaji</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <!-- Action Buttons -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <a href="request_slip_gaji.php" class="btn btn-primary">
                            <i class="fa fa-plus fa-fw"></i> Request Slip Gaji Baru
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Request -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-list fa-fw"></i> Riwayat Request Slip Gaji
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="dataTables">
                                <thead>
                                    <tr>
                                        <th class="text-center" width="5%">No</th>
                                        <th class="text-center">Periode</th>
                                        <th class="text-center">Tanggal Request</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Tanggal Approval</th>
                                        <th class="text-center">Disetujui Oleh</th>
                                        <!-- <th class="text-center" width="15%">Aksi</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if (mysqli_num_rows($result) > 0) {
                                        $no = 1;
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $periode = $bulan_list[$row['bulan']] . " " . $row['tahun'];
                                            $tanggal_request = date('d/m/Y H:i', strtotime($row['tanggal_request']));
                                            $tanggal_approval = $row['tanggal_approval'] ? date('d/m/Y H:i', strtotime($row['tanggal_approval'])) : '-';
                                            
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
                                        <td class="text-center"><strong><?php echo $periode; ?></strong></td>
                                        <td class="text-center"><?php echo $tanggal_request; ?></td>
                                        <td class="text-center"><?php echo $status_badge; ?></td>
                                        <td class="text-center"><?php echo $tanggal_approval; ?></td>
                                        <td class="text-center"><?php echo $row['approved_by'] ? $row['approved_by'] : '-'; ?></td>
                                        <!-- <td class="text-center">
                                            <?php if ($row['status'] == 'approved') { ?>
                                                <a href="request_slip_gaji_download.php?id=<?php echo $row['id_request']; ?>" 
                                                   class="btn btn-sm btn-success" target="_blank">
                                                    <i class="fa fa-download fa-fw"></i> Download Slip
                                                </a>
                                            <?php } elseif ($row['status'] == 'rejected') { ?>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="showRejectReason('<?php echo addslashes($row['keterangan_reject']); ?>')">
                                                    <i class="fa fa-info-circle fa-fw"></i> Lihat Alasan
                                                </button>
                                            <?php } else { ?>
                                                <span class="text-muted"><i>Menunggu approval</i></span>
                                            <?php } ?>
                                        </td> -->
                                    </tr>
                                    <?php 
                                        }
                                    } else {
                                    ?>
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <em>Belum ada request slip gaji. Silakan buat request baru.</em>
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

<!-- Modal untuk menampilkan alasan reject -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-info-circle"></i> Alasan Penolakan</h4>
            </div>
            <div class="modal-body">
                <p id="reject-reason-text"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function showRejectReason(reason) {
    document.getElementById('reject-reason-text').innerText = reason || 'Tidak ada keterangan.';
    $('#rejectModal').modal('show');
}
</script>

<?php include("layout_bottom.php"); ?>
