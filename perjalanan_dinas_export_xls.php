<?php
include("sess_check.php");
include __DIR__ . '/dist/config/koneksi.php';

header("Content-Type: application/force-download");
header("Cache-Control: no-cache, must-revalidate");
header("content-disposition:attachment; filename=perjalanan_dinas.xls");

$sql = "SELECT p.id, p.no_dokumen, p.nama, p.departemen, p.tanggal_perjalanan, p.kota_tujuan, p.tanggal_dokumen, p.budget_total,
    pg.status,
    pg.approval_manager_hr,
    pg.approval_direktur,
    pg.tanggal_pengajuan
    FROM perjalanan_dinas p
    LEFT JOIN (
        SELECT p2.*
        FROM perjalanan_pengajuan p2
        INNER JOIN (
            SELECT id_perjalanan, MAX(id) AS mid FROM perjalanan_pengajuan GROUP BY id_perjalanan
        ) m ON p2.id_perjalanan = m.id_perjalanan AND p2.id = m.mid
    ) pg ON pg.id_perjalanan = p.id
    ORDER BY p.id DESC";

$query = mysqli_query($conn, $sql);
$pagedesc = "Data Perjalanan Dinas";
$pagetitle = str_replace(" ", "_", $pagedesc);
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
	    <h4>DATA PERJALANAN DINAS</h4>
    </center>
	<table border="1">
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th class="text-center">No. Dokumen</th>
                <th class="text-center">Nama</th>
                <th class="text-center">Departemen</th>
                <th class="text-center">Kota Tujuan</th>
                <th class="text-center">Budget Total</th>
                <th class="text-center">Tanggal Pengajuan</th>
                <th class="text-center">Status</th>
                <th class="text-center">Approval HR</th>
                <th class="text-center">Approval Direktur</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while ($row = mysqli_fetch_assoc($query)): 
                $status = $row['status'] ?? 'BELUM DIAJUKAN';
                $approval_manager_hr = $row['approval_manager_hr'] ?? 'Pending';
                $approval_direktur = $row['approval_direktur'] ?? 'Pending';
                $tgl_pengajuan = $row['tanggal_pengajuan'] ? date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])) : '-';
            ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo $row['no_dokumen']; ?></td>
                    <td><?php echo $row['nama']; ?></td>
                    <td><?php echo $row['departemen']; ?></td>
                    <td><?php echo $row['kota_tujuan']; ?></td>
                    <td class="text-right"><?php echo $row['budget_total']; ?></td>
                    <td class="text-center"><?php echo $tgl_pengajuan; ?></td>
                    <td class="text-center"><?php echo $status; ?></td>
                    <td class="text-center"><?php echo $approval_manager_hr; ?></td>
                    <td class="text-center"><?php echo $approval_direktur; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
	</table>
</body>
</html>
