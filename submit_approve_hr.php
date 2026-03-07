<?php
// Handler untuk submit approval Manager HR (single endpoint)
// Terima POST dari form managerhr/perjalanan_dinas_approve.php (aksi: approve_with_changes | revisi | reject)

include("sess_check.php");
include("dist/config/koneksi.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: perjalanan_dinas_list.php");
    exit;
}

$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
$aksi = $_POST['aksi'] ?? 'approve_with_changes';
$catatan = trim($_POST['catatan'] ?? '');
$nominals = $_POST['nominal'] ?? [];

$user_nama = $sess_admname ?? 'Manager HR';
$user_npp  = $sess_admuser ?? null;
$tgl_now = date('Y-m-d H:i:s');

if ($id <= 0) {
    header("Location: perjalanan_dinas_list.php?err=" . urlencode("ID tidak valid"));
    exit;
}

// update rincian jika ada perubahan (aksi approve_with_changes atau revisi)
if (in_array($aksi, ['approve_with_changes','revisi'])) {
    foreach ($nominals as $rid => $val) {
        $rid = intval($rid);
        $val_clean = preg_replace('/[^0-9]/','',$val);
        $nominal_new = $val_clean === '' ? '0' : $val_clean;

        // ambil nilai lama
        $stmtOld = mysqli_prepare($conn, "SELECT nominal, total, qty, keterangan FROM perjalanan_rincian WHERE id = ?");
        mysqli_stmt_bind_param($stmtOld, 'i', $rid);
        mysqli_stmt_execute($stmtOld);
        $resOld = mysqli_stmt_get_result($stmtOld);
        $old = mysqli_fetch_assoc($resOld);
        if (!$old) continue;

        $old_nominal = $old['nominal'] ?? '0';
        $qty = (float)($old['qty'] ?: 1);
        $old_total = $old['total'] ?? '0';

        // simpan log jika berubah
        if ($old_nominal !== $nominal_new) {
            $note_log = "Revisi nominal oleh {$user_nama}";
            $insLog = mysqli_prepare($conn, "INSERT INTO perjalanan_rincian_log (rincian_id, perjalanan_id, old_nominal, old_total, old_keterangan, changed_by, note) VALUES (?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($insLog, 'iisssss', $rid, $id, $old_nominal, $old_total, $old['keterangan'], $user_nama, $note_log);
            @mysqli_stmt_execute($insLog);
        }

        // update rincian
        $total_new = (string)((float)$nominal_new * $qty);
        $upd = mysqli_prepare($conn, "UPDATE perjalanan_rincian SET nominal = ?, total = ?, last_revised_by = ?, last_revised_at = NOW(), revision_count = revision_count + 1 WHERE id = ?");
        mysqli_stmt_bind_param($upd, 'sssi', $nominal_new, $total_new, $user_nama, $rid);
        mysqli_stmt_execute($upd);
    }

    // rekalkulasi budget_total
    $stmtSum = mysqli_prepare($conn, "SELECT SUM(CAST(REPLACE(total,',','') AS DECIMAL(20,2))) AS s FROM perjalanan_rincian WHERE perjalanan_id = ?");
    mysqli_stmt_bind_param($stmtSum, 'i', $id);
    mysqli_stmt_execute($stmtSum);
    $resSum = mysqli_stmt_get_result($stmtSum);
    $sumRow = mysqli_fetch_assoc($resSum);
    $budget_total = (float)($sumRow['s'] ?? 0);

    $updBudget = mysqli_prepare($conn, "UPDATE perjalanan_dinas SET budget_total = ? WHERE id = ?");
    mysqli_stmt_bind_param($updBudget, 'di', $budget_total, $id);
    mysqli_stmt_execute($updBudget);
}

// tentukan status dan nilai approval
if ($aksi === 'approve_with_changes' || $aksi === 'approve') {
    $status = 'APPROVED_HR';
    $approval_val = 'APPROVED';
} elseif ($aksi === 'revisi') {
    $status = 'DITOLAK';
    $approval_val = 'REVISI';
} elseif ($aksi === 'reject' || $aksi === 'refuse') {
    $status = 'DITOLAK';
    $approval_val = 'REJECTED';
} else {
    $status = 'DITOLAK';
    $approval_val = 'REJECTED';
}

// Simpan record perjalanan_pengajuan
// Gunakan nama kolom standar: approval_hr, approver_hr, tanggal_approval_hr, catatan_hr
$ins = mysqli_prepare($conn, "INSERT INTO perjalanan_pengajuan (id_perjalanan, npp, pengaju, tanggal_pengajuan, status, approval_hr, approver_hr, tanggal_approval_hr, catatan_hr) VALUES (?,?,?,?,?,?,?,?,?)");
mysqli_stmt_bind_param($ins, 'issssssss', $id, $user_npp, $user_nama, $tgl_now, $status, $approval_val, $user_nama, $tgl_now, $catatan);
$ok = mysqli_stmt_execute($ins);

if ($ok) {
    header("Location: perjalanan_dinas_list.php?msg=" . urlencode("Tindakan berhasil: $status"));
    exit;
} else {
    $err = mysqli_error($conn);
    header("Location: perjalanan_dinas_approve.php?id={$id}&err=" . urlencode("Gagal menyimpan: $err"));
    exit;
}
?>