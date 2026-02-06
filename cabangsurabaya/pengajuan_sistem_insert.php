<?php
    include("sess_check.php");

    $npp                  = $_POST['npp'];
    $first_four_digits    = substr($npp, 0, 4); // Ambil 4 digit pertama dari NPP
    $no_pengajuan         = $first_four_digits . date('His'); // Gabungkan 4 digit pertama NPP dengan jam, menit, detik
    $nama_karyawan        = $_POST['nama_karyawan'];
    $deskripsi_pengajuan  = $_POST['deskripsi_pengajuan'];
    $nama_bagian          = $_POST['nama_bagian'];
    $status_pengajuan     = "Belum dikerjakan";
    $tgl_pengajuan        = date('Y-m-d');

    $sqlcek = "SELECT * FROM pengajuan_sistem WHERE npp= '$npp'";
    $resscek = mysqli_query($conn, $sqlcek);
    $rowscek = mysqli_fetch_array($resscek);

    if($npp){
        $sql = "INSERT INTO pengajuan_sistem (no_pengajuan, npp, nama_karyawan, deskripsi_pengajuan, nama_bagian, status_pengajuan, tgl_pengajuan) 
        VALUES ('$no_pengajuan','$npp','$nama_karyawan','$deskripsi_pengajuan','$nama_bagian','$status_pengajuan','$tgl_pengajuan')";
        $query = mysqli_query($conn, $sql);

        if($query){
            echo"<script type='text/javascript'>
                    alert('Pengajuan Sistem Berhasil!');
                    document.location = 'pengajuan_sistem.php';
                </script>";
        }else{
            echo"<script type='text/javascript'>
                    alert('Terjadi kesalahan, silahkan coba lagi!.'); 
                    document.location = 'pengajuan_sistem_tambah.php'; 
                </script>";
        }

    }else{
        header("location: pengajuan_sistem_tambah.php?act=add&msg=double");
    }
?>
