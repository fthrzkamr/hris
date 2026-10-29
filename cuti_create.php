<?php
	include("sess_check.php");
	

	
	$sql = "SELECT * FROM employee WHERE npp='". $sess_mngid ."'";
	$ress = mysqli_query($conn, $sql);
	$data = mysqli_fetch_array($ress);
	// deskripsi halaman
	$pagedesc = "Buat Pengajuan";
	$menuparent = "cuti";
	include("layout_top.php");
	$now = date('Y-m-d');
	$npp = $sess_mngid;
?>
<script type="text/javascript">
function valid()
{
	if(document.cuti.akhir.value < document.cuti.mulai.value){
		alert("Tanggal akhir cuti harus lebih besar dari tanggal mulai cuti!");
		return false;
	}

	return true;
}
</script>

<!-- top of file -->
		<!-- Page Content -->
		<div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">Pengajuan Cuti</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->

				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<form class="form-horizontal" name="cuti" action="cuti_insert.php" method="POST" enctype="multipart/form-data" onSubmit="return valid();">
							<div class="panel panel-default">
								<div class="panel-heading"><h3>Form Pengajuan Cuti</h3></div>
								<div class="panel-body">

									<div class="form-group">
										<label class="control-label col-sm-3">Sisa Cuti</label>
										<div class="col-sm-4">
											<input type="text" class="form-control" required value="<?php echo $data['jml_cuti'] ?>" readonly>
										</div>
									</div>

									<!-- tujuan cuti -->
									<div class="form-group">
										<label class="control-label col-sm-3">Tipe Cuti</label>
										<div class="col-sm-4">
											<select name="tipe_cuti" id="tipe_cuti" class="form-control" required>
												<option value="" selected>---- Pilih Tipe Cuti ----</option>
												<option value="cuti tahunan">Cuti Tahunan (12 Hari)</option>
												<option value="cuti menikah">Cuti Menikah (3 Hari)</option>
												<option value="cuti hamil">Cuti Hamil (90 Hari )</option>
												<option value="keluarga inti">Keluarga Inti Meninggal (2 Hari)</option>
												<option value="menikahkan">Menikahkan Anak (2 Hari)</option>
												<option value="khitanan">Khitanan, Baptisan Anak (2 Hari)</option>
												<option value="istri">Istri Melahirkan (4 Hari)</option>
												<option value="keluarga">Anggota Keluarga Meninggal (1 Hari)</option>
												<option value="izin sakit">Izin Sakit</option>
												<option value="hutang cuti">Hutang Cuti</option>
											</select>
										</div>
									</div>
									<!-- end tujuan cuti -->

									<div class="form-group">
										<label class="control-label col-sm-3">Mulai Cuti</label>
										<div class="col-sm-4">
											<input type="date" name="mulai" class="form-control"  required>
											<input type="hidden" name="now" class="form-control" value="<?php echo $now;?>" required>
											<input type="hidden" name="npp" class="form-control" value="<?php echo $npp;?>" required>
										</div>
									</div>
									
									<div class="form-group">
										<label class="control-label col-sm-3">Akhir Cuti</label>
										<div class="col-sm-4">
											<input type="date" name="akhir" class="form-control"  required>
										</div>
									</div>

									<div class="form-group">
										<label class="control-label col-sm-3">Keterangan</label>
										<div class="col-sm-4">
											<textarea name="keterangan" class="form-control" placeholder="Keterangan" rows="3" required></textarea>
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
<script type="text/javascript">
document.addEventListener("DOMContentLoaded", function () {
    const tipeCuti = document.getElementById("tipe_cuti");
    const mulaiCuti = document.querySelector("input[name='mulai']");
    const akhirCuti = document.querySelector("input[name='akhir']");

    tipeCuti.addEventListener("change", function () {
        if (mulaiCuti.value) {
            hitungTanggalAkhir();
        }
    });

    mulaiCuti.addEventListener("change", function () {
        if (tipeCuti.value) {
            hitungTanggalAkhir();
        }
    });

    function hitungTanggalAkhir() {
    let durasi = 0;
    let mulai = new Date(mulaiCuti.value);

    // Reset readOnly setiap kali tipe cuti berubah
    akhirCuti.readOnly = false;

    if (tipeCuti.value === "cuti menikah") {
        durasi = 2;
    } else if (tipeCuti.value === "cuti hamil") {
        durasi = 89;
    } else if (tipeCuti.value === "keluarga inti") {
        durasi = 1;
    } else if (tipeCuti.value === "menikahkan") {
        durasi = 1;
    } else if (tipeCuti.value === "khitanan") {
        durasi = 1;
    } else if (tipeCuti.value === "istri") {
        durasi = 3;
    } else if (tipeCuti.value === "keluarga") {
        durasi = 0;
    } else {
        akhirCuti.value = ""; // Reset jika cuti tahunan
        return;
    }

    let akhir = new Date(mulai);
    akhir.setDate(mulai.getDate() + durasi);

    // Format ke YYYY-MM-DD agar sesuai dengan input date
    let akhirFormatted = akhir.toISOString().split("T")[0];

    akhirCuti.value = akhirFormatted;
    akhirCuti.readOnly = true; // Mencegah edit manual selain cuti tahunan
}

});
</script>

<?php
	include("layout_bottom.php");
?>