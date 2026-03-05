<?php
include("sess_check.php");
$pagedesc = 'Detail Perjalanan Dinas';
$menuparent = 'perjalanan_dinas';
include("layout_top.php");

// DB connection
include __DIR__ . '/dist/config/koneksi.php';

// Get ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo '<div class="alert alert-danger">ID tidak valid.</div>';
    include 'layout_bottom.php';
    exit;
}

// Fetch main record
$stmt = mysqli_prepare($conn, "SELECT * FROM perjalanan_dinas WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo '<div class="alert alert-danger">Data tidak ditemukan.</div>';
    include 'layout_bottom.php';
    exit;
}

// Fetch rincian
$stmt_rincian = mysqli_prepare($conn, "SELECT * FROM perjalanan_rincian WHERE perjalanan_id = ? ORDER BY nomor");
mysqli_stmt_bind_param($stmt_rincian, 'i', $id);
mysqli_stmt_execute($stmt_rincian);
$result_rincian = mysqli_stmt_get_result($stmt_rincian);
$rincian_items = [];
while ($row = mysqli_fetch_assoc($result_rincian)) {
    $rincian_items[] = $row;
}

// Fetch submission status
$stmt_status = mysqli_prepare($conn, "SELECT * FROM perjalanan_pengajuan WHERE id_perjalanan = ? ORDER BY id DESC LIMIT 1");
mysqli_stmt_bind_param($stmt_status, 'i', $id);
mysqli_stmt_execute($stmt_status);
$result_status = mysqli_stmt_get_result($stmt_status);
$submission = mysqli_fetch_assoc($result_status);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Detail Perjalanan Dinas</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
        }

        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 10px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .logo {
            width: 140px;
        }

        .logo img {
            max-width: 100%;
            height: auto;
        }

        .title {
            flex: 1;
            text-align: center;
            font-weight: 700;
            font-size: 18px;
        }

        .doc-box {
            border: 1px solid #000;
            padding: 8px;
            font-size: 11px;
            width: 140px;
        }

        table.form {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        table.form td,
        table.form th {
            border: 1px solid #000;
            padding: 6px;
            font-size: 13px;
        }

        .rincian-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .rincian-table td,
        .rincian-table th {
            border: 1px solid #000;
            padding: 6px;
            font-size: 12px;
        }

        .signature {
            height: 70px;
            text-align: center;
        }

        .print-controls {
            margin-bottom: 10px;
        }

        .status-info {
            background-color: #f0f8ff;
            border: 1px solid #337ab7;
            padding: 12px;
            margin: 12px 0;
            border-radius: 4px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 5px 0;
        }

        .status-belum {
            background-color: #d9edf7;
            color: #31708f;
        }

        .status-diajukan {
            background-color: #fcf8e3;
            color: #8a6d3b;
        }

        .status-disetujui {
            background-color: #dff0d8;
            color: #3c763d;
        }

        .status-ditolak {
            background-color: #f2dede;
            color: #a94442;
        }

        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        @media print {

            html,
            body {
                height: auto;
            }

            body {
                font-size: 11px;
                -webkit-print-color-adjust: exact;
            }

            .print-controls,
            .status-info,
            .info-bottom {
                display: none !important;
            }

            .container {
                max-width: 100%;
                margin: 0;
                padding: 6px;
            }

            table,
            th,
            td {
                font-size: 11px;
            }

            .rincian-table th,
            .rincian-table td {
                padding: 4px;
            }

            .rincian-table tr,
            .rincian-table,
            .header,
            .doc-box,
            .director-approval {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                -webkit-column-break-inside: avoid !important;
                -webkit-page-break-inside: avoid !important;
            }

            .director-approval .signature {
                height: 100px;
            }

            .info-bottom {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="print-controls">
            <a href="perjalanan_dinas_list.php"
                style="text-decoration:none;padding:6px 12px;background:#f5f5f5;border:1px solid #ccc;border-radius:3px;color:#333;margin-right:8px">←
                Kembali</a>
            <button onclick="window.print()"
                style="padding:6px 12px;background:#337ab7;color:white;border:none;border-radius:3px;cursor:pointer">Cetak
                / Print</button>
        </div>

        <?php if ($submission): ?>
            <div class="status-info">
                <strong>Status Pengajuan:</strong>
                <?php
                $status = $submission['status'];
                $status_class = 'status-belum';
                if ($status == 'DIAJUKAN')
                    $status_class = 'status-diajukan';
                elseif ($status == 'DISETUJUI')
                    $status_class = 'status-disetujui';
                elseif ($status == 'DITOLAK')
                    $status_class = 'status-ditolak';
                ?>
                <span class="status-badge <?php echo $status_class; ?>">
                    <?php echo htmlspecialchars($status); ?>
                </span>
                <br>
                <small>
                    Diajukan oleh: <strong><?php echo htmlspecialchars($submission['pengaju']); ?></strong>
                    pada <?php echo date('d-m-Y H:i', strtotime($submission['tanggal_pengajuan'])); ?>
                    <?php if (!empty($submission['catatan'])): ?>
                        <br>Catatan: <?php echo htmlspecialchars($submission['catatan']); ?>
                    <?php endif; ?>
                </small>
            </div>
        <?php endif; ?>

        <div class="header">
            <div class="logo">
                <img src="foto/logo-dua.webp" alt="Logo">
            </div>
            <div class="title">
                FORM ANGGARAN<br>
                PERJALANAN BISNIS (DINAS)
            </div>
            <div class="doc-box">
                <div><strong>No. Dokumen:</strong> <?php echo htmlspecialchars($data['no_dokumen']); ?></div>
                <div><strong>Revisi:</strong> <?php echo htmlspecialchars($data['revisi']); ?></div>
                <div><strong>Tanggal:</strong> <?php echo date('d-m-Y', strtotime($data['tanggal_dokumen'])); ?></div>
            </div>
        </div>

        <table class="form">
            <tr>
                <td style="width:20%">Nama</td>
                <td style="width:30%">: <?php echo htmlspecialchars($data['nama']); ?></td>
                <td style="width:20%">Departemen</td>
                <td>: <?php echo htmlspecialchars($data['departemen']); ?></td>
            </tr>
            <tr>
                <td>Tanggal Perjalanan</td>
                <td>: <?php echo htmlspecialchars($data['tanggal_perjalanan']); ?></td>
                <td>Jumlah Hari</td>
                <td>: <?php echo htmlspecialchars($data['jumlah_hari']); ?> hari</td>
            </tr>
            <tr>
                <td>Kota Asal</td>
                <td>: <?php echo htmlspecialchars($data['kota_asal']); ?></td>
                <td>Kota Tujuan</td>
                <td>: <?php echo htmlspecialchars($data['kota_tujuan']); ?></td>
            </tr>
            <tr>
                <td>Tujuan</td>
                <td colspan="3">: <?php echo nl2br(htmlspecialchars($data['tujuan'])); ?></td>
            </tr>
        </table>

        <table class="rincian-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width:8%;text-align:center">NO</th>
                    <th rowspan="2" style="width:22%;text-align:center">KETERANGAN</th>
                    <th colspan="4" style="text-align:center">ANGGARAN</th>
                    <th rowspan="2" style="width:15%;text-align:center">KETERANGAN</th>
                </tr>
                <tr>
                    <th style="width:12%;text-align:center">NOMINAL</th>
                    <th style="width:8%;text-align:center">QTY</th>
                    <th style="width:12%;text-align:center">PERKIRAAN</th>
                    <th style="width:12%;text-align:center">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rincian_items)): ?>
                    <?php foreach ($rincian_items as $item): ?>
                        <tr>
                            <td style="text-align:center"><?php echo htmlspecialchars($item['nomor']); ?></td>
                            <td><?php echo htmlspecialchars($item['ket']); ?></td>
                            <td style="text-align:right">
                                <?php 
                                // Show nominal if set, otherwise show dash
                                if (!empty($item['nominal']) && $item['nominal'] != '0') {
                                    echo number_format((float)$item['nominal'], 0, ',', '.');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td style="text-align:center"><?php echo htmlspecialchars($item['qty']); ?></td>
                            <td style="text-align:right">
                                <?php echo number_format((float)$item['perkiraan'], 0, ',', '.'); ?>
                            </td>
                            <td style="text-align:right">
                                <?php
                                // Calculate total from nominal (if set) or perkiraan
                                $nilai = !empty($item['nominal']) && $item['nominal'] != '0' ? 
                                    (float)$item['nominal'] : (float)$item['perkiraan'];
                                echo number_format($nilai * (float)$item['qty'], 0, ',', '.');
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['keterangan']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center">Tidak ada rincian anggaran</td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="5" style="text-align:right;font-weight:bold">BUDGET TOTAL:</td>
                    <td style="text-align:right;font-weight:bold">
                        <?php echo number_format((float)$data['budget_total'], 0, ',', '.'); ?>
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top:15px; font-size:12px">
            <strong>Rekening (Transfer):</strong><br>
            Trf Ke rek. Mandiri an. Auliya Nurul Haqim Acc. 60012166181
        </div>

        <div style="margin-top:15px; font-size:12px">
            <strong>Note:</strong>
            <ol style="margin:5px 0 0 20px; padding:0">
                <li>Jika sudah selesai harap diserahkan maksimal H+3 hari dari keberangkatan untuk check poin sebelum
                    pembayaran</li>
                <li>Jika sudah selesai harap membawa struk dan nota yang ada</li>
                <li>Jika diperlukan penambahan biaya tolong diinfokan dahulu ke Manager HRD dan Finance</li>
            </ol>
        </div>

        <table style="width:100%; margin-top:20px; border:0; table-layout:fixed;">
            <tr>
                <td style="width:25%; text-align:center; border:0">Diusulkan Oleh,</td>
                <td style="width:25%; text-align:center; border:0">Mengetahui HRGA Manager,</td>
                <td style="width:25%; text-align:center; border:0">Mengetahui FA Manager,</td>
            </tr>
            <tr>
                <td class="signature" style="border:0; height:80px"></td>
                <td class="signature" style="border:0; height:80px"></td>
                <td class="signature" style="border:0; height:80px"></td>
            </tr>
            <tr>
                <td style="text-align:center; border:0; font-size:11px">Nama :
                    <?php echo htmlspecialchars($data['nama']); ?></td>
                <td style="text-align:center; border:0; font-size:11px">Nama : Auliya Nurul Haqim</td>
                <td style="text-align:center; border:0; font-size:11px">Nama : A. Arief Ananto</td>
            </tr>
            <tr>
                <td style="text-align:center; border:0; font-size:11px">Tanggal :
                    <?php echo date('d-m-Y', strtotime($data['tanggal_dokumen'])); ?></td>
                <td style="text-align:center; border:0; font-size:11px">Tanggal :</td>
                <td style="text-align:center; border:0; font-size:11px">Tanggal :</td>
            </tr>
        </table>

        <div class="director-approval" style="margin-top:18px; text-align:center">
            <div style="font-weight:600;">Disetujui Oleh Direktur,</div>
            <div class="signature" style="height:90px; margin-top:6px"></div>
            <div style="margin-top:6px; font-size:11px">Nama : Lucky Hafiansyah</div>
            <div style="font-size:11px">Tanggal : </div>
        </div>

        <!-- <div style="margin-top:20px; text-align:center">
        <div class="info-bottom" style="margin-top:20px; text-align:center">
            <p style="font-size:11px;color:#666">Detail perjalanan dinas yang tersimpan di sistem.</p>
        </div>
        </div> -->
    </div>
</body>
                    
</html>