<?php
include("sess_check.php");
include("../dist/config/koneksi.php");
header('Content-Type: application/json');

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
        if (isset($r['nominal_rp']) && $r['nominal_rp'] !== null && $r['nominal_rp'] !== '') $settings[$k] = intval($r['nominal_rp']);
        elseif (isset($r['nilai_angka']) && $r['nilai_angka'] !== null && $r['nilai_angka'] !== '') $settings[$k] = intval($r['nilai_angka']);
    }
}
// Read rate per titik and caps from settings; provide sensible defaults
$rate = isset($settings['RATE_PER_TITIK_LEBIH']) ? intval($settings['RATE_PER_TITIK_LEBIH']) : 20000; // Rp per titik
$count_cap = isset($settings['BATAS_ATAS_BONUS_TITIK']) ? intval($settings['BATAS_ATAS_BONUS_TITIK']) : 0; // cap in jumlah titik (optional)
// amount cap setting names (try multiple common variations)
$amount_cap = 0;
if (isset($settings['MAX_BONUS_INSENTIF']) && intval($settings['MAX_BONUS_INSENTIF'])>0) $amount_cap = intval($settings['MAX_BONUS_INSENTIF']);

// Fetch existing row
$stmt = mysqli_prepare($conn, "SELECT id, bonus_insentif_full_masuk, uang_lembur, denda_telat, uang_makan, potongan_makan FROM transaksi_insentif_kurir WHERE npp = ? AND periode = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare gagal: '.mysqli_error($conn)]);
    exit();
}
mysqli_stmt_bind_param($stmt, 'ss', $npp, $periode);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Data transaksi tidak ditemukan untuk NPP/Periode tersebut']);
    exit();
}

$bonus_full = floatval($row['bonus_insentif_full_masuk'] ?? 0);
$uang_lembur = floatval($row['uang_lembur'] ?? 0);
$denda_telat = floatval($row['denda_telat'] ?? 0);
$uang_makan = floatval($row['uang_makan'] ?? 0);

// Calculate bonus from excess titik using configured rate and caps
$kelebihan = max(0, $aktual - $target);
$eligible_count = ($count_cap > 0) ? min($kelebihan, $count_cap) : $kelebihan;
$raw_bonus = $eligible_count * $rate;
$bonus_titik = ($amount_cap > 0) ? min($raw_bonus, $amount_cap) : $raw_bonus;

// Recalculate jumlah_dibayarkan: sum components (simplified)
$jumlah = $bonus_titik + $bonus_full + $uang_lembur + $uang_makan - $denda_telat;

// Update DB
$upd = mysqli_prepare($conn, "UPDATE transaksi_insentif_kurir SET total_titik = ?, target_titik = ?, bonus_insentif_titik = ?, jumlah_dibayarkan = ?, updated_at = NOW() WHERE npp = ? AND periode = ?");
if (!$upd) {
    echo json_encode(['success' => false, 'message' => 'Prepare update gagal: '.mysqli_error($conn)]);
    exit();
}
mysqli_stmt_bind_param($upd, 'iiddss', $aktual, $target, $bonus_titik, $jumlah, $npp, $periode);
$ok = mysqli_stmt_execute($upd);
if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Update gagal: '.mysqli_error($conn)]);
    exit();
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
