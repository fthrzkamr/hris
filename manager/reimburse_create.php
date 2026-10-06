<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Buat Pengajuan";
	$menuparent = "reimburse";
	include("layout_top.php");
	$now = date('Y-m-d');
	$npp = $sess_mngid;
	
		// $kode = $_GET['code'];
		$sql = "SELECT * FROM employee WHERE npp='". $npp."'";
		$query = mysqli_query($conn,$sql);
		$result = mysqli_fetch_array($query);
	
?>

<script type="text/javascript">
$(document).ready(function() {
    $('#tujuan').change(function(){
        if($(this).val() === '4'){
            $('#reject').attr('disabled', false);
        }else{
            $('#reject').attr('disabled', 'disabled');
        }
    });

});

function formatRupiah(input) {
    let value = input.value.replace(/[^0-9]/g, ""); // Hanya angka
    let formatted = new Intl.NumberFormat('id-ID').format(value); // Format ke rupiah
    input.value = formatted;
}
</script>
<!-- top of file -->
		<!-- Page Content -->
		<div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">Pengajuan Reimburse</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->

				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<div class="alert alert-info" style="font-size: 13px;">
							<strong>Adapun pembaruan dan ketentuan yang perlu diperhatikan adalah sebagai berikut:</strong>
							<ol style="margin-top: 10px; margin-bottom: 0; padding-left: 20px;">
								<li>Penambahan fitur klaim kacamata dengan limit sebesar Rp500.000 (maksimal 1 kali klaim per tahun).</li>
								<li>Reimbursement sebesar 80% dari total pengajuan, yang mencakup pembelian obat dan vitamin, serta tidak berlaku untuk kebutuhan kecantikan (beauty).</li>
								<li>Pengajuan klaim hanya dapat dilakukan maksimal 30 hari kalender sejak tanggal pada kwitansi.</li>
								<li>Struk dan kwitansi asli wajib diserahkan kepada HR (Auliya) sebagai dokumen pendukung.</li>
								<li>Dokumen harus jelas dan valid (mencantumkan tanggal transaksi dan nominal). HR berhak menolak klaim apabila dokumen tidak lengkap atau tidak sesuai.</li>
								<li>Reimbursement akan diproses pada saat tanggal transfer insentif dan lembur.</li>
								<li>Klaim dapat ditolak apabila:
									<ul style="padding-left: 20px;">
										<li>Tidak sesuai dengan kategori yang ditentukan</li>
										<li>Melebihi limit yang berlaku</li>
										<li>Dokumen tidak lengkap atau tidak valid</li>
									</ul>
								</li>
								<li>Untuk pertanyaan lebih lanjut, harap dapat langsung menghubungi HR (Auliya).</li>
								<li>Perusahaan berhak untuk meninjau dan memperbarui kebijakan ini sewaktu-waktu sesuai kebutuhan.</li>
							</ol>
						</div>
					</div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<form class="form-horizontal" name="reimburse" action="reimburse_insert.php" method="POST" enctype="multipart/form-data" onSubmit="return valid();">
							<div class="panel panel-default">
								<div class="panel-heading"><h3>Form Pengajuan Reimburse</h3>
							</div>
								<div class="panel-body">
									<div class="form-group">
										<label class="control-label col-sm-3">Nama Pasien</label>
										<div class="col-sm-4">
											<input type="text" name="nama_anggota_keluarga" class="form-control"  placeholder="Masukan Nama Lengkap" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Status Pasien</label>
										<div class="col-sm-4">
											<select name="hubungan_keluarga" id="hubungan_keluarga" class="form-control" required>
												<option value="" selected>---- Pilih Hubungan Keluarga ----</option>
												<option value="Karyawan">Karyawan</option>
												<option value="Pasangan">Pasangan </option>
												<option value="Anak">Anak</option>

												
											</select>
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Nama Fasilitas Kesehatan</label>
										<div class="col-sm-4">
											<input type="text" name="nama_fasilitas_kesehatan" class="form-control" placeholder="Nama Fasilitas Kesehatan" >
											<input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>
											<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required>
										</div>
									</div>

									<div class="form-group">
										<label class="control-label col-sm-3">Alamat Fasilitas Kesehatan</label>
										<div class="col-sm-4">
											<input type="text" name="fasilitas_kesehatan" class="form-control" placeholder="Alamat Fasilitas Kesehatan" >
										</div>
									</div>
									
                                    <div class="form-group">
										<label class="control-label col-sm-3">Nama Dokter</label>
										<div class="col-sm-4">
											<input type="text" name="nama_dokter" class="form-control" placeholder="Nama Dokter (jika Periksa dokter)" required>
										</div>
									</div>

                                    <div class="form-group">
										<label class="control-label col-sm-3">Tanggal Pemeriksaan</label>
										<div class="col-sm-4">
											<input type="date" name="tanggal_pemeriksaan" class="form-control" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">No Kwitansi</label>
										<div class="col-sm-4">
											<input type="text" name="no_kwitansi" class="form-control" placeholder="" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Total Kwitansi</label>
										<div class="col-sm-4">
											<input type="text" name="total_kwitansi" class="form-control" placeholder="" required onkeyup="formatRupiah(this)">

										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Sisa Limit</label>
										<div class="col-sm-4">
											<input type="text" class="form-control" value="Rp <?php echo number_format($result['kesehatan'], 0, ',', '.'); ?>"  readonly>
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Upload Dokumen</label>
										<div class="col-sm-4">
											<input type="file" name="foto" class="form-control" accept="image/*" required>
										</div>
									</div>
								</div>
								<div class="panel-footer">
									<button type="submit" name="simpan" class="btn btn-success">Simpan</button>
								</div>
							</div><!-- /.panel -->
						</form>
					</div><!-- /.col-lg-12 -->
				</div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div><!-- /#page-wrapper -->
<!-- bottom of file -->
<?php
	include("layout_bottom.php");
?>