<?php
include("sess_check.php");

// Deskripsi halaman
$pagedesc = "Daftar Slip Gaji Approved";
$menuparent = "gaji";
include("layout_top.php");

// Bulan dalam bahasa Indonesia
$bulan_list = [
    "01" => "Januari", "02" => "Februari", "03" => "Maret",
    "04" => "April", "05" => "Mei", "06" => "Juni",
    "07" => "Juli", "08" => "Agustus", "09" => "September",
    "10" => "Oktober", "11" => "November", "12" => "Desember"
];

// Ambil semua request slip gaji yang sudah approved
$sql = "SELECT r.*, e.nama_emp, e.nama_bagian, b.nama_bagian as nama_bagian_join, e.cabang 
        FROM request_slip_gaji r
        LEFT JOIN employee e ON r.npp = e.npp
        LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
        WHERE r.status = 'approved'
        ORDER BY r.tanggal_approval DESC";
$result = mysqli_query($conn, $sql);
?>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">

        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Daftar Slip Gaji (Approved)</h1>
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
                    Halaman ini menampilkan semua slip gaji yang sudah disetujui. 
                    Anda dapat export slip gaji dalam 2 format:
                    <ul style="margin-top: 5px; margin-bottom: 0;">
                        <li><strong>XLS (Excel)</strong> - Untuk print langsung dari Excel/LibreOffice</li>
                        <li><strong>PDF</strong> - Untuk print dari PDF viewer</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Tabel Slip Gaji -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-list fa-fw"></i> Daftar Slip Gaji yang Sudah Disetujui
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
                                        <th class="text-center">Tanggal Approval</th>
                                        <th class="text-center">Disetujui Oleh</th>
                                        <th class="text-center" width="10%">Aksi</th>
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
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td class="text-center"><?php echo $row['npp']; ?></td>
                                        <td><?php echo $row['nama_emp']; ?></td>
                                        <td class="text-center"><?php echo $row['nama_bagian_join'] ?: '-'; ?></td>
                                        <td class="text-center"><?php echo $row['cabang'] ?: '-'; ?></td>
                                        <td class="text-center"><strong><?php echo $periode; ?></strong></td>
                                        <td class="text-center"><?php echo $tanggal_request; ?></td>
                                        <td class="text-center"><?php echo $tanggal_approval; ?></td>
                                        <td class="text-center"><?php echo $row['approved_by'] ? $row['approved_by'] : '-'; ?></td>
                                        <td class="text-center">
                                            <a href="request_slip_gaji_xls.php?id=<?php echo $row['id_request']; ?>" 
                                               class="btn btn-sm btn-success" target="_blank" title="Export Slip Gaji ke Excel">
                                                <i class="fa fa-file-excel-o fa-fw"></i> XLS
                                            </a>
                                            <a href="request_slip_gaji_download.php?id=<?php echo $row['id_request']; ?>" 
                                               class="btn btn-sm btn-danger" target="_blank" title="Download Slip Gaji PDF">
                                                <i class="fa fa-file-pdf-o fa-fw"></i> PDF
                                            </a>
                                        </td>
                                    </tr>
                                    <?php 
                                        }
                                    } else {
                                    ?>
                                    <tr>
                                        <td colspan="10" class="text-center">
                                            <em>Belum ada slip gaji yang disetujui.</em>
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
