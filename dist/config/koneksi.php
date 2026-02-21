<?php
  //error_reporting(0);

  $dbhost = "localhost";
  $dbuser = "root";
  $dbpass = "";
  $dbname = "u9621710_hris";

  $conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
  if (!$conn) {
      die("Tidak dapat terhubung ke database: " . mysqli_connect_error());
  }
  mysqli_set_charset($conn, 'utf8mb4');
?>