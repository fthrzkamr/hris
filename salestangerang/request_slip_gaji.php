<?php
include("sess_check.php");

// Deskripsi halaman
$pagedesc = "Request Slip Gaji";
$menuparent = "gaji";
include("layout_top.php");

// Ambil NPP user yang login
$npp = $_SESSION['salestangerang'];

// Ambil data karyawan yang login
$sql_emp = "SELECT npp, nama_emp FROM employee WHERE npp = ?";
$stmt_emp = mysqli_prepare($conn, $sql_emp);
mysqli_stmt_bind_param($stmt_emp, "s", $npp);
mysqli_stmt_execute($stmt_emp);
$result_emp = mysqli_stmt_get_result($stmt_emp);
$data_emp = mysqli_fetch_assoc($result_emp);

// Bulan dalam bahasa Indonesia
$bulan_list = [
    "01" => "Januari",
    "02" => "Februari",
    "03" => "Maret",
    "04" => "April",
    "05" => "Mei",
    "06" => "Juni",
    "07" => "Juli",
    "08" => "Agustus",
    "09" => "September",
    "10" => "Oktober",
    "11" => "November",
    "12" => "Desember"
];
?>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">

        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Request Slip Gaji</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <!-- Form Request -->
        <div class="row">
            <div class="col-lg-8">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <i class="fa fa-file-text fa-fw"></i> Form Request Slip Gaji
                    </div>
                    <div class="panel-body">
                        <form method="post" action="request_slip_gaji_insert.php" name="postform" onsubmit="return validateForm()">
                            
                            <div class="form-group">
                                <label>NPP</label>
                                <input type="text" class="form-control" name="npp" value="<?php echo $data_emp['npp']; ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label>Nama Karyawan</label>
                                <input type="text" class="form-control" name="nama_karyawan" value="<?php echo $data_emp['nama_emp']; ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label>Tahun <span class="text-danger">*</span></label>
                                <select class="form-control" name="tahun" id="tahun" required>
                                    <option value="">-- Pilih Tahun --</option>
                                    <?php 
                                    for ($i = 2023; $i <= date("Y"); $i++) {
                                        echo "<option value='$i'>$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Bulan <span class="text-danger">*</span></label>
                                <select class="form-control" name="bulan" id="bulan" required>
                                    <option value="">-- Pilih Bulan --</option>
                                    <?php 
                                    foreach ($bulan_list as $key => $value) {
                                        echo "<option value='$key'>$value</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Keterangan</label>
                                <textarea class="form-control" name="keterangan" rows="3" placeholder="Keterangan request (opsional)"></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save fa-fw"></i> Kirim Request
                                </button>
                                <a href="request_slip_gaji_list.php" class="btn btn-default">
                                    <i class="fa fa-list fa-fw"></i> Lihat Daftar Request
                                </a>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <!-- Info Panel -->
            <div class="col-lg-4">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <i class="fa fa-info-circle fa-fw"></i> Informasi
                    </div>
                    <div class="panel-body">
                        <p><strong>Ketentuan Request Slip Gaji:</strong></p>
                        <ul>
                            <li>Anda hanya dapat melakukan request untuk slip gaji Anda sendiri</li>
                            <li>Pilih periode (bulan & tahun) yang ingin direquest</li>
                            <li>Request akan diproses oleh HR/Admin</li>
                            <li>Setelah disetujui, Anda dapat mengunduh slip gaji</li>
                            <li>HR akan memverifikasi ketersediaan data gaji</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.container-fluid -->
</div><!-- /#page-wrapper -->

<script>
function validateForm() {
    var tahun = document.getElementById('tahun').value;
    var bulan = document.getElementById('bulan').value;
    
    if (!tahun || !bulan) {
        alert('Mohon pilih tahun dan bulan terlebih dahulu!');
        return false;
    }
    
    return confirm('Apakah Anda yakin ingin mengirim request slip gaji untuk periode yang dipilih?');
}
</script>

<?php include("layout_bottom.php"); ?>
