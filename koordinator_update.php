<?php
include("sess_check.php");

if (isset($_POST['perbarui'])) {
    $id               = mysqli_real_escape_string($conn, $_POST['id_koordinator'] ?? '');
    $nama_koordinator = mysqli_real_escape_string($conn, trim($_POST['nama_koordinator'] ?? ''));
    $npp              = mysqli_real_escape_string($conn, trim($_POST['npp'] ?? ''));
    $created_at       = mysqli_real_escape_string($conn, $_POST['created_at'] ?? '');
    $akses            = mysqli_real_escape_string($conn, trim($_POST['akses'] ?? ''));

    // Jika NPP kosong, cari otomatis dari tabel employee berdasarkan nama
    if ($npp === '' && $nama_koordinator !== '') {
        $q_emp = mysqli_query($conn, "SELECT npp FROM employee WHERE TRIM(LOWER(nama_emp)) = LOWER('$nama_koordinator') LIMIT 1");
        if ($q_emp && $r_emp = mysqli_fetch_assoc($q_emp)) {
            $npp = $r_emp['npp'];
        }
    }

    $sql = "UPDATE koordinator SET
                nama_koordinator = '$nama_koordinator',
                npp              = '$npp',
                created_at       = '$created_at',
                akses            = '$akses'
            WHERE id_koordinator = '$id'";
    mysqli_query($conn, $sql);
    header("Location: koordinator.php?act=update&msg=success");
    exit();
}
?>
