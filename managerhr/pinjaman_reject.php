<?php
include("sess_check.php");

if (isset($row_sess['status_karyawan']) && $row_sess['status_karyawan'] == 'Magang') {
	header("location: index.php");
	exit;
}

if (isset($_GET['id'])) {
    $id_pinjaman = $_GET['id'];
    
    // Update loan status to 'ditolak'
    $sql_update = "UPDATE pinjaman SET status = 'ditolak' WHERE id_pinjaman = ? AND status = 'menunggu'";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "s", $id_pinjaman);
    $update_res = mysqli_stmt_execute($stmt_update);
    
    if ($update_res) {
        header("Location: pinjaman.php?success=1");
        exit;
    }
}
header("Location: pinjaman.php");
exit;
?>
