<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Detail Calon Karyawan";
	include("layout_top.php");
	include("dist/function/format_tanggal.php");
	
	if (!function_exists('tgl_eng_to_ind')) {
		function tgl_eng_to_ind($date) {
			$bulan = array('01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember');
			$split = explode('-', $date);
			return $split[2] . ' ' . $bulan[$split[1]] . ' ' . $split[0];
		}
	}
	
	$npp = mysqli_real_escape_string($conn, $_GET['npp']);
	$sql = "SELECT * FROM employee WHERE npp = '$npp'";
	$ress = mysqli_query($conn, $sql);
	$data = mysqli_fetch_array($ress);
	
	if(!$data) {
		echo '<script>alert("Data tidak ditemukan"); window.location="calon_karyawan_list.php";</script>';
		exit;
	}
?>
<!-- Page Content -->
<div id="page-wrapper">
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-12">
				<h1 class="page-header">Detail Calon Karyawan</h1>
			</div>
		</div>
		
		<div class="row">
			<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
		</div>
		
		<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="fa fa-user"></i> Informasi Calon Karyawan
						<div class="pull-right">
							<a href="calon_karyawan_list.php" class="btn btn-default btn-xs">Kembali</a>
						</div>
					</div>
					<div class="panel-body">
						<div class="row">
							<div class="col-md-3 text-center">
								<?php if(!empty($data['foto_emp']) && file_exists('../' . $data['foto_emp'])): ?>
									<img src="../<?php echo $data['foto_emp'] ?>" class="img-thumbnail" style="max-width: 200px;">
								<?php else: ?>
									<img src="../foto/default.png" class="img-thumbnail" style="max-width: 200px;" onerror="this.src='https://via.placeholder.com/200x250?text=No+Photo'">
								<?php endif; ?>
								<br><br>
								<p><strong>NPP: <?php echo $data['npp'] ?></strong></p>
								<p>
									<?php if($data['aktif'] == 'Menunggu Review'): ?>
										<span class="label label-warning">Menunggu Review</span>
									<?php else: ?>
										<span class="label label-success">Aktif</span>
									<?php endif; ?>
								</p>
							</div>
							
							<div class="col-md-9">
								<!-- Section 1: Profil Karyawan -->
								<div class="panel panel-info">
									<div class="panel-heading"><strong>1. Profil Karyawan</strong></div>
									<div class="panel-body">
										<table class="table table-condensed">
											<tr>
												<td width="30%"><strong>Nama Lengkap</strong></td>
												<td><?php echo $data['nama_emp'] ?></td>
											</tr>
											<tr>
												<td><strong>Jenis Kelamin</strong></td>
												<td><?php echo $data['jk_emp'] ?></td>
											</tr>
											<tr>
												<td><strong>Tempat, Tanggal Lahir</strong></td>
												<td><?php echo $data['kota_lahir'] . ', ' . tgl_eng_to_ind($data['tanggal_lahir']) ?></td>
											</tr>
											<tr>
												<td><strong>Alamat Sesuai KTP</strong></td>
												<td><?php echo nl2br($data['alamat']) ?></td>
											</tr>
											<tr>
												<td><strong>Alamat Tinggal Sekarang</strong></td>
												<td><?php echo nl2br($data['alamat_tinggal_sekarang']) ?></td>
											</tr>
											<tr>
												<td><strong>No. KTP</strong></td>
												<td><?php echo $data['nomor_ktp'] ?></td>
											</tr>
											<tr>
												<td><strong>No. Kartu Keluarga</strong></td>
												<td><?php echo $data['nomor_kk'] ?></td>
											</tr>
											<tr>
												<td><strong>Agama</strong></td>
												<td><?php echo $data['agama'] ?></td>
											</tr>
											<tr>
												<td><strong>Golongan Darah</strong></td>
												<td><?php echo $data['gol_darah'] ?></td>
											</tr>
											<tr>
												<td><strong>Status Perkawinan</strong></td>
												<td><?php echo ucwords($data['status_kawin']) ?></td>
											</tr>
											<tr>
												<td><strong>No. HP/Telepon</strong></td>
												<td><?php echo $data['telp_emp'] ?></td>
											</tr>
											<tr>
												<td><strong>No. HP Alternatif</strong></td>
												<td><?php echo $data['nomor_tlp'] ?></td>
											</tr>
										</table>
									</div>
								</div>
								
								<!-- Section 2: Pendidikan -->
								<div class="panel panel-success">
									<div class="panel-heading"><strong>2. Pendidikan Terakhir</strong></div>
									<div class="panel-body">
										<table class="table table-condensed">
											<tr>
												<td width="30%"><strong>Jenjang Pendidikan</strong></td>
												<td><?php echo $data['pendidikan_terakhir'] ?></td>
											</tr>
											<tr>
												<td><strong>Nama Institusi</strong></td>
												<td><?php echo $data['nama_institusi'] ?></td>
											</tr>
											<tr>
												<td><strong>Jurusan</strong></td>
												<td><?php echo $data['jurusan'] ?></td>
											</tr>
										</table>
									</div>
								</div>
								
								<!-- Section 3: Data Keluarga -->
								<div class="panel panel-warning">
									<div class="panel-heading"><strong>3. Data Keluarga</strong></div>
									<div class="panel-body">
										<table class="table table-condensed">
											<tr>
												<td width="30%"><strong>Nama Pasangan</strong></td>
												<td><?php echo $data['nama_pasangan'] ?></td>
											</tr>
											<tr>
												<td><strong>Pekerjaan Pasangan</strong></td>
												<td><?php echo $data['pekerjaan'] ?></td>
											</tr>
											<tr>
												<td><strong>Nama Anak</strong></td>
												<td><?php echo nl2br($data['nama_anak']) ?></td>
											</tr>
										</table>
									</div>
								</div>
								
								<!-- Section 4: Kontak Darurat -->
								<div class="panel panel-danger">
									<div class="panel-heading"><strong>4. Kontak Darurat</strong></div>
									<div class="panel-body">
										<table class="table table-condensed">
											<tr>
												<td width="30%"><strong>Kontak Darurat 1</strong></td>
												<td><?php echo $data['nomor_emrg_pr'] ?></td>
											</tr>
											<tr>
												<td><strong>Kontak Darurat 2</strong></td>
												<td><?php echo $data['nomor_emrg_kd'] ?></td>
											</tr>
										</table>
									</div>
								</div>
								
								<!-- Section 5: Data Tambahan -->
								<div class="panel panel-primary">
									<div class="panel-heading"><strong>5. Data Tambahan</strong></div>
									<div class="panel-body">
										<table class="table table-condensed">
											<tr>
												<td width="30%"><strong>No. NPWP</strong></td>
												<td><?php echo $data['nomor_npwp'] ?></td>
											</tr>
											<tr>
												<td><strong>No. BPJS Kesehatan</strong></td>
												<td><?php echo $data['bpjs_kesehatan'] ?></td>
											</tr>
											<tr>
												<td><strong>Nama Bank</strong></td>
												<td><?php echo $data['nama_bank'] ?></td>
											</tr>
											<tr>
												<td><strong>Nomor Rekening</strong></td>
												<td><?php echo $data['norek_mandiri'] ?></td>
											</tr>
											<tr>
												<td><strong>Penempatan Kerja (Cabang)</strong></td>
												<td><?php echo $data['cabang'] ?></td>
											</tr>
											<tr>
												<td><strong>Bagian/Departemen</strong></td>
												<td>
													<?php 
													// Get bagian name from id
													$id_bagian = $data['nama_bagian'];
													$sql_bagian = "SELECT nama_bagian FROM bagian WHERE id_bagian = '$id_bagian'";
													$res_bagian = mysqli_query($conn, $sql_bagian);
													if($res_bagian && mysqli_num_rows($res_bagian) > 0) {
														$row_bagian = mysqli_fetch_assoc($res_bagian);
														echo $row_bagian['nama_bagian'];
													} else {
														echo $data['nama_bagian'];
													}
													?>
												</td>
											</tr>
										</table>
									</div>
								</div>
								
								<!-- Action Buttons -->
								<div class="text-center" style="margin-top: 20px;">
									<a href="calon_karyawan_list.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
	include("layout_bottom.php");
?>
