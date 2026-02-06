<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Buat Pengajuan";
	$menuparent = "Pengajuan";
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
                        <h1 class="page-header">Pengajuan Sistem</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->

				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<form class="form-horizontal" name="pengajuan" action="pengajuan_insert.php" method="POST" enctype="multipart/form-data" onSubmit="return valid();">
							<div class="panel panel-default">
								<div class="panel-heading"><h3>Form Pengajuan Sistem</h3>
								<!-- <button type="submit" name="simpan" class="btn btn-success">Simpan</button> -->
							</div>
								<div class="panel-body">
									<div class="form-group">
										<label class="control-label col-sm-3">Nama</label>
										<div class="col-sm-4">
											<input type="text" name="nama_karyawan" class="form-control" value="<?php echo $result['nama_emp'];?>" placeholder="" required>
                                            <input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required>
                                            <!-- <input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>
											<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required> -->
										</div>
									</div>
                                    <div class="form-group">
                                        <label class="control-label col-sm-3">Bagian</label>
                                        <div class="col-sm-4">
                                            <select id="nama_bagian" name="nama_bagian" class="form-control"  >
                                                <option value="" selected>--- Pilih Bagian ---</option>
                                                    <?php 
                                                        $data = mysqli_query($conn,"select * from bagian");
                                                        while($d = mysqli_fetch_array($data)){
                                                            ?>
                                                            <option value="<?php echo $d['id'] ?>"><?php echo $d['nama_bagian'] ?></option>
                                                            <?php
                                                        }
                                                    ?>				
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-sm-3">Pengajuan Sistem yg di butuhkan (Secara detail dan logis ) :</label>
                                        <div class="col-sm-4">
                                            <textarea name="pengajuan_dibutuhkan" class="form-control" placeholder="Pengajuan" rows="5" required></textarea>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-sm-3">Pengajuan Sistem yg di butuhkan (Secara detail dan logis ) :</label>
                                        <div class="col-sm-4">
                                            <textarea name="pengajuan_manfaat" class="form-control" placeholder="Pengajuan" rows="5" required></textarea>
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