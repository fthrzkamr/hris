<?php
	include("sess_check.php");

    $npp	                = $_POST['npp'];
    $nama_karyawan			= $_POST['nama_karyawan'];
    $tujuan_lembur 			= $_POST['tujuan_lembur'];
    $cabang					= $_POST['cabang'];
    $tgl_lembur			    = $_POST['tgl_lembur'];
    $jam_mulai_lembur		= $_POST['jam_mulai_lembur'];
    $jam_berakhir_lembur	= $_POST['jam_berakhir_lembur'];
    $nama_koordinator 		= $_POST['nama_koordinator'];
	$alasan_lembur 		    = $_POST['alasan_lembur'];
	$status                 = "Approval";
	$jumlah		 		    = $_POST['jumlah'];

	$reject                 = "kosong";
		

	
		$id = date('dmYHis');
        $sqlcek = "SELECT * FROM lembur WHERE npp='$npp'";
		$resscek = mysqli_query($conn, $sqlcek);
		$rowscek = mysqli_fetch_array($resscek);

	
		if($npp){
            $sql 	= "INSERT INTO lembur (id_lmbr,npp, nama_karyawan, tujuan_lembur, cabang, tgl_lembur, jam_mulai_lembur, jam_berakhir_lembur, nama_koordinator,alasan_lembur,status,jumlah,reject) 
            VALUES ('$id','$npp','$nama_karyawan','$tujuan_lembur','$cabang','$tgl_lembur','$jam_mulai_lembur','$jam_berakhir_lembur','$nama_koordinator','$alasan_lembur','$status','$jumlah','$reject')";
            $query 	= mysqli_query($conn,$sql);
			// var_dump($query);
			// exit();
	
			
			if($query){
				echo "<script type='text/javascript'>
						alert('Pengajuan Lembur berhasil!'); 
						document.location = 'lembur_wait_bersamaan.php'; 
					</script>";

			}else {
				echo "<script type='text/javascript'>
						alert('Terjadi kesalahan, silahkan coba lagi!.'); 
						document.location = 'lembur_wait_bersamaan.php'; 
					</script>";
			}
        }else{
            header("location: lembur_insert.php?act=add&msg=double");	

		}
?>