<?php
include("sess_check.php");

$no=$_POST['no'];
$aksi=$_POST['aksi'];
$reject=$_POST['reject'];
$stt = "";
// $null = 0;

if($aksi=="2"){
	$stt="Rejected";
	$sql = "UPDATE lembur SET
			status='". $stt ."',
			reject='". $reject ."'
			WHERE id_lmbr='". $no ."'";
		$ress = mysqli_query($conn, $sql);
		header("location: lembur_wait.php?act=update&msg=success");
	
}else{
	$stt="Approved";
	// $num	=1;
	$sql = "UPDATE lembur SET
			status='". $stt ."'
			WHERE id_lmbr='". $no ."'";
		$ress = mysqli_query($conn, $sql);
		header("location: lembur_wait.php?act=update&msg=success");
	
}
?>