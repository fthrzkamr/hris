<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Data Karyawan";
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
                        <h1 class="page-header">Pengaturan Limit</h1>
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
								<a href="karyawan_xls.php" class="btn btn-success">Data Download .xls</a>
							</div>
								<table class="table table-striped table-bordered table-hover stripe" id="tabel-data">
									<thead>
										<tr>
											<th width="1%">No</th>
											<th width="5%">Nomer Induk Karyawan</th>
											<th width="15%">Nama Karyawan</th>
											<th width="10%">Status Karyawan</th>
											<th width="5%">Status Reimburse</th>
											<th width="5%">Plafond Kesehatan</th>
											<th width="5%">Outstanding Kesehatan</th>
											<th width="5%">Plafond Kacamata</th>
											<th width="5%">Outstanding Kacamata</th>
											<th width="10%">Opsi</th>
										</tr>
									</thead>
									<tbody>
										<?php
											$i = 1;
											$sql = "SELECT * FROM employee WHERE aktif = 'Aktif' ORDER BY nama_emp ASC";
											// $sql = "SELECT * FROM employee AS A LEFT JOIN bagian AS B ON  A.nama_bagian=b.id ORDER BY nama_emp ASC";
											$ress = mysqli_query($conn, $sql);
											while($data = mysqli_fetch_array($ress)) {
										?>
										<tr>
												<td class="text-center"> <?php echo $i ?></td>
												<td class="text-center"> <?php echo$data['npp']?></td>
												<td class="text-center"> <?php echo$data['nama_emp']?></td>
												<td class="text-center"> <?php echo$data['aktif']?></td>
												<td class="text-center"> 
													<?php if ($data['status_rem'] == 'Aktif' ){
														echo "<span class='badge badge badge-danger'>Aktif</span>";
													}else if($data['status_rem'] == 'N/A'){
														echo "<span>N/A</span>";
													}
													?>
												</td>
												<td class="text-center"><?php echo number_format($data['plafond'], 0, ".", ".")?></td>													
												<td class="text-center"><?php echo number_format($data['kesehatan'], 0, ".", ".")?></td>
												<td class="text-center"><?php echo number_format($data['plafond_kacamata'], 0, ".", ".")?></td>													
												<td class="text-center"><?php echo number_format($data['kacamata'], 0, ".", ".")?></td>														
												<td> 
													<center><a href="karyawan_edit.php?npp=<?php echo $data['npp'] ?>" class="btn btn-warning btn-xs">Edit</a></center>
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
			"columnDefs": [
				{ "orderable": false, "targets": [7] }
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
						$($this.data('remote-target')).load('karyawan_detail.php?code='+code);
						app.code = code;
						
					}
		});		
    </script>
<?php
	include("layout_bottom.php");
?>