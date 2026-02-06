<?php
include("sess_check.php");

if(isset($_GET['npp'])) {
    $sql = "SELECT * FROM employee WHERE npp='". $_GET['npp'] ."'";
    $ress = mysqli_query($conn, $sql);
    $data = mysqli_fetch_array($ress);
}

$pagedesc = "Data Karyawan";
$menuparent = "master";
include("layout_top.php");
?>

<script type="text/javascript">
function formatRupiah(input) {
    let value = input.value.replace(/\D/g, ""); // Hanya angka
    let formatted = new Intl.NumberFormat('id-ID').format(value); // Format ke rupiah
    input.value = formatted;
}

// Fungsi untuk menghapus titik sebelum submit
function removeDotsBeforeSubmit() {
    document.querySelectorAll("input[name='gaji_pokok'], input[name='kacamata']").forEach(input => {
        input.value = input.value.replace(/\./g, ""); // Hapus titik
    });
}
</script>

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Edit Data Karyawan</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <form class="form-horizontal" action="tunjangan_update.php" method="POST" enctype="multipart/form-data" onsubmit="removeDotsBeforeSubmit()">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3>Edit Data</h3></div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="control-label col-sm-3">NPP</label>
                                <div class="col-sm-4">
                                    <input type="text" name="npplama" class="form-control" value="<?php echo $data['npp'] ?>" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Nama Karyawan</label>
                                <div class="col-sm-4">
                                    <input type="text" name="nama" class="form-control" value="<?php echo $data['nama_emp'] ?>" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Gaji Pokok</label>
                                <div class="col-sm-4">
                                    <input type="text" name="gaji_pokok" class="form-control" 
                                    value="<?php echo isset($data['gaji_pokok']) ? number_format($data['gaji_pokok'], 0, ',', '.') : '0'; ?>" 
                                    required onkeyup="formatRupiah(this)">                                
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tunjangan Tetap - Jabatan</label>
                                <div class="col-sm-4">
                                    <input type="text" name="tunj_jabatan" class="form-control" 
                                    value="<?php echo isset($data['tunj_jabatan']) ? number_format($data['tunj_jabatan'], 0, ',', '.') : '0'; ?>" 
                                    required onkeyup="formatRupiah(this)">                                
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tunjangan Tidak Tetap - Transport</label>
                                <div class="col-sm-4">
                                    <input type="text" name="tunj_transport" class="form-control" 
                                    value="<?php echo isset($data['tunj_transport']) ? number_format($data['tunj_transport'], 0, ',', '.') : '0'; ?>" 
                                    required onkeyup="formatRupiah(this)">                                
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tunjangan Tidak Tetap - Kinerja</label>
                                <div class="col-sm-4">
                                    <input type="text" name="tunj_kinerja" class="form-control" 
                                    value="<?php echo isset($data['tunj_kinerja']) ? number_format($data['tunj_kinerja'], 0, ',', '.') : '0'; ?>" 
                                    required onkeyup="formatRupiah(this)">                                
                                </div>
                            </div>
                        </div>
                        <div class="panel-footer">
                            <button type="submit" name="perbarui" class="btn btn-success">Update</button>
                            <a class="btn btn-warning" href="tunjangan.php">Kembali</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include("layout_bottom.php"); ?>
