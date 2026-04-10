<?php
include("sess_check.php");
include("dist/config/koneksi.php");
header('Content-Type: application/json');

try {
    $input = $_POST;
    $npp = isset($input['npp']) ? trim($input['npp']) : '';
    $periode = isset($input['periode']) ? trim($input['periode']) : '';
    $aktual = isset($input['aktual']) ? intval($input['aktual']) : 0;
    $target = isset($input['target']) ? intval($input['target']) : 0;

    if ($npp === '' || $periode === '') {
        echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
        exit();
    }

    // Load settings for calculation
    $settings = [];
    $rs = mysqli_query($conn, "SELECT nama_variabel, nilai_angka, nominal_rp FROM pengaturan_insentif_kurir");
    if ($rs) {
        while ($r = mysqli_fetch_assoc($rs)) {
            $k = strtoupper(trim($r['nama_variabel']));
            if (isset($r['nominal_rp']) && $r['nominal_rp'] !== null && $r['nominal_rp'] !== '') 
                $settings[$k] = intval($r['nominal_rp']);
            elseif (isset($r['nilai_angka']) && $r['nilai_angka'] !== null && $r['nilai_angka'] !== '') 
                $settings[$k] = intval($r['nilai_angka']);
        }
    }

    // Read rate per titik and caps from settings
    $rate = isset($settings['RATE_PER_TITIK_LEBIH']) ? intval($settings['RATE_PER_TITIK_LEBIH']) : 20000;
    $count_cap = isset($settings['BATAS_ATAS_BONUS_TITIK']) ? intval($settings['BATAS_ATAS_BONUS_TITIK']) : 0;
    $amount_cap = isset($settings['MAX_BONUS_INSENTIF']) ? intval($settings['MAX_BONUS_INSENTIF']) : 0;

    // Fetch existing row - use direct query
    $npp_esc = mysqli_real_escape_string($conn, $npp);
    $periode_esc = mysqli_real_escape_string($conn, $periode);
    
    $query = "SELECT id, bonus_insentif_full_masuk, uang_lembur, denda_telat, uang_makan, potongan_makan, hari_hadir 
              FROM transaksi_insentif_kurir 
              WHERE npp = '$npp_esc' AND periode = '$periode_esc' 
              LIMIT 1";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception('Query gagal: ' . mysqli_error($conn));
    }
    
    $row = mysqli_fetch_assoc($result);
    
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        exit();
    }

    $bonus_full = floatval($row['bonus_insentif_full_masuk'] ?? 0);
    $uang_lembur = floatval($row['uang_lembur'] ?? 0);
    $denda_telat = floatval($row['denda_telat'] ?? 0);
    $uang_makan = floatval($row['uang_makan'] ?? 0);
    $hari_hadir = intval($row['hari_hadir'] ?? 0);

    // Calculate bonus from excess titik
    $kelebihan = max(0, $aktual - $target);
    $eligible_count = ($count_cap > 0) ? min($kelebihan, $count_cap) : $kelebihan;
    $raw_bonus = $eligible_count * $rate;
    $bonus_titik = ($amount_cap > 0) ? min($raw_bonus, $amount_cap) : $raw_bonus;

    // Recalculate jumlah_dibayarkan sesuai frontend formula:
    // total = uang_makan + bonus_titik + bonus_full + uang_lembur - denda_telat
    $jumlah = intval($uang_makan + $bonus_titik + $bonus_full + $uang_lembur - $denda_telat);
    // Clamp negatif jadi 0
    if ($jumlah < 0) $jumlah = 0;

    // Update DB
    $update_query = "UPDATE transaksi_insentif_kurir 
                     SET total_titik = $aktual, 
                         target_titik = $target, 
                         bonus_insentif_titik = $bonus_titik, 
                         jumlah_dibayarkan = $jumlah, 
                         updated_at = NOW() 
                     WHERE npp = '$npp_esc' AND periode = '$periode_esc'";
    
    $update_result = mysqli_query($conn, $update_query);
    
    if (!$update_result) {
        throw new Exception('Update gagal: ' . mysqli_error($conn));
    }

    // Return updated values
    echo json_encode([
        'success' => true,
        'npp' => $npp,
        'periode' => $periode,
        'total_titik' => $aktual,
        'target_titik' => $target,
        'kelebihan' => $kelebihan,
        'bonus_insentif_titik' => $bonus_titik,
        'jumlah_dibayarkan' => $jumlah
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>