<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Form Pengajuan Training";
	$menuparent = "training";
	include("layout_top.php");
	$now = date('Y-m-d');
	$npp = $sess_mngid;
	
	// Ambil data karyawan dengan Join Bagian
	$sql_sess = "SELECT e.*, b.nama_bagian AS nama_dept FROM employee e 
                 LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian 
                 WHERE e.npp='". $chk_sess ."'";
	$ress_sess = mysqli_query($conn, $sql_sess);
	$data = mysqli_fetch_array($ress_sess);
?>

<script type="text/javascript">
function hitungTotal() {
    var total = 0;
    for(var i = 1; i <= 4; i++) {
        var nilai = parseFloat(document.getElementById('budget_' + i).value) || 0;
        total += nilai;
    }
    document.getElementById('budget_total').value = total.toFixed(0);
}
</script>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Form Pengajuan Training</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <form class="form-horizontal" name="training" action="form_pengajuan_training_insert.php" method="POST">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3>Form Pengajuan Training</h3></div>
                        <div class="panel-body">
                            <!-- Data Karyawan -->
                            <div class="form-group">
                                <label class="control-label col-sm-3">NPP</label>
                                <div class="col-sm-4">
                                    <input type="text" name="npp" class="form-control" value="<?php echo $data['npp'] ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Nama Karyawan</label>
                                <div class="col-sm-6">
                                    <input type="text" name="nama_karyawan" class="form-control" value="<?php echo $data['nama_emp'] ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Bagian</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" value="<?php echo $data['nama_dept'] ?>" readonly>
                                    <input type="hidden" name="id_bagian" value="<?php echo $data['nama_bagian'] ?>">
                                </div>
                            </div>
                            
                            <hr>
                            
                            <!-- Data Training -->
                            <div class="form-group">
                                <label class="control-label col-sm-3">Judul Training <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input type="text" name="judul_training" class="form-control" placeholder="Contoh: Training Leadership & Management" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tujuan Training <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <textarea name="tujuan_training" class="form-control" rows="3" placeholder="Jelaskan tujuan mengikuti training ini" required></textarea>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Penyelenggara</label>
                                <div class="col-sm-6">
                                    <input type="text" name="penyelenggara" class="form-control" placeholder="Nama lembaga/instansi penyelenggara">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tanggal Mulai <span class="text-danger">*</span></label>
                                <div class="col-sm-3">
                                    <input type="date" name="tanggal_mulai" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tanggal Selesai <span class="text-danger">*</span></label>
                                <div class="col-sm-3">
                                    <input type="date" name="tanggal_selesai" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Lokasi Training <span class="text-danger">*</span></label>
                                <div class="col-sm-6">
                                    <input type="text" name="lokasi_training" class="form-control" placeholder="Contoh: Jakarta / Online" required>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <!-- Budget Estimation -->
                            <h4 class="col-sm-12"><strong>Estimasi Budget</strong></h4>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Biaya Pendaftaran</label>
                                <div class="col-sm-4">
                                    <input type="number" name="budget_1" id="budget_1" class="form-control" value="0" onkeyup="hitungTotal()">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Biaya Transportasi</label>
                                <div class="col-sm-4">
                                    <input type="number" name="budget_2" id="budget_2" class="form-control" value="0" onkeyup="hitungTotal()">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Biaya Akomodasi</label>
                                <div class="col-sm-4">
                                    <input type="number" name="budget_3" id="budget_3" class="form-control" value="0" onkeyup="hitungTotal()">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Biaya Lain-lain</label>
                                <div class="col-sm-4">
                                    <input type="number" name="budget_4" id="budget_4" class="form-control" value="0" onkeyup="hitungTotal()">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3"><strong>Total Budget</strong></label>
                                <div class="col-sm-4">
                                    <input type="text" name="budget_total" id="budget_total" class="form-control" value="0" readonly style="font-weight: bold;">
                                </div>
                            </div>
                            
                        </div><!-- /.panel-body -->
                        
                        <div class="panel-footer">
                            <div class="form-group">
                                <div class="col-sm-offset-3 col-sm-9">
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan Pengajuan</button>
                                    <a href="index.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.panel -->
                </form>
            </div><!-- /.col-lg-12 -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div><!-- /#page-wrapper -->

<?php include("layout_bottom.php"); ?>
