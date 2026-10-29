<?php
include("sess_check.php");

$npp    = $_POST['npp'];
$ajuan  = date('Y-m-d');
$mulai  = $_POST['mulai'];
$akhir  = $_POST['akhir'];
$ket    = $_POST['keterangan'];
$tujuan = $_POST['tipe_cuti'];

$start  = new DateTime($mulai);
$finish = new DateTime($akhir);
// Menghitung durasi tanpa hari Minggu
$durasi = 0;
$current = clone $start;
while ($current <= $finish) {
    if ($current->format('w') != 0) { // 0 = Minggu
        $durasi++;
    }
    $current->modify('+1 day');
}

$stt    = "Menunggu Approval";
$id     = date('dmYHis');

// Ambil data jumlah cuti tahunan karyawan
$pgw  = "SELECT jml_cuti FROM employee WHERE npp='$npp'";
$qpgw = mysqli_query($conn, $pgw);
$ress = mysqli_fetch_array($qpgw);
$jml_cuti = $ress['jml_cuti'];

// Hanya cuti tahunan yang mengurangi saldo cuti
if ($tujuan == "cuti tahunan") {
    if ($durasi > $jml_cuti) {
        echo "<script type='text/javascript'>
                alert('Durasi cuti lebih banyak dari jumlah cuti tersedia!'); 
                document.location = 'cuti_create.php'; 
              </script>";
        exit();
    }
    $total_cuti = $jml_cuti - $durasi;
    $update_cuti = "UPDATE employee SET jml_cuti='$total_cuti' WHERE npp='$npp'";
    mysqli_query($conn, $update_cuti);
}

// Simpan pengajuan cuti (termasuk cuti non-tahunan tanpa mengurangi saldo cuti)
$sql = "INSERT INTO cuti (no_cuti, npp, tgl_pengajuan, tgl_awal, tgl_akhir, durasi, keterangan, stt_cuti, tipe_cuti) 
        VALUES ('$id','$npp','$ajuan','$mulai','$akhir','$durasi','$ket','$stt','$tujuan')";
$query = mysqli_query($conn, $sql);

if ($query) {
    echo "<script type='text/javascript'>
            alert('Pengajuan cuti berhasil!'); 
            document.location = 'cuti_app.php'; 
          </script>";
} else {
    echo "<script type='text/javascript'>
            alert('Terjadi kesalahan, silahkan coba lagi!'); 
            document.location = 'cuti_create.php'; 
          </script>";
}
?>
