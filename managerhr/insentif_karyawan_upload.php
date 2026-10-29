<?php
// session check
include('sess_check.php');
$pagedesc = 'Upload Data Insentif Karyawan';
$menuparent = 'insentif';
include('layout_top.php');
?>
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Upload Excel Data Insentif Karyawan</h1>
                </div>
            </div>

            <?php include('layout_alert.php'); ?>
            
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <i class="fa fa-upload"></i> Form Upload Excel Insentif Karyawan (DATA HARIAN)
                        </div>
                        <div class="panel-body">
                            <form method="POST" action="insentif_karyawan_process.php" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label>File Excel</label>
                                    <input type="file" name="excel_file" class="form-control" required accept=".xlsx,.xls">
                                    <p class="help-block">Format file: .xlsx atau .xls</p>
                                </div>
                                
                                <div class="alert alert-info">
                                    <strong>Format Excel (DATA HARIAN - 9 Kolom):</strong><br>
                                    <ul>
                                        <li><strong>Kolom A:</strong> NPP (ID Karyawan)</li>
                                        <li><strong>Kolom B:</strong> Tanggal_Absen (Format: MM/DD/YYYY atau Excel date)</li>
                                        <li><strong>Kolom C:</strong> Jam_Absen (Format: "HH:MM HH:MM" contoh: "08:21 16:27")</li>
                                        <li><strong>Kolom D:</strong> Jenis_Tugas (Operasional/Ambil Barang/Titik Tambahan)</li>
                                        <li><strong>Kolom E:</strong> Total_Aktual_Titik (angka aktual)</li>
                                        <li><strong>Kolom F:</strong> Target_Titik (angka target)</li>
                                        <li><strong>Kolom G:</strong> Lembur_Operasional (angka jam)</li>
                                        <li><strong>Kolom H:</strong> Lembur_Ambil_Barang (angka jam)</li>
                                        <li><strong>Kolom I:</strong> Lembur_Lainnya (angka jam)</li>
                                    </ul>
                                    <p class="help-block" style="margin-top: 10px;"><i class="fa fa-info-circle"></i> <em>Area/Cabang otomatis diambil dari database berdasarkan NPP</em></p>
                                    
                                    <div style="background: #fefae6; padding: 10px; border-left: 4px solid #f39c12; margin: 10px 0;">
                                        <strong>RUMUS PERHITUNGAN:</strong><br>
                                        <ul style="margin: 5px 0;">
                                            <li>Jam Standar Masuk: 09:15 | Jam Standar Keluar: 16:00</li>
                                            <li>Denda Telat: Rp 1.000/menit (jika masuk > 09:15)</li>
                                            <li>Uang Makan: Rp 300.000 (jika Hadir Full)</li>
                                            <li>Insentif Full Masuk: Rp 100.000 (jika Hadir Full)</li>
                                            <li>Insentif Titik Tambahan: (Aktual - Target) × Rp 20.000</li>
                                            <li>Lembur Operasional: Jam × Rp 30.000</li>
                                            <li>Lembur Ambil Barang: Jam × Rp 50.000</li>
                                            <li>Lembur Lainnya: Jam × Rp 30.000</li>
                                        </ul>
                                    </div>

                                    <strong>Contoh Data Excel:</strong><br>
                                    <div style="overflow-x: auto;">
                                    <table class="table table-bordered" style="background: white; margin-top: 10px; font-size: 11px;">
                                        <tr>
                                            <th>NPP</th>
                                            <th>Tanggal</th>
                                            <th>Jam Absen</th>
                                            <th>Jenis Tugas</th>
                                            <th>Aktual</th>
                                            <th>Target</th>
                                            <th>L.Ops</th>
                                            <th>L.AB</th>
                                            <th>L.Lain</th>
                                        </tr>
                                        <tr>
                                            <td>22910033</td>
                                            <td>02/01/2026</td>
                                            <td>08:30 16:30</td>
                                            <td>Operasional</td>
                                            <td>180</td>
                                            <td>150</td>
                                            <td>2</td>
                                            <td>0</td>
                                            <td>0</td>
                                        </tr>
                                        <tr>
                                            <td>22910033</td>
                                            <td>02/02/2026</td>
                                            <td>09:20 17:00</td>
                                            <td>Ambil Barang</td>
                                            <td>120</td>
                                            <td>150</td>
                                            <td>0</td>
                                            <td>1</td>
                                            <td>0</td>
                                        </tr>
                                    </table>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-upload"></i> Upload & Proses
                                </button>
                                <a href="insentif_karyawan_list.php" class="btn btn-default">
                                    <i class="fa fa-list"></i> Lihat Rekap Bulanan
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
<?php include('layout_bottom.php'); ?>
