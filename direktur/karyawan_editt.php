<?php
	include("sess_check.php");
	
	if(isset($_GET['npp'])) {
		$sql = "SELECT * FROM employee AS A 
        LEFT JOIN koordinator AS B ON A.nama_koordinator = B.id 
        LEFT JOIN bagian AS C ON A.nama_bagian = C.id 
        LEFT JOIN manager AS D ON A.nama_manager = D.id 
        WHERE A.npp='". $_GET['npp'] ."'";
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

<script type="text/javascript">
$(document).ready(function() {
    $('#status_kawin').change(function(){
        if($(this).val() === 'Sudah Menikah'){
            $('#reject').attr('disabled', false);
            $('#reject1').attr('disabled', false);
            $('#reject2').attr('disabled', false);
            $('#reject3').attr('disabled', false);
        }else{
            $('#reject').attr('disabled', 'disabled');
            $('#reject1').attr('disabled', 'disabled');
            $('#reject2').attr('disabled', 'disabled');
            $('#reject3').attr('disabled', 'disabled');

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
                        <h1 class="page-header">Data Karyawan</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->

				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<form class="form-horizontal" action="karyawan_updatee.php" method="POST" enctype="multipart/form-data">
				<div class="row">
					<div class="col-lg-12">
						<div class="panel panel-default">
							<div class="panel-heading"><h3>Edit Data</h3></div>
								<div class="panel-body">
									<div class="form-group">
										<label class="control-label col-sm-4">Id Karyawan</label>
										<div class="col-sm-4">
											<input type="text" name="npplama" class="form-control" placeholder="NPP" value="<?php echo $data['npp'] ?>" readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">NPP Baru (Abaikan jika tidak diubah)</label>
										<div class="col-sm-4">
											<input type="text" name="npp" onBlur="checkNppAvailability()" class="form-control" placeholder="NPP Baru (Abaikan Jika Tidak Ada Perubahan!)">
											<span id="user-availability-status" style="font-size:12px;"></span>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Nama Lengkap</label>
										<div class="col-sm-4">
											<input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" value="<?php echo $data['nama_emp'] ?>" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Tanggal Masuk Karyawan</label>
										<div class="col-sm-4">
										<input type="date" name="tanggal_masuk_karyawan" min="0" class="form-control" value="<?php echo $data['tanggal_masuk_karyawan'] ?>"required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Tanggal Lahir</label>
										<div class="col-sm-4">
											<input type="date" name="tanggal_lahir" min="0" class="form-control" value="<?php echo $data['tanggal_lahir'] ?>"required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Nomor KTP</label>
										<div class="col-sm-4">
											<input type="number" name="nomor_ktp" min="0" class="form-control" value="<?php echo $data['nomor_ktp'] ?>"required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Alamat KTP</label>
										<div class="col-sm-4">
											<textarea name="alamat" class="form-control" placeholder="Alamat Sesuai KTP" required><?php echo $data['alamat']?></textarea>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Alamat Tinggal Sekarang</label>
										<div class="col-sm-4">
											<textarea name="alamat_tinggal_sekarang" class="form-control" placeholder="Alamat Tinggal Sekarang" required><?php echo $data['alamat_tinggal_sekarang']?></textarea>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Kota Lahir</label>
										<div class="col-sm-4">
											<textarea name="kota_lahir" class="form-control" placeholder="Kota Lahir" required><?php echo $data['kota_lahir'] ?></textarea>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Pendidikan Terakhir</label>
                                        <div class="col-sm-4">
											<select name="pendidikan_terakhir" id="pendidikan_terakhir" class="form-control"  required>
												<option value="<?php echo $data['pendidikan_terakhir'] ?>" selected><?php echo $data['pendidikan_terakhir'] ?></option>
												<option value="SMP">SMP</option>
												<option value="SMK">SMK</option>
												<option value="SMA">SMA</option>
												<option value="S1">S1</option>
												<option value="S1">S2</option>
											</select>
                                        </div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Nama Institusi Pendidikan Terakhir</label>
										<div class="col-sm-4">
											<input type="text" name="nama_institusi" class="form-control" placeholder="Nama Institusi Pendidikan Terakhir" value="<?php echo $data['nama_institusi'] ?>" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Nomor Rekening Mandiri (Aktif)</label>
										<div class="col-sm-4">
											<input type="number" name="norek_mandiri" class="form-control" value="<?php echo $data['norek_mandiri'] ?>" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Jenis Kelamin</label>
										<div class="col-sm-4">
											<select name="jk" id="jk" class="form-control" required>
												<option value="<?php echo $data['jk_emp'] ?>" selected><?php echo $data['jk_emp'] ?></option>
												<option value="Laki-Laki">Laki-Laki</option>
												<option value="Perempuan">Perempuan</option>
											</select>
										</div>	
									</div>
									<div class="form-group">
                                        <label class="control-label col-sm-4">Nomor Handphone</label>
                                        <div class="col-sm-4">
                                            <input type="number" name="telp" min="0" class="form-control" placeholder="Nomor Handphone" value="<?php echo $data['telp_emp'] ?>" required>
                                        </div>
                                    </div>
									<div class="form-group">
										<label class="control-label col-sm-4">Nama Koordinator</label>
                                        <div class="col-sm-4">
											<select id="nama_koordinator" name="nama_koordinator" class="form-control" >
												<option value="<?php echo $data['id'] ?>" selected><?php echo $data['nama_koordinator'] ?></option>
													<?php 
														$datas = mysqli_query($conn,"select * from koordinator");
														while($d = mysqli_fetch_array($datas)){
															?>
															<option value="<?php echo $d['id'] ?>"><?php echo $d['nama_koordinator'] ?></option>
															<?php
														}
													?>				
											</select>
                                        </div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Nama Manager</label>
                                        <div class="col-sm-4">
											<select id="nama_manager" name="nama_manager" class="form-control" >
												<option value="<?php echo $data['id'] ?>" selected><?php echo $data['nama_manager'] ?></option>
													<?php 
														$datas = mysqli_query($conn,"select * from manager");
														while($d = mysqli_fetch_array($datas)){
															?>
															<option value="<?php echo $d['id'] ?>"><?php echo $d['nama_manager'] ?></option>
															<?php
														}
													?>				
											</select>
                                        </div>
									</div>
                                    <div class="form-group">
										<label class="control-label col-sm-4">Bagian</label>
                                        <div class="col-sm-4">
											<select id="nama_bagian" name="nama_bagian" class="form-control"  >
											    <option value="<?php echo $data['id'] ?>" selected><?php echo $data['nama_bagian'] ?></option>
													<?php 
														$datas = mysqli_query($conn,"select * from bagian");
														while($d = mysqli_fetch_array($datas)){
															?>
															<option value="<?php echo $d['id'] ?>"><?php echo $d['nama_bagian'] ?></option>
															<?php
														}
													?>				
											</select>
                                        </div>
									</div>
									<div class="form-group">
                                        <label class="control-label col-sm-4">Jumlah Cuti</label>
                                        <div class="col-sm-4">
                                            <input type="number" min="0" name="jml" class="form-control" placeholder="Jumlah Cuti" value="<?php echo $data['jml_cuti'] ?>" required>
                                        </div>
                                    </div>
									<div class="form-group">
										<label class="control-label col-sm-4">Cabang</label>
                                        <div class="col-sm-4">
											<select name="cabang" id="cabang" class="form-control" required>
                                                <option value="<?php echo $data['cabang'] ?>" selected><?php echo $data['cabang'] ?></option>
												<option value="Puri">Puri</option>
                                                <option value="Bekasi">Bekasi</option>
                                                <option value="Cibinong">Cibinong</option>
                                                <option value="Surabaya">Surabaya</option>
                                                <option value="Medan">Medan</option>
                                                <option value="Bali">Bali</option>
											</select>
                                        </div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Status Karyawan</label>
										<div class="col-sm-4">
											<select name="aktif" id="aktif" class="form-control" required>
												<option value="<?php echo $data['aktif'] ?>" selected><?php echo $data['aktif'] ?></option>
												<option value="Aktif">Aktif</option>
												<option value="Magang">Magang</option>
												<option value="Tidak Aktif">Tidak Aktif</option>
											</select>
										</div>	
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Status Pernikahan</label>
                                        <div class="col-sm-4">
											<select name="status_kawin" id="status_kawin" class="form-control"  required>
												<option value="<?php echo $data['status_kawin'] ?>" selected><?php echo $data['status_kawin'] ?></option>
												<option value="Sudah Menikah">Sudah Menikah</option>
												<option value="Belum Menikah">Belum Menikah</option>
											</select>
                                        </div>
									</div>
									<div class="form-group">
                                        <label class="control-label col-sm-4">Nama Pasangan Suami/Istri</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="nama_pasangan" id="reject" class="form-control" placeholder="Nama Suami/Istri" value="<?php echo $data['nama_pasangan'] ?>" disabled>
                                        </div>
                                    </div>
									<div class="form-group">
                                        <label class="control-label col-sm-4">Pekerjaan Suami/Istri</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="pekerjaan" id="reject1" class="form-control" placeholder="Pekerjaan Suami/Istri" value="<?php echo $data['pekerjaan'] ?>" disabled>
                                        </div>
                                    </div>
									<div class="form-group">
                                        <label class="control-label col-sm-4">Nomor Handphone</label>
                                        <div class="col-sm-4">
                                            <input type="number" name="nomor_tlp" min="0" id="reject2"  placeholder="Nomor Handphone" class="form-control" value="<?php echo $data['nomor_tlp'] ?>" disabled>
                                        </div>
                                    </div>
									<div class="form-group">
                                        <label class="control-label col-sm-4">Nama Anak</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="nama_anak" id="reject3" placeholder="Nama Anak Kandung" class="form-control" value="<?php echo $data['nama_anak'] ?>" disabled>
                                        </div>
                                    </div>
									<div class="form-group">
										<label class="control-label col-sm-4">Foto (Abaikan Jika Tidak Diubah)</label>
										<div class="col-sm-4">
											<input type="file" name="foto" class="form-control" accept="image/*">
										</div>
									</div>
									<div class="panel-footer">
										<button type="submit" name="perbarui" class="btn btn-success">Update</button>
									</div>
								</div><!-- /.panel -->
							</div>
						</div>
					</div>
				</div><!-- /.col-lg-12 -->
				</form>
            </div><!-- /.container-fluid -->
        </div><!-- /#page-wrapper -->
<!-- bottom of file -->
<?php
	include("layout_bottom.php");
?>