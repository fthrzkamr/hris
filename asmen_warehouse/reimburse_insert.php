<?php
	include("sess_check.php");

    $npp	                    = $_POST['npp'];
    $nama_fasilitas_kesehatan	= $_POST['nama_fasilitas_kesehatan'];
    $fasilitas_kesehatan	= $_POST['fasilitas_kesehatan'];
	$nama_anggota_keluarga = $_POST['nama_anggota_keluarga'];
	$hubungan_keluarga      = $_POST['hubungan_keluarga'];
    $nama_dokter 			= $_POST['nama_dokter'];
    $tanggal_pemeriksaan    = $_POST['tanggal_pemeriksaan'];
    $total_kwitansi			= $_POST['total_kwitansi'];
	$no_kwitansi			= $_POST['no_kwitansi'];
    // $total_rembes   		= $_POST['total_rembes'];
    $foto			        = substr($_FILES["foto"]["name"],-0);
	$newfoto 		        = "foto".$npp.$foto;
    $status                 = "Menunggu Approval";
	$reject					="kosong";


	
		$id = date('dmYHis');
        $sqlcek = "SELECT * FROM employee WHERE npp='$npp'";
		$resscek = mysqli_query($conn, $sqlcek);
		$rowscek = mysqli_fetch_array($resscek);


		$a		=(80 / 100);
		$p		= $total_kwitansi * $a ;
		$jml= $rowscek['kesehatan'];
		$total = ($jml - $p);

		
		
	
		if($total_kwitansi>$jml){
		
		echo "<script type='text/javascript'>
					alert(' Rembes lebih banyak dari jumlah limit tersedia!!!!.'); 
					document.location = 'reimburse_create.php'; 
				</script>";	
		}else{
		
            $sql 	= "INSERT INTO rembes (id_rmbs,npp, nama_fasilitas_kesehatan,fasilitas_kesehatan, nama_anggota_keluarga,hubungan_keluarga, nama_dokter, tanggal_pemeriksaan, total_kwitansi,no_kwitansi, foto, status,reject) 
            VALUES ('$id','$npp','$nama_fasilitas_kesehatan','$fasilitas_kesehatan','$nama_anggota_keluarga','$hubungan_keluarga','$nama_dokter','$tanggal_pemeriksaan','$total_kwitansi','$no_kwitansi','$newfoto','$status','$reject')";
            $query 	= mysqli_query($conn,$sql);
			
			$edit	= "UPDATE employee SET kesehatan='".$total."' WHERE npp					='". $npp ."'";
			$ress = mysqli_query($conn, $edit);
			
	
			if($query){
                // move_uploaded_file($_FILES["foto"]["tmp_name"],"foto/".$newfoto);
				echo "<script type='text/javascript'>
						alert('Pengajuan Reimburse berhasil!'); 
						document.location = 'reimburse_waitapp.php'; 
					</script>";

			}else {
				echo "<script type='text/javascript'>
						alert('Terjadi kesalahan, silahkan coba lagi!.'); 
						document.location = 'reimburse_waitapp.php'; 
					</script>";
			}
        }
?>