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

    // Dasar jadwal potongan = tanggal approve HRD (bukan tanggal pengajuan).
    // Jika pinjaman sudah punya tanggal approve tersimpan atau diinput di form, pakai itu.
    $tanggal_approval_hrd = $tanggal_pengajuan;
    if (isset($_POST['tanggal_approval_hrd']) && !empty($_POST['tanggal_approval_hrd'])) {
        $tanggal_approval_hrd = $_POST['tanggal_approval_hrd'];
    } else {
        $chk_col = mysqli_query($conn, "SHOW COLUMNS FROM pinjaman LIKE 'tanggal_approval_hrd'");
        if ($chk_col && mysqli_num_rows($chk_col) > 0) {
            $id_esc = mysqli_real_escape_string($conn, $id_pinjaman);
            $r_app = mysqli_fetch_assoc(mysqli_query($conn, "SELECT tanggal_approval_hrd FROM pinjaman WHERE id_pinjaman = '$id_esc'"));
            if (!empty($r_app['tanggal_approval_hrd'])) {
                $tanggal_approval_hrd = $r_app['tanggal_approval_hrd'];
            }
        }
    }
    // tanggal 1-15 -> potong tanggal 26 bulan berjalan, tanggal 16-31 -> tanggal 26 bulan berikutnya
    $tgl_approve_obj = new DateTime($tanggal_approval_hrd);
    if ((int)$tgl_approve_obj->format('d') > 15) {
        $tgl_approve_obj->modify('first day of next month');
    }
    $tanggal_potong_pertama = $tgl_approve_obj->format('Y-m') . '-26';
    
    // Hitung tanggal lunas (tanggal 26 di bulan tenor terakhir)
    $tgl_lunas_obj = new DateTime($tanggal_potong_pertama);
    $tgl_lunas_obj->modify('+' . ($tenor - 1) . ' months');
    $tanggal_lunas = $tgl_lunas_obj->format('Y-m-d');

    // Update data pinjaman
    $sql = "UPDATE pinjaman SET 
                tanggal_pengajuan = '$tanggal_pengajuan',
                tanggal_approval_hrd = '$tanggal_approval_hrd',
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
