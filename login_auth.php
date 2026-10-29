<?php
session_start();
include("dist/config/koneksi.php");

if (isset($_POST['login'])) {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        header("Location: login.php?err=empty");
        exit();
    }

    $username = mysqli_real_escape_string($conn, trim(strip_tags($_POST['username'])));
    $password = mysqli_real_escape_string($conn, trim(strip_tags($_POST['password'])));

    $sql = "SELECT * FROM employee WHERE npp='$username' AND password='$password'";
    $ress = mysqli_query($conn, $sql);

    if ($ress && mysqli_num_rows($ress) > 0) {
        $dataku = mysqli_fetch_assoc($ress);
        $role = $dataku['hak_akses'] ?? '';

        // Pemetaan hak akses ke session key dan halaman tujuan
        $roleMap = [
            'Admin'            => ['session' => 'admin',            'dest' => 'index.php'],
            'managerhr'        => ['session' => 'managerhr',        'dest' => 'managerhr/index.php'],
            'Manageropr'       => ['session' => 'manageropr',       'dest' => 'manageropr/index.php'],
            'karyawan'         => ['session' => 'karyawan',         'dest' => 'karyawan/index.php'],
            'Karyawan'         => ['session' => 'karyawan',         'dest' => 'karyawan/index.php'],
            'Pegawai'          => ['session' => 'pegawai',          'dest' => 'pegawai/index.php'],
            'supervisor'       => ['session' => 'supervisor',       'dest' => 'supervisor/index.php'],
            'direktur'         => ['session' => 'direktur',         'dest' => 'direktur/index.php'],
            'apj'              => ['session' => 'apj',              'dest' => 'apj/index.php'],
            'finance'          => ['session' => 'finance',          'dest' => 'finance/index.php'],
            'gudang'           => ['session' => 'gudang',           'dest' => 'gudang/index.php'],
            'operasional'      => ['session' => 'operasional',      'dest' => 'operasionalk/index.php'],
            'asmen_warehouse'  => ['session' => 'asmen_warehouse',  'dest' => 'asmen_warehouse/index.php'],
            'asmen_accounting' => ['session' => 'asmen_accounting', 'dest' => 'asmen_accounting/index.php'],
            'asmen_bdo'        => ['session' => 'asmen_bdo',        'dest' => 'asmen_bdo/index.php'],
            'pajak'            => ['session' => 'pajak',            'dest' => 'pajak/index.php'],
            'salesjakarta'     => ['session' => 'salesjakarta',     'dest' => 'salesjakarta/index.php'],
            'financear'        => ['session' => 'financear',        'dest' => 'financear/index.php'],
            'cabangmedan'      => ['session' => 'cabangmedan',      'dest' => 'cabangmedan/index.php'],
            'cabangbali'       => ['session' => 'cabangbali',       'dest' => 'cabangbali/index.php'],
            'salestangerang'   => ['session' => 'salestangerang',   'dest' => 'salestangerang/index.php'],
            'cabangsurabaya'   => ['session' => 'cabangsurabaya',   'dest' => 'cabangsurabaya/index.php'],
            'salessurabaya'    => ['session' => 'salessurabaya',    'dest' => 'salessurabaya/index.php'],
            'ga'               => ['session' => 'ga',               'dest' => 'ga/index.php'],
            'it'               => ['session' => 'it',               'dest' => 'it/index.php'],
            'cabangbekasi'     => ['session' => 'cabangbekasi',     'dest' => 'cabangbekasi/index.php'],
            'koor_ga'          => ['session' => 'koor_ga',          'dest' => 'koor_ga/index.php'],
        ];

        if (isset($roleMap[$role])) {
            $sessKey = $roleMap[$role]['session'];
            $dest = $roleMap[$role]['dest'];
            $_SESSION[$sessKey] = $dataku['npp'];

            echo '<script type="text/javascript">
                    alert("Sistem ini bersifat rahasia! Harap menjaga kerahasiaan data dan informasi di dalamnya.");
                    document.location="' . $dest . '?login=success";
                  </script>';
            exit();
        }
    }

    echo '<script type="text/javascript">
            alert("Username dan password tidak terdaftar!");
            document.location="login.php";
          </script>';
    exit();
}
?>
