<?php
include("sess_check.php");

// Set header agar bisa diunduh sebagai Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Potongan.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Query untuk mengambil data potongan dan menghubungkan dengan employee
$sql = "SELECT 
    p.npp, 
    e.nama_emp, 
    e.total_gaji,
    p.tanggal, 
    p.p_keterlambatan, 
    p.p_pinjaman, 
    p.p_lain, 
    p.desc_lain, 
    p.p_hasil 
FROM laporan_potongan p
LEFT JOIN employee e ON p.npp = e.npp 
ORDER BY p.tanggal DESC;";
$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
</head>

<body>

    <h4 style="text-align:center;">LAPORAN DATA POTONGAN</h4>
    <table border="1">
        <thead>
            <tr>
                <th>No</th>
                <th>NIP</th>
                <th>Nama Karyawan</th>
                <th>Tanggal</th>
                <th>Total Gaji</th>
                <th>Keterlambatan</th>
                <th>Pinjaman</th>
                <th>Potongan Lain</th>
                <th>Deskripsi Potongan Lain</th>
                <th>Total Potongan</th>
                <th>Total Bersih</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            while ($data = mysqli_fetch_assoc($result)) {
                $gaji_bersih = $data['total_gaji'] - ($data['p_hasil'] ?? 0);
                echo "<tr>";
                echo "<td style='text-align:center;'>" . $no . "</td>";
                echo "<td>" . $data['npp'] . "</td>";
                echo "<td>" . $data['nama_emp'] . "</td>";
                echo "<td style='text-align:center;'>" . date('d-m-Y', strtotime($data['tanggal'])) . "</td>";
                echo "<td style='text-align:right;'>" . number_format($data['total_gaji'], 0, ",", ".") . "</td>";
                echo "<td style='text-align:right;'>" . number_format($data['p_keterlambatan'], 0, ",", ".") . "</td>";
                echo "<td style='text-align:right;'>" . number_format($data['p_pinjaman'], 0, ",", ".") . "</td>";
                echo "<td style='text-align:right;'>" . number_format($data['p_lain'], 0, ",", ".") . "</td>";
                echo "<td>" . $data['desc_lain'] . "</td>";
                echo "<td>Rp " . number_format($gaji_bersih, 0, ',', '.') . "</td>";
                echo "<td style='text-align:right;'>" . number_format($data['p_hasil'], 0, ",", ".") . "</td>";
                echo "</tr>";
                $no++;
            }
            ?>
        </tbody>
    </table>

</body>

</html>