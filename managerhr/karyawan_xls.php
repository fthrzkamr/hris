<?php
include("sess_check.php");

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Data_Karyawan.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil data dari database
$sql = "SELECT * FROM employee ORDER BY nama_emp ASC";
$ress = mysqli_query($conn, $sql);

// Cetak header tabel
echo "<table border='1'>";
echo "<tr>
        <th>No</th>
        <th>Nomer Induk Karyawan</th>
        <th>Nama Karyawan</th>
        <th>Status Karyawan</th>
        <th>Status Reimburse</th>
        <th>Plafond Kesehatan</th>
        <th>Limit Kesehatan</th>
        <th>Plafond Kacamata</th>
        <th>Limit Kacamata</th>
      </tr>";

// Cetak data karyawan
$i = 1;
while ($data = mysqli_fetch_array($ress)) {
    echo "<tr>";
    echo "<td>{$i}</td>";
    echo "<td>{$data['npp']}</td>";
    echo "<td>{$data['nama_emp']}</td>";
    echo "<td>{$data['aktif']}</td>";
    echo "<td>{$data['status_rem']}</td>";
    echo "<td>" . number_format($data['plafond'], 0, ".", ".") . "</td>";
    echo "<td>" . number_format($data['kesehatan'], 0, ".", ".") . "</td>";
    echo "<td>" . number_format($data['plafond_kacamata'], 0, ".", ".") . "</td>";
    echo "<td>" . number_format($data['kacamata'], 0, ".", ".") . "</td>";
    echo "</tr>";
    $i++;
}

echo "</table>";
?>
