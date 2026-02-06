<?php
include("sess_check.php");

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT p.*, e.nama_emp 
            FROM pinjaman p 
            JOIN employee e ON p.npp = e.npp 
            WHERE p.id_pinjaman = '$id'";
    $ress = mysqli_query($conn, $sql);
    $data = mysqli_fetch_array($ress);
}

$pagedesc = "Edit Pinjaman";
$menuparent = "pinjaman";
include("layout_top.php");
$now = date('Y-m-d');
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
				<h1 class="page-header">Edit Pengajuan Pinjaman</h1>
			</div>
		</div>

		<div class="row">
			<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
		</div>
		
		<div class="row">
			<div class="col-lg-12">
				<form class="form-horizontal" name="pinjaman" action="pinjaman_update.php" method="POST">
					<div class="panel panel-default">
						<div class="panel-heading"><h3>Form Edit Pengajuan Pinjaman</h3></div>
						<div class="panel-body">
							<input type="hidden" name="id_pinjaman" value="<?= $data['id_pinjaman'] ?>">
							<input type="hidden" name="npp" value="<?= $data['npp'] ?>">

							<div class="form-group">
								<label class="control-label col-sm-3">ID Pinjaman</label>
								<div class="col-sm-4">
									<input type="text" class="form-control" value="<?= $data['id_pinjaman'] ?>" readonly>
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-sm-3">Nama Karyawan</label>
								<div class="col-sm-4">
									<input type="text" class="form-control" value="<?= $data['nama_emp'] ?>" readonly>
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-sm-3">NPP</label>
								<div class="col-sm-4">
									<input type="text" class="form-control" value="<?= $data['npp'] ?>" readonly>
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-sm-3">Tanggal Pengajuan</label>
								<div class="col-sm-4">
									<input type="date" name="tanggal_pengajuan" class="form-control" value="<?= $data['tanggal_pengajuan'] ?>" required>
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-sm-3">Jumlah Pinjaman</label>
								<div class="col-sm-4">
									<input type="text" id="jumlah_pinjaman" name="jumlah_pinjaman" class="form-control" placeholder="Masukkan jumlah pinjaman" required value="<?= number_format($data['jumlah_pinjaman'], 0, ',', '.') ?>" onkeyup="this.value = formatRupiah(this.value); hitungCicilan();" onkeypress="return hanyaAngka(event)">
								</div>
							</div>

						<div class="form-group">
							<label class="control-label col-sm-3">Tenor</label>
							<div class="col-sm-4">
								<select name="tenor" id="tenor" class="form-control" onchange="toggleTenorInput();" required>
									<option value="">-- Pilih Tenor --</option>
									<?php for($i=1; $i<=12; $i++) { ?>
										<option value="<?= $i ?>" <?= ($data['tenor'] == $i) ? 'selected' : '' ?>><?= $i ?> Bulan</option>
									<?php } ?>
									<option value="manual" <?= ($data['tenor'] > 12) ? 'selected' : '' ?>>Input Manual</option>
								</select>
							</div>
						</div>

						<div class="form-group" id="tenor_manual_div" style="display: <?= ($data['tenor'] > 12) ? 'block' : 'none' ?>;">
							<label class="control-label col-sm-3">Tenor Manual (Bulan)</label>
							<div class="col-sm-4">
								<input type="number" id="tenor_manual" name="tenor_manual" class="form-control" placeholder="Masukkan jumlah bulan" min="1" value="<?= ($data['tenor'] > 12) ? $data['tenor'] : '' ?>" onkeyup="hitungCicilan();">
							</div>
						</div>							<div class="form-group">
								<label class="control-label col-sm-3">Cicilan per Bulan</label>
								<div class="col-sm-4">
									<input type="text" id="cicilan_per_bulan" name="cicilan_per_bulan" class="form-control" readonly required value="<?= number_format($data['cicilan_per_bulan'], 0, ',', '.') ?>">
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-sm-3">Status</label>
								<div class="col-sm-4">
									<select name="status" class="form-control" required>
										<option value="aktif" <?= ($data['status'] == 'aktif') ? 'selected' : '' ?>>Aktif</option>
										<option value="lunas" <?= ($data['status'] == 'lunas') ? 'selected' : '' ?>>Lunas</option>
									</select>
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-sm-3">Keterangan</label>
								<div class="col-sm-4">
									<textarea name="keterangan" class="form-control" placeholder="Tuliskan keterangan tambahan jika ada..." rows="3"><?= $data['keterangan'] ?></textarea>
								</div>
							</div>
						</div>

						<div class="panel-footer">
							<button type="submit" name="update" class="btn btn-success">Update Pinjaman</button>
							<a href="pinjaman.php" class="btn btn-default">Batal</a>
						</div>
					</div><!-- /.panel -->
				</form>
			</div><!-- /.col-lg-12 -->
		</div><!-- /.row -->
	</div><!-- /.container-fluid -->
</div><!-- /#page-wrapper -->

<?php include("layout_bottom.php"); ?>
