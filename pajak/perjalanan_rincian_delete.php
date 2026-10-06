<?php
require_once __DIR__ . '/sess_check.php';
header('Content-Type: application/json');

require_once __DIR__ . '/dist/config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// accept id in various formats and sanitize
$id_raw = isset($_POST['id']) ? $_POST['id'] : (isset($_POST['rid']) ? $_POST['rid'] : '');
$id_clean = preg_replace('/[^0-9]/', '', (string)$id_raw);
$id = $id_clean === '' ? 0 : intval($id_clean);
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid id']);
    exit;
}

// fetch row
$stmt = mysqli_prepare($conn, "SELECT perjalanan_id, nominal, total, keterangan FROM perjalanan_rincian WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
if (!$row) {
    echo json_encode(['success' => false, 'error' => 'Item not found']);
    exit;
}

$perjalanan_id = intval($row['perjalanan_id']);
$user_display = $sess_admname ?? $sess_mngname ?? ($_SESSION['nama'] ?? ($_SESSION['user'] ?? 'system'));

// insert log
$note = 'Deleted via UI by ' . $user_display;
$log_stmt = mysqli_prepare($conn, "INSERT INTO perjalanan_rincian_log (rincian_id, perjalanan_id, old_nominal, old_total, old_keterangan, changed_by, note) VALUES (?,?,?,?,?,?,?)");
mysqli_stmt_bind_param($log_stmt, 'iisssss', $id, $perjalanan_id, $row['nominal'], $row['total'], $row['keterangan'], $user_display, $note);
mysqli_stmt_execute($log_stmt);

// delete
$del = mysqli_prepare($conn, "DELETE FROM perjalanan_rincian WHERE id = ? AND perjalanan_id = ?");
mysqli_stmt_bind_param($del, 'ii', $id, $perjalanan_id);
$ok = mysqli_stmt_execute($del);
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
    echo json_encode(['success' => true, 'budget_total' => $new_total]);
} else echo json_encode(['success' => false, 'error' => 'DB delete failed']);

?>
