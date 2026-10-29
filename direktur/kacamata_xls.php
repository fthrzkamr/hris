<?php
include("sess_check.php");

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Data_Kacamata.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil data dari database
$sql = "SELECT * FROM kacamata ORDER BY nama_karyawan ASC";
$ress = mysqli_query($conn, $sql);

// Cek apakah query berhasil dijalankan
if (!$ress) {
    die("Query gagal: " . mysqli_error($conn)); // Menampilkan pesan error jika query gagal
}

// Cetak judul laporan
echo "<h2 style='text-align: center;'>LAPORAN DATA REIMBURSE KACAMATA</h2>";
echo "<br>";

// Cetak header tabel
echo "<table border='1'>";
echo "<tr>
        <th>No</th>
        <th>NIP</th>
        <th>Nama Karyawan</th>
        <th>Tempat</th>
        <th>Tanggal Pengajuan</th>
        <th>Total Kwintansi</th>
        <th>Total Rembes</th>
        <th>Sisa Limit</th>
        <th>Status</th>
      </tr>";

// Cetak data karyawan
$i = 1;
while ($data = mysqli_fetch_array($ress)) {
    // Perhitungan total rembes dan sisa limit
    $limit = isset($data['kacamata']) ? $data['kacamata'] : 0;
    $kacamata = isset($data['total_kwintansi']) ? $data['total_kwintansi'] : 0;
    $a = (80 / 100);
    $total_rembes = $kacamata * $a;
    $sisa_limit = $kacamata - $total_rembes;
    
    echo "<tr>";
    echo "<td>{$i}</td>";
    echo "<td>{$data['npp']}</td>";
    echo "<td>{$data['nama_karyawan']}</td>";
    echo "<td>{$data['nama_fasilitas']}</td>";
    echo "<td>" . date('Y-m-d', strtotime($data['tanggal_pengajuan'])) . "</td>"; 
    echo "<td>" . number_format($data['total_kwintansi'], 0, ".", ".") . "</td>";
    echo "<td>" . number_format($total_rembes, 0, ".", ".") . "</td>";
    echo "<td>" . number_format($sisa_limit, 0, ".", ".") . "</td>";
    echo "<td>{$data['status']}</td>";
    echo "</tr>";
    $i++;
}

echo "</table>";
?>
