<?php
include("sess_check.php");

// Deskripsi halaman
$pagedesc = "Approval Kacamata";
$menuparent = "approval";
include("layout_top.php");

$now = date('Y-m-d');
$Sql = "SELECT kacamata.*, employee.* FROM kacamata, employee 
        WHERE kacamata.npp = employee.npp 
        AND kacamata.id_kacamata = '$_GET[no]'";
$Qry = mysqli_query($conn, $Sql);
$data = mysqli_fetch_array($Qry);

$kacamata = $data['total_kwintansi'];
$a		=(80 / 100);
$p		= $kacamata * $a ;

// Ambil data batas maksimal klaim dan total kwitansi
// $limit = $data['kacamata']; // Asumsikan kolom untuk batas klaim bernama 'batas_klaim'
// $kacamata = $data['total_kwintansi'];

// // Perhitungan Total Reimburse sesuai dengan kacamata_detail.php
// if ($kacamata <= $limit) {
//     $total_rembes = $kacamata;
// } else {
//     $total_rembes = $limit;
// }

// Fungsi untuk format Rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}
?>

<script type="text/javascript">
$(document).ready(function() {
    $('#aksi').change(function(){
        $('#reject').prop('disabled', $(this).val() !== '2');
    });
});
</script>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Data Approval Kacamata</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <form class="form-horizontal" name="rembes" action="kacamata_update.php" 
                      method="POST" enctype="multipart/form-data" onSubmit="return valid();">
                    
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3>Review Pengajuan Kacamata</h3>
                        </div>
                        <div class="panel-body">
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">No</label>
                                <div class="col-sm-4">
                                    <input type="text" name="no" class="form-control" 
                                           value="<?php echo $data['id_kacamata']; ?>" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-3">Nama Karyawan</label>
                                <div class="col-sm-4">
                                    <input type="text" name="nama_karyawan" class="form-control" 
                                           value="<?php echo $data['nama_emp']; ?>" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-3">Nama Tempat Fasilitas</label>
                                <div class="col-sm-4">
                                    <input type="text" name="nama_fasilitas" class="form-control" 
                                           value="<?php echo $data['nama_fasilitas']; ?>" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-3">Alamat Tempat</label>
                                <div class="col-sm-4">
                                    <input type="text" name="alamat_fasilitas" class="form-control" 
                                           value="<?php echo $data['alamat_fasilitas']; ?>" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-3">Tanggal Pengajuan</label>
                                <div class="col-sm-4">
                                    <input type="text" name="tanggal_pengajuan" class="form-control" 
                                           value="<?php echo IndonesiaTgl($data['tanggal_pengajuan']); ?>" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-3">Total Kwitansi</label>
                                <div class="col-sm-4">
                                    <input type="text" name="total_kwintansi" class="form-control" 
                                           value="<?php echo formatRupiah($data['total_kwintansi']); ?>" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-3">Total Reimburse</label>
                                <div class="col-sm-4">
                                    <input type="text" name="total_rembes" class="form-control" 
                                           value="<?php echo formatRupiah($p); ?>" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-3">Foto</label>
                                <div class="col-sm-4">
                                    <input type="text" name="foto" class="form-control" 
                                           value="<?php echo $data['foto']; ?>" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-3">Status</label>
                                <div class="col-sm-4">
                                    <select name="aksi" id="aksi" class="form-control" required>
                                        <option value="" selected>---- Pilih Aksi ----</option>
                                        <option value="1">Approved</option>
                                        <option value="2">Rejected</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-3">Keterangan Reject</label>
                                <div class="col-sm-4">
                                    <textarea name="reject" id="reject" class="form-control" 
                                              placeholder="Keterangan Reject" rows="3" disabled></textarea>
                                </div>
                            </div>

                        </div>
                        <div class="panel-footer">
                            <button type="submit" class="btn btn-success">Simpan</button>
                        </div>
                    </div><!-- /.panel -->
                </form>
            </div><!-- /.col-lg-12 -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div><!-- /#page-wrapper -->

<?php
include("layout_bottom.php");
?>
