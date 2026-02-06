<?php
// Script otomatis memproses pembayaran angsuran yang sudah jatuh tempo
// Dijalankan setiap kali halaman pinjaman.php dibuka
// Note: $conn sudah tersedia dari sess_check.php yang di-include di parent file

$today = date('Y-m-d');

// Update status angsuran yang tanggal_potong sudah lewat atau hari ini
$sql_update = "UPDATE angsuran_pinjaman 
               SET status = 'dibayar' 
               WHERE tanggal_potong <= '$today' 
               AND status = 'belum'";

$result = mysqli_query($conn, $sql_update);

if ($result) {
    $rows_affected = mysqli_affected_rows($conn);
    
    if ($rows_affected > 0) {
        // Ada angsuran yang baru saja diproses
        // Simpan log atau notifikasi jika diperlukan
        $_SESSION['auto_payment_processed'] = $rows_affected;
    }
}

// Update status pinjaman menjadi 'lunas' jika semua angsuran sudah dibayar
$sql_check_lunas = "UPDATE pinjaman p
                    SET p.status = 'lunas'
                    WHERE p.status = 'aktif'
                    AND NOT EXISTS (
                        SELECT 1 FROM angsuran_pinjaman a 
                        WHERE a.id_pinjaman = p.id_pinjaman 
                        AND a.status = 'belum'
                    )
                    AND EXISTS (
                        SELECT 1 FROM angsuran_pinjaman a 
                        WHERE a.id_pinjaman = p.id_pinjaman
                    )";

mysqli_query($conn, $sql_check_lunas);

?>
