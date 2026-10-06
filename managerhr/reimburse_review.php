<?php
include("sess_check.php");

// Deskripsi halaman
$pagedesc = "Approval Kesehatan";
$menuparent = "approval";
include("layout_top.php");

$now = date('Y-m-d');
$Sql = "SELECT rembes.*, employee.* FROM rembes, employee 
        WHERE rembes.npp = employee.npp 
        AND rembes.id_rmbs = '$_GET[no]'";
$Qry = mysqli_query($conn, $Sql);
$data = mysqli_fetch_array($Qry);

$kesehatan = $data['total_kwitansi'];
$a		=(80 / 100);
$p		= $kesehatan * $a ;

// Fungsi untuk format Rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}
?>

<script type="text/javascript">
$(document).ready(function() {
    $('#aksi').change(function(){
        $('#reject').prop('disabled', $(this).val() !== '2');
    });
});
</script>


<!-- top of file -->
		<!-- Page Content -->
		<div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">Data Approval Reimburse</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->

				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<form class="form-horizontal" name="rembes" action="reimburse_update.php" method="POST" enctype="multipart/form-data" onSubmit="return valid();">
							<div class="panel panel-default">
								<div class="panel-heading"><h3>Review Pengajuan Reimburse</h3></div>
								<div class="panel-body">
								
									<div class="form-group">
										<label class="control-label col-sm-3">No. Reimburse</label>
										<div class="col-sm-4">
											<input type="text" name="no" class="form-control" value="<?php echo $data['id_rmbs'];?>" readonly>
										</div>
									</div>

									<div class="form-group">
										<label class="control-label col-sm-3">Nama Karyawan</label>
										<div class="col-sm-4">
											<input type="text" name="mulai" class="form-control" value="<?php echo $data['nama_emp'];?> " readonly>
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Fasilitas Kesehatan</label>
										<div class="col-sm-4">
											<input type="text" name="fasilitas_kesehatan" class="form-control" value="<?php echo $data['nama_fasilitas_kesehatan'];?> " readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Alamat</label>
										<div class="col-sm-4">
											<input type="text" name="fasilitas_kesehatan" class="form-control" value="<?php echo $data['fasilitas_kesehatan'];?> " readonly>
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Nama dokter</label>
										<div class="col-sm-4">
											<input type="text" name="nama_dokter" class="form-control" value="<?php echo $data['nama_dokter'];?> " readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Tanggal Pemeriksaan</label>
										<div class="col-sm-4">
											<input type="text" name="tanggal_pemeriksaan" class="form-control" value="<?php echo IndonesiaTgl($data['tanggal_pemeriksaan']);?> " readonly>
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Total Kwitansi</label>
										<div class="col-sm-4">
											<input type="text" name="total_kwitansi" class="form-control" value="<?php echo formatRupiah($data['total_kwitansi']); ?>" readonly>
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Total Reimburse</label>
										<div class="col-sm-4">
											<input type="text" name="total_rembes" class="form-control" value="<?php echo formatRupiah($p)?>" readonly>
										</div>
									</div>

									<div class="form-group">
										<label class="control-label col-sm-3">Foto Bukti / Kwitansi</label>
										<div class="col-sm-4">
											<?php
											$foto_file = $data['foto'];
											$foto_path = "../dist/uploads/raim_kesehatan/" . $foto_file;
											$ext = strtolower(pathinfo($foto_file, PATHINFO_EXTENSION));
											
											if (!empty($foto_file)) {
												if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
													// Menampilkan pratinjau gambar jika file adalah gambar
													echo '<a href="'.$foto_path.'" target="_blank">';
													echo '<img src="'.$foto_path.'" class="img-responsive img-thumbnail" style="max-height: 250px; margin-bottom: 10px;" alt="Bukti Kwitansi">';
													echo '</a>';
												} else if (in_array($ext, ['pdf'])) {
													// Menampilkan tombol download/lihat jika file adalah PDF
													echo '<a href="'.$foto_path.'" target="_blank" class="btn btn-primary"><i class="fa fa-file-pdf-o"></i> Lihat Dokumen PDF</a>';
												} else {
													// File format lain
													echo '<a href="'.$foto_path.'" target="_blank" class="btn btn-default"><i class="fa fa-download"></i> Download File</a>';
												}
												echo '<p class="help-block"><small><i>Klik gambar/tombol di atas untuk melihat ukuran penuh.</i></small></p>';
												echo '<input type="hidden" name="foto" value="'.$foto_file.'">';
											} else {
												echo '<input type="text" class="form-control" value="Tidak ada foto terlampir" readonly>';
											}
											?>
										</div>
									</div>
									
									<div class="form-group">
										<label class="control-label col-sm-3">Status</label>
										<div class="col-sm-4">
											
										<select name="aksi" id="aksi" class="form-control" required>

												<option value="" selected>---- Pilih Aksi ----</option>
												<option value="1">Approved</option>
												<option value="2">Rejected</option>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Keterangan Reject</label>
										<div class="col-sm-4">
											<textarea name="reject" id="reject" class="form-control" placeholder="Keterangan Reject" rows="3" disabled></textarea>
										</div>
									</div>
								</div>
								<div class="panel-footer">
									<button type="submit" class="btn btn-success">Simpan</button>
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