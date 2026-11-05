<?php
include('sess_check.php');
if (is_file(__DIR__ . '/dist/config/koneksi.php')) {
    include_once __DIR__ . '/dist/config/koneksi.php';
} elseif (is_file(__DIR__ . '/../dist/config/koneksi.php')) {
    include_once __DIR__ . '/../dist/config/koneksi.php';
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$from = isset($_GET['from']) ? $_GET['from'] : '';

// Tentukan url redirect tujuan
$redirect_url = 'perjalanan_dinas_list.php';
if ($from === 'saya') {
    $redirect_url = 'perjalanan_dinas_list.php?tab=saya';
} elseif ($from === 'pending') {
    $redirect_url = 'perjalanan_dinas_list.php?tab=pending';
} elseif ($from === 'semua') {
    $redirect_url = 'perjalanan_dinas_list.php?tab=semua';
}

if ($id <= 0) {
    $sep = (strpos($redirect_url, '?') !== false) ? '&' : '?';
    header("Location: " . $redirect_url . $sep . "err=" . urlencode("ID perjalanan tidak valid"));
    exit;
}

// NPP user yang sedang login
$current_user_npp = $sess_mngid ?? ($sess_kryid ?? ($sess_spvid ?? ($sess_admid ?? ($sess_dirid ?? ($_SESSION['npp'] ?? null)))));

// Ambil data perjalanan dan status terakhirnya
$stmt = mysqli_prepare($conn, "SELECT p.id, p.no_dokumen, p.npp, p.nama, 
    (SELECT status FROM perjalanan_pengajuan WHERE id_perjalanan = p.id ORDER BY id DESC LIMIT 1) AS status 
    FROM perjalanan_dinas p WHERE p.id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);

if (!$row) {
    $sep = (strpos($redirect_url, '?') !== false) ? '&' : '?';
    header("Location: " . $redirect_url . $sep . "err=" . urlencode("Data perjalanan dinas tidak ditemukan"));
    exit;
}

$status = $row['status'] ?? 'DIAJUKAN';
$status_upper = strtoupper(trim($status));

// Batasan Status: Hanya boleh dihapus jika belum disetujui (status DIAJUKAN, BELUM DIAJUKAN, atau DITOLAK)
$allowed_statuses = ['DIAJUKAN', 'BELUM DIAJUKAN', 'DITOLAK'];
if (!in_array($status_upper, $allowed_statuses)) {
    $sep = (strpos($redirect_url, '?') !== false) ? '&' : '?';
    header("Location: " . $redirect_url . $sep . "err=" . urlencode("Pengajuan perjalanan dinas ini sudah diproses/disetujui ($status) dan tidak dapat dihapus"));
    exit;
}

// Cek hak akses: Hanya Pengaju Utama (pemohon), Manager HR, atau Admin yang boleh menghapus
$is_pemohon = ($row['npp'] === $current_user_npp);
$is_admin_or_mng = isset($_SESSION['managerhr']) || isset($_SESSION['admin']) || isset($_SESSION['direktur']);

if (!$is_pemohon && !$is_admin_or_mng) {
    $sep = (strpos($redirect_url, '?') !== false) ? '&' : '?';
    header("Location: " . $redirect_url . $sep . "err=" . urlencode("Anda tidak memiliki izin untuk menghapus pengajuan perjalanan dinas ini"));
    exit;
}

// Mulai proses penghapusan
mysqli_begin_transaction($conn);
try {
    // 1. Hapus riwayat log rincian
    mysqli_query($conn, "DELETE FROM perjalanan_rincian_log WHERE perjalanan_id = $id");

    // 2. Hapus rincian biaya
    mysqli_query($conn, "DELETE FROM perjalanan_rincian WHERE perjalanan_id = $id");

    // 3. Hapus data rekan/karyawan
    mysqli_query($conn, "DELETE FROM perjalanan_karyawan WHERE perjalanan_id = $id");

    // 4. Hapus riwayat pengajuan flow approval
    mysqli_query($conn, "DELETE FROM perjalanan_pengajuan WHERE id_perjalanan = $id");

    // 5. Hapus header utama perjalanan dinas
    $del = mysqli_query($conn, "DELETE FROM perjalanan_dinas WHERE id = $id");
    if (!$del) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_commit($conn);
    $sep = (strpos($redirect_url, '?') !== false) ? '&' : '?';
    header("Location: " . $redirect_url . $sep . "msg=" . urlencode("Pengajuan perjalanan dinas (" . $row['no_dokumen'] . ") berhasil dihapus"));
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    $sep = (strpos($redirect_url, '?') !== false) ? '&' : '?';
    header("Location: " . $redirect_url . $sep . "err=" . urlencode("Gagal menghapus pengajuan: " . $e->getMessage()));
    exit;
}
