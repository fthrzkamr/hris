<?php
include("sess_check.php");
$pagedesc = 'Approve Perjalanan Dinas';
$menuparent = 'perjalanan_dinas';
include("layout_top.php");

// DB connection
include __DIR__ . '/dist/config/koneksi.php';

// Get ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo '<div class="alert alert-danger">ID tidak valid.</div>';
    include("layout_bottom.php");
    exit;
}

// Ensure approval columns exist - check first to avoid errors
$checkColumns = mysqli_query($conn, "SHOW COLUMNS FROM perjalanan_pengajuan LIKE 'approval_hr'");
if ($checkColumns && mysqli_num_rows($checkColumns) == 0) {
    // Columns don't exist, add them
    $alterQueries = [
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN approval_hr VARCHAR(50)",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN approver_hr VARCHAR(100)",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN tanggal_approval_hr DATETIME",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN catatan_hr TEXT",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN approval_direktur VARCHAR(50)",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN approver_direktur VARCHAR(100)",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN tanggal_approval_direktur DATETIME",
        "ALTER TABLE perjalanan_pengajuan ADD COLUMN catatan_direktur TEXT"
    ];
    
    foreach ($alterQueries as $query) {
        @mysqli_query($conn, $query); // Suppress errors if column already exists
    }
}

// Fetch main record
$stmt = mysqli_prepare($conn, "SELECT * FROM perjalanan_dinas WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo '<div class="alert alert-danger">Data tidak ditemukan.</div>';
    include("layout_bottom.php");
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

if (!$submission) {
    echo '<div class="alert alert-danger">Data pengajuan tidak ditemukan.</div>';
    include("layout_bottom.php");
    exit;
}

$status = $submission['status'];
$approval_hr = $submission['approval_hr'] ?? null;
$approval_manager_hr = $submission['approval_manager_hr'] ?? null;
$approval_direktur = $submission['approval_direktur'] ?? null;

// Direktur can only approve if status is APPROVED_MANAGER_HR and Manager HR has approved
$can_approve_direktur = ($status == 'APPROVED_MANAGER_HR' && $approval_manager_hr == 'APPROVED' && empty($approval_direktur));
?>

<style>
    .info-box {
        background-color: #f0f8ff;
        border: 1px solid #337ab7;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }

    .approval-section {
        background-color: #f9f9f9;
        border: 2px solid #5cb85c;
        padding: 20px;
        margin: 20px 0;
        border-radius: 5px;
    }

    .approval-section.rejected {
        border-color: #d9534f;
    }

    .form-control-inline {
        display: inline-block;
        width: auto;
    }

    table.detail-table {
        width: 100%;
        margin-bottom: 15px;
    }

    table.detail-table td {
        padding: 8px;
        border: 1px solid #ddd;
    }

    table.detail-table td:first-child {
        font-weight: bold;
        width: 200px;
        background-color: #f5f5f5;
    }

    .rincian-table {
        width: 100%;
        margin-top: 15px;
    }

    .rincian-table th,
    .rincian-table td {
        padding: 8px;
        border: 1px solid #ddd;
    }

    .rincian-table thead {
        background-color: #337ab7;
        color: white;
    }
</style>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                <i class="fa fa-check-square-o"></i> Approve Perjalanan Dinas (Direktur)
            </h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa fa-info-circle"></i> Informasi Pengajuan
                </div>
                <div class="panel-body">
                    <div class="info-box">
                        <strong>Status:</strong> <?php echo htmlspecialchars($status); ?><br>
                        <strong>Diajukan oleh:</strong> <?php echo htmlspecialchars($submission['pengaju']); ?><br>
                        <strong>Tanggal Pengajuan:</strong> <?php echo date('d-m-Y H:i', strtotime($submission['tanggal_pengajuan'])); ?>
                    </div>

                    <table class="detail-table">
                        <tr>
                            <td>No. Dokumen</td>
                            <td><?php echo htmlspecialchars($data['no_dokumen']); ?></td>
                        </tr>
                        <tr>
                            <td>Nama</td>
                            <td><?php echo htmlspecialchars($data['nama']); ?></td>
                        </tr>
                        <tr>
                            <td>NPP</td>
                            <td><?php echo htmlspecialchars($data['npp']); ?></td>
                        </tr>
                        <tr>
                            <td>Departemen</td>
                            <td><?php echo htmlspecialchars($data['departemen']); ?></td>
                        </tr>
                        <tr>
                            <td>Tanggal Perjalanan</td>
                            <td><?php echo $data['tanggal_perjalanan'] ? htmlspecialchars($data['tanggal_perjalanan']) : '<em>Fleksibel</em>'; ?></td>
                        </tr>
                        <tr>
                            <td>Jumlah Hari</td>
                            <td><?php echo htmlspecialchars($data['jumlah_hari']); ?> hari</td>
                        </tr>
                        <tr>
                            <td>Kota Asal - Tujuan</td>
                            <td><?php echo htmlspecialchars($data['kota_asal']); ?> → <?php echo htmlspecialchars($data['kota_tujuan']); ?></td>
                        </tr>
                        <tr>
                            <td>Tujuan</td>
                            <td><?php echo nl2br(htmlspecialchars($data['tujuan'])); ?></td>
                        </tr>
                    </table>

                    <?php if ($approval_hr == 'APPROVED'): ?>
                        <div class="alert alert-success">
                            <strong><i class="fa fa-check-circle"></i> HR telah menyetujui</strong><br>
                            Approved oleh: <?php echo htmlspecialchars($submission['approver_hr']); ?><br>
                            Tanggal: <?php echo date('d-m-Y H:i', strtotime($submission['tanggal_approval_hr'])); ?>
                            <?php if (!empty($submission['catatan_hr'])): ?>
                                <br>Catatan: <?php echo nl2br(htmlspecialchars($submission['catatan_hr'])); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <h4><i class="fa fa-list"></i> Rincian Anggaran (Disetujui HR)</h4>
                    <table class="table table-bordered rincian-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 20%;">Keterangan</th>
                                <th style="width: 12%;">Perkiraan</th>
                                <th style="width: 8%;">Qty</th>
                                <th style="width: 12%;">Nominal Final</th>
                                <th style="width: 12%;">Total</th>
                                <th style="width: 21%;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total = 0;
                            if (!empty($rincian_items)): 
                            ?>
                                <?php foreach ($rincian_items as $item): 
                                    $total_line = (float)$item['nominal'] * (float)$item['qty'];
                                    $grand_total += $total_line;
                                ?>
                                    <tr>
                                        <td class="text-center"><?php echo htmlspecialchars($item['nomor']); ?></td>
                                        <td><?php echo htmlspecialchars($item['ket']); ?></td>
                                        <td class="text-right">Rp <?php echo number_format((float)$item['perkiraan'], 0, ',', '.'); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($item['qty']); ?></td>
                                        <td class="text-right"><strong>Rp <?php echo number_format((float)$item['nominal'], 0, ',', '.'); ?></strong></td>
                                        <td class="text-right"><strong>Rp <?php echo number_format($total_line, 0, ',', '.'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['keterangan']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr style="background-color: #f5f5f5; font-weight: bold;">
                                <td colspan="5" class="text-right">GRAND TOTAL:</td>
                                <td class="text-right">Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- DIRECTOR APPROVAL FORM -->
            <?php if ($can_approve_direktur): ?>
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <i class="fa fa-check-circle"></i> Keputusan Approval Direktur
                    </div>
                    <div class="panel-body">
                        <form method="post" action="perjalanan_dinas_approve_update.php">
                            <input type="hidden" name="id_perjalanan" value="<?php echo $id; ?>">
                            <input type="hidden" name="approval_type" value="direktur">

                            <div class="form-group">
                                <label>Catatan Direktur:</label>
                                <textarea name="catatan_direktur" class="form-control" rows="3" placeholder="Catatan tambahan (opsional)"></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" name="decision" value="APPROVED" class="btn btn-success btn-lg">
                                    <i class="fa fa-check"></i> Approve (Direktur)
                                </button>
                                <button type="submit" name="decision" value="REJECTED" class="btn btn-danger btn-lg">
                                    <i class="fa fa-times"></i> Reject
                                </button>
                                <a href="perjalanan_dinas_list.php" class="btn btn-default btn-lg">
                                    <i class="fa fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i> 
                    <?php if ($status == 'DISETUJUI'): ?>
                        Pengajuan ini sudah disetujui oleh Direktur.
                    <?php elseif ($status == 'DITOLAK'): ?>
                        Pengajuan ini sudah ditolak.
                    <?php else: ?>
                        Pengajuan ini belum bisa diproses. Status: <strong><?php echo htmlspecialchars($status); ?></strong>
                    <?php endif; ?>
                </div>
                <a href="perjalanan_dinas_list.php" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Kembali ke Daftar
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include("layout_bottom.php"); ?>
