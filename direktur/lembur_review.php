<?php
	include("sess_check.php");
	
	
	// deskripsi halaman
	$pagedesc = "Approval Lembur";
	$menuparent = "approval";
	include("layout_top.php");
	$now = date('Y-m-d');
	$Sql = "SELECT lembur.*, employee.* FROM lembur, employee WHERE lembur.npp=employee.npp AND lembur.id_lmbr='$_GET[no]'";
	$Qry = mysqli_query($conn, $Sql);
	$data = mysqli_fetch_array($Qry);
	// var_dump($Sql);
	// exit;
?>
<script type="text/javascript">
$(document).ready(function() {
    $('#aksi').change(function(){
        if($(this).val() === '2'){
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
                        <h1 class="page-header">Data Approval Lemburan</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->

				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<form class="form-horizontal" name="lembur" action="lembur_update.php" method="POST" enctype="multipart/form-data" onSubmit="return valid();">
							<div class="panel panel-default">
								<div class="panel-heading"><h3>Review Pengajuan Lemburan</h3></div>
								<div class="panel-body">
								
									<div class="form-group">
										<label class="control-label col-sm-3">No. Lemburan</label>
										<div class="col-sm-4">
											<input type="text" name="no" class="form-control" value="<?php echo $data['id_lmbr'];?>" readonly>
										</div>
									</div>

									<div class="form-group">
										<label class="control-label col-sm-3">Nama Karyawan</label>
										<div class="col-sm-4">
											<input type="text" name="mulai" class="form-control" value="<?php echo $data['nama_karyawan'];?> " readonly>
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Tujuan Lembur </label>
										<div class="col-sm-4">
											<!-- <input type="hidden" name="id_rmbs" value="<?php echo $data['id_rmbs'] ?>"> -->
											<input type="text" name="fasilitas_kesehatan" class="form-control" value="<?php echo $data['tujuan_lembur'];?> " readonly>
										
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Alasan Lembur</label>
										<div class="col-sm-4">
											<input type="text" name="nama_dokter" class="form-control" value="<?php echo $data['alasan_lembur'];?> " readonly>
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Cabang</label>
										<div class="col-sm-4">
											<input type="text" name="total_kwitansi" class="form-control" value="<?php echo $data['cabang'];?> " readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Tanggal Lembur</label>
										<div class="col-sm-4">
											<input type="text" name="tanggal_pemeriksaan" class="form-control" value="<?php echo IndonesiaTgl($data['tgl_lembur']);?> " readonly>
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Jam Mulai Lembur</label>
										<div class="col-sm-4">
											<input type="text" name="total_kwitansi" class="form-control" value="<?php echo $data['jam_mulai_lembur'];?> " readonly>
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Jam Berakhir Lembur</label>
										<div class="col-sm-4">
											<input type="text" name="total_kwitansi" class="form-control" value="<?php echo $data['jam_berakhir_lembur'];?> " readonly>
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Nama Koordinator</label>
										<div class="col-sm-4">
											<input type="text" name="total_rembes" class="form-control" value="<?php echo $data['nama_koordinator'];?> " readonly>
										</div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-3">Jumlah Karyawan Lembur</label>
										<div class="col-sm-4">
											<input type="text" name="total_rembes" class="form-control" value="<?php echo $data['jumlah'];?> " readonly>
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