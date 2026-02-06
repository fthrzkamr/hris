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
                <form class="form-horizontal" action="karyawan_insertt.php" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3>Tambah Data Karyawan</h3></div>
                        <div class="panel-body">                            
                            <div class="form-group">
                                <label class="control-label col-sm-4">Nama Lengkap</label>
                                <div class="col-sm-4">
                                    <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Tanggal Masuk Karyawan</label>
                                <div class="col-sm-4">
                                    <input type="date" name="tanggal_masuk_karyawan" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Tanggal Lahir</label>
                                <div class="col-sm-4">
                                    <input type="date" name="tanggal_lahir" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Nomer Induk Karyawan</label>
                                <div class="col-sm-4">
                                    <input type="number" name="npp" onBlur="checkNppAvailability()" class="form-control" value ="<?php echo date('y') ?>" placeholder="Nomer Induk Karyawan" required>
                                    <span id="user-availability-status" style="font-size:12px;"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Nomor KTP </label>
                                <div class="col-sm-4">
                                    <input type="text" name="nomor_ktp" class="form-control" placeholder="Nomor KTP" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Alamat KTP</label>
                                <div class="col-sm-4">
                                    <textarea name="alamat" class="form-control" placeholder="Alamat Sesuai KTP" rows="3" required></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Alamat Tinggal Sekarang</label>
                                <div class="col-sm-4">
                                    <textarea name="alamat_tinggal_sekarang" class="form-control" placeholder="Alamat Domisili" rows="3" required></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Kota Lahir</label>
                                <div class="col-sm-4">
                                    <input type="text" name="kota_lahir" class="form-control" placeholder="Kota Lahir" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Pendidikan Terakhir</label>
                                <div class="col-sm-4">
                                    <select name="nama_institusi" id="nama_institusi" class="form-control" required>
                                        <option value="" selected>---Pendidikan Terakhir---</option>
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
                                    <input type="text" name="pendidikan_terakhir" class="form-control" placeholder="Nama Institusi Pendidikan Terakhir" required>
                                </div>
                            </div>
                            
                         
                            <div class="form-group">
                                <label class="control-label col-sm-4">Nomor Rekening Bank Mandiri</label>
                                <div class="col-sm-4">
                                    <input type="number" name="norek_mandiri" class="form-control" placeholder="Nomor Rekening Bank Mandiri (Aktif)" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Jenis Kelamin</label>
                                <div class="col-sm-4">
                                    <select name="jk" id="jk" class="form-control" required>
                                        <option value="" selected>--- Pilih Jenis Kelamin ---</option>
                                        <option value="Laki-Laki">Laki-Laki</option>
                                        <option value="Perempuan">Perempuan</option>
                                    </select>
                                </div>	
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Nomor Handphone</label>
                                <div class="col-sm-4">
                                    <input type="number" name="telp" min="0" class="form-control" placeholder="Nomor Handphone" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Nama Koordinator</label>
                                <div class="col-sm-4">
                                    <select id="nama_koordinator" name="nama_koordinator" class="form-control" >
                                        <option value="" selected>--- Pilih Koordinator ---</option>
                                            <?php 
                                                $data = mysqli_query($conn,"select * from koordinator");
                                                while($d = mysqli_fetch_array($data)){
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
                                        <option value="" selected>--- Pilih Manager ---</option>
                                            <?php 
                                                $data = mysqli_query($conn,"select * from manager");
                                                while($d = mysqli_fetch_array($data)){
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
                                <label class="control-label col-sm-4">Jumlah Cuti</label>
                                <div class="col-sm-4">
                                    <input type="number" min="0" name="jml" class="form-control" placeholder="Jumlah Cuti" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Cabang</label>
                                <div class="col-sm-4">
                                    <select name="cabang" id="cabang" class="form-control" required>
                                        <option value="" selected>---Pilih Cabang---</option>
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
                                <label class="control-label col-sm-4">Status Pernikahan</label>
                                <div class="col-sm-4">
                                    <select name="status_kawin" id="status_kawin" class="form-control" required>
                                        <option value="" selected>---Status Pernikahan---</option>
                                        <option value="Sudah Menikah">Sudah Menikah</option>
                                        <option value="Belum Menikah">Belum Menikah</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Nama Pasangan Kawin</label>
                                <div class="col-sm-4">
                                    <input type="text" name="nama_pasangan" id="reject" class="form-control" placeholder="Nama Suami/Istri"  disabled>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Pekerjaan </label>
                                <div class="col-sm-4">
                                    <input type="text" name="pekerjaan" id="reject1" class="form-control" placeholder="Pekerjaan Suami/Istri"  disabled>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Nomor Handphone</label>
                                <div class="col-sm-4">
                                    <input type="number" name="nomor_tlp" min="0" id="reject2"  placeholder="Nomor Handphone" class="form-control"  disabled>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Nama Anak</label>
                                <div class="col-sm-4">
                                    <input type="text" name="nama_anak" id="reject3" placeholder="Nama Anak Kandung" class="form-control"  disabled>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Foto</label>
                                <div class="col-sm-4">
                                    <input type="file" name="foto" class="form-control" accept="image/*" required>
                                </div>
                            </div>	
                        </div>
                    </div> 
                </div>
                    <div class="panel-footer">
                        <button type="submit" name="simpan" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div><!-- /.container-fluid -->
        </div><!-- /#page-wrapper -->
<!-- bottom of file -->
<?php
	include("layout_bottom.php");
?>