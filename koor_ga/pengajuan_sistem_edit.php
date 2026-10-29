<?php
	include("sess_check.php");
	
	if(isset($_GET['no_pengajuan'])) {
		$sql = "SELECT * FROM pengajuan_sistem WHERE no_pengajuan='". $_GET['no_pengajuan'] ."'";
		$ress = mysqli_query($conn, $sql);
		$data = mysqli_fetch_array($ress);
	}
	// deskripsi halaman
	$pagedesc = "Data Pengajuan Sistem";
	$menuparent = "master";
	include("layout_top.php");
?>

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
						<form class="form-horizontal" action="pengajuan_sistem_update.php" method="POST" enctype="multipart/form-data">
							<div class="panel panel-default">
								<div class="panel-heading"><h3>Edit Data Pengajuan Sitem</h3></div>
								<div class="panel-body">
                                    <div class="form-group">
										<label class="control-label col-sm-3">No Pengajuan</label>
										<div class="col-sm-4">
											<input type="text" name="no_pengajuan" class="form-control" value="<?php echo $data['no_pengajuan'] ?>" readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Nama</label>
										<div class="col-sm-4">
											<input type="hidden" name="npp" value="<?php echo $data['npp']; ?>">

											<input type="text" name="nama_karyawan" class="form-control" placeholder="Nama Lengkap" value="<?php echo $data['nama_karyawan'] ?>" readonly>
										</div>
									</div>
									<div class="form-group">
                                        <label class="control-label col-sm-3">Pengajuan Sistem</label>
                                        <div class="col-sm-4">
                                            <textarea name="deskripsi_pengajuan" class="form-control" rows="5" value="" readonly><?php echo $data['deskripsi_pengajuan']; ?></textarea>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-sm-3">Status Pengajuan</label>
                                        <div class="col-sm-4">
                                            <select name="status_pengajuan" id="status_pengajuan" class="form-control" required>
                                                <!-- Status pengajuan yang sedang dipilih -->
                                                <option value="<?php echo $data['status_pengajuan']; ?>" selected><?php echo ucwords($data['status_pengajuan']); ?></option>
                                                
                                                <!-- Opsi lain yang berbeda dengan status yang sedang dipilih -->
                                                <?php if(strtolower($data['status_pengajuan']) != 'belum dikerjakan') { ?>
                                                    <option value="Belum Dikerjakan">Belum Dikerjakan</option>
                                                <?php } ?>
                                                
                                                <?php if(strtolower($data['status_pengajuan']) != 'proses pengerjaan') { ?>
                                                    <option value="Proses Pengerjaan">Proses Pengerjaan</option>
                                                <?php } ?>
                                                
                                                <?php if(strtolower($data['status_pengajuan']) != 'sudah selesai') { ?>
                                                    <option value="Sudah Selesai">Sudah Selesai</option>
                                                <?php } ?>
                                            </select>
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