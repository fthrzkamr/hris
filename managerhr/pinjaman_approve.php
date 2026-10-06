<?php
include("sess_check.php");

if (isset($row_sess['status_karyawan']) && $row_sess['status_karyawan'] == 'Magang') {
	header("location: index.php");
	exit;
}

if (isset($_GET['id'])) {
    $id_pinjaman = $_GET['id'];
    
    // Fetch loan details to ensure it is in 'menunggu' state
    $sql = "SELECT * FROM pinjaman WHERE id_pinjaman = ? AND status = 'menunggu'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $id_pinjaman);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($res) > 0) {
        $data = mysqli_fetch_assoc($res);
        $npp = $data['npp'];
        $jumlah_pinjaman = $data['jumlah_pinjaman'];
        $tenor = $data['tenor'];
        $tanggal_pengajuan = $data['tanggal_pengajuan'];
        $cicilan = $data['cicilan_per_bulan'];
        
        // Calculate payment schedule dates
        $tgl_pengajuan_obj = new DateTime($tanggal_pengajuan);
        $hari_pengajuan = (int)$tgl_pengajuan_obj->format('d');
        
        if ($hari_pengajuan <= 26) {
            $tanggal_potong_pertama = $tgl_pengajuan_obj->format('Y-m') . '-26';
        } else {
            $tgl_pengajuan_obj->modify('+1 month');
            $tanggal_potong_pertama = $tgl_pengajuan_obj->format('Y-m') . '-26';
        }
        
        $tgl_lunas_obj = new DateTime($tanggal_potong_pertama);
        $tgl_lunas_obj->modify('+' . ($tenor - 1) . ' months');
        $tanggal_lunas = $tgl_lunas_obj->format('Y-m-d');
        
        // Update loan status to 'aktif' and set the tanggal_lunas
        $sql_update = "UPDATE pinjaman SET status = 'aktif', tanggal_lunas = ? WHERE id_pinjaman = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "ss", $tanggal_lunas, $id_pinjaman);
        $update_res = mysqli_stmt_execute($stmt_update);
        
        if ($update_res) {
            // Generate automatic installment schedules
            $tanggal_angsuran = new DateTime($tanggal_potong_pertama);
            
            for ($i = 1; $i <= $tenor; $i++) {
                $tgl_potong = $tanggal_angsuran->format('Y-m-d');
                $bulan = (int)$tanggal_angsuran->format('m');
                $tahun = (int)$tanggal_angsuran->format('Y');
                $id_angsuran = $id_pinjaman . '/' . str_pad($i, 2, '0', STR_PAD_LEFT);
                
                $sql_angsuran = "INSERT INTO angsuran_pinjaman 
                                (id_angsuran, id_pinjaman, bulan, tahun, jumlah_angsuran, status, tanggal_potong) 
                                VALUES 
                                ('$id_angsuran', '$id_pinjaman', $bulan, $tahun, $cicilan, 'belum', '$tgl_potong')";
                mysqli_query($conn, $sql_angsuran);
                
                $tanggal_angsuran->modify('+1 month');
            }
            
            header("Location: pinjaman.php?success=1");
            exit;
        }
    }
}
header("Location: pinjaman.php");
exit;
?>
