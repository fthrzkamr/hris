<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Data Lemburan";
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
                        <h1 class="page-header">Data Lemburan Karyawan</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->
				
				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<div class="panel panel-default">
							
							<div class="panel-body">
								<?php
										$Sql = "SELECT lembur.*, employee.* FROM lembur, employee WHERE lembur.npp=employee.npp AND employee.nama_koordinator='1' ORDER BY lembur.tgl_lembur DESC";
								$Qry = mysqli_query($conn, $Sql);
										
								?>						
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
											<th width="5%">Opsi</th>
										</tr>
									</thead>
									<tbody>
										<?php
											$i=1;
											while($data = mysqli_fetch_array($Qry)){
												echo '<tr>';
												echo '<td class="text-center">'. $i .'</td>';
												echo '<td class="text-center">'. $data['nama_karyawan'] .'</td>';
												echo '<td class="text-center">'. $data['tujuan_lembur'] .'</td>';
												echo '<td class="text-center">'. IndonesiaTgl($data['tgl_lembur']) .'</td>';
												echo '<td class="text-center">'. $data['jam_mulai_lembur'] .'</td>';
												echo '<td class="text-center">'. $data['jam_berakhir_lembur'] .'</td>';
												echo '<td class="text-center">'. $data['status'] .'</td>';
												echo '<td class="text-center">
													  ';?>
													  <!-- <a href="lembur_hapus.php?id_lmbr=<?php echo $data['id_lmbr'];?>" onclick="return confirm('Apakah anda yakin akan menghapus Lembur. <?php echo $data['id_lmbr'];?>?');" class="btn btn-danger btn-xs">Hapus</a></td> -->
													  <a href="lembur_review.php?no=<?php echo $data['id_lmbr'];?>" class="btn btn-primary btn-xs">Review</a></td>

											<?php
													  echo '</td>';
												echo '</tr>';												
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
							<p>Sedang memproses…</p>
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
			"columnDefs": [
				{ "orderable": false, "targets": [] }
			]
		});
		
		$('#tabel-data').parent().addClass("table-responsive");
	});
</script>
	<script>
		var app = {
			code: '0'
		};
		
		$('[data-load-code]').on('click',function(e) {
					e.preventDefault();
					var $this = $(this);
					var code = $this.data('load-code');
					if(code) {
						$($this.data('remote-target')).load('lembur_detail.php?code='+code);
						app.code = code;
						
					}
		});		

    </script>
<?php
	include("layout_bottom.php");
?>