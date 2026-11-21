<?php
include('sess_check.php');
if (is_file(__DIR__ . '/dist/config/koneksi.php')) {
    include_once __DIR__ . '/dist/config/koneksi.php';
} elseif (is_file(__DIR__ . '/../dist/config/koneksi.php')) {
    include_once __DIR__ . '/../dist/config/koneksi.php';
}

$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
$from = isset($_GET['from']) ? $_GET['from'] : (isset($_POST['from']) ? $_POST['from'] : 'semua');

// Tentukan url redirect tujuan
$redirect_url = 'perjalanan_dinas_list.php';
if ($from === 'saya') {
    $redirect_url = 'perjalanan_dinas_list.php?tab=saya';
} elseif ($from === 'pending') {
    $redirect_url = 'perjalanan_dinas_list.php?tab=pending';
} elseif ($from === 'semua') {
    $redirect_url = 'perjalanan_dinas_list.php?tab=semua';
} elseif ($from === 'detail') {
    $redirect_url = 'perjalanan_dinas_detail.php?id=' . $id;
}

if ($id <= 0) {
    $sep = (strpos($redirect_url, '?') !== false) ? '&' : '?';
    header("Location: " . $redirect_url . $sep . "err=" . urlencode("ID perjalanan tidak valid"));
    exit;
}

// Cek hak akses: Hanya Manager HR atau Admin yang boleh membatalkan approval
$is_admin_or_mng = isset($_SESSION['managerhr']) || isset($_SESSION['admin']);
if (!$is_admin_or_mng) {
    $sep = (strpos($redirect_url, '?') !== false) ? '&' : '?';
    header("Location: " . $redirect_url . $sep . "err=" . urlencode("Anda tidak memiliki izin untuk membatalkan approval ini"));
    exit;
}

$user_nama = $sess_mngname ?? ($sess_admname ?? 'Manager HR');

// Ambil pengajuan terakhir untuk perjalanan ini
$stmt = mysqli_prepare($conn, "SELECT p.id, p.no_dokumen, pp.id AS pengajuan_id, pp.status, pp.catatan_manager_hr, pp.approval_manager_hr, pp.approval_direktur 
    FROM perjalanan_dinas p 
    INNER JOIN (
        SELECT p1.id, p1.id_perjalanan, p1.status, p1.catatan_manager_hr, p1.approval_manager_hr, p1.approval_direktur 
        FROM perjalanan_pengajuan p1 
        INNER JOIN (
            SELECT id_perjalanan, MAX(id) AS max_id FROM perjalanan_pengajuan GROUP BY id_perjalanan
        ) p_max ON p1.id_perjalanan = p_max.id_perjalanan AND p1.id = p_max.max_id
    ) pp ON p.id = pp.id_perjalanan 
    WHERE p.id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);

if (!$row) {
    $sep = (strpos($redirect_url, '?') !== false) ? '&' : '?';
    header("Location: " . $redirect_url . $sep . "err=" . urlencode("Data perjalanan dinas tidak ditemukan"));
    exit;
}

$status = $row['status'] ?? '';
$pengajuan_id = (int)$row['pengajuan_id'];

// Tentukan status kembalian
// Catatan: REALISASI_SELESAI sengaja TIDAK bisa dikembalikan lewat sini, karena status realisasi
// sudah dilacak per-karyawan (perjalanan_karyawan.status_realisasi) dan sudah dikunci final per orang;
// membalikkan status di level trip saja akan membuatnya tidak sinkron dengan status per-karyawan.
$target_status = '';
if ($status === 'APPROVED_MANAGER_HR' || $status === 'DISETUJUI') {
    $target_status = 'DIAJUKAN';
} else {
    $sep = (strpos($redirect_url, '?') !== false) ? '&' : '?';
    header("Location: " . $redirect_url . $sep . "err=" . urlencode("Pengajuan ini tidak berstatus approval yang dapat dikembalikan ($status)"));
    exit;
}

// Mulai transaksi
mysqli_begin_transaction($conn);
try {
    $audit_note = " [Approval dibatalkan oleh $user_nama pada " . date('d-m-Y H:i') . "]";
    $new_catatan = trim(($row['catatan_manager_hr'] ?? '') . $audit_note);

    $upd = mysqli_prepare($conn, "UPDATE perjalanan_pengajuan
        SET status = 'DIAJUKAN',
            approval_manager_hr = NULL,
            approver_manager_hr = NULL,
            tanggal_approval_manager_hr = NULL,
            approval_direktur = NULL,
            approver_direktur = NULL,
            tanggal_approval_direktur = NULL,
            catatan_manager_hr = ?
        WHERE id = ?");
    mysqli_stmt_bind_param($upd, 'si', $new_catatan, $pengajuan_id);

    $ok = mysqli_stmt_execute($upd);
    if (!$ok) {
        throw new Exception(mysqli_error($conn));
    }

    // Catat log audit
    $log_stmt = mysqli_prepare($conn, "INSERT INTO perjalanan_rincian_log (rincian_id, perjalanan_id, old_nominal, old_total, old_keterangan, changed_by, note) VALUES (0, ?, '0', '0', '', ?, ?)");
    $log_note = "Approval perjalanan dinas no. " . $row['no_dokumen'] . " dibatalkan oleh $user_nama dan status dikembalikan ke " . $target_status;
    mysqli_stmt_bind_param($log_stmt, 'iss', $id, $user_nama, $log_note);
    @mysqli_stmt_execute($log_stmt);

    mysqli_commit($conn);
    $sep = (strpos($redirect_url, '?') !== false) ? '&' : '?';
    header("Location: " . $redirect_url . $sep . "msg=" . urlencode("Approval pengajuan no. " . $row['no_dokumen'] . " berhasil dibatalkan. Status dikembalikan ke " . $target_status . "."));
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    $sep = (strpos($redirect_url, '?') !== false) ? '&' : '?';
    header("Location: " . $redirect_url . $sep . "err=" . urlencode("Gagal membatalkan approval: " . $e->getMessage()));
    exit;
}
?>
