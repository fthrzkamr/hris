<?php

// session check
include("sess_check.php");
$pagedesc = "Rekapitulasi Bulanan Insentif Kurir";
$menuparent = "insentif";
include("layout_top.php");

// Get filter parameters
$filter_periode = isset($_GET['periode']) ? $_GET['periode'] : '';
$filter_npp = isset($_GET['npp']) ? $_GET['npp'] : '';

// Build query: select records from transaksi_insentif_kurir and join employee for metadata
$sql = "SELECT t.*, e.nama_emp, b.nama_bagian, e.cabang
        FROM transaksi_insentif_kurir t
        LEFT JOIN employee e ON t.npp = e.npp
        LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
        WHERE 1=1";

$filter_periode_escaped = mysqli_real_escape_string($conn, $filter_periode);
if (!empty($filter_periode_escaped)) {
    $sql .= " AND t.periode = '" . $filter_periode_escaped . "'";
}

if (!empty($filter_npp)) {
    $sql .= " AND t.npp LIKE '%" . mysqli_real_escape_string($conn, $filter_npp) . "%'";
}

$sql .= " ORDER BY t.periode DESC, t.npp ASC";

$query = mysqli_query($conn, $sql);
?>
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">
                        <i class="fa fa-calculator"></i> Rekapitulasi Bulanan Insentif Kurir
                        <small>(Monthly Payslip - Aggregated)</small>
                    </h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

            <?php 
            include("layout_alert.php"); 
            
            // Display error/details if any
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
                                <a href="absensi_kurir_list.php" class="btn btn-warning">
                                    <i class="fa fa-book"></i> Lihat Buku Harian
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
                                            <th rowspan="2">No</th>
                                            <th rowspan="2">NPP</th>
                                            <th rowspan="2">Nama Karyawan</th>
                                            <th rowspan="2">Bagian</th>
                                            <th rowspan="2">Cabang</th>
                                            <th rowspan="2">Periode</th>
                                            <th colspan="4" class="text-center">Performa</th>
                                            <th colspan="5" class="text-center">Pembayaran</th>
                                            <th rowspan="2">Status</th>
                                            <th rowspan="2">Tgl Update</th>
                                            <th rowspan="2">Aksi</th>
                                        </tr>
                                        <tr>
                                            <th>Total Titik</th>
                                            <th>Target Titik</th>
                                            <th>Pencapaian (%)</th>
                                            <th>Kelebihan</th>

                                            <th title="Bonus dari kelebihan titik (max 500rb/bulan)">Bonus Titik</th>
                                            <th title="Bonus full kehadiran (250rb jika 0 alpha)">Bonus Full Hadir</th>
                                            <th>Uang Lembur</th>
                                            <th>Denda Telat</th>
                                            <th title="Positif=Allowance (Hadir), Negatif=Penalty (Alpha)">Uang Makan</th>
                                            <th>Total Dibayarkan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        while ($row = mysqli_fetch_assoc($query)) {
                                            $no_titik = intval($row['total_titik']);
                                            $target_titik = intval($row['target_titik']);
                                            $kelebihan_titik = max(0, $no_titik - $target_titik);
                                            $persentase = ($target_titik > 0) ? (($no_titik / $target_titik) * 100) : 0;
                                            $status = ($no_titik >= $target_titik) ? 'Tercapai' : 'Tidak Tercapai';
                                            $status_class = ($status == 'Tercapai') ? 'success' : 'danger';
                                            // Format periode as "Bulan Tahun" (e.g., "Nov 2025")
                                            $periode_obj = DateTime::createFromFormat('Y-m', $row['periode']);
                                            $periode_formatted = $periode_obj ? $periode_obj->format('M Y') : $row['periode'];
                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($row['npp']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_emp'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_bagian'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['cabang'] ?? '-'); ?></td>
                                            <td><?php echo $periode_formatted; ?></td>
                                            <td class="text-right"><?php echo number_format($no_titik); ?></td>
                                            <td class="text-right"><?php echo number_format($target_titik); ?></td>
                                            <td class="text-right"><?php echo number_format($persentase,2); ?>%</td>
                                            <td class="text-right"><?php echo number_format($kelebihan_titik); ?></td>
                                            <td class="text-right"><?php echo number_format($row['bonus_insentif_titik'] ?? 0); ?></td>
                                            <td class="text-right"><?php echo number_format($row['bonus_insentif_full_masuk'] ?? 0); ?></td>
                                            <td class="text-right"><?php echo number_format($row['uang_lembur'] ?? 0); ?></td>
                                            <td class="text-right"><?php echo number_format($row['denda_telat'] ?? 0); ?></td>
                                            <td class="text-right"><?php echo number_format($row['potongan_makan'] ?? 0); ?></td>
                                            <td class="text-right"><?php echo number_format($row['jumlah_dibayarkan'] ?? 0); ?></td>
                                            <td class="text-center"><span class="label label-<?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                                            <td><?php echo date('d-m-Y H:i', strtotime($row['updated_at'])); ?></td>
                                            <td>
                                                <a href="insentif_kurir_update.php?id=<?php echo intval($row['id']); ?>" class="btn btn-xs btn-warning">Edit</a>
                                            </td>
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
