<?php
	include("sess_check.php");
	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");
	
	// $sql = "SELECT * FROM employee WHERE npp='". $sess_mngid ."'";
	// $ress = mysqli_query($conn, $sql);
	// $datas = mysqli_fetch_array($ress);
	// deskripsi halaman
	$pagedesc = "Approval Cuti";
	$menuparent = "approval";
	include("layout_top.php");
	$now = date('Y-m-d');
	$Sql = "SELECT cuti.*, employee.* FROM cuti, employee WHERE cuti.npp=employee.npp AND cuti.no_cuti='$_GET[no]'";
	$Qry = mysqli_query($conn, $Sql);
	$data = mysqli_fetch_array($Qry);

?>
<!-- <script type="text/javascript">
$(document).ready(function() {
    $('#aksi').change(function(){
        if($(this).val() === '2'){
            $('#reject').attr('disabled', false);
        }else{
            $('#reject').attr('disabled', 'disabled');
        }
    });

});
</script> -->
<script type="text/javascript">
$(document).ready(function() {
    $('#aksi').change(function(){
        if($(this).val() === '2'){
            $('#reject').attr('disabled', false);
        }else {
			$('#reject').attr('disabled', 'disabled');
            $('#reject1').attr('disabled', 'disabled');
            $('#reject2').attr('disabled', 'disabled');

        }
    });

});
$(document).ready(function() {
    $('#aksi').change(function(){
        if($(this).val() === '1'){
			$('#reject').attr('disabled', true);
            $('#reject1').attr('disabled', false);
			$('#reject2').attr('disabled', false);

        }else {
			$('#reject').attr('disabled', false);
            $('#reject1').attr('disabled', true);
            $('#reject2').attr('disabled', true);

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
                        <h1 class="page-header">Data Approval Cuti</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->

				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<form class="form-horizontal" name="cuti" action="approval_update.php" method="POST" enctype="multipart/form-data" onSubmit="return valid();">
							<div class="panel panel-default">
								<div class="panel-heading"><h3>Review Pengajuan Cuti</h3></div>
								<div class="panel-body">
								<div class="form-group">
										<label class="control-label col-sm-3">No. Cuti</label>
										<div class="col-sm-4">
											<input type="text" name="no" class="form-control" value="<?php echo $data['no_cuti'];?>" readonly>

										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Nama Karyawan</label>
										<div class="col-sm-4">
											<input type="text" name="mulai" class="form-control" value="<?php echo $data['nama_emp'];?> " readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Tanggal Pengajuan</label>
										<div class="col-sm-4">
											<input type="text" name="mulai" class="form-control" value="<?php echo IndonesiaTgl($data['tgl_pengajuan']);?> " readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Tanggal Mulai Cuti</label>
										<div class="col-sm-4">
											<input type="text" name="mulai"  class="form-control" value="<?php echo IndonesiaTgl($data['tgl_awal']);?>" disabled>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Tanggal Akhir Cuti</label>
										<div class="col-sm-4">
											<input type="text" name="mulai"  class="form-control" value="<?php echo IndonesiaTgl($data['tgl_akhir']);?> " disabled>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Durasi Cuti</label>
										<div class="col-sm-4">
											<input type="text" name="mulai" class="form-control" value="<?php echo $data['durasi'];?> " readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Tipe Cuti</label>
										<div class="col-sm-4">
											<input type="text" name="mulai" class="form-control" value="<?php echo $data['tipe_cuti'];?> " readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Keterangan Lengkap Cuti</label>
										<div class="col-sm-4">
											<textarea name="keterangan" class="form-control" placeholder="Keterangan" rows="3" readonly><?php echo $data['keterangan'];?></textarea>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Aksi</label>
										<div class="col-sm-4">
											<select name="aksi" id="aksi" class="form-control" required>
												<option value="" selected>---- Pilih Aksi ----</option>
												<option value="1">Approved</option>
												<option value="2">Rejected</option>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">ACC Tanggal Awal Cuti</label>
										<div class="col-sm-4">
											<input type="date" name="tgl_awal" id="reject1" class="form-control" 
												value="<?php echo $data['tgl_awal']; ?>" >
										</div>
									</div>

									<div class="form-group">
										<label class="control-label col-sm-3">ACC Tanggal Akhir Cuti</label>
										<div class="col-sm-4">
											<input type="date" name="tgl_akhir" id="reject2" class="form-control" 
												value="<?php echo $data['tgl_akhir']; ?>" >
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Keterangan </label>
										<div class="col-sm-4">
											<textarea name="reject" id="reject" class="form-control" placeholder="Keterangan" rows="3" disabled></textarea>
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