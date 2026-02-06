<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Pengajuan Sistem";
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
                        <h1 class="page-header">Pengajuan Sistem</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->
				
				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-12">
						<div class="panel panel-default">
							<div class="panel-heading">
								<a href="pengajuan_sistem_tambah.php" class="btn btn-success">Tambah</a>
							</div>
							<div class="panel-body">
								<table class="table table-striped table-bordered table-hover" id="tabel-data">
									<thead>
										<tr>
											<th width="1%">No</th>
											<th width="2%">No Pengajuan</th>
											<th width="10%">Nama</th>
											<th width="40%">Sistem yang diajukan</th>
											<th width="5%">Bagian</th>
											<th width="5%">Tgl Pengajuan</th>
											<th width="5%">Status Pengajuan</th>
                                            <th width="10%">Opsi</th>

										</tr>
									</thead>
									<style>
										td.text-justify {
											max-width: 300px;
											word-wrap: break-word;
											white-space: normal;
										}
									</style>

									<tbody>
										<?php
											$i = 1;
											$sql = "SELECT * FROM pengajuan_sistem ORDER BY no_pengajuan DESC";
											$ress = mysqli_query($conn, $sql);
											while($data = mysqli_fetch_array($ress)) {
												// Menghapus spasi ekstra dalam deskripsi_pengajuan
												$deskripsi_pengajuan = preg_replace('/\s+/', ' ', $data['deskripsi_pengajuan']);
												echo '<tr>';
												echo '<td class="text-center">'. $i .'</td>';
												echo '<td class="text-center">'. $data['no_pengajuan'] .'</td>';
												echo '<td class="text-center">'. $data['nama_karyawan'] .'</td>';
												echo '<td class="text-justify">'. $deskripsi_pengajuan .'</td>';
												echo '<td class="text-center">'. $data['nama_bagian'] .'</td>';
												echo '<td class="text-center">'. $data['tgl_pengajuan'] .'</td>';
												echo '<td class="text-center">'. ucwords($data['status_pengajuan']) .'</td>';
												echo '<td class="text-center">';
												echo '<a href="pengajuan_sistem_edit.php?no_pengajuan='. $data['no_pengajuan'] .'" class="btn btn-warning btn-xs">Edit</a> ';
												echo '<a href="pengajuan_sistem_hapus.php?no_pengajuan='. $data['no_pengajuan'] .'" onclick="return confirm(\'Apakah anda yakin akan menghapus nomor pengajuan '. $data['no_pengajuan'] .'?\');" class="btn btn-danger btn-xs">Hapus</a>';
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
				{ "orderable": false, "targets": [2] }
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
						$($this.data('remote-target')).load('bagian_detail.php?code='+code);
						app.code = code;
						
					}
		});		
</script>
<?php
	include("layout_bottom.php");
?>