<?php 
require_once("dist/config/koneksi.php");
// code user username availablity
if(!empty($_POST["npp"])) {
	$npp= $_POST["npp"];
	$sql = "SELECT * FROM employee WHERE npp='$npp'";
	$query = mysqli_query($conn,$sql);
	if(mysqli_num_rows($query)>0){
		echo "<span style='color:red'> Nomer Induk Karyawan sudah terdaftar.</span>";
		echo "<script>$('#submit').prop('disabled',true);</script>";
	}else{
		echo "<span style='color:green'> Nomer Induk Karyawan bisa digunakan.</span>";
		echo "<script>$('#submit').prop('disabled',false);</script>";
	}
}

?>
