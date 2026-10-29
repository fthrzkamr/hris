<?php
include("sess_check.php");

// Check if user is Manager or Leader
if($sess_jabatan !== 'Manager' && $sess_jabatan !== 'Leader') {
    header("location: index.php?error=access_denied");
    exit();
}

// DB connection
include("dist/config/koneksi.php");

// Get ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo '<div class="alert alert-danger">ID tidak valid.</div>';
    exit;
}

// Fetch main record - only allow viewing own records
$stmt = mysqli_prepare($conn, "SELECT * FROM permintaan_karyawan WHERE id = ? AND created_by = ?");
mysqli_stmt_bind_param($stmt, 'is', $id, $sess_mngid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo '<div class="alert alert-danger">Data tidak ditemukan atau Anda tidak memiliki akses.</div>';
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

        @media print {
            .print-controls {
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
                <td style="width:20%">Unit Kerja</td>
                <td>: <?php echo htmlspecialchars($data['unit_kerja'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td>Tanggal Mulai Bekerja</td>
                <td colspan="3">: <?php echo $data['tgl_mulai'] ? date('d-m-Y', strtotime($data['tgl_mulai'])) : '-'; ?></td>
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
                <td>: <?php echo htmlspecialchars($data['usia']); ?></td>
            </tr>
            <tr>
                <td>Pendidikan</td>
                <td>: <?php echo htmlspecialchars($data['pendidikan']); ?></td>
                <td>Jurusan</td>
                <td>: <?php echo htmlspecialchars($data['jurusan']); ?></td>
            </tr>
            <tr>
                <td>Pengalaman</td>
                <td>: <?php echo htmlspecialchars($data['pengalaman']); ?></td>
                <td>Tinggi dan Berat</td>
                <td>: <?php echo htmlspecialchars($data['tinggi']); ?> cm / <?php echo htmlspecialchars($data['berat']); ?> kg</td>
            </tr>
            <tr>
                <td>Rentang Gaji</td>
                <td>: Rp <?php echo number_format($data['rentang_gaji'], 0, ',', '.'); ?></td>
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

        <table class="form" style="margin-top:12px">
            <tr>
                <th colspan="2" class="center">Informasi Pembuatan</th>
            </tr>
            <tr>
                <td style="width:30%">Dibuat oleh</td>
                <td>: <?php echo htmlspecialchars($sess_mngname); ?> (<?php echo htmlspecialchars($data['created_by']); ?>)</td>
            </tr>
            <tr>
                <td>Tanggal dibuat</td>
                <td>: <?php echo date('d-m-Y H:i:s', strtotime($data['created_at'])); ?></td>
            </tr>
        </table>

        <p class="small" style="margin-top:18px">Form ini menampilkan detail permintaan karyawan baru yang telah tersimpan di sistem.</p>
    </div>
</body>

</html>
