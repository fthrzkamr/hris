<?php
	include("sess_check.php");
	
	if(isset($_GET['id_koordinator'])) {
		$sql = "SELECT * FROM koordinator WHERE id_koordinator='". $_GET['id_koordinator'] ."'";
		$ress = mysqli_query($conn, $sql);
		$data = mysqli_fetch_array($ress);
	}
	// deskripsi halaman
	$pagedesc = "Data Koordinator";
	$menuparent = "master";
	include("layout_top.php");
?>

<!-- top of file -->
		<!-- Page Content -->
		<div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">Data Koordinator</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->

				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<form class="form-horizontal" action="koordinator_update.php" method="POST" enctype="multipart/form-data">
							<div class="panel panel-default">
								<div class="panel-heading"><h3>Edit Data</h3></div>
								<div class="panel-body">
									<div class="form-group">
										<label class="control-label col-sm-3">Nama</label>
										<div class="col-sm-4">
											<input type="hidden" name="id_koordinator" value="<?php echo $data['id_koordinator']; ?>">

											<input type="text" name="nama_koordinator" class="form-control"  value="<?php echo $data['nama_koordinator'] ?>" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Tanggal</label>
										<div class="col-sm-4">
											<input type="date" name="created_at" class="form-control"  value="<?php echo $data['created_at'] ?>"required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Akses</label>
										<div class="col-sm-4">
											<input type="text" name="akses" class="form-control"  value="<?php echo $data['akses'] ?>" required>
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