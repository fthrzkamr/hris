<?php
include("dist/config/koneksi.php");

$npp = "24940107";
echo "<h2>Hasil Diagnosa Database:</h2>";

$sql = "SELECT npp, nama_emp, COUNT(*) as jumlah FROM employee WHERE npp='$npp' GROUP BY npp, nama_emp";
$query = mysqli_query($conn, $sql);

if(mysqli_num_rows($query) > 0){
	while($row = mysqli_fetch_array($query)){
		echo "NPP: " . $row['npp'] . " | Nama: " . $row['nama_emp'] . " | <b>Jumlah Duplikat: " . $row['jumlah'] . "</b><br>";
	}
} else {
	echo "NPP $npp tidak ditemukan sama sekali di database.";
}

echo "<hr><h3>Cek Primary Key:</h3>";
$res = mysqli_query($conn, "SHOW KEYS FROM employee WHERE Key_name = 'PRIMARY'");
if(mysqli_num_rows($res) > 0){
	echo "✅ Primary Key SUDAH ADA.";
} else {
	echo "❌ Primary Key TIDAK ADA (Ini penyebab duplikat!).";
}
?>
