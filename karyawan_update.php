<?php
include("sess_check.php");

if (isset($_POST['perbarui'])) {
    $npplama 					= $_POST['npplama'];
    $npp 						= $_POST['npp'];
    $nama 						= $_POST['nama'];
    $jml 						= $_POST['jml'];
    $jk 						= $_POST['jk'];
    $telp 						= $_POST['telp'];
    $alamat 					= $_POST['alamat'];
    $cekfoto 					= $_FILES["foto"]["name"];
    $nomor_ktp 					= $_POST['nomor_ktp'];
    $kota_lahir 				= $_POST['kota_lahir'];
    $alamat_tinggal_sekarang 	= $_POST['alamat_tinggal_sekarang'];
    $tanggal_lahir				= $_POST['tanggal_lahir'];
    $pendidikan_terakhir 		= $_POST['pendidikan_terakhir'];
    $nama_institusi 			= $_POST['nama_institusi'];
    $tanggal_masuk_karyawan 	= $_POST['tanggal_masuk_karyawan'];
    $norek 						= $_POST['norek_mandiri'];
    $aktif 						= $_POST['aktif'];
    $cabang 					= $_POST['cabang'];
    $nama_koordinator 			= $_POST['nama_koordinator'];
    $nama_manager 				= $_POST['nama_manager'];
    $nama_bagian 				= $_POST['nama_bagian'];
    $status_rem 				= "N/A";
    $status_kawin 				= $_POST['status_kawin'];
    $nama_pasangan 				= $_POST['nama_pasangan'];
    $pekerjaan 					= $_POST['pekerjaan'];
    $nomor_tlp 					= $_POST['nomor_tlp'];
    $nama_anak 					= $_POST['nama_anak'];
    $jabatan 					= $_POST['jabatan'];
    $status_ptkp 				= $_POST['status_ptkp'];
    $jurusan 					= $_POST['jurusan'];
    $nomor_npwp 				= $_POST['nomor_npwp'];
    $bpjs_kesehatan 			= $_POST['bpjs_kesehatan'];
    $nomor_bpjs_ktr 			= $_POST['nomor_bpjs_ktr'];
    $nama_bank 					= $_POST['nama_bank'];
    $agama 						= $_POST['agama'];
    $nomor_emrg_pr 				= $_POST['nomor_emrg_pr'];
    $nomor_emrg_kd 				= $_POST['nomor_emrg_kd'];
    $gol_darah					= $_POST['gol_darah'];
    $nomor_kk 					= $_POST['nomor_kk'];
    $status_karyawan 			= $_POST['status_karyawan'];

    if ($npp != "") {
        $sqlcek = "SELECT * FROM employee WHERE npp='$npp'";
        $ress = mysqli_query($conn, $sqlcek);
        $rows = mysqli_num_rows($ress);
        if ($rows < 1) {
            if ($cekfoto != "") {
                $foto = substr($_FILES["foto"]["name"], -5);
                $newfoto = "foto" . $npp . $foto;
                move_uploaded_file($_FILES["foto"]["tmp_name"], "foto/" . $newfoto);
                $foto_sql = ", foto='$newfoto'";
            } else {
                $foto_sql = "";
            }

            $sql = "UPDATE employee SET 
                npp='$npp', 
				nama_emp='$nama', 
				jk_emp='$jk', 
				telp_emp='$telp', 
				alamat='$alamat', 
                jml_cuti='$jml', 
				nomor_ktp='$nomor_ktp', 
				kota_lahir='$kota_lahir', 
                alamat_tinggal_sekarang='$alamat_tinggal_sekarang', 
				tanggal_lahir='$tanggal_lahir',
                pendidikan_terakhir='$pendidikan_terakhir', 
				nama_institusi='$nama_institusi', 
                tanggal_masuk_karyawan='$tanggal_masuk_karyawan', 
				nama_koordinator='$nama_koordinator',
                nama_manager='$nama_manager', 
				nama_bagian='$nama_bagian', 
				status_rem='$status_rem',
                status_kawin='$status_kawin', 
				nama_pasangan='$nama_pasangan', 
				pekerjaan='$pekerjaan', 
                nomor_tlp='$nomor_tlp', 
				nama_anak='$nama_anak', 
				norek_mandiri='$norek', 
				aktif='$aktif', 
                cabang='$cabang', 
				jabatan='$jabatan', 
				status_ptkp='$status_ptkp', 
				nomor_npwp='$nomor_npwp', 
                jurusan='$jurusan', 
				bpjs_kesehatan='$bpjs_kesehatan', 
				nomor_bpjs_ktr='$nomor_bpjs_ktr', 
                nama_bank='$nama_bank', 
				agama='$agama', 
				nomor_emrg_pr='$nomor_emrg_pr',
                nomor_emrg_kd='$nomor_emrg_kd', 
				gol_darah='$gol_darah', 
				nomor_kk='$nomor_kk', 
                status_karyawan='$status_karyawan' $foto_sql WHERE npp='$npplama'";
            
            $ress = mysqli_query($conn, $sql);
            header("location: karyawan.php?act=update&msg=success");
        } else {
            header("location: karyawan_edit.php?npp=$npplama&act=add&msg=double");
        }
    } else {
        if ($cekfoto != "") {
            $foto = substr($_FILES["foto"]["name"], -5);
            $newfoto = "foto" . $npplama . $foto;
            move_uploaded_file($_FILES["foto"]["tmp_name"], "foto/" . $newfoto);
            $foto_sql = ", foto='$newfoto'";
        } else {
            $foto_sql = "";
        }

        $sql = "UPDATE employee SET 
            nama_emp='$nama', jk_emp='$jk', telp_emp='$telp', alamat='$alamat', 
            jml_cuti='$jml', nomor_ktp='$nomor_ktp', kota_lahir='$kota_lahir', 
            alamat_tinggal_sekarang='$alamat_tinggal_sekarang', tanggal_lahir='$tanggal_lahir',
            pendidikan_terakhir='$pendidikan_terakhir', nama_institusi='$nama_institusi', 
            tanggal_masuk_karyawan='$tanggal_masuk_karyawan', nama_koordinator='$nama_koordinator',
            nama_manager='$nama_manager', nama_bagian='$nama_bagian', status_rem='$status_rem',
            status_kawin='$status_kawin', nama_pasangan='$nama_pasangan', pekerjaan='$pekerjaan', 
            nomor_tlp='$nomor_tlp', nama_anak='$nama_anak', norek_mandiri='$norek', aktif='$aktif', 
            cabang='$cabang', jabatan='$jabatan', status_ptkp='$status_ptkp', nomor_npwp='$nomor_npwp', 
            jurusan='$jurusan', bpjs_kesehatan='$bpjs_kesehatan', nomor_bpjs_ktr='$nomor_bpjs_ktr', 
            nama_bank='$nama_bank', agama='$agama', nomor_emrg_pr='$nomor_emrg_pr',
            nomor_emrg_kd='$nomor_emrg_kd', gol_darah='$gol_darah', nomor_kk='$nomor_kk', 
            status_karyawan='$status_karyawan' $foto_sql WHERE npp='$npplama'";
        
        $ress = mysqli_query($conn, $sql);
        header("location: karyawan.php?act=update&msg=success");
    }
}
?>
