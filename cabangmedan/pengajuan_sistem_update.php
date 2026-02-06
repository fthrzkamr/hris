<?php
// Menghubungkan ke database
include("sess_check.php"); // Pastikan file ini berisi pengaturan koneksi database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mengambil data dari formulir
    $no_pengajuan = $_POST['no_pengajuan'];
    $npp = $_POST['npp'];
    $nama_karyawan = $_POST['nama_karyawan'];
    $deskripsi_pengajuan = $_POST['deskripsi_pengajuan'];
    $status_pengajuan = $_POST['status_pengajuan'];
    
    // Menghindari SQL Injection
    $no_pengajuan = mysqli_real_escape_string($conn, $no_pengajuan);
    $npp = mysqli_real_escape_string($conn, $npp);
    $nama_karyawan = mysqli_real_escape_string($conn, $nama_karyawan);
    $deskripsi_pengajuan = mysqli_real_escape_string($conn, $deskripsi_pengajuan);
    $status_pengajuan = mysqli_real_escape_string($conn, $status_pengajuan);
    
    // SQL untuk memperbarui data
    $sql = "UPDATE pengajuan_sistem SET 
                nama_karyawan = '$nama_karyawan',
                deskripsi_pengajuan = '$deskripsi_pengajuan',
                status_pengajuan = '$status_pengajuan'
            WHERE no_pengajuan = '$no_pengajuan'";
    
    if (mysqli_query($conn, $sql)) {
        // Jika berhasil diperbarui, arahkan ke halaman dengan pesan sukses
        header("Location: pengajuan_sistem.php?status=success");
    } else {
        // Jika gagal diperbarui, arahkan ke halaman dengan pesan error
        header("Location: pengajuan_sistem.php?status=error");
    }
    
    // Menutup koneksi database
    mysqli_close($conn);
} else {
    // Jika tidak menggunakan metode POST, arahkan kembali ke halaman edit
    header("Location: pengajuan_sistem_edit.php");
}
?>
