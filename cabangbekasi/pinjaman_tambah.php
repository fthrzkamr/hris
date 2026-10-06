<?php
include("sess_check.php");

if (isset($row_sess['status_karyawan']) && $row_sess['status_karyawan'] == 'Magang') {
	header("location: index.php");
	exit;
}

$pagedesc = "Pengajuan Pinjaman";
$menuparent = "pinjaman";
include("layout_top.php");

$now = date('Y-m-d');
$npp = $sess_mngid;
$nama_emp = $sess_mngname;
?>

<script type="text/javascript">
function formatRupiah(angka, prefix) {
	let number_string = angka.replace(/[^,\d]/g, '').toString(),
		split = number_string.split(','),
		sisa  = split[0].length % 3,
		rupiah  = split[0].substr(0, sisa),
		ribuan  = split[0].substr(sisa).match(/\d{3}/gi);

	if (ribuan) {
		let separator = sisa ? '.' : '';
		rupiah += separator + ribuan.join('.');
	}
	return split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
}

function toggleTenorInput() {
	let select = document.getElementById('tenor');
	let manualInput = document.getElementById('tenor_manual');
	let manualDiv = document.getElementById('tenor_manual_div');
	
	if (select.value === 'manual') {
		manualDiv.style.display = 'block';
		manualInput.required = true;
	} else {
		manualDiv.style.display = 'none';
		manualInput.required = false;
		manualInput.value = '';
	}
	hitungCicilan();
}

function hitungCicilan() {
	let jumlahPinjaman = document.getElementById('jumlah_pinjaman').value.replace(/\./g, '');
	let tenor = document.getElementById('tenor').value;
	let tenorManual = document.getElementById('tenor_manual').value;
	let cicilan = 0;
	
	// Gunakan tenor manual jika dipilih
	let tenorAkhir = (tenor === 'manual' && tenorManual) ? parseInt(tenorManual) : parseInt(tenor);

	if (jumlahPinjaman && tenorAkhir) {
		cicilan = Math.floor(parseInt(jumlahPinjaman) / tenorAkhir);
	}

	document.getElementById('cicilan_per_bulan').value = formatRupiah(cicilan.toString());
}

function hanyaAngka(evt) {
	var charCode = (evt.which) ? evt.which : event.keyCode;
	if (charCode > 31 && (charCode < 48 || charCode > 57)) {
		return false;
	}
	return true;
}
</script>

<div id="page-wrapper">
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-12">
				<h1 class="page-header">Pengajuan Pinjaman</h1>
			</div>
		</div>

		<div class="row">
			<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
		</div>

		<div class="row">
			<div class="col-lg-12">
				<div class="alert alert-info">
					<h4><i class="fa fa-info-circle"></i> <strong>Syarat dan Ketentuan Pinjaman Karyawan</strong></h4>
					<ol style="margin-left: -20px; margin-bottom: 0;">
						<li>Karyawan berstatus PKWT atau PKWTT.</li>
						<li>Memiliki masa kerja minimal 1 (satu) tahun.</li>
						<li>Tidak sedang memiliki pinjaman berjalan atau pinjaman sebelumnya telah lunas.</li>
						<li>Pengajuan pinjaman maksimal 50% dari gaji.</li>
						<li>Cicilan maksimal 30% dari gaji per bulan.</li>
						<li>Pinjaman hanya diberikan untuk kebutuhan yang bersifat mendesak (<em>urgent</em>).</li>
						<li>Pengajuan pinjaman wajib mendapatkan persetujuan Manajemen.</li>
						<li>Pengajuan pinjaman tidak otomatis disetujui dan akan dipertimbangkan berdasarkan kebutuhan serta kondisi perusahaan.</li>
						<li>Potongan cicilan dilakukan melalui penggajian setiap bulan hingga pinjaman lunas.</li>
						<li>Pencairan pinjaman dilakukan bersamaan dengan pembayaran insentif dan lembur.</li>
						<li>Karyawan yang sedang dalam proses pengunduran diri (<em>resign</em>) atau pemutusan hubungan kerja tidak dapat mengajukan pinjaman.</li>
						<li>Apabila karyawan mengundurkan diri sebelum pinjaman lunas, sisa pinjaman akan diperhitungkan dan dipotong dari hak-hak karyawan yang masih menjadi kewajiban perusahaan.</li>
					</ol>
				</div>
			</div>
		</div>

		
		<div class="row">
			<div class="col-lg-12">
				<form class="form-horizontal" name="pinjaman" action="pinjaman_insert.php" method="POST">
					<div class="panel panel-default">
						<div class="panel-heading"><h3>Form Pengajuan Pinjaman</h3></div>
						<div class="panel-body">
							<div class="form-group">
								<label class="control-label col-sm-3">Tanggal Pengajuan</label>
								<div class="col-sm-4">
									<input type="date" name="tanggal_pengajuan" class="form-control" value="<?= $now ?>" required>
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-sm-3">Nama Karyawan</label>
								<div class="col-sm-4">
									<input type="text" class="form-control" value="<?= htmlspecialchars($nama_emp) ?>" readonly>
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-sm-3">NPP</label>
								<div class="col-sm-4">
									<input type="text" class="form-control" value="<?= htmlspecialchars($npp) ?>" readonly>
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-sm-3">Jumlah Pinjaman</label>
								<div class="col-sm-4">
									<input type="text" id="jumlah_pinjaman" name="jumlah_pinjaman" class="form-control" placeholder="Masukkan jumlah pinjaman" required onkeyup="this.value = formatRupiah(this.value); hitungCicilan();" onkeypress="return hanyaAngka(event)">
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-sm-3">Tenor</label>
								<div class="col-sm-4">
									<select name="tenor" id="tenor" class="form-control" onchange="toggleTenorInput();" required>
										<option value="">-- Pilih Tenor --</option>
										<option value="1">1 Bulan</option>
										<option value="2">2 Bulan</option>
										<option value="3">3 Bulan</option>
										<option value="4">4 Bulan</option>
										<option value="5">5 Bulan</option>
										<option value="6">6 Bulan</option>
										<option value="7">7 Bulan</option>
										<option value="8">8 Bulan</option>
										<option value="9">9 Bulan</option>
										<option value="10">10 Bulan</option>
										<option value="11">11 Bulan</option>
										<option value="12">12 Bulan</option>
										<option value="manual">Input Manual</option>
									</select>
								</div>
							</div>

							<div class="form-group" id="tenor_manual_div" style="display: none;">
								<label class="control-label col-sm-3">Tenor Manual (Bulan)</label>
								<div class="col-sm-4">
									<input type="number" id="tenor_manual" name="tenor_manual" class="form-control" placeholder="Masukkan jumlah bulan" min="1" onkeyup="hitungCicilan();">
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-sm-3">Cicilan per Bulan</label>
								<div class="col-sm-4">
									<input type="text" id="cicilan_per_bulan" name="cicilan_per_bulan" class="form-control" readonly required>
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-sm-3">Keterangan</label>
								<div class="col-sm-4">
									<textarea name="keterangan" class="form-control" placeholder="Tuliskan keterangan tambahan jika ada..."></textarea>
								</div>
							</div>
						</div>

						<div class="panel-footer">
							<button type="submit" name="simpan" class="btn btn-success">Ajukan Pinjaman</button>
							<a href="pinjaman.php" class="btn btn-default">Batal</a>
						</div>
					</div><!-- /.panel -->
				</form>
			</div><!-- /.col-lg-12 -->
		</div><!-- /.row -->
	</div><!-- /.container-fluid -->
</div><!-- /#page-wrapper -->

<?php include("layout_bottom.php"); ?>
