<?php
include("sess_check.php");

$npp    = $_POST['npp'];
$ajuan  = date('Y-m-d');
$mulai  = $_POST['mulai'];
$akhir  = $_POST['akhir'];
$ket    = $_POST['keterangan'];
$tujuan = $_POST['tipe_cuti'];

// Validasi tanggal mulai & akhir
if (empty($mulai) || empty($akhir) || $akhir < $mulai) {
    echo "<script type='text/javascript'>
            alert('Tanggal akhir cuti harus lebih besar atau sama dengan tanggal mulai cuti!'); 
            document.location = 'cuti_create.php'; 
          </script>";
    exit();
}

// Validasi agar tidak bisa mengajukan cuti di tanggal yang sama atau bentrok dengan cuti aktif
$npp_esc   = mysqli_real_escape_string($conn, $npp);
$mulai_esc = mysqli_real_escape_string($conn, $mulai);
$akhir_esc = mysqli_real_escape_string($conn, $akhir);

$cek_bentrok = mysqli_query($conn, "SELECT no_cuti, tgl_awal, tgl_akhir, stt_cuti FROM cuti 
    WHERE npp='$npp_esc' 
      AND stt_cuti NOT LIKE '%Reject%' 
      AND stt_cuti NOT LIKE '%Tolak%' 
      AND stt_cuti NOT LIKE '%Batal%' 
      AND tgl_awal <= '$akhir_esc' 
      AND tgl_akhir >= '$mulai_esc' 
    LIMIT 1");

if ($cek_bentrok && mysqli_num_rows($cek_bentrok) > 0) {
    $row_bentrok = mysqli_fetch_assoc($cek_bentrok);
    $tgl_a = date('d-m-Y', strtotime($row_bentrok['tgl_awal']));
    $tgl_b = date('d-m-Y', strtotime($row_bentrok['tgl_akhir']));
    $stt_b = htmlspecialchars($row_bentrok['stt_cuti'], ENT_QUOTES, 'UTF-8');
    echo "<script type='text/javascript'>
            alert('Gagal! Anda sudah memiliki pengajuan cuti pada tanggal $tgl_a s/d $tgl_b (Status: $stt_b). Tidak dapat mengajukan cuti di tanggal yang sama atau bentrok.'); 
            document.location = 'cuti_create.php'; 
          </script>";
    exit();
}

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
