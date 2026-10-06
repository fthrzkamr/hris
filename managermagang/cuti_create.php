<?php
	include("sess_check.php");
	

	
	$sql = "SELECT * FROM employee WHERE npp='". $sess_mngid ."'";
	$ress = mysqli_query($conn, $sql);
	$data = mysqli_fetch_array($ress);
	// deskripsi halaman
	$pagedesc = "Buat Pengajuan";
	$menuparent = "cuti";
	include("layout_top.php");
	$now = date('Y-m-d');
	$npp = $sess_mngid;
?>
<script type="text/javascript">
function valid()
{
	if(document.cuti.akhir.value < document.cuti.mulai.value){
		alert("Tanggal akhir cuti harus lebih besar dari tanggal mulai cuti!");
		return false;
	}

	return true;
}
</script>
<!-- <script type="text/javascript">
$(document).ready(function() {
    $('#tujuan').change(function(){
        if($(this).val() === '4'){
            $('#reject').attr('disabled', false);
        }else{
            $('#reject').attr('disabled', 'disabled');
        }
    });

});
</script> -->
<!-- top of file -->
		<!-- Page Content -->
		<div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">Pengajuan Cuti</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->

				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<form class="form-horizontal" name="cuti" action="cuti_insert.php" method="POST" enctype="multipart/form-data" onSubmit="return valid();">
							<div class="panel panel-default">
								<div class="panel-heading"><h3>Form Pengajuan Cuti</h3></div>
								<div class="panel-body">
									<div class="form-group">
										<label class="control-label col-sm-3">Sisa Cuti</label>
										<div class="col-sm-4">
											<input type="text" class="form-control" required value="<?php echo $data['jml_cuti'] ?>" readonly>
										</div>
									</div>

									<div class="form-group">
										<label class="control-label col-sm-3">Mulai Cuti</label>
										<div class="col-sm-4">
											<input type="date" name="mulai" class="form-control"  required>
											<input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>
											<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required>
										</div>
									</div>
									

									<div class="form-group">
										<label class="control-label col-sm-3">Akhir Cuti</label>
										<div class="col-sm-4">
											<input type="date" name="akhir" class="form-control"  required>
										</div>
									</div>
									<!-- <div class="form-group">
										<label class="control-label col-sm-3">Durasi Cuti</label>
										<div class="col-sm-4">
											<input type="text" class="form-control" required value="" readonly>
										</div>
									</div> -->

									<!-- tujuan cuti -->
									<div class="form-group">
										<label class="control-label col-sm-3">Tipe Cuti</label>
										<div class="col-sm-4">
											<select name="tipe_cuti" id="tipe_cuti" class="form-control" required>
												<option value="" selected>---- Pilih Tipe Cuti ----</option>
												<option value="cuti tahunan">Cuti Tahunan (12 Hari)</option>
												<option value="cuti menikah">Cuti Menikah (3 Hari)</option>
												<option value="cuti hamil">Cuti Hamil (90 Hari )</option>
												<option value="keluarga inti">Keluarga Inti Meninggal (2 Hari)</option>
											</select>
										</div>
									</div>
									<!-- end tujuan cuti -->

									<div class="form-group">
										<label class="control-label col-sm-3">Keterangan</label>
										<div class="col-sm-4">
											<textarea name="keterangan" class="form-control" placeholder="Keterangan" rows="3" required></textarea>
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