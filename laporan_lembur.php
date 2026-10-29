<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Laporan Data Lemburan";
	include("layout_top.php");
	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");
?>
<!-- top of file -->
		<!-- Page Content -->
		<div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">Laporan Data Lemburan</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->
				
				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<div class="panel panel-default">
							<div class="panel-body">
					        <form method="get" name="laporan" onSubmit="return valid();"> 
								<div class="form-group">
									<div class="col-sm-4">
										<label>Tanggal Awal </label>
										<input type="date" class="form-control" name="awal" placeholder="From Date(dd/mm/yyyy)" required>
									</div>
									<div class="col-sm-4">
										<label>Tanggal Akhir </label>
										<input type="date" class="form-control" name="akhir" placeholder="To Date(dd/mm/yyyy)" required>
									</div>
									<div class="col-sm-4">
										<label>&nbsp;</label><br/>
										<input type="submit" name="submit" value="Lihat Laporan" class="btn btn-primary">
									</div>
								</div>
							</form>
							</div>
						</div>
						<?php
							if(isset($_GET['submit'])){
								$no=0;
								$mulai 	 = $_GET['awal'];
								$selesai = $_GET['akhir'];
								$sql = "SELECT lembur.*, employee.* FROM lembur, employee WHERE lembur.npp=employee.npp 
										AND lembur.tgl_lembur BETWEEN '$mulai' AND '$selesai' ORDER BY lembur.tgl_lembur DESC";
								$query = mysqli_query($conn,$sql);
							?>
				
				<div class="row">
					<div class="col-lg-12">
						<div class="panel panel-default">
							<div class="panel-body">
								<table class="table table-striped table-bordered table-hover" id="tabel-data">
									<thead>
										<tr>
											<th width="1%">No</th>
											<th width="10%">Nama Karyawan</th>
											<th width="5%">Tujuan Lembur</th>
											<th width="5%">Tgl Lembur</th>
											<th width="5%">Jam Mulai Lembur</th>
											<th width="5%">Jam Berakhir Lembur</th>
											<th width="5%">Status Lemburan</th>
											
										</tr>
									</thead>
									<tbody>
										<?php
											$i=1;
											while($data = mysqli_fetch_array($query)) {
												echo '<tr>';
												echo '<td class="text-center">'. $i .'</td>';
												echo '<td class="text-center">'. $data['nama_karyawan'] .'</td>';
												echo '<td class="text-center">'. $data['tujuan_lembur'] .'</td>';
												echo '<td class="text-center">'. IndonesiaTgl($data['tgl_lembur']) .'</td>';
												echo '<td class="text-center">'. $data['jam_mulai_lembur'] .'</td>';
												echo '<td class="text-center">'. $data['jam_berakhir_lembur'] .'</td>';
												echo '<td class="text-center">'. $data['status'] .'</td>';
												echo '</tr>';
												$i++;
											    }
										    ?>
									</tbody>
								</table>
							<div class="form-group">
									<a href="laporan_cetak.php?awal=<?php echo $mulai;?>&akhir=<?php echo $selesai;?>" target="_blank" class="btn btn-warning">Cetak</a>
						            <a href="laporan_lembur_xls.php?awal=<?php echo $mulai;?>&akhir=<?php echo $selesai;?>" target="_blank" class="btn btn-success">xls</a>
							</div>
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
			<?php }?>
            </div><!-- /.container-fluid -->
        </div><!-- /#page-wrapper -->
<!-- bottom of file -->
<script type="text/javascript">
	$(document).ready(function() {
		$('#tabel-data').DataTable({
			"responsive": true,
			"processing": true,
			"columnDefs": [
				{ "orderable": false, "targets": [4] }
			]
		});
		
		$('#tabel-data').parent().addClass("table-responsive");
	});
</script>
<script>
		var app = {
			code: '0'
		};
</script>
<?php
	include("layout_bottom.php");
?>
