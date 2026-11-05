<?php
$pagedesc = "Form Perjalanan Dinas";
$menuparent = "perjalanan_dinas";
include("sess_check.php");
include("layout_top.php");
?>
<!-- Select2 Searchable Dropdown CSS & JS -->
<link href="dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Styling Integrasi Select2 dengan Bootstrap 3 */
    .select2-container .select2-selection--single {
        height: 34px !important;
        border: 1px solid #ccc !important;
        border-radius: 4px !important;
        padding: 3px 8px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 26px !important;
        font-size: 13px !important;
        color: #333 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px !important;
        right: 6px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #999 !important;
    }
    .select2-dropdown {
        border: 1px solid #ccc !important;
        border-radius: 4px !important;
        box-shadow: 0 6px 16px rgba(0,0,0,0.15) !important;
        z-index: 9999 !important;
    }
    .select2-search--dropdown {
        padding: 6px 8px !important;
    }
    .select2-search--dropdown .select2-search__field {
        border: 1px solid #ccc !important;
        border-radius: 3px !important;
        padding: 6px 10px !important;
        font-size: 13px !important;
    }
    .select2-results__option {
        padding: 7px 12px !important;
        font-size: 13px !important;
    }
    .select2-results__option--highlighted {
        background-color: #337ab7 !important;
        color: #fff !important;
    }
</style>
<script src="dist/js/select2.min.js"></script>
<script>
if (typeof $.fn.select2 === 'undefined') {
    document.write('<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"><\/script>');
}
</script>
<div id="page-wrapper">
    <?php
    // Get user data from session
    $nama_user = isset($sess_mngname) ? $sess_mngname : '';
    $departemen_user = isset($sess_bagian) ? $sess_bagian : '';
    $npp_user = isset($sess_mngid) ? $sess_mngid : '';

    // If sess_bagian is empty or numeric, try to get nama_bagian by joining employee -> bagian
    if (empty($departemen_user) || is_numeric($departemen_user)) {
        $npp_esc = mysqli_real_escape_string($conn, $npp_user);
        $query_dept = "SELECT COALESCE(b.nama_bagian, e.nama_bagian) AS nama_bagian
                       FROM employee e
                       LEFT JOIN bagian b ON b.id_bagian = e.nama_bagian OR b.nama_bagian = e.nama_bagian
                       WHERE e.npp = '" . $npp_esc . "' LIMIT 1";
        $result_dept = mysqli_query($conn, $query_dept);
        if ($result_dept && mysqli_num_rows($result_dept) > 0) {
            $row_dept = mysqli_fetch_assoc($result_dept);
            $departemen_user = $row_dept['nama_bagian'];
        }
    }

    // Ambil daftar seluruh karyawan aktif selain user yang sedang login untuk dropdown rekan
    $emp_query = "SELECT e.npp, e.nama_emp, e.jabatan, COALESCE(b.nama_bagian, e.nama_bagian) AS nama_bagian 
                  FROM employee e 
                  LEFT JOIN bagian b ON b.id_bagian = e.nama_bagian OR b.nama_bagian = e.nama_bagian
                  WHERE e.aktif = 'Aktif' AND e.npp != '" . mysqli_real_escape_string($conn, $npp_user) . "'
                  ORDER BY e.nama_emp ASC";
    $emp_res = mysqli_query($conn, $emp_query);
    $all_employees = [];
    while ($row_e = mysqli_fetch_assoc($emp_res)) {
        $all_employees[] = $row_e;
    }
    ?>

    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Form Perjalanan Dinas</h1>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-success alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="fa fa-check"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-danger alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="alert alert-info">
                <h4 style="cursor:pointer;" data-toggle="collapse" data-target="#syaratKetentuanDinas" aria-expanded="true" aria-controls="syaratKetentuanDinas">
                    <i class="fa fa-info-circle"></i> <strong>Syarat dan Ketentuan Perjalanan Dinas (Upcountry)</strong>
                    <i class="fa fa-chevron-up pull-right"></i>
                </h4>
                <div id="syaratKetentuanDinas" class="collapse in">
                    <ol style="margin-left: -20px; margin-bottom: 0;">
                        <li>Perjalanan dinas dilakukan berdasarkan pengajuan atau penugasan dari atasan yang berwenang.</li>
                        <li>Perjalanan dinas dikategorikan sebagai <em>Upcountry</em> apabila memenuhi salah satu kriteria berikut:
                            <ul>
                                <li>Jarak tempuh minimal 120 km (satu kali jalan) dari lokasi kerja/cabang asal; dan/atau</li>
                                <li>Waktu tempuh minimal 3 (tiga) jam perjalanan satu kali jalan.</li>
                            </ul>
                        </li>
                        <li>Perjalanan dinas dapat dilaksanakan selama 1 (satu) hari atau lebih sesuai kebutuhan operasional.</li>
                        <li>Pengajuan perjalanan dinas diajukan minimal 3 (tiga) hari kerja sebelum keberangkatan. Dalam kondisi mendesak, pengajuan dapat dilakukan atas persetujuan atasan.</li>
                        <li>Karyawan wajib mengajukan Form Perjalanan Dinas melalui HRIS.</li>
                        <li>Biaya yang dapat diajukan meliputi transportasi, akomodasi/penginapan, dan operasional dinas lainnya.</li>
                        <li>Perpanjangan perjalanan dinas harus mendapat persetujuan atasan yang berwenang.</li>
                        <li>Laporan pertanggungjawaban perjalanan dinas wajib disampaikan maksimal 3 (tiga) hari kerja setelah perjalanan dinas selesai dengan disertai bukti yang sah.</li>
                        <li>Perusahaan berhak menolak penggantian biaya yang tidak sesuai ketentuan, tidak didukung bukti yang sah, atau tidak berkaitan dengan kepentingan dinas.</li>
                    </ol>
                </div>
            </div>
            <script>
            $(function(){
                $('[data-target="#syaratKetentuanDinas"]').on('click', function(){
                    $(this).find('.fa-chevron-up, .fa-chevron-down').toggleClass('fa-chevron-up fa-chevron-down');
                });
            });
            </script>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-plane fa-fw"></i> Form Pengajuan Perjalanan Dinas
                </div>
                <div class="panel-body">
                    <form action="submit_perjalanan_dinas.php" method="POST" id="perjalananForm" role="form">
                        <input type="hidden" name="npp" value="<?php echo htmlspecialchars($npp_user); ?>">

                        <!-- BAGIAN DATA PENGAJU UTAMA -->
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label>Nama Pengaju Utama (PIC) <span class="text-danger">*</span></label>
                                    <input type="text" name="nama" class="form-control"
                                        value="<?php echo htmlspecialchars($nama_user); ?>" readonly
                                        style="background-color: #f5f5f5; cursor: not-allowed;">
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label>Departemen <span class="text-danger">*</span></label>
                                    <input type="text" name="departemen" class="form-control"
                                        value="<?php echo htmlspecialchars($departemen_user); ?>" readonly
                                        style="background-color: #f5f5f5; cursor: not-allowed;">
                                </div>
                            </div>
                        </div>

                        <!-- BAGIAN MULTI-KARYAWAN / REKAN PERJALANAN DINAS -->
                        <div class="panel panel-info" style="margin-top: 10px; border-color: #bce8f1;">
                            <div class="panel-heading" style="background-color: #d9edf7; color: #31708f; font-weight: bold;">
                                <i class="fa fa-users"></i> Rekan Perjalanan Dinas (Rombongan / Multi-Karyawan)
                            </div>
                            <div class="panel-body">
                                <p class="text-muted" style="font-size: 13px; margin-bottom: 12px;">
                                    Jika Anda melakukan perjalanan dinas bersama rekan lain, tambahkan rekan Anda di bawah ini. Tabel rincian biaya untuk rekan akan otomatis muncul dan pengajuan ini akan <strong>otomatis masuk ke menu riwayat (history) rekan yang dipilih</strong>.
                                </p>

                                <div id="rekanContainer">
                                    <!-- Baris rekan akan ditambahkan lewat JavaScript di sini -->
                                </div>

                                <button type="button" id="btnAddRekan" class="btn btn-default btn-sm">
                                    <i class="fa fa-user-plus text-primary"></i> Tambah Rekan Karyawan
                                </button>
                            </div>
                        </div>

                        <!-- BAGIAN JADWAL & TUJUAN PERJALANAN -->
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6">
                                <div class="form-group">
                                    <label>Tanggal Perjalanan</label>
                                    <input type="date" name="tanggal_perjalanan" class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6">
                                <div class="form-group">
                                    <label>Jumlah Hari <span class="text-danger">*</span></label>
                                    <input type="number" name="jumlah_hari" id="jumlahHariInput" class="form-control" min="1"
                                        placeholder="Jumlah hari perjalanan" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6">
                                <div class="form-group">
                                    <label>Kota Asal</label>
                                    <input type="text" name="kota_asal" class="form-control"
                                        placeholder="Kota keberangkatan">
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6">
                                <div class="form-group">
                                    <label>Kota Tujuan <span class="text-danger">*</span></label>
                                    <input type="text" name="kota_tujuan" class="form-control"
                                        placeholder="Kota tujuan perjalanan" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>Tujuan Perjalanan Bisnis <span class="text-danger">*</span></label>
                                    <textarea name="tujuan" class="form-control" rows="3"
                                        placeholder="Tujuan singkat dan alasan perjalanan (mis. Kunjungan pelanggan, training, dinas lapangan)"
                                        required></textarea>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- PANDUAN PENGISIAN RINCIAN BIAYA -->
                        <div class="row">
                            <div class="col-lg-12">
                                <h4><i class="fa fa-money"></i> Rincian Perkiraan Biaya Perjalanan Dinas</h4>
                                <div class="alert alert-info" role="alert" style="margin-top:8px;">
                                    <strong><i class="fa fa-info-circle"></i> Petunjuk Pengisian:</strong>
                                    <ul style="margin-top:5px; margin-bottom:5px; padding-left:20px;">
                                        <li>Tabel sudah otomatis disiapkan dengan <strong>3 kategori biaya standar</strong> (Tiket, Hotel, dan Transportasi Lokal).</li>
                                        <li>Silakan masukkan estimasi <strong>Perkiraan (Rp)</strong> dan <strong>Qty</strong>. Hapus baris jika item tersebut tidak dibutuhkan pada perjalanan dinas ini.</li>
                                        <li>Klik tombol panah dropdown pada kolom keterangan untuk memilih kategori cepat, atau Anda dapat mengetik keterangan secara bebas.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <style>
                            /* Mencegah pemotongan dropdown menu di dalam tabel agar bisa mengambang bebas */
                            .table-responsive {
                                overflow: visible !important;
                            }
                            @media (max-width: 767px) {
                                .table-responsive {
                                    overflow-x: auto !important;
                                }
                            }
                            .table-personal {
                                margin-bottom: 0 !important;
                            }
                            .table-personal th {
                                vertical-align: middle !important;
                                font-size: 12px;
                                padding: 9px 8px !important;
                            }
                            .table-personal td {
                                vertical-align: middle !important;
                                padding: 8px !important;
                            }
                            .table-personal .form-control {
                                height: 34px !important;
                                font-size: 13px !important;
                                border-radius: 3px !important;
                            }
                            .table-personal .qty-input {
                                text-align: center !important;
                                font-weight: 600 !important;
                            }
                            .table-personal .perkiraan-input {
                                text-align: right !important;
                                font-weight: 600 !important;
                                font-family: 'Consolas', 'Courier New', monospace !important;
                                font-size: 13px !important;
                            }
                            .table-personal .subtotal-text {
                                font-weight: 700 !important;
                                font-size: 13px !important;
                                color: #2e6da4 !important;
                                font-family: 'Consolas', 'Courier New', monospace !important;
                            }
                            /* Baris yang membuka dropdown diberi z-index tertinggi agar mengambang di atas baris lain */
                            .table-personal tr.row-dropdown-open {
                                position: relative !important;
                                z-index: 1050 !important;
                            }
                            .table-personal .input-group-btn.open {
                                position: relative !important;
                                z-index: 1060 !important;
                            }
                            /* Tampilan Dropdown Kategori Mengambang (Floating Card) */
                            .table-personal .kategori-menu {
                                min-width: 340px !important;
                                padding: 6px 0 !important;
                                margin-top: 4px !important;
                                border: 1px solid #d2d6de !important;
                                border-radius: 6px !important;
                                background-color: #ffffff !important;
                                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.18), 0 4px 10px rgba(0, 0, 0, 0.08) !important;
                                z-index: 1070 !important;
                            }
                            /* Saat ruang di bawah sempit, menu mengambang ke atas (dropup) */
                            .table-personal .input-group-btn.dropup .kategori-menu {
                                margin-bottom: 4px !important;
                                margin-top: 0 !important;
                                top: auto !important;
                                bottom: 100% !important;
                                box-shadow: 0 -10px 25px rgba(0, 0, 0, 0.18), 0 -4px 10px rgba(0, 0, 0, 0.08) !important;
                            }
                            .kategori-menu > li > a {
                                padding: 8px 16px !important;
                                font-size: 13px !important;
                                font-weight: normal !important;
                                color: #333 !important;
                            }
                            .kategori-menu > li > a:hover {
                                background-color: #eaf2f8 !important;
                                color: #1b5280 !important;
                            }
                            .kategori-menu .dropdown-header {
                                font-size: 11px !important;
                                font-weight: bold !important;
                                letter-spacing: 0.5px !important;
                                color: #777 !important;
                                padding: 6px 16px !important;
                                background: #fcfcfc !important;
                                border-bottom: 1px solid #f0f0f0 !important;
                                margin-bottom: 4px !important;
                            }
                        </style>

                        <!-- TABEL BIAYA PERSONAL PENGAJU UTAMA -->
                        <div class="panel panel-default" style="border-left: 4px solid #337ab7; margin-top: 15px;">
                            <div class="panel-heading" style="background:#eaf2f8;">
                                <strong><i class="fa fa-user text-primary"></i> Rincian Biaya: <?php echo htmlspecialchars($nama_user); ?> (Pengaju Utama)</strong>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-personal" id="tablePersonalUtama" data-npp="<?php echo htmlspecialchars($npp_user); ?>">
                                        <thead>
                                            <tr style="background-color: #337ab7; color: white;">
                                                <th style="width:40px" class="text-center">#</th>
                                                <th>Keterangan Biaya</th>
                                                <th style="width:85px" class="text-center">Qty</th>
                                                <th style="width:170px" class="text-center">Perkiraan (Rp)</th>
                                                <th style="width:160px" class="text-center">Subtotal (Rp)</th>
                                                <th style="width:55px" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="nomor text-center">1</td>
                                                <td>
                                                    <div class="input-group">
                                                        <input type="text" name="personal_ket[<?php echo htmlspecialchars($npp_user); ?>][]" class="form-control ket-input" value="Tiket Transportasi (Pesawat / Kereta / Travel)" placeholder="Ketik keterangan atau pilih kategori..." required>
                                                        <div class="input-group-btn">
                                                            <button type="button" class="btn btn-default dropdown-toggle btn-kategori" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Pilih kategori cepat">
                                                                <span class="caret"></span>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-right kategori-menu">
                                                                <li class="dropdown-header" style="font-size:11px; font-weight:bold; color:#777;">PILIH KATEGORI BIAYA:</li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Tiket Transportasi (Pesawat / Kereta / Travel)">Tiket Transportasi (Pesawat / KA / Travel)</a></li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Hotel / Penginapan">Hotel / Penginapan</a></li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Transportasi Lokal (Taksi Online / Grab / Sewa)">Transportasi Lokal (Taksi Online / Grab / Sewa)</a></li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Bensin & Tol">Bensin & Tol</a></li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Parkir & Retribusi">Parkir & Retribusi</a></li>
                                                                <li role="separator" class="divider"></li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Biaya Operasional / Tak Terduga">Biaya Operasional / Tak Terduga</a></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><input type="number" name="personal_qty[<?php echo htmlspecialchars($npp_user); ?>][]" class="form-control qty-input" value="1" min="1" required></td>
                                                <td>
                                                    <div class="input-group">
                                                        <span class="input-group-addon" style="font-size:11px; padding:6px 8px; font-weight:bold; color:#555;">Rp</span>
                                                        <input type="text" name="personal_perkiraan[<?php echo htmlspecialchars($npp_user); ?>][]" class="form-control currency-input perkiraan-input" value="0" required>
                                                    </div>
                                                </td>
                                                <td class="text-right" style="vertical-align: middle;"><span class="subtotal-text">Rp 0</span></td>
                                                <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-personal-row" title="Hapus baris ini jika tidak dibutuhkan"><i class="fa fa-trash"></i></button></td>
                                            </tr>
                                            <tr>
                                                <td class="nomor text-center">2</td>
                                                <td>
                                                    <div class="input-group">
                                                        <input type="text" name="personal_ket[<?php echo htmlspecialchars($npp_user); ?>][]" class="form-control ket-input" value="Hotel / Penginapan" placeholder="Ketik keterangan atau pilih kategori..." required>
                                                        <div class="input-group-btn">
                                                            <button type="button" class="btn btn-default dropdown-toggle btn-kategori" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Pilih kategori cepat">
                                                                <span class="caret"></span>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-right kategori-menu">
                                                                <li class="dropdown-header" style="font-size:11px; font-weight:bold; color:#777;">PILIH KATEGORI BIAYA:</li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Tiket Transportasi (Pesawat / Kereta / Travel)">Tiket Transportasi (Pesawat / KA / Travel)</a></li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Hotel / Penginapan">Hotel / Penginapan</a></li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Transportasi Lokal (Taksi Online / Grab / Sewa)">Transportasi Lokal (Taksi Online / Grab / Sewa)</a></li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Bensin & Tol">Bensin & Tol</a></li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Parkir & Retribusi">Parkir & Retribusi</a></li>
                                                                <li role="separator" class="divider"></li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Biaya Operasional / Tak Terduga">Biaya Operasional / Tak Terduga</a></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><input type="number" name="personal_qty[<?php echo htmlspecialchars($npp_user); ?>][]" class="form-control qty-input" value="1" min="1" required></td>
                                                <td>
                                                    <div class="input-group">
                                                        <span class="input-group-addon" style="font-size:11px; padding:6px 8px; font-weight:bold; color:#555;">Rp</span>
                                                        <input type="text" name="personal_perkiraan[<?php echo htmlspecialchars($npp_user); ?>][]" class="form-control currency-input perkiraan-input" value="0" required>
                                                    </div>
                                                </td>
                                                <td class="text-right" style="vertical-align: middle;"><span class="subtotal-text">Rp 0</span></td>
                                                <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-personal-row" title="Hapus baris ini jika tidak dibutuhkan"><i class="fa fa-trash"></i></button></td>
                                            </tr>
                                            <tr>
                                                <td class="nomor text-center">3</td>
                                                <td>
                                                    <div class="input-group">
                                                        <input type="text" name="personal_ket[<?php echo htmlspecialchars($npp_user); ?>][]" class="form-control ket-input" value="Transportasi Lokal (Taksi Online / Bensin & Tol)" placeholder="Ketik keterangan atau pilih kategori..." required>
                                                        <div class="input-group-btn">
                                                            <button type="button" class="btn btn-default dropdown-toggle btn-kategori" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Pilih kategori cepat">
                                                                <span class="caret"></span>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-right kategori-menu">
                                                                <li class="dropdown-header" style="font-size:11px; font-weight:bold; color:#777;">PILIH KATEGORI BIAYA:</li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Tiket Transportasi (Pesawat / Kereta / Travel)">Tiket Transportasi (Pesawat / KA / Travel)</a></li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Hotel / Penginapan">Hotel / Penginapan</a></li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Transportasi Lokal (Taksi Online / Grab / Sewa)">Transportasi Lokal (Taksi Online / Grab / Sewa)</a></li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Bensin & Tol">Bensin & Tol</a></li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Parkir & Retribusi">Parkir & Retribusi</a></li>
                                                                <li role="separator" class="divider"></li>
                                                                <li><a href="javascript:void(0)" class="opt-kategori" data-val="Biaya Operasional / Tak Terduga">Biaya Operasional / Tak Terduga</a></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><input type="number" name="personal_qty[<?php echo htmlspecialchars($npp_user); ?>][]" class="form-control qty-input" value="1" min="1" required></td>
                                                <td>
                                                    <div class="input-group">
                                                        <span class="input-group-addon" style="font-size:11px; padding:6px 8px; font-weight:bold; color:#555;">Rp</span>
                                                        <input type="text" name="personal_perkiraan[<?php echo htmlspecialchars($npp_user); ?>][]" class="form-control currency-input perkiraan-input" value="0" required>
                                                    </div>
                                                </td>
                                                <td class="text-right" style="vertical-align: middle;"><span class="subtotal-text">Rp 0</span></td>
                                                <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-personal-row" title="Hapus baris ini jika tidak dibutuhkan"><i class="fa fa-trash"></i></button></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm btn-add-personal-row" data-npp="<?php echo htmlspecialchars($npp_user); ?>">
                                    <i class="fa fa-plus"></i> Tambah Baris Biaya <?php echo htmlspecialchars($nama_user); ?>
                                </button>
                            </div>
                        </div>

                        <!-- TABEL BIAYA PERSONAL REKAN (DINAMIS DITAMBAHKAN VIA JS) -->
                        <div id="personalRekanContainer">
                            <!-- Tabel rincian per rekan akan muncul otomatis di sini saat rekan dipilih -->
                        </div>

                        <!-- KOTAK SUMMARY GRAND TOTAL -->
                        <div class="row" style="margin-top: 25px;">
                            <div class="col-md-4 col-sm-12" style="margin-bottom: 10px;">
                                <div style="background: #f4f8fb; border: 1px solid #d9edf7; border-left: 4px solid #337ab7; padding: 12px 15px; border-radius: 4px;">
                                    <div style="font-size: 11px; text-transform: uppercase; color: #555; font-weight: bold; letter-spacing: 0.5px;">
                                        Total Pengaju (<?php echo htmlspecialchars($nama_user); ?>)
                                    </div>
                                    <div id="displayTotalUtama" style="font-size: 20px; font-weight: bold; color: #337ab7; margin-top: 4px;">
                                        Rp 0
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-12" style="margin-bottom: 10px;">
                                <div style="background: #f4fbfd; border: 1px solid #bce8f1; border-left: 4px solid #5bc0de; padding: 12px 15px; border-radius: 4px;">
                                    <div style="font-size: 11px; text-transform: uppercase; color: #555; font-weight: bold; letter-spacing: 0.5px;">
                                        Total Rekan Peserta
                                    </div>
                                    <div id="displayTotalRekan" style="font-size: 20px; font-weight: bold; color: #31708f; margin-top: 4px;">
                                        Rp 0
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-12" style="margin-bottom: 10px;">
                                <div style="background: #f4faf4; border: 1px solid #d6e9c6; border-left: 4px solid #5cb85c; padding: 12px 15px; border-radius: 4px;">
                                    <div style="font-size: 11px; text-transform: uppercase; color: #555; font-weight: bold; letter-spacing: 0.5px;">
                                        Grand Total Estimasi
                                    </div>
                                    <div id="displayGrandTotal" style="font-size: 22px; font-weight: bold; color: #3c763d; margin-top: 4px;">
                                        Rp 0
                                    </div>
                                    <input type="hidden" name="budget_total" id="inputGrandTotal" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="row" style="margin-top: 20px;">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fa fa-send"></i> Ajukan Perjalanan Dinas
                                    </button>
                                    <a href="perjalanan_dinas_list.php" class="btn btn-default btn-lg">
                                        <i class="fa fa-times"></i> Batal
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div><!-- /.panel-body -->
            </div><!-- /.panel -->
        </div><!-- /.col-lg-12 -->
    </div><!-- /.row -->

    <!-- DATA KARYAWAN UNTUK JAVASCRIPT -->
    <script>
    var employeesList = <?php echo json_encode($all_employees); ?>;
    var nppUtama = "<?php echo htmlspecialchars($npp_user); ?>";
    var namaUtama = "<?php echo htmlspecialchars($nama_user); ?>";

    $(document).ready(function () {
        // Template Generator Baris Biaya
        function getRowHtml(npp, nomor, defaultVal) {
            defaultVal = defaultVal || '';
            return `
                <tr>
                    <td class="nomor text-center" style="vertical-align: middle;">${nomor}</td>
                    <td>
                        <div class="input-group">
                            <input type="text" name="personal_ket[${npp}][]" class="form-control ket-input" value="${defaultVal}" placeholder="Ketik keterangan atau pilih kategori..." required>
                            <div class="input-group-btn">
                                <button type="button" class="btn btn-default dropdown-toggle btn-kategori" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Pilih kategori cepat">
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right kategori-menu">
                                    <li class="dropdown-header" style="font-size:11px; font-weight:bold; color:#777;">PILIH KATEGORI BIAYA:</li>
                                    <li><a href="javascript:void(0)" class="opt-kategori" data-val="Tiket Transportasi (Pesawat / Kereta / Travel)">Tiket Transportasi (Pesawat / KA / Travel)</a></li>
                                    <li><a href="javascript:void(0)" class="opt-kategori" data-val="Hotel / Penginapan">Hotel / Penginapan</a></li>
                                    <li><a href="javascript:void(0)" class="opt-kategori" data-val="Transportasi Lokal (Taksi Online / Grab / Sewa)">Transportasi Lokal (Taksi Online / Grab / Sewa)</a></li>
                                    <li><a href="javascript:void(0)" class="opt-kategori" data-val="Bensin & Tol">Bensin & Tol</a></li>
                                    <li><a href="javascript:void(0)" class="opt-kategori" data-val="Parkir & Retribusi">Parkir & Retribusi</a></li>
                                    <li role="separator" class="divider"></li>
                                    <li><a href="javascript:void(0)" class="opt-kategori" data-val="Biaya Operasional / Tak Terduga">Biaya Operasional / Tak Terduga</a></li>
                                </ul>
                            </div>
                        </div>
                    </td>
                    <td><input type="number" name="personal_qty[${npp}][]" class="form-control qty-input" value="1" min="1" required></td>
                    <td>
                        <div class="input-group">
                            <span class="input-group-addon" style="font-size:11px; padding:6px 8px; font-weight:bold; color:#555;">Rp</span>
                            <input type="text" name="personal_perkiraan[${npp}][]" class="form-control currency-input perkiraan-input" value="0" required>
                        </div>
                    </td>
                    <td class="text-right" style="vertical-align: middle;"><span class="subtotal-text">Rp 0</span></td>
                    <td class="text-center" style="vertical-align: middle;"><button type="button" class="btn btn-sm btn-danger remove-personal-row" title="Hapus baris ini jika tidak dibutuhkan"><i class="fa fa-trash"></i></button></td>
                </tr>
            `;
        }

        $(document).on('click', '.opt-kategori', function (e) {
            e.preventDefault();
            var val = $(this).data('val') || $(this).text().trim();
            var $input = $(this).closest('.input-group').find('.ket-input');
            $input.val(val).focus();
        });

        // Penanganan Dropdown Kategori Mengambang (Floating & Smart Dropup)
        $(document).on('show.bs.dropdown', '.table-personal .input-group-btn', function () {
            var $btnGroup = $(this);
            var $tr = $btnGroup.closest('tr');

            // Reset z-index semua row dan beri prioritas z-index tertinggi pada baris aktif
            $('.table-personal tr').removeClass('row-dropdown-open').css('z-index', '');
            $tr.addClass('row-dropdown-open').css({
                'position': 'relative',
                'z-index': 1050
            });

            // Cek apakah posisi tombol dekat batas bawah layar agar mengambang ke atas (dropup)
            var btnTop = $btnGroup.offset().top - $(window).scrollTop();
            var windowHeight = $(window).height();
            var menuHeight = 270;

            if ((windowHeight - btnTop) < menuHeight && btnTop > menuHeight) {
                $btnGroup.addClass('dropup');
            } else {
                $btnGroup.removeClass('dropup');
            }
        });

        $(document).on('hide.bs.dropdown', '.table-personal .input-group-btn', function () {
            var $tr = $(this).closest('tr');
            $tr.removeClass('row-dropdown-open').css({
                'position': '',
                'z-index': ''
            });
            $(this).removeClass('dropup');
        });

        // Format angka ke format Rupiah dengan pemisah titik ribuan (mis. 1.500.000)
        function formatNumber(angka) {
            if (angka === undefined || angka === null || angka === '') return '0';
            var cleanStr = angka.toString().replace(/[^0-9]/g, '');
            if (!cleanStr) return '0';
            var num = parseInt(cleanStr, 10);
            if (isNaN(num)) return '0';
            if (typeof Intl !== 'undefined' && Intl.NumberFormat) {
                return new Intl.NumberFormat('id-ID').format(num);
            }
            var sisa = cleanStr.length % 3;
            var rupiah = cleanStr.substr(0, sisa);
            var ribuan = cleanStr.substr(sisa).match(/\d{3}/g);
            if (ribuan) {
                var separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }
            return rupiah || '0';
        }

        function cleanNumber(str) {
            if (!str) return 0;
            var num = parseInt(str.toString().replace(/[^0-9]/g, ''), 10) || 0;
            return num;
        }

        $(document).on('focus', '.perkiraan-input', function () {
            if ($(this).val() === '0') {
                $(this).val('');
            }
        });

        $(document).on('input keyup', '.currency-input', function () {
            var val = $(this).val();
            if (val === '') {
                calculateAllTotals();
                return;
            }
            $(this).val(formatNumber(val));
            calculateAllTotals();
        });

        $(document).on('blur', '.perkiraan-input', function () {
            var val = $(this).val().trim();
            if (val === '' || val === '0') {
                $(this).val('0');
            } else {
                $(this).val(formatNumber(val));
            }
            calculateAllTotals();
        });

        function calculateAllTotals() {
            var totalUtama = 0;
            var totalRekan = 0;

            // 1. Biaya Personal Pengaju Utama
            $('#tablePersonalUtama tbody tr').each(function () {
                var qty = parseInt($(this).find('.qty-input').val()) || 0;
                var perkiraan = cleanNumber($(this).find('.perkiraan-input').val());
                var subtotal = qty * perkiraan;
                $(this).find('.subtotal-text').text('Rp ' + formatNumber(subtotal));
                totalUtama += subtotal;
            });

            // 2. Biaya Personal Rekan
            $('#personalRekanContainer .table-personal tbody tr').each(function () {
                var qty = parseInt($(this).find('.qty-input').val()) || 0;
                var perkiraan = cleanNumber($(this).find('.perkiraan-input').val());
                var subtotal = qty * perkiraan;
                $(this).find('.subtotal-text').text('Rp ' + formatNumber(subtotal));
                totalRekan += subtotal;
            });

            var grandTotal = totalUtama + totalRekan;

            $('#displayTotalUtama').text('Rp ' + formatNumber(totalUtama));
            $('#displayTotalRekan').text('Rp ' + formatNumber(totalRekan));
            $('#displayGrandTotal').text('Rp ' + formatNumber(grandTotal));
            $('#inputGrandTotal').val(grandTotal);
        }

        function updateTableNumbers(tableElem) {
            $(tableElem).find('tbody tr').each(function (index) {
                $(this).find('.nomor').text(index + 1);
            });
        }

        $(document).on('input change keyup', '.qty-input', function () {
            calculateAllTotals();
        });

        // ==========================================
        // FITUR TAMBAH / HAPUS REKAN KARYAWAN
        // ==========================================
        function buildEmployeeOptions(selectedNpp) {
            var html = '<option value="">-- Pilih Rekan Karyawan --</option>';
            $.each(employeesList, function (i, emp) {
                var isSel = (emp.npp === selectedNpp) ? 'selected' : '';
                var deptText = emp.nama_bagian ? ' (' + emp.nama_bagian + ')' : '';
                var jabText = emp.jabatan ? ' - ' + emp.jabatan : '';
                html += `<option value="${emp.npp}" data-nama="${emp.nama_emp}" data-dept="${emp.nama_bagian || ''}" ${isSel}>${emp.nama_emp} - ${emp.npp}${jabText}${deptText}</option>`;
            });
            return html;
        }

        // Inisialisasi Searchable Dropdown Select2
        function initSelect2(element) {
            if (typeof $.fn.select2 !== 'undefined') {
                $(element).select2({
                    placeholder: '-- Cari nama / NPP rekan karyawan --',
                    allowClear: true,
                    width: '100%'
                });
            }
        }

        $('#btnAddRekan').on('click', function () {
            var optionsHtml = buildEmployeeOptions('');
            var $row = $(`
                <div class="row rekan-row" style="margin-bottom: 10px; align-items: center;">
                    <div class="col-sm-5 col-xs-12" style="margin-bottom: 5px;">
                        <select name="rekan_npp[]" class="form-control select-rekan" required>
                            ${optionsHtml}
                        </select>
                        <input type="hidden" name="rekan_nama[]" class="hidden-rekan-nama">
                        <input type="hidden" name="rekan_dept[]" class="hidden-rekan-dept">
                    </div>
                    <div class="col-sm-4 col-xs-8" style="margin-bottom: 5px;">
                        <input type="text" class="form-control input-sm preview-rekan-dept" placeholder="Departemen" readonly style="background-color:#f9f9f9; height:34px;">
                    </div>
                    <div class="col-sm-3 col-xs-4" style="margin-bottom: 5px;">
                        <button type="button" class="btn btn-danger btn-sm btn-remove-rekan btn-block" style="height:34px;">
                            <i class="fa fa-trash"></i> Hapus
                        </button>
                    </div>
                </div>
            `);
            $('#rekanContainer').append($row);
            var $select = $row.find('.select-rekan');
            initSelect2($select);
            setTimeout(function () {
                $select.select2('open');
            }, 100);
        });

        $(document).on('change', '.select-rekan', function () {
            var $row = $(this).closest('.rekan-row');
            var selectedNpp = $(this).val();
            var prevNpp = $(this).data('prev-npp') || '';

            if (!selectedNpp || selectedNpp === '') {
                $row.find('.hidden-rekan-nama').val('');
                $row.find('.hidden-rekan-dept').val('');
                $row.find('.preview-rekan-dept').val('');
                if (prevNpp) {
                    $('#personalCard_' + prevNpp).remove();
                    $(this).data('prev-npp', '');
                    calculateAllTotals();
                }
                return;
            }

            // Validasi duplikat rekan
            var duplicateCount = 0;
            $('.select-rekan').each(function () {
                if ($(this).val() === selectedNpp) duplicateCount++;
            });

            if (duplicateCount > 1) {
                alert('Karyawan ini sudah ditambahkan sebelumnya!');
                $(this).val(prevNpp || '');
                if (typeof $.fn.select2 !== 'undefined') {
                    $(this).trigger('change.select2');
                }
                return;
            }

            var $selectedOpt = $(this).find('option:selected');
            var nama = $selectedOpt.data('nama');
            var dept = $selectedOpt.data('dept');

            $row.find('.hidden-rekan-nama').val(nama);
            $row.find('.hidden-rekan-dept').val(dept);
            $row.find('.preview-rekan-dept').val(dept || '-');

            if (prevNpp && prevNpp !== selectedNpp) {
                $('#personalCard_' + prevNpp).remove();
            }

            $(this).data('prev-npp', selectedNpp);

            // Munculkan tabel rincian biaya khusus untuk rekan ini
            if ($('#personalCard_' + selectedNpp).length === 0) {
                var cardHtml = `
                    <div class="panel panel-default personal-card" id="personalCard_${selectedNpp}" style="border-left: 4px solid #5bc0de; margin-top: 20px;">
                        <div class="panel-heading" style="background:#f4f9fc;">
                            <strong><i class="fa fa-user text-info"></i> Rincian Biaya: ${nama} (Rekan Dinas - ${selectedNpp})</strong>
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-personal" data-npp="${selectedNpp}">
                                    <thead>
                                        <tr style="background-color: #5bc0de; color: white;">
                                            <th style="width:40px" class="text-center">#</th>
                                            <th>Keterangan Biaya</th>
                                            <th style="width:85px" class="text-center">Qty</th>
                                            <th style="width:170px" class="text-center">Perkiraan (Rp)</th>
                                            <th style="width:160px" class="text-center">Subtotal (Rp)</th>
                                            <th style="width:55px" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${getRowHtml(selectedNpp, 1, 'Tiket Transportasi (Pesawat / Kereta / Travel)')}
                                        ${getRowHtml(selectedNpp, 2, 'Hotel / Penginapan')}
                                        ${getRowHtml(selectedNpp, 3, 'Transportasi Lokal (Taksi Online / Grab / Sewa)')}
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-info btn-sm btn-add-personal-row" data-npp="${selectedNpp}">
                                <i class="fa fa-plus"></i> Tambah Baris Biaya ${nama}
                            </button>
                        </div>
                    </div>
                `;
                $('#personalRekanContainer').append(cardHtml);
            }

            calculateAllTotals();
        });

        $(document).on('click', '.btn-remove-rekan', function () {
            var $row = $(this).closest('.rekan-row');
            var $select = $row.find('.select-rekan');
            var npp = $select.val();
            if ($select.data('select2')) {
                $select.select2('destroy');
            }
            if (npp) {
                $('#personalCard_' + npp).remove();
            }
            $row.remove();
            calculateAllTotals();
        });

        // ==========================================
        // TAMBAH / HAPUS BARIS BIAYA PER KARYAWAN
        // ==========================================
        $(document).on('click', '.btn-add-personal-row', function () {
            var npp = $(this).data('npp');
            var table = $(this).closest('.panel-body').find('table.table-personal');
            var tbody = table.find('tbody');
            var rowCount = tbody.find('tr').length + 1;
            tbody.append(getRowHtml(npp, rowCount, ''));
            updateTableNumbers(table);
            calculateAllTotals();
        });

        $(document).on('click', '.remove-personal-row', function () {
            var table = $(this).closest('table.table-personal');
            var tbody = table.find('tbody');
            if (tbody.find('tr').length > 1) {
                $(this).closest('tr').remove();
                updateTableNumbers(table);
                calculateAllTotals();
            } else {
                alert('Tabel biaya minimal memiliki 1 baris.');
            }
        });

        // Validasi Submit
        $('#perjalananForm').on('submit', function (e) {
            calculateAllTotals();
            var total = cleanNumber($('#inputGrandTotal').val());
            if (total <= 0) {
                e.preventDefault();
                alert('Total perkiraan biaya perjalanan dinas belum diisi atau masih Rp 0.');
                return false;
            }

            var confirmation = confirm('Anda akan mengajukan perjalanan dinas dengan total estimasi biaya Rp ' + formatNumber(total) + '.\nPengajuan akan langsung dikirim untuk persetujuan. Lanjutkan?');
            if (!confirmation) {
                e.preventDefault();
                return false;
            }
        });

        calculateAllTotals();
    });
    </script>
</div>
<?php include("layout_bottom.php"); ?>
