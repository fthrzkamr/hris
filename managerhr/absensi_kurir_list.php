<?php
// session check
include("sess_check.php");
$pagedesc = "Absensi Kurir";
$menuparent = "insentif";
include("layout_top.php");

// Get filter parameters
$filter_tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : '';
$filter_tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';
$filter_npp = isset($_GET['npp']) ? $_GET['npp'] : '';

// Pagination settings
$records_per_page = 25;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Load BONUS_FULL_HADIR from settings
$bonus_full_hadir_setting = 250000; // default
$rs_setting = mysqli_query($conn, "SELECT nominal_rp FROM pengaturan_insentif_kurir WHERE nama_variabel = 'BONUS_FULL_HADIR' LIMIT 1");
if ($rs_setting && $row_setting = mysqli_fetch_assoc($rs_setting)) {
    $bonus_full_hadir_setting = intval($row_setting['nominal_rp']);
}

// Build query: select records from absensi_kurir and join employee
$sql_base = "FROM absensi_kurir a
        LEFT JOIN employee e ON a.npp = e.npp
        LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
        WHERE 1=1";

if (!empty($filter_tanggal_awal)) {
    $sql_base .= " AND a.tanggal_absen >= '" . mysqli_real_escape_string($conn, $filter_tanggal_awal) . "'";
}
if (!empty($filter_tanggal_akhir)) {
    $sql_base .= " AND a.tanggal_absen <= '" . mysqli_real_escape_string($conn, $filter_tanggal_akhir) . "'";
}
if (!empty($filter_npp)) {
    $sql_base .= " AND a.npp LIKE '%" . mysqli_real_escape_string($conn, $filter_npp) . "%'";
}

// Count total records
$count_sql = "SELECT COUNT(*) as total " . $sql_base;
$count_result = mysqli_query($conn, $count_sql);
$total_records = 0;
if ($count_result) {
    $count_row = mysqli_fetch_assoc($count_result);
    $total_records = intval($count_row['total']);
}
$total_pages = ceil($total_records / $records_per_page);

// Query untuk menampilkan data per halaman (dengan pagination)
$sql = "SELECT a.*, e.nama_emp, b.nama_bagian, e.cabang " . $sql_base . " ORDER BY a.tanggal_absen DESC, a.npp ASC LIMIT $records_per_page OFFSET $offset";
$query = mysqli_query($conn, $sql);
$total_rows = mysqli_num_rows($query);

// Query untuk menghitung SEMUA data (tanpa pagination) - untuk summary statistics yang akurat
$sql_all = "SELECT a.* " . $sql_base;
$query_all = mysqli_query($conn, $sql_all);

// Calculate summary statistics dari SEMUA data yang sesuai filter
$total_hadir = 0;
$total_alpha = 0;
$total_cuti = 0;
$total_telat = 0;
$grand_total_makan = 0;
$grand_total_lembur = 0;
$grand_total_denda = 0;
$grand_total_insentif = 0;
$grand_total_bayar = 0;
$grand_total_bonus_titik = 0;
$grand_total_bonus_full_hadir = 0;
$grand_total_menit = 0;

$npp_periode_map = []; // Track NPP and periode for bonus query
while ($row = mysqli_fetch_assoc($query_all)) {
    if ($row['is_hadir'])
        $total_hadir++;
    if (!$row['is_hadir'] && !$row['is_cuti'])
        $total_alpha++;
    if ($row['is_cuti'])
        $total_cuti++;
    if ($row['is_late'])
        $total_telat++;
    // Only include negative (potongan) values in page-level total for uang makan
    $uang_makan_val = floatval($row['uang_makan'] ?? 0);
    if ($uang_makan_val < 0) {
        $grand_total_makan += $uang_makan_val;
    }
    $grand_total_lembur += floatval($row['uang_lembur'] ?? 0);
    $grand_total_denda += floatval($row['denda_telat'] ?? 0);
    $grand_total_insentif += floatval($row['insentif_titik'] ?? 0);
    $grand_total_bayar += floatval($row['grand_total_harian'] ?? 0);
    $grand_total_menit += intval($row['menit_terlambat'] ?? 0);

    // Track NPP and periode for bonus aggregation
    $periode = date('Y-m', strtotime($row['tanggal_absen']));
    $key = $row['npp'] . '|' . $periode;
    $npp_periode_map[$key] = 1;
}

// Query total bonus and uang_makan from transaksi_insentif_kurir for the filtered periods
if (!empty($npp_periode_map)) {
    $conditions = [];
    foreach (array_keys($npp_periode_map) as $key) {
        list($npp_val, $periode_val) = explode('|', $key);
        $npp_esc = mysqli_real_escape_string($conn, $npp_val);
        $periode_esc = mysqli_real_escape_string($conn, $periode_val);
        $conditions[] = "(npp = '$npp_esc' AND periode = '$periode_esc')";
    }
    // Prefer monthly recorded potongan_makan if present; otherwise keep daily negative-sum
    $bonus_sql = "SELECT SUM(bonus_insentif_titik) as total_bonus_titik, SUM(bonus_insentif_full_masuk) as total_bonus_full, SUM(uang_makan) as total_uang_makan, SUM(COALESCE(potongan_makan,0)) as total_potongan_makan
                  FROM transaksi_insentif_kurir
                  WHERE " . implode(' OR ', $conditions);
    $bonus_query = mysqli_query($conn, $bonus_sql);
    if ($bonus_query && $bonus_row = mysqli_fetch_assoc($bonus_query)) {
        $grand_total_bonus_titik = floatval($bonus_row['total_bonus_titik'] ?? 0);
        $grand_total_bonus_full_hadir = floatval($bonus_row['total_bonus_full'] ?? 0);
        // If monthly potongan_makan exists, use it (display negative as deduction). Otherwise keep daily negative-sum.
        $total_potongan = floatval($bonus_row['total_potongan_makan'] ?? 0);
        if ($total_potongan > 0) {
            $grand_total_makan = -1 * $total_potongan; // show as negative deduction
        }
    }
}

// Reset pointer untuk query yang akan ditampilkan
mysqli_data_seek($query, 0);
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                <?php echo $pagedesc; ?>
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
                            <input type="date" name="tanggal_awal" class="form-control"
                                value="<?php echo htmlspecialchars($filter_tanggal_awal); ?>">
                        </div>
                        <div class="form-group">
                            <label>Tanggal Akhir:</label>
                            <input type="date" name="tanggal_akhir" class="form-control"
                                value="<?php echo htmlspecialchars($filter_tanggal_akhir); ?>">
                        </div>
                        <div class="form-group">
                            <label>NPP:</label>
                            <input type="text" name="npp" class="form-control" placeholder="Cari NPP..."
                                value="<?php echo htmlspecialchars($filter_npp); ?>">
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
                        <?php
                        // Build export query preserving current filters
                        $export_query = http_build_query(array(
                            'tanggal_awal' => $filter_tanggal_awal,
                            'tanggal_akhir' => $filter_tanggal_akhir,
                            'npp' => $filter_npp
                        ));
                        ?>
                        <a href="absensi_kurir_export_xls.php?<?php echo $export_query; ?>" class="btn btn-default">
                            <i class="fa fa-file-excel-o"></i> Export Excel
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
                    <i class="fa fa-table"></i> Data Harian
                    <span class="pull-right">Halaman <?php echo $current_page; ?> dari <?php echo $total_pages; ?> |
                        Total: <?php echo $total_records; ?> data</span>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="dataTables"
                            style="font-size: 11px;">
                            <thead>
                                <tr>
                                    <th rowspan="2">No</th>
                                    <th rowspan="2">Tanggal</th>
                                    <th rowspan="2">NPP</th>
                                    <th rowspan="2">Nama</th>
                                    <th rowspan="2">Tugas</th>

                                    <th colspan="2" class="text-center bg-info">Jam Kerja</th>

                                    <th colspan="4" class="text-center bg-warning">Status</th>

                                    <th colspan="6" class="text-center bg-success">Komponen Finansial (Rp)</th>
                                </tr>
                                <tr>
                                    <!-- Jam Kerja (2 kolom) -->
                                    <th class="bg-info">Masuk</th>
                                    <th class="bg-info">Pulang</th>

                                    <!-- Status (4 kolom) -->
                                    <th class="bg-warning">Hadir</th>
                                    <th class="bg-warning">Telat</th>
                                    <th class="bg-warning">Cuti</th>
                                    <th class="bg-warning">Ket. Cuti</th>

                                    <!-- Finansial (6 kolom) -->
                                    <th class="bg-success">Bonus Titik</th>
                                    <th class="bg-success">Bonus Full</th>
                                    <th class="bg-success">Makan</th>
                                    <th class="bg-success">Lembur</th>
                                    <th class="bg-success">Denda</th>
                                    <th class="bg-success">Menit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = $offset + 1;
                                $hari_indo = array('Sun'=>'Min','Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab');

                                while ($row = mysqli_fetch_assoc($query)) {
                                    $is_cuti = !empty($row['is_cuti']);
                                    $is_hadir = !empty($row['is_hadir']);
                                    $is_late = !empty($row['is_late']);
                                    $menit_terlambat = intval($row['menit_terlambat'] ?? 0);

                                    // Status badges
                                    if ($is_cuti) {
                                        $status_hadir = '-';
                                        $telat_badge = '-';
                                        $status_cuti = '<span class="label label-info">Ya</span>';
                                    } elseif ($is_hadir) {
                                        $status_hadir = '<span class="label label-success">✓</span>';
                                        $telat_badge = $is_late ? '<span class="label label-warning">'.$menit_terlambat.'\'</span>' : '<span class="label label-default">✓</span>';
                                        $status_cuti = '-';
                                    } else {
                                        $status_hadir = '<span class="label label-danger">✗</span>';
                                        $telat_badge = '-';
                                        $status_cuti = '-';
                                    }

                                    $row_class = $is_cuti ? 'info' : (!$is_hadir ? 'danger' : ($is_late ? 'warning' : ''));

                                    $hari_en = date('D', strtotime($row['tanggal_absen']));
                                    $tanggal_formatted = date('d/m/Y', strtotime($row['tanggal_absen'])) . '<br><small>' . ($hari_indo[$hari_en] ?? $hari_en) . '</small>';
                                    $jam_masuk = $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-';
                                    $jam_pulang = $row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : '-';

                                    $uang_makan_harian = floatval($row['uang_makan'] ?? 0);
                                    $uang_lembur = floatval($row['uang_lembur'] ?? 0);
                                    $denda = floatval($row['denda_telat'] ?? 0);
                                    ?>
                                    <tr class="<?php echo $row_class; ?>">
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo $tanggal_formatted; ?></td>
                                        <td><?php echo htmlspecialchars($row['npp']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_emp'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['jenis_tugas'] ?? '-'); ?></td>

                                        <!-- Jam Kerja (2) -->
                                        <td class="text-center"><?php echo $jam_masuk; ?></td>
                                        <td class="text-center"><?php echo $jam_pulang; ?></td>

                                        <!-- Status (4) -->
                                        <td class="text-center"><?php echo $status_hadir; ?></td>
                                        <td class="text-center"><?php echo $telat_badge; ?></td>
                                        <td class="text-center"><?php echo $status_cuti; ?></td>
                                        <td><?php echo htmlspecialchars($row['keterangan_cuti'] ?? '-'); ?></td>

                                        <!-- Finansial (6) -->
                                        <td class="text-center text-muted">-</td>
                                        <td class="text-center text-muted">-</td>
                                        <td class="text-right <?php echo ($uang_makan_harian < 0) ? 'text-danger' : ''; ?>">
                                            <?php echo ($uang_makan_harian < 0) ? number_format($uang_makan_harian) : '-'; ?>
                                        </td>
                                        <td class="text-right"><?php echo $uang_lembur > 0 ? number_format($uang_lembur) : '-'; ?></td>
                                        <td class="text-right <?php echo $denda > 0 ? 'text-danger' : ''; ?>">
                                            <?php echo $denda > 0 ? number_format($denda) : '-'; ?>
                                        </td>
                                        <td class="text-center"><?php echo $menit_terlambat > 0 ? $menit_terlambat : '-'; ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <tr class="info">
                                    <th colspan="11" class="text-right"><strong>TOTAL:</strong></th>
                                    <th class="text-right"><strong>Rp <?php echo number_format($grand_total_bonus_titik); ?></strong></th>
                                    <th class="text-right"><strong>Rp <?php echo number_format($grand_total_bonus_full_hadir); ?></strong></th>
                                    <th class="text-right text-danger"><strong>Rp <?php echo number_format($grand_total_makan); ?></strong></th>
                                    <th class="text-right"><strong>Rp <?php echo number_format($grand_total_lembur); ?></strong></th>
                                    <th class="text-right text-danger"><strong>Rp <?php echo number_format($grand_total_denda); ?></strong></th>
                                    <th class="text-center"><strong><?php echo number_format($grand_total_menit); ?></strong></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Pagination controls -->
                    <?php if ($total_pages > 1): ?>
                        <div class="text-center">
                            <ul class="pagination">
                                <?php if ($current_page > 1): ?>
                                    <li><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">&laquo;
                                            First</a></li>
                                    <li><a
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>">&lsaquo;
                                            Prev</a></li>
                                <?php else: ?>
                                    <li class="disabled"><span>&laquo; First</span></li>
                                    <li class="disabled"><span>&lsaquo; Prev</span></li>
                                <?php endif; ?>

                                <?php
                                // Show page numbers
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);

                                for ($i = $start_page; $i <= $end_page; $i++):
                                    ?>
                                    <li class="<?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                        <a
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($current_page < $total_pages): ?>
                                    <li><a
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>">Next
                                            &rsaquo;</a></li>
                                    <li><a
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">Last
                                            &raquo;</a></li>
                                <?php else: ?>
                                    <li class="disabled"><span>Next &rsaquo;</span></li>
                                    <li class="disabled"><span>Last &raquo;</span></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include("layout_bottom.php"); ?>