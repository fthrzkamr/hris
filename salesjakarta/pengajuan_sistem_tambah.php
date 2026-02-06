<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Data Pengajuan Sistem";
	$menuparent = "master";
	include("layout_top.php");
	$npp = $sess_mngid;

	$sql_sess = "SELECT * FROM employee WHERE npp='". $chk_sess ."'";
	$ress_sess = mysqli_query($conn, $sql_sess);
	$data  = mysqli_fetch_array($ress_sess);
?>
<script type="text/javascript">
	function checkNppAvailability() {
	$("#loaderIcon").show();
	jQuery.ajax({
		url: "check_nppavailability.php",
		data:'id='+$("#id").val(),
		type: "POST",
		success:function(data){
			$("#user-availability-status").html(data);
			$("#loaderIcon").hide();
		},
		error:function (){}
	});
	}
</script>
<!-- top of file -->
		<!-- Page Content -->
		<div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">Data Pengajuan Sistem</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->

				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<form class="form-horizontal" action="pengajuan_sistem_insert.php" method="POST" enctype="multipart/form-data">
							<div class="panel panel-default">
								<div class="panel-heading"><h3>Tambah Data Pengajuan Sistem</h3></div>
								<div class="panel-body">
									<div class="form-group">
										<label class="control-label col-sm-3">Nama</label>
										<div class="col-sm-4">
                                            <input type="text" name="nama_karyawan" class="form-control" value="<?php echo $data['nama_emp'] ?>" required readonly>
											<input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>
											<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Pengajuan Sistem</label>
										<div class="col-sm-4">
											<textarea name="deskripsi_pengajuan" class="form-control" rows="5" wrap="off" required></textarea>
										</div>
										<div class="col-sm-5">
											<small class="form-text text-muted">*Deskripsikan dengan detail dan jelas</small>
										</div>
									</div>
                                    
                                    <div class="form-group">
                                        <label class="control-label col-sm-3">Bagian</label>
                                        <div class="col-sm-4">
                                            <select name="nama_bagian" id="nama_bagian" class="form-control" required>
                                                <option value="" selected>--- Pilih Bagian ---</option>
                                                <option value="Finance">Finance</option>
                                                <option value="Warehouse">Warehouse</option>
                                                <option value="HR">HR</option>
                                                <option value="HRD">HRD</option>
                                                <option value="Apoteker">Apoteker</option>
                                            </select>
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