<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Laporan Reimbursement";
	include("layout_top.php");
	include("dist/function/format_rupiah.php");

    // Tangani Filter Tahun
    $thn_sekarang = date('Y');
    $thn_pilih = isset($_GET['thn']) ? $_GET['thn'] : $thn_sekarang;

    // Ambil daftar tahun dari data reimbursement (rembes & kacamata) untuk dropdown
    $list_thn = [];
    $sql_thn = "SELECT thn FROM (
                    SELECT DISTINCT YEAR(tanggal_pemeriksaan) as thn FROM rembes WHERE tanggal_pemeriksaan IS NOT NULL
                    UNION
                    SELECT DISTINCT YEAR(tanggal_pengajuan) as thn FROM kacamata WHERE tanggal_pengajuan IS NOT NULL
                ) as combined_years ORDER BY thn DESC";
    $query_thn = mysqli_query($conn, $sql_thn);
    while($row_thn = mysqli_fetch_array($query_thn)) {
        $list_thn[] = $row_thn['thn'];
    }
    // Pastikan tahun sekarang ada di list
    if (!in_array($thn_sekarang, $list_thn)) {
        array_unshift($list_thn, $thn_sekarang);
    }

    // Query Summary Kesehatan:
    // 1. Total Plafond: Cek history, jika tidak ada pakai data current
    $sql_plafond = "SELECT SUM(COALESCE(ph.plafond_kesehatan, e.plafond)) as total_plafond 
                    FROM employee e
                    LEFT JOIN plafond_history ph ON e.npp = ph.npp AND ph.tahun = '$thn_pilih'
                    WHERE YEAR(e.tanggal_masuk_karyawan) <= '$thn_pilih' AND (e.plafond > 0 OR ph.plafond_kesehatan > 0)";
    $res_plafond = mysqli_query($conn, $sql_plafond);
    $data_plafond = mysqli_fetch_assoc($res_plafond);
    $total_plafond = $data_plafond['total_plafond'] ?? 0;

    // 2. Total Claimed untuk tahun terpilih dari tabel rembes (80% dari kwitansi)
    $sql_claimed = "SELECT SUM(total_kwitansi * 0.8) as total_claimed 
                    FROM rembes 
                    WHERE YEAR(tanggal_pemeriksaan) = '$thn_pilih' AND status != 'Rejected'";
    $res_claimed = mysqli_query($conn, $sql_claimed);
    $data_claimed = mysqli_fetch_assoc($res_claimed);
    $total_claimed = $data_claimed['total_claimed'] ?? 0;

    $total_remaining = $total_plafond - $total_claimed;
    $percent_claimed = ($total_plafond > 0) ? ($total_claimed / $total_plafond) * 100 : 0;
    $percent_remaining = ($total_plafond > 0) ? ($total_remaining / $total_plafond) * 100 : 0;

    // Query Summary Kacamata:
    // 1. Total Plafond Kacamata
    $sql_plafond_km = "SELECT SUM(COALESCE(ph.plafond_kacamata, e.plafond_kacamata)) as total_plafond_km 
                        FROM employee e
                        LEFT JOIN plafond_history ph ON e.npp = ph.npp AND ph.tahun = '$thn_pilih'
                        WHERE YEAR(e.tanggal_masuk_karyawan) <= '$thn_pilih' AND (e.plafond_kacamata > 0 OR ph.plafond_kacamata > 0)";
    $res_plafond_km = mysqli_query($conn, $sql_plafond_km);
    $data_plafond_km = mysqli_fetch_assoc($res_plafond_km);
    $total_plafond_km = $data_plafond_km['total_plafond_km'] ?? 0;

    // 2. Total Claimed Kacamata (80% dari kwintansi) - Menampilkan semua yang belum di-reject (Menunggu & Approved)
    $sql_claimed_km = "SELECT SUM(total_kwintansi * 0.8) as total_claimed_km 
                        FROM kacamata 
                        WHERE YEAR(tanggal_pengajuan) = '$thn_pilih' AND status != 'Rejected'";
    $res_claimed_km = mysqli_query($conn, $sql_claimed_km);
    $data_claimed_km = mysqli_fetch_assoc($res_claimed_km);
    $total_claimed_km = $data_claimed_km['total_claimed_km'] ?? 0;

    $total_remaining_km = $total_plafond_km - $total_claimed_km;
    $percent_claimed_km = ($total_plafond_km > 0) ? ($total_claimed_km / $total_plafond_km) * 100 : 0;
    $percent_remaining_km = ($total_plafond_km > 0) ? ($total_remaining_km / $total_plafond_km) * 100 : 0;
?>

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Laporan Reimbursement - Tahun <?php echo $thn_pilih; ?></h1>
            </div>
        </div>

        <!-- Filter Tahun -->
        <div class="row" style="margin-bottom: 20px;">
            <div class="col-lg-4">
                <form method="GET" class="form-inline">
                    <div class="form-group">
                        <label>Pilih Tahun: </label>
                        <select name="thn" class="form-control" onchange="this.form.submit()">
                            <?php foreach($list_thn as $t) { ?>
                                <option value="<?php echo $t; ?>" <?php echo ($t == $thn_pilih) ? 'selected' : ''; ?>><?php echo $t; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards Kesehatan -->
        <div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-university fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge" style="font-size: 24px;"><?php echo format_rupiah($total_plafond); ?></div>
                                <div>Total Plafond Kesehatan</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="panel panel-red">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-stethoscope fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge" style="font-size: 24px;"><?php echo format_rupiah($total_claimed); ?></div>
                                <div>Total Claimed Kesehatan (<?php echo $thn_pilih; ?>)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="panel panel-green">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-medkit fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge" style="font-size: 24px;"><?php echo format_rupiah($total_remaining); ?></div>
                                <div>Sisa Limit Kesehatan</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress/Percentage Section Kesehatan -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-bar-chart-o fa-fw"></i> Penggunaan Plafond Kesehatan - <?php echo $thn_pilih; ?>
                    </div>
                    <div class="panel-body">
                        <h4>Sudah Terpakai (Claimed)</h4>
                        <div class="progress">
                            <div class="progress-bar progress-bar-danger" role="progressbar" style="width: <?php echo $percent_claimed; ?>%;">
                                <?php echo number_format($percent_claimed, 2); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Table Kesehatan -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Rincian Penggunaan Plafond Kesehatan Per Karyawan (Tahun <?php echo $thn_pilih; ?>)</span>
                        <a href="reimburse_report_stat_xls.php?thn=<?php echo $thn_pilih; ?>" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</a>
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped table-bordered table-hover" id="tabel-reimburse">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NPP</th>
                                    <th>Nama Karyawan</th>
                                    <th>Total Plafond</th>
                                    <th>Claimed (<?php echo $thn_pilih; ?>)</th>
                                    <th>Sisa Plafond</th>
                                    <th>% Terpakai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $no = 1;
                                    $sql_detail = "SELECT e.npp, e.nama_emp, 
                                                   COALESCE(ph.plafond_kesehatan, e.plafond) as fixed_plafond,
                                                   (SELECT SUM(r.total_kwitansi * 0.8) FROM rembes r WHERE r.npp = e.npp AND YEAR(r.tanggal_pemeriksaan) = '$thn_pilih' AND r.status != 'Rejected') as claimed_year
                                                   FROM employee e
                                                   LEFT JOIN plafond_history ph ON e.npp = ph.npp AND ph.tahun = '$thn_pilih'
                                                   WHERE YEAR(e.tanggal_masuk_karyawan) <= '$thn_pilih' 
                                                   AND (e.plafond > 0 OR ph.plafond_kesehatan > 0)
                                                   ORDER BY e.nama_emp ASC";
                                    $query_detail = mysqli_query($conn, $sql_detail);
                                    while($row = mysqli_fetch_array($query_detail)) {
                                        $p = $row['fixed_plafond'];
                                        $c = $row['claimed_year'] ?? 0;
                                        $s = $p - $c;
                                        $pct = ($p > 0) ? ($c / $p) * 100 : 0;
                                        
                                        echo "<tr>";
                                        echo "<td>$no</td>";
                                        echo "<td>".$row['npp']."</td>";
                                        echo "<td>".$row['nama_emp']."</td>";
                                        echo "<td>".format_rupiah($p)."</td>";
                                        echo "<td class='text-danger'>".format_rupiah($c)."</td>";
                                        echo "<td class='text-success'>".format_rupiah($s)."</td>";
                                        echo "<td>".number_format($pct, 2)."%</td>";
                                        echo "</tr>";
                                        $no++;
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <br>
        <hr>
        <br>

        <!-- Summary Cards Kacamata -->
        <div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-university fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge" style="font-size: 24px;"><?php echo format_rupiah($total_plafond_km); ?></div>
                                <div>Total Plafond Kacamata</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="panel panel-yellow">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-eye fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge" style="font-size: 24px;"><?php echo format_rupiah($total_claimed_km); ?></div>
                                <div>Total Claimed Kacamata (<?php echo $thn_pilih; ?>)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="panel panel-green">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-eye-slash fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge" style="font-size: 24px;"><?php echo format_rupiah($total_remaining_km); ?></div>
                                <div>Sisa Limit Kacamata</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress/Percentage Section Kacamata -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-bar-chart-o fa-fw"></i> Penggunaan Plafond Kacamata - <?php echo $thn_pilih; ?>
                    </div>
                    <div class="panel-body">
                        <h4>Sudah Terpakai (Claimed)</h4>
                        <div class="progress">
                            <div class="progress-bar progress-bar-warning" role="progressbar" style="width: <?php echo $percent_claimed_km; ?>%;">
                                <?php echo number_format($percent_claimed_km, 2); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Table Kacamata -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Rincian Penggunaan Plafond Kacamata Per Karyawan (Tahun <?php echo $thn_pilih; ?>)</span>
                        <a href="kacamata_report_stat_xls.php?thn=<?php echo $thn_pilih; ?>" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</a>
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped table-bordered table-hover" id="tabel-kacamata">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NPP</th>
                                    <th>Nama Karyawan</th>
                                    <th>Total Plafond</th>
                                    <th>Claimed (<?php echo $thn_pilih; ?>)</th>
                                    <th>Sisa Plafond</th>
                                    <th>% Terpakai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $no = 1;
                                    $sql_detail_km = "SELECT e.npp, e.nama_emp, 
                                                   COALESCE(ph.plafond_kacamata, e.plafond_kacamata) as fixed_plafond_km,
                                                   (SELECT SUM(k.total_kwintansi * 0.8) FROM kacamata k WHERE k.npp = e.npp AND YEAR(k.tanggal_pengajuan) = '$thn_pilih' AND k.status != 'Rejected') as claimed_year_km
                                                   FROM employee e
                                                   LEFT JOIN plafond_history ph ON e.npp = ph.npp AND ph.tahun = '$thn_pilih'
                                                   WHERE YEAR(e.tanggal_masuk_karyawan) <= '$thn_pilih' 
                                                   AND (e.plafond_kacamata > 0 OR ph.plafond_kacamata > 0)
                                                   ORDER BY e.nama_emp ASC";
                                    $query_detail_km = mysqli_query($conn, $sql_detail_km);
                                    while($row_km = mysqli_fetch_array($query_detail_km)) {
                                        $p_km = $row_km['fixed_plafond_km'];
                                        $c_km = $row_km['claimed_year_km'] ?? 0;
                                        $s_km = $p_km - $c_km;
                                        $pct_km = ($p_km > 0) ? ($c_km / $p_km) * 100 : 0;
                                        
                                        echo "<tr>";
                                        echo "<td>$no</td>";
                                        echo "<td>".$row_km['npp']."</td>";
                                        echo "<td>".$row_km['nama_emp']."</td>";
                                        echo "<td>".format_rupiah($p_km)."</td>";
                                        echo "<td class='text-danger'>".format_rupiah($c_km)."</td>";
                                        echo "<td class='text-success'>".format_rupiah($s_km)."</td>";
                                        echo "<td>".number_format($pct_km, 2)."%</td>";
                                        echo "</tr>";
                                        $no++;
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		$('#tabel-reimburse').DataTable({
			"responsive": true,
			"processing": true
		});
		$('#tabel-kacamata').DataTable({
			"responsive": true,
			"processing": true
		});
	});
</script>

<?php
	include("layout_bottom.php");
?>
