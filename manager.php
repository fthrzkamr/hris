<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Data Manager";
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
                        <h1 class="page-header">Data Manager</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->
				
				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<div class="panel panel-default">
							<div class="panel-heading">
								<a href="manager_tambah.php" class="btn btn-success">Tambah</a>
							</div>
							<div class="panel-body">
								<table class="table table-striped table-bordered table-hover" id="tabel-data">
									<thead>
										<tr>
											<th width="1%">ID</th>
											<th width="10%">Nama Manager</th>
											<th width="5%">Akses</th>
                                            <th width="10%">Opsi</th>

										</tr>
									</thead>
									<tbody>
										<?php
											$i = 1;
											$sql = "SELECT * FROM manager ORDER BY nama_manager ASC";
											$ress = mysqli_query($conn, $sql);
											while($data = mysqli_fetch_array($ress)) {
												echo '<tr>';
												echo '<td class="text-center">'. $i .'</td>';
												echo '<td class="text-center">'. $data['nama_manager'] .'</td>';
												echo '<td class="text-center">'. $data['akses'] .'</td>';
												echo '<td class="text-center">
													  <a href="#myModal" data-toggle="modal" data-load-code="'.$data['id_manager'].'" data-remote-target="#myModal .modal-body" class="btn btn-primary btn-xs">Detail</a>
													  <a href="manager_edit.php?id_manager='. $data['id_manager'] .'" class="btn btn-warning btn-xs">Edit</a>';?>
													  <a href="manager_hapus.php?id_manager=<?php echo $data['id_manager'];?>" onclick="return confirm('Apakah anda yakin akan menghapus <?php echo $data['nama_manager'];?>?');" class="btn btn-danger btn-xs">Hapus</a></td>
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
				{ "orderable": false, "targets": [3] }
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
						$($this.data('remote-target')).load('manager_detail.php?code='+code);
						app.code = code;
						
					}
		});		
    </script>
<?php
	include("layout_bottom.php");
?>