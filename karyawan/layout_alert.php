<?php
	// login message
	if(isset($_GET['err']) && $_GET['err'] == "empty") {
		echo '<div class="alert alert-warning alert-dismissable">';
		echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
		echo "Maaf, nama pengguna atau kata sandi belum diisi.";
		echo '</div>';
	}
	elseif(isset($_GET['err']) && $_GET['err'] == "not_found") {
		echo '<div class="alert alert-danger alert-dismissable">';
		echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
		echo "Maaf, nama pengguna atau password salah.";
		echo '</div>';
	}

	// insert message
	if(isset($_GET['act']) && $_GET['act'] == "add" && $_GET['msg'] == "success") {
		echo '<div class="alert alert-success alert-dismissable">';
		echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
		echo "Data berhasil disimpan.";
		echo '</div>';
	}
	elseif(isset($_GET['act']) && $_GET['act'] == "add" && $_GET['msg'] == "fail") {
		echo '<div class="alert alert-danger alert-dismissable">';
		echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
		echo "Gagal menyimpan data.";
		echo '</div>';
	}
	
	// update message
	if(isset($_GET['act']) && $_GET['act'] == "update" && $_GET['msg'] == "success") {
		echo '<div class="alert alert-info alert-dismissable">';
		echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
		echo "Data berhasil diperbarui.";
		echo '</div>';
	}
	elseif(isset($_GET['act']) && $_GET['act'] == "update" && $_GET['msg'] == "fail") {
		echo '<div class="alert alert-danger alert-dismissable">';
		echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
		echo "Gagal memperbarui data.";
		echo '</div>';
	}
	elseif(isset($_GET['act']) && $_GET['act'] == "update" && $_GET['msg'] == "pwd_err_1") {
		echo '<div class="alert alert-danger alert-dismissable">';
		echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
		echo "Gagal memperbarui data, password lama tidak sesuai.";
		echo '</div>';
	}
	elseif(isset($_GET['act']) && $_GET['act'] == "update" && $_GET['msg'] == "pwd_err_2") {
		echo '<div class="alert alert-danger alert-dismissable">';
		echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
		echo "Gagal memperbarui data, password baru tidak sesuai.";
		echo '</div>';
	}
	
	// delete message
	if(isset($_GET['act']) && $_GET['act'] == "delete" && $_GET['msg'] == "success") {
		echo '<div class="alert alert-warning alert-dismissable">';
		echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
		echo "Data berhasil dihapus.";
		echo '</div>';
	}
	elseif(isset($_GET['act']) && $_GET['act'] == "delete" && $_GET['msg'] == "fail") {
		echo '<div class="alert alert-danger alert-dismissable">';
		echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
		echo "Gagal menghapus data.";
		echo '</div>';
	}

	if(isset($_GET['act']) && $_GET['act'] == "retur" && $_GET['msg'] == "fail") {
		echo '<div class="alert alert-warning alert-dismissable">';
		echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
		echo "Jumlah Retur tidak boleh lebih dari Jumlah Terima!";
		echo '</div>';
	}
	
	// rekap message
	if(isset($_GET['act']) && $_GET['act'] == "rekap" && $_GET['msg'] == "add_success") {
		echo '<div class="alert alert-success alert-dismissable">';
		echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
		echo "Data rekapitulasi kas/saldo akhir bulan ini berhasil disimpan.";
		echo '</div>';
	}
	if(isset($_GET['act']) && $_GET['act'] == "rekap" && $_GET['msg'] == "update_success") {
		echo '<div class="alert alert-info alert-dismissable">';
		echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
		echo "Data rekapitulasi kas/saldo akhir bulan ini berhasil diperbarui.";
		echo '</div>';
	}

	// session flash message (dipakai oleh processor/handler)
	if (isset($_SESSION['message']) && $_SESSION['message'] !== '') {
		$alert_type = isset($_SESSION['status']) ? strtolower((string)$_SESSION['status']) : 'info';
		if ($alert_type === 'error') { $alert_type = 'danger'; }
		if (!in_array($alert_type, ['success', 'info', 'warning', 'danger'], true)) { $alert_type = 'info'; }
		echo '<div class="alert alert-' . $alert_type . ' alert-dismissable">';
		echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
		echo htmlspecialchars((string)$_SESSION['message'], ENT_QUOTES, 'UTF-8');
		echo '</div>';
		unset($_SESSION['message']);
		unset($_SESSION['status']);
	}
?>