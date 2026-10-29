<?php
	include("sess_check.php");
	
	if(isset($_GET['npp'])) {
		$sql = "SELECT A.*, A.npp AS npp_auth, B.*, C.*, D.* FROM employee AS A 
        LEFT JOIN koordinator AS B ON A.nama_koordinator = B.id_koordinator 
        LEFT JOIN bagian AS C ON A.nama_bagian = C.id_bagian
        LEFT JOIN manager AS D ON A.nama_manager = D.id_manager
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
				
				<form class="form-horizontal" action="data_karyawan_update.php" method="POST" enctype="multipart/form-data">
				<div class="row">
					<div class="col-lg-12">
						<div class="panel panel-default">
							<div class="panel-heading"><h3>Edit Data</h3></div>
								<div class="panel-body">
									<div class="form-group">
										<label class="control-label col-sm-4">Id Karyawan</label>
										<div class="col-sm-4">
											<input type="text" name="npplama" class="form-control" placeholder="NPP" value="<?php echo $data['npp_auth'] ?>" readonly>
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
											<input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" value="<?php echo $data['nama_emp'] ?>">
										</div>
									</div>
									<div class="form-group">
        									<label class="control-label col-sm-4">Jabatan</label>
                                            <div class="col-sm-4">
        										<select name="jabatan" id="jabatan" class="form-control" >
        											<option value="<?php echo $data['jabatan'] ?>" selected><?php echo $data['jabatan'] ?></option>
        											<option value="Staff">Staff</option>
        											<option value="Officer">Officer</option>
        											<option value="Leader">Leader</option>
        											<option value="Asisten Manager">Asisten Manager</option>
        											<option value="Manager">Manager</option>
        										</select>
                                            </div>
        							</div>
        							<div class="form-group">
        									<label class="control-label col-sm-4">Status PTKP</label>
                                            <div class="col-sm-4">
        										<select name="status_ptkp" id="status_ptkp" class="form-control" >
        											<option value="<?php echo $data['status_ptkp'] ?>" selected><?php echo $data['status_ptkp'] ?></option>
                                                     <option value="TK/0">TK/0</option>
                                                     <option value="TK/1">TK/1</option>
                                                     <option value="TK/2">TK/2</option>
                                                     <option value="TK/3">TK/3</option>
                                                     <option value="K/0">K/0</option>
                                                     <option value="K/1">K/1</option>
                                                     <option value="K/2">K/2</option>
                                                     <option value="K/3">K/3</option>
                                                     <option value="K/I/0">K/I/0</option>
        										</select>
                                            </div>
        							</div>
        							<div class="form-group">
										<label class="control-label col-sm-4">Nomor NPWP</label>
										<div class="col-sm-4">
										<input type="text" name="nomor_npwp" class="form-control" value="<?php echo $data['nomor_npwp'] ?>">
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Tanggal Masuk Karyawan</label>
										<div class="col-sm-4">
										<input type="date" name="tanggal_masuk_karyawan" class="form-control" value="<?php echo $data['tanggal_masuk_karyawan'] ?>">
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Tanggal Lahir</label>
										<div class="col-sm-4">
											<input type="date" name="tanggal_lahir" class="form-control" value="<?php echo $data['tanggal_lahir'] ?>">
										</div>
									</div>
                                    <div class="form-group">
                                        <label class="control-label col-sm-4">Nomor Kartu Keluarga</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="nomor_kk" class="form-control" 
                                                   value="<?php echo htmlspecialchars($data['nomor_kk']); ?>" 
                                                   pattern="\d{16}" title="Masukkan 16 digit angka" inputmode="numeric">
                                        </div>
                                    </div>
									<div class="form-group">
										<label class="control-label col-sm-4">Nomor KTP</label>
										<div class="col-sm-4">
											<input type="text" name="nomor_ktp"  class="form-control" value="<?php echo $data['nomor_ktp'] ?>">
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Alamat KTP</label>
										<div class="col-sm-4">
											<textarea name="alamat" class="form-control" placeholder="Alamat Sesuai KTP" rows="3"><?php echo $data['alamat']?></textarea>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Alamat Tinggal Sekarang</label>
										<div class="col-sm-4">
											<textarea name="alamat_tinggal_sekarang" class="form-control" placeholder="Alamat Tinggal Sekarang" rows="3"><?php echo $data['alamat_tinggal_sekarang']?></textarea>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Kota Lahir</label>
										<div class="col-sm-4">
											 <input type="text" name="kota_lahir" class="form-control" value="<?php echo $data['kota_lahir'] ?>">
										</div>
									</div>
        							<div class="form-group">
                                        <label class="control-label col-sm-4">Agama</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="agama" class="form-control" value="<?php echo $data['agama'] ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-sm-4">Golongan Darah</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="gol_darah" class="form-control" value="<?php echo $data['gol_darah'] ?>">
                                        </div>
                                    </div>
									<div class="form-group">
										<label class="control-label col-sm-4">Pendidikan Terakhir</label>
                                        <div class="col-sm-4">
											<select name="pendidikan_terakhir" id="pendidikan_terakhir" class="form-control" >
												<option value="<?php echo $data['pendidikan_terakhir'] ?>" selected><?php echo $data['pendidikan_terakhir'] ?></option>
												<option value="SMP">SMP</option>
												<option value="SMK">SMK</option>
												<option value="SMA">SMA</option>
												<option value="S1">S1</option>
												<option value="S1">S2</option>
												<option value="D3">D3</option>
												<option value="D4">D4</option>
											</select>
                                        </div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Nama Institusi Pendidikan Terakhir</label>
										<div class="col-sm-4">
											<input type="text" name="nama_institusi" class="form-control" placeholder="Nama Institusi Pendidikan Terakhir" value="<?php echo $data['nama_institusi'] ?>">
										</div>
									</div>
									<div class="form-group">
                                        <label class="control-label col-sm-4">Jurusan</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="jurusan" class="form-control" value="<?php echo $data['jurusan'] ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-sm-4">Nama Bank</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="nama_bank" class="form-control" value="<?php echo $data['nama_bank'] ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-sm-4">Nomor Rekening (Aktif)</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="norek_mandiri" class="form-control" 
                                                   value="<?php echo htmlspecialchars($data['norek_mandiri']); ?>" 
                                                   pattern="\d+" title="Masukkan hanya angka" inputmode="numeric">
                                        </div>
                                    </div>
									<div class="form-group">
										<label class="control-label col-sm-4">Jenis Kelamin</label>
										<div class="col-sm-4">
											<select name="jk" id="jk" class="form-control">
												<option value="<?php echo $data['jk_emp'] ?>" selected><?php echo $data['jk_emp'] ?></option>
												<option value="Laki-Laki">Laki-Laki</option>
												<option value="Perempuan">Perempuan</option>
											</select>
										</div>	
									</div>
									<div class="form-group">
                                        <label class="control-label col-sm-4">Nomor Handphone</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="telp" min="0" class="form-control" placeholder="Nomor Handphone" value="<?php echo $data['telp_emp'] ?>">
                                        </div>
                                    </div>
									<div class="form-group">
										<label class="control-label col-sm-4">Nama Koordinator</label>
										<div class="col-sm-4">
											<select id="nama_koordinator" name="nama_koordinator" class="form-control">
												<option value="<?php echo htmlspecialchars($data['id_koordinator']); ?>" selected>
													<?php echo htmlspecialchars($data['nama_koordinator']); ?>
												</option>
												<?php 
													$koordinators = mysqli_query($conn, "SELECT * FROM koordinator");
													while ($k = mysqli_fetch_array($koordinators)) {
														echo '<option value="' . htmlspecialchars($k['id_koordinator']) . '">' . htmlspecialchars($k['nama_koordinator']) . '</option>';
													}
												?>
											</select>
										</div>
									</div>

									<div class="form-group">
										<label class="control-label col-sm-4">Nama Manager</label>
										<div class="col-sm-4">
											<select id="nama_manager" name="nama_manager" class="form-control">
												<option value="<?php echo htmlspecialchars($data['id_manager']); ?>" selected>
													<?php echo htmlspecialchars($data['nama_manager']); ?>
												</option>
												<?php 
													$managers = mysqli_query($conn, "SELECT * FROM manager");
													while ($m = mysqli_fetch_array($managers)) {
														echo '<option value="' . htmlspecialchars($m['id_manager']) . '">' . htmlspecialchars($m['nama_manager']) . '</option>';
													}
												?>
											</select>
										</div>
									</div>

									<div class="form-group">
										<label class="control-label col-sm-4">Bagian</label>
										<div class="col-sm-4">
											<select id="nama_bagian" name="nama_bagian" class="form-control">
												<option value="<?php echo htmlspecialchars($data['id_bagian']); ?>" selected>
													<?php echo htmlspecialchars($data['nama_bagian']); ?>
												</option>
												<?php 
													$bagians = mysqli_query($conn, "SELECT * FROM bagian");
													while ($b = mysqli_fetch_array($bagians)) {
														echo '<option value="' . htmlspecialchars($b['id_bagian']) . '">' . htmlspecialchars($b['nama_bagian']) . '</option>';
													}
												?>
											</select>
										</div>
									</div>
									<div class="form-group">
                                        <label class="control-label col-sm-4">Jumlah Cuti</label>
                                        <div class="col-sm-4">
                                            <input type="number" min="0" name="jml" class="form-control" placeholder="Jumlah Cuti" value="<?php echo $data['jml_cuti'] ?>">
                                        </div>
                                    </div>
									<div class="form-group">
										<label class="control-label col-sm-4">Cabang</label>
                                        <div class="col-sm-4">
											<select name="cabang" id="cabang" class="form-control">
                                                <option value="<?php echo $data['cabang'] ?>" selected><?php echo $data['cabang'] ?></option>
												<option value="Puri">Puri</option>
                                                <option value="Bekasi">Bekasi</option>
                                                <option value="Cibinong">Cibinong</option>
                                                <option value="Surabaya">Surabaya</option>
                                                <option value="Medan">Medan</option>
                                                <option value="Bali">Bali</option>
                                                <option value="Banjarmasin">Banjarmasin</option>
											</select>
                                        </div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Status BPJS Kesehatan</label>
                                        <div class="col-sm-4">
											<select name="bpjs_kesehatan" id="bpjs_kesehatan" class="form-control">
                                                <option value="<?php echo $data['bpjs_kesehatan'] ?>" selected><?php echo $data['bpjs_kesehatan'] ?></option>
                                                <option value="Mandiri">Mandiri</option>
                                                <option value="PBI">PBI</option>
                                                <option value="Edabu">Edabu</option>
                                                <option value="Dengan Pasangan">Dengan Pasangan</option>
                                                <option value="Tidak Ada">Tidak Ada</option>
											</select>
                                        </div>
									</div>
        							<div class="form-group">
                                        <label class="control-label col-sm-4">Nomor BPJS Keternagakerjaan</label>
                                        <div class="col-sm-4">
                                            <input type="number" name="nomor_bpjs_ktr" placeholder="Kosongkan Jika Tidak ada" class="form-control" value="<?php echo $data['nomor_bpjs_ktr']?>">
                                        </div>
                                    </div>
									<div class="form-group">
										<label class="control-label col-sm-4">Status Karyawan</label>
										<div class="col-sm-4">
											<select name="aktif" id="aktif" class="form-control">
												<option value="<?php echo $data['aktif'] ?>" selected><?php echo $data['aktif'] ?></option>
												<option value="Aktif">Aktif</option>
												<option value="Tidak Aktif">Tidak Aktif</option>
											</select>
										</div>	
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Status Pernikahan</label>
                                        <div class="col-sm-4">
											<select name="status_kawin" id="status_kawin" class="form-control" >
												<option value="<?php echo $data['status_kawin'] ?>" selected><?php echo $data['status_kawin'] ?></option>
												<option value="Sudah Menikah">Sudah Menikah</option>
												<option value="Belum Menikah">Belum Menikah</option>
											</select>
                                        </div>
									</div>
									<div class="form-group">
                                        <label class="control-label col-sm-4">Nama Pasangan Suami/Istri</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="nama_pasangan" id="reject" class="form-control" placeholder="Nama Suami/Istri" value="<?php echo $data['nama_pasangan'] ?>" >
                                        </div>
                                    </div>
									<div class="form-group">
                                        <label class="control-label col-sm-4">Pekerjaan Suami/Istri</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="pekerjaan" id="reject1" class="form-control" placeholder="Pekerjaan Suami/Istri" value="<?php echo $data['pekerjaan'] ?>" >
                                        </div>
                                    </div>
									<div class="form-group">
                                        <label class="control-label col-sm-4">Nomor Handphone</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="nomor_tlp"  id="reject2"  placeholder="Nomor Handphone" class="form-control" value="<?php echo $data['nomor_tlp'] ?>" >
                                        </div>
                                    </div>
									<div class="form-group">
                                        <label class="control-label col-sm-4">Jumlah Anak</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="nama_anak" id="reject3" placeholder="Nama Anak Kandung" class="form-control" value="<?php echo $data['nama_anak'] ?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                    <label class="control-label col-sm-4">Nomor Emergensi 1</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="nomor_emrg_pr"  value="<?php echo $data['nomor_emrg_pr'] ?>" class="form-control">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-sm-4">Nomor Emergensi 2</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="nomor_emrg_kd"  value="<?php echo $data['nomor_emrg_kd'] ?>" class="form-control">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-sm-4">Status Karyawan</label>
                                        <div class="col-sm-4">
                                            <select name="status_karyawan" class="form-control">
												<option value="<?php echo $data['status_karyawan'] ?>" selected><?php echo $data['status_karyawan'] ?></option>
                                                <option value="Tetap">Tetap</option>
                                                <option value="Kontrak">Kontrak</option>
                                                <option value="Magang">Magang</option>
                                                <option value="Harian">Harian</option>
                                            </select>
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
										<a class="btn btn-warning" href="data_karyawan.php">Batal</a>
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
