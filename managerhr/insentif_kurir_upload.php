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
            </div>

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
                                    <strong>Format Excel (struktur baru - Data Cuti & Lembur Otomatis dari Database):</strong><br>
                                    <ul>
                                        <li>Kolom A: npp (NPP Karyawan)</li>
                                        <li>Kolom B: tanggal_absen (Format: MM/DD/YYYY atau YYYY-MM-DD atau Excel date)</li>
                                        <li>Kolom C: jam_absen (bisa berisi "jam_masuk jam_pulang" seperti "08:49 16:02" atau hanya jam masuk; bisa juga Excel time)</li>
                                        <li>Kolom D: jenis_tugas (opsional)</li>
                                        <li>Kolom E: total_aktual_titik (angka)</li>
                                        <li>Kolom F: target_titik (angka)</li>
                                    </ul>
                                    <div class="alert alert-success" style="margin-top: 10px;">
                                        <i class="fa fa-database"></i> <strong>Data cuti dan lembur diambil otomatis dari database!</strong><br>
                                        <small>Sistem akan query tabel <code>cuti</code> dan <code>lembur</code> berdasarkan NPP dan tanggal. Tidak perlu input manual di Excel.</small>
                                    </div>
                                    <strong>Contoh (baris):</strong><br>
                                    <table class="table table-bordered" style="background: white; margin-top: 10px; font-size: 11px;">
                                        <tr>
                                            <th>npp</th>
                                            <th>tanggal_absen</th>
                                            <th>jam_absen</th>
                                            <th>jenis_tugas</th>
                                            <th>total_aktual_titik</th>
                                            <th>target_titik</th>
                                        </tr>
                                        <tr>
                                            <td>22910033</td>
                                            <td>11/26/2025</td>
                                            <td>08:49 16:02</td>
                                            <td>Barang</td>
                                            <td>180</td>
                                            <td>150</td>
                                        </tr>
                                        <tr>
                                            <td>21000025</td>
                                            <td>12/16/2025</td>
                                            <td>08:33 00:00</td>
                                            <td>Tukar Faktur</td>
                                            <td>486</td>
                                            <td>500</td>
                                        </tr>
                                        <tr>
                                            <td>23920047</td>
                                            <td>12/20/2025</td>
                                            <td></td>
                                            <td></td>
                                            <td>0</td>
                                            <td>0</td>
                                        </tr>
                                    </table>
                                    <p class="text-info" style="margin-top: 10px;"><small><strong>Catatan:</strong> Hanya perlu kolom A-F. Data cuti (status & keterangan) dan lembur (jumlah per kategori) akan diquery otomatis dari database saat proses upload.</small></p>
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
<?php include("layout_bottom.php"); ?>
