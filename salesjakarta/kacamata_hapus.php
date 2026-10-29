<?php
include("sess_check.php");

// Pastikan ada parameter ID yang dikirim
if (isset($_GET['id_kacamata'])) {
    $id_kacamata = $_GET['id_kacamata'];

    // Ambil data pengajuan sebelum dihapus
    $sql = "SELECT * FROM kacamata WHERE id_kacamata='$id_kacamata'";
    $query = mysqli_query($conn, $sql);
    $data = mysqli_fetch_array($query);

    if ($data) {
        $npp = $data['npp'];
        $total_kwintansi = $data['total_kwintansi'];

        // Ambil limit kacamata saat ini
        $sql_cek = "SELECT kacamata FROM employee WHERE npp='$npp'";
        $res_cek = mysqli_query($conn, $sql_cek);
        $row_cek = mysqli_fetch_array($res_cek);
        $limit_sekarang = $row_cek['kacamata'];

        // Kembalikan limit kacamata
        $limit_baru = $limit_sekarang + ($total_kwintansi * 0.80); // 80% dari total kwitansi

        // Update limit kacamata
        $update = "UPDATE employee SET kacamata='$limit_baru' WHERE npp='$npp'";
        mysqli_query($conn, $update);

        // Hapus pengajuan dari database
        $delete = "DELETE FROM kacamata WHERE id_kacamata='$id_kacamata'";
        $result = mysqli_query($conn, $delete);

        if ($result) {
            echo "<script>
                    alert('Pengajuan berhasil dihapus dan limit dikembalikan!');
                    document.location = 'kacamata_rincian.php';
                  </script>";
        } else {
            echo "<script>
                    alert('Gagal menghapus pengajuan!');
                    document.location = 'kacamata_wait.php';
                  </script>";
        }
    } else {
        echo "<script>
                alert('Data tidak ditemukan!');
                document.location = 'kacamata_wait.php';
              </script>";
    }
} else {
    echo "<script>
            alert('Akses tidak valid!');
            document.location = 'kacamata_wait.php';
          </script>";
}
?>
