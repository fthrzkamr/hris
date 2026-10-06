<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Laporan Data Cuti";
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
                        <h1 class="page-header">Laporan Data Cuti</h1>
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
										<label>Tanggal Awal Cuti</label>
										<input type="date" class="form-control" name="awal" placeholder="From Date(dd/mm/yyyy)" required>
									</div>
									<div class="col-sm-4">
										<label>Tanggal Akhir Cuti</label>
										<input type="date" class="form-control" name="akhir" placeholder="To Date(dd/mm/yyyy)" required min="<?php echo date('Y-m-d'); ?>">
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
								$sql = "SELECT cuti.no_cuti, cuti.npp, cuti.tgl_pengajuan, cuti.tgl_awal, cuti.tgl_akhir, 
										cuti.keterangan, cuti.stt_cuti, employee.nama_emp, koordinator.nama_koordinator 
										FROM cuti 
										LEFT JOIN employee ON cuti.npp=employee.npp 
										LEFT JOIN koordinator ON employee.nama_koordinator=koordinator.id_koordinator
										WHERE cuti.tgl_pengajuan BETWEEN '$mulai' AND '$selesai' ORDER BY cuti.tgl_pengajuan DESC";
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
											<!-- <th width="10%">No Cuti</th> -->
											<th width="10%">Nama Karyawan</th>
											<th width="10%">Koordinator</th>
											<th width="5%">Tgl Pengajuan</th>
											<th width="5%">Tgl Awal Cuti</th>
											<th width="5%">Tgl Akhir Cuti</th>
											<th width="5%">Keterangan Cuti</th>
											<th width="5%">Status</th>
										</tr>
									</thead>
									<tbody>
										<?php
											$i=1;
											while($data = mysqli_fetch_array($query)) {
												echo '<tr>';
												echo '<td class="text-center">'. $i .'</td>';
												// echo '<td>'. $data['no_cuti'] .'</td>';
												echo '<td>'. $data['nama_emp'] .'</td>';
												echo '<td>'. $data['nama_koordinator'] .'</td>';
												echo '<td class="text-center text-nowrap">'. format_tanggal($data['tgl_pengajuan']) .'</td>';
												echo '<td class="text-center text-nowrap">'. format_tanggal($data['tgl_awal']) .'</td>';
												echo '<td class="text-center text-nowrap">'. format_tanggal($data['tgl_akhir']) .'</td>';
												echo '<td>'. $data['keterangan'] .'</td>';
												echo '<td>'. $data['stt_cuti'] .'</td>';
												echo '</tr>';
												$i++;
											}
										?>
									</tbody>
								</table>
							<div class="form-group">
									<a href="laporan_cuti.php?awal=<?php echo $mulai;?>&akhir=<?php echo $selesai;?>" target="_blank" class="btn btn-danger">pdf</a>
									<a href="laporan_xls.php?awal=<?php echo $mulai;?>&akhir=<?php echo $selesai;?>" target="_blank" class="btn btn-success">xls</a>
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