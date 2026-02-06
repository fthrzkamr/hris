<?php
	include("sess_check.php");
	
	
		$sql = "SELECT * FROM employee WHERE npp='". $sess_mngid ."'";
		$ress = mysqli_query($conn, $sql);
		$data = mysqli_fetch_array($ress);

	// deskripsi halaman
	$pagedesc = "Data Karyawan";
	$menuparent = "master";
	include("layout_top.php");
?>

<!-- top of file -->
		<!-- Page Content -->
		<div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                      <!-- </Center> <h1 class="page-header">Data Karyawan</h1> -->
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->

				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<form class="form-horizontal" action="ubah_foto_update.php" method="POST" enctype="multipart/form-data">
							<div class="panel panel-default">
							<Center><div class="panel-heading"><h1> Data Karyawan</h1></div></Center>
								<div class="panel-body">
								
									<div class="form-group">
										<label class="control-label col-sm-3">Nama Lengkap</label>
										<div class="col-sm-4">
											<input type="text" name="nama_emp" class="form-control"  value="<?php echo $data['nama_emp'] ?>" required>
											<input type="hidden" name="npp" value="<?php echo $data['npp'] ?>">

										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Jenis Kelamin</label>
										<div class="col-sm-4">
											<select name="jk_emp" id="jk" class="form-control" required>
												<option value="<?php echo $data['jk_emp'] ?>" selected><?php echo $data['jk_emp'] ?></option>
												<option value="Laki-Laki">Laki-Laki</option>
												<option value="Perempuan">Perempuan</option>
											</select>
										</div>	
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Nomor KTP</label>
										<div class="col-sm-4">
											<input type="number" name="nomor_ktp" class="form-control"  value="<?php echo $data['nomor_ktp'] ?>" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Alamat KTP</label>
										<div class="col-sm-4">
											<textarea name="alamat" class="form-control" placeholder="Alamat" rows="3" required><?php echo $data['alamat'] ?></textarea>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Alamat Domisili</label>
										<div class="col-sm-4">
											<textarea name="alamat_tinggal_sekarang" class="form-control" placeholder="Alamat" rows="3" required><?php echo $data['alamat_tinggal_sekarang'] ?></textarea>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Nomor Telepon</label>
										<div class="col-sm-4">
											<input type="number" name="telp_emp" min="0" class="form-control" placeholder="Telepon" value="<?php echo $data['telp_emp'] ?>"required>
										</div>
									</div>
									
									<div class="form-group">
										<label class="control-label col-sm-3">Nama Pasangan Kawin</label>
										<div class="col-sm-4">
											<input type="text" name="nama_pasangan" min="0" class="form-control" value="<?php echo $data['nama_pasangan'] ?>">
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Nomor Hp Pasangan Kawin</label>
										<div class="col-sm-4">
											<input type="number" name="nomor_tlp" min="0" class="form-control" placeholder="Telepon" value="<?php echo $data['nomor_tlp'] ?>">
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