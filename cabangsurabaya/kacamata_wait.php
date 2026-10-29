<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Waiting Approval";
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
                        <h1 class="page-header">Data Approval Kacamata </h1>
                    </div><!-- /.col-lg-12 -->
					
                </div><!-- /.row -->
				
				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<div class="panel panel-default">
						<div class="panel-body">

						<div class="panel-heading">
								<a href="kacamata_xls.php" class="btn btn-success">Data Kacamata.xls</a>
							</div>

						<?php
								$Sql = "SELECT kacamata.*, employee.* FROM kacamata, employee WHERE kacamata.npp=employee.npp 
										 ORDER BY kacamata.tanggal_pengajuan DESC";
								$Qry = mysqli_query($conn, $Sql);
								
							?>						
								<table class="table table-striped table-bordered table-hover" id="tabel-data">
									<thead>
										<tr>
											<th width="1%">No</th>
											<th width="10%">Nama Karyawan</th>
											<th width="5%">Nama Fasilitas</th>
											<th width="5%">Tgl Pengajuan</th>
											<th width="5%">Total Kwitansi</th>
											<th width="5%">Total Rembes</th>
											<th width="5%">Sisa Limit</th>
											<th width="5%">Status</th>
											<th width="5%">Opsi</th>
										</tr>
									</thead>
									<tbody>
										<?php
											$i=1;
											while($data = mysqli_fetch_array($Qry)){
												$limit	= $data['kacamata'];
												$kacamata = $data['total_kwintansi'];
												$a		=(80 / 100);
												$p		= $kacamata * $a ;
												echo '<tr>';
												echo '<td class="text-center">'. $i .'</td>';
												// echo '<td class="text-center">'. $data['no_cuti'] .'</td>';
												echo '<td class="text-center"><a href="#myModal" data-toggle="modal" data-load-npp="'.$data['npp'].'" data-remote-target="#myModal .modal-body">'.$data['nama_emp'].'</a></td>';
												echo '<td class="text-center">'. $data['nama_fasilitas'] .'</td>';
												echo '<td class="text-center">'. IndonesiaTgl($data['tanggal_pengajuan']) .'</td>';
												echo '<td class="text-center">'.number_format($data['total_kwintansi'], 0, ".", ".") .'</td>';
												echo '<td class="text-center">'. number_format($p, 0, ".", ".").'</td>';
												echo '<td class="text-center">'.number_format($data['kacamata'], 0, ".", ".") .'</td>';
												echo '<td class="text-center">'. $data['status'] .'</td>';
												echo '<td class="text-center">
													  ';?>
													  <a href="kacamata_review.php?no=<?php echo $data['id_kacamata'];?>" class="btn btn-primary btn-xs">Review</a></td>
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

		$('[data-load-npp]').on('click',function(e) {
					e.preventDefault();
					var $this = $(this);
					var npp = $this.data('load-npp');
					if(npp) {
						$($this.data('remote-target')).load('karyawan_detail.php?code='+npp);
						app.npp = npp;
						
					}
		});		
    </script>
<?php
	include("layout_bottom.php");
?>