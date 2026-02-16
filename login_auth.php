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
			// var_dump($dataku);
			// exit();
			if ($dataku['hak_akses'] == "Admin") {

				// membuat variabel session
				$_SESSION['admin'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: index.php?login=success");
			}else if ($dataku['hak_akses'] == "managerhr") {

				// membuat variabel session
				$_SESSION['managerhr'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: managerhr/index.php?login=success"); 
			
			}else if ($dataku['hak_akses'] == "Manageropr") {

				// membuat variabel session
				$_SESSION['manageropr'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: manageropr/index.php?login=success");
			} else if ($dataku['hak_akses'] == "Manager") {

				// membuat variabel session
				$_SESSION['manager'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: manager/index.php?login=success");

			} else if ($dataku['hak_akses'] == "Pegawai") {

				// membuat variabel session
				$_SESSION['pegawai'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: pegawai/index.php?login=success");
			} else if ($dataku['hak_akses'] == "supervisor") {

				// membuat variabel session
				$_SESSION['supervisor'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: supervisor/index.php?login=success");
			} else if ($dataku['hak_akses'] == "direktur") {

				// membuat variabel session
				$_SESSION['direktur'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: direktur/index.php?login=success");
			} else if ($dataku['hak_akses'] == "apj") {

				// membuat variabel session
				$_SESSION['apj'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: apj/index.php?login=success");
			} else if ($dataku['hak_akses'] == "finance") {

				// membuat variabel session
				$_SESSION['finance'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: finance/index.php?login=success");
			} else if ($dataku['hak_akses'] == "gudang") {

				// membuat variabel session
				$_SESSION['gudang'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: gudang/index.php?login=success");
			} else if ($dataku['hak_akses'] == "operasional") {

				// membuat variabel session
				$_SESSION['operasional'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: operasionalk/index.php?login=success");
			} else if ($dataku['hak_akses'] == "asmen_warehouse") {

				// membuat variabel session
				$_SESSION['asmen_warehouse'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: asmen_warehouse/index.php?login=success");
			} else if ($dataku['hak_akses'] == "asmen_accounting") {

				// membuat variabel session
				$_SESSION['asmen_accounting'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: asmen_accounting/index.php?login=success");
			} else if ($dataku['hak_akses'] == "asmen_bdo") {

				// membuat variabel session
				$_SESSION['asmen_bdo'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: asmen_bdo/index.php?login=success");
			} else if ($dataku['hak_akses'] == "pajak") {

				// membuat variabel session
				$_SESSION['pajak'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: pajak/index.php?login=success");
			} else if ($dataku['hak_akses'] == "salesjakarta") {

				// membuat variabel session
				$_SESSION['salesjakarta'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: salesjakarta/index.php?login=success");
			} else if ($dataku['hak_akses'] == "financear") {

				// membuat variabel session
				$_SESSION['financear'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: financear/index.php?login=success");
			} else if ($dataku['hak_akses'] == "cabangmedan") {

				// membuat variabel session
				$_SESSION['cabangmedan'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: cabangmedan/index.php?login=success");

			} else if ($dataku['hak_akses'] == "salestangerang") {

				// membuat variabel session
				$_SESSION['salestangerang'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: salestangerang/index.php?login=success");

			} else if ($dataku['hak_akses'] == "cabangsurabaya") {

				// membuat variabel session
				$_SESSION['cabangsurabaya'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: cabangsurabaya/index.php?login=success");

			} else if ($dataku['hak_akses'] == "salessurabaya") {

				// membuat variabel session
				$_SESSION['salessurabaya'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: salessurabaya/index.php?login=success");
			} else if ($dataku['hak_akses'] == "ga") {

				// membuat variabel session
				$_SESSION['ga'] = $dataku['npp'];
				// mengarahkan ke halaman indeks.php
				header("location: ga/index.php?login=success");

			} else if ($dataku['hak_akses'] == "it") {

				// membuat variabel session
				$_SESSION['it'] = $dataku['npp'];
				// menitrahkan ke halaman indeks.php
				header("location: it/index.php?login=success");
			}
		} else {
			// header("location:login.php?pesan=gagal");
			echo '<script language="javascript">alert("Username dan password tidak terdaftar!"); document.location="login.php";</script>';
		}
	}

}
?>