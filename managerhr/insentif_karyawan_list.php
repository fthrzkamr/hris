<?php
// session check
include('sess_check.php');
$pagedesc = 'Rekap Insentif Karyawan Bulanan';
$menuparent = 'insentif';
include('layout_top.php');

// Get filter parameters
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$filter_npp = isset($_GET['npp']) ? $_GET['npp'] : '';

// Build query - monthly aggregation
$sql = "SELECT 
            tik.npp,
            e.nama_emp,
            b.nama_bagian,
            DATE_FORMAT(tik.periode, '%Y-%m') AS bulan,
            tik.total_titik,
            tik.target_titik,
            tik.bonus_insentif,
            tik.denda_telat,
            tik.potongan_makan,
            tik.uang_lembur,
            tik.jumlah_dibayarkan,
            tik.updated_at
        FROM transaksi_insentif_kurir tik
        LEFT JOIN employee e ON tik.npp = e.npp
        LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
        WHERE 1=1";

if (!empty($filter_bulan)) {
    $sql .= " AND DATE_FORMAT(tik.periode, '%Y-%m') = '" . mysqli_real_escape_string($conn, $filter_bulan) . "'";
}

if (!empty($filter_npp)) {
    $sql .= " AND tik.npp LIKE '%" . mysqli_real_escape_string($conn, $filter_npp) . "%'";
}

$sql .= " ORDER BY tik.periode DESC, e.nama_emp ASC";

$query = mysqli_query($conn, $sql);
?>
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Rekap Insentif Karyawan Bulanan</h1>
                </div>
            </div>

            <?php 
            include('layout_alert.php'); 
            
            if (isset($_SESSION['alert_details'])) {
                echo '<div class="alert alert-warning alert-dismissible">';
                echo '<button type="button" class="close" data-dismiss="alert">&times;</button>';
                echo '<strong>Detail:</strong><br>';
                echo $_SESSION['alert_details'];
                echo '</div>';
                unset($_SESSION['alert_details']);
            }
            ?>
            
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <i class="fa fa-filter"></i> Filter Data
                        </div>
                        <div class="panel-body">
                            <form method="GET" action="" class="form-inline">
                                <div class="form-group">
                                    <label>Bulan:</label>
                                    <input type="month" name="bulan" class="form-control" value="<?php echo htmlspecialchars($filter_bulan); ?>">
                                </div>
                                    <label>NPP:</label>
                                    <input type="text" name="npp" class="form-control" placeholder="Cari NPP..." value="<?php echo htmlspecialchars($filter_npp); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search"></i> Filter
                                </button>
                                <a href="insentif_karyawan_list.php" class="btn btn-default">
                                    <i class="fa fa-refresh"></i> Reset
                                </a>
                                <a href="insentif_karyawan_upload.php" class="btn btn-success">
                                    <i class="fa fa-upload"></i> Upload Excel
                                </a>
                            </form>
                        </div>
                    </div>
                    
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <i class="fa fa-table"></i> Rekap Insentif Karyawan (Bulanan)
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="dataTables">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>NPP</th>
                                            <th>Nama</th>
                                            <th>Bagian</th>
                                            <th>Periode</th>
                                            <th>Total Titik</th>
                                            <th>Target</th>
                                            <th>Bonus Insentif</th>
                                            <th>Uang Lembur</th>
                                            <th>Denda</th>
                                            <th>Total Dibayar</th>
                                            <th>Update</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        while ($row = mysqli_fetch_array($query)) {
                                            $bulan_formatted = date('M Y', strtotime($row['bulan'] . '-01'));
                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($row['npp']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_emp'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_bagian'] ?? '-'); ?></td>
                                            <td><?php echo $bulan_formatted; ?></td>
                                            <td class="text-right"><?php echo number_format($row['total_titik'], 0); ?></td>
                                            <td class="text-right"><?php echo number_format($row['target_titik'], 0); ?></td>
                                            <td class="text-right">Rp <?php echo number_format($row['bonus_insentif'], 0, ',', '.'); ?></td>
                                            <td class="text-right">Rp <?php echo number_format($row['uang_lembur'], 0, ',', '.'); ?></td>
                                            <td class="text-right">Rp <?php echo number_format($row['denda_telat'], 0, ',', '.'); ?></td>
                                            <td class="text-right"><strong style="color: #0066cc;">Rp <?php echo number_format($row['jumlah_dibayarkan'], 0, ',', '.'); ?></strong></td>
                                            <td><?php echo date('d-m-Y H:i', strtotime($row['updated_at'])); ?></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php include('layout_bottom.php'); ?>
