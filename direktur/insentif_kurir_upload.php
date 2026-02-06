<?php
// session check
include("sess_check.php");
$pagedesc = "Upload Insentif Kurir";
$menuparent = "insentif";
include("layout_top.php");
?>
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Upload Excel Insentif Kurir</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->

            <?php include("layout_alert.php"); ?>
            
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <i class="fa fa-upload"></i> Form Upload Excel Insentif Kurir
                        </div>
                        <div class="panel-body">
                            <form method="POST" action="insentif_kurir_process.php" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label>File Excel</label>
                                    <input type="file" name="excel_file" class="form-control" required accept=".xlsx,.xls">
                                    <p class="help-block">Format file: .xlsx atau .xls</p>
                                </div>
                                
                                <div class="alert alert-info">
                                    <strong>Format Excel:</strong><br>
                                    <ul>
                                        <li>Kolom A: npp (NPP Karyawan)</li>
                                        <li>Kolom B: periode (Format: YYYY-MM-DD, contoh: 2025-12-01)</li>
                                        <li>Kolom C: total_titik (Total titik yang dicapai)</li>
                                        <li>Kolom D: target_titik (Target titik yang harus dicapai)</li>
                                    </ul>
                                    <strong>Contoh:</strong><br>
                                    <table class="table table-bordered" style="background: white; margin-top: 10px;">
                                        <tr>
                                            <th>npp</th>
                                            <th>periode</th>
                                            <th>total_titik</th>
                                            <th>target_titik</th>
                                        </tr>
                                        <tr>
                                            <td>23005</td>
                                            <td>2025-12-01</td>
                                            <td>1500</td>
                                            <td>1000</td>
                                        </tr>
                                        <tr>
                                            <td>23006</td>
                                            <td>2025-12-01</td>
                                            <td>800</td>
                                            <td>1000</td>
                                        </tr>
                                    </table>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-upload"></i> Upload & Proses
                                </button>
                                <a href="insentif_kurir_list.php" class="btn btn-default">
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
