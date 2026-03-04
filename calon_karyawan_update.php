<?php
include("sess_check.php");

if(isset($_POST['update'])) {
    $npp = mysqli_real_escape_string($conn, $_POST['npp']);
    $tanggal_masuk_karyawan = mysqli_real_escape_string($conn, $_POST['tanggal_masuk_karyawan']);
    $status_karyawan = mysqli_real_escape_string($conn, $_POST['status_karyawan']);
    $aktif = mysqli_real_escape_string($conn, $_POST['aktif']);
    $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan']);
    $jml_cuti = intval($_POST['jml_cuti']);
    $status_ptkp = mysqli_real_escape_string($conn, $_POST['status_ptkp']);
    
    // Gaji - remove dots for numeric values
    $gaji_pokok = intval(str_replace('.', '', $_POST['gaji_pokok']));
    $tunj_jabatan = intval(str_replace('.', '', $_POST['tunj_jabatan']));
    $tunj_transport = intval(str_replace('.', '', $_POST['tunj_transport']));
    $tunj_kinerja = intval(str_replace('.', '', $_POST['tunj_kinerja']));
    $total_gaji = intval(str_replace('.', '', $_POST['total_gaji']));
    
    // Benefit
    $status_rem = mysqli_real_escape_string($conn, $_POST['status_rem']);
    $plafond = intval(str_replace('.', '', $_POST['plafond']));
    $kesehatan = intval(str_replace('.', '', $_POST['kesehatan']));
    $plafond_kacamata = intval(str_replace('.', '', $_POST['plafond_kacamata']));
    $kacamata = intval(str_replace('.', '', $_POST['kacamata']));
    
    // Atasan
    $nama_koordinator = mysqli_real_escape_string($conn, $_POST['nama_koordinator']);
    $nama_manager = mysqli_real_escape_string($conn, $_POST['nama_manager']);
    
    $sql = "UPDATE employee SET 
        tanggal_masuk_karyawan = '$tanggal_masuk_karyawan',
        status_karyawan = '$status_karyawan',
        aktif = '$aktif',
        jabatan = '$jabatan',
        jml_cuti = $jml_cuti,
        status_ptkp = '$status_ptkp',
        gaji_pokok = $gaji_pokok,
        tunj_jabatan = $tunj_jabatan,
        tunj_transport = $tunj_transport,
        tunj_kinerja = $tunj_kinerja,
        total_gaji = $total_gaji,
        status_rem = '$status_rem',
        plafond = $plafond,
        kesehatan = $kesehatan,
        plafond_kacamata = $plafond_kacamata,
        kacamata = $kacamata,
        nama_koordinator = '$nama_koordinator',
        nama_manager = '$nama_manager'
        WHERE npp = '$npp'";
    
    if(mysqli_query($conn, $sql)) {
        header("location: calon_karyawan_detail.php?npp=" . $npp . "&success=update");
    } else {
        header("location: calon_karyawan_edit.php?npp=" . $npp . "&error=update");
    }
} else {
    header("location: calon_karyawan_list.php");
}
?>
