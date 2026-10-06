<?php
// memulai session
session_start();
// memanggil file koneksi
include("dist/config/koneksi.php");

// mengecek apakah tombol login sudah di tekan atau belum
if (isset($_POST['login'])) {
	// mengecek apakah username dan password sudah di isi atau belum
	if (empty($_POST['username']) || empty($_POST['password'])) {
		// mengarahkan ke halaman login.php
		header("location: login.php?err=empty");
	} else {
		// membaca nilai variabel username dan password
		$username = $_POST['username'];
		$password = $_POST['password'];
		// mencegah sql injection
		$username = htmlentities(trim(strip_tags($username)));
		$password = htmlentities(trim(strip_tags($password)));
		$sql = "SELECT * FROM employee WHERE npp='" . $username . "' AND password='" . $password . "'";
		$ress = mysqli_query($conn, $sql);
		$rows = mysqli_num_rows($ress);

		if ($rows > 0) {
			$dataku = mysqli_fetch_array($ress);
            $dest = "";
            
			if ($dataku['hak_akses'] == "Admin") {
				$_SESSION['admin'] = $dataku['npp'];
				$dest = "index.php";
			}else if ($dataku['hak_akses'] == "managerhr") {
				$_SESSION['managerhr'] = $dataku['npp'];
				$dest = "managerhr/index.php"; 
			}else if ($dataku['hak_akses'] == "Manageropr") {
				$_SESSION['manageropr'] = $dataku['npp'];
				$dest = "manageropr/index.php";
			} else if ($dataku['hak_akses'] == "Manager") {
				$_SESSION['manager'] = $dataku['npp'];
				$dest = "manager/index.php";
			} else if ($dataku['hak_akses'] == "Pegawai") {
				$_SESSION['pegawai'] = $dataku['npp'];
				$dest = "pegawai/index.php";
			} else if ($dataku['hak_akses'] == "supervisor") {
				$_SESSION['supervisor'] = $dataku['npp'];
				$dest = "supervisor/index.php";
			} else if ($dataku['hak_akses'] == "direktur") {
				$_SESSION['direktur'] = $dataku['npp'];
				$dest = "direktur/index.php";
			} else if ($dataku['hak_akses'] == "apj") {
				$_SESSION['apj'] = $dataku['npp'];
				$dest = "apj/index.php";
			} else if ($dataku['hak_akses'] == "finance") {
				$_SESSION['finance'] = $dataku['npp'];
				$dest = "finance/index.php";
			} else if ($dataku['hak_akses'] == "gudang") {
				$_SESSION['gudang'] = $dataku['npp'];
				$dest = "gudang/index.php";
			} else if ($dataku['hak_akses'] == "operasional") {
				$_SESSION['operasional'] = $dataku['npp'];
				$dest = "operasionalk/index.php";
			} else if ($dataku['hak_akses'] == "asmen_warehouse") {
				$_SESSION['asmen_warehouse'] = $dataku['npp'];
				$dest = "asmen_warehouse/index.php";
			} else if ($dataku['hak_akses'] == "asmen_accounting") {
				$_SESSION['asmen_accounting'] = $dataku['npp'];
				$dest = "asmen_accounting/index.php";
			} else if ($dataku['hak_akses'] == "asmen_bdo") {
				$_SESSION['asmen_bdo'] = $dataku['npp'];
				$dest = "asmen_bdo/index.php";
			} else if ($dataku['hak_akses'] == "pajak") {
				$_SESSION['pajak'] = $dataku['npp'];
				$dest = "pajak/index.php";
			} else if ($dataku['hak_akses'] == "salesjakarta") {
				$_SESSION['salesjakarta'] = $dataku['npp'];
				$dest = "salesjakarta/index.php";
			} else if ($dataku['hak_akses'] == "financear") {
				$_SESSION['financear'] = $dataku['npp'];
				$dest = "financear/index.php";
			} else if ($dataku['hak_akses'] == "cabangmedan") {
				$_SESSION['cabangmedan'] = $dataku['npp'];
				$dest = "cabangmedan/index.php";
			} else if ($dataku['hak_akses'] == "cabangbali") {
				$_SESSION['cabangbali'] = $dataku['npp'];
				$dest = "cabangbali/index.php";
			} else if ($dataku['hak_akses'] == "salestangerang") {
				$_SESSION['salestangerang'] = $dataku['npp'];
				$dest = "salestangerang/index.php";
			} else if ($dataku['hak_akses'] == "cabangsurabaya") {
				$_SESSION['cabangsurabaya'] = $dataku['npp'];
				$dest = "cabangsurabaya/index.php";
			} else if ($dataku['hak_akses'] == "salessurabaya") {
				$_SESSION['salessurabaya'] = $dataku['npp'];
				$dest = "salessurabaya/index.php";
			} else if ($dataku['hak_akses'] == "ga") {
				$_SESSION['ga'] = $dataku['npp'];
				$dest = "ga/index.php";
			} else if ($dataku['hak_akses'] == "it") {
				$_SESSION['it'] = $dataku['npp'];
				$dest = "it/index.php";
			} else if ($dataku['hak_akses'] == "cabangbekasi") {
				$_SESSION['cabangbekasi'] = $dataku['npp'];
				$dest = "cabangbekasi/index.php";
			} else if ($dataku['hak_akses'] == "koor_ga") {
				$_SESSION['koor_ga'] = $dataku['npp'];
				$dest = "koor_ga/index.php";
			}
            
            if ($dest != "") {
                echo '<script language="javascript">
                        alert("Sistem ini bersifat rahasia! Harap menjaga kerahasiaan data dan informasi di dalamnya."); 
                        document.location="'.$dest.'?login=success";
                      </script>';
            }
		}
 else {
			// header("location:login.php?pesan=gagal");
			echo '<script language="javascript">alert("Username dan password tidak terdaftar!"); document.location="login.php";</script>';
		}
	}

}
?>