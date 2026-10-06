<?php
require_once __DIR__ . '/sess_check.php';
header('Content-Type: application/json');

// DB
require_once __DIR__ . '/dist/config/koneksi.php';

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

// basic authorization: allow users with session (adjust role checks as needed)
$user_display = $sess_admname ?? $sess_mngname ?? ($_SESSION['nama'] ?? ($_SESSION['user'] ?? 'system'));

// normalize numbers: remove non-digit
function normalize_number($v){
    if($v === null || $v === '') return '0';
    // remove anything except digits, minus, dot
    $clean = preg_replace('/[^0-9\-\.]/', '', $v);
    if($clean === '') return '0';
    // if contains dot as thousand sep, treat as integer string by removing dots
    $clean = str_replace(',', '.', $clean); // keep decimal dot
    // remove thousand separators if any (e.g., 1.000.000)
    $parts = explode('.', $clean);
    if(count($parts) > 2){
        // likely thousand separators, join all but last as integer
        $dec = array_pop($parts);
        $int = implode('', $parts);
        $clean = $int . '.' . $dec;
    }
    // return numeric string without formatting
    return $clean;
}

$nominal = normalize_number($nominal_raw);
$perkiraan = normalize_number($perkiraan_raw);

// compute used nominal and total
$used_nominal = ($nominal !== '0' && $nominal !== '') ? (float)$nominal : ((float)$perkiraan);
$total_val = $used_nominal * (float)$qty;
$total = (string) round($total_val);

// Validate numeric inputs
if ($nominal !== '' && !is_numeric($nominal)) {
    echo json_encode(['success' => false, 'error' => 'Nominal harus berupa angka']);
    exit;
}
if ($perkiraan !== '' && !is_numeric($perkiraan)) {
    echo json_encode(['success' => false, 'error' => 'Perkiraan harus berupa angka']);
    exit;
}
if (!is_int($qty) || $qty < 0) {
    echo json_encode(['success' => false, 'error' => 'Qty harus berupa angka bulat >= 0']);
    exit;
}

if ($perjalanan_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'perjalanan_id required']);
    exit;
}

mysqli_report(MYSQLI_REPORT_OFF);

// insert or update
if ($id > 0) {
    // fetch old row for log (include nomor)
    $stmt = mysqli_prepare($conn, "SELECT nomor, nominal, total, keterangan FROM perjalanan_rincian WHERE id = ? AND perjalanan_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $id, $perjalanan_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $old = mysqli_fetch_assoc($res);

    // log old values (only if found)
    if ($old) {
        $log_stmt = mysqli_prepare($conn, "INSERT INTO perjalanan_rincian_log (rincian_id, perjalanan_id, old_nominal, old_total, old_keterangan, changed_by, note) VALUES (?,?,?,?,?,?,?)");
        $note = 'Updated via UI by ' . $user_display;
        mysqli_stmt_bind_param($log_stmt, 'iisssss', $id, $perjalanan_id, $old['nominal'], $old['total'], $old['keterangan'], $user_display, $note);
        mysqli_stmt_execute($log_stmt);
    }

    $upd = mysqli_prepare($conn, "UPDATE perjalanan_rincian SET ket = ?, nominal = ?, qty = ?, perkiraan = ?, total = ?, keterangan = ?, last_revised_by = ?, last_revised_at = NOW(), revision_count = revision_count + 1 WHERE id = ? AND perjalanan_id = ?");
    mysqli_stmt_bind_param($upd, 'ssissssii', $ket, $nominal, $qty, $perkiraan, $total, $keterangan, $user_display, $id, $perjalanan_id);
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
        // return row info for frontend (use existing nomor)
        $resp = ['success' => true, 'budget_total' => $new_total, 'id' => $id, 'row' => [
            'id' => $id,
            'nomor' => isset($old['nomor']) ? $old['nomor'] : null,
            'ket' => $ket,
            'nominal' => $nominal,
            'qty' => $qty,
            'perkiraan' => $perkiraan,
            'total' => $total,
            'keterangan' => $keterangan
        ]];
        echo json_encode($resp);
    }
    else echo json_encode(['success' => false, 'error' => 'DB update failed']);
    exit;
} else {
    // determine next nomor
    $stmtn = mysqli_prepare($conn, "SELECT COALESCE(MAX(nomor),0) AS mx FROM perjalanan_rincian WHERE perjalanan_id = ?");
    mysqli_stmt_bind_param($stmtn, 'i', $perjalanan_id);
    mysqli_stmt_execute($stmtn);
    $resn = mysqli_stmt_get_result($stmtn);
    $rown = mysqli_fetch_assoc($resn);
    $next_nomor = intval($rown['mx']) + 1;

    $ins = mysqli_prepare($conn, "INSERT INTO perjalanan_rincian (perjalanan_id, nomor, ket, nominal, qty, perkiraan, total, keterangan, last_revised_by, last_revised_at, revision_count) VALUES (?,?,?,?,?,?,?,?,?,NOW(),1)");
    // types: i (perjalanan_id), i (nomor), s (ket), s (nominal), i (qty), s (perkiraan), s (total), s (keterangan), s (last_revised_by)
    // bind types: i (perjalanan_id), i (nomor), s (ket), s (nominal), i (qty), s (perkiraan), s (total), s (keterangan), s (last_revised_by)
    mysqli_stmt_bind_param($ins, 'iississss', $perjalanan_id, $next_nomor, $ket, $nominal, $qty, $perkiraan, $total, $keterangan, $user_display);
    $ok = mysqli_stmt_execute($ins);
    if ($ok) {
        // recalculate budget_total
        // use REPLACE+CAST to ensure varchar numeric values are aggregated correctly
        $sum_stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(CAST(REPLACE(REPLACE(total,'.',''),',','') AS DECIMAL(20,2))),0) AS sumtotal FROM perjalanan_rincian WHERE perjalanan_id = ?");
        mysqli_stmt_bind_param($sum_stmt, 'i', $perjalanan_id);
        mysqli_stmt_execute($sum_stmt);
        $sum_res = mysqli_stmt_get_result($sum_stmt);
        $sum_row = mysqli_fetch_assoc($sum_res);
        $new_total = (float)$sum_row['sumtotal'];
        $upd2 = mysqli_prepare($conn, "UPDATE perjalanan_dinas SET budget_total = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd2, 'di', $new_total, $perjalanan_id);
        mysqli_stmt_execute($upd2);
        // get inserted id
        $new_id = mysqli_insert_id($conn);
        $resp = ['success' => true, 'budget_total' => $new_total, 'id' => $new_id, 'row' => [
            'id' => $new_id,
            'nomor' => $next_nomor,
            'ket' => $ket,
            'nominal' => $nominal,
            'qty' => $qty,
            'perkiraan' => $perkiraan,
            'total' => $total,
            'keterangan' => $keterangan
        ]];
        echo json_encode($resp);
    }
    else echo json_encode(['success' => false, 'error' => 'DB insert failed']);
    exit;
}

?>
