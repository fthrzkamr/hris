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

// Get approver name from session
$approver = isset($sess_mngname) ? $sess_mngname : 'SYSTEM';

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
    
    if ($approval_type == 'hr') {
        // HR Approval
        if ($current_status != 'DIAJUKAN') {
            throw new Exception('Status pengajuan tidak valid untuk approval HR.');
        }
        
        // Update nominal in perjalanan_rincian
        if (isset($_POST['rincian_id']) && isset($_POST['nominal']) && isset($_POST['qty']) && isset($_POST['keterangan'])) {
            $rincian_ids = $_POST['rincian_id'];
            $nominals = $_POST['nominal'];
            $qtys = $_POST['qty'];
            $keterangans = $_POST['keterangan'];
            
            $new_budget_total = 0;
            
            for ($i = 0; $i < count($rincian_ids); $i++) {
                $rid = intval($rincian_ids[$i]);
                $nom = floatval($nominals[$i]);
                $qty = floatval($qtys[$i]);
                $ket = mysqli_real_escape_string($conn, $keterangans[$i]);
                $total = $nom * $qty;
                
                $new_budget_total += $total;
                
                $sql_update_rincian = "UPDATE perjalanan_rincian SET nominal = ?, total = ?, keterangan = ? WHERE id = ?";
                $stmt_rincian = mysqli_prepare($conn, $sql_update_rincian);
                mysqli_stmt_bind_param($stmt_rincian, 'ddsi', $nom, $total, $ket, $rid);
                
                if (!mysqli_stmt_execute($stmt_rincian)) {
                    throw new Exception('Gagal menyimpan nominal: ' . mysqli_error($conn));
                }
            }
            
            // Update budget_total in perjalanan_dinas
            $sql_update_budget = "UPDATE perjalanan_dinas SET budget_total = ? WHERE id = ?";
            $stmt_budget = mysqli_prepare($conn, $sql_update_budget);
            mysqli_stmt_bind_param($stmt_budget, 'di', $new_budget_total, $id_perjalanan);
            
            if (!mysqli_stmt_execute($stmt_budget)) {
                throw new Exception('Gagal update budget total: ' . mysqli_error($conn));
            }
        }
        
        $catatan_hr = isset($_POST['catatan_hr']) ? mysqli_real_escape_string($conn, $_POST['catatan_hr']) : '';
        
        // Update pengajuan with HR approval
        $new_status = ($decision == 'APPROVED') ? 'APPROVED_HR' : 'DITOLAK';
        
        $sql_update = "UPDATE perjalanan_pengajuan 
                       SET approval_hr = ?, 
                           approver_hr = ?, 
                           tanggal_approval_hr = NOW(), 
                           catatan_hr = ?,
                           status = ?
                       WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, 'ssssi', $decision, $approver, $catatan_hr, $new_status, $pengajuan_id);
        
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception('Gagal menyimpan approval HR: ' . mysqli_error($conn));
        }
        
        $message = ($decision == 'APPROVED') ? 'Pengajuan berhasil disetujui oleh HR.' : 'Pengajuan ditolak oleh HR.';
        
    } elseif ($approval_type == 'direktur') {
        // Director Approval
        if ($current_status != 'APPROVED_HR') {
            throw new Exception('Status pengajuan tidak valid untuk approval Direktur.');
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
            throw new Exception('Gagal menyimpan approval Direktur: ' . mysqli_error($conn));
        }
        
        $message = ($decision == 'APPROVED') ? 'Pengajuan berhasil disetujui oleh Direktur.' : 'Pengajuan ditolak oleh Direktur.';
        
    } else {
        throw new Exception('Tipe approval tidak valid.');
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
