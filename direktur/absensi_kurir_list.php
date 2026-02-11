<?php
// session check
include("sess_check.php");
$pagedesc = "Buku Harian Absensi Kurir";
$menuparent = "insentif";
include("layout_top.php");

// Get filter parameters
$filter_tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : '';
$filter_tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';
$filter_npp = isset($_GET['npp']) ? $_GET['npp'] : '';

// Load BONUS_FULL_HADIR from settings
$bonus_full_hadir_setting = 250000; // default
$rs_setting = mysqli_query($conn, "SELECT nominal_rp FROM pengaturan_insentif_kurir WHERE nama_variabel = 'BONUS_FULL_HADIR' LIMIT 1");
if ($rs_setting && $row_setting = mysqli_fetch_assoc($rs_setting)) {
    $bonus_full_hadir_setting = intval($row_setting['nominal_rp']);
}

// Build query: select records from absensi_kurir and join employee
$sql = "SELECT a.*, e.nama_emp, b.nama_bagian, e.cabang
        FROM absensi_kurir a
        LEFT JOIN employee e ON a.npp = e.npp
        LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
        WHERE 1=1";

if (!empty($filter_tanggal_awal)) {
    $sql .= " AND a.tanggal_absen >= '" . mysqli_real_escape_string($conn, $filter_tanggal_awal) . "'";
}
if (!empty($filter_tanggal_akhir)) {
    $sql .= " AND a.tanggal_absen <= '" . mysqli_real_escape_string($conn, $filter_tanggal_akhir) . "'";
}
if (!empty($filter_npp)) {
    $sql .= " AND a.npp LIKE '%" . mysqli_real_escape_string($conn, $filter_npp) . "%'";
}

$sql .= " ORDER BY a.tanggal_absen DESC, a.npp ASC LIMIT 500";

$query = mysqli_query($conn, $sql);
$total_rows = mysqli_num_rows($query);

// Calculate summary statistics
$total_hadir = 0;
$total_alpha = 0;
$total_cuti = 0;
$total_telat = 0;
$grand_total_makan = 0;
$grand_total_lembur = 0;
$grand_total_denda = 0;
$grand_total_insentif = 0;
$grand_total_bayar = 0;

mysqli_data_seek($query, 0); // Reset pointer
while ($row = mysqli_fetch_assoc($query)) {
    if ($row['is_hadir']) $total_hadir++;
    if (!$row['is_hadir'] && !$row['is_cuti']) $total_alpha++;
    if ($row['is_cuti']) $total_cuti++;
    if ($row['is_late']) $total_telat++;
    $grand_total_makan += floatval($row['uang_makan'] ?? 0);
    $grand_total_lembur += floatval($row['uang_lembur'] ?? 0);
    $grand_total_denda += floatval($row['denda_telat'] ?? 0);
    $grand_total_insentif += floatval($row['insentif_titik'] ?? 0);
    $grand_total_bayar += floatval($row['grand_total_harian'] ?? 0);
}
mysqli_data_seek($query, 0); // Reset pointer again for display
?>
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">
                        <i class="fa fa-calendar-check-o"></i> Buku Harian Absensi Kurir
                        <small>(Catatan Harian - Jejak Audit)</small>
                    </h1>
                </div>
            </div>

            <?php include("layout_alert.php"); ?>
            
            <!-- Summary Statistics Panel -->
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="panel panel-success">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-xs-3"><i class="fa fa-check-circle fa-3x"></i></div>
                                <div class="col-xs-9 text-right">
                                    <div class="huge"><?php echo $total_hadir; ?></div>
                                    <div>Hari Hadir</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="panel panel-danger">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-xs-3"><i class="fa fa-times-circle fa-3x"></i></div>
                                <div class="col-xs-9 text-right">
                                    <div class="huge"><?php echo $total_alpha; ?></div>
                                    <div>Hari Alpha</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="panel panel-warning">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-xs-3"><i class="fa fa-clock-o fa-3x"></i></div>
                                <div class="col-xs-9 text-right">
                                    <div class="huge"><?php echo $total_telat; ?></div>
                                    <div>Terlambat</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-xs-3"><i class="fa fa-calendar-times-o fa-3x"></i></div>
                                <div class="col-xs-9 text-right">
                                    <div class="huge"><?php echo $total_cuti; ?></div>
                                    <div>Hari Cuti</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
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
                                    <label>Tanggal Awal:</label>
                                    <input type="date" name="tanggal_awal" class="form-control" value="<?php echo htmlspecialchars($filter_tanggal_awal); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Akhir:</label>
                                    <input type="date" name="tanggal_akhir" class="form-control" value="<?php echo htmlspecialchars($filter_tanggal_akhir); ?>">
                                </div>
                                <div class="form-group">
                                    <label>NPP:</label>
                                    <input type="text" name="npp" class="form-control" placeholder="Cari NPP..." value="<?php echo htmlspecialchars($filter_npp); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search"></i> Filter
                                </button>
                                <a href="absensi_kurir_list.php" class="btn btn-default">
                                    <i class="fa fa-refresh"></i> Reset
                                </a>
                                <a href="insentif_kurir_upload.php" class="btn btn-success">
                                    <i class="fa fa-upload"></i> Upload Excel
                                </a>
                                <a href="insentif_kurir_list.php" class="btn btn-info">
                                    <i class="fa fa-bar-chart"></i> Lihat Rekap Bulanan
                                </a>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Data Table Panel -->
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <i class="fa fa-table"></i> Data Harian (Maksimal 500 baris) - Total: <?php echo $total_rows; ?> data
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover table-condensed" id="dataTables" style="font-size: 11px;">
                                    <thead>
                                        <tr>
                                            <th rowspan="2">No</th>
                                            <th rowspan="2">Tanggal</th>
                                            <th rowspan="2">NPP</th>
                                            <th rowspan="2">Nama</th>
                                            <th rowspan="2">Tugas</th>
                                            <th colspan="2" class="text-center bg-info">Jam Kerja</th>
                                            <th colspan="3" class="text-center bg-warning">Status</th>
                                            <th colspan="2" class="text-center bg-primary">Pencapaian</th>
                                            <th colspan="6" class="text-center bg-success">Komponen Finansial (Rp)</th>
                                            <th rowspan="2" class="bg-danger">Total Harian</th>
                                        </tr>
                                        <tr>
                                            <!-- Jam Kerja -->
                                            <th class="bg-info">Masuk</th>
                                            <th class="bg-info">Pulang</th>
                                            
                                            <!-- Status -->
                                            <th class="bg-warning">Hadir</th>
                                            <th class="bg-warning" title="Terlambat (menit)">Telat</th>
                                            <th class="bg-warning">Cuti</th>
                                            
                                            <!-- Titik -->
                                            <th class="bg-primary">Aktual</th>
                                            <th class="bg-primary">Target</th>
                                            
                                            <!-- Finansial -->
                                            <th class="bg-success" title="Insentif Penambahan Titik (dihitung bulanan, max 500rb/bulan)">Bonus Titik</th>
                                            <th class="bg-success" title="Bonus Full Hadir (dihitung bulanan, 250rb jika 0 alpha)">Bonus Full Hadir</th>
                                            <th class="bg-success" title="+ jika hadir, - jika alpha">Makan</th>
                                            <th class="bg-success">Lembur</th>
                                            <th class="bg-success" title="Potongan denda keterlambatan">Denda</th>
                                            <th class="bg-success" title="Menit Terlambat">Menit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        // Mapping hari ke bahasa Indonesia
                                        $hari_indo = array(
                                            'Sun' => 'Min', 'Mon' => 'Sen', 'Tue' => 'Sel', 
                                            'Wed' => 'Rab', 'Thu' => 'Kam', 'Fri' => 'Jum', 'Sat' => 'Sab'
                                        );
                                        
                                        while ($row = mysqli_fetch_assoc($query)) {
                                            // Status badges
                                            $status_cuti = $row['is_cuti'] ? '<span class="label label-info">Cuti</span>' : '-';

                                            if ($row['is_cuti']) {
                                                // For cuti rows, show '-' for hadir and telat to avoid confusion
                                                $status_hadir = '-';
                                                $telat_badge = '-';
                                            } else {
                                                $status_hadir = $row['is_hadir'] ? '<span class="label label-success">✓ Hadir</span>' : '<span class="label label-danger">✗ Tidak</span>';
                                                if ($row['is_late']) {
                                                    $telat_badge = '<span class="label label-warning">' . $row['menit_terlambat'] . ' menit</span>';
                                                } else {
                                                    $telat_badge = '<span class="label label-default">Tepat Waktu</span>';
                                                }
                                            }
                                            
                                            // Highlight rows
                                            $row_class = '';
                                            if ($row['is_cuti']) $row_class = 'info';
                                            elseif (!$row['is_hadir']) $row_class = 'danger';
                                            elseif ($row['is_late']) $row_class = 'warning';
                                            
                                            // Format tanggal dengan hari dalam bahasa Indonesia
                                            $hari_en = date('D', strtotime($row['tanggal_absen']));
                                            $hari_id = $hari_indo[$hari_en];
                                            $tanggal_formatted = date('d/m/Y', strtotime($row['tanggal_absen'])) . ' (' . $hari_id . ')';
                                        ?>
                                        <tr class="<?php echo $row_class; ?>">
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo $tanggal_formatted; ?></td>
                                            <td><?php echo htmlspecialchars($row['npp']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_emp'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['jenis_tugas'] ?? '-'); ?></td>
                                            
                                            <!-- Jam Kerja -->
                                            <td><?php echo $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-'; ?></td>
                                            <td><?php echo $row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : '-'; ?></td>
                                            
                                            <!-- Status -->
                                            <td class="text-center"><?php echo $status_hadir; ?></td>
                                            <td class="text-center"><?php echo $telat_badge; ?></td>
                                            <td class="text-center"><?php echo $status_cuti; ?></td>
                                            
                                            <!-- Titik -->
                                            <td class="text-right"><?php echo number_format($row['total_aktual_titik']); ?></td>
                                            <td class="text-right"><?php echo number_format($row['target_titik']); ?></td>
                                            
                                            <!-- Finansial -->
                                            <td class="text-right"><?php echo number_format($row['insentif_titik'] ?? 0); ?></td>
                                            <td class="text-right text-muted" title="Dihitung bulanan (Rp <?php echo number_format($bonus_full_hadir_setting); ?> jika 0 alpha)">
                                                <?php echo $row['is_hadir'] ? number_format($bonus_full_hadir_setting) : '0'; ?>
                                            </td>
                                            <td class="text-right <?php echo (($row['uang_makan'] ?? 0) < 0) ? 'text-danger' : ''; ?>">
                                                <?php echo number_format($row['uang_makan'] ?? 0); ?>
                                            </td>
                                            <td class="text-right"><?php echo number_format($row['uang_lembur'] ?? 0); ?></td>
                                            <td class="text-right text-danger"><?php echo number_format($row['denda_telat'] ?? 0); ?></td>
                                            <td class="text-center"><?php echo $row['menit_terlambat'] ?? 0; ?></td>
                                            
                                            <!-- Total -->
                                            <td class="text-right"><strong><?php echo number_format($row['grand_total_harian'] ?? 0); ?></strong></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="info">
                                            <th colspan="12" class="text-right"><strong>TOTAL KESELURUHAN:</strong></th>
                                            <th class="text-right"><strong>Rp <?php echo number_format($grand_total_insentif); ?></strong></th>
                                            <th class="text-right text-muted"><strong>-</strong></th>
                                            <th class="text-right"><strong>Rp <?php echo number_format($grand_total_makan); ?></strong></th>
                                            <th class="text-right"><strong>Rp <?php echo number_format($grand_total_lembur); ?></strong></th>
                                            <th class="text-right text-danger"><strong>Rp <?php echo number_format($grand_total_denda); ?></strong></th>
                                            <th></th>
                                            <th class="text-right bg-warning"><strong>Rp <?php echo number_format($grand_total_bayar); ?></strong></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php include("layout_bottom.php"); ?>
