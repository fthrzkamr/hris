<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Review Pengajuan Training";
	$menuparent = "training";
	include("layout_top.php");
	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");
	
	$Sql = "SELECT pt.*, e.nama_emp, e.npp, b.nama_bagian 
	        FROM pengajuan_training pt
	        LEFT JOIN employee e ON pt.npp = e.npp
	        LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
	        WHERE pt.id_pengajuan='$_GET[id]'";
	$Qry = mysqli_query($conn, $Sql);
	$data = mysqli_fetch_array($Qry);
	
	// Ambil rincian budget
	$SqlRincian = "SELECT * FROM training_rincian WHERE id_pengajuan='$_GET[id]'";
	$QryRincian = mysqli_query($conn, $SqlRincian);
?>

<script type="text/javascript">
$(document).ready(function() {
    $('#aksi').change(function(){
        if($(this).val() === 'Rejected'){
            $('#reject_reason_div').show();
            $('#reject_reason').attr('required', true);
        }else{
            $('#reject_reason_div').hide();
            $('#reject_reason').attr('required', false);
        }
    });
});
</script>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Review Pengajuan Training</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <form class="form-horizontal" name="training" action="training_update.php" method="POST">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3>Detail Pengajuan Training</h3></div>
                        <div class="panel-body">
                        
                            <div class="form-group">
                                <label class="control-label col-sm-3">ID Pengajuan</label>
                                <div class="col-sm-4">
                                    <input type="text" name="id_pengajuan" class="form-control" value="<?php echo $data['id_pengajuan'];?>" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-3">NPP</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control" value="<?php echo $data['npp'];?>" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-3">Nama Karyawan</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control" value="<?php echo $data['nama_emp'];?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Bagian</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" value="<?php echo $data['nama_bagian'];?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tanggal Pengajuan</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" value="<?php echo IndonesiaTgl(date('Y-m-d', strtotime($data['tanggal_pengajuan'])));?>" readonly>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Judul Training</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" value="<?php echo $data['judul_training'];?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tujuan Training</label>
                                <div class="col-sm-8">
                                    <textarea class="form-control" rows="3" readonly><?php echo $data['tujuan_training'];?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Penyelenggara</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control" value="<?php echo $data['penyelenggara'];?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tanggal Training</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control" value="<?php echo IndonesiaTgl($data['tanggal_mulai']) .' s/d '. IndonesiaTgl($data['tanggal_selesai']);?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Lokasi Training</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control" value="<?php echo $data['lokasi_training'];?>" readonly>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h4 class="col-sm-12"><strong>Rincian Budget</strong></h4>
                            
                            <div class="col-sm-12">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th width="5%">No</th>
                                            <th width="60%">Item</th>
                                            <th width="35%" class="text-right">Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        while($rincian = mysqli_fetch_array($QryRincian)){
                                            echo '<tr>';
                                            echo '<td class="text-center">'. $no .'</td>';
                                            echo '<td>'. $rincian['nama_item'] .'</td>';
                                            echo '<td class="text-right">'. format_rupiah($rincian['nilai']) .'</td>';
                                            echo '</tr>';
                                            $no++;
                                        }
                                        ?>
                                        <tr>
                                            <td colspan="2" class="text-right"><strong>TOTAL BUDGET</strong></td>
                                            <td class="text-right"><strong><?php echo format_rupiah($data['budget_total']);?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <hr>
                            
                            <!-- Approval Section -->
                            <h4 class="col-sm-12"><strong>Keputusan Approval</strong></h4>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">Status <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <select name="aksi" id="aksi" class="form-control" required>
                                        <option value="">-- Pilih Keputusan --</option>
                                        <option value="Approved">Disetujui</option>
                                        <option value="Rejected">Ditolak</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group" id="reject_reason_div" style="display:none;">
                                <label class="control-label col-sm-3">Alasan Penolakan <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <textarea name="reject_reason" id="reject_reason" class="form-control" rows="3" placeholder="Jelaskan alasan penolakan"></textarea>
                                </div>
                            </div>
                            
                        </div>
                        
                        <div class="panel-footer">
                            <div class="form-group">
                                <div class="col-sm-offset-3 col-sm-9">
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Submit Keputusan</button>
                                    <a href="training_wait.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include("layout_bottom.php"); ?>
