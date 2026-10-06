<?php
include("sess_check.php");

// Pastikan ini adalah permintaan POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form dengan validasi agar tidak null
    $npp                	    = $_POST['npp'] ?? '';
    $nama_anggota_keluarga      = $_POST['nama_anggota_keluarga'] ?? '';
    $hubungan_keluarga     	    = $_POST['hubungan_keluarga'] ?? '';
    $nama_fasilitas_kesehatan   = $_POST['nama_fasilitas_kesehatan'] ?? '';
    $fasilitas_kesehatan   	    = $_POST['fasilitas_kesehatan'] ?? '';
    $nama_dokter		        = $_POST['nama_dokter'] ?? '';
    $tanggal_pemeriksaan    	= $_POST['tanggal_pemeriksaan'] ?? '';
    $no_kwitansi        	    = $_POST['no_kwitansi'] ?? '';

    // Pastikan `total_kwintansi` tidak kosong dan ubah ke numerik
    $total_kwintansi = $_POST['total_kwitansi'] ?? '0';
    $total_kwintansi = str_replace(['.', ' '], '', $total_kwintansi); // Hilangkan titik pemisah ribuan & spasi
    $total_kwintansi = str_replace(',', '.', $total_kwintansi); // Ubah koma menjadi titik sebagai desimal
    $total_kwintansi = is_numeric($total_kwintansi) ? floatval($total_kwintansi) : 0; // Konversi ke float

    // Format tanggal
    $tanggal_pemeriksaan = date("Y-m-d", strtotime($tanggal_pemeriksaan));

    // Generate ID unik
    $id_rmbs = date('dmYHis');

    // Cek limit kesehatan yang tersedia dari database
    $sqlcek = "SELECT kesehatan FROM employee WHERE npp='$npp'";
    $resscek = mysqli_query($conn, $sqlcek);
    
    if (!$resscek) {
        die("Error Query: " . mysqli_error($conn));
    }

    $rowscek = mysqli_fetch_assoc($resscek);
    $limit_kesehatan = floatval($rowscek['kesehatan']); // Konversi ke float

    // Hitung pengurangan limit
    $persentase_dibayar = 0.80; // 80%
    $dibayar_perusahaan = $total_kwintansi * $persentase_dibayar;
    $sisa_limit = $limit_kesehatan - $dibayar_perusahaan;

    // Debugging output untuk memastikan perhitungan benar
    // var_dump($total_kwintansi, $dibayar_perusahaan, $sisa_limit);

    // Validasi limit kesehatan
    if ($dibayar_perusahaan > $limit_kesehatan) {
        echo "<script>alert('Pengajuan lebih besar dari limit kesehatan yang tersedia!'); window.location='reimburse_create.php';</script>";
        exit();
    }

    // Proses upload file
    $target_dir = "../dist/uploads/raim_kesehatan/";
    $original_name = basename($_FILES["foto"]["name"]);
    $file_type = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    // Validasi format file
    $allowed_types = ["jpg", "jpeg", "png", "pdf"];
    if (!in_array($file_type, $allowed_types)) {
        echo "<script>alert('Format file tidak valid! Hanya JPG, PNG, dan PDF yang diperbolehkan.');window.location='reimburse_create.php';</script>";
        exit();
    }

    // Nama file dibuat unik (npp + id_rmbs) agar tidak menimpa file upload lain yang kebetulan bernama sama
    $file_name = $npp . '_' . $id_rmbs . '.' . $file_type;
    $target_file = $target_dir . $file_name;

    // Simpan file ke server
    if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
        // Simpan data ke database
        $sql = "INSERT INTO rembes 
                (id_rmbs, npp, nama_anggota_keluarga, hubungan_keluarga, nama_fasilitas_kesehatan, fasilitas_kesehatan, nama_dokter, tanggal_pemeriksaan, total_kwitansi, no_kwitansi, foto, status, reject, created_at) 
                VALUES 
                ('$id_rmbs', '$npp', '$nama_anggota_keluarga', '$hubungan_keluarga', '$nama_fasilitas_kesehatan', '$fasilitas_kesehatan', '$nama_dokter','$tanggal_pemeriksaan', '$total_kwintansi', '$no_kwitansi', '$file_name', 'Menunggu Approval', 'Tidak', NOW())";
        
        $query = mysqli_query($conn, $sql);
        if (!$query) {
            die("Error Insert: " . mysqli_error($conn));
        }

        // Perbarui limit kesehatan di database
        $update_kesehatan = "UPDATE employee SET kesehatan='$sisa_limit' WHERE npp='$npp'";
        $update_query = mysqli_query($conn, $update_kesehatan);
        if (!$update_query) {
            die("Error Update: " . mysqli_error($conn));
        }

        echo "<script>alert('Pengajuan Reimburse berhasil!'); window.location='reimburse_waitapp.php';</script>";
    } else {
        echo "<script>alert('Gagal mengunggah file!');window.location='reimburse_create.php';</script>";
    }
} else {
    echo "<script>alert('Akses tidak valid!');window.location='reimburse_create.php';</script>";
}
?>
