<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Data Reimburse";
	include("layout_top.php");
	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");
	$id = $sess_mngid;
?>
<!-- top of file -->
		<!-- Page Content -->
		<div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">Data Reimbursement </h1>
						
                    </div><!-- /.col-lg-12 -->
					<div class="panel-heading">
						<a href="reimburse_create.php" class="btn btn-success">Pengajuan Baru</a>
					</div>
                </div><!-- /.row -->
				
				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<div class="panel panel-default">
							<div class="panel-body">
						<?php
								$Sql = "SELECT rembes.*, employee.* FROM rembes, employee WHERE rembes.npp=employee.npp AND rembes.npp='$id' ORDER BY rembes.tanggal_pemeriksaan DESC";
								$Qry = mysqli_query($conn, $Sql);
								
							?>						
								<table class="table table-striped table-bordered table-hover" id="tabel-data">
									<thead>
										<tr>
											<th width="1%">No</th>
											<th width="10%">Nama Karyawan</th>
											<th width="5%">Fasilitas Kesehatan</th>
											<th width="5%">Nama Dokter</th>
											<th width="5%">Tanggal Pemeriksaan</th>
											<th width="5%">Total Kwitansi</th>
											<th width="5%">Total Reimburse</th>
                                            <th width="5%">Status</th>
											<th width="5%">Opsi</th>
										</tr>
									</thead>
									<tbody>
										<?php
										
											$i=1;
											while($data = mysqli_fetch_array($Qry)){
												$rembes = $data['total_kwitansi'];
												$a		=(80 / 100);
												$p		= $rembes * $a ;
												echo '<tr>';
												echo '<td class="text-center">'. $i .'</td>';
												echo '<td class="text-center">'. $data['nama_emp'] .'</td>';
												echo '<td class="text-center">'. $data['fasilitas_kesehatan'] .'</td>';
                                                echo '<td class="text-center">'. $data['nama_dokter'] .'</td>';

												echo '<td class="text-center">'. IndonesiaTgl($data['tanggal_pemeriksaan']) .'</td>';
												echo '<td class="text-center">'.number_format($data['total_kwitansi'], 0, ".", ".") .'</td>';
												echo '<td class="text-center">'. number_format($p, 0, ".", ".") .'</td>';

												echo '<td class="text-center">'. $data['status'] .'</td>';
												echo '<td class="text-center">
													  <a href="#myModal" data-toggle="modal" data-load-code="'.$data['id_rmbs'].'" data-remote-target="#myModal .modal-body" class="btn btn-primary btn-xs">Detail</a>';?>
													  <a href="reimburse_hapus.php?id_rmbs=<?php echo $data['id_rmbs'];?>" onclick="return confirm('Apakah anda yakin akan menghapus Data Reimburse. <?php echo $data['id_rmbs'];?>?');" class="btn btn-danger btn-xs">Hapus</a></td>
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
						$($this.data('remote-target')).load('reimburse_detail.php?code='+code);
						app.code = code;
						
					}
		});		

    </script>
<?php
	include("layout_bottom.php");
?>