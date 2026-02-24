<?php
include("sess_check.php");
$pagedesc = 'Detail Permintaan Karyawan';
$menuparent = 'approval';
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
$stmt = mysqli_prepare($conn, "SELECT * FROM permintaan_karyawan WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo '<div class="alert alert-danger">Data tidak ditemukan.</div>';
    include 'layout_bottom.php';
    exit;
}

// Fetch job duties
$stmt_duties = mysqli_prepare($conn, "SELECT tugas FROM job_duties_karyawan WHERE permintaan_id = ? ORDER BY nomor_urut");
mysqli_stmt_bind_param($stmt_duties, 'i', $id);
mysqli_stmt_execute($stmt_duties);
$result_duties = mysqli_stmt_get_result($stmt_duties);
$duties = [];
while ($row = mysqli_fetch_assoc($result_duties)) {
    $duties[] = $row['tugas'];
}

// Fetch skills
$stmt_skills = mysqli_prepare($conn, "SELECT skill FROM skills_karyawan WHERE permintaan_id = ? ORDER BY nomor");
mysqli_stmt_bind_param($stmt_skills, 'i', $id);
mysqli_stmt_execute($stmt_skills);
$result_skills = mysqli_stmt_get_result($stmt_skills);
$skills = [];
while ($row = mysqli_fetch_assoc($result_skills)) {
    $skills[] = $row['skill'];
}

// Fetch submission status
$stmt_status = mysqli_prepare($conn, "SELECT * FROM permintaan_pengajuan WHERE id_permintaan = ? ORDER BY id DESC LIMIT 1");
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
    <title>Detail Permintaan Karyawan</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
        }

        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 10px
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .title {
            flex: 1;
            text-align: center;
            font-weight: 700;
            font-size: 22px
        }

        table.form {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px
        }

        table.form td,
        table.form th {
            border: 1px solid #000;
            padding: 6px;
            font-size: 13px
        }

        .duties tr td {
            border: 1px solid #000;
            height: 24px
        }

        .duties-number {
            width: 40px;
            text-align: center
        }

        .center {
            text-align: center
        }

        .small {
            font-size: 12px
        }

        .print-controls {
            margin-bottom: 8px
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

        @media print {
            .print-controls,
            .status-info {
                display: none !important;
            }

            body {
                background: white !important;
            }

            .container {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="print-controls">
            <a href="permintaan_karyawan_list.php" style="text-decoration:none;padding:6px 12px;background:#f5f5f5;border:1px solid #ccc;border-radius:3px;color:#333;margin-right:8px">← Kembali</a>
            <button onclick="window.print()" style="padding:6px 12px;background:#337ab7;color:white;border:none;border-radius:3px;cursor:pointer">Cetak / Print</button>
        </div>

        <?php if ($submission): ?>
        <div class="status-info">
            <strong>Status Pengajuan:</strong>
            <?php 
            $status = $submission['status'];
            $status_class = 'status-belum';
            if ($status == 'DIAJUKAN') $status_class = 'status-diajukan';
            elseif ($status == 'DISETUJUI') $status_class = 'status-disetujui';
            elseif ($status == 'DITOLAK') $status_class = 'status-ditolak';
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
            <div style="width:18%">
                <div style="border:1px solid #000;padding:8px;font-size:12px;text-align:left">
                    <strong>No. Dokumen:</strong> <?php echo htmlspecialchars($data['no_dokumen']); ?><br>
                    <strong>Revisi:</strong> <?php echo htmlspecialchars($data['revisi']); ?><br>
                    <strong>Tanggal Dokumen:</strong> <?php echo date('d-m-Y', strtotime($data['tanggal_dokumen'])); ?>
                </div>
            </div>
            <div class="title">FORM PERMINTAAN KARYAWAN BARU</div>
            <div style="width:18%"></div>
        </div>

        <table class="form">
            <tr>
                <td style="width:20%">Jabatan</td>
                <td style="width:40%">: <?php echo htmlspecialchars($data['jabatan']); ?></td>
                <td style="width:20%">Tanggal Mulai Bekerja</td>
                <td>: <?php echo $data['tgl_mulai'] ? date('d-m-Y', strtotime($data['tgl_mulai'])) : '-'; ?></td>
            </tr>
            <tr>
                <td>Jumlah dibutuhkan</td>
                <td>: <?php echo htmlspecialchars($data['jumlah_dibutuhkan']); ?> orang</td>
                <td>Untuk</td>
                <td>: <?php echo htmlspecialchars($data['untuk']); ?></td>
            </tr>
            <tr>
                <td>Jumlah Karyawan Sekarang</td>
                <td>: <?php echo htmlspecialchars($data['jumlah_sekarang']); ?> orang</td>
                <td>Alasan</td>
                <td>: <?php echo htmlspecialchars($data['alasan']); ?></td>
            </tr>
        </table>

        <table class="form" style="margin-top:18px">
            <tr>
                <th colspan="4" class="center">Job Duties</th>
            </tr>
            <?php if (!empty($duties)): ?>
                <?php foreach ($duties as $idx => $duty): ?>
                <tr class="duties">
                    <td class="duties-number"><?php echo ($idx + 1); ?></td>
                    <td colspan="3"><?php echo htmlspecialchars($duty); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="center small">Tidak ada tugas pekerjaan yang tercatat.</td>
                </tr>
            <?php endif; ?>
        </table>

        <table class="form" style="margin-top:12px">
            <tr>
                <th colspan="4" class="center">Requirements</th>
            </tr>
            <tr>
                <td style="width:18%">Jenis Kelamin</td>
                <td style="width:32%">: <?php echo htmlspecialchars($data['gender']); ?></td>
                <td style="width:18%">Usia</td>
                <td>: <?php echo htmlspecialchars($data['usia']); ?> tahun</td>
            </tr>
            <tr>
                <td>Pendidikan</td>
                <td>: <?php echo htmlspecialchars($data['pendidikan']); ?></td>
                <td>Jurusan</td>
                <td>: <?php echo htmlspecialchars($data['jurusan']); ?></td>
            </tr>
            <tr>
                <td>Pengalaman</td>
                <td>: <?php echo htmlspecialchars($data['pengalaman']); ?> tahun</td>
                <td>Tinggi dan Berat</td>
                <td>: <?php echo htmlspecialchars($data['tinggi']); ?> cm / <?php echo htmlspecialchars($data['berat']); ?> kg</td>
            </tr>
            <tr>
                <td>Rentang Gaji</td>
                <td>: Rp <?php echo htmlspecialchars($data['rentang_gaji']); ?></td>
                <td>Keahlian dan Kemampuan</td>
                <td>:
                    <?php if (!empty($skills)): ?>
                        <?php foreach ($skills as $idx => $skill): ?>
                            <div><?php echo ($idx + 1); ?>. <?php echo htmlspecialchars($skill); ?></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="small">-</div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if (!empty($data['lain_lain'])): ?>
            <tr>
                <td>Lain-lain</td>
                <td colspan="3">: <?php echo htmlspecialchars($data['lain_lain']); ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <p class="small" style="margin-top:18px">Form ini menampilkan detail permintaan karyawan baru yang telah tersimpan di sistem.</p>
    </div>
</body>

</html>
