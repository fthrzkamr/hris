<?php
include("sess_check.php");
include(__DIR__ . '/../dist/config/koneksi.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo '<div class="alert alert-danger">ID perjalanan tidak valid.</div>';
    exit;
}

// Fetch perjalanan
$stmt = mysqli_prepare($conn, "SELECT id FROM perjalanan_dinas WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($res);

if (!$data) {
    echo '<div class="alert alert-danger">Data perjalanan tidak ditemukan.</div>';
    exit;
}

// Get latest status from pengajuan
$stmt_p = mysqli_prepare($conn, "SELECT status FROM perjalanan_pengajuan WHERE id_perjalanan = ? ORDER BY id DESC LIMIT 1");
mysqli_stmt_bind_param($stmt_p, 'i', $id);
mysqli_stmt_execute($stmt_p);
$res_p = mysqli_stmt_get_result($stmt_p);
$pengajuan = mysqli_fetch_assoc($res_p);
$status = $pengajuan ? $pengajuan['status'] : 'BELUM DIAJUKAN';

$allowed_statuses = ['DISETUJUI', 'APPROVED_MANAGER_HR', 'REALISASI_DITOLAK'];
if (!in_array($status, $allowed_statuses)) {
    echo '<div class="alert alert-danger">Status perjalanan tidak valid untuk pengajuan realisasi (' . htmlspecialchars($status) . ').</div>';
    exit;
}

// Fetch rincian items
$stmt_r = mysqli_prepare($conn, "SELECT * FROM perjalanan_rincian WHERE perjalanan_id = ? ORDER BY nomor");
mysqli_stmt_bind_param($stmt_r, 'i', $id);
mysqli_stmt_execute($stmt_r);
$res_r = mysqli_stmt_get_result($stmt_r);
$rincian_items = [];
while ($r = mysqli_fetch_assoc($res_r)) {
    $rincian_items[] = $r;
}

$prefix = '../'; // karena berada di subfolder (it/, managerhr/, direktur/)
?>
<p>Masukkan nominal biaya riil (aktual) yang dikeluarkan serta unggah bukti foto kuitansi/nota per item rincian.</p>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr class="info" style="background-color: #d9edf7; color: #31708f;">
                <th>Keterangan</th>
                <th style="width:10%; text-align:center">Qty</th>
                <th style="width:25%; text-align:right">Nominal Budget</th>
                <th style="width:30%; text-align:right">Nominal Realisasi (Aktual)</th>
                <th style="width:35%">Upload Foto Bukti Nota</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rincian_items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['ket']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($item['qty']); ?></td>
                    <td class="text-right">Rp <?php echo number_format((float)($item['nominal'] ?? 0), 0, ',', '.'); ?></td>
                    <td>
                        <div class="input-group">
                            <span class="input-group-addon">Rp</span>
                            <input type="text" name="nominal_realisasi[<?php echo $item['id']; ?>]" 
                                   value="<?php echo (!empty($item['nominal_realisasi']) && (float)$item['nominal_realisasi'] > 0) ? number_format((float)$item['nominal_realisasi'], 0, ',', '.') : number_format((float)($item['nominal'] ?? 0), 0, ',', '.'); ?>" 
                                   class="form-control nominal-input" style="text-align:right" required>
                        </div>
                    </td>
                    <td>
                        <input type="file" name="bukti_realisasi[<?php echo $item['id']; ?>][]" multiple accept="image/*" class="form-control">
                        <?php if (!empty($item['bukti_realisasi'])): 
                            $buktis = json_decode($item['bukti_realisasi'] ?? '', true) ?: [];
                            if (!empty($buktis)): ?>
                                <div style="margin-top: 5px;">
                                    <small class="text-muted">Bukti foto sebelumnya:</small><br>
                                    <?php foreach ($buktis as $b): ?>
                                        <a href="<?php echo htmlspecialchars($prefix . $b); ?>" target="_blank" style="margin-right:5px;">
                                            <img src="<?php echo htmlspecialchars($prefix . $b); ?>" style="width:30px; height:30px; object-fit:cover; border:1px solid #ccc; border-radius:3px;">
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
$(function() {
    function formatRp(num) {
        num = parseFloat(num) || 0;
        return num.toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }
    function unformat(v) {
        return parseInt((v + '').replace(/\D/g, '')) || 0;
    }
    
    $(document).off('input', '.nominal-input').on('input', '.nominal-input', function() {
        var v = $(this).val().replace(/\D/g, '');
        if (v === '') $(this).val('');
        else $(this).val(formatRp(v));
    });
});
</script>
