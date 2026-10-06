<?php
	include("sess_check.php");

    // Ambil data dari form
    $npp                = $_POST['npp'];
    $nama_karyawan      = $_POST['nama_karyawan'];
    $id_bagian          = $_POST['id_bagian'];
    $jenis_training     = $_POST['jenis_training'];
    $judul_training     = $_POST['judul_training'];
    $tujuan_training    = $_POST['tujuan_training'];
    $penyelenggara      = $_POST['penyelenggara'];
    $tanggal_mulai      = $_POST['tanggal_mulai'];
    $tanggal_selesai    = $_POST['tanggal_selesai'];
    $lokasi_training    = $_POST['lokasi_training'];
    
    // Budget items
    $budget_1           = $_POST['budget_1'];
    $budget_2           = $_POST['budget_2'];
    $budget_3           = $_POST['budget_3'];
    $budget_4           = $_POST['budget_4'];
    $budget_total       = $_POST['budget_total'];
    $konfirmasi_mengikuti = $_POST['konfirmasi_mengikuti'];
    
    $status             = "Pending";
    $tanggal_pengajuan  = date('Y-m-d H:i:s');

    // Generate ID
    $bulan_ini = date('m');
    $tahun_ini = date('Y');
    
    $q_last_id = mysqli_query($conn, "SELECT id_pengajuan FROM pengajuan_training WHERE MONTH(tanggal_pengajuan) = '$bulan_ini' AND YEAR(tanggal_pengajuan) = '$tahun_ini' AND id_pengajuan LIKE 'TR/%' ORDER BY id_pengajuan DESC LIMIT 1");
    if(mysqli_num_rows($q_last_id) > 0) {
        $r_last_id = mysqli_fetch_assoc($q_last_id);
        $last_id = $r_last_id['id_pengajuan'];
        $parts = explode('/', $last_id);
        if(isset($parts[1])) {
            $urut = (int)$parts[1] + 1;
        } else {
            $urut = 1;
        }
    } else {
        $urut = 1;
    }
    $id_pengajuan = 'TR/' . sprintf("%03d", $urut) . '/' . $bulan_ini . '/' . $tahun_ini;

    // Insert ke tabel pengajuan_training
    $sql = "INSERT INTO pengajuan_training (
                id_pengajuan, npp, nama_karyawan, id_bagian, jenis_training,
                judul_training, tujuan_training, penyelenggara,
                tanggal_mulai, tanggal_selesai, lokasi_training,
                budget_total, konfirmasi_mengikuti, tanggal_pengajuan, status
            ) VALUES (
                '$id_pengajuan', '$npp', '$nama_karyawan', '$id_bagian', '$jenis_training',
                '$judul_training', '$tujuan_training', '$penyelenggara',
                '$tanggal_mulai', '$tanggal_selesai', '$lokasi_training',
                '$budget_total', '$konfirmasi_mengikuti', '$tanggal_pengajuan', '$status'
            )";
    
    $query = mysqli_query($conn, $sql);
    
    if($query) {
        // Insert budget details ke tabel training_rincian
        $budget_items = [
            ['nama' => 'Biaya Pendaftaran', 'nilai' => $budget_1],
            ['nama' => 'Biaya Transportasi', 'nilai' => $budget_2],
            ['nama' => 'Biaya Akomodasi', 'nilai' => $budget_3],
            ['nama' => 'Biaya Lain-lain', 'nilai' => $budget_4]
        ];
        
        foreach($budget_items as $item) {
            if($item['nilai'] > 0) {
                $sql_detail = "INSERT INTO training_rincian (
                                    id_pengajuan, nama_item, nilai
                                ) VALUES (
                                    '$id_pengajuan', '{$item['nama']}', '{$item['nilai']}'
                                )";
                mysqli_query($conn, $sql_detail);
            }
        }
        
        echo "<script type='text/javascript'>
                alert('Pengajuan training berhasil disimpan!'); 
                document.location = 'training_status.php'; 
            </script>";
    } else {
        echo "<script type='text/javascript'>
                alert('Terjadi kesalahan, silahkan coba lagi! Error: " . mysqli_error($conn) . "'); 
                document.location = 'form_pengajuan_training.php'; 
            </script>";
    }
?>
