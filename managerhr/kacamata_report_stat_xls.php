<?php
include("sess_check.php");

$thn_pilih = isset($_GET['thn']) ? $_GET['thn'] : date('Y');

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Reimbursement_Kacamata_{$thn_pilih}.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<h2 style='text-align: center;'>LAPORAN PENGGUNAAN PLAFOND KACAMATA PER KARYAWAN TAHUN {$thn_pilih}</h2>";
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
$sql_detail_km = "SELECT e.npp, e.nama_emp, 
               COALESCE(ph.plafond_kacamata, e.plafond_kacamata) as fixed_plafond_km,
               (SELECT SUM(k.total_kwintansi * 0.8) FROM kacamata k WHERE k.npp = e.npp AND YEAR(k.tanggal_pengajuan) = '$thn_pilih' AND k.status = 'Approved') as claimed_year_km
               FROM employee e
               LEFT JOIN plafond_history ph ON e.npp = ph.npp AND ph.tahun = '$thn_pilih'
               WHERE YEAR(e.tanggal_masuk_karyawan) <= '$thn_pilih' 
               AND (e.plafond_kacamata > 0 OR ph.plafond_kacamata > 0)
               ORDER BY e.nama_emp ASC";
$query_detail_km = mysqli_query($conn, $sql_detail_km);
while($row_km = mysqli_fetch_array($query_detail_km)) {
    $p_km = round($row_km['fixed_plafond_km'], 0);
    $c_km = round($row_km['claimed_year_km'] ?? 0, 0);
    $s_km = round($p_km - $c_km, 0);
    $pct_km = ($p_km > 0) ? ($c_km / $p_km) * 100 : 0;
    
    echo "<tr>";
    echo "<td>$no</td>";
    echo "<td>".$row_km['npp']."</td>";
    echo "<td>".$row_km['nama_emp']."</td>";
    echo "<td>".$p_km."</td>";
    echo "<td>".$c_km."</td>";
    echo "<td>".$s_km."</td>";
    echo "<td>".number_format($pct_km, 2)."%</td>";
    echo "</tr>";
    $no++;
}
echo "</table>";
?>
