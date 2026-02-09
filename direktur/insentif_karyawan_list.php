<?php
// session check
include("sess_check.php");
$pagedesc = "Daftar Absensi Karyawan";
$menuparent = "insentif";
include("layout_top.php");

// Get filter parameters
$filter_tanggal_dari = isset($_GET['tanggal_dari']) ? $_GET['tanggal_dari'] : '';
$filter_tanggal_sampai = isset($_GET['tanggal_sampai']) ? $_GET['tanggal_sampai'] : '';
$filter_npp = isset($_GET['npp']) ? $_GET['npp'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$sql = "SELECT 
            ak.*,
            e.nama_emp,
            b.nama_bagian
        FROM absensi_karyawan ak
        LEFT JOIN employee e ON ak.npp = e.npp
        LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
        WHERE 1=1";

if (!empty($filter_tanggal_dari)) {
    $sql .= " AND ak.tanggal >= '" . mysqli_real_escape_string($conn, $filter_tanggal_dari) . "'";
}

if (!empty($filter_tanggal_sampai)) {
    $sql .= " AND ak.tanggal <= '" . mysqli_real_escape_string($conn, $filter_tanggal_sampai) . "'";
}

if (!empty($filter_npp)) {
    $sql .= " AND ak.npp LIKE '%" . mysqli_real_escape_string($conn, $filter_npp) . "%'";
}

if (!empty($filter_status)) {
    $sql .= " AND ak.status_absensi = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}

$sql .= " ORDER BY ak.tanggal DESC, ak.npp ASC";

$query = mysqli_query($conn, $sql);
?>
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Daftar Absensi Karyawan</h1>
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
                                    <label>Dari Tanggal:</label>
                                    <input type="date" name="tanggal_dari" class="form-control" value="<?php echo htmlspecialchars($filter_tanggal_dari); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Sampai Tanggal:</label>
                                    <input type="date" name="tanggal_sampai" class="form-control" value="<?php echo htmlspecialchars($filter_tanggal_sampai); ?>">
                                </div>
                                <div class="form-group">
                                    <label>NPP:</label>
                                    <input type="text" name="npp" class="form-control" placeholder="Cari NPP..." value="<?php echo htmlspecialchars($filter_npp); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Status:</label>
                                    <select name="status" class="form-control">
                                        <option value="">Semua</option>
                                        <option value="Hadir" <?php echo ($filter_status == 'Hadir') ? 'selected' : ''; ?>>Hadir</option>
                                        <option value="Terlambat" <?php echo ($filter_status == 'Terlambat') ? 'selected' : ''; ?>>Terlambat</option>
                                        <option value="Tidak Hadir" <?php echo ($filter_status == 'Tidak Hadir') ? 'selected' : ''; ?>>Tidak Hadir</option>
                                        <option value="Pulang Awal" <?php echo ($filter_status == 'Pulang Awal') ? 'selected' : ''; ?>>Pulang Awal</option>
                                    </select>
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
                    
                    <!-- Data Table Panel -->
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <i class="fa fa-table"></i> Data Absensi Karyawan
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
                                            <th>Tanggal</th>
                                            <th>Jam Masuk</th>
                                            <th>Jam Pulang</th>
                                            <th>Durasi Kerja</th>
                                            <th>Status</th>
                                            <th>Tgl Update</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        while ($row = mysqli_fetch_array($query)) {
                                            // Set label class based on status
                                            $status_class = 'default';
                                            switch($row['status_absensi']) {
                                                case 'Hadir':
                                                    $status_class = 'success';
                                                    break;
                                                case 'Terlambat':
                                                    $status_class = 'warning';
                                                    break;
                                                case 'Tidak Hadir':
                                                    $status_class = 'danger';
                                                    break;
                                                case 'Pulang Awal':
                                                    $status_class = 'info';
                                                    break;
                                            }
                                            
                                            $tanggal_formatted = date('d/m/Y', strtotime($row['tanggal']));
                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($row['npp']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_emp'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_bagian'] ?? '-'); ?></td>
                                            <td><?php echo $tanggal_formatted; ?></td>
                                            <td class="text-center">
                                                <?php echo $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-'; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php echo $row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : '-'; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php 
                                                if ($row['durasi_kerja']) {
                                                    $durasi = explode(':', $row['durasi_kerja']);
                                                    echo $durasi[0] . ' jam ' . $durasi[1] . ' menit';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="label label-<?php echo $status_class; ?>">
                                                    <?php echo $row['status_absensi']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($row['updated_at'])); ?></td>
                                            <td class="text-center">
                                                <?php
                                                // Show Edit button for entries that may need manual correction
                                                $needs_edit = false;
                                                if (empty($row['jam_pulang']) || $row['jam_pulang'] == '00:00:00' || $row['jam_pulang'] == '16:00:00' || $row['status_absensi'] == 'Pulang Awal') {
                                                    $needs_edit = true;
                                                }

                                                if ($needs_edit) {
                                                    echo '<a href="insentif_karyawan_update.php?id=' . $row['id'] . '" class="btn btn-xs btn-warning"><i class="fa fa-edit"></i> Edit</a>';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
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
