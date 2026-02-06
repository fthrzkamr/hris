<?php
include("sess_check.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $npp                = $_POST['npp'];
    $jumlah_pinjaman    = str_replace(".", "", $_POST['jumlah_pinjaman']);
    $tenor              = $_POST['tenor'];
    
    // Handle manual tenor input
    if ($tenor === 'manual' && isset($_POST['tenor_manual'])) {
        $tenor = $_POST['tenor_manual'];
    }
    
    $tanggal_pengajuan  = $_POST['tanggal_pengajuan'];
    $keterangan         = $_POST['keterangan'];
    $status             = 'aktif';

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

    // 1. Ambil nomor urut terakhir
    $queryLast = mysqli_query($conn, "SELECT MAX(id_pinjaman) as max_id FROM pinjaman");
    $dataLast = mysqli_fetch_array($queryLast);
    $lastId = $dataLast['max_id'];

    // Ekstrak nomor urut terakhir
    if ($lastId != '') {
        $parts = explode('/', $lastId);
        $lastNumber = intval($parts[1]) + 1;
    } else {
        $lastNumber = 1;
    }

    $urutBaru = str_pad($lastNumber, 3, '0', STR_PAD_LEFT); // 001, 002, dst
    $last4Npp = substr($npp, -4); // 4 digit terakhir dari NPP
    $id_pinjaman = "PJM/$urutBaru/$last4Npp";

    // Simpan ke database
    $sql = "INSERT INTO pinjaman (
                id_pinjaman, npp, tanggal_pengajuan, jumlah_pinjaman, tenor, cicilan_per_bulan, keterangan, status, tanggal_lunas
            ) VALUES (
                '$id_pinjaman', '$npp', '$tanggal_pengajuan', '$jumlah_pinjaman', '$tenor', '$cicilan', '$keterangan', '$status', '$tanggal_lunas'
            )";

    $res = mysqli_query($conn, $sql);

    if ($res) {
        // Generate jadwal angsuran otomatis
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
        
        header("Location: pinjaman.php?act=add&msg=success");
    } else {
        echo "Gagal menyimpan data: " . mysqli_error($conn);
    }
}
?>