<?php
include("sess_check.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $npp = $_POST['npp'];
    $tahun = $_POST['tahun'];
    $bulan = $_POST['bulan'];
    
    // Validasi: pastikan NPP yang dicek adalah NPP yang login
    if ($npp != $sess_mngid) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Unauthorized access'
        ]);
        exit;
    }
    
    // Cek apakah sudah pernah request untuk periode ini
    $sql_request = "SELECT status FROM request_slip_gaji 
                    WHERE npp = ? AND tahun = ? AND bulan = ?
                    ORDER BY tanggal_request DESC LIMIT 1";
    $stmt_request = mysqli_prepare($conn, $sql_request);
    mysqli_stmt_bind_param($stmt_request, "sss", $npp, $tahun, $bulan);
    mysqli_stmt_execute($stmt_request);
    $result_request = mysqli_stmt_get_result($stmt_request);
    
    if (mysqli_num_rows($result_request) > 0) {
        $data_request = mysqli_fetch_assoc($result_request);
        echo json_encode([
            'status' => 'ok',
            'already_requested' => true,
            'request_status' => $data_request['status']
        ]);
    } else {
        echo json_encode([
            'status' => 'ok',
            'already_requested' => false
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
?>
