<?php
	include("sess_check.php");
	
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
						<form class="form-horizontal" action="karyawan_insert.php" method="POST" enctype="multipart/form-data">
							<div class="panel panel-default">
								<div class="panel-heading"><h3>Tambah Data Karyawan</h3></div>
								<div class="panel-body">
									<div class="form-group">
										<label class="control-label col-sm-3">Nomer Induk Karyawan</label>
										<div class="col-sm-4">
											<input type="text" name="npp" onBlur="checkNppAvailability()" class="form-control" placeholder="Nomer Induk Karyawan" required>
											<span id="user-availability-status" style="font-size:12px;"></span>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Nama Lengkap</label>
										<div class="col-sm-4">
											<input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Nomor KTP </label>
										<div class="col-sm-4">
											<input type="text" name="nomor_ktp" class="form-control" placeholder="Nomor KTP" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Tanggal Lahir</label>
										<div class="col-sm-4">
											<input type="date" name="tanggal_lahir" class="form-control" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Alamat KTP</label>
										<div class="col-sm-4">
											<textarea name="alamat" class="form-control" placeholder="Alamat Sesuai KTP" rows="3" required></textarea>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Alamat Tinggal Sekarang</label>
										<div class="col-sm-4">
											<textarea name="alamat_tinggal_sekarang" class="form-control" placeholder="Alamat Domisili" rows="3" required></textarea>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Kota Lahir</label>
										<div class="col-sm-4">
											<textarea name="kota_lahir" class="form-control" placeholder="Kota / Kabupaten Tempat Lahir" rows="3" required></textarea>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Pendidikan Terakhir </label>
										<div class="col-sm-4">
											<input type="text" name="pendidikan_terakhir" class="form-control" placeholder="Pendidikan Terakhir" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Nama Institusi Sekolah </label>
										<div class="col-sm-4">
											<input type="text" name="nama_institusi" class="form-control" placeholder="Nama sekolah" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Tanggal Masuk Karyawan</label>
										<div class="col-sm-4">
											<input type="date" name="tanggal_masuk_karyawan" class="form-control" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Status BPJS </label>
										<div class="col-sm-4">
											<input type="text" name="status_bpjs" class="form-control" placeholder="Pendidikan Terakhir" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Asuransi Lain </label>
										<div class="col-sm-4">
											<input type="text" name="asuransi_lain" class="form-control" placeholder="Pendidikan Terakhir" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Nomor rekening Mandiri (Aktif) </label>
										<div class="col-sm-4">
											<input type="number" name="norek_mandiri" class="form-control" placeholder="Pendidikan Terakhir" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Jenis Kelamin</label>
										<div class="col-sm-3">
											<select name="jk" id="jk" class="form-control" required>
												<option value="" selected>--- Pilih Jenis Kelamin ---</option>
												<option value="Laki-Laki">Laki-Laki</option>
												<option value="Perempuan">Perempuan</option>
											</select>
										</div>
										
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Telepon</label>
										<div class="col-sm-4">
											<input type="number" name="telp" min="0" class="form-control" placeholder="Telepon" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Divisi</label>
										<div class="col-sm-4">
											<input type="text" name="divisi" class="form-control" placeholder="Divisi" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Jabatan</label>
										<div class="col-sm-4">
											<input type="text" name="jabatan" class="form-control" placeholder="Jabatan" required>
										</div>
									</div>
									
									<div class="form-group">
										<label class="control-label col-sm-3">Jumlah Cuti</label>
										<div class="col-sm-3">
											<input type="number" name="jml" min="0" class="form-control" placeholder="Jumlah Cuti" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Hak Akses</label>
										<div class="col-sm-3">
											<select name="akses" id="akses" class="form-control" required>
												<option value="" selected>--- Pilih Hak Akses ---</option>
												<!-- <option value="Leader">Leader</option> -->
												<option value="Manager">Karyawan</option>
												<!-- <option value="Pegawai">Pegawai</option>
												<option value="Supervisor">Supervisor</option> -->
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Foto</label>
										<div class="col-sm-3">
											<input type="file" name="foto" class="form-control" accept="image/*" required>
										</div>
									</div>

									<div class="form-group">
										<label class="control-label col-sm-3">Telepon Emergency</label>
										<div class="col-sm-3">
											<input type="number" name="no_tlp_emergency" class="form-control" placeholder="Nomor Emergency" required>
										</div>
									</div>

									<div class="form-group">
										<label class="control-label col-sm-3">Status Alamat</label>
										<div class="col-sm-3">
											<select name="status_alamat" id="status_alamat" class="form-control" required>
												<option value="" selected>--- Pilih Status Alamat ---</option>
												<!-- <option value="Leader">Leader</option> -->
												<option value="rumah orang tua">Rumah Orang Tua</option>
												<option value="milik sendiri">Milik Sendiri</option>
												<option value="kontrak">Kontrak</option>
												<option value="kost">Kost</option>
												<option value="sewa">Sewa</option>
												<!-- <option value="Pegawai">Pegawai</option>
												<option value="Supervisor">Supervisor</option> -->
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Cabang</label>
										<div class="col-sm-4">
											<input type="text" name="cabang" class="form-control" placeholder="Jabatan" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Nama Panggilan</label>
										<div class="col-sm-4">
											<input type="text" name="nama_panggilan" class="form-control" placeholder="Jabatan" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Nama Pasangan Kawin</label>
										<div class="col-sm-4">
											<input type="text" name="nama_pasangan_kawin" class="form-control" placeholder="Jabatan" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Tgl Lahir Pasangan</label>
										<div class="col-sm-4">
											<input type="text" name="tgl_lahir_pasangan" class="form-control" placeholder="Jabatan" required>
										</div>
									</div>

									<div class="form-group">
										<label class="control-label col-sm-3">Nama Kontak Emergensi</label>
										<div class="col-sm-4">
											<input type="text" name="kontak_emergensi" class="form-control" placeholder="Jabatan" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Hubungan</label>
										<div class="col-sm-4">
											<input type="text" name="hubungan" class="form-control" placeholder="Jabatan" required>
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