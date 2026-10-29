<?php
include("sess_check.php");

// Pastikan ada parameter ID yang dikirim
if (isset($_GET['id_rmbs'])) {
    $id_rmbs = $_GET['id_rmbs'];

    // Ambil data pengajuan sebelum dihapus
    $sql = "SELECT * FROM rembes WHERE id_rmbs='$id_rmbs'";
    $query = mysqli_query($conn, $sql);
    $data = mysqli_fetch_array($query);

    if ($data) {
        $npp = $data['npp'];
        $total_kwitansi = $data['total_kwitansi'];

        // Ambil limit kesehatan saat ini
        $sql_cek = "SELECT kesehatan FROM employee WHERE npp='$npp'";
        $res_cek = mysqli_query($conn, $sql_cek);
        $row_cek = mysqli_fetch_array($res_cek);
        $limit_sekarang = $row_cek['kesehatan'];

        // Kembalikan limit kesehatan
        $limit_baru = $limit_sekarang + ($total_kwitansi * 0.80); // 80% dari total kwitansi

        // Update limit kesehatan
        $update = "UPDATE employee SET kesehatan='$limit_baru' WHERE npp='$npp'";
        mysqli_query($conn, $update);

        // Hapus pengajuan dari database
        $delete = "DELETE FROM rembes WHERE id_rmbs='$id_rmbs'";
        $result = mysqli_query($conn, $delete);

        if ($result) {
            echo "<script>
                    alert('Pengajuan berhasil dihapus dan limit dikembalikan!');
                    document.location = 'reimburse_waitapp.php';
                  </script>";
        } else {
            echo "<script>
                    alert('Gagal menghapus pengajuan!');
                    document.location = 'reimburse_waitapp.php';
                  </script>";
        }
    } else {
        echo "<script>
                alert('Data tidak ditemukan!');
                document.location = 'reimburse_waitapp.php';
              </script>";
    }
} else {
    echo "<script>
            alert('Akses tidak valid!');
            document.location = 'reimburse_waitapp.php';
          </script>";
}
?>
