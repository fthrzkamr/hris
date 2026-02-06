<?php
include("sess_check.php");

$id = $sess_mngid;

$sql_g = "SELECT B.nama_bagian,B.id_bagian,A.npp,A.nama_emp,A.cabang,A.foto_emp FROM employee AS A LEFT JOIN bagian AS B ON A.nama_bagian = B.id_bagian  WHERE npp='$id'";
$ress_g = mysqli_query($conn, $sql_g);
$res = mysqli_fetch_array($ress_g);

// menunggu approved
$sqlb = "SELECT * FROM cuti WHERE npp='$id'";
$ressb = mysqli_query($conn, $sqlb);
$b = mysqli_num_rows($ressb);
// end

$sqlc = "SELECT * FROM cuti WHERE npp='$id'";
$ressc = mysqli_query($conn, $sqlc);
$c = mysqli_num_rows($ressc);

$sqla = "SELECT * FROM cuti WHERE npp='$id'";
$ressa = mysqli_query($conn, $sqla);
$a = mysqli_num_rows($ressa);

$sqld = "SELECT * FROM cuti WHERE manager='$id' ";
$ressd = mysqli_query($conn, $sqld);
$d = mysqli_num_rows($ressd);

$sqle = "SELECT * FROM cuti WHERE manager='$id'";
$resse = mysqli_query($conn, $sqle);
$e = mysqli_num_rows($resse);

$sqle = "SELECT * FROM lembur WHERE npp='$id'";
$resse = mysqli_query($conn, $sqle);
$f = mysqli_num_rows($resse);

$sqle = "SELECT * FROM rembes WHERE npp='$id'";
$resse = mysqli_query($conn, $sqle);
$g = mysqli_num_rows($resse);
// deskripsi halaman
$pagedesc = "Beranda";
include("layout_top.php");
include("dist/function/format_rupiah.php");
?>
<!-- top of file -->
<!-- Page Content -->
<div id="page-wrapper">
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-12">
				<form class="form-horizontal">
					<div class="panel panel-default">
						<div class="panel-body">
							<h2 align="center">Selamat Datang, <?php echo $res['nama_emp']; ?> ! </h2>
							<hr />
							<div>
								<img src="../foto/<?php echo $res['foto_emp'] ?>" width="210px" align="left" style="margin-left: 300px; margin-right:50px" />

								<br />

								<table>
									<tr>
										<th>NIK</th>
										<td></td>
										<th width="50px"><br>
											<center>:</center><br>
										</th>
										<th><?php echo $res['npp']; ?></th>
									</tr>
									<tr>
										<th>CABANG </th>
										<th></th>
										<th width="50px"><br>
											<center>:</center><br>
										</th>
										<th><?php echo $res['cabang']; ?></th>
									</tr>
									<tr>
										<th>BAGIAN</th>
										<th></th>
										<th width="50px"><br>
											<center>:</center><br>
										</th>
										<th><?php echo $res['nama_bagian']; ?></th>
									</tr>
								</table>
							</div>
						</div>
				</form>
			</div><!-- /.col-lg-12 -->
		</div><!-- /.row -->
		<div class="row">
			<div class="col-lg-4 col-md-4">
				<div class="panel panel-primary">
					<a href="cuti_create.php">
						<div class="panel-footer">
							<span class="pull-left">Pengajuan Cuti</span>
							<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
							<div class="clearfix"></div>
						</div>
					</a>
					<div class="panel-heading">
						<div class="row">
							<div class="col-xs-3">
								<!-- <i class="fa fa-check-circle fa-3x"></i> -->
							</div>
							<div class="col-xs-9 text-right">
								<div class="huge"><?php echo $a; ?></div>
								<div>
									<h4>Data Cuti</h4>
								</div>
							</div>
						</div>
					</div>
					<a href="cuti_app.php">
						<div class="panel-footer">
							<span class="pull-left">Lihat Rincian</span>
							<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
							<div class="clearfix"></div>
						</div>
					</a>
				</div>
			</div><!-- /.panel-green -->

			<div class="col-lg-4 col-md-4">

				<div class="panel panel-yellow">
					<a href="lembur_create.php">
						<div class="panel-footer">
							<span class="pull-left">Pengajuan Lembur</span>
							<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
							<div class="clearfix"></div>
						</div>
					</a>
					<div class="panel-heading">
						<div class="row">
							<div class="col-xs-3">
								<!-- <i class="fa fa-plus-circle fa-3x"></i> -->
							</div>
							<div class="col-xs-9 text-right bold">
								<div class="huge text-bold"><?php echo $f; ?></div>
								<div>
									<h4>Data Lemburan</h4>
								</div>
							</div>
						</div>
					</div>
					<a href="lembur_waitapp.php">
						<div class="panel-footer">
							<span class="pull-left">Lihat Rincian</span>
							<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
							<div class="clearfix"></div>
						</div>
					</a>
				</div>
			</div><!-- /.panel-green -->


		</div><!-- /.row -->

	</div><!-- /.container-fluid -->
</div><!-- /#page-wrapper -->
<!-- bottom of file -->
<?php
include("layout_bottom.php");
?>