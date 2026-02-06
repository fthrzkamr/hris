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
                        <h1 class="page-header">Data Karyawan</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->
				
				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<div class="panel panel-default">
							<div class="panel-heading">
								<a href="karyawan_tambahh.php" class="btn btn-success">Tambah</a>
								<!--<a href="karyawan_cetak.php" class="btn btn-danger">.Pdf</a>-->
								<!--<a href="karyawan_xls.php" class="btn btn-success">.Xls</a>-->
							</div>
							<div class="panel-body">
								<table class="table table-striped table-bordered table-hover" id="tabel-data">
									<thead>
										<tr>
											<th width="1%">No</th>
											<th width="10%">Nomer Induk Karyawan</th>
											<th width="10%">Nama Karyawan</th>
											<th width="5%">Cabang</th>
											<th width="10%">Bagian</th>
											<th width="5%">Sisa Cuti</th>
											<th width="10%">Status Karyawan</th>
											<th width="10%">Opsi</th>
										</tr>
									</thead>
									<tbody>
										<?php
											$i = 1;
											$sql = "SELECT * FROM employee WHERE aktif='aktif' ORDER BY nama_emp ASC";
											$ress = mysqli_query($conn, $sql);
											while($data = mysqli_fetch_array($ress)) {
												echo '<tr>';
												echo '<td class="text-center">'. $i .'</td>';
												echo '<td class="text-center">'. $data['npp'] .'</td>';
												echo '<td class="text-center">'. $data['nama_emp'] .'</td>';
												echo '<td class="text-center">'. $data['cabang'] .'</td>';
												echo '<td class="text-center">'. $data['nama_bagian'] .'</td>';
												echo '<td class="text-center">'. $data['jml_cuti'] .'</td>';
												echo '<td class="text-center">'. $data['aktif'] .'</td>';
												echo '<td class="text-center">
													  <a href="#myModal" data-toggle="modal" data-load-code="'.$data['npp'].'" data-remote-target="#myModal .modal-body" class="btn btn-primary btn-xs">Detail</a>
													  <a href="karyawan_editt.php?npp='. $data['npp'] .'" class="btn btn-warning btn-xs">Edit</a>';?>
													  <a href="karyawan_hapuss.php?id=<?php echo $data['npp'];?>" onclick="return confirm('Apakah anda yakin akan menghapus <?php echo $data['nama_emp'];?>?');" class="btn btn-danger btn-xs">Hapus</a></td>
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
				{ "orderable": false, "targets": [6] }
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