<?php
ob_start();
require_once __DIR__ . '/sess_check.php';
if (is_file(__DIR__ . '/dist/config/koneksi.php')) {
    require_once __DIR__ . '/dist/config/koneksi.php';
} elseif (is_file(__DIR__ . '/../dist/config/koneksi.php')) {
    require_once __DIR__ . '/../dist/config/koneksi.php';
}
if (ob_get_length()) {
    ob_clean();
}
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$perjalanan_id = isset($_POST['perjalanan_id']) ? intval($_POST['perjalanan_id']) : 0;
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$ket = trim($_POST['ket'] ?? '');
$nominal_raw = trim($_POST['nominal'] ?? '0');
$qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;
$perkiraan_raw = trim($_POST['perkiraan'] ?? '0');
$keterangan = trim($_POST['keterangan'] ?? '');
$npp_karyawan = trim($_POST['npp_karyawan'] ?? '');
$nama_karyawan = trim($_POST['nama_karyawan'] ?? '');

if (!empty($npp_karyawan) && empty($nama_karyawan)) {
    $q_pk = mysqli_query($conn, "SELECT nama FROM perjalanan_karyawan WHERE perjalanan_id = $perjalanan_id AND npp = '" . mysqli_real_escape_string($conn, $npp_karyawan) . "' LIMIT 1");
    if ($q_pk && mysqli_num_rows($q_pk) > 0) {
        $nama_karyawan = mysqli_fetch_assoc($q_pk)['nama'];
    } else {
        $q_emp = mysqli_query($conn, "SELECT nama_emp FROM employee WHERE npp = '" . mysqli_real_escape_string($conn, $npp_karyawan) . "' LIMIT 1");
        if ($q_emp && mysqli_num_rows($q_emp) > 0) {
            $nama_karyawan = mysqli_fetch_assoc($q_emp)['nama_emp'];
        }
    }
}

// basic authorization: allow users with session
$user_display = $sess_mngname ?? ($sess_kryname ?? ($sess_spvname ?? ($sess_admname ?? ($sess_dirname ?? ($_SESSION['nama'] ?? 'HR')))));

// Normalize Rupiah / numeric input into clean integer string
function normalize_number($v) {
    if ($v === null || $v === '') return '0';
    $v = trim((string)$v);
    // Hapus desimal koma jika ada (mis. 500.000,00)
    $v = preg_replace('/,[0-9]{1,2}$/', '', $v);
    // Jika format float standar dari DB (mis. 500000.00 dengan 1 atau 2 digit desimal, bukan ribuan .000)
    if (preg_match('/^[0-9]+\.[0-9]{1,2}$/', $v)) {
        $v = explode('.', $v)[0];
    }
    // Hapus semua karakter selain angka (termasuk titik ribuan dan Rp)
    $clean = preg_replace('/[^0-9]/', '', $v);
    return $clean === '' ? '0' : $clean;
}

$nominal = normalize_number($nominal_raw);
$perkiraan = normalize_number($perkiraan_raw);

if ($qty < 1) {
    $qty = 1;
}

// Validate numeric inputs
if ($nominal !== '' && !is_numeric($nominal)) {
    echo json_encode(['success' => false, 'error' => 'Nominal harus berupa angka']);
    exit;
}
if ($perkiraan !== '' && !is_numeric($perkiraan)) {
    echo json_encode(['success' => false, 'error' => 'Perkiraan harus berupa angka']);
    exit;
}

if ($perjalanan_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'perjalanan_id required']);
    exit;
}

mysqli_report(MYSQLI_REPORT_OFF);

// insert or update
if ($id > 0) {
    // fetch old row for log (include nomor and perkiraan)
    $stmt = mysqli_prepare($conn, "SELECT nomor, nominal, perkiraan, total, keterangan FROM perjalanan_rincian WHERE id = ? AND perjalanan_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $id, $perjalanan_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $old = mysqli_fetch_assoc($res);

    if (!$old) {
        echo json_encode(['success' => false, 'error' => 'Item rincian tidak ditemukan']);
        exit;
    }

    // PATEN: Nominal yang diajukan (perkiraan awal) TIDAK BOLEH diubah saat edit
    $perkiraan = normalize_number($old['perkiraan']);
    $used_nominal = ($nominal !== '0' && $nominal !== '') ? (float)$nominal : ((float)$perkiraan);
    $total_val = $used_nominal * (float)$qty;
    $total = (string) round($total_val);

    // log old values
    $log_stmt = mysqli_prepare($conn, "INSERT INTO perjalanan_rincian_log (rincian_id, perjalanan_id, old_nominal, old_total, old_keterangan, changed_by, note) VALUES (?,?,?,?,?,?,?)");
    if ($log_stmt) {
        $note = 'Updated via UI by ' . $user_display;
        mysqli_stmt_bind_param($log_stmt, 'iisssss', $id, $perjalanan_id, $old['nominal'], $old['total'], $old['keterangan'], $user_display, $note);
        @mysqli_stmt_execute($log_stmt);
    }

    if (!empty($npp_karyawan)) {
        $upd = mysqli_prepare($conn, "UPDATE perjalanan_rincian SET ket = ?, nominal = ?, qty = ?, perkiraan = ?, total = ?, keterangan = ?, npp_karyawan = ?, nama_karyawan = ?, last_revised_by = ?, last_revised_at = NOW(), revision_count = revision_count + 1 WHERE id = ? AND perjalanan_id = ?");
        mysqli_stmt_bind_param($upd, 'ssissssssii', $ket, $nominal, $qty, $perkiraan, $total, $keterangan, $npp_karyawan, $nama_karyawan, $user_display, $id, $perjalanan_id);
    } else {
        $upd = mysqli_prepare($conn, "UPDATE perjalanan_rincian SET ket = ?, nominal = ?, qty = ?, perkiraan = ?, total = ?, keterangan = ?, last_revised_by = ?, last_revised_at = NOW(), revision_count = revision_count + 1 WHERE id = ? AND perjalanan_id = ?");
        mysqli_stmt_bind_param($upd, 'ssissssii', $ket, $nominal, $qty, $perkiraan, $total, $keterangan, $user_display, $id, $perjalanan_id);
    }
    $ok = mysqli_stmt_execute($upd);
    if ($ok) {
        // recalculate budget_total using safe CAST/REPLACE for varchar totals
        $sum_stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(CAST(REPLACE(REPLACE(total,'.',''),',','') AS DECIMAL(20,2))),0) AS sumtotal FROM perjalanan_rincian WHERE perjalanan_id = ?");
        mysqli_stmt_bind_param($sum_stmt, 'i', $perjalanan_id);
        mysqli_stmt_execute($sum_stmt);
        $sum_res = mysqli_stmt_get_result($sum_stmt);
        $sum_row = mysqli_fetch_assoc($sum_res);
        $new_total = (float)$sum_row['sumtotal'];
        $upd2 = mysqli_prepare($conn, "UPDATE perjalanan_dinas SET budget_total = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd2, 'di', $new_total, $perjalanan_id);
        mysqli_stmt_execute($upd2);

        $resp = ['success' => true, 'budget_total' => $new_total, 'id' => $id, 'row' => [
            'id' => $id,
            'nomor' => isset($old['nomor']) ? $old['nomor'] : null,
            'ket' => $ket,
            'nominal' => $nominal,
            'qty' => $qty,
            'perkiraan' => $perkiraan,
            'total' => $total,
            'keterangan' => $keterangan,
            'npp_karyawan' => $npp_karyawan,
            'nama_karyawan' => $nama_karyawan
        ]];
        echo json_encode($resp);
    } else {
        echo json_encode(['success' => false, 'error' => 'Gagal menyimpan perubahan: ' . mysqli_error($conn)]);
    }
    exit;
} else {
    $used_nominal = ($nominal !== '0' && $nominal !== '') ? (float)$nominal : ((float)$perkiraan);
    $total_val = $used_nominal * (float)$qty;
    $total = (string) round($total_val);

    // determine next nomor
    $stmtn = mysqli_prepare($conn, "SELECT COALESCE(MAX(nomor),0) AS mx FROM perjalanan_rincian WHERE perjalanan_id = ?");
    mysqli_stmt_bind_param($stmtn, 'i', $perjalanan_id);
    mysqli_stmt_execute($stmtn);
    $resn = mysqli_stmt_get_result($stmtn);
    $rown = mysqli_fetch_assoc($resn);
    $next_nomor = intval($rown['mx']) + 1;

    $ins = mysqli_prepare($conn, "INSERT INTO perjalanan_rincian (perjalanan_id, nomor, ket, nominal, qty, perkiraan, total, keterangan, kategori_biaya, npp_karyawan, nama_karyawan, last_revised_by, last_revised_at, revision_count) VALUES (?,?,?,?,?,?,?,?,'personal',?,?,?,NOW(),1)");
    mysqli_stmt_bind_param($ins, 'iississssss', $perjalanan_id, $next_nomor, $ket, $nominal, $qty, $perkiraan, $total, $keterangan, $npp_karyawan, $nama_karyawan, $user_display);
    $ok = mysqli_stmt_execute($ins);
    if ($ok) {
        // recalculate budget_total
        $sum_stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(CAST(REPLACE(REPLACE(total,'.',''),',','') AS DECIMAL(20,2))),0) AS sumtotal FROM perjalanan_rincian WHERE perjalanan_id = ?");
        mysqli_stmt_bind_param($sum_stmt, 'i', $perjalanan_id);
        mysqli_stmt_execute($sum_stmt);
        $sum_res = mysqli_stmt_get_result($sum_stmt);
        $sum_row = mysqli_fetch_assoc($sum_res);
        $new_total = (float)$sum_row['sumtotal'];
        $upd2 = mysqli_prepare($conn, "UPDATE perjalanan_dinas SET budget_total = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd2, 'di', $new_total, $perjalanan_id);
        mysqli_stmt_execute($upd2);

        $new_id = mysqli_insert_id($conn);
        $resp = ['success' => true, 'budget_total' => $new_total, 'id' => $new_id, 'row' => [
            'id' => $new_id,
            'nomor' => $next_nomor,
            'ket' => $ket,
            'nominal' => $nominal,
            'qty' => $qty,
            'perkiraan' => $perkiraan,
            'total' => $total,
            'keterangan' => $keterangan,
            'npp_karyawan' => $npp_karyawan,
            'nama_karyawan' => $nama_karyawan
        ]];
        echo json_encode($resp);
    } else {
        echo json_encode(['success' => false, 'error' => 'Gagal menambah item: ' . mysqli_error($conn)]);
    }
    exit;
}
