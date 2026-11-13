<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Buat Pengajuan";
	$menuparent = "lembur";
	include("layout_top.php");
	$now = date('Y-m-d');
	$npp = $sess_mngid;
	$sql_sess = "SELECT * FROM employee AS A LEFT JOIN koordinator AS B ON A.nama_koordinator = B.id_koordinator WHERE A.npp='". $chk_sess ."'";
	$ress_sess = mysqli_query($conn, $sql_sess);
	$data  = mysqli_fetch_array($ress_sess);
?>

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
                        <h1 class="page-header">Pengajuan Lembur</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->

				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<form class="form-horizontal" name="lembur" action="lembur_insert.php" method="POST" enctype="multipart/form-data" onSubmit="return valid();">
							<div class="panel panel-default">
								<div class="panel-heading"><h3>Form Pengajuan Lembur</h3></div>
								<div class="panel-body">
                                    <div class="form-group">
										<label class="control-label col-sm-3">Nama Karyawan</label>
										<div class="col-sm-4">
											<input type="text" name="nama_karyawan" class="form-control" value="<?php echo $data['nama_emp'] ?>" required readonly>
											<input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>
											<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required>
										</div>
									</div>
									
									<!-- tujuan cuti -->
									<div class="form-group">
										<label class="control-label col-sm-3">Tujuan Lembur</label>
										<div class="col-sm-4">
											<select name="tujuan_lembur" id="tujuan_lembur" class="form-control" required>
												<option value="" selected>---- Pilih Tujuan Lembur ----</option>
												<option value="Lembur Hari Libur Nasional">Lembur Hari Libur Nasional</option>
												<option value="Lembur Oprasional">Lembur Operasional</option>
												<option value="Lembur Stock Opname">Lembur Stock Opname</option>
												<option value="Lembur Lainnya">Lembur Lainnya</option>
											</select>
										</div>
									</div>
									<!-- end tujuan cuti -->

									<div class="form-group">
										<label class="control-label col-sm-3">Cabang</label>
										<div class="col-sm-4">
											<input type="text" name="cabang" class="form-control" " value="<?php echo $data['cabang'] ?>" readonly>
										</div>
									</div>

                                    <div class="form-group">
										<label class="control-label col-sm-3">Tanggal Lembur</label>
										<div class="col-sm-4">
											<input type="date" name="tgl_lembur" class="form-control" required>
										</div>
									</div>

                                    <div class="form-group">
										<label class="control-label col-sm-3">Jam Mulai Lembur</label>
										<div class="col-sm-4">
											<input type="time" name="jam_mulai_lembur" class="form-control" placeholder="Jam Mulai" required>
										</div>
									</div>


                                    <div class="form-group">
										<label class="control-label col-sm-3">Jam Berakhir Lembur</label>
										<div class="col-sm-4">
											<input type="time" name="jam_berakhir_lembur" class="form-control" placeholder="Jam Akhir" required>
										</div>
									</div>
                                    
                                    <div class="form-group">
										<label class="control-label col-sm-3">Nama Koordinator</label>
										<div class="col-sm-4">
											<input type="text" name="nama_koordinator" class="form-control" value="<?php echo $data['nama_koordinator'] ?>" required readonly>
										</div>
									</div>

								 
									<div class="form-group">
										<label class="control-label col-sm-3">Alasan Lembur</label>
										<div class="col-sm-4">
											<input type="text" name="alasan_lembur" class="form-control" placeholder="" required>
										</div>
									</div>

									<div class="form-group">
										<label class="control-label col-sm-3">Jumlah Karyawan Lembur</label>
										<div class="col-sm-4">
											<input type="number" name="jumlah" class="form-control" placeholder="" required>
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