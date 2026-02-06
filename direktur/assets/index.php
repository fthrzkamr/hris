<?php
include("sess_check.php");

$id = $sess_admid;

// Get employee information
$sql_g = "SELECT * FROM employee WHERE npp='$id'";
$ress_g = mysqli_query($conn, $sql_g);
$res = mysqli_fetch_array($ress_g);

// Count leave applications for the logged-in user
$sqlc = "SELECT * FROM cuti WHERE npp='$id'";
$ressc = mysqli_query($conn, $sqlc);
$c = mysqli_num_rows($ressc);

// Count leave applications where the logged-in user is a manager
$sqld = "SELECT * FROM cuti WHERE manager='$id'";
$ressd = mysqli_query($conn, $sqld);
$d = mysqli_num_rows($ressd);

// Count overtime applications for the logged-in user
$sqle = "SELECT * FROM lembur WHERE npp='$id'";
$resse = mysqli_query($conn, $sqle);
$f = mysqli_num_rows($resse);

// Count reimbursement applications for the logged-in user
$sqle = "SELECT * FROM rembes WHERE npp='$id'";
$resse = mysqli_query($conn, $sqle);
$g = mysqli_num_rows($resse);

// Count active employees
$sql_e = "SELECT npp FROM employee WHERE aktif='Aktif'";
$ress_e = mysqli_query($conn, $sql_e);
$ea = mysqli_num_rows($ress_e);

// Count leave applications awaiting HRD approval
$sql_wait = "SELECT no_cuti FROM cuti WHERE stt_cuti='Menunggu APproval HRD'";
$ress_wait = mysqli_query($conn, $sql_wait);
$wait = mysqli_num_rows($ress_wait);

// Count all overtime applications
$sql_wait = "SELECT id_lmbr FROM lembur";
$ress_wait = mysqli_query($conn, $sql_wait);
$lembur = mysqli_num_rows($ress_wait);

// Page description
$pagedesc = "Beranda";
include("layout_top.php");
include("dist/function/format_rupiah.php");
?>
<link rel="stylesheet" href="assets/style.css" />
<link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"
      integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6">
                <form class="form-horizontal">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="calendar">
                                <div class="month">
                                    <i class="fas fa-angle-left prev"></i>
                                    <div class="date">December 2015</div>
                                    <i class="fas fa-angle-right next"></i>
                                </div>
                                <div class="weekdays">
                                    <div>Sun</div>
                                    <div>Mon</div>
                                    <div>Tue</div>
                                    <div>Wed</div>
                                    <div>Thu</div>
                                    <div>Fri</div>
                                    <div>Sat</div>
                                </div>
                                <div class="days"></div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
			<div class="form-harizontal">
				<div class="panel panel-default">
					<div class="panel-body">
						<div class="col-lg-6">
							<div class="right">
								<div class="today-date">
									<div class="event-day">Wed</div>
									<div class="event-date">12th December 2022</div>
								</div>
								<div class="events"></div>
								<div class="add-event-wrapper">
									<div class="add-event-body">
										<div class="add-event-input">
											<input type="text" placeholder="Event Name" class="event-name" />
										</div>
										<div class="add-event-input">
											<input type="text" placeholder="Event Time From" class="event-time-from" />
										</div>
										<div class="add-event-input">
											<input type="text" placeholder="Event Time To" class="event-time-to" />
										</div>
									</div>
								</div>
							</div>
            			</div>
					</div>
				</div>
			</div>

        </div>
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
                            <div class="col-xs-3"></div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?php echo $c; ?></div>
                                <div><h4>Data Cuti</h4></div>
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
            </div>
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
                            <div class="col-xs-3"></div>
                            <div class="col-xs-9 text-right bold">
                                <div class="huge text-bold"><?php echo $f; ?></div>
                                <div><h4>Data Lemburan</h4></div>
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
            </div>
        </div>
    </div>
</div>
<script src="assets/script.js"></script>
<?php include("layout_bottom.php"); ?>
