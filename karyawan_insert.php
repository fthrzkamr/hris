<?php
    include("sess_check.php");

    $id             = $sess_admid;
    $npp            = $_POST['npp'];
    $nama           = $_POST['nama'];
    $jk             = $_POST['jk'];
    $telp           = $_POST['telp'];
    $jml            = $_POST['jml'];
    $alamat         = $_POST['alamat'];
    $hak_akses      = "karyawan";
    $foto           = substr($_FILES["foto"]["name"], -5);
    $newfoto        = "foto" . $npp . $foto;

    // Data tambahan
    $nomorktp       = $_POST['nomor_ktp'];
    $kotalahir      = $_POST['kota_lahir'];
    $alamattinggal  = $_POST['alamat_tinggal_sekarang'];
    $tgllahir       = $_POST['tanggal_lahir'];
    $pendidikan     = $_POST['pendidikan_terakhir'];
    $namainstitusi  = $_POST['nama_institusi'];
    $tglmasuk       = $_POST['tanggal_masuk_karyawan'];
    $norekmandiri   = $_POST['norek_mandiri'];
    $jabatan        = $_POST['jabatan'];
    $statusptkp     = $_POST['status_ptkp'];
    $jurusan        = $_POST['jurusan'];
    $nomornpwp      = $_POST['nomor_npwp'];
    $bpjs_kesehatan = $_POST['bpjs_kesehatan'];
    $nomor_bpjs_ktr = $_POST['nomor_bpjs_ktr'];
    $nama_bank      = $_POST['nama_bank'];
    $agama          = $_POST['agama'];
    $nomor_emrg_pr  = $_POST['nomor_emrg_pr'];
    $nomor_emrg_kd  = $_POST['nomor_emrg_kd'];
    $gol_darah      = $_POST['gol_darah'];
    $nomor_kk       = $_POST['nomor_kk'];
    $status_karyawan= $_POST['status_karyawan'];
    $cabang         = $_POST['cabang'];

    $sql = "INSERT INTO employee(
        npp, nama_emp, jk_emp, telp_emp, alamat, hak_akses, jml_cuti, password, foto_emp,
        nomor_ktp, kota_lahir, alamat_tinggal_sekarang, tanggal_lahir, pendidikan_terakhir, nama_institusi, tanggal_masuk_karyawan, norek_mandiri,
        jabatan, status_ptkp, jurusan, nomor_npwp, bpjs_kesehatan, nomor_bpjs_ktr, nama_bank, agama,
        nomor_emrg_pr, nomor_emrg_kd, gol_darah, nomor_kk, status_karyawan, cabang
    ) VALUES (
        '$npp', '$nama', '$jk', '$telp', '$alamat', '$hak_akses', '$jml', '$npp', '$newfoto',
        '$nomorktp', '$kotalahir', '$alamattinggal', '$tgllahir', '$pendidikan', '$namainstitusi', '$tglmasuk', '$norekmandiri',
        '$jabatan', '$statusptkp', '$jurusan', '$nomornpwp', '$bpjs_kesehatan', '$nomor_bpjs_ktr', '$nama_bank', '$agama',
        '$nomor_emrg_pr', '$nomor_emrg_kd', '$gol_darah', '$nomor_kk', '$status_karyawan','$cabang'
    )";

    // Debugging
    // echo "<h3>Debugging Data</h3>";
    // echo "<pre>";
    // var_dump($_POST); // Dump all POST data
    // var_dump($sql);   // Dump the SQL query
    // echo "</pre>";

    $ress = mysqli_query($conn, $sql);
    if ($ress) {
        move_uploaded_file($_FILES["foto"]["tmp_name"], "foto/" . $newfoto);
        echo "<script>alert('Tambah Karyawan Berhasil!');</script>";
        echo "<script type='text/javascript'> document.location = 'karyawan.php'; </script>";
    } else {
        echo "<h3>Error Description</h3>";
        echo mysqli_error($conn); // Display MySQL error
    }
?>
