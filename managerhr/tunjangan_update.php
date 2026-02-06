<?php
include("sess_check.php");

if (isset($_POST['perbarui'])) {
    $npp = $_POST['npplama'];
    
    // Ambil nilai inputan dan hilangkan titik jika ada (misal: format ribuan diubah ke angka biasa)
    $gaji_pokok = str_replace('.', '', $_POST['gaji_pokok']);
    $tunj_jabatan = str_replace('.', '', $_POST['tunj_jabatan']);
    $tunj_transport = str_replace('.', '', $_POST['tunj_transport']);
    $tunj_kinerja = str_replace('.', '', $_POST['tunj_kinerja']);

    // Pastikan semua angka diubah ke integer agar tidak terjadi error saat perhitungan
    $gaji_pokok = intval($gaji_pokok);
    $tunj_jabatan = intval($tunj_jabatan);
    $tunj_transport = intval($tunj_transport);
    $tunj_kinerja = intval($tunj_kinerja);

    // Hitung total gaji (gaji pokok + semua tunjangan)
    $total_gaji = $gaji_pokok + $tunj_jabatan + $tunj_transport + $tunj_kinerja;

    // Query update data
    $sql = "UPDATE employee SET 
                gaji_pokok='$gaji_pokok', 
                tunj_jabatan='$tunj_jabatan', 
                tunj_transport='$tunj_transport', 
                tunj_kinerja='$tunj_kinerja',
                total_gaji='$total_gaji'
            WHERE npp='$npp'";

    $ress = mysqli_query($conn, $sql);

    if ($ress) {
        echo "<script>alert('Data berhasil diperbarui!'); window.location='tunjangan.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data!'); window.history.back();</script>";
    }
}
?>
