<?php
include("sess_check.php");

if (isset($row_sess['status_karyawan']) && $row_sess['status_karyawan'] == 'Magang') {
	header("location: index.php");
	exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_pinjaman        = $_POST['id_pinjaman'];
    $npp                = $_POST['npp'];
    $jumlah_pinjaman    = str_replace(".", "", $_POST['jumlah_pinjaman']);
    $tenor              = $_POST['tenor'];
    
    // Handle manual tenor input
    if ($tenor === 'manual' && isset($_POST['tenor_manual'])) {
        $tenor = $_POST['tenor_manual'];
    }
    
    $tanggal_pengajuan  = $_POST['tanggal_pengajuan'];
    $keterangan         = $_POST['keterangan'];
    $status             = $_POST['status'];

    // Hitung cicilan per bulan
    $cicilan = $jumlah_pinjaman / $tenor;

    // Hitung tanggal potong pertama (selalu tanggal 26)
    $tgl_pengajuan_obj = new DateTime($tanggal_pengajuan);
    $hari_pengajuan = (int)$tgl_pengajuan_obj->format('d');
    
    // Jika pengajuan tanggal 1-26: potong di tanggal 26 bulan yang sama
    // Jika pengajuan tanggal 27-31: potong di tanggal 26 bulan berikutnya
    if ($hari_pengajuan <= 26) {
        $tanggal_potong_pertama = $tgl_pengajuan_obj->format('Y-m') . '-26';
    } else {
        $tgl_pengajuan_obj->modify('+1 month');
        $tanggal_potong_pertama = $tgl_pengajuan_obj->format('Y-m') . '-26';
    }
    
    // Hitung tanggal lunas (tanggal 26 di bulan tenor terakhir)
    $tgl_lunas_obj = new DateTime($tanggal_potong_pertama);
    $tgl_lunas_obj->modify('+' . ($tenor - 1) . ' months');
    $tanggal_lunas = $tgl_lunas_obj->format('Y-m-d');

    // Update data pinjaman
    $sql = "UPDATE pinjaman SET 
                tanggal_pengajuan = '$tanggal_pengajuan',
                jumlah_pinjaman = '$jumlah_pinjaman',
                tenor = '$tenor',
                cicilan_per_bulan = '$cicilan',
                keterangan = '$keterangan',
                status = '$status',
                tanggal_lunas = '$tanggal_lunas'
            WHERE id_pinjaman = '$id_pinjaman'";

    $res = mysqli_query($conn, $sql);

    if ($res) {
        // Hapus jadwal angsuran lama
        $sql_delete = "DELETE FROM angsuran_pinjaman WHERE id_pinjaman = '$id_pinjaman'";
        mysqli_query($conn, $sql_delete);
        
        // Generate ulang jadwal angsuran
        $tanggal_angsuran = new DateTime($tanggal_potong_pertama);
        
        for ($i = 1; $i <= $tenor; $i++) {
            $tgl_potong = $tanggal_angsuran->format('Y-m-d');
            $bulan = (int)$tanggal_angsuran->format('m');
            $tahun = (int)$tanggal_angsuran->format('Y');
            
            // Generate ID Angsuran
            $id_angsuran = $id_pinjaman . '/' . str_pad($i, 2, '0', STR_PAD_LEFT);
            
            // Insert ke tabel angsuran_pinjaman
            $sql_angsuran = "INSERT INTO angsuran_pinjaman 
                            (id_angsuran, id_pinjaman, bulan, tahun, jumlah_angsuran, status, tanggal_potong) 
                            VALUES 
                            ('$id_angsuran', '$id_pinjaman', $bulan, $tahun, $cicilan, 'belum', '$tgl_potong')";
            mysqli_query($conn, $sql_angsuran);
            
            // Tambah 1 bulan untuk angsuran berikutnya (selalu tanggal 26)
            $tanggal_angsuran->modify('+1 month');
        }
        
        header("Location: pinjaman.php?act=update&msg=success");
    } else {
        echo "<script type='text/javascript'>
                alert('Gagal memperbarui data: " . mysqli_error($conn) . "'); 
                document.location = 'pinjaman_edit.php?id=$id_pinjaman'; 
              </script>";
    }
}
?>
