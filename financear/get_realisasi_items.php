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
                        <?php $sudah_dibelikan = !empty($item['dibelikan_kantor']); ?>
                        <label style="font-weight:400; font-size:12px; margin-bottom:6px; display:block;">
                            <input type="checkbox" name="dibelikan_kantor[<?php echo $item['id']; ?>]" value="1" class="chk-dibelikan-kantor" data-target="file-<?php echo $item['id']; ?>" <?php echo $sudah_dibelikan ? 'checked' : ''; ?>>
                            Sudah dibelikan/dibayar kantor (tanpa nota)
                        </label>
                        <input type="file" id="file-<?php echo $item['id']; ?>" name="bukti_realisasi[<?php echo $item['id']; ?>][]" multiple accept="image/*" class="form-control" style="<?php echo $sudah_dibelikan ? 'display:none;' : ''; ?>">
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
});
</script>
<?php endif; ?>
