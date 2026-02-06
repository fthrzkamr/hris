<?php
// session check
include("sess_check.php");
$pagedesc = "Upload Absensi Karyawan";
$menuparent = "insentif";
include("layout_top.php");
?>
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Upload Excel Absensi Karyawan</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->

            <?php include("layout_alert.php"); ?>
            
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <i class="fa fa-upload"></i> Form Upload Excel Absensi Karyawan
                        </div>
                        <div class="panel-body">
                            <form method="POST" action="insentif_karyawan_process.php" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label>File Excel</label>
                                    <input type="file" name="excel_file" class="form-control" required accept=".xlsx,.xls">
                                    <p class="help-block">Format file: .xlsx atau .xls</p>
                                </div>
                                
                                <div class="alert alert-info">
                                    <strong>Format Excel:</strong><br>
                                    <ul>
                                        <li>Kolom A: npp (NPP Karyawan)</li>
                                        <li>Kolom B: tanggal (Format: YYYY-MM-DD, contoh: 2025-12-01)</li>
                                        <li>Kolom C: jam_masuk (Format: HH:MM, contoh: 08:00)</li>
                                        <li>Kolom D: jam_pulang (Format: HH:MM, contoh: 16:00, boleh kosong jika belum pulang)</li>
                                    </ul>
                                    <strong>Contoh:</strong><br>
                                    <table class="table table-bordered" style="background: white; margin-top: 10px;">
                                        <tr>
                                            <th>npp</th>
                                            <th>tanggal</th>
                                            <th>jam_masuk</th>
                                            <th>jam_pulang</th>
                                        </tr>
                                        <tr>
                                            <td>19001</td>
                                            <td>2025-12-01</td>
                                            <td>08:00</td>
                                            <td>16:00</td>
                                        </tr>
                                        <tr>
                                            <td>23005</td>
                                            <td>2025-12-01</td>
                                            <td>07:30</td>
                                            <td>18:00</td>
                                        </tr>
                                        <tr>
                                            <td>23006</td>
                                            <td>2025-12-01</td>
                                            <td>08:00</td>
                                            <td>(kosong/belum pulang)</td>
                                        </tr>
                                    </table>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-upload"></i> Upload & Proses
                                </button>
                                <a href="insentif_karyawan_list.php" class="btn btn-default">
                                    <i class="fa fa-list"></i> Lihat Daftar
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        <!-- /#page-wrapper -->
<?php include("layout_bottom.php"); ?>
