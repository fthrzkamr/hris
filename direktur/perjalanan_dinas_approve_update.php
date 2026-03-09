<?php
include('sess_check.php');
include('dist/config/koneksi.php');

// Ensure request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perjalanan_dinas_list.php');
    exit;
}

$id_perjalanan = isset($_POST['id_perjalanan']) ? intval($_POST['id_perjalanan']) : 0;
$approval_type = isset($_POST['approval_type']) ? $_POST['approval_type'] : '';
$decision = isset($_POST['decision']) ? $_POST['decision'] : '';

if ($id_perjalanan <= 0 || empty($approval_type) || empty($decision)) {
    header('Location: perjalanan_dinas_list.php?error=' . urlencode('Data tidak valid.'));
    exit;
}

// Get approver name from session - direktur
$approver = isset($sess_mngname) ? $sess_mngname : (isset($sess_admname) ? $sess_admname : 'DIREKTUR');

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Fetch current submission record
    $stmt = mysqli_prepare($conn, "SELECT * FROM perjalanan_pengajuan WHERE id_perjalanan = ? ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id_perjalanan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pengajuan = mysqli_fetch_assoc($result);
    
    if (!$pengajuan) {
        throw new Exception('Data pengajuan tidak ditemukan.');
    }
    
    $pengajuan_id = $pengajuan['id'];
    $current_status = $pengajuan['status'];
    
    if ($approval_type == 'direktur') {
        // Director Approval - only director can approve here
        if ($current_status != 'APPROVED_MANAGER_HR') {
            throw new Exception('Status pengajuan tidak valid untuk approval direktur.');
        }
        
        $catatan_direktur = isset($_POST['catatan_direktur']) ? mysqli_real_escape_string($conn, $_POST['catatan_direktur']) : '';
        
        // Update pengajuan with Director approval
        $new_status = ($decision == 'APPROVED') ? 'DISETUJUI' : 'DITOLAK';
        
        $sql_update = "UPDATE perjalanan_pengajuan 
                       SET approval_direktur = ?, 
                           approver_direktur = ?, 
                           tanggal_approval_direktur = NOW(),
                           catatan_direktur = ?,
                           status = ?
                       WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, 'ssssi', $decision, $approver, $catatan_direktur, $new_status, $pengajuan_id);
        
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception('Gagal mengupdate approval direktur.');
        }
        
        $message = ($decision == 'APPROVED') ? 'Pengajuan berhasil disetujui oleh Direktur.' : 'Pengajuan ditolak oleh Direktur.';
        
    } else {
        throw new Exception('Tipe approval tidak valid untuk direktur.');
    }
    
    mysqli_commit($conn);
    header('Location: perjalanan_dinas_list.php?success=' . urlencode($message));
    exit;
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log($e->getMessage());
    header('Location: perjalanan_dinas_approve.php?id=' . $id_perjalanan . '&error=' . urlencode($e->getMessage()));
    exit;
}
