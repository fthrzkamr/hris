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
$approval_direktur = $submission['approval_direktur'] ?? null;

// Determine what can be done
$can_approve_hr = ($status == 'DIAJUKAN' && empty($approval_hr));
$can_approve_direktur = ($status == 'APPROVED_HR' && $approval_hr == 'APPROVED' && empty($approval_direktur));
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

    .nominal-input {
        width: 150px;
    }
</style>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                <i class="fa fa-check-square-o"></i> Approve Perjalanan Dinas
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

                    <h4><i class="fa fa-list"></i> Rincian Anggaran</h4>
                    <table class="table table-bordered rincian-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 20%;">Keterangan</th>
                                <th style="width: 15%;">Perkiraan</th>
                                <th style="width: 10%;">Qty</th>
                                <th style="width: 15%;">Subtotal Perkiraan</th>
                                <th style="width: 15%;">Nominal Final</th>
                                <th style="width: 20%;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($rincian_items)): ?>
                                <?php foreach ($rincian_items as $item): ?>
                                    <tr>
                                        <td class="text-center"><?php echo htmlspecialchars($item['nomor']); ?></td>
                                        <td><?php echo htmlspecialchars($item['ket']); ?></td>
                                        <td class="text-right">Rp <?php echo number_format((float)$item['perkiraan'], 0, ',', '.'); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($item['qty']); ?></td>
                                        <td class="text-right">Rp <?php echo number_format((float)$item['perkiraan'] * (float)$item['qty'], 0, ',', '.'); ?></td>
                                        <td class="text-right">
                                            <?php if (!empty($item['nominal']) && $item['nominal'] != '0'): ?>
                                                <strong>Rp <?php echo number_format((float)$item['nominal'], 0, ',', '.'); ?></strong>
                                            <?php else: ?>
                                                <em class="text-muted">Belum diisi</em>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['keterangan']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr style="background-color: #f5f5f5; font-weight: bold;">
                                <td colspan="4" class="text-right">BUDGET TOTAL (Perkiraan):</td>
                                <td class="text-right">Rp <?php echo number_format((float)$data['budget_total'], 0, ',', '.'); ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- HR APPROVAL FORM -->
            <?php if ($can_approve_hr): ?>
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <i class="fa fa-check-circle"></i> Approval HR - Input Nominal Akhir
                    </div>
                    <div class="panel-body">
                        <form method="post" action="perjalanan_dinas_approve_update.php" id="formApprovalHR">
                            <input type="hidden" name="id_perjalanan" value="<?php echo $id; ?>">
                            <input type="hidden" name="approval_type" value="hr">

                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> Silakan isi nominal akhir untuk setiap item anggaran, lalu pilih Approve atau Reject.
                            </div>

                            <table class="table table-bordered">
                                <thead style="background-color: #5cb85c; color: white;">
                                    <tr>
                                        <th style="width: 5%;">No</th>
                                        <th style="width: 25%;">Keterangan</th>
                                        <th style="width: 15%;">Perkiraan</th>
                                        <th style="width: 10%;">Qty</th>
                                        <th style="width: 20%;">Nominal Akhir (Rp)</th>
                                        <th style="width: 15%;">Total</th>
                                        <th style="width: 10%;">Ket</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rincian_items as $item): ?>
                                        <tr>
                                            <td class="text-center"><?php echo htmlspecialchars($item['nomor']); ?></td>
                                            <td><?php echo htmlspecialchars($item['ket']); ?></td>
                                            <td class="text-right">Rp <?php echo number_format((float)$item['perkiraan'], 0, ',', '.'); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($item['qty']); ?></td>
                                            <td>
                                                <input type="hidden" name="rincian_id[]" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="qty[]" value="<?php echo $item['qty']; ?>">
                                                <input type="number" name="nominal[]" class="form-control nominal-input nominal-calc" 
                                                       value="<?php echo $item['perkiraan']; ?>" min="0" step="1" required>
                                            </td>
                                            <td class="text-right total-display">Rp 0</td>
                                            <td>
                                                <input type="text" name="keterangan[]" class="form-control" 
                                                       value="<?php echo htmlspecialchars($item['keterangan']); ?>">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr style="background-color: #f5f5f5; font-weight: bold;">
                                        <td colspan="5" class="text-right">GRAND TOTAL:</td>
                                        <td class="text-right" id="grandTotalDisplay">Rp 0</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="form-group">
                                <label>Catatan HR:</label>
                                <textarea name="catatan_hr" class="form-control" rows="3" placeholder="Catatan tambahan (opsional)"></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" name="decision" value="APPROVED" class="btn btn-success btn-lg">
                                    <i class="fa fa-check"></i> Approve (HR)
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
            <?php endif; ?>

            <!-- DIRECTOR APPROVAL FORM -->
            <?php if ($can_approve_direktur): ?>
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <i class="fa fa-check-circle"></i> Approval Direktur
                    </div>
                    <div class="panel-body">
                        <div class="alert alert-success">
                            <strong>HR telah menyetujui dengan nominal final.</strong><br>
                            Approved oleh: <?php echo htmlspecialchars($submission['approver_hr']); ?><br>
                            Tanggal: <?php echo date('d-m-Y H:i', strtotime($submission['tanggal_approval_hr'])); ?>
                            <?php if (!empty($submission['catatan_hr'])): ?>
                                <br>Catatan: <?php echo nl2br(htmlspecialchars($submission['catatan_hr'])); ?>
                            <?php endif; ?>
                        </div>

                        <h4>Nominal Final yang Disetujui HR:</h4>
                        <table class="table table-bordered">
                            <thead style="background-color: #f0ad4e; color: white;">
                                <tr>
                                    <th>No</th>
                                    <th>Keterangan</th>
                                    <th>Qty</th>
                                    <th>Nominal Akhir</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $grand_total_dir = 0;
                                foreach ($rincian_items as $item): 
                                    $total_line = (float)$item['nominal'] * (float)$item['qty'];
                                    $grand_total_dir += $total_line;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['nomor']); ?></td>
                                        <td><?php echo htmlspecialchars($item['ket']); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($item['qty']); ?></td>
                                        <td class="text-right">Rp <?php echo number_format((float)$item['nominal'], 0, ',', '.'); ?></td>
                                        <td class="text-right">Rp <?php echo number_format((float)$total_line, 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="background-color: #f5f5f5; font-weight: bold;">
                                    <td colspan="4" class="text-right">GRAND TOTAL:</td>
                                    <td class="text-right">Rp <?php echo number_format($grand_total_dir, 0, ',', '.'); ?></td>
                                </tr>
                            </tbody>
                        </table>

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
            <?php endif; ?>

            <?php if (!$can_approve_hr && !$can_approve_direktur): ?>
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i> Pengajuan ini tidak dapat diproses saat ini. 
                    Status: <strong><?php echo htmlspecialchars($status); ?></strong>
                </div>
                <a href="perjalanan_dinas_list.php" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Kembali ke Daftar
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Calculate totals when nominal changes
    function calculateTotals() {
        var grandTotal = 0;
        
        $('.nominal-calc').each(function(index) {
            var nominal = parseFloat($(this).val()) || 0;
            var qty = parseFloat($('input[name="qty[]"]').eq(index).val()) || 0;
            var total = nominal * qty;
            
            // Update row total
            $('.total-display').eq(index).text('Rp ' + total.toLocaleString('id-ID'));
            
            grandTotal += total;
        });
        
        // Update grand total
        $('#grandTotalDisplay').text('Rp ' + grandTotal.toLocaleString('id-ID'));
    }
    
    // Calculate on page load
    calculateTotals();
    
    // Recalculate when nominal changes
    $('.nominal-calc').on('input', calculateTotals);
    
    // Confirm before submit
    $('#formApprovalHR').on('submit', function(e) {
        var decision = $(document.activeElement).val();
        var message = decision === 'APPROVED' ? 
            'Anda akan menyetujui pengajuan ini sebagai HR. Lanjutkan?' : 
            'Anda akan menolak pengajuan ini. Lanjutkan?';
        
        if (!confirm(message)) {
            e.preventDefault();
        }
    });
});
</script>

<?php include("layout_bottom.php"); ?>
