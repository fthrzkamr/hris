<?php

// session check
include("sess_check.php");
$pagedesc = "Daftar Insentif Kurir";
$menuparent = "insentif";
include("layout_top.php");

// Get filter parameters
$filter_periode = isset($_GET['periode']) ? $_GET['periode'] : '';
$filter_npp = isset($_GET['npp']) ? $_GET['npp'] : '';

// Pagination settings
$records_per_page = 50; // Increased for better DataTables performance
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Build query: select records from transaksi_insentif_kurir and join employee for metadata
// Fetch lembur rates from settings so list can compute lembur amounts directly from `lembur` table
$rs_lembur = mysqli_query($conn, "SELECT nama_variabel, nominal_rp FROM pengaturan_insentif_kurir WHERE kategori='LEMBUR' AND is_active=1");
$rates = array('RATE_LEMBUR_OPERASIONAL' => 0, 'RATE_LEMBUR_AMBIL_BARANG' => 0, 'RATE_LEMBUR_LAINNYA' => 0);
if ($rs_lembur) {
    while ($rle = mysqli_fetch_assoc($rs_lembur)) {
        $rates[$rle['nama_variabel']] = floatval($rle['nominal_rp']);
    }
}
$rate_op = floatval($rates['RATE_LEMBUR_OPERASIONAL']);
$rate_ambil = floatval($rates['RATE_LEMBUR_AMBIL_BARANG']);
$rate_lain = floatval($rates['RATE_LEMBUR_LAINNYA']);

// Build base SQL and LEFT JOIN aggregated lembur amounts per npp+periode (Approved only)
$sql_base = "FROM transaksi_insentif_kurir t
        LEFT JOIN employee e ON t.npp = e.npp
        LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
        LEFT JOIN (
            SELECT l.npp, DATE_FORMAT(l.tgl_lembur, '%Y-%m') AS periode,
                SUM(CASE WHEN LOWER(l.tujuan_lembur) LIKE '%operasional%' THEN (l.jumlah * {$rate_op}) ELSE 0 END) AS lembur_operasional_amt,
                SUM(CASE WHEN LOWER(l.tujuan_lembur) LIKE '%ambil%' OR LOWER(l.tujuan_lembur) LIKE '%pickup%' THEN (l.jumlah * {$rate_ambil}) ELSE 0 END) AS lembur_ambil_amt,
                SUM(CASE WHEN LOWER(l.tujuan_lembur) LIKE '%lain%' OR LOWER(l.tujuan_lembur) LIKE '%lainnya%' THEN (l.jumlah * {$rate_lain}) ELSE 0 END) AS lembur_lain_amt
            FROM lembur l
            WHERE l.status = 'Approved'
            GROUP BY l.npp, DATE_FORMAT(l.tgl_lembur, '%Y-%m')
        ) lb ON lb.npp = t.npp AND lb.periode = t.periode
        WHERE 1=1";

$filter_periode_escaped = mysqli_real_escape_string($conn, $filter_periode);
if (!empty($filter_periode_escaped)) {
    $sql_base .= " AND t.periode = '" . $filter_periode_escaped . "'";
}

if (!empty($filter_npp)) {
    $sql_base .= " AND t.npp LIKE '%" . mysqli_real_escape_string($conn, $filter_npp) . "%'";
}

// Count total records
$count_sql = "SELECT COUNT(*) as total " . $sql_base;
$count_result = mysqli_query($conn, $count_sql);
$total_records = 0;
if ($count_result) {
    $count_row = mysqli_fetch_assoc($count_result);
    $total_records = intval($count_row['total']);
}
$total_pages = ceil($total_records / $records_per_page);

// Fetch all records for DataTables client-side processing
$sql = "SELECT t.*, COALESCE(lb.lembur_operasional_amt, t.lembur_operasional) AS lembur_operasional, COALESCE(lb.lembur_ambil_amt, t.lembur_ambil_barang) AS lembur_ambil_barang, COALESCE(lb.lembur_lain_amt, t.lembur_lainnya) AS lembur_lainnya, COALESCE((COALESCE(lb.lembur_operasional_amt,0) + COALESCE(lb.lembur_ambil_amt,0) + COALESCE(lb.lembur_lain_amt,0)), t.uang_lembur) AS uang_lembur, t.hari_hadir, t.hari_telat, t.hari_cuti, e.nama_emp, b.nama_bagian, e.cabang " . $sql_base . " ORDER BY t.periode DESC, t.npp ASC";

$query = mysqli_query($conn, $sql);
// Load titik cap from settings (fallback to 25)
$cap_titik = 25;
$rs_cap = mysqli_query($conn, "SELECT nilai_angka, nominal_rp FROM pengaturan_insentif_kurir WHERE nama_variabel='BATAS_ATAS_BONUS_TITIK' LIMIT 1");
if ($rs_cap && mysqli_num_rows($rs_cap) > 0) {
    $rc = mysqli_fetch_assoc($rs_cap);
    if (!empty($rc['nilai_angka']))
        $cap_titik = intval($rc['nilai_angka']);
    elseif (!empty($rc['nominal_rp']))
        $cap_titik = intval($rc['nominal_rp']);
}
?>
<style>
    /* Styling untuk meningkatkan readability */
    #dataTables tbody tr:hover {
        background-color: #f5f5f5 !important;
    }

    #dataTables thead tr th {
        background-color: #337ab7;
        color: white;
        font-weight: bold;
        vertical-align: middle;
        font-size: 11px;
        padding: 8px 4px;
    }

    .table-bordered>thead>tr>th {
        border-bottom-width: 2px;
    }

    /* Highlight untuk total dibayarkan */
    .bg-success {
        background-color: #5cb85c !important;
        color: white !important;
    }

    /* Responsive table adjustments */
    #dataTables {
        font-size: 12px;
    }

    #dataTables td {
        padding: 6px 4px;
        white-space: nowrap;
    }

    /* DataTables custom styling */
    .dataTables_wrapper .dataTables_length select {
        padding: 4px;
        margin: 0 5px;
    }

    .dataTables_wrapper .dataTables_filter input {
        margin-left: 5px;
        padding: 4px;
    }

    .dataTables_wrapper .dataTables_info {
        padding-top: 8px;
        font-size: 12px;
    }

    .dataTables_wrapper .dataTables_paginate {
        padding-top: 8px;
    }

    /* Mobile responsiveness */
    @media screen and (max-width: 767px) {
        #dataTables {
            font-size: 10px;
        }

        #dataTables thead tr th {
            font-size: 9px;
            padding: 6px 2px;
        }

        #dataTables td {
            padding: 4px 2px;
        }

        .panel-heading {
            font-size: 13px;
        }

        .form-inline .form-group {
            display: block;
            margin-bottom: 10px;
        }

        .form-inline .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-inline .form-control {
            width: 100%;
        }

        .table-responsive {
            border: 0;
            margin-bottom: 15px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    }

    @media screen and (max-width: 480px) {
        h1.page-header {
            font-size: 20px;
        }

        h1.page-header small {
            display: block;
            margin-top: 5px;
        }
    }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                <?php echo $pagedesc; ?>
            </h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>

    <?php
    include("layout_alert.php");

    // Display error/details if any
    if (isset($_SESSION['alert_details'])) {
        echo '<div class="alert alert-warning alert-dismissible">';
        echo '<button type="button" class="close" data-dismiss="alert">&times;</button>';
        echo '<strong>Detail:</strong><br>';
        echo $_SESSION['alert_details'];
        echo '</div>';
        unset($_SESSION['alert_details']);
    }
    ?>

    <div class="row">
        <div class="col-lg-12">
            <!-- Filter Panel -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-filter"></i> Filter Data
                </div>
                <div class="panel-body">
                    <form method="GET" action="" class="form-inline">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label>Periode:</label>
                            <input type="month" name="periode" class="form-control"
                                value="<?php echo htmlspecialchars($filter_periode); ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label>NPP:</label>
                            <input type="text" name="npp" class="form-control" placeholder="Cari NPP..."
                                value="<?php echo htmlspecialchars($filter_npp); ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 10px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> Filter
                            </button>
                            <a href="insentif_kurir_list.php" class="btn btn-default">
                                <i class="fa fa-refresh"></i> Reset
                            </a>
                        </div>
                        <div class="form-group" style="margin-bottom: 10px;">
                            <a href="insentif_kurir_upload.php" class="btn btn-success">
                                <i class="fa fa-upload"></i> Upload Excel
                            </a>
                            <a href="absensi_kurir_list.php" class="btn btn-warning">
                                <i class="fa fa-book"></i> Lihat Buku Harian
                            </a>
                            <?php
                            $export_q = http_build_query(array(
                                'periode' => $filter_periode,
                                'npp' => $filter_npp
                            ));
                            ?>
                            <a href="insentif_kurir_export_xls.php?<?php echo $export_q; ?>" class="btn btn-default">
                                <i class="fa fa-file-excel-o"></i> Export Excel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Legend Panel -->
            <div class="panel panel-info">
                <div class="panel-heading">
                    <i class="fa fa-info-circle"></i> Keterangan Warna
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-3">
                            <span
                                style="display: inline-block; padding: 5px 10px; background-color: #dff0d8; border-radius: 3px; margin-right: 5px;">
                                <strong>Hijau</strong>
                            </span> = Bonus / Kelebihan Titik
                        </div>
                        <div class="col-md-3">
                            <span
                                style="display: inline-block; padding: 5px 10px; background-color: #d9edf7; border-radius: 3px; margin-right: 5px;">
                                <strong>Biru</strong>
                            </span> = Bonus Full Hadir
                        </div>
                        <div class="col-md-3">
                            <span
                                style="display: inline-block; padding: 5px 10px; background-color: #fcf8e3; border-radius: 3px; margin-right: 5px;">
                                <strong>Kuning</strong>
                            </span> = Telat 1-60 menit
                        </div>
                        <div class="col-md-3">
                            <span
                                style="display: inline-block; padding: 5px 10px; background-color: #f2dede; border-radius: 3px; margin-right: 5px; color: #a94442;">
                                <strong>Merah</strong>
                            </span> = Denda / Potongan / Telat >60 mnt
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table Panel -->
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa fa-table"></i> Data Insentif Kurir
                    <span class="pull-right">Total: <?php echo $total_records; ?> data</span>
                </div>
                <!-- /.panel-heading -->
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover" id="dataTables">
                            <thead>
                                <tr>
                                    <th rowspan="2">No</th>
                                    <th rowspan="2">NPP</th>
                                    <th rowspan="2">Nama Karyawan</th>
                                    <th rowspan="2">Bagian</th>
                                    <th rowspan="2">Cabang</th>
                                    <th rowspan="2">Periode</th>
                                    <th colspan="9" class="text-center">Performa</th>
                                    <th colspan="9" class="text-center">Komponen Pembayaran</th>
                                    <th rowspan="2" class="bg-success">Total Dibayarkan</th>
                                    <!-- <th rowspan="2">Tgl Update</th>
                                    <th rowspan="2">Aksi</th> -->
                                </tr>
                                <tr>
                                    <th>Aktual Titik</th>
                                    <th>Target Titik</th>
                                    <th>Kelebihan</th>
                                    <th title="Titik maksimal yang dibayarkan">Titik Max</th>
                                    <th title="Total menit keterlambatan dalam periode">Akumulasi Telat (menit)</th>
                                    <th title="Jumlah hari hadir dalam periode">Hadir</th>
                                    <th title="Jumlah hari telat dalam periode">Telat</th>
                                    <th title="Jumlah hari cuti dalam periode">Cuti</th>
                                    <th title="Jumlah hari sakit dalam periode">Sakit</th>
                                    <th title="Bonus dari kelebihan titik (max 500rb/bulan)">Bonus Titik</th>
                                    <th title="Bonus jika 0 cuti, 0 sakit, dan 0 telat">Bonus Full Hadir</th>
                                    <th>Lembur Operasional</th>
                                    <th>Lembur Ambil Barang</th>
                                    <th>Lembur Lainnya</th>
                                    <th>Total Lembur</th>
                                    <th>Denda Telat</th>
                                    <th title="Potongan absolut dari ketidakhadiran">Potongan Makan</th>
                                    <th title="Uang makan final bulanan setelah potongan">Uang Makan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = $offset + 1;
                                while ($row = mysqli_fetch_assoc($query)) {
                                    $no_titik = intval($row['total_titik']);
                                    $target_titik = intval($row['target_titik']);
                                    $kelebihan_titik = $no_titik - $target_titik;
                                    // Determine status more logically:
                                    // - If no valid target -> N/A
                                    // - If actual >= target -> Tercapai (success)
                                    // - If actual < target but >= 75% of target -> Hampir (warning)
                                    // - Else -> Tidak Tercapai (danger)
                                    // New simplified status logic:
                                    // - If target <= 0 => treated as 'Belum Tercapai'
                                    // - If actual >= target OR actual >= 75% of target => 'Tercapai'
                                    // - Else => 'Belum Tercapai'
                                    if ($target_titik <= 0) {
                                        $status = 'Belum Tercapai';
                                        $status_class = 'danger';
                                        $persentase = 0;
                                    } else {
                                        $persentase = ($target_titik > 0) ? (($no_titik / $target_titik) * 100) : 0;
                                        if ($no_titik >= $target_titik || $persentase >= 75) {
                                            $status = 'Tercapai';
                                            $status_class = 'success';
                                        } else {
                                            $status = 'Belum Tercapai';
                                            $status_class = 'danger';
                                        }
                                    }

                                    // Color coding untuk HRD
                                    $akumulasi_telat = intval($row['akumulasi_telat'] ?? 0);
                                    $telat_style = $akumulasi_telat > 60 ? 'background-color: #f2dede; font-weight: bold;' : ($akumulasi_telat > 0 ? 'background-color: #fcf8e3;' : '');

                                    $bonus_titik = intval($row['bonus_insentif_titik'] ?? 0);
                                    $bonus_titik_style = $bonus_titik > 0 ? 'background-color: #dff0d8; font-weight: bold;' : '';

                                    $bonus_full = intval($row['bonus_insentif_full_masuk'] ?? 0);
                                    $bonus_full_style = $bonus_full > 0 ? 'background-color: #d9edf7; font-weight: bold;' : '';

                                    $denda = intval($row['denda_telat'] ?? 0);
                                    $denda_style = $denda > 0 ? 'background-color: #f2dede; color: #a94442;' : '';

                                    $potongan = intval($row['potongan_makan'] ?? 0);
                                    $potongan_style = $potongan > 0 ? 'background-color: #f2dede; color: #a94442;' : '';

                                    $kelebihan_style = $kelebihan_titik > 0 ? 'background-color: #dff0d8; font-weight: bold;' : '';

                                    // Format periode as "Bulan Tahun" (e.g., "Nov 2025")
                                    $periode_obj = DateTime::createFromFormat('Y-m', $row['periode']);
                                    $periode_formatted = $periode_obj ? $periode_obj->format('M Y') : $row['periode'];
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($row['npp']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_emp'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_bagian'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['cabang'] ?? '-'); ?></td>
                                        <td><?php echo $periode_formatted; ?></td>
                                        <td class="text-right editable-titik"
                                            data-npp="<?php echo htmlspecialchars($row['npp']); ?>"
                                            data-periode="<?php echo $row['periode']; ?>"
                                            data-total="<?php echo $no_titik; ?>" data-target="<?php echo $target_titik; ?>"
                                            style="cursor:pointer;">
                                            <?php echo number_format($no_titik); ?> <i class="fa fa-pencil"
                                                style="font-size:10px;color:#666;margin-left:6px;"></i>
                                        </td>
                                        <td class="text-right editable-titik"
                                            data-npp="<?php echo htmlspecialchars($row['npp']); ?>"
                                            data-periode="<?php echo $row['periode']; ?>"
                                            data-total="<?php echo $no_titik; ?>" data-target="<?php echo $target_titik; ?>"
                                            style="cursor:pointer;">
                                            <?php echo number_format($target_titik); ?> <i class="fa fa-pencil"
                                                style="font-size:10px;color:#666;margin-left:6px;"></i>
                                        </td>
                                        <!-- <td class="text-right"><?php echo number_format($persentase, 2); ?>%</td> -->
                                        <td class="text-right" style="<?php echo $kelebihan_style; ?>">
                                            <?php echo number_format($kelebihan_titik); ?>
                                        </td>
                                        <td class="text-right"><?php echo number_format($cap_titik); ?></td>
                                        <td class="text-right" style="<?php echo $telat_style; ?>">
                                            <?php echo number_format($row['akumulasi_telat'] ?? 0); ?> menit
                                        </td>
                                        <td class="text-center"><?php echo intval($row['hari_hadir'] ?? 0); ?></td>
                                        <td class="text-center"><?php echo intval($row['hari_telat'] ?? 0); ?></td>
                                        <td class="text-center"><?php echo intval($row['hari_cuti'] ?? 0); ?></td>
                                        <td class="text-center"><?php echo intval($row['hari_sakit'] ?? 0); ?></td>
                                        <td class="text-right" style="<?php echo $bonus_titik_style; ?>">
                                            <?php echo number_format($row['bonus_insentif_titik'] ?? 0); ?>
                                        </td>
                                        <td class="text-right" style="<?php echo $bonus_full_style; ?>">
                                            <?php echo number_format($row['bonus_insentif_full_masuk'] ?? 0); ?>
                                        </td>
                                        <td class="text-right"><?php echo number_format($row['lembur_operasional'] ?? 0); ?></td>
                                        <td class="text-right"><?php echo number_format($row['lembur_ambil_barang'] ?? 0); ?></td>
                                        <td class="text-right"><?php echo number_format($row['lembur_lainnya'] ?? 0); ?></td>
                                        <td class="text-right"><?php echo number_format($row['uang_lembur'] ?? 0); ?></td>
                                        <td class="text-right" style="<?php echo $denda_style; ?>">
                                            <?php echo number_format($row['denda_telat'] ?? 0); ?>
                                        </td>
                                        <td class="text-right" style="<?php echo $potongan_style; ?>">
                                            <?php echo number_format($row['potongan_makan'] ?? 0); ?>
                                        </td>
                                        <td class="text-right"><?php echo number_format($row['uang_makan'] ?? 0); ?></td>
                                        <td class="text-right bg-success" style="font-weight: bold; font-size: 14px;">
                                            <?php echo number_format($row['jumlah_dibayarkan'] ?? 0); ?>
                                        </td>

                                        <!-- <td><?php echo date('d-m-Y H:i', strtotime($row['updated_at'])); ?></td>
                                        <td>
                                            <a href="insentif_kurir_update.php?id=<?php echo intval($row['id']); ?>"
                                                class="btn btn-xs btn-warning">Edit</a>
                                        </td> -->
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- /.table-responsive -->
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- Edit Modal -->
            <div id="modalEditTitik" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form id="formEditTitik">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="modal-title">Edit Titik - <span id="modalNpp"></span> <small
                                        id="modalPeriode"></small></h4>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="modalNppInput" name="npp">
                                <input type="hidden" id="modalPeriodeInput" name="periode">
                                <div class="form-group">
                                    <label>Aktual Titik</label>
                                    <input type="number" class="form-control" id="modalAktual" name="aktual" min="0"
                                        required>
                                </div>
                                <div class="form-group">
                                    <label>Target Titik</label>
                                    <input type="number" class="form-control" id="modalTarget" name="target" min="0"
                                        required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->

<script>
    $(document).ready(function () {
        $('#dataTables').DataTable({
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
            language: {
                lengthMenu: "Tampilkan _MENU_ data per halaman",
                zeroRecords: "Data tidak ditemukan",
                info: "Menampilkan halaman _PAGE_ dari _PAGES_",
                infoEmpty: "Tidak ada data yang tersedia",
                infoFiltered: "(difilter dari _MAX_ total data)",
                search: "Cari:",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                }
            },
            order: [[5, 'desc']], // Sort by periode column (descending)
            columnDefs: [
                { orderable: false, targets: [0] }, // Disable sorting on "No"
                { className: "text-center", targets: [0, 4] }, // center: No, Cabang
                {
                    className: "text-right",
                    targets: [6, 7, 8, 9, 10, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24]
                } // numeric columns
            ],
            drawCallback: function () {
                // Re-apply Bootstrap tooltip after redraw
                $('[data-toggle="tooltip"]').tooltip();
            },
            dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
                '<"row"<"col-sm-12"tr>>' +
                '<"row"<"col-sm-5"i><"col-sm-7"p>>'
        });

        // Enable tooltips
        $('[title]').tooltip();

        // Open modal when clicking on editable titik cells
        $(document).on('click', '.editable-titik', function () {
            var npp = $(this).data('npp');
            var periode = $(this).data('periode');
            var total = $(this).data('total');
            var target = $(this).data('target');
            $('#modalNpp').text(npp);
            $('#modalPeriode').text(periode);
            $('#modalNppInput').val(npp);
            $('#modalPeriodeInput').val(periode);
            $('#modalAktual').val(total);
            $('#modalTarget').val(target);
            $('#modalEditTitik').modal('show');
        });

        // Submit edit form via AJAX
        $('#formEditTitik').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            var data = form.serialize();
            $.post('insentif_kurir_update_ajax.php', data, function (res) {
                if (res && res.success) {
                    // Update row cells: find matching row by npp+periode
                    var selector = '.editable-titik[data-npp="' + res.npp + '"][data-periode="' + res.periode + '"]';
                    $(selector).each(function () {
                        // first editable cell is total, second is target; update data attributes and text
                        var isTotalCell = $(this).data('total') == $(this).text().replace(/[^0-9]/g, '');
                        // update numeric display
                        if ($(this).index() == 6) { // column 6 = total titik
                            $(this).data('total', res.total_titik);
                            $(this).html(numberWithCommas(res.total_titik) + ' <i class="fa fa-pencil" style="font-size:10px;color:#666;margin-left:6px;"></i>');
                        }
                        if ($(this).index() == 7) { // column 7 = target titik
                            $(this).data('target', res.target_titik);
                            $(this).html(numberWithCommas(res.target_titik) + ' <i class="fa fa-pencil" style="font-size:10px;color:#666;margin-left:6px;"></i>');
                        }
                    });
                    // Update Kelebihan (col 8), Bonus Titik (col 15) and Total Dibayarkan (col 24)
                    var row = $('td.editable-titik[data-npp="' + res.npp + '"][data-periode="' + res.periode + '"]').first().closest('tr');
                    if (row.length) {
                        row.find('td').eq(8).text(numberWithCommas(res.kelebihan));
                        row.find('td').eq(15).text(numberWithCommas(res.bonus_insentif_titik));
                        row.find('td').eq(24).text(numberWithCommas(res.jumlah_dibayarkan));
                    }
                    $('#modalEditTitik').modal('hide');
                } else {
                    alert((res && res.message) ? res.message : 'Gagal menyimpan perubahan.');
                }
            }, 'json').fail(function () {
                alert('Terjadi kesalahan koneksi.');
            });
        });

        function numberWithCommas(x) {
            if (x === null || x === undefined) return '0';
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
    });
</script>
</div>
<!-- /.panel-body -->
</div>
<!-- /.panel -->
</div>
<!-- /.col-lg-12 -->
</div>
<!-- /.row -->
</div>
<!-- /#page-wrapper -->
<?php include("layout_bottom.php"); ?>