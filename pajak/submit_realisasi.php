<?php
include('sess_check.php');
include('dist/config/koneksi.php');

// Ensure request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perjalanan_dinas_list.php');
    exit;
}

$id_perjalanan = isset($_POST['id_perjalanan']) ? intval($_POST['id_perjalanan']) : 0;
if ($id_perjalanan <= 0) {
    header('Location: perjalanan_dinas_list.php?error=' . urlencode('ID perjalanan tidak valid.'));
    exit;
}

$npp_user = isset($sess_mngid) ? $sess_mngid : '';
if (empty($npp_user)) {
    header('Location: perjalanan_dinas_list.php?error=' . urlencode('Sesi tidak valid.'));
    exit;
}

// Function to compress image using GD Library
function compressImage($sourcePath, $destinationPath, $quality = 65, $maxWidth = 1200) {
    $imgInfo = @getimagesize($sourcePath);
    if (!$imgInfo) return false;
    $mime = $imgInfo['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $image = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $image = @imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$image) return false;
    
    // Fix EXIF orientation for JPEG auto-rotate
    if (($mime == 'image/jpeg' || $mime == 'image/jpg') && function_exists('exif_read_data')) {
        $exif = @exif_read_data($sourcePath);
        if ($exif && isset($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3: $image = imagerotate($image, 180, 0); break;
                case 6: $image = imagerotate($image, -90, 0); break;
                case 8: $image = imagerotate($image, 90, 0); break;
            }
        }
    }
    
    // Resize image if dimensions exceed maxWidth
    $width = imagesx($image);
    $height = imagesy($image);
    if ($width > $maxWidth || $height > $maxWidth) {
        if ($width > $height) {
            $newWidth = $maxWidth;
            $newHeight = ($height / $width) * $maxWidth;
        } else {
            $newHeight = $maxWidth;
            $newWidth = ($width / $height) * $maxWidth;
        }
        $tempImg = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG/GIF
        if ($mime == 'image/png' || $mime == 'image/gif') {
            imagealphablending($tempImg, false);
            imagesavealpha($tempImg, true);
        }
        
        imagecopyresampled($tempImg, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $tempImg;
    }
    
    // Save compressed image as JPEG
    $result = imagejpeg($image, $destinationPath, $quality);
    imagedestroy($image);
    return $result;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // 1. Fetch current submission
    $stmt = mysqli_prepare($conn, "SELECT p.*, pg.status FROM perjalanan_dinas p LEFT JOIN (SELECT p2.* FROM perjalanan_pengajuan p2 INNER JOIN (SELECT id_perjalanan, MAX(id) AS mid FROM perjalanan_pengajuan GROUP BY id_perjalanan) m ON p2.id_perjalanan = m.id_perjalanan AND p2.id = m.mid) pg ON pg.id_perjalanan = p.id WHERE p.id = ? FOR UPDATE");
    mysqli_stmt_bind_param($stmt, 'i', $id_perjalanan);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $perjalanan = mysqli_fetch_assoc($res);
    
    if (!$perjalanan) {
        throw new Exception('Data perjalanan tidak ditemukan.');
    }
    
    $allowed_statuses = ['DISETUJUI', 'APPROVED_MANAGER_HR', 'REALISASI_DITOLAK'];
    if (!in_array($perjalanan['status'], $allowed_statuses)) {
        throw new Exception('Status perjalanan tidak valid untuk pengajuan realisasi.');
    }
    
    // Create uploads directory if not exists
    $upload_dir = __DIR__ . '/../uploads/bukti_realisasi/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }
    
    // 2. Process Nominals
    $nominals = isset($_POST['nominal_realisasi']) ? $_POST['nominal_realisasi'] : [];
    
    // Process Files - helper to restructure nested FILES array
    $files_by_rincian = [];
    if (isset($_FILES['bukti_realisasi'])) {
        $file_arr = $_FILES['bukti_realisasi'];
        foreach ($file_arr['name'] as $rid => $names) {
            if (is_array($names)) {
                foreach ($names as $idx => $name) {
                    if ($file_arr['error'][$rid][$idx] == UPLOAD_ERR_OK) {
                        $files_by_rincian[$rid][] = [
                            'name' => $file_arr['name'][$rid][$idx],
                            'type' => $file_arr['type'][$rid][$idx],
                            'tmp_name' => $file_arr['tmp_name'][$rid][$idx],
                            'error' => $file_arr['error'][$rid][$idx],
                            'size' => $file_arr['size'][$rid][$idx]
                        ];
                    }
                }
            }
        }
    }
    
    // Update each rincian
    foreach ($nominals as $rid => $val) {
        $rid = intval($rid);
        $val_clean = preg_replace('/[^0-9]/', '', $val);
        $nominal_real = $val_clean === '' ? 0 : floatval($val_clean);
        
        // Fetch current rincian to get qty
        $stmt_r = mysqli_prepare($conn, "SELECT qty, bukti_realisasi FROM perjalanan_rincian WHERE id = ? AND perjalanan_id = ?");
        mysqli_stmt_bind_param($stmt_r, 'ii', $rid, $id_perjalanan);
        mysqli_stmt_execute($stmt_r);
        $res_r = mysqli_stmt_get_result($stmt_r);
        $curr_r = mysqli_fetch_assoc($res_r);
        
        if (!$curr_r) continue;
        
        $qty = floatval(($curr_r['qty'] ?? 1) ?: 1);
        $total_real = $nominal_real * $qty;
        
        // Process file uploads for this rincian
        $existing_bukti = [];
        if (!empty($curr_r['bukti_realisasi'])) {
            $existing_bukti = json_decode($curr_r['bukti_realisasi'] ?? '', true) ?: [];
        }
        
        if (isset($files_by_rincian[$rid])) {
            foreach ($files_by_rincian[$rid] as $file_info) {
                $ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($ext, $allowed_exts)) {
                    $new_filename = 'bukti_' . $rid . '_' . uniqid() . '.jpg'; // Save all compressed as jpg
                    $destination = $upload_dir . $new_filename;
                    
                    if (compressImage($file_info['tmp_name'], $destination)) {
                        $existing_bukti[] = 'uploads/bukti_realisasi/' . $new_filename;
                    }
                }
            }
        }
        
        $bukti_json = json_encode($existing_bukti);
        
        // Update DB
        $stmt_upd = mysqli_prepare($conn, "UPDATE perjalanan_rincian SET nominal_realisasi = ?, total_realisasi = ?, bukti_realisasi = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_upd, 'ddsi', $nominal_real, $total_real, $bukti_json, $rid);
        mysqli_stmt_execute($stmt_upd);
    }
    
    // 3. Update status in perjalanan_pengajuan
    // Get last pengajuan record
    $stmt_p = mysqli_prepare($conn, "SELECT id FROM perjalanan_pengajuan WHERE id_perjalanan = ? ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt_p, 'i', $id_perjalanan);
    mysqli_stmt_execute($stmt_p);
    $res_p = mysqli_stmt_get_result($stmt_p);
    $last_p = mysqli_fetch_assoc($res_p);
    
    $user_nama = isset($sess_mngname) ? $sess_mngname : ($sess_admname ?? 'User');
    $status = 'REALISASI_DIAJUKAN';
    
    if ($last_p) {
        $pid = $last_p['id'];
        $stmt_upd_p = mysqli_prepare($conn, "UPDATE perjalanan_pengajuan SET npp = ?, pengaju = ?, tanggal_pengajuan = NOW(), status = ?, approval_manager_hr = NULL, approver_manager_hr = NULL, tanggal_approval_manager_hr = NULL, catatan_manager_hr = NULL, approval_direktur = NULL, approver_direktur = NULL, tanggal_approval_direktur = NULL, catatan_direktur = NULL WHERE id = ?");
        mysqli_stmt_bind_param($stmt_upd_p, 'sssi', $npp_user, $user_nama, $status, $pid);
        if (!mysqli_stmt_execute($stmt_upd_p)) {
            throw new Exception('Gagal mengupdate status realisasi.');
        }
    } else {
        $stmt_ins_p = mysqli_prepare($conn, "INSERT INTO perjalanan_pengajuan (id_perjalanan, npp, pengaju, tanggal_pengajuan, status) VALUES (?, ?, ?, NOW(), ?)");
        mysqli_stmt_bind_param($stmt_ins_p, 'isss', $id_perjalanan, $npp_user, $user_nama, $status);
        if (!mysqli_stmt_execute($stmt_ins_p)) {
            throw new Exception('Gagal membuat log status realisasi.');
        }
    }
    
    mysqli_commit($conn);
    header('Location: perjalanan_dinas_list.php?success=' . urlencode('Laporan realisasi berhasil dikirim.'));
    exit;
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    header('Location: perjalanan_dinas_detail.php?id=' . $id_perjalanan . '&error=' . urlencode($e->getMessage()));
    exit;
}
?>
