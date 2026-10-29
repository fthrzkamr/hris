<?php
include("sess_check.php");

$npp = mysqli_real_escape_string($conn, $_GET['npp']);

// Get employee data to delete photo
$sql_get = "SELECT foto_emp FROM employee WHERE npp = '$npp'";
$res_get = mysqli_query($conn, $sql_get);
$data = mysqli_fetch_array($res_get);

// Delete photo file if exists
if(!empty($data['foto_emp'])) {
    $foto_path = '../' . $data['foto_emp'];
    if(file_exists($foto_path)) {
        unlink($foto_path);
    }
}

// Delete employee record
$sql = "DELETE FROM employee WHERE npp = '$npp'";

if(mysqli_query($conn, $sql)) {
    echo '<script>alert("Data calon karyawan berhasil dihapus"); window.location="calon_karyawan_list.php";</script>';
} else {
    echo '<script>alert("Gagal menghapus data: ' . mysqli_error($conn) . '"); window.location="calon_karyawan_list.php";</script>';
}
?>
