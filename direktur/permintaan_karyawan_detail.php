<?php
include("sess_check.php");
$pagedesc = 'Review Permintaan Karyawan (Direktur)';
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

// Check if can approve (status = DISETUJUI dan status_app_direktur masih kosong)
$can_approve = ($submission && $submission['status'] === 'DISETUJUI' && empty($submission['status_app_direktur']));
?>
<style>
    .document-header {
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 20px;
        background-color: #f9f9f9;
    }

    .info-table {
        width: 100%;
        margin-bottom: 15px;
    }

    .info-table td {
        padding: 8px;
        border: 1px solid #ddd;
    }

    .info-table td:first-child {
        font-weight: bold;
        width: 25%;
        background-color: #f5f5f5;
    }

    .section-title {
        background-color: #337ab7;
        color: white;
        padding: 10px;
        margin-top: 20px;
        margin-bottom: 10px;
        font-weight: bold;
    }

    .duties-list {
        list-style: decimal;
        padding-left: 20px;
    }

    .duties-list li {
        margin-bottom: 8px;
        padding: 5px;
        border-bottom: 1px solid #eee;
    }

    .status-info {
        background-color: #dff0d8;
        border: 1px solid #d6e9c6;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }

    .action-buttons {
        margin-top: 20px;
        padding: 15px;
        background-color: #f5f5f5;
        border-radius: 4px;
    }

    .approval-form {
        margin-top: 20px;
    }
</style>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Review Permintaan Karyawan (Direktur)</h1>
        </div>
    </div>

    <?php include("layout_alert.php"); ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-file-text"></i> Detail Permintaan Karyawan
                    <div class="pull-right">
                        <a href="permintaan_karyawan_list.php" class="btn btn-xs btn-default">
                            <i class="fa fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="panel-body">
                    
                    <?php if ($submission): ?>
                    <div class="status-info">
                        <strong><i class="fa fa-check-circle"></i> Status Manager HR:</strong>
                        <span class="label label-success"><?php echo htmlspecialchars($submission['status']); ?></span>
                        <br>
                        Diajukan oleh: <strong><?php echo htmlspecialchars($submission['pengaju']); ?></strong> 
                        pada <?php echo date('d-m-Y H:i', strtotime($submission['tanggal_pengajuan'])); ?>
                        <?php if (!empty($submission['catatan'])): ?>
                            <br><strong>Catatan Manager:</strong> <?php echo htmlspecialchars($submission['catatan']); ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Document Info -->
                    <div class="document-header">
                        <h4 class="text-center" style="margin-top: 0;"><strong>FORM PERMINTAAN KARYAWAN BARU</strong></h4>
                        <div class="row">
                            <div class="col-sm-4">
                                <strong>No. Dokumen:</strong> <?php echo htmlspecialchars($data['no_dokumen']); ?>
                            </div>
                            <div class="col-sm-4">
                                <strong>Revisi:</strong> <?php echo htmlspecialchars($data['revisi']); ?>
                            </div>
                            <div class="col-sm-4">
                                <strong>Tanggal:</strong> <?php echo date('d-m-Y', strtotime($data['tanggal_dokumen'])); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Main Information -->
                    <table class="info-table">
                        <tr>
                            <td>Jabatan</td>
                            <td><?php echo htmlspecialchars($data['jabatan']); ?></td>
                            <td>Unit Kerja</td>
                            <td><?php echo htmlspecialchars($data['unit_kerja'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <td>Tanggal Mulai Bekerja</td>
                            <td colspan="3"><?php echo $data['tgl_mulai'] ? date('d-m-Y', strtotime($data['tgl_mulai'])) : '-'; ?></td>
                        </tr>
                        <tr>
                            <td>Jumlah Dibutuhkan</td>
                            <td><?php echo htmlspecialchars($data['jumlah_dibutuhkan']); ?> orang</td>
                            <td>Untuk</td>
                            <td><?php echo htmlspecialchars($data['untuk']); ?></td>
                        </tr>
                        <tr>
                            <td>Jumlah Karyawan Sekarang</td>
                            <td><?php echo htmlspecialchars($data['jumlah_sekarang']); ?> orang</td>
                            <td>Alasan</td>
                            <td><?php echo htmlspecialchars($data['alasan']); ?></td>
                        </tr>
                    </table>

                    <!-- Job Duties -->
                    <div class="section-title">Job Duties</div>
                    <?php if (!empty($duties)): ?>
                        <ol class="duties-list">
                            <?php foreach ($duties as $duty): ?>
                                <li><?php echo htmlspecialchars($duty); ?></li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <p class="text-muted">Tidak ada tugas pekerjaan yang tercatat.</p>
                    <?php endif; ?>

                    <!-- Requirements -->
                    <div class="section-title">Requirements</div>
                    <table class="info-table">
                        <tr>
                            <td>Jenis Kelamin</td>
                            <td><?php echo htmlspecialchars($data['gender']); ?></td>
                            <td>Usia</td>
                            <td><?php echo htmlspecialchars($data['usia']); ?> tahun</td>
                        </tr>
                        <tr>
                            <td>Pendidikan</td>
                            <td><?php echo htmlspecialchars($data['pendidikan']); ?></td>
                            <td>Jurusan</td>
                            <td><?php echo htmlspecialchars($data['jurusan']); ?></td>
                        </tr>
                        <tr>
                            <td>Pengalaman</td>
                            <td><?php echo htmlspecialchars($data['pengalaman']); ?> tahun</td>
                            <td>Tinggi / Berat</td>
                            <td><?php echo htmlspecialchars($data['tinggi']); ?> cm / <?php echo htmlspecialchars($data['berat']); ?> kg</td>
                        </tr>
                        <tr>
                            <td>Rentang Gaji</td>
                            <td>Rp <?php echo number_format($data['rentang_gaji'], 0, ',', '.'); ?></td>
                            <td>Keahlian dan Kemampuan</td>
                            <td>
                                <?php if (!empty($skills)): ?>
                                    <ol style="margin: 0; padding-left: 20px;">
                                        <?php foreach ($skills as $skill): ?>
                                            <li><?php echo htmlspecialchars($skill); ?></li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($data['lain_lain'])): ?>
                        <tr>
                            <td>Lain-lain</td>
                            <td colspan="3"><?php echo htmlspecialchars($data['lain_lain']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>

                    <?php if ($can_approve): ?>
                    <!-- Approval Form -->
                    <div class="action-buttons">
                        <h4><strong>Approval Direktur</strong></h4>
                        <p class="text-muted">Permintaan ini sudah disetujui oleh Manager HR. Silakan berikan persetujuan final.</p>
                        <form method="POST" action="permintaan_karyawan_approve.php" class="approval-form">
                            <input type="hidden" name="id_permintaan" value="<?php echo $id; ?>">
                            <input type="hidden" name="pengajuan_id" value="<?php echo $submission['id']; ?>">
                            
                            <div class="form-group">
                                <label>Keputusan Direktur:</label>
                                <select name="keputusan" class="form-control" required style="max-width: 300px;">
                                    <option value="">-- Pilih Keputusan --</option>
                                    <option value="DISETUJUI">Setujui</option>
                                    <option value="DITOLAK">Tolak</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Catatan (Opsional):</label>
                                <textarea name="catatan" class="form-control" rows="3" placeholder="Masukkan catatan jika perlu..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check"></i> Kirim Keputusan
                            </button>
                            <a href="permintaan_karyawan_list.php" class="btn btn-default">
                                <i class="fa fa-times"></i> Batal
                            </a>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> 
                        <?php if (!empty($submission['status_app_direktur'])): ?>
                            Permintaan ini sudah diproses oleh Direktur dengan keputusan: <strong><?php echo htmlspecialchars($submission['status_app_direktur']); ?></strong>
                        <?php else: ?>
                            Permintaan ini tidak memerlukan approval atau sudah diproses.
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout_bottom.php'; ?>
