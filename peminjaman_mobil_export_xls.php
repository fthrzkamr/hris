<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/dist/config/koneksi.php';

// Izinkan akses export laporan untuk session login yang valid (Auliya Nurul / managerhr, Fariz Hannan / karyawan, GA, Admin, dll.)
$_is_allowed_export = false;
foreach (['managerhr', 'karyawan', 'ga', 'koor_ga', 'admin', 'direktur'] as $_sess_key) {
    if (!empty($_SESSION[$_sess_key])) {
        $_is_allowed_export = true;
        break;
    }
}

if (!$_is_allowed_export) {
    header("location: login.php?login=false");
    exit;
}

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Laporan_Pemakaian_Mobil.xls");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil parameter filter (mobil, rentang tanggal, nama/NPP, dan pencarian umum)
$filter_mobil       = isset($_GET['filter_mobil']) ? trim($_GET['filter_mobil']) : '';
$filter_tgl_mulai   = isset($_GET['filter_tgl_mulai']) ? trim($_GET['filter_tgl_mulai']) : '';
$filter_tgl_selesai = isset($_GET['filter_tgl_selesai']) ? trim($_GET['filter_tgl_selesai']) : '';
$filter_nama        = isset($_GET['filter_nama']) ? trim($_GET['filter_nama']) : '';
$search_q           = isset($_GET['search']) ? trim($_GET['search']) : '';

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
if ($search_q !== '') {
    $esc_s = mysqli_real_escape_string($conn, $search_q);
    $where_parts[] = "(b.mobil LIKE '%$esc_s%' OR b.nama_emp LIKE '%$esc_s%' OR b.npp LIKE '%$esc_s%' OR e.nama_emp LIKE '%$esc_s%' OR b.keperluan LIKE '%$esc_s%' OR b.tujuan LIKE '%$esc_s%')";
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
<html lang="id">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $pagetitle; ?></title>
	<style>
		table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 12px; }
		table, th, td { border: 1px solid #333333; }
		th { background-color: #007aff; color: #ffffff; font-weight: bold; padding: 8px; text-align: center; }
		td { padding: 6px 8px; text-align: left; vertical-align: middle; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
	</style>
</head>
<body>
    <center>
	    <h3>LAPORAN PENGGUNAAN &amp; PEMAKAIAN KENDARAAN</h3>
        <?php if ($filter_mobil !== ''): ?>
            <p><strong>Unit Kendaraan:</strong> <?php echo htmlspecialchars($filter_mobil); ?></p>
        <?php endif; ?>
        <?php if ($filter_tgl_mulai !== '' || $filter_tgl_selesai !== ''): ?>
            <p><strong>Periode:</strong> <?php echo htmlspecialchars($filter_tgl_mulai ?: '-'); ?> s/d <?php echo htmlspecialchars($filter_tgl_selesai ?: '-'); ?></p>
        <?php endif; ?>
        <?php if ($filter_nama !== ''): ?>
            <p><strong>Peminjam:</strong> <?php echo htmlspecialchars($filter_nama); ?></p>
        <?php endif; ?>
    </center>
	<table border="1">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal Pinjam</th>
                <th>Jam</th>
                <th>Peminjam</th>
                <th>Mobil</th>
                <th>KM Awal</th>
                <th>KM Akhir</th>
                <th>Bensin Awal</th>
                <th>Bensin Akhir</th>
                <th>KM Tempuh</th>
                <th>Bensin Terpakai</th>
                <th>Keperluan</th>
                <th>Tujuan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($report_trips as $trip): 
                $tgl_pinjam = date('d/m/Y', strtotime($trip['tgl_mulai']));
                if (!empty($trip['tgl_selesai']) && $trip['tgl_selesai'] !== $trip['tgl_mulai']) {
                    $tgl_pinjam .= ' - ' . date('d/m/Y', strtotime($trip['tgl_selesai']));
                }
                $jam_pinjam = substr($trip['jam_mulai'], 0, 5) . ' - ' . substr($trip['jam_selesai'], 0, 5);
                
                $km_awal_str = $trip['km_awal'] ? number_format($trip['km_awal'], 0, ',', '.') . ' KM' : '-';
                $km_akhir_str = ($trip['km_akhir'] !== null && $trip['km_akhir'] !== '') ? number_format($trip['km_akhir'], 0, ',', '.') . ' KM' : 'Belum Lapor';
                $bensin_awal_str = $trip['bensin_awal'] ? intval($trip['bensin_awal']) . '/8 Bar' : 'Tidak ada data';
                $bensin_akhir_str = ($trip['bensin_akhir'] !== null && $trip['bensin_akhir'] !== '') ? intval($trip['bensin_akhir']) . '/8 Bar' : '-';

                $km_tempuh_str = 'Belum Lapor Kembali';
                if ($trip['km_tempuh'] !== null) {
                    $km_tempuh_str = number_format($trip['km_tempuh'], 0, ',', '.') . ' KM';
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
                    <td class="text-center"><?php echo $tgl_pinjam; ?></td>
                    <td class="text-center"><?php echo $jam_pinjam; ?></td>
                    <td><?php echo htmlspecialchars($trip['nama_emp']); ?></td>
                    <td><?php echo htmlspecialchars($trip['mobil']); ?></td>
                    <td class="text-right"><?php echo $km_awal_str; ?></td>
                    <td class="text-right"><?php echo $km_akhir_str; ?></td>
                    <td class="text-center"><?php echo $bensin_awal_str; ?></td>
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
