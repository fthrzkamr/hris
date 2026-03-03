<?php
include("sess_check.php");
include __DIR__ . '/dist/config/koneksi.php';

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: permintaan_karyawan_list.php');
    exit;
}

// Get form data
$id_permintaan = isset($_POST['id_permintaan']) ? intval($_POST['id_permintaan']) : 0;
$pengajuan_id = isset($_POST['pengajuan_id']) ? intval($_POST['pengajuan_id']) : 0;
$keputusan = isset($_POST['keputusan']) ? $_POST['keputusan'] : '';
$catatan = isset($_POST['catatan']) ? trim($_POST['catatan']) : '';

// Validate input
if ($id_permintaan <= 0 || $pengajuan_id <= 0 || empty($keputusan)) {
    $_SESSION['pesan'] = 'Data tidak lengkap';
    $_SESSION['type_pesan'] = 'danger';
    header('Location: permintaan_karyawan_list.php');
    exit;
}

// Validate keputusan
if (!in_array($keputusan, ['DISETUJUI', 'DITOLAK'])) {
    $_SESSION['pesan'] = 'Keputusan tidak valid';
    $_SESSION['type_pesan'] = 'danger';
    header('Location: permintaan_karyawan_list.php');
    exit;
}

// Get current user info
$approved_by = isset($sess_admname) ? $sess_admname : 'Manager HR';
$npp = isset($sess_admuser) ? $sess_admuser : '';

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update permintaan_pengajuan - set status field
    $stmt = mysqli_prepare($conn, "UPDATE permintaan_pengajuan 
        SET status = ?, catatan = ?
        WHERE id = ? AND id_permintaan = ?");
    mysqli_stmt_bind_param($stmt, 'ssii', $keputusan, $catatan, $pengajuan_id, $id_permintaan);
    $update_result = mysqli_stmt_execute($stmt);
    
    if (!$update_result) {
        throw new Exception('Gagal update status: ' . mysqli_error($conn));
    }

    // Insert into history if table exists
    $history_sql = "INSERT INTO permintaan_history (permintaan_id, diubah_oleh, diubah_pada, catatan)
        VALUES (?, ?, NOW(), ?)";
    $note = "Manager HR - " . $keputusan . ($catatan ? ": " . $catatan : "");
    $stmt_history = mysqli_prepare($conn, $history_sql);
    if ($stmt_history) {
        mysqli_stmt_bind_param($stmt_history, 'iss', $id_permintaan, $approved_by, $note);
        mysqli_stmt_execute($stmt_history);
    }

    // Commit transaction
    mysqli_commit($conn);

    // Set success message
    if ($keputusan === 'DISETUJUI') {
        $_SESSION['pesan'] = 'Permintaan karyawan berhasil disetujui. Akan diteruskan ke Direktur.';
        $_SESSION['type_pesan'] = 'success';
    } else {
        $_SESSION['pesan'] = 'Permintaan karyawan ditolak.';
        $_SESSION['type_pesan'] = 'warning';
    }

} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    $_SESSION['pesan'] = 'Terjadi kesalahan: ' . $e->getMessage();
    $_SESSION['type_pesan'] = 'danger';
}

// Redirect back to list
header('Location: permintaan_karyawan_list.php');
exit;
?>
