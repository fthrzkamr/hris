<?php
include("sess_check.php");
$id = $_GET['id'];	
$sql = "DELETE FROM employee WHERE npp='". $id ."'";
$ress = mysqli_query($conn, $sql);
header("location: data_karyawan.php?act=delete&msg=success");
?>
