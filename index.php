<?php
include("sess_check.php");

$id = $sess_admid;
$id_esc = mysqli_real_escape_string($conn, $id);

$sisa_cuti = 0;
$dalam_pengajuan = 0;
$total_pengajuan = 0;
$total_lembur = 0;

// Ambil profil karyawan dan sisa cuti dalam satu query
$sql_g = "SELECT COALESCE(B.nama_bagian, A.nama_bagian) AS nama_bagian, B.id_bagian, A.npp, A.nama_emp, A.cabang, A.foto_emp, A.jml_cuti 
          FROM employee AS A 
          LEFT JOIN bagian AS B ON A.nama_bagian = B.id_bagian 
          WHERE A.npp = '$id_esc'";
$ress_g = mysqli_query($conn, $sql_g);
$res = mysqli_fetch_assoc($ress_g) ?: [];
$sisa_cuti = $res['jml_cuti'] ?? 0;

// Hitung pengajuan cuti yang sedang berjalan
$q_pending = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM cuti WHERE npp='$id_esc' AND (stt_cuti LIKE 'Menunggu%' OR stt_cuti IN ('Diajukan','Pending'))");
if ($q_pending) {
    $rowp = mysqli_fetch_assoc($q_pending);
    $dalam_pengajuan = $rowp['cnt'] ?? 0;
}

// Hitung total pengajuan cuti
$q_total = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM cuti WHERE npp='$id_esc'");
if ($q_total) {
    $rowt = mysqli_fetch_assoc($q_total);
    $total_pengajuan = $rowt['cnt'] ?? 0;
}

// Hitung total pengajuan lembur
$q_lembur = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM lembur WHERE npp='$id_esc'");
if ($q_lembur) {
    $rowl = mysqli_fetch_assoc($q_lembur);
    $total_lembur = $rowl['cnt'] ?? 0;
}

// Deskripsi halaman
$pagedesc = "Beranda";
include("layout_top.php");
include("dist/function/format_rupiah.php");
?>
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <form class="form-horizontal">
                    <div class="panel panel-default">
	<div class="panel-body">
		<style>
			.beranda-welcome-title {
				text-align: center;
				margin-top: 10px;
				margin-bottom: 15px;
				font-weight: 600;
				font-size: 26px;
			}
			.beranda-profile-wrap {
				display: flex;
				align-items: center;
				justify-content: center;
				gap: 40px;
				padding: 15px 20px;
				flex-wrap: wrap;
			}
			.beranda-profile-img {
				width: 160px;
				height: 160px;
				border-radius: 12px;
				object-fit: cover;
				box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
				flex-shrink: 0;
			}
			.beranda-profile-table {
				border-collapse: collapse;
			}
			.beranda-profile-table th,
			.beranda-profile-table td {
				padding: 8px 0;
				font-size: 15px;
				vertical-align: middle;
				text-align: left;
			}
			.beranda-profile-table .label-col {
				font-weight: 700;
				color: #555;
				min-width: 85px;
			}
			.beranda-profile-table .sep-col {
				width: 28px;
				text-align: center;
				font-weight: 700;
				color: #555;
			}
			.beranda-profile-table .val-col {
				font-weight: 700;
				color: #222;
			}
			@media (max-width: 576px) {
				.beranda-welcome-title {
					font-size: 20px;
				}
				.beranda-profile-wrap {
					flex-direction: column;
					gap: 18px;
					padding: 10px;
				}
				.beranda-profile-img {
					width: 135px;
					height: 135px;
				}
				.beranda-profile-table th,
				.beranda-profile-table td {
					font-size: 14px;
					padding: 6px 0;
				}
			}
		</style>
		<h2 class="beranda-welcome-title">Selamat Datang, <?php echo htmlspecialchars($res['nama_emp'] ?? ''); ?> !</h2>
		<hr/>
		<?php
		$foto_emp = trim($res['foto_emp'] ?? '');
		if ($foto_emp !== '' && is_file(__DIR__ . "/foto/" . $foto_emp)) {
			$foto_src = "foto/" . rawurlencode($foto_emp);
		} elseif ($foto_emp !== '' && is_file(__DIR__ . "/foto/karyawan/" . $foto_emp)) {
			$foto_src = "foto/karyawan/" . rawurlencode($foto_emp);
		} else {
			$foto_src = "foto/default-user.svg";
		}
		?>
		<div class="beranda-profile-wrap">
			<img src="<?php echo $foto_src; ?>" alt="Foto Karyawan" class="beranda-profile-img" onerror="this.onerror=null;this.src='foto/default-user.svg';" />
			<table class="beranda-profile-table">
				<tr>
					<th class="label-col">NIK</th>
					<td class="sep-col">:</td>
					<td class="val-col"><?php echo htmlspecialchars($res['npp'] ?? ''); ?></td>
				</tr>
				<tr>
					<th class="label-col">CABANG</th>
					<td class="sep-col">:</td>
					<td class="val-col"><?php echo htmlspecialchars($res['cabang'] ?? ''); ?></td>
				</tr>
				<tr>
					<th class="label-col">BAGIAN</th>
					<td class="sep-col">:</td>
					<td class="val-col"><?php echo htmlspecialchars($res['nama_bagian'] ?? ''); ?></td>
				</tr>
			</table>
		</div>
	</div>
</div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4 col-md-4">
                <div class="panel panel-primary">
                    <a href="cuti_create.php">
                        <div class="panel-footer">
                            <span class="pull-left">Pengajuan Cuti</span>
                            <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </div>
                    </a>
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3"></div>
                            <div class="col-xs-9 text-right">
                                <div class="huge text-bold"></div>
                                <div><h4>Data Cuti</h4></div>
                            </div>
                        </div>
                    </div>
                    <a href="cuti_app.php">
                        <div class="panel-footer">
                            <span class="pull-left">Lihat Rincian</span>
                            <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="col-lg-4 col-md-4">
                <div class="panel panel-yellow">
                    <a href="lembur_create.php">
                        <div class="panel-footer">
                            <span class="pull-left">Pengajuan Lembur</span>
                            <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </div>
                    </a>
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3"></div>
                            <div class="col-xs-9 text-right bold">
                                <div class="huge text-bold"></div>
                                <div><h4>Data Lemburan</h4></div>
                            </div>
                        </div>
                    </div>
                    <a href="lembur_waitapp.php">
                        <div class="panel-footer">
                            <span class="pull-left">Lihat Rincian</span>
                            <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include("layout_bottom.php");
?>
