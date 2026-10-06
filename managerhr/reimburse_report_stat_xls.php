<?php
include("sess_check.php");

$thn_pilih = isset($_GET['thn']) ? $_GET['thn'] : date('Y');

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Reimbursement_Kesehatan_{$thn_pilih}.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<h2 style='text-align: center;'>LAPORAN PENGGUNAAN PLAFOND KESEHATAN PER KARYAWAN TAHUN {$thn_pilih}</h2>";
echo "<br>";
echo "<table border='1'>";
echo "<tr>
        <th>No</th>
        <th>NPP</th>
        <th>Nama Karyawan</th>
        <th>Total Plafond</th>
        <th>Claimed ({$thn_pilih})</th>
        <th>Sisa Plafond</th>
        <th>% Terpakai</th>
      </tr>";

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
    $p = round($row['fixed_plafond'], 0);
    $c = round($row['claimed_year'] ?? 0, 0);
    $s = round($p - $c, 0);
    $pct = ($p > 0) ? ($c / $p) * 100 : 0;
    
    echo "<tr>";
    echo "<td>$no</td>";
    echo "<td>".$row['npp']."</td>";
    echo "<td>".$row['nama_emp']."</td>";
    echo "<td>".$p."</td>";
    echo "<td>".$c."</td>";
    echo "<td>".$s."</td>";
    echo "<td>".number_format($pct, 2)."%</td>";
    echo "</tr>";
    $no++;
}
echo "</table>";
?>
