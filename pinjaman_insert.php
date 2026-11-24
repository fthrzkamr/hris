<?php
include("sess_check.php");

if (isset($row_sess['status_karyawan']) && $row_sess['status_karyawan'] == 'Magang') {
	header("location: index.php");
	exit;
}

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

    // Pastikan kolom tanggal approval HRD tersedia
    $chk_col = mysqli_query($conn, "SHOW COLUMNS FROM pinjaman LIKE 'tanggal_approval_hrd'");
    if ($chk_col && mysqli_num_rows($chk_col) == 0) {
        mysqli_query($conn, "ALTER TABLE pinjaman ADD COLUMN tanggal_approval_hrd DATE NULL");
    }

    // Input langsung oleh HR (status aktif) = dianggap disetujui pada tanggal pengajuan yang diinput.
    // Aturan potongan berdasarkan tanggal approve HRD:
    // tanggal 1-15 -> potong tanggal 26 bulan berjalan, tanggal 16-31 -> tanggal 26 bulan berikutnya
    $tanggal_approval_hrd = $tanggal_pengajuan;
    $tgl_approve_obj = new DateTime($tanggal_approval_hrd);
    if ((int)$tgl_approve_obj->format('d') > 15) {
        $tgl_approve_obj->modify('first day of next month');
    }
    $tanggal_potong_pertama = $tgl_approve_obj->format('Y-m') . '-26';
    
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
                id_pinjaman, npp, tanggal_pengajuan, jumlah_pinjaman, tenor, cicilan_per_bulan, keterangan, status, tanggal_lunas, tanggal_approval_hrd
            ) VALUES (
                '$id_pinjaman', '$npp', '$tanggal_pengajuan', '$jumlah_pinjaman', '$tenor', '$cicilan', '$keterangan', '$status', '$tanggal_lunas', '$tanggal_approval_hrd'
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