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
						<form class="form-horizontal" name="reimburse" action="reimburse_insert.php" method="POST" enctype="multipart/form-data" onSubmit="return valid();">
							<div class="panel panel-default">
								<div class="panel-heading"><h3>Form Pengajuan Reimburse</h3>
								<!-- <button type="submit" name="simpan" class="btn btn-success">Simpan</button> -->
							</div>
								<div class="panel-body">
									<div class="form-group">
										<label class="control-label col-sm-3">Nama Pasien</label>
										<div class="col-sm-4">
									<input type="text" name="nama_anggota_keluarga" class="form-control" placeholder="Masukan Nama Lengkap" required>
											<!-- <input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>
											<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required> -->
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Status Pasien</label>
										<div class="col-sm-4">
											<select name="hubungan_keluarga" id="hubungan_keluarga" class="form-control" required>
												<option value="" selected>---- Pilih Hubungan Keluarga ----</option>
												<option value="Pasangan Kawin"><?php echo $result['nama_emp'];?></option>
												<option value="Anak">Anak</option>
												<option value="Tidak Ada">Tidak Ada</option>

												
											</select>
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Nama Fasilitas Kesehatan</label>
										<div class="col-sm-4">
											<input type="text" name="nama_fasilitas_kesehatan" class="form-control" placeholder="Nama Fasilitas Kesehatan" required>
											<input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>
											<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required>
										</div>
									</div>

									<div class="form-group">
										<label class="control-label col-sm-3">Alamat Fasilitas Kesehatan</label>
										<div class="col-sm-4">
											<input type="text" name="fasilitas_kesehatan" class="form-control" placeholder="Alamat Fasilitas Kesehatan" required>
											<!--<input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>-->
											<!--<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required>-->
										</div>
									</div>
									
                                    <div class="form-group">
										<label class="control-label col-sm-3">Nama Dokter</label>
										<div class="col-sm-4">
											<input type="text" name="nama_dokter" class="form-control" placeholder="Nama Dokter (jika Periksa dokter)" required>
											<!-- <input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>
											<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required> -->
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
											<!-- <input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>
											<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required> -->
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Total Kwitansi</label>
										<div class="col-sm-4">
											<input type="number" name="total_kwitansi" class="form-control" placeholder="" required>
											<!-- <input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>
											<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required> -->
										</div>
									</div>
									
									<!-- <div class="form-group">
										<label class="control-label col-sm-3">Total Reimburse</label>
										<div class="col-sm-4">
											<input type="number" name="total_kwitansi" class="form-control" placeholder="" required>
											
										</div>
									</div> -->
									<div class="form-group">
										<label class="control-label col-sm-3">Sisa LImit</label>
										<div class="col-sm-4">
											<input type="number" class="form-control" value="<?php echo $result['kesehatan'];?>" placeholder="" required readonly>
											<!-- <input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>
											<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required> -->
										</div>
									</div>
                                   
									<!-- <div class="form-group">
										<label class="control-label col-sm-3">Sisa Limit</label>
										<div class="col-sm-4">
											<input type="number" name="total_rembes" class="form-control" placeholder="" required readonly>
											
										</div>
									</div> -->


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