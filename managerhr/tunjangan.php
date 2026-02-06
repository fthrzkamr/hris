<?php
include("sess_check.php");

// deskripsi halaman
$pagedesc = "Data Karyawan";
include("layout_top.php");
include("dist/function/format_tanggal.php");
include("dist/function/format_rupiah.php");

if (isset($_GET['success']) && $_GET['success'] == 1) {
    echo "<script>
        setTimeout(() => {
            Swal.fire({
                icon: 'success',
                title: 'Upload Berhasil!',
                text: 'Data potongan gaji berhasil diperbarui.',
                showConfirmButton: false,
                timer: 3000
            });
        }, 500);
    </script>";
}
?>
<!-- top of file -->
<!-- Page Content -->
<style>
	.panel-heading {
		display: flex;
		/* Membuat semua elemen dalam container sejajar horizontal */
		align-items: center;
		/* Memastikan semua elemen sejajar secara vertikal */
		gap: 15px;
		/* Mengatur jarak antar elemen */
	}

	.panel-heading form {
		display: flex;
		/* Elemen dalam form juga sejajar horizontal */
		align-items: center;
		/* Vertikal sejajar */
		gap: 10px;
		/* Mengatur jarak antar elemen dalam form */
	}

	.panel-heading input {
		width: auto;
		/* Atur lebar input sesuai konten */
	}

	.panel-heading label {
		margin: 0;
		/* Menghilangkan margin default label */
	}
</style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<div id="page-wrapper">
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-12">
				<h1 class="page-header">Informasi Karyawan</h1>
			</div><!-- /.col-lg-12 -->
		</div><!-- /.row -->

		<div class="row">
			<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
		</div>
		<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-body">
						<div class="panel-heading d-flex align-items-center gap-3">
							<form class="d-flex align-items-center gap-2" action="tunjangan_import.php" method="post" enctype="multipart/form-data">
								<label class="mb-0"><strong>Pilih File Excel (.xls/.xlsx)</strong></label>
								<input type="file" name="file_excel" class="form-control form-control-sm" accept=".xls,.xlsx" required>
								<button type="submit" class="btn btn-primary btn-sm">Upload</button>
							</form>
							<a href="tunjangan_lap.php" class="btn btn-success btn-sm">Laporan.xls</a>
						</div>

						<table class="table table-striped table-bordered table-hover stripe" id="tabel-data">
							<thead>
								<tr>
									<th width="1%">No</th>
									<th width="5%">NIP</th>
									<th width="15%">Nama Karyawan</th>
									<th width="10%">Gaji Pokok</th>
									<th width="10%">Tunj. Jabatan</th>
									<th width="10%">Tunj. Transport</th>
									<th width="10%">Tunj. Kinerja</th>
									<th width="10%">Total Gaji</th>
									<th width="10%">Hasil Gaji</th>
									<th width="10%">Opsi</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$i = 1;
								$sql = "SELECT e.npp, e.nama_emp, e.gaji_pokok, e.tunj_jabatan, e.tunj_transport, e.tunj_kinerja, e.total_gaji, p.p_hasil
										FROM employee e
										LEFT JOIN potongan p ON e.npp = p.npp
										WHERE e.aktif = 'Aktif'
										ORDER BY e.nama_emp ASC";
								$ress = mysqli_query($conn, $sql) or die("Query Error: " . mysqli_error($conn));
								while ($data = mysqli_fetch_array($ress)) {
								?>
									<tr>
										<td class="text-center"> <?php echo $i ?></td>
										<td class="text-center"> <?php echo $data['npp'] ?></td>
										<td class="text-center"> <?php echo $data['nama_emp'] ?></td>
										<td class="text-center"><?php echo number_format($data['gaji_pokok'], 0, ".", ".") ?></td>
										<td class="text-center"><?php echo number_format($data['tunj_jabatan'], 0, ".", ".") ?></td>
										<td class="text-center"><?php echo number_format($data['tunj_transport'], 0, ".", ".") ?></td>
										<td class="text-center"><?php echo number_format($data['tunj_kinerja'], 0, ".", ".") ?></td>
										<td class="text-center"><?php echo number_format($data['total_gaji'], 0, ".", ".") ?></td>
										<td class="text-center"><?php echo number_format($data['p_hasil'], 0, ".", ".") ?></td>
										<td class="text-center">
											<div class="d-flex justify-content-center gap-1">
												<a href="tunjangan_edit.php?npp=<?php echo $data['npp'] ?>" class="btn btn-warning btn-xs">Edit</a>
												<!-- <a href="tunjangan_hitung.php?npp=<?php echo $data['npp'] ?>" class="btn btn-info btn-xs">Hitung</a> -->
											</div>
										</td>
									</tr>
								<?php
									$i++;
								}
								?>
							</tbody>


						</table>
					</div>
					<!-- Large modal -->
					<div class="modal fade bs-example-modal" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
						<div class="modal-dialog modal-lg">
							<div class="modal-content">
								<div class="modal-body">
									<p>One fine body…</p>
								</div>
							</div>
						</div>
					</div>
				</div><!-- /.panel -->
			</div><!-- /.col-lg-12 -->
		</div><!-- /.row -->
	</div><!-- /.container-fluid -->
</div><!-- /#page-wrapper -->
<!-- bottom of file -->
<script type="text/javascript">
	$(document).ready(function() {
		$('#tabel-data').DataTable({
			"responsive": true,
			"processing": true,
			"columnDefs": [{
				"orderable": false,
				"targets": [7]
			}]
		});

		$('#tabel-data').parent().addClass("table-responsive");
	});
</script>
<?php
include("layout_bottom.php");
?>