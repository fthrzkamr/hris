<?php
include("sess_check.php");
include __DIR__ . '/dist/config/koneksi.php';

header("Content-Type: application/force-download");
header("Cache-Control: no-cache, must-revalidate");
header("content-disposition:attachment; filename=laporan_pemakaian_mobil.xls");

// Fetch filter_mobil from GET parameters
$filter_mobil = isset($_GET['filter_mobil']) ? mysqli_real_escape_string($conn, $_GET['filter_mobil']) : '';
$where_clause = "WHERE status = 'Disetujui'";
if ($filter_mobil !== '') {
    $where_clause .= " AND mobil = '$filter_mobil'";
}

// Fetch trips chronologically
$report_trips = [];
$q_rep = mysqli_query($conn, "
    SELECT * FROM peminjaman_mobil 
    $where_clause 
    ORDER BY mobil ASC, tgl_mulai ASC, jam_mulai ASC
");
if ($q_rep) {
    while ($r = mysqli_fetch_assoc($q_rep)) {
        $report_trips[] = $r;
    }
}

// Calculate chronological differences per car
for ($idx = 0; $idx < count($report_trips); $idx++) {
    $current = &$report_trips[$idx];
    $current_car = $current['mobil'];
    
    // Find next trip of the SAME car
    $next = null;
    for ($j = $idx + 1; $j < count($report_trips); $j++) {
        if ($report_trips[$j]['mobil'] === $current_car) {
            $next = $report_trips[$j];
            break;
        }
    }
    
    if ($next) {
        $current['km_tempuh'] = $next['km_awal'] - $current['km_awal'];
        $current['bensin_terpakai'] = $current['bensin_awal'] - $next['bensin_awal'];
    } else {
        $current['km_tempuh'] = null;
        $current['bensin_terpakai'] = null;
    }
}

// Sort report_trips descending (latest first) by tgl_mulai and jam_mulai
usort($report_trips, function($a, $b) {
    $dateA = $a['tgl_mulai'] . ' ' . $a['jam_mulai'];
    $dateB = $b['tgl_mulai'] . ' ' . $b['jam_mulai'];
    return strcmp($dateB, $dateA);
});

$pagetitle = "Laporan_Pemakaian_Mobil";
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title><?php echo $pagetitle; ?></title>
	<style>
		table { border-collapse: collapse; width: 100%; }
		table, th, td { border: 1px solid black; }
		th, td { padding: 8px; text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
	</style>
</head>
<body>
    <center>
	    <h4>LAPORAN PENGGUNAAN &amp; PEMAKAIAN KENDARAAN</h4>
        <?php if ($filter_mobil !== ''): ?>
            <h5>Mobil: <?php echo htmlspecialchars($filter_mobil); ?></h5>
        <?php endif; ?>
    </center>
	<table border="1">
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th class="text-center">Tanggal Pinjam</th>
                <th class="text-center">Peminjam</th>
                <th class="text-center">Mobil</th>
                <th class="text-center">KM Awal</th>
                <th class="text-center">Bensin Awal</th>
                <th class="text-center">KM Tempuh</th>
                <th class="text-center">Bensin Terpakai</th>
                <th class="text-center">Keperluan</th>
                <th class="text-center">Tujuan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($report_trips as $trip): 
                $tgl_pinjam = date('d-m-Y', strtotime($trip['tgl_mulai'])) . ' ' . substr($trip['jam_mulai'], 0, 5) . ' - ' . substr($trip['jam_selesai'], 0, 5);
                
                $km_tempuh_str = '-';
                if ($trip['km_tempuh'] !== null) {
                    $km_tempuh_str = $trip['km_tempuh'] . ' KM';
                }

                $bensin_terpakai_str = '-';
                if ($trip['bensin_terpakai'] !== null) {
                    if ($trip['bensin_terpakai'] > 0) {
                        $bensin_terpakai_str = $trip['bensin_terpakai'] . ' Bar';
                    } elseif ($trip['bensin_terpakai'] < 0) {
                        $bensin_terpakai_str = 'Refuel (+' . abs($trip['bensin_terpakai']) . ' Bar)';
                    } else {
                        $bensin_terpakai_str = '0 Bar';
                    }
                }
            ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo $tgl_pinjam; ?></td>
                    <td><?php echo htmlspecialchars($trip['nama_emp']); ?></td>
                    <td><?php echo htmlspecialchars($trip['mobil']); ?></td>
                    <td class="text-right"><?php echo $trip['km_awal']; ?> KM</td>
                    <td class="text-center"><?php echo $trip['bensin_awal'] ? $trip['bensin_awal'] . ' Bar' : 'Tidak ada data'; ?></td>
                    <td class="text-right"><?php echo $km_tempuh_str; ?></td>
                    <td class="text-center"><?php echo $bensin_terpakai_str; ?></td>
                    <td><?php echo htmlspecialchars($trip['keperluan']); ?></td>
                    <td><?php echo htmlspecialchars($trip['tujuan'] ?? '-'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
	</table>
</body>
</html>
