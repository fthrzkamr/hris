<?php
require_once(file_exists(__DIR__ . "/libur_helper.php") ? __DIR__ . "/libur_helper.php" : dirname(__DIR__) . "/libur_helper.php");
$libur_nasional = get_libur_nasional();
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
												<option value="izin">Izin</option>
												<option value="izin Sakit">Izin Sakit</option>
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
    const liburNasional = <?php echo json_encode($libur_nasional); ?>;

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
        if (!mulaiCuti.value || !tipeCuti.value) return;

        let targetHari = 0;
        const tipe = tipeCuti.value;

        // Reset readOnly tiap tipe cuti berubah
        akhirCuti.readOnly = false;

        if (tipe === "cuti menikah") {
            targetHari = 3;
        } else if (tipe === "cuti hamil") {
            targetHari = 90;
        } else if (tipe === "keluarga inti") {
            targetHari = 2;
        } else if (tipe === "menikahkan") {
            targetHari = 2;
        } else if (tipe === "khitanan") {
            targetHari = 2;
        } else if (tipe === "istri") {
            targetHari = 4;
        } else if (tipe === "keluarga") {
            targetHari = 1;
        } else {
            akhirCuti.value = ""; // Reset jika cuti tahunan / izin dll
            return;
        }

        // Parse tanggal mulai dalam lokal time (YYYY-MM-DD)
        let parts = mulaiCuti.value.split("-");
        let curr = new Date(parts[0], parts[1] - 1, parts[2]);

        let count = 0;
        while (count < targetHari) {
            let y = curr.getFullYear();
            let m = String(curr.getMonth() + 1).padStart(2, '0');
            let d = String(curr.getDate()).padStart(2, '0');
            let tglStr = `${y}-${m}-${d}`;

            // Lewati hari Minggu (0 = Minggu) DAN Libur Nasional
            if (curr.getDay() !== 0 && !liburNasional.includes(tglStr)) {
                count++;
            }
            if (count < targetHari) {
                curr.setDate(curr.getDate() + 1);
            }
        }

        // Format ke YYYY-MM-DD
        let y = curr.getFullYear();
        let m = String(curr.getMonth() + 1).padStart(2, '0');
        let d = String(curr.getDate()).padStart(2, '0');

        akhirCuti.value = `${y}-${m}-${d}`;
        akhirCuti.readOnly = true; // Mencegah edit manual selain cuti tahunan
    }

});
</script>

<?php
	include("layout_bottom.php");
?>