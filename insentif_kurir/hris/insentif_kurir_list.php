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
$records_per_page = 25; // Records per page
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Build query: select records from transaksi_insentif_kurir and join employee for metadata
$sql_base = "FROM transaksi_insentif_kurir t
        LEFT JOIN employee e ON t.npp = e.npp
        LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
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

// Fetch records with pagination
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'periode';
$sort_order = isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';

// Validate sort column
$allowed_sorts = ['periode', 'npp', 'nama_emp', 'cabang', 'total_titik', 'target_titik', 'hari_hadir', 'denda_telat', 'jumlah_dibayarkan'];
if (!in_array($sort_column, $allowed_sorts)) {
    $sort_column = 'periode';
}

$sql = "SELECT t.*, t.hari_hadir, t.hari_telat, t.hari_cuti, t.hari_alpha, e.nama_emp, b.nama_bagian, e.cabang " 
    . $sql_base 
    . " ORDER BY t." . $sort_column . " " . $sort_order . ", t.npp ASC"
    . " LIMIT " . $records_per_page . " OFFSET " . $offset;

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
    #insentifTable tbody tr:hover {
        background-color: #f5f5f5 !important;
    }

    #insentifTable thead tr th {
        background-color: #337ab7;
        color: white;
        font-weight: bold;
        vertical-align: middle;
        font-size: 11px;
        padding: 8px 4px;
    }

    /* Sortable column headers */
    #insentifTable thead th.sortable {
        cursor: pointer;
        user-select: none;
        position: relative;
        padding-right: 20px;
    }

    #insentifTable thead th.sortable:hover {
        background-color: #286090;
    }

    #insentifTable thead th.sortable:after {
        content: "⇅";
        position: absolute;
        right: 5px;
        opacity: 0.3;
        font-size: 10px;
    }

    #insentifTable thead th.sortable.sorted-asc:after {
        content: "▲";
        opacity: 1;
    }

    #insentifTable thead th.sortable.sorted-desc:after {
        content: "▼";
        opacity: 1;
    }

    /* Editable cells styling - IMPROVED */
    .editable-titik, .editable-cuti-sakit-makan, .editable-total-dibayarkan, .editable-telat {
        cursor: pointer !important;
        position: relative;
        /* Reserve space for icon to prevent layout shift */
        padding-right: 20px !important;
    }

    .editable-titik:hover, .editable-cuti-sakit-makan:hover, .editable-total-dibayarkan:hover, .editable-telat:hover {
        background-color: #ffffcc !important;
    }

    /* Icon pensil - position absolute agar tidak menggeser layout */
    .editable-titik:after, .editable-cuti-sakit-makan:after, .editable-total-dibayarkan:after, .editable-telat:after {
        content: "✎";
        font-size: 11px;
        color: #999;
        position: absolute;
        right: 4px;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0;
        pointer-events: none;
        /* Prevent icon from causing reflow */
        display: inline-block;
        width: 12px;
        text-align: center;
    }

    .editable-titik:hover:after, .editable-cuti-sakit-makan:hover:after, .editable-total-dibayarkan:hover:after, .editable-telat:hover:after {
        opacity: 1;
    }

    /* Force table to maintain column widths */
    #insentifTable {
        font-size: 12px;
        table-layout: auto;
    }

    #insentifTable td {
        padding: 6px 4px;
        white-space: nowrap;
        /* Prevent text wrapping that could cause shift */
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Specific width constraints for editable columns to prevent shift */
    #insentifTable td.editable-cuti-sakit-makan {
        min-width: 50px; /* Ensure consistent width */
    }

    #insentifTable td.editable-titik {
        min-width: 80px; /* Ensure consistent width for titik columns */
    }

    /* Highlight untuk total dibayarkan */
    .bg-success {
        background-color: #5cb85c !important;
        color: white !important;
    }

    /* Responsive table adjustments */
    #insentifTable {
        font-size: 12px;
    }

    #insentifTable td {
        padding: 6px 4px;
        white-space: nowrap;
    }

    /* Pagination styling */
    .pagination-wrapper {
        margin-top: 20px;
        text-align: center;
    }

    .pagination {
        display: inline-block;
        padding-left: 0;
        margin: 20px 0;
        border-radius: 4px;
    }

    .pagination > li {
        display: inline;
    }

    .pagination > li > a,
    .pagination > li > span {
        position: relative;
        float: left;
        padding: 6px 12px;
        line-height: 1.42857143;
        text-decoration: none;
        color: #337ab7;
        background-color: #fff;
        border: 1px solid #ddd;
        margin-left: -1px;
    }

    .pagination > li:first-child > a,
    .pagination > li:first-child > span {
        margin-left: 0;
        border-bottom-left-radius: 4px;
        border-top-left-radius: 4px;
    }

    .pagination > li:last-child > a,
    .pagination > li:last-child > span {
        border-bottom-right-radius: 4px;
        border-top-right-radius: 4px;
    }

    .pagination > li > a:hover,
    .pagination > li > span:hover,
    .pagination > li > a:focus,
    .pagination > li > span:focus {
        color: #23527c;
        background-color: #eeeeee;
        border-color: #ddd;
    }

    .pagination > .active > a,
    .pagination > .active > span,
    .pagination > .active > a:hover,
    .pagination > .active > span:hover,
    .pagination > .active > a:focus,
    .pagination > .active > span:focus {
        z-index: 2;
        color: #fff;
        background-color: #337ab7;
        border-color: #337ab7;
        cursor: default;
    }

    .pagination > .disabled > span,
    .pagination > .disabled > span:hover,
    .pagination > .disabled > span:focus,
    .pagination > .disabled > a,
    .pagination > .disabled > a:hover,
    .pagination > .disabled > a:focus {
        color: #777777;
        background-color: #fff;
        border-color: #ddd;
        cursor: not-allowed;
    }

    .pagination-info {
        margin-top: 10px;
        color: #666;
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

    /* Success notification styling */
    .success-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #5cb85c 0%, #449d44 100%);
        color: white;
        padding: 15px 45px 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        min-width: 350px;
        max-width: 500px;
        font-size: 14px;
        opacity: 0;
        transform: translateX(400px);
        transition: all 0.3s ease-in-out;
    }

    .success-notification.show {
        opacity: 1;
        transform: translateX(0);
    }

    .success-notification i {
        margin-right: 10px;
        font-size: 18px;
    }

    .success-notification .close-notification {
        position: absolute;
        top: 50%;
        right: 15px;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        padding: 0;
        width: 20px;
        height: 20px;
        line-height: 20px;
        text-align: center;
        opacity: 0.8;
    }

    .success-notification .close-notification:hover {
        opacity: 1;
    }

    @media screen and (max-width: 767px) {
        .success-notification {
            min-width: 280px;
            max-width: 90%;
            right: 5%;
            font-size: 12px;
        }
    }

    /* Unified Modal Tabs Styling */
    #modalEditInsentif .nav-tabs {
        border-bottom: 2px solid #337ab7;
    }

    #modalEditInsentif .nav-tabs > li > a {
        color: #666;
        font-weight: 600;
        border: 1px solid transparent;
        border-radius: 4px 4px 0 0;
        padding: 12px 20px;
    }

    #modalEditInsentif .nav-tabs > li.active > a,
    #modalEditInsentif .nav-tabs > li.active > a:hover,
    #modalEditInsentif .nav-tabs > li.active > a:focus {
        color: #337ab7;
        background-color: #fff;
        border: 1px solid #ddd;
        border-bottom-color: transparent;
        font-weight: bold;
    }

    #modalEditInsentif .nav-tabs > li > a:hover {
        background-color: #f5f5f5;
        border-color: #eee;
    }

    #modalEditInsentif .tab-content {
        min-height: 300px;
    }

    #modalEditInsentif .form-group label {
        font-weight: 600;
        color: #333;
    }

    #modalEditInsentif .input-lg {
        font-size: 16px;
        height: 45px;
    }

    #modalEditInsentif .alert {
        border-radius: 6px;
    }

    @media screen and (max-width: 767px) {
        #modalEditInsentif .modal-dialog {
            margin: 10px;
        }
        
        #modalEditInsentif .nav-tabs > li > a {
            font-size: 12px;
            padding: 8px 12px;
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
            <!-- <div class="panel panel-info">
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
            </div> -->

            <!-- Data Table Panel -->
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa fa-table"></i> Data Insentif Kurir
                    <span class="pull-right">
                        Halaman <?php echo $current_page; ?> dari <?php echo max(1, $total_pages); ?> 
                        (Total: <?php echo number_format($total_records); ?> data)
                    </span>
                </div>
                <!-- /.panel-heading -->
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover" id="insentifTable">
                            <thead>
                                <tr>
                                    <th rowspan="2">No</th>
                                    <th rowspan="2" class="sortable" data-sort="npp">NPP</th>
                                    <th rowspan="2" class="sortable" data-sort="nama_emp">Nama Karyawan</th>
                                    <th rowspan="2">Bagian</th>
                                    <th rowspan="2" class="sortable" data-sort="cabang">Cabang</th>
                                    <th rowspan="2" class="sortable" data-sort="periode">Periode</th>
                                    <th colspan="9" class="text-center">Performa</th>
                                    <th colspan="8" class="text-center">Komponen Pembayaran</th>
                                    <th rowspan="2" class="bg-success sortable" data-sort="jumlah_dibayarkan">Total Dibayarkan</th>
                                    <!-- <th rowspan="2">Status</th> -->
                                </tr>
                                <tr>
                                    <th class="sortable" data-sort="total_titik">Titik Berhasil</th>
                                    <th class="sortable" data-sort="target_titik">Target Titik</th>
                                    <th>Kelebihan</th>
                                    <th title="Titik maksimal yang dibayarkan">Titik Max</th>
                                    <th title="Total menit keterlambatan dalam periode">Akumulasi Telat (menit)</th>
                                    <th class="sortable" data-sort="hari_hadir" title="Jumlah hari hadir dalam periode">Hadir</th>
                                    <th title="Jumlah hari telat dalam periode">Telat</th>
                                    <th title="Jumlah hari cuti dalam periode">Cuti</th>
                                    <th title="Jumlah hari sakit dalam periode">Sakit</th>
                                    <th title="Bonus dari kelebihan titik (max 500rb/bulan)">Bonus Titik</th>
                                    <th title="Bonus full kehadiran (250rb jika 0 alpha)">Bonus Full Hadir</th>
                                    <th title="Nominal lembur operasional">Lembur Operasional</th>
                                    <th title="Nominal lembur ambil barang">Lembur Ambil Barang</th>
                                    <th title="Nominal lembur kategori lainnya">Lembur Lainnya</th>
                                    <th class="sortable" data-sort="denda_telat">Denda Telat</th>
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
                                    $kelebihan_titik = $no_titik - $target_titik; // Tampilkan nilai negatif jika belum memenuhi target
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

                                    // Style untuk kelebihan titik: hijau jika positif, merah jika negatif
                                    $kelebihan_style = $kelebihan_titik > 0 ? 'background-color: #dff0d8; font-weight: bold;' : ($kelebihan_titik < 0 ? 'background-color: #f2dede; color: #a94442;' : '');

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
                                            title="Klik untuk edit">
                                            <?php echo number_format($no_titik); ?>
                                        </td>
                                        <td class="text-right editable-titik"
                                            data-npp="<?php echo htmlspecialchars($row['npp']); ?>"
                                            data-periode="<?php echo $row['periode']; ?>"
                                            data-total="<?php echo $no_titik; ?>" data-target="<?php echo $target_titik; ?>"
                                            title="Klik untuk edit">
                                            <?php echo number_format($target_titik); ?>
                                        </td>
                                        <!-- <td class="text-right"><?php echo number_format($persentase, 2); ?>%</td> -->
                                        <td class="text-right" style="<?php echo $kelebihan_style; ?>">
                                            <?php echo number_format($kelebihan_titik); ?>
                                        </td>
                                        <td class="text-right"><?php echo number_format($cap_titik); ?></td>
                                        <td class="text-right editable-telat" style="<?php echo $telat_style; ?>"
                                            data-npp="<?php echo htmlspecialchars($row['npp']); ?>"
                                            data-periode="<?php echo $row['periode']; ?>"
                                            data-akumulasi="<?php echo intval($row['akumulasi_telat'] ?? 0); ?>"
                                            data-hari-telat="<?php echo intval($row['hari_telat'] ?? 0); ?>"
                                            data-denda="<?php echo intval($row['denda_telat'] ?? 0); ?>"
                                            title="Klik untuk edit telat & denda">
                                            <?php echo number_format($row['akumulasi_telat'] ?? 0); ?> menit
                                        </td>
                                        <td class="text-center"><?php echo intval($row['hari_hadir'] ?? 0); ?></td>
                                        <td class="text-center editable-telat"
                                            data-npp="<?php echo htmlspecialchars($row['npp']); ?>"
                                            data-periode="<?php echo $row['periode']; ?>"
                                            data-akumulasi="<?php echo intval($row['akumulasi_telat'] ?? 0); ?>"
                                            data-hari-telat="<?php echo intval($row['hari_telat'] ?? 0); ?>"
                                            data-denda="<?php echo intval($row['denda_telat'] ?? 0); ?>"
                                            title="Klik untuk edit telat & denda">
                                            <?php echo intval($row['hari_telat'] ?? 0); ?>
                                        </td>
                                        <!-- Cuti (editable) -->
                                        <td class="text-center editable-cuti-sakit-makan"
                                            data-npp="<?php echo htmlspecialchars($row['npp']); ?>"
                                            data-periode="<?php echo $row['periode']; ?>"
                                            data-cuti="<?php echo intval($row['hari_cuti'] ?? 0); ?>"
                                            data-sakit="<?php echo intval($row['hari_alpha'] ?? 0); ?>"
                                            data-uangmakan="<?php echo intval($row['uang_makan'] ?? 0); ?>"
                                            data-bonusfull="<?php echo intval($row['bonus_insentif_full_masuk'] ?? 0); ?>"
                                            title="Klik untuk edit">
                                            <?php echo intval($row['hari_cuti'] ?? 0); ?>
                                        </td>
                                        <!-- Sakit (editable) -->
                                        <td class="text-center editable-cuti-sakit-makan"
                                            data-npp="<?php echo htmlspecialchars($row['npp']); ?>"
                                            data-periode="<?php echo $row['periode']; ?>"
                                            data-cuti="<?php echo intval($row['hari_cuti'] ?? 0); ?>"
                                            data-sakit="<?php echo intval($row['hari_alpha'] ?? 0); ?>"
                                            data-uangmakan="<?php echo intval($row['uang_makan'] ?? 0); ?>"
                                            data-bonusfull="<?php echo intval($row['bonus_insentif_full_masuk'] ?? 0); ?>"
                                            title="Klik untuk edit">
                                            <?php echo intval($row['hari_alpha'] ?? 0); ?>
                                        </td>
                                        <!-- Bonus Titik -->
                                        <td class="text-right" style="<?php echo $bonus_titik_style; ?>">
                                            <?php echo number_format($row['bonus_insentif_titik'] ?? 0); ?>
                                        </td>
                                        <!-- Bonus Full Hadir -->
                                        <td class="text-right" style="<?php echo $bonus_full_style; ?>">
                                            <?php echo number_format($row['bonus_insentif_full_masuk'] ?? 0); ?>
                                        </td>
                                        <!-- Split lembur into three kategori -->
                                        <td class="text-right lembur-operasional"
                                            data-npp="<?php echo htmlspecialchars($row['npp']); ?>"
                                            data-periode="<?php echo $row['periode']; ?>"
                                            data-lembur-operasional="<?php echo intval($row['lembur_operasional'] ?? 0); ?>">
                                            <?php echo number_format($row['lembur_operasional'] ?? 0); ?>
                                        </td>
                                        <td class="text-right lembur-ambil"
                                            data-npp="<?php echo htmlspecialchars($row['npp']); ?>"
                                            data-periode="<?php echo $row['periode']; ?>"
                                            data-lembur-ambil="<?php echo intval($row['lembur_ambil_barang'] ?? 0); ?>">
                                            <?php echo number_format($row['lembur_ambil_barang'] ?? 0); ?>
                                        </td>
                                        <td class="text-right lembur-lain"
                                            data-npp="<?php echo htmlspecialchars($row['npp']); ?>"
                                            data-periode="<?php echo $row['periode']; ?>"
                                            data-lembur-lain="<?php echo intval($row['lembur_lainnya'] ?? 0); ?>">
                                            <?php echo number_format($row['lembur_lainnya'] ?? 0); ?>
                                        </td>
                                         <td class="text-right" style="<?php echo $denda_style; ?>">
                                             <?php echo number_format($row['denda_telat'] ?? 0); ?>
                                         </td>
                                        <td class="text-right" style="<?php echo $potongan_style; ?>">
                                            <?php echo number_format($row['potongan_makan'] ?? 0); ?>
                                        </td>
                                        <td class="text-right editable-cuti-sakit-makan"
                                            data-npp="<?php echo htmlspecialchars($row['npp']); ?>"
                                            data-periode="<?php echo $row['periode']; ?>"
                                            data-cuti="<?php echo intval($row['hari_cuti'] ?? 0); ?>"
                                            data-sakit="<?php echo intval($row['hari_alpha'] ?? 0); ?>"
                                            data-uangmakan="<?php echo intval($row['uang_makan'] ?? 0); ?>"
                                            data-bonusfull="<?php echo intval($row['bonus_insentif_full_masuk'] ?? 0); ?>"
                                            title="Klik untuk edit">
                                            <?php echo number_format($row['uang_makan'] ?? 0); ?>
                                        </td>
                                        <!-- Total Dibayarkan (editable & auto-calculated) -->
                                        <td class="text-right bg-success editable-total-dibayarkan"
                                            data-npp="<?php echo htmlspecialchars($row['npp']); ?>"
                                            data-periode="<?php echo $row['periode']; ?>"
                                            data-total="<?php echo intval($row['jumlah_dibayarkan'] ?? 0); ?>"
                                            title="Klik untuk edit manual (opsional)">
                                            <?php echo number_format($row['jumlah_dibayarkan'] ?? 0); ?>
                                        </td>
                                        <!-- Status -->
                                        <!-- <td class="text-center">
                                            <span class="label label-<?php echo ($status_class === 'success') ? 'success' : 'danger'; ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td> -->
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- /.table-responsive -->

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination-wrapper">
                        <ul class="pagination">
                            <?php
                            // Build query string for pagination
                            $query_params = array();
                            if (!empty($filter_periode)) $query_params['periode'] = $filter_periode;
                            if (!empty($filter_npp)) $query_params['npp'] = $filter_npp;
                            if (!empty($_GET['sort'])) $query_params['sort'] = $_GET['sort'];
                            if (!empty($_GET['order'])) $query_params['order'] = $_GET['order'];

                            // Previous button
                            if ($current_page > 1):
                                $query_params['page'] = $current_page - 1;
                                $prev_url = '?' . http_build_query($query_params);
                            ?>
                                <li><a href="<?php echo $prev_url; ?>">&laquo; Previous</a></li>
                            <?php else: ?>
                                <li class="disabled"><span>&laquo; Previous</span></li>
                            <?php endif; ?>

                            <?php
                            // Page numbers
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);

                            if ($start_page > 1): ?>
                                <?php
                                $query_params['page'] = 1;
                                $first_url = '?' . http_build_query($query_params);
                                ?>
                                <li><a href="<?php echo $first_url; ?>">1</a></li>
                                <?php if ($start_page > 2): ?>
                                    <li class="disabled"><span>...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <?php if ($i == $current_page): ?>
                                    <li class="active"><span><?php echo $i; ?></span></li>
                                <?php else: ?>
                                    <?php
                                    $query_params['page'] = $i;
                                    $page_url = '?' . http_build_query($query_params);
                                    ?>
                                    <li><a href="<?php echo $page_url; ?>"><?php echo $i; ?></a></li>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <li class="disabled"><span>...</span></li>
                                <?php endif; ?>
                                <?php
                                $query_params['page'] = $total_pages;
                                $last_url = '?' . http_build_query($query_params);
                                ?>
                                <li><a href="<?php echo $last_url; ?>"><?php echo $total_pages; ?></a></li>
                            <?php endif; ?>

                            <?php
                            // Next button
                            if ($current_page < $total_pages):
                                $query_params['page'] = $current_page + 1;
                                $next_url = '?' . http_build_query($query_params);
                            ?>
                                <li><a href="<?php echo $next_url; ?>">Next &raquo;</a></li>
                            <?php else: ?>
                                <li class="disabled"><span>Next &raquo;</span></li>
                            <?php endif; ?>
                        </ul>
                        <div class="pagination-info">
                            Menampilkan <?php echo number_format($offset + 1); ?> - <?php echo number_format(min($offset + $records_per_page, $total_records)); ?> dari <?php echo number_format($total_records); ?> data
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                <!-- /.panel-body -->
            </div>
            
            <!-- UNIFIED EDIT MODAL - All-in-One -->
            <div id="modalEditInsentif" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title">
                                <i class="fa fa-edit"></i> Edit Data Insentif - 
                                <strong><span id="modalNppUnified"></span></strong> 
                                <small class="text-muted">Periode: <span id="modalPeriodeUnified"></span></small>
                            </h4>
                        </div>
                        <div class="modal-body">
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs" role="tablist">
                                <li role="presentation" class="active">
                                    <a href="#tabTitik" aria-controls="tabTitik" role="tab" data-toggle="tab">
                                        <i class="fa fa-bullseye"></i> Titik & Target
                                    </a>
                                </li>
                                <li role="presentation">
                                    <a href="#tabCutiSakit" aria-controls="tabCutiSakit" role="tab" data-toggle="tab">
                                        <i class="fa fa-calendar-times-o"></i> Cuti & Sakit
                                    </a>
                                </li>
                                <li role="presentation">
                                    <a href="#tabLembur" aria-controls="tabLembur" role="tab" data-toggle="tab">
                                        <i class="fa fa-clock-o"></i> Lembur
                                    </a>
                                </li>
                            </ul>

                            <!-- Tab panes -->
                            <div class="tab-content" style="margin-top: 20px;">
                                <!-- TAB 1: TITIK & TARGET -->
                                <div role="tabpanel" class="tab-pane active" id="tabTitik">
                                    <form id="formEditTitik">
                                        <input type="hidden" id="modalNppInputTitik" name="npp">
                                        <input type="hidden" id="modalPeriodeInputTitik" name="periode">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label><i class="fa fa-check-circle text-success"></i> Titik Berhasil (Aktual)</label>
                                                    <input type="number" class="form-control input-lg" id="modalAktual" name="aktual" min="0" required>
                                                    <small class="text-muted">Jumlah titik yang berhasil dicapai</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label><i class="fa fa-target text-primary"></i> Target Titik</label>
                                                    <input type="number" class="form-control input-lg" id="modalTarget" name="target" min="0" required>
                                                    <small class="text-muted">Target titik yang harus dicapai</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i> <strong>Perhitungan Otomatis:</strong>
                                            <ul style="margin: 8px 0 0 20px;">
                                                <li>Kelebihan Titik = Aktual - Target</li>
                                                <li>Bonus dihitung dari kelebihan × rate per titik</li>
                                                <li>Maksimal bonus sesuai pengaturan sistem</li>
                                            </ul>
                                        </div>
                                        <div class="text-right">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fa fa-save"></i> Simpan Titik & Target
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <!-- TAB 2: CUTI & SAKIT -->
                                <div role="tabpanel" class="tab-pane" id="tabCutiSakit">
                                    <form id="formEditCutiSakit">
                                        <input type="hidden" id="modalNppInputCuti" name="npp">
                                        <input type="hidden" id="modalPeriodeInputCuti" name="periode">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label><i class="fa fa-plane text-warning"></i> Hari Cuti</label>
                                                    <input type="number" class="form-control input-lg" id="modalCuti" name="hari_cuti" min="0" step="1" value="0">
                                                    <small class="text-muted">Jumlah hari cuti yang diambil</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label><i class="fa fa-medkit text-danger"></i> Hari Sakit</label>
                                                    <input type="number" class="form-control input-lg" id="modalSakit" name="hari_sakit" min="0" step="1" value="0">
                                                    <small class="text-muted">Jumlah hari sakit</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="alert alert-warning">
                                            <i class="fa fa-exclamation-triangle"></i> <strong>Dampak Otomatis:</strong>
                                            <ul style="margin: 8px 0 0 20px;">
                                                <li><strong>Uang Makan:</strong> Dipotong Rp 15.000 per hari (cuti + sakit)</li>
                                                <li><strong>Bonus Full Hadir:</strong> Otomatis jadi 0 jika ada cuti/sakit/telat</li>
                                                <li><strong>NPP Khusus:</strong> NPP 22910033 tidak mendapat uang makan</li>
                                            </ul>
                                        </div>
                                        <div class="text-right">
                                            <button type="submit" class="btn btn-warning btn-lg">
                                                <i class="fa fa-save"></i> Simpan Cuti & Sakit
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <!-- TAB 3: LEMBUR -->
                                <div role="tabpanel" class="tab-pane" id="tabLembur">
                                    <form id="formEditLembur">
                                        <input type="hidden" id="modalNppInputLembur" name="npp">
                                        <input type="hidden" id="modalPeriodeInputLembur" name="periode">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label><i class="fa fa-cog"></i> Lembur Operasional (unit)</label>
                                                    <input type="number" class="form-control input-lg" id="modalLemburOperasional" name="jumlah_operasional" min="0" step="1" value="0">
                                                    <small class="text-muted">Unit</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label><i class="fa fa-truck"></i> Lembur Ambil Barang (unit)</label>
                                                    <input type="number" class="form-control input-lg" id="modalLemburAmbil" name="jumlah_ambil" min="0" step="1" value="0">
                                                    <small class="text-muted">Unit</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label><i class="fa fa-ellipsis-h"></i> Lembur Lainnya (unit)</label>
                                                    <input type="number" class="form-control input-lg" id="modalLemburLain" name="jumlah_lain" min="0" step="1" value="0">
                                                    <small class="text-muted">Unit</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row" style="margin-top:8px;">
                                            <div class="col-md-12">
                                                <div class="alert alert-success">
                                                    <i class="fa fa-calculator"></i> <strong>Perhitungan Otomatis:</strong>
                                                    <div id="lmbPreview" style="margin-top:8px;">Total Lembur: Rp <span id="lmbTotalPreview">0</span> (OP: Rp <span id="lmbOpPreview">0</span>, AMBIL: Rp <span id="lmbAmbilPreview">0</span>, LAIN: Rp <span id="lmbLainPreview">0</span>)</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <button type="submit" class="btn btn-success btn-lg">
                                                <i class="fa fa-save"></i> Simpan Lembur
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">
                                <i class="fa fa-times"></i> Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.unified modal -->
            
            <!-- MODAL EDIT TOTAL DIBAYARKAN -->
            <div id="modalEditTotalDibayarkan" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-success" style="background-color: #5cb85c; color: white;">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <h4 class="modal-title">
                                <i class="fa fa-money"></i> Edit Total Dibayarkan
                            </h4>
                        </div>
                        <form id="formEditTotalDibayarkan">
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> 
                                    <strong>Manual Override:</strong> Edit ini akan mengubah Total Dibayarkan secara langsung, 
                                    mengabaikan perhitungan otomatis.
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fa fa-user"></i> NPP</label>
                                            <input type="text" class="form-control input-lg" id="modalTotalNPP" readonly style="background-color: #f5f5f5; font-weight: bold;">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fa fa-calendar"></i> Periode</label>
                                            <input type="text" class="form-control input-lg" id="modalTotalPeriode" readonly style="background-color: #f5f5f5; font-weight: bold;">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fa fa-money"></i> Total Dibayarkan Saat Ini</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-addon">Rp</span>
                                        <input type="text" class="form-control" id="modalTotalCurrent" readonly style="background-color: #f0f0f0; font-weight: bold; font-size: 18px; color: #333;">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fa fa-edit"></i> Total Dibayarkan Baru <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-addon">Rp</span>
                                        <input type="number" class="form-control" id="modalTotalNew" name="jumlah_dibayarkan" 
                                               required min="0" step="1000" 
                                               placeholder="Masukkan nilai baru"
                                               style="font-size: 18px; font-weight: bold;">
                                    </div>
                                    <small class="help-block text-muted">
                                        <i class="fa fa-info-circle"></i> Masukkan total yang ingin dibayarkan
                                    </small>
                                </div>
                                
                                <input type="hidden" id="modalTotalNPPInput" name="npp">
                                <input type="hidden" id="modalTotalPeriodeInput" name="periode">
                                <input type="hidden" name="action" value="update_total_dibayarkan">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default btn-lg" data-dismiss="modal">
                                    <i class="fa fa-times"></i> Batal
                                </button>
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fa fa-save"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- /.modal edit total dibayarkan -->
            
            <!-- MODAL EDIT TELAT & DENDA -->
            <div id="modalEditTelat" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-warning" style="background-color: #f0ad4e; color: white;">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <h4 class="modal-title">
                                <i class="fa fa-clock-o"></i> Edit Data Keterlambatan
                            </h4>
                        </div>
                        <form id="formEditTelat">
                            <div class="modal-body">
                                <div class="alert alert-warning">
                                    <i class="fa fa-exclamation-triangle"></i> 
                                    <strong>Perhatian:</strong> Edit ini akan mempengaruhi perhitungan Bonus Full Hadir dan Total Dibayarkan.
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fa fa-user"></i> NPP</label>
                                            <input type="text" class="form-control input-lg" id="modalTelatNPP" readonly style="background-color: #f5f5f5; font-weight: bold;">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fa fa-calendar"></i> Periode</label>
                                            <input type="text" class="form-control input-lg" id="modalTelatPeriode" readonly style="background-color: #f5f5f5; font-weight: bold;">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fa fa-clock-o"></i> Akumulasi Telat (menit) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control input-lg" id="modalAkumulasiTelat" name="akumulasi_telat" 
                                                   required min="0" step="1" 
                                                   placeholder="Contoh: 120">
                                            <small class="help-block text-muted">
                                                <i class="fa fa-info-circle"></i> Total menit keterlambatan dalam bulan ini
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fa fa-calendar-times-o"></i> Hari Telat <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control input-lg" id="modalHariTelat" name="hari_telat" 
                                                   required min="0" step="1" 
                                                   placeholder="Contoh: 5">
                                            <small class="help-block text-muted">
                                                <i class="fa fa-info-circle"></i> Jumlah hari terlambat
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- <div class="form-group">
                                    <label><i class="fa fa-money"></i> Denda Telat (Rp) <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-addon">Rp</span>
                                        <input type="number" class="form-control" id="modalDendaTelat" name="denda_telat" 
                                               required min="0" step="1000" 
                                               placeholder="Masukkan denda keterlambatan"
                                               style="font-size: 18px; font-weight: bold;">
                                    </div>
                                    <small class="help-block text-muted">
                                        <i class="fa fa-info-circle"></i> Denda akan mengurangi Total Dibayarkan
                                    </small>
                                </div>
                                 -->
                                <div class="alert alert-info" style="margin-bottom: 0;">
                                    <i class="fa fa-lightbulb-o"></i> 
                                    <strong>Info:</strong> Jika Hari Telat > 0, maka Bonus Full Hadir otomatis = 0
                                </div>
                                
                                <input type="hidden" id="modalTelatNPPInput" name="npp">
                                <input type="hidden" id="modalTelatPeriodeInput" name="periode">
                                <input type="hidden" name="action" value="update_telat">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default btn-lg" data-dismiss="modal">
                                    <i class="fa fa-times"></i> Batal
                                </button>
                                <button type="submit" class="btn btn-warning btn-lg">
                                    <i class="fa fa-save"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- /.modal edit telat -->
            
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
                { className: "text-right", targets: [6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23] }
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

        // EDIT TELAT: Open modal for edit telat & denda
        $(document).on('click', '.editable-telat', function () {
            var $cell = $(this);
            var npp = $cell.data('npp');
            var periode = $cell.data('periode');
            var akumulasi = $cell.data('akumulasi') || 0;
            var hariTelat = $cell.data('hari-telat') || 0;
            var denda = $cell.data('denda') || 0;
            
            // Populate modal
            $('#modalTelatNPP').val(npp);
            $('#modalTelatPeriode').val(periode);
            $('#modalAkumulasiTelat').val(akumulasi);
            $('#modalHariTelat').val(hariTelat);
            
            // Calculate denda based on akumulasi (1 menit = Rp 1.000)
            var calculatedDenda = akumulasi * 1000;
            $('#modalDendaTelat').val(calculatedDenda);
            $('#modalDendaTelatDisplay').val(numberWithCommas(calculatedDenda));
            
            $('#modalTelatNPPInput').val(npp);
            $('#modalTelatPeriodeInput').val(periode);
            
            // Show modal
            $('#modalEditTelat').modal('show');
        });

        // AUTO-CALCULATE DENDA when Akumulasi Telat changes
        $('#modalAkumulasiTelat').on('input', function() {
            var akumulasi = parseInt($(this).val()) || 0;
            var denda = akumulasi * 1000; // Rp 1.000 per menit
            $('#modalDendaTelat').val(denda);
            $('#modalDendaTelatDisplay').val(numberWithCommas(denda));
        });

        // FORM: Submit Edit Telat via AJAX
        $('#formEditTelat').on('submit', function (e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            var npp = $('#modalTelatNPPInput').val();
            var periode = $('#modalTelatPeriodeInput').val();
            var akumulasi = parseInt($('#modalAkumulasiTelat').val());
            var hariTelat = parseInt($('#modalHariTelat').val());
            var denda = parseInt($('#modalDendaTelat').val()); // Auto-calculated
            
            if (isNaN(akumulasi) || akumulasi < 0 || isNaN(hariTelat) || hariTelat < 0) {
                alert('Nilai tidak valid! Masukkan angka positif untuk Akumulasi Telat dan Hari Telat.');
                return;
            }
            
            $.post('insentif_kurir_update_ajax.php', formData, function (res) {
                if (res && res.success) {
                    // Update cells in table
                    var $row = $('.editable-telat[data-npp="' + npp + '"][data-periode="' + periode + '"]').closest('tr');
                    
                    // Update Akumulasi Telat cells (both cells with data-akumulasi)
                    $row.find('.editable-telat').each(function() {
                        $(this).data('akumulasi', res.akumulasi_telat);
                        $(this).data('hari-telat', res.hari_telat);
                        $(this).data('denda', res.denda_telat);
                    });
                    
                    // Update Akumulasi Telat text (first editable-telat is akumulasi column)
                    $row.find('.editable-telat').first().text(numberWithCommas(res.akumulasi_telat) + ' menit');
                    
                    // Update Hari Telat text (second editable-telat is hari telat column)
                    $row.find('.editable-telat').eq(1).text(res.hari_telat);
                    
                    // Update Denda Telat column (find by class text-right, not editable)
                    $row.find('td').each(function(idx) {
                        var text = $(this).text().trim();
                        // Find denda column (contains formatted number, not editable)
                        if (idx > 15 && !$(this).hasClass('editable-telat') && !$(this).hasClass('editable-cuti-sakit-makan') && !$(this).hasClass('bg-success')) {
                            var currentVal = parseInt(text.replace(/[^0-9-]/g, ''));
                            if (!isNaN(currentVal) && Math.abs(currentVal) > 100000) { // likely denda column
                                $(this).text(numberWithCommas(res.denda_telat));
                                return false; // break
                            }
                        }
                    });
                    
                    // Update Bonus Full Hadir if returned
                    if (typeof res.bonus_full_hadir !== 'undefined') {
                        $row.find('td').eq(14).text(numberWithCommas(res.bonus_full_hadir)); // Column Bonus Full
                    }
                    
                    // Update Total Dibayarkan
                    var $totalCell = $row.find('.editable-total-dibayarkan');
                    $totalCell.data('total', res.jumlah_dibayarkan);
                    $totalCell.text(numberWithCommas(res.jumlah_dibayarkan));
                    
                    // Close modal
                    $('#modalEditTelat').modal('hide');
                    
                    // Show success notification
                    showSuccessNotification(
                        '✓ <strong>Data Keterlambatan berhasil diupdate!</strong><br>' +
                        'NPP: ' + res.npp + ' | Periode: ' + res.periode + '<br>' +
                        'Akumulasi: ' + numberWithCommas(res.akumulasi_telat) + ' menit | ' +
                        'Hari Telat: ' + res.hari_telat + ' | ' +
                        'Denda: Rp ' + numberWithCommas(res.denda_telat) + '<br>' +
                        'Total Dibayarkan: Rp ' + numberWithCommas(res.jumlah_dibayarkan)
                    );
                } else {
                    alert((res && res.message) ? res.message : 'Gagal update data keterlambatan.');
                }
            }, 'json').fail(function () {
                alert('Terjadi kesalahan koneksi.');
            });
        });

        // UNIFIED MODAL: Open when clicking on ANY editable cell
        $(document).on('click', '.editable-titik, .editable-cuti-sakit-makan', function () {
            var $cell = $(this);
            var npp = $cell.data('npp');
            var periode = $cell.data('periode');
            var $row = $cell.closest('tr');
            
            // Populate modal header
            $('#modalNppUnified').text(npp);
            $('#modalPeriodeUnified').text(periode);
            
            // Populate all hidden inputs
            $('#modalNppInputTitik, #modalNppInputCuti, #modalNppInputLembur').val(npp);
            $('#modalPeriodeInputTitik, #modalPeriodeInputCuti, #modalPeriodeInputLembur').val(periode);
            
            // TAB 1: TITIK & TARGET - populate from row
            var aktual = $row.find('td').eq(6).text().replace(/[^0-9]/g, '') || 0;
            var target = $row.find('td').eq(7).text().replace(/[^0-9]/g, '') || 0;
            $('#modalAktual').val(aktual);
            $('#modalTarget').val(target);
            
            // TAB 2: CUTI & SAKIT - populate from data attributes
            var cuti = $cell.data('cuti') || $row.find('.editable-cuti-sakit-makan').first().data('cuti') || 0;
            var sakit = $cell.data('sakit') || $row.find('.editable-cuti-sakit-makan').first().data('sakit') || 0;
            $('#modalCuti').val(cuti);
            $('#modalSakit').val(sakit);
            
            // TAB 3: LEMBUR - reset to default
            $('#modalKategoriLembur').prop('selectedIndex', 0);
            $('#modalJumlahLembur').val(0);
            
            // Determine which tab to show based on clicked cell
            if ($cell.hasClass('editable-titik')) {
                // Show Titik tab
                $('.nav-tabs a[href="#tabTitik"]').tab('show');
            } else if ($cell.hasClass('editable-cuti-sakit-makan')) {
                // Show Cuti/Sakit tab
                $('.nav-tabs a[href="#tabCutiSakit"]').tab('show');
            }
            
            // Show the unified modal
            $('#modalEditInsentif').modal('show');
        });

        // EDIT TOTAL DIBAYARKAN: Open modal instead of prompt
        $(document).on('click', '.editable-total-dibayarkan', function () {
            var $cell = $(this);
            var npp = $cell.data('npp');
            var periode = $cell.data('periode');
            var currentTotal = $cell.data('total') || 0;
            var currentFormatted = numberWithCommas(currentTotal);
            
            // Populate modal
            $('#modalTotalNPP').val(npp);
            $('#modalTotalPeriode').val(periode);
            $('#modalTotalCurrent').val(currentFormatted);
            $('#modalTotalNew').val(currentTotal).focus();
            $('#modalTotalNPPInput').val(npp);
            $('#modalTotalPeriodeInput').val(periode);
            
            // Show modal
            $('#modalEditTotalDibayarkan').modal('show');
        });

        // FORM: Submit Edit Total Dibayarkan via AJAX
        $('#formEditTotalDibayarkan').on('submit', function (e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            var npp = $('#modalTotalNPPInput').val();
            var periode = $('#modalTotalPeriodeInput').val();
            var newTotal = parseInt($('#modalTotalNew').val());
            
            if (isNaN(newTotal) || newTotal < 0) {
                alert('Nilai tidak valid! Masukkan angka positif.');
                return;
            }
            
            $.post('insentif_kurir_update_ajax.php', formData, function (res) {
                if (res && res.success) {
                    // Update cell in table
                    var $cell = $('.editable-total-dibayarkan[data-npp="' + npp + '"][data-periode="' + periode + '"]');
                    $cell.data('total', res.jumlah_dibayarkan);
                    $cell.text(numberWithCommas(res.jumlah_dibayarkan));
                    
                    // Close modal
                    $('#modalEditTotalDibayarkan').modal('hide');
                    
                    // Show success notification
                    showSuccessNotification(
                        '✓ <strong>Total Dibayarkan berhasil diupdate!</strong><br>' +
                        'NPP: ' + res.npp + ' | Periode: ' + res.periode + '<br>' +
                        'Total Baru: Rp ' + numberWithCommas(res.jumlah_dibayarkan)
                    );
                } else {
                    alert((res && res.message) ? res.message : 'Gagal update total dibayarkan.');
                }
            }, 'json').fail(function () {
                alert('Terjadi kesalahan koneksi.');
            });
        });

        // FORM 1: Submit Titik & Target via AJAX
        $('#formEditTitik').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            var data = form.serialize();
            var submitBtn = form.find('button[type="submit"]');
            submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
            
            $.post('insentif_kurir_update_ajax.php', data, function (res) {
                if (res && res.success) {
                    var row = $('.editable-titik[data-npp="' + res.npp + '"][data-periode="' + res.periode + '"]').first().closest('tr');
                    if (row.length) {
                        row.find('td').eq(6).text(numberWithCommas(res.total_titik));
                        row.find('td').eq(7).text(numberWithCommas(res.target_titik));
                        row.find('td').eq(8).text(numberWithCommas(res.kelebihan));
                        row.find('td').eq(15).text(numberWithCommas(res.bonus_insentif_titik));
                        row.find('.editable-titik').data('total', res.total_titik).data('target', res.target_titik);
                        computeRowTotal(row);
                    }
                    showSuccessNotification('✓ <strong>Titik berhasil diupdate!</strong><br>Aktual: ' + numberWithCommas(res.total_titik) + ' | Target: ' + numberWithCommas(res.target_titik) + ' | Bonus: Rp ' + numberWithCommas(res.bonus_insentif_titik));
                    // Switch to next tab
                    $('.nav-tabs a[href="#tabCutiSakit"]').tab('show');
                } else {
                    alert((res && res.message) ? res.message : 'Gagal menyimpan titik.');
                }
                submitBtn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Titik & Target');
            }, 'json').fail(function () {
                alert('Terjadi kesalahan koneksi.');
                submitBtn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Titik & Target');
            });
        });

        // FORM 2: Submit Cuti & Sakit via AJAX
        $('#formEditCutiSakit').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            var data = form.serialize() + '&action=update_cuti_sakit_makan';
            var submitBtn = form.find('button[type="submit"]');
            submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
            
            $.post('insentif_kurir_update_ajax.php', data, function (res) {
                if (res && res.success) {
                    var row = $('.editable-cuti-sakit-makan[data-npp="' + res.npp + '"][data-periode="' + res.periode + '"]').first().closest('tr');
                    if (row.length) {
                        row.find('td').eq(12).text(res.hari_cuti);
                        row.find('td').eq(13).text(res.hari_sakit);
                        row.find('td').eq(16).text(numberWithCommas(res.bonus_full_hadir));
                        row.find('td').eq(19).text(numberWithCommas(res.potongan_makan));
                        row.find('td').eq(20).text(numberWithCommas(res.uang_makan));
                        row.find('.editable-cuti-sakit-makan').data('cuti', res.hari_cuti).data('sakit', res.hari_sakit);
                        computeRowTotal(row);
                    }
                    showSuccessNotification('✓ <strong>Cuti/Sakit berhasil diupdate!</strong><br>Cuti: ' + res.hari_cuti + ' hari | Sakit: ' + res.hari_sakit + ' hari<br>Uang Makan: Rp ' + numberWithCommas(res.uang_makan) + ' | Bonus Full: Rp ' + numberWithCommas(res.bonus_full_hadir));
                    // Switch to next tab
                    $('.nav-tabs a[href="#tabLembur"]').tab('show');
                } else {
                    alert((res && res.message) ? res.message : 'Gagal menyimpan cuti/sakit.');
                }
                submitBtn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Cuti & Sakit');
            }, 'json').fail(function () {
                alert('Terjadi kesalahan koneksi.');
                submitBtn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Cuti & Sakit');
            });
        });
        // FORM 3: Submit Lembur via AJAX
        $('#formEditLembur').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            // selalu kirim ketiga field (lebih sederhana), backend mendukung partial juga
            var data = form.serialize() + '&action=update_lembur';
            var submitBtn = form.find('button[type="submit"]');
            submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
            
            $.post('insentif_kurir_update_ajax.php', data, function (res) {
                if (res && res.success) {
                    var row = $('.editable-titik[data-npp="' + res.npp + '"][data-periode="' + res.periode + '"]').first().closest('tr');
                    if (row.length) {
                        // update per-kategori cells (display amounts) and data-attributes
                        row.find('.lembur-operasional').data('lembur-operasional', res.lembur_operasional_amt).text(numberWithCommas(res.lembur_operasional_amt));
                        row.find('.lembur-ambil').data('lembur-ambil', res.lembur_ambil_amt).text(numberWithCommas(res.lembur_ambil_amt));
                        row.find('.lembur-lain').data('lembur-lain', res.lembur_lain_amt).text(numberWithCommas(res.lembur_lain_amt));
                        
                        // recompute total dibayarkan in frontend
                        computeRowTotal(row);
                    }
                    showSuccessNotification('✓ <strong>Lembur berhasil diupdate!</strong><br>OP: Rp ' + numberWithCommas(res.lembur_operasional_amt) + ' | AMBIL: Rp ' + numberWithCommas(res.lembur_ambil_amt) + ' | LAIN: Rp ' + numberWithCommas(res.lembur_lain_amt) + '<br>Total Lembur: Rp ' + numberWithCommas(res.uang_lembur) + '<br>Total Dibayarkan: Rp ' + numberWithCommas(res.jumlah_dibayarkan));
                    $('#modalEditInsentif').modal('hide');
                } else {
                    alert((res && res.message) ? res.message : 'Gagal menyimpan lembur.');
                }
                submitBtn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Lembur');
            }, 'json').fail(function () {
                alert('Terjadi kesalahan koneksi.');
                submitBtn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Lembur');
            });
        });

        // Open modal for cuti/sakit
        $(document).on('click', '.editable-cuti-sakit-makan', function () {
            var npp = $(this).data('npp');
            var periode = $(this).data('periode');
            var cuti = $(this).data('cuti') || 0;
            var sakit = $(this).data('sakit') || 0;
            $('#modalNppC').text(npp + ' / ' + periode);
            $('#modalNppInputC').val(npp);
            $('#modalPeriodeInputC').val(periode);
            $('#modalCuti').val(cuti);
            $('#modalSakit').val(sakit);
            $('#modalEditCutiSakitMakan').modal('show');
        });

        // Submit edit cuti/sakit via AJAX
        $('#formEditCutiSakitMakan').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            var data = form.serialize() + '&action=update_cuti_sakit_makan';
            $.post('insentif_kurir_update_ajax.php', data, function (res) {
                if (res && res.success) {
                    // update displayed cells for that npp+periode
                    var selector = '.editable-cuti-sakit-makan[data-npp="' + res.npp + '"][data-periode="' + res.periode + '"]';
                    $(selector).each(function () {
                        $(this).data('cuti', res.hari_cuti);
                        $(this).data('sakit', res.hari_sakit);
                        $(this).data('uangmakan', res.uang_makan);
                        $(this).data('bonusfull', res.bonus_full_hadir);
                    });
                    // update table row cells (find row)
                    var row = $('.editable-cuti-sakit-makan[data-npp="' + res.npp + '"][data-periode="' + res.periode + '"]').first().closest('tr');
                    if (row.length) {
                        // Update editable cells: cuti, sakit, uang_makan
                        row.find('td').filter(function (i) {
                            return $(this).hasClass('editable-cuti-sakit-makan');
                        }).each(function (idx) {
                            // first editable occurrence -> cuti, second -> sakit, third -> uang_makan
                            if (idx === 0) $(this).text(res.hari_cuti);
                            if (idx === 1) $(this).text(res.hari_sakit);
                            if (idx === 2) $(this).text(numberWithCommas(res.uang_makan));
                        });
                        var bonusFullCell = row.find('td').eq(16);
                        if (bonusFullCell.length) bonusFullCell.text(numberWithCommas(res.bonus_full_hadir));

                        // HITUNG ULANG TOTAL BARIS (frontend)
                        computeRowTotal(row);

                        // update total dibayarkan yang dikembalikan server juga (jika ada)
                        row.find('td.bg-success').first().text(numberWithCommas(res.jumlah_dibayarkan));
                    }
                    $('#modalEditCutiSakitMakan').modal('hide');
                    
                    // Show success notification
                    showSuccessNotification('✓ Cuti/Sakit berhasil diupdate! Cuti: ' + res.hari_cuti + ' hari, Sakit: ' + res.hari_sakit + ' hari, Uang Makan: Rp ' + numberWithCommas(res.uang_makan) + ', Bonus Full: Rp ' + numberWithCommas(res.bonus_full_hadir));
                } else {
                    alert((res && res.message) ? res.message : 'Gagal menyimpan perubahan.');
                }
            }, 'json').fail(function () {
                alert('Terjadi kesalahan koneksi.');
            });
        });

        // --- START: fungsi helper untuk hitung total di frontend ---
        function parseCellNumber(cell) {
            var txt = $(cell).text() || '';
            // ambil tanda minus juga
            var num = parseInt(txt.replace(/[^0-9\-]/g, ''), 10);
            return isNaN(num) ? 0 : num;
        }

        function computeRowTotal($row) {
            if (!$row || !$row.length) return;
            // index kolom (0-based) sesuai struktur tabel saat ini
            var idx_bonus_titik   = 15;
            var idx_bonus_full    = 16;
            var idx_lembur_op     = 17;
            var idx_lembur_ambil  = 18;
            var idx_lembur_lain   = 19;
            var idx_denda         = 20;
            var idx_pot_makan     = 21; // tidak ikut ke total, hanya info
            var idx_uang_makan    = 22;
            var idx_total         = 23;

            var bonusTitik = parseCellNumber($row.find('td').eq(idx_bonus_titik));
            var bonusFull  = parseCellNumber($row.find('td').eq(idx_bonus_full));
            var lemburOp   = parseCellNumber($row.find('td').eq(idx_lembur_op));
            var lemburAmb  = parseCellNumber($row.find('td').eq(idx_lembur_ambil));
            var lemburLain = parseCellNumber($row.find('td').eq(idx_lembur_lain));
            var denda      = parseCellNumber($row.find('td').eq(idx_denda));
            var uangMakan  = parseCellNumber($row.find('td').eq(idx_uang_makan));

            var lembur = lemburOp + lemburAmb + lemburLain;
            var total  = uangMakan + bonusTitik + bonusFull + lembur - denda;

            var $totalCell = $row.find('td').eq(idx_total);
            $totalCell.text(numberWithCommas(total));

            if (total > 0) {
                $totalCell.addClass('bg-success').css({'color': 'white'});
            } else {
                $totalCell.removeClass('bg-success').css({'color': total < 0 ? '#a94442' : ''});
            }
        }

        // Hitung total untuk semua baris saat load (berguna bila DB belum update)
        $('#insentifTable tbody tr').each(function () {
            computeRowTotal($(this));
        });
        // --- END fungsi helper ---

        // --- START: Success notification system ---
        function showSuccessNotification(message) {
            // Remove any existing notification
            $('.success-notification').remove();
            
            // Create notification element
            var $notification = $('<div class="success-notification">' +
                '<i class="fa fa-check-circle"></i> ' + message +
                '<button type="button" class="close-notification">&times;</button>' +
                '</div>');
            
            // Append to body
            $('body').append($notification);
            
            // Animate in
            setTimeout(function() {
                $notification.addClass('show');
            }, 10);
            
            // Auto hide after 5 seconds
            setTimeout(function() {
                hideNotification($notification);
            }, 5000);
            
            // Close button handler
            $notification.find('.close-notification').on('click', function() {
                hideNotification($notification);
            });
        }
        
        function hideNotification($notification) {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }
        
        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        // --- END notification system ---
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