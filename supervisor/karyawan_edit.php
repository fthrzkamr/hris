<?php
	include("sess_check.php");
	
	if(isset($_GET['npp'])) {
		$sql = "SELECT * FROM employee WHERE npp='". $_GET['npp'] ."'";
		$ress = mysqli_query($conn, $sql);
		$data = mysqli_fetch_array($ress);
	}
	// deskripsi halaman
	$pagedesc = "Data Karyawan";
	$menuparent = "master";
	include("layout_top.php");
?>
<script type="text/javascript">
	function checkNppAvailability() {
	$("#loaderIcon").show();
	jQuery.ajax({
		url: "check_nppavailability.php",
		data:'npp='+$("#npp").val(),
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
                        <h1 class="page-header">Data Karyawan</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->

				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<form class="form-horizontal" action="karyawan_update.php" method="POST" enctype="multipart/form-data">
							<div class="panel panel-default">
								<div class="panel-heading"><h3>Edit Data</h3></div>
								<div class="panel-body">
									<div class="form-group">
										<label class="control-label col-sm-3">NIK </label>
										<div class="col-sm-4">
											<input type="text" name="npplama" class="form-control" placeholder="NPP" value="<?php echo $data['npp'] ?>" readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Nama Karyawan </label>
										<div class="col-sm-4">
											<input type="text" name="nama"  class="form-control" placeholder="limit" value="<?php echo $data['nama_emp'] ?>"required  readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Status Reimburse</label>
										<div class="col-sm-4">
											<select name="status_rem" id="status_rem" class="form-control" required>
												<option value="" selected>--- Pilih ---</option>
												<option value="Aktif">Aktif</option>
												<option value="N/A">N / A</option>
											</select>
										</div>
										
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Limit </label>
										<div class="col-sm-4">
											<input type="number" name="kesehatan" class="form-control" placeholder="limit" value="<?php echo $data['kesehatan'] ?>"required >
										</div>
									</div>
								</div>
								<div class="panel-footer">
									<button type="submit" name="perbarui" class="btn btn-success">Update</button>
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