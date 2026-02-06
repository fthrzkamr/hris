<?php
	include("sess_check.php");
	
	$pagedesc = "Pengajuan Pinjaman";
	$menuparent = "pinjaman";
	include("layout_top.php");

	$now = date('Y-m-d');

	// Ambil semua karyawan
	$sql_emp = "SELECT * FROM employee ORDER BY nama_emp ASC";
	$res_emp = mysqli_query($conn, $sql_emp);
?>

<script type="text/javascript">
let employeeData = {};

<?php while ($emp = mysqli_fetch_assoc($res_emp)) : ?>
	employeeData["<?= $emp['npp'] ?>"] = "<?= addslashes($emp['nama_emp']) ?>";
<?php endwhile; ?>

function updateNPP() {
	let select = document.getElementById('nama_karyawan');
	let selectedNPP = select.value;
	document.getElementById('npp').value = selectedNPP;
	document.getElementById('npp_display').value = selectedNPP;
}

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

function hitungCicilan() {
	let jumlahPinjaman = document.getElementById('jumlah_pinjaman').value.replace(/\./g, '');
	let tenor = document.getElementById('tenor').value;
	let cicilan = 0;

	if (jumlahPinjaman && tenor) {
		cicilan = Math.floor(parseInt(jumlahPinjaman) / parseInt(tenor));
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
				<form class="form-horizontal" name="pinjaman" action="pinjaman_insert.php" method="POST">
					<div class="panel panel-default">
						<div class="panel-heading"><h3>Form Pengajuan Pinjaman</h3></div>
						<div class="panel-body">
							<input type="hidden" name="tanggal_pengajuan" value="<?= $now ?>">
							<input type="hidden" name="npp" id="npp">

							<div class="form-group">
								<label class="control-label col-sm-3">Nama Karyawan</label>
								<div class="col-sm-4">
									<select class="form-control" name="nama_karyawan" id="nama_karyawan" onchange="updateNPP();" required>
										<option value="">-- Pilih Karyawan --</option>
										<?php
											mysqli_data_seek($res_emp, 0); // reset pointer
											while ($data = mysqli_fetch_array($res_emp)) {
												echo "<option value='".$data['npp']."'>".$data['nama_emp']."</option>";
											}
										?>
									</select>
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-sm-3">NPP</label>
								<div class="col-sm-4">
									<input type="text" class="form-control" id="npp_display" readonly>
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
									<select name="tenor" id="tenor" class="form-control" onchange="hitungCicilan();" required>
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
									</select>
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
						</div>
					</div><!-- /.panel -->
				</form>
			</div><!-- /.col-lg-12 -->
		</div><!-- /.row -->
	</div><!-- /.container-fluid -->
</div><!-- /#page-wrapper -->

<?php include("layout_bottom.php"); ?>
