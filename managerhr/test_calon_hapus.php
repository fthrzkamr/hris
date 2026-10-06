<?php
include("sess_check.php");

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $query = "DELETE FROM test_calon_karyawan WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        header("location: test_calon_list.php?act=delete&msg=success");
    } else {
        header("location: test_calon_list.php?act=delete&msg=fail");
    }
} else {
    header("location: test_calon_list.php");
}
?>
