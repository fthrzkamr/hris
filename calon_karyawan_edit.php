<?php
include("sess_check.php");

if(isset($_GET['npp'])) {
    $sql = "SELECT * FROM employee WHERE npp='". $_GET['npp'] ."'";
    $ress = mysqli_query($conn, $sql);
    $data = mysqli_fetch_array($ress);
    
    if(!$data) {
        echo '<script>alert("Data tidak ditemukan"); window.location="calon_karyawan_list.php";</script>';
        exit;
    }
}

$pagedesc = "Edit Data Karyawan";
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
    document.querySelectorAll("input[type='text']").forEach(input => {
        if(input.name.includes('gaji') || input.name.includes('tunj') || input.name.includes('kesehatan') || input.name.includes('plafond') || input.name.includes('kacamata')) {
            input.value = input.value.replace(/\./g, ""); // Hapus titik
        }
    });
}

function hitungTotalGaji() {
    let gaji_pokok = parseInt(document.getElementsByName('gaji_pokok')[0].value.replace(/\./g, "") || 0);
    let tunj_jabatan = parseInt(document.getElementsByName('tunj_jabatan')[0].value.replace(/\./g, "") || 0);
    let tunj_transport = parseInt(document.getElementsByName('tunj_transport')[0].value.replace(/\./g, "") || 0);
    let tunj_kinerja = parseInt(document.getElementsByName('tunj_kinerja')[0].value.replace(/\./g, "") || 0);
    
    let total = gaji_pokok + tunj_jabatan + tunj_transport + tunj_kinerja;
    document.getElementsByName('total_gaji')[0].value = new Intl.NumberFormat('id-ID').format(total);
}
</script>

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Edit Data Karyawan - <?php echo $data['nama_emp'] ?></h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <form class="form-horizontal" action="calon_karyawan_update.php" method="POST" enctype="multipart/form-data" onsubmit="removeDotsBeforeSubmit()">
                    
                    <!-- Data Dasar -->
                    <div class="panel panel-primary">
                        <div class="panel-heading"><h3><i class="fa fa-user"></i> Data Dasar Karyawan</h3></div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="control-label col-sm-3">NPP</label>
                                <div class="col-sm-6">
                                    <input type="text" name="npp" class="form-control" value="<?php echo $data['npp'] ?>" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Nama Karyawan</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control" value="<?php echo $data['nama_emp'] ?>" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tanggal Masuk <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <input type="date" name="tanggal_masuk_karyawan" class="form-control" value="<?php echo $data['tanggal_masuk_karyawan'] ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Status Karyawan <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <select name="status_karyawan" class="form-control" required>
                                        <option value="">--- Pilih ---</option>
                                        <option value="Kontrak" <?php if($data['status_karyawan'] == "Kontrak") echo "selected"; ?>>Kontrak</option>
                                        <option value="Tetap" <?php if($data['status_karyawan'] == "Tetap") echo "selected"; ?>>Tetap</option>
                                        <option value="Magang" <?php if($data['status_karyawan'] == "Magang") echo "selected"; ?>>Magang</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Status Aktif <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <select name="aktif" class="form-control" required>
                                        <option value="Aktif" <?php if($data['aktif'] == "Aktif") echo "selected"; ?>>Aktif</option>
                                        <option value="Tidak" <?php if($data['aktif'] == "Tidak") echo "selected"; ?>>Tidak Aktif</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Jabatan</label>
                                <div class="col-sm-6">
                                    <input type="text" name="jabatan" class="form-control" value="<?php echo $data['jabatan'] ?>" placeholder="Contoh: Staff, Leader, Manager">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Jatah Cuti (Hari)</label>
                                <div class="col-sm-3">
                                    <input type="number" name="jml_cuti" class="form-control" value="<?php echo $data['jml_cuti'] ?>" min="0">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Status PTKP</label>
                                <div class="col-sm-3">
                                    <input type="text" name="status_ptkp" class="form-control" value="<?php echo $data['status_ptkp'] ?>" placeholder="TK/0, K/1, K/2, dll">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Data Gaji -->
                    <div class="panel panel-success">
                        <div class="panel-heading"><h3><i class="fa fa-money"></i> Data Gaji & Tunjangan</h3></div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="control-label col-sm-3">Gaji Pokok <span class="text-danger">*</span></label>
                                <div class="col-sm-5">
                                    <input type="text" name="gaji_pokok" class="form-control" value="<?php echo number_format($data['gaji_pokok'], 0, ',', '.') ?>" required onkeyup="formatRupiah(this); hitungTotalGaji();">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tunjangan Jabatan</label>
                                <div class="col-sm-5">
                                    <input type="text" name="tunj_jabatan" class="form-control" value="<?php echo number_format($data['tunj_jabatan'], 0, ',', '.') ?>" onkeyup="formatRupiah(this); hitungTotalGaji();">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tunjangan Transport</label>
                                <div class="col-sm-5">
                                    <input type="text" name="tunj_transport" class="form-control" value="<?php echo number_format($data['tunj_transport'], 0, ',', '.') ?>" onkeyup="formatRupiah(this); hitungTotalGaji();">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tunjangan Kinerja</label>
                                <div class="col-sm-5">
                                    <input type="text" name="tunj_kinerja" class="form-control" value="<?php echo number_format($data['tunj_kinerja'], 0, ',', '.') ?>" onkeyup="formatRupiah(this); hitungTotalGaji();">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3"><strong>Total Gaji</strong></label>
                                <div class="col-sm-5">
                                    <input type="text" name="total_gaji" class="form-control" value="<?php echo number_format($data['total_gaji'], 0, ',', '.') ?>" readonly style="background-color: #f5f5f5; font-weight: bold;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Benefit & Kesehatan -->
                    <div class="panel panel-info">
                        <div class="panel-heading"><h3><i class="fa fa-medkit"></i> Benefit & Kesehatan</h3></div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="control-label col-sm-3">Status Reimburse Kesehatan</label>
                                <div class="col-sm-4">
                                    <select name="status_rem" class="form-control">
                                        <option value="N/A" <?php if($data['status_rem'] == "N/A") echo "selected"; ?>>N/A</option>
                                        <option value="Aktif" <?php if($data['status_rem'] == "Aktif") echo "selected"; ?>>Aktif</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Plafond Kesehatan</label>
                                <div class="col-sm-5">
                                    <input type="text" name="plafond" class="form-control" value="<?php echo number_format($data['plafond'], 0, ',', '.') ?>" onkeyup="formatRupiah(this)">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Outstanding Kesehatan</label>
                                <div class="col-sm-5">
                                    <input type="text" name="kesehatan" class="form-control" value="<?php echo number_format($data['kesehatan'], 0, ',', '.') ?>" onkeyup="formatRupiah(this)">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Plafond Kacamata</label>
                                <div class="col-sm-5">
                                    <input type="text" name="plafond_kacamata" class="form-control" value="<?php echo number_format($data['plafond_kacamata'], 0, ',', '.') ?>" onkeyup="formatRupiah(this)">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Outstanding Kacamata</label>
                                <div class="col-sm-5">
                                    <input type="text" name="kacamata" class="form-control" value="<?php echo number_format($data['kacamata'], 0, ',', '.') ?>" onkeyup="formatRupiah(this)">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Data Atasan -->
                    <div class="panel panel-warning">
                        <div class="panel-heading"><h3><i class="fa fa-sitemap"></i> Data Atasan</h3></div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="control-label col-sm-3">Koordinator</label>
                                <div class="col-sm-6">
                                    <select name="nama_koordinator" class="form-control">
                                        <option value="">-- Pilih Koordinator --</option>
                                        <?php
                                        $query_koordinator = mysqli_query($conn, "SELECT nama_koordinator FROM koordinator ORDER BY nama_koordinator ASC");
                                        while($row_koordinator = mysqli_fetch_assoc($query_koordinator)) {
                                            $selected = ($data['nama_koordinator'] == $row_koordinator['nama_koordinator']) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($row_koordinator['nama_koordinator']) . '" ' . $selected . '>' . htmlspecialchars($row_koordinator['nama_koordinator']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Manager</label>
                                <div class="col-sm-6">
                                    <select name="nama_manager" class="form-control">
                                        <option value="">-- Pilih Manager --</option>
                                        <?php
                                        $query_manager = mysqli_query($conn, "SELECT nama_manager FROM manager ORDER BY nama_manager ASC");
                                        while($row_manager = mysqli_fetch_assoc($query_manager)) {
                                            $selected = ($data['nama_manager'] == $row_manager['nama_manager']) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($row_manager['nama_manager']) . '" ' . $selected . '>' . htmlspecialchars($row_manager['nama_manager']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="panel panel-default">
                        <div class="panel-footer text-center">
                            <a href="calon_karyawan_list.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
                            <button type="submit" name="update" class="btn btn-primary btn-lg"><i class="fa fa-save"></i> Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include("layout_bottom.php"); ?>
