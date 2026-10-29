<?php
include("sess_check.php");

// Pastikan ini adalah permintaan POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form dengan validasi agar tidak null
    $npp                = $_POST['npp'] ?? '';
    $nama_karyawan      = $_POST['nama_karyawan'] ?? '';
    $jenis_kacamata     = $_POST['jenis_kacamata'] ?? '';
    $nama_fasilitas     = $_POST['nama_fasilitas'] ?? '';
    $alamat_fasilitas   = $_POST['alamat_fasilitas'] ?? '';
    $tanggal_pengajuan  = $_POST['tanggal_pengajuan'] ?? '';
    $no_kwitansi        = $_POST['no_kwintansi'] ?? '';

    // Pastikan `total_kwintansi` tidak kosong dan ubah ke numerik
    $total_kwintansi = $_POST['total_kwintansi'] ?? '0';
    $total_kwintansi = str_replace(['.', ' '], '', $total_kwintansi); // Hilangkan titik pemisah ribuan & spasi
    $total_kwintansi = str_replace(',', '.', $total_kwintansi); // Ubah koma menjadi titik sebagai desimal
    $total_kwintansi = is_numeric($total_kwintansi) ? floatval($total_kwintansi) : 0; // Konversi ke float

    // Format tanggal
    $tanggal_pengajuan = date("Y-m-d", strtotime($tanggal_pengajuan));

    // Generate ID unik
    $id_kacamata = date('dmYHis');

    // Cek limit kacamata yang tersedia dari database
    $sqlcek = "SELECT kacamata FROM employee WHERE npp='$npp'";
    $resscek = mysqli_query($conn, $sqlcek);
    
    if (!$resscek) {
        die("Error Query: " . mysqli_error($conn));
    }

    $rowscek = mysqli_fetch_assoc($resscek);
    $limit_kacamata = floatval($rowscek['kacamata']); // Konversi ke float

    // Hitung pengurangan limit
    $persentase_dibayar = 0.80; // 80%
    $dibayar_perusahaan = $total_kwintansi * $persentase_dibayar;
    $sisa_limit = $limit_kacamata - $dibayar_perusahaan;

    // Debugging output untuk memastikan perhitungan benar
    // var_dump($total_kwintansi, $dibayar_perusahaan, $sisa_limit);

    // Validasi limit kacamata
    if ($dibayar_perusahaan > $limit_kacamata) {
        echo "<script>alert('Pengajuan lebih besar dari limit kacamata yang tersedia!'); window.location='kacamata_create.php';</script>";
        exit();
    }

    // Proses upload file
    $target_dir = "../dist/uploads/raim_kacamata/";
    $original_name = basename($_FILES["foto"]["name"]);
    $file_type = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    // Validasi format file
    $allowed_types = ["jpg", "jpeg", "png", "pdf"];
    if (!in_array($file_type, $allowed_types)) {
        echo "<script>alert('Format file tidak valid! Hanya JPG, PNG, dan PDF yang diperbolehkan.');window.location='kacamata_create.php';</script>";
        exit();
    }

    // Nama file dibuat unik (npp + id_kacamata) agar tidak menimpa file upload lain yang kebetulan bernama sama
    $file_name = $npp . '_' . $id_kacamata . '.' . $file_type;
    $target_file = $target_dir . $file_name;

    // Simpan file ke server
    if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
        // Simpan data ke database
        $sql = "INSERT INTO kacamata 
                (id_kacamata, npp, nama_karyawan, jenis_kacamata, nama_fasilitas, alamat_fasilitas, tanggal_pengajuan, total_kwintansi, no_kwintansi, foto, status, reject) 
                VALUES 
                ('$id_kacamata', '$npp', '$nama_karyawan', '$jenis_kacamata', '$nama_fasilitas', '$alamat_fasilitas', '$tanggal_pengajuan', '$total_kwintansi', '$no_kwitansi', '$file_name', 'Menunggu', 'Tidak')";
        
        $query = mysqli_query($conn, $sql);
        if (!$query) {
            die("Error Insert: " . mysqli_error($conn));
        }

        // Perbarui limit kacamata di database
        $update_kacamata = "UPDATE employee SET kacamata='$sisa_limit' WHERE npp='$npp'";
        $update_query = mysqli_query($conn, $update_kacamata);
        if (!$update_query) {
            die("Error Update: " . mysqli_error($conn));
        }

        echo "<script>alert('Pengajuan kacamata berhasil!'); window.location='kacamata_rincian.php';</script>";
    } else {
        echo "<script>alert('Gagal mengunggah file!');window.location='kacamata_create.php';</script>";
    }
} else {
    echo "<script>alert('Akses tidak valid!');window.location='kacamata_create.php';</script>";
}
?>
