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
$approved_by = isset($sess_admname) ? $sess_admname : 'Direktur';
$npp = isset($sess_admuser) ? $sess_admuser : '';

// Combine catatan from director
$catatan_direktur = $catatan;

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update permintaan_pengajuan - set status_app_direktur field
    $stmt = mysqli_prepare($conn, "UPDATE permintaan_pengajuan 
        SET status_app_direktur = ?
        WHERE id = ? AND id_permintaan = ?");
    mysqli_stmt_bind_param($stmt, 'sii', $keputusan, $pengajuan_id, $id_permintaan);
    $update_result = mysqli_stmt_execute($stmt);
    
    if (!$update_result) {
        throw new Exception('Gagal update status direktur: ' . mysqli_error($conn));
    }

    // If there's a note from director, update the catatan field to append it
    if (!empty($catatan_direktur)) {
        // Get existing catatan
        $stmt_get = mysqli_prepare($conn, "SELECT catatan FROM permintaan_pengajuan WHERE id = ?");
        mysqli_stmt_bind_param($stmt_get, 'i', $pengajuan_id);
        mysqli_stmt_execute($stmt_get);
        $result_get = mysqli_stmt_get_result($stmt_get);
        $row_get = mysqli_fetch_assoc($result_get);
        $existing_catatan = $row_get['catatan'] ?? '';
        
        // Append director's note
        $new_catatan = $existing_catatan;
        if (!empty($new_catatan)) {
            $new_catatan .= "\n\n";
        }
        $new_catatan .= "Catatan Direktur: " . $catatan_direktur;
        
        // Update catatan
        $stmt_update_note = mysqli_prepare($conn, "UPDATE permintaan_pengajuan SET catatan = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_update_note, 'si', $new_catatan, $pengajuan_id);
        mysqli_stmt_execute($stmt_update_note);
    }

    // Insert into history if table exists
    $history_sql = "INSERT INTO permintaan_history (permintaan_id, diubah_oleh, diubah_pada, catatan)
        VALUES (?, ?, NOW(), ?)";
    $note = "Direktur - " . $keputusan . ($catatan_direktur ? ": " . $catatan_direktur : "");
    $stmt_history = mysqli_prepare($conn, $history_sql);
    if ($stmt_history) {
        mysqli_stmt_bind_param($stmt_history, 'iss', $id_permintaan, $approved_by, $note);
        mysqli_stmt_execute($stmt_history);
    }

    // Commit transaction
    mysqli_commit($conn);

    // Set success message
    if ($keputusan === 'DISETUJUI') {
        $_SESSION['pesan'] = 'Permintaan karyawan berhasil disetujui oleh Direktur. Proses approval selesai.';
        $_SESSION['type_pesan'] = 'success';
    } else {
        $_SESSION['pesan'] = 'Permintaan karyawan ditolak oleh Direktur.';
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
