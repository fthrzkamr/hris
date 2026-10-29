<?php
include("sess_check.php");

$no=$_POST['no'];
$aksi=$_POST['aksi'];
$reject=$_POST['reject'];
$stt = "";
// $null = 0;

if($aksi=="2"){
	$stt="Rejected";
	$sql = "UPDATE rembes SET
			status='". $stt ."',
			reject='". $reject ."'
			WHERE id_rmbs='". $no ."'";
		$ress = mysqli_query($conn, $sql);
		header("location: reimburse_wait.php?act=update&msg=success");
	
}else{
	$stt="Approved";
	// $num	=1;
	$sql = "UPDATE rembes SET
			status='". $stt ."'
			WHERE id_rmbs='". $no ."'";
		$ress = mysqli_query($conn, $sql);
		header("location: reimburse_wait.php?act=update&msg=success");
	
}
?>