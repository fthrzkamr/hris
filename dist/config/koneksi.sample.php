<?php
  // ==============================================================
  // HRIS - Konfigurasi Database (Template / Sample)
  // Salin file ini menjadi 'koneksi.php' di folder dist/config/
  // ==============================================================

  // Pengaturan Maintenance (Ubah ke true saat sedang maintenance)
  $is_maintenance = false;

  if ($is_maintenance && basename($_SERVER['PHP_SELF']) != 'maintenance.php') {
      header("Location: maintenance.php");
      exit();
  }

  // Pengaturan Kredensial Database
  // Ganti nilai di bawah ini dengan user dan password database hosting/cPanel Anda:
  $dbhost = "localhost";
  $dbuser = "u9621710_hris";     // User database cPanel
  $dbpass = "password_db_anda";   // Password user database cPanel
  $dbname = "u9621710_hris";     // Nama database cPanel

  $conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
  if (!$conn) {
      die("<div style='font-family:sans-serif;padding:30px;text-align:center;'>
            <h2>Gagal Terhubung ke Database</h2>
            <p>Silakan periksa kredensial database pada <code>dist/config/koneksi.php</code>.</p>
            <p style='color:red;'>Error: " . htmlspecialchars(mysqli_connect_error()) . "</p>
          </div>");
  }
  mysqli_set_charset($conn, 'utf8mb4');
?>
