<?php
include("sess_check.php");

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $npp = mysqli_real_escape_string($conn, $_POST['npp']);
        $nama_karyawan = mysqli_real_escape_string($conn, $_POST['nama_karyawan']);
        $tahun = mysqli_real_escape_string($conn, $_POST['tahun']);
        $bulan = mysqli_real_escape_string($conn, $_POST['bulan']);
        $keterangan = isset($_POST['keterangan']) ? mysqli_real_escape_string($conn, $_POST['keterangan']) : '';

        if ($npp != $_SESSION['cabangsurabaya']) {
            $_SESSION['message'] = 'Akses ditolak! Anda hanya dapat melakukan request untuk diri sendiri.';
            $_SESSION['status'] = 'danger';
            header("location: request_slip_gaji.php");
            exit;
        }

        $sql_check_request = "SELECT COUNT(*) as total FROM request_slip_gaji 
                              WHERE npp = ? AND tahun = ? AND bulan = ? AND status = 'pending'";
        $stmt_check_req = mysqli_prepare($conn, $sql_check_request);
        mysqli_stmt_bind_param($stmt_check_req, "sss", $npp, $tahun, $bulan);
        mysqli_stmt_execute($stmt_check_req);
        $result_check_req = mysqli_stmt_get_result($stmt_check_req);
        $data_check_req = mysqli_fetch_assoc($result_check_req);

        if ($data_check_req['total'] > 0) {
            $_SESSION['message'] = 'Anda sudah memiliki request pending untuk periode ini. Mohon tunggu hingga request sebelumnya diproses.';
            $_SESSION['status'] = 'warning';
            header("location: request_slip_gaji.php");
            exit;
        }

        $tanggal_request = date('Y-m-d H:i:s');
        $sql_insert = "INSERT INTO request_slip_gaji (npp, nama_karyawan, bulan, tahun, tanggal_request, status, created_at) 
                       VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
        $stmt_insert = mysqli_prepare($conn, $sql_insert);
        mysqli_stmt_bind_param($stmt_insert, "sssss", $npp, $nama_karyawan, $bulan, $tahun, $tanggal_request);

        if (mysqli_stmt_execute($stmt_insert)) {
            $_SESSION['message'] = 'Request slip gaji berhasil dikirim! Silakan tunggu approval dari HR/Admin.';
            $_SESSION['status'] = 'success';
            header("location: request_slip_gaji_list.php");
        } else {
            $_SESSION['message'] = 'Gagal mengirim request. Error: ' . mysqli_error($conn);
            $_SESSION['status'] = 'danger';
            header("location: request_slip_gaji.php");
        }
        break;
    default:
        header("location: request_slip_gaji.php");
}
