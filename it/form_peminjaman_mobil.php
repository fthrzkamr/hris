<?php
	include("sess_check.php");
	
	$pagedesc = "Form Peminjaman Mobil";
	$menuparent = "peminjaman_mobil";
	include("layout_top.php");
	$now = date('Y-m-d');
	$npp = $sess_mngid;
	
	// Ambil data karyawan dengan Join Bagian
	$sql_sess = "SELECT e.*, b.nama_bagian AS nama_dept FROM employee e 
                 LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian 
                 WHERE e.npp='". $npp ."'";
	$ress_sess = mysqli_query($conn, $sql_sess);
	$data = mysqli_fetch_array($ress_sess);
?>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Form Pengajuan Peminjaman Mobil</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <form class="form-horizontal" name="peminjaman" action="peminjaman_mobil_insert.php" method="POST">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3>Data Pengajuan</h3></div>
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
                                </div>
                            </div>
                            
                            <hr>
                            
                            <!-- Data Peminjaman -->
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tujuan Peminjaman <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <textarea name="tujuan_peminjaman" class="form-control" rows="2" placeholder="Contoh: Meeting dengan klien PT XYZ" required></textarea>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Lokasi Tujuan <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input type="text" name="tujuan_lokasi" class="form-control" placeholder="Contoh: Gedung Astra, Jakarta Pusat" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Waktu Mulai <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <input type="datetime-local" name="tanggal_mulai" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Waktu Selesai <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <input type="datetime-local" name="tanggal_selesai" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Pilih Kendaraan <span class="text-danger">*</span></label>
                                <div class="col-sm-6">
                                    <select name="id_kendaraan" class="form-control" required>
                                        <option value="">-- Pilih Mobil --</option>
                                        <?php
                                        $sql_k = "SELECT * FROM kendaraan WHERE status_kendaraan='Tersedia' ORDER BY nama_kendaraan ASC";
                                        $res_k = mysqli_query($conn, $sql_k);
                                        while($dk = mysqli_fetch_array($res_k)){
                                            echo '<option value="'.$dk['id_kendaraan'].'">'.$dk['nama_kendaraan'].' ('.$dk['plat_nomor'].')</option>';
                                        }
                                        ?>
                                    </select>
                                    <p class="help-block"><small>*Hanya menampilkan mobil yang sedang tersedia</small></p>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Keterangan Tambahan</label>
                                <div class="col-sm-8">
                                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Barang bawaan, butuh driver, dll"></textarea>
                                </div>
                            </div>
                            
                        </div><!-- /.panel-body -->
                        
                        <div class="panel-footer">
                            <div class="form-group">
                                <div class="col-sm-offset-3 col-sm-9">
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Kirim Pengajuan</button>
                                    <a href="peminjaman_mobil_list.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
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
