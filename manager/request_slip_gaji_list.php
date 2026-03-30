<?php
include("sess_check.php");

// Deskripsi halaman
$pagedesc = "Daftar Request Slip Gaji";
$menuparent = "gaji";
include("layout_top.php");

// Ambil NPP user yang login
$npp = isset($sess_mngid) ? $sess_mngid : '';

// Bulan dalam bahasa Indonesia
$bulan_list = [
    "01" => "Januari", "02" => "Februari", "03" => "Maret",
    "04" => "April", "05" => "Mei", "06" => "Juni",
    "07" => "Juli", "08" => "Agustus", "09" => "September",
    "10" => "Oktober", "11" => "November", "12" => "Desember"
];

// Ambil data request milik user yang login yang sudah disetujui (KEAMANAN PENTING!)
$sql = "SELECT * FROM request_slip_gaji 
        WHERE npp = ? AND status = 'approved' 
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

        <!-- Info Panel -->
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle fa-fw"></i> <strong>Informasi:</strong> 
                    Halaman ini menampilkan slip gaji yang sudah disetujui. 
                    Anda dapat export slip gaji dalam 2 format:
                    <ul style="margin-top: 5px; margin-bottom: 0;">
                        <li><strong>XLS (Excel)</strong> - Untuk print langsung dari Excel/LibreOffice</li>
                        <li><strong>PDF</strong> - Untuk print dari PDF viewer</li>
                    </ul>
                </div>
            </div>
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
                                        <!-- <th class="text-center" width="10%">Aksi</th> -->
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
                                            $status_badge = '<span class="label label-success">Disetujui</span>';
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td class="text-center"><strong><?php echo $periode; ?></strong></td>
                                        <td class="text-center"><?php echo $tanggal_request; ?></td>
                                        <td class="text-center"><?php echo $status_badge; ?></td>
                                        <td class="text-center"><?php echo $tanggal_approval; ?></td>
                                        <td class="text-center"><?php echo $row['approved_by'] ? $row['approved_by'] : '-'; ?></td>
                                        <!-- <td class="text-center">
                                            <a href="request_slip_gaji_xls.php?id=<?php echo $row['id_request']; ?>" 
                                               class="btn btn-xs btn-success" target="_blank" title="Export Slip Gaji ke Excel">
                                                <i class="fa fa-file-excel-o fa-fw"></i> XLS
                                            </a>
                                            <a href="request_slip_gaji_download.php?id=<?php echo $row['id_request']; ?>" 
                                               class="btn btn-xs btn-danger" target="_blank" title="Download Slip Gaji PDF">
                                                <i class="fa fa-file-pdf-o fa-fw"></i> PDF
                                            </a>
                                        </td> -->
                                    </tr>
                                    <?php 
                                        }
                                    } else {
                                    ?>
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <em>Belum ada slip gaji yang disetujui. Request Anda akan muncul di sini setelah disetujui oleh HR.</em>
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

<?php include("layout_bottom.php"); ?>
