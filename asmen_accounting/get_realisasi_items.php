<?php
include("sess_check.php");
include(__DIR__ . '/../dist/config/koneksi.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo '<div class="alert alert-danger">ID perjalanan tidak valid.</div>';
    exit;
}

$npp_user = isset($sess_mngid) ? $sess_mngid : (isset($sess_admid) ? $sess_admid : '');
$nama_user = isset($sess_mngname) ? $sess_mngname : (isset($sess_admname) ? $sess_admname : 'User');

// Fetch perjalanan (termasuk npp pemohon, untuk fallback item yang belum ditandai npp_karyawan)
$stmt = mysqli_prepare($conn, "SELECT id, npp AS npp_pemohon FROM perjalanan_dinas WHERE id = ?");
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

$allowed_statuses = ['DISETUJUI', 'APPROVED_MANAGER_HR', 'REALISASI_DITOLAK', 'REALISASI_DIAJUKAN', 'REALISASI_VERIFIED_HR'];
if (!in_array($status, $allowed_statuses)) {
    echo '<div class="alert alert-danger">Status perjalanan tidak valid untuk pengajuan realisasi (' . htmlspecialchars($status) . ').</div>';
    exit;
}

// Status realisasi milik user ini sendiri (dilacak per-karyawan, independen dari karyawan lain di trip yang sama)
$stmt_k = mysqli_prepare($conn, "SELECT status_realisasi, catatan_realisasi, verified_by_realisasi, verified_at_realisasi FROM perjalanan_karyawan WHERE perjalanan_id = ? AND npp = ?");
mysqli_stmt_bind_param($stmt_k, 'is', $id, $npp_user);
mysqli_stmt_execute($stmt_k);
$karyawan_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_k));
$status_realisasi_saya = $karyawan_row['status_realisasi'] ?? 'BELUM';
$is_locked = ($status_realisasi_saya === 'DISETUJUI');

// Fetch rincian items MILIK SENDIRI SAJA
// Item dianggap milik user ini jika npp_karyawan = npp user, ATAU npp_karyawan kosong (belum ditandai) DAN user ini adalah pemohon
$stmt_r = mysqli_prepare($conn, "SELECT * FROM perjalanan_rincian WHERE perjalanan_id = ? ORDER BY nomor");
mysqli_stmt_bind_param($stmt_r, 'i', $id);
mysqli_stmt_execute($stmt_r);
$res_r = mysqli_stmt_get_result($stmt_r);
$rincian_items = [];
while ($r = mysqli_fetch_assoc($res_r)) {
    $item_npp = !empty($r['npp_karyawan']) ? trim($r['npp_karyawan']) : $data['npp_pemohon'];
    if ($item_npp === $npp_user) {
        $rincian_items[] = $r;
    }
}

$prefix = '../'; // karena berada di subfolder (it/, managerhr/, direktur/)
?>
<div class="alert alert-info" style="margin-bottom: 12px;">
    <i class="fa fa-user"></i> Laporan realisasi ini untuk: <strong><?php echo htmlspecialchars($nama_user); ?> (<?php echo htmlspecialchars($npp_user); ?>)</strong>.
    Rincian biaya peserta lain pada perjalanan ini dilaporkan sendiri-sendiri oleh masing-masing peserta.
</div>

<?php if ($status_realisasi_saya === 'DISETUJUI'): ?>
    <div class="alert alert-success">
        <i class="fa fa-check-circle"></i> Realisasi Anda sudah <strong>disetujui final</strong> oleh HR
        <?php echo !empty($karyawan_row['verified_by_realisasi']) ? ' (' . htmlspecialchars($karyawan_row['verified_by_realisasi']) . ')' : ''; ?>
        <?php echo !empty($karyawan_row['verified_at_realisasi']) ? ' pada ' . date('d-m-Y H:i', strtotime($karyawan_row['verified_at_realisasi'])) : ''; ?>.
        Data di bawah ini tidak bisa diubah lagi.
    </div>
<?php elseif ($status_realisasi_saya === 'DITOLAK'): ?>
    <div class="alert alert-danger">
        <i class="fa fa-times-circle"></i> Realisasi Anda <strong>ditolak</strong> oleh HR.
        <?php if (!empty($karyawan_row['catatan_realisasi'])): ?>
            <br>Alasan: <?php echo htmlspecialchars($karyawan_row['catatan_realisasi']); ?>
        <?php endif; ?>
        Silakan perbaiki dan kirim ulang.
    </div>
<?php elseif ($status_realisasi_saya === 'DIAJUKAN'): ?>
    <div class="alert alert-warning"><i class="fa fa-clock-o"></i> Laporan realisasi Anda sedang menunggu review HR. Anda masih bisa mengubahnya sampai disetujui final.</div>
<?php endif; ?>

<?php if (empty($rincian_items)): ?>
    <div class="alert alert-warning">Tidak ada rincian biaya yang terdaftar atas nama Anda pada perjalanan dinas ini.</div>
<?php else: ?>
<p>Masukkan nominal biaya riil (aktual) yang dikeluarkan serta unggah bukti foto kuitansi/nota per item rincian.</p>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr class="info" style="background-color: #d9edf7; color: #31708f;">
                <th>Keterangan</th>
                <th style="width:10%; text-align:center">Qty</th>
                <th style="width:25%; text-align:right">Total Budget</th>
                <th style="width:30%; text-align:right">Total Realisasi (Aktual)</th>
                <th style="width:35%">Upload Foto Bukti Nota</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rincian_items as $item):
                $qty_item = max(1, (float)($item['qty'] ?? 1));
                $total_budget_item = (float)($item['nominal'] ?? 0) * $qty_item;
                $total_realisasi_default = (!empty($item['total_realisasi']) && (float)$item['total_realisasi'] > 0) ? (float)$item['total_realisasi'] : $total_budget_item;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['ket']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($item['qty']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($total_budget_item, 0, ',', '.'); ?></td>
                    <td>
                        <div class="input-group">
                            <span class="input-group-addon">Rp</span>
                            <input type="text" name="nominal_realisasi[<?php echo $item['id']; ?>]"
                                   value="<?php echo number_format($total_realisasi_default, 0, ',', '.'); ?>"
                                   class="form-control nominal-input" style="text-align:right" required <?php echo $is_locked ? 'readonly disabled' : ''; ?>>
                        </div>
                    </td>
                    <td>
                        <?php $sudah_dibelikan = !empty($item['dibelikan_kantor']); ?>
                        <label style="font-weight:400; font-size:12px; margin-bottom:6px; display:block;">
                            <input type="checkbox" name="dibelikan_kantor[<?php echo $item['id']; ?>]" value="1" class="chk-dibelikan-kantor" data-target="file-<?php echo $item['id']; ?>" <?php echo $sudah_dibelikan ? 'checked' : ''; ?> <?php echo $is_locked ? 'disabled' : ''; ?>>
                            Sudah dibelikan/dibayar kantor (tanpa nota)
                        </label>
                        <?php if (!$is_locked): ?>
                        <input type="file" id="file-<?php echo $item['id']; ?>" name="bukti_realisasi[<?php echo $item['id']; ?>][]" multiple accept="image/*" class="form-control" style="<?php echo $sudah_dibelikan ? 'display:none;' : ''; ?>">
                        <?php endif; ?>
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

    $(document).off('change', '.chk-dibelikan-kantor').on('change', '.chk-dibelikan-kantor', function() {
        var target = $('#' + $(this).data('target'));
        if ($(this).is(':checked')) {
            target.hide();
        } else {
            target.show();
        }
    });

    <?php if ($is_locked): ?>
    $('#realisasiModal .modal-footer .btn-success').hide();
    <?php else: ?>
    $('#realisasiModal .modal-footer .btn-success').show();
    <?php endif; ?>
});
</script>
<?php endif; ?>
