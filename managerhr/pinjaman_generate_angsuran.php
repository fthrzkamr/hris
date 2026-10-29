<?php
include("sess_check.php");

echo "<h2>Generate Jadwal Angsuran untuk Data Pinjaman yang Ada</h2>";
echo "<p>Script ini akan membuat jadwal angsuran otomatis untuk semua pinjaman yang belum memiliki jadwal.</p>";
echo "<hr>";

// Ambil semua data pinjaman
$sql_pinjaman = "SELECT * FROM pinjaman ORDER BY tanggal_pengajuan ASC";
$res_pinjaman = mysqli_query($conn, $sql_pinjaman);

$total_processed = 0;
$total_angsuran_created = 0;

while ($pinjaman = mysqli_fetch_array($res_pinjaman)) {
    $id_pinjaman = $pinjaman['id_pinjaman'];
    $tanggal_pengajuan = $pinjaman['tanggal_pengajuan'];
    $tenor = $pinjaman['tenor'];
    $cicilan = $pinjaman['cicilan_per_bulan'];
    
    // Cek apakah sudah ada angsuran
    $sql_check = "SELECT COUNT(*) as total FROM angsuran_pinjaman WHERE id_pinjaman = '$id_pinjaman'";
    $res_check = mysqli_query($conn, $sql_check);
    $check_data = mysqli_fetch_assoc($res_check);
    
    if ($check_data['total'] > 0) {
        echo "<p style='color:orange;'>⚠️ $id_pinjaman - Sudah ada {$check_data['total']} angsuran (SKIP)</p>";
        continue;
    }
    
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
    
    echo "<p><strong>🔄 Processing: $id_pinjaman</strong></p>";
    echo "<ul>";
    echo "<li>Tanggal Pengajuan: $tanggal_pengajuan (Hari ke-$hari_pengajuan)</li>";
    echo "<li>Tanggal Potong Pertama: $tanggal_potong_pertama</li>";
    echo "<li>Tenor: $tenor bulan</li>";
    echo "<li>Cicilan: Rp " . number_format($cicilan, 0, ',', '.') . "</li>";
    echo "</ul>";
    
    // Generate jadwal angsuran
    $tanggal_angsuran = new DateTime($tanggal_potong_pertama);
    $today = date('Y-m-d');
    $angsuran_created = 0;
    $angsuran_auto_paid = 0;
    
    echo "<table border='1' cellpadding='5' style='margin-bottom:20px; border-collapse:collapse;'>";
    echo "<tr style='background:#f0f0f0;'>";
    echo "<th>No</th><th>ID Angsuran</th><th>Bulan</th><th>Tahun</th><th>Tanggal Potong</th><th>Jumlah</th><th>Status</th>";
    echo "</tr>";
    
    for ($i = 1; $i <= $tenor; $i++) {
        $tgl_potong = $tanggal_angsuran->format('Y-m-d');
        $bulan = (int)$tanggal_angsuran->format('m');
        $tahun = (int)$tanggal_angsuran->format('Y');
        
        // Generate ID Angsuran
        $id_angsuran = $id_pinjaman . '/' . str_pad($i, 2, '0', STR_PAD_LEFT);
        
        // Tentukan status: jika tanggal potong sudah lewat, otomatis 'dibayar'
        $status = ($tgl_potong <= $today) ? 'dibayar' : 'belum';
        if ($status == 'dibayar') {
            $angsuran_auto_paid++;
        }
        
        // Insert ke tabel angsuran_pinjaman
        $sql_angsuran = "INSERT INTO angsuran_pinjaman 
                        (id_angsuran, id_pinjaman, bulan, tahun, jumlah_angsuran, status, tanggal_potong) 
                        VALUES 
                        ('$id_angsuran', '$id_pinjaman', $bulan, $tahun, $cicilan, '$status', '$tgl_potong')";
        
        if (mysqli_query($conn, $sql_angsuran)) {
            $angsuran_created++;
            $total_angsuran_created++;
            
            $bulan_nama = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            $status_color = ($status == 'dibayar') ? 'green' : 'red';
            $status_text = ($status == 'dibayar') ? 'LUNAS ✓' : 'BELUM';
            
            echo "<tr>";
            echo "<td style='text-align:center;'>$i</td>";
            echo "<td>$id_angsuran</td>";
            echo "<td style='text-align:center;'>{$bulan_nama[$bulan]}</td>";
            echo "<td style='text-align:center;'>$tahun</td>";
            echo "<td style='text-align:center;'>$tgl_potong</td>";
            echo "<td style='text-align:right;'>Rp " . number_format($cicilan, 0, ',', '.') . "</td>";
            echo "<td style='text-align:center; color:$status_color;'><strong>$status_text</strong></td>";
            echo "</tr>";
        } else {
            echo "<tr><td colspan='7' style='color:red;'>ERROR: " . mysqli_error($conn) . "</td></tr>";
        }
        
        // Tambah 1 bulan untuk angsuran berikutnya (selalu tanggal 26)
        $tanggal_angsuran->modify('+1 month');
    }
    
    echo "</table>";
    
    if ($angsuran_auto_paid > 0) {
        echo "<p style='color:green;'>✅ <strong>$angsuran_auto_paid angsuran otomatis ditandai LUNAS</strong> (tanggal potong sudah lewat)</p>";
    }
    
    echo "<p style='color:green;'>✅ Berhasil membuat $angsuran_created angsuran untuk pinjaman $id_pinjaman</p>";
    echo "<hr>";
    
    $total_processed++;
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
$result_lunas = mysqli_query($conn, $sql_check_lunas);
$pinjaman_lunas = mysqli_affected_rows($conn);

echo "<h3 style='color:blue;'>📊 SUMMARY</h3>";
echo "<ul>";
echo "<li>Total Pinjaman Diproses: <strong>$total_processed</strong></li>";
echo "<li>Total Angsuran Dibuat: <strong>$total_angsuran_created</strong></li>";
if ($pinjaman_lunas > 0) {
    echo "<li style='color:green;'>Pinjaman yang Otomatis Lunas: <strong>$pinjaman_lunas</strong></li>";
}
echo "</ul>";

echo "<br><br>";
echo "<p><a href='pinjaman.php' style='padding:10px 20px; background:#337ab7; color:white; text-decoration:none; border-radius:4px;'>← Kembali ke Daftar Pinjaman</a></p>";
?>
