<?php
// session check
include("sess_check.php");
$pagedesc = "Daftar Insentif Kurir";
$menuparent = "insentif";
include("layout_top.php");

// Get filter parameters
$filter_periode = isset($_GET['periode']) ? $_GET['periode'] : '';
$filter_npp = isset($_GET['npp']) ? $_GET['npp'] : '';

// Build query
$sql = "SELECT 
            ik.*,
            e.nama_emp,
            b.nama_bagian
        FROM insentif_kurir ik
        LEFT JOIN employee e ON ik.npp = e.npp
        LEFT JOIN bagian b ON e.nama_bagian = b.id
        WHERE 1=1";

if (!empty($filter_periode)) {
    $sql .= " AND DATE_FORMAT(ik.periode, '%Y-%m') = '" . mysqli_real_escape_string($conn, $filter_periode) . "'";
}

if (!empty($filter_npp)) {
    $sql .= " AND ik.npp LIKE '%" . mysqli_real_escape_string($conn, $filter_npp) . "%'";
}

$sql .= " ORDER BY ik.periode DESC, ik.npp ASC";

$query = mysqli_query($conn, $sql);
?>
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Daftar Insentif Kurir</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->

            <?php 
            include("layout_alert.php"); 
            
            // Display error details if any
            if (isset($_SESSION['alert_details'])) {
                echo '<div class="alert alert-warning alert-dismissible">';
                echo '<button type="button" class="close" data-dismiss="alert">&times;</button>';
                echo '<strong>Detail Error:</strong><br>';
                echo $_SESSION['alert_details'];
                echo '</div>';
                unset($_SESSION['alert_details']);
            }
            ?>
            
            <div class="row">
                <div class="col-lg-12">
                    <!-- Filter Panel -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <i class="fa fa-filter"></i> Filter Data
                        </div>
                        <div class="panel-body">
                            <form method="GET" action="" class="form-inline">
                                <div class="form-group">
                                    <label>Periode:</label>
                                    <input type="month" name="periode" class="form-control" value="<?php echo htmlspecialchars($filter_periode); ?>">
                                </div>
                                <div class="form-group">
                                    <label>NPP:</label>
                                    <input type="text" name="npp" class="form-control" placeholder="Cari NPP..." value="<?php echo htmlspecialchars($filter_npp); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search"></i> Filter
                                </button>
                                <a href="insentif_kurir_list.php" class="btn btn-default">
                                    <i class="fa fa-refresh"></i> Reset
                                </a>
                                <a href="insentif_kurir_upload.php" class="btn btn-success">
                                    <i class="fa fa-upload"></i> Upload Excel
                                </a>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Data Table Panel -->
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <i class="fa fa-table"></i> Data Insentif Kurir
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="dataTables">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>NPP</th>
                                            <th>Nama Karyawan</th>
                                            <th>Bagian</th>
                                            <th>Periode</th>
                                            <th>Total Titik</th>
                                            <th>Target Titik</th>
                                            <th>Pencapaian (%)</th>
                                            <th>Status</th>
                                            <th>Tgl Update</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        while ($row = mysqli_fetch_array($query)) {
                                            $status_class = ($row['status_target'] == 'Tercapai') ? 'success' : 'danger';
                                            $periode_formatted = date('F Y', strtotime($row['periode']));
                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($row['npp']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_emp'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_bagian'] ?? '-'); ?></td>
                                            <td><?php echo $periode_formatted; ?></td>
                                            <td class="text-right"><?php echo number_format($row['total_titik']); ?></td>
                                            <td class="text-right"><?php echo number_format($row['target_titik']); ?></td>
                                            <td class="text-right">
                                                <span class="label label-<?php echo $status_class; ?>">
                                                    <?php echo number_format($row['persentase_pencapaian'], 2); ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <span class="label label-<?php echo $status_class; ?>">
                                                    <?php echo $row['status_target']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($row['updated_at'])); ?></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- /.table-responsive -->
                        </div>
                        <!-- /.panel-body -->
                    </div>
                    <!-- /.panel -->
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /#page-wrapper -->
<?php include("layout_bottom.php"); ?>
