<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Data Calon Karyawan";
	include("layout_top.php");
	include("dist/function/format_tanggal.php");
	
	if (!function_exists('tgl_eng_to_ind')) {
		function tgl_eng_to_ind($date) {
			$bulan = array('01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember');
			$split = explode('-', $date);
			return $split[2] . ' ' . $bulan[$split[1]] . ' ' . $split[0];
		}
	}
?>
<!-- top of file -->
		<!-- Page Content -->
		<div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">Data Calon Karyawan</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->
				
				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<!-- Tabel: Calon Karyawan Menunggu Review -->
				<div class="row">
					<div class="col-lg-12">
						<div class="panel panel-default">		
							<div class="panel-body">
								<table class="table table-striped table-bordered table-hover stripe" id="tabel-data">
									<thead>
										<tr>
											<th width="1%">No</th>
											<th width="8%">NPP</th>
											<th width="15%">Nama Lengkap</th>
											<th width="10%">Jenis Kelamin</th>
											<th width="10%">Tanggal Lahir</th>
											<th width="10%">No. Telepon</th>
											<th width="10%">Cabang</th>
											<th width="10%">Status</th>
											<th width="15%">Opsi</th>
										</tr>
									</thead>
									<tbody>
										<?php
											$i = 1;
											$sql = "SELECT * FROM employee 
											                WHERE status_karyawan = 'Calon Karyawan' AND aktif = 'Menunggu Review'
											                ORDER BY npp DESC";
											$ress = mysqli_query($conn, $sql);
											while($data = mysqli_fetch_array($ress)) {
										?>
										<tr>
												<td class="text-center"> <?php echo $i ?></td>
												<td class="text-center"> <?php echo$data['npp']?></td>
												<td> <?php echo$data['nama_emp']?></td>
												<td class="text-center"> <?php echo$data['jk_emp']?></td>
												<td class="text-center"> <?php echo!empty($data['tanggal_lahir']) ? tgl_eng_to_ind($data['tanggal_lahir']) : '-'?></td>
												<td class="text-center"> <?php echo$data['telp_emp']?></td>
												<td class="text-center"> <?php echo$data['cabang']?></td>
												<td class="text-center">
													<span class="label label-warning">Menunggu Review</span>
												</td>
												<td class="text-center"> 
													<a href="calon_karyawan_detail.php?npp=<?php echo $data['npp'] ?>" class="btn btn-info btn-xs">Detail</a>
													<a href="calon_karyawan_approve.php?npp=<?php echo $data['npp'] ?>" class="btn btn-success btn-xs" onclick="return confirm('Setujui calon karyawan ini?')">Approve</a>
													<a href="calon_karyawan_hapus.php?npp=<?php echo $data['npp'] ?>" class="btn btn-danger btn-xs" onclick="return confirm('Hapus data calon karyawan ini?')">Hapus</a>
												</td>
										</tr>											
									<?php
												$i++;
											}
										?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
<?php
	include("layout_bottom.php");
?>
