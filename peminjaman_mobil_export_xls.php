<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/dist/config/koneksi.php';

// Izinkan akses export laporan untuk Auliya Nurul (managerhr / 24940107), Fariz Hannan Rahmat (26020216), GA, dan Admin
$_is_allowed_export = false;
if (!empty($_SESSION['managerhr'])) {
    $_is_allowed_export = true;
} elseif (!empty($_SESSION['karyawan']) && in_array($_SESSION['karyawan'], ['26020216', '24940107'], true)) {
    $_is_allowed_export = true;
} elseif (!empty($_SESSION['ga'])) {
    $_is_allowed_export = true;
} elseif (!empty($_SESSION['admin'])) {
    $_is_allowed_export = true;
}

if (!$_is_allowed_export) {
    header("location: login.php?login=false");
    exit;
}

header("Content-Type: application/force-download");
header("Cache-Control: no-cache, must-revalidate");
header("content-disposition:attachment; filename=laporan_pemakaian_mobil.xls");

// Ambil parameter filter (mobil, rentang tanggal, nama/NPP)
$filter_mobil       = isset($_GET['filter_mobil']) ? trim($_GET['filter_mobil']) : '';
$filter_tgl_mulai   = isset($_GET['filter_tgl_mulai']) ? trim($_GET['filter_tgl_mulai']) : '';
$filter_tgl_selesai = isset($_GET['filter_tgl_selesai']) ? trim($_GET['filter_tgl_selesai']) : '';
$filter_nama        = isset($_GET['filter_nama']) ? trim($_GET['filter_nama']) : '';

$where_parts = ["b.status = 'Disetujui'"];
if ($filter_mobil !== '') {
    $esc_mobil = mysqli_real_escape_string($conn, $filter_mobil);
    $where_parts[] = "b.mobil LIKE '%$esc_mobil%'";
}
if ($filter_tgl_mulai !== '' && $filter_tgl_selesai !== '') {
    $esc_start = mysqli_real_escape_string($conn, $filter_tgl_mulai);
    $esc_end   = mysqli_real_escape_string($conn, $filter_tgl_selesai);
    $where_parts[] = "(b.tgl_mulai <= '$esc_end' AND b.tgl_selesai >= '$esc_start')";
} elseif ($filter_tgl_mulai !== '') {
    $esc_start = mysqli_real_escape_string($conn, $filter_tgl_mulai);
    $where_parts[] = "b.tgl_selesai >= '$esc_start'";
} elseif ($filter_tgl_selesai !== '') {
    $esc_end = mysqli_real_escape_string($conn, $filter_tgl_selesai);
    $where_parts[] = "b.tgl_mulai <= '$esc_end'";
}
if ($filter_nama !== '') {
    $esc_nama = mysqli_real_escape_string($conn, $filter_nama);
    $where_parts[] = "(b.nama_emp LIKE '%$esc_nama%' OR b.npp LIKE '%$esc_nama%' OR e.nama_emp LIKE '%$esc_nama%')";
}
$where_clause = "WHERE " . implode(" AND ", $where_parts);

// Fetch trips
$report_trips = [];
$q_rep = mysqli_query($conn, "
    SELECT b.*, IFNULL(e.nama_emp, b.nama_emp) AS nama_emp
    FROM peminjaman_mobil b
    LEFT JOIN employee e ON b.npp = e.npp
    $where_clause
    ORDER BY b.tgl_mulai DESC, b.jam_mulai DESC
");
if ($q_rep) {
    while ($r = mysqli_fetch_assoc($q_rep)) {
        if ($r['km_akhir'] !== null && $r['km_akhir'] !== '') {
            $r['km_tempuh'] = $r['km_akhir'] - $r['km_awal'];
            $r['bensin_terpakai'] = $r['bensin_awal'] - $r['bensin_akhir'];
        } else {
            $r['km_tempuh'] = null;
            $r['bensin_terpakai'] = null;
        }
        $report_trips[] = $r;
    }
}

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
        <?php if ($filter_tgl_mulai !== '' || $filter_tgl_selesai !== ''): ?>
            <h5>Periode: <?php echo htmlspecialchars($filter_tgl_mulai ?: '-'); ?> s/d <?php echo htmlspecialchars($filter_tgl_selesai ?: '-'); ?></h5>
        <?php endif; ?>
        <?php if ($filter_nama !== ''): ?>
            <h5>Peminjam: <?php echo htmlspecialchars($filter_nama); ?></h5>
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
                <th class="text-center">KM Akhir</th>
                <th class="text-center">Bensin Awal</th>
                <th class="text-center">Bensin Akhir</th>
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
                
                $km_akhir_str = ($trip['km_akhir'] !== null && $trip['km_akhir'] !== '') ? $trip['km_akhir'] . ' KM' : 'Belum Lapor';
                $bensin_akhir_str = ($trip['bensin_akhir'] !== null && $trip['bensin_akhir'] !== '') ? intval($trip['bensin_akhir']) . ' Bar' : '-';

                $km_tempuh_str = 'Belum Lapor Kembali';
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
                    <td class="text-right"><?php echo $trip['km_awal'] ? $trip['km_awal'] . ' KM' : '-'; ?></td>
                    <td class="text-right"><?php echo $km_akhir_str; ?></td>
                    <td class="text-center"><?php echo $trip['bensin_awal'] ? $trip['bensin_awal'] . ' Bar' : 'Tidak ada data'; ?></td>
                    <td class="text-center"><?php echo $bensin_akhir_str; ?></td>
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
