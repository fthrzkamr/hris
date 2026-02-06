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
                        <h1 class="page-header">Pengajuan Kacamata</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->

				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<form class="form-horizontal" name="reimburse" action="kacamata_insert.php" method="POST" enctype="multipart/form-data" onSubmit="return valid();">
							<div class="panel panel-default">
								<div class="panel-heading"><h3>Form Pengajuan Kacamata</h3>
								<!-- <button type="submit" name="simpan" class="btn btn-success">Simpan</button> -->
							</div>
								<div class="panel-body">
                                    <div class="form-group">
										<label class="control-label col-sm-3">Nama Karyawan</label>
										<div class="col-sm-4">
											<input type="text" name="nama_karyawan" class="form-control" value="<?php echo $result['nama_emp'] ?>"  readonly>
											<input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>
											<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required>
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Jenis Kacamata</label>
										<div class="col-sm-4">
											<input type="text" name="jenis_kacamata" class="form-control" placeholder="" required>
											<!-- <input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>
											<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required> -->
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Nama Tempat Fasilitas</label>
										<div class="col-sm-4">
											<input type="text" name="nama_fasilitas" class="form-control" placeholder="Nama Tempat Fasilitas" required>
											<input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>
											<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required>
										</div>
									</div>

									<div class="form-group">
										<label class="control-label col-sm-3">Alamat Tempat Fasilitas</label>
										<div class="col-sm-4">
											<input type="text" name="alamat_fasilitas" class="form-control" placeholder="Alamat Tempat Fasilitas" required>
											<!--<input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>-->
											<!--<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required>-->
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Tanggal Pengajuan</label>
										<div class="col-sm-4">
											<input type="date" name="tanggal_pengajuan" class="form-control" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">No Kwitansi</label>
										<div class="col-sm-4">
											<input type="text" name="no_kwintansi" class="form-control" placeholder="" required>
											<!-- <input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>
											<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required> -->
										</div>
									</div>
                                    <div class="form-group">
                                        <label class="control-label col-sm-3">Total Kwitansi</label>
                                        <div class="col-sm-4">
                                            <input type="text" id="total_kwintansi" name="total_kwintansi" class="form-control" placeholder="Masukkan jumlah" required onkeyup="formatRupiah(this)">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-sm-3">Sisa Limit</label>
                                        <div class="col-sm-4">
                                            <input type="text" id="sisaLimit" class="form-control" value="Rp <?php echo number_format($result['kacamata'], 0, ',', '.'); ?>" readonly>
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