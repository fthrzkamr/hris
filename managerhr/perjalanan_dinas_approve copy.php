<?php
include("sess_check.php");
include(__DIR__ . '/../dist/config/koneksi.php');

// Get ID early for POST processing
$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);

// Proses POST (handle approve / revisi / reject) - BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $id > 0) {
    $aksi = $_POST['aksi'];
    $catatan = trim($_POST['catatan'] ?? '');
    $user_nama = $sess_mngname ?? 'Manager HR';
    $user_npp = $sess_mngid ?? null;

    // Ambil data pengajuan terakhir (opsional)
    $stmt_last = mysqli_prepare($conn, "SELECT id, status FROM perjalanan_pengajuan WHERE id_perjalanan = ? ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt_last, 'i', $id);
    mysqli_stmt_execute($stmt_last);
    $res_last = mysqli_stmt_get_result($stmt_last);
    $last = mysqli_fetch_assoc($res_last);

    // Jika approve_with_changes, update rincian sesuai input nominal[] dan catat perubahan
    if ($aksi === 'approve_with_changes' || $aksi === 'approve') {
        $nominals = $_POST['nominal'] ?? [];
        foreach ($nominals as $rid => $val) {
            $rid = intval($rid);
            $val_clean = preg_replace('/[^0-9]/', '', $val);
            $nominal_new = $val_clean === '' ? '0' : $val_clean;

            // ambil rincian lama
            $stmt_r = mysqli_prepare($conn, "SELECT nominal, qty, total, keterangan FROM perjalanan_rincian WHERE id = ?");
            mysqli_stmt_bind_param($stmt_r, 'i', $rid);
            mysqli_stmt_execute($stmt_r);
            $res_r = mysqli_stmt_get_result($stmt_r);
            $old = mysqli_fetch_assoc($res_r);
            if (!$old)
                continue;

            $old_nominal = $old['nominal'] ?? '0';
            $old_total = $old['total'] ?? '0';
            $qty = (float) ($old['qty'] ?? 1);

            // simpan log perubahan jika ada perubahan nominal (untuk audit trail)
            if ($old_nominal !== $nominal_new) {
                $note_log = "Perubahan nominal oleh Manager HR ($user_nama). Nominal lama: Rp " . number_format((float)$old_nominal, 0, ',', '.') . " → Nominal baru: Rp " . number_format((float)$nominal_new, 0, ',', '.') . ". " . ($catatan ? "Catatan: $catatan" : "");
                $ins_log = mysqli_prepare($conn, "INSERT INTO perjalanan_rincian_log (rincian_id, perjalanan_id, old_nominal, old_total, old_keterangan, changed_by, note) VALUES (?,?,?,?,?,?,?)");
                mysqli_stmt_bind_param($ins_log, 'iisssss', $rid, $id, $old_nominal, $old_total, $old['keterangan'], $user_nama, $note_log);
                @mysqli_stmt_execute($ins_log);
            }

            // hitung total baru dan update rincian
            $total_new = (string) ((float) $nominal_new * $qty);
            $upd = mysqli_prepare($conn, "UPDATE perjalanan_rincian SET nominal = ?, total = ?, last_revised_by = ?, last_revised_at = NOW(), revision_count = revision_count + 1 WHERE id = ?");
            mysqli_stmt_bind_param($upd, 'sssi', $nominal_new, $total_new, $user_nama, $rid);
            mysqli_stmt_execute($upd);
        }

        // recalc budget_total di tabel perjalanan_dinas
        $stmt_sum = mysqli_prepare($conn, "SELECT SUM(CAST(REPLACE(total,',','') AS DECIMAL(20,2))) AS s FROM perjalanan_rincian WHERE perjalanan_id = ?");
        mysqli_stmt_bind_param($stmt_sum, 'i', $id);
        mysqli_stmt_execute($stmt_sum);
        $res_sum = mysqli_stmt_get_result($stmt_sum);
        $sum = mysqli_fetch_assoc($res_sum);
        $budget_total = (float) ($sum['s'] ?? 0);
        $upd_budget = mysqli_prepare($conn, "UPDATE perjalanan_dinas SET budget_total = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd_budget, 'di', $budget_total, $id);
        mysqli_stmt_execute($upd_budget);
    }

    // Tentukan status dan approval_manager_hr (hanya Approve atau Reject)
    if ($aksi === 'approve' || $aksi === 'approve_with_changes') {
        $status = 'APPROVED_MANAGER_HR'; // diteruskan ke Direktur
        $approval_manager = 'APPROVED';
    } elseif ($aksi === 'reject') {
        $status = 'DITOLAK';
        $approval_manager = 'REJECTED';
    } else {
        // Default: reject jika aksi tidak dikenali
        $status = 'DITOLAK';
        $approval_manager = 'REJECTED';
    }

    // UPDATE record pengajuan yang sudah ada (jangan insert baru)
    if (!$last || empty($last['id'])) {
        header("Location: perjalanan_dinas_list.php?err=" . urlencode("Data pengajuan tidak ditemukan"));
        exit;
    }

    $pengajuan_id = (int)$last['id'];
    $upd = mysqli_prepare($conn, "UPDATE perjalanan_pengajuan SET approval_manager_hr = ?, approver_manager_hr = ?, tanggal_approval_manager_hr = NOW(), catatan_manager_hr = ?, status = ? WHERE id = ?");
    mysqli_stmt_bind_param($upd, 'ssssi', $approval_manager, $user_nama, $catatan, $status, $pengajuan_id);
    $ok = mysqli_stmt_execute($upd);

    if ($ok) {
        header("Location: perjalanan_dinas_list.php?msg=" . urlencode("Proses approval berhasil: $status"));
        exit;
    } else {
        $err = mysqli_error($conn);
        header("Location: perjalanan_dinas_list.php?err=" . urlencode("Gagal menyimpan approval: $err"));
        exit;
    }
}

// Now safe to include layout (after redirect handling)
$pagedesc = 'Review & Approve Perjalanan Dinas';
$menuparent = 'perjalanan_dinas';
include('layout_top.php');

$allowed_roles = ['managerhr', 'admin']; // sesuaikan pemeriksaan role jika perlu

// Validate ID after layout
if ($id <= 0) {
    echo "<div class='alert alert-danger'>ID perjalanan tidak valid.</div>";
    include 'layout_bottom.php';
    exit;
}

// Ambil data perjalanan & rincian untuk ditampilkan
$stmt = mysqli_prepare($conn, "SELECT * FROM perjalanan_dinas WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($res);
if (!$data) {
    echo "<div class='alert alert-danger'>Data perjalanan tidak ditemukan.</div>";
    include 'layout_bottom.php';
    exit;
}

$stmt_r = mysqli_prepare($conn, "SELECT * FROM perjalanan_rincian WHERE perjalanan_id = ? ORDER BY nomor");
mysqli_stmt_bind_param($stmt_r, 'i', $id);
mysqli_stmt_execute($stmt_r);
$res_r = mysqli_stmt_get_result($stmt_r);
$rincian = [];
while ($r = mysqli_fetch_assoc($res_r))
    $rincian[] = $r;

// ambil pengajuan terakhir
$stmt_p = mysqli_prepare($conn, "SELECT * FROM perjalanan_pengajuan WHERE id_perjalanan = ? ORDER BY id DESC LIMIT 1");
mysqli_stmt_bind_param($stmt_p, 'i', $id);
mysqli_stmt_execute($stmt_p);
$res_p = mysqli_stmt_get_result($stmt_p);
$pengajuan = mysqli_fetch_assoc($res_p);
?>
<style>
    /* Header: logo | title | meta */
    .info-header {
        display: grid;
        grid-template-columns: 90px 1fr 260px;
        gap: 18px;
        align-items: center;
        margin-bottom: 14px;
        background: #fff;
        padding: 15px;
        border-radius: 4px;
    }
    .logo { width:90px; height:60px; display:flex; align-items:center; justify-content:center; }
    .logo img { max-height:50px; width:auto; }
    .doc-title { font-weight:700; color:#2b7ae4; font-size:18px; }
    .doc-meta { text-align:right; font-size:13px; color:#666; }

    /* Two-column info row: details + budget card */
    .info-row { display: grid; grid-template-columns: 1fr 260px; gap: 12px; margin-bottom: 12px; align-items: start; }
    .info-left { }
    .info-right { }

    /* Label/value grid for consistent alignment */
    .kv-grid { display: grid; grid-template-columns: 150px 1fr 150px 1fr; gap:6px 24px; align-items:center; }
    .kv-label { font-weight:600; color:#333; white-space:nowrap; }
    .kv-value { color:#222; }
    .kv-full { grid-column: 1 / -1; margin-top:8px; color:#555; font-size:13px; }

    .budget-card { background:linear-gradient(180deg,#fff,#fbfdff); padding:12px; border-radius:8px; border:1px solid #e3f2fd; text-align:center; }
    .budget-amount { font-size:20px; font-weight:700; color:#2b7ae4; }
    .nominal-field { width:100%; padding:6px 8px; text-align:right; border-radius:4px; border:1px solid #ddd; }
    .actions { display:flex; gap:8px; margin-top:14px; flex-wrap:wrap; align-items:center; }
    .note { margin-top:10px; color:#666; font-size:13px; }
    .select-action { width:260px; }

    @media (max-width:768px) {
        .info-header { grid-template-columns: 1fr; align-items: start; }
        .doc-meta { text-align: left; }
        .info-row { grid-template-columns: 1fr; }
        .info-right { order: 2; }
        .actions { flex-direction:column; }
        .actions .btn, .select-action { width:100%; }
        .kv-grid { grid-template-columns: 120px 1fr; gap:6px 12px; }
        .kv-full { grid-column: 1 / -1; }
    }

    /* Small table tweaks */
    .rincian-table td, .rincian-table th { vertical-align: middle; }
</style>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><?php echo $pagedesc; ?></h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-primary">
                <div class="panel-heading">Review & Approve Anggaran</div>
                <div class="panel-body">
                    <div class="info-header">
                        <div class="logo"><img src="foto/logo-dua.webp" alt="logo"></div>
                        <div class="doc-title">Form Anggaran Perjalanan Dinas</div>
                        <div class="doc-meta">
                            <div><strong>No:</strong> <?php echo htmlspecialchars($data['no_dokumen']); ?></div>
                            <div><strong>Tanggal:</strong> <?php echo date('d-m-Y', strtotime($data['tanggal_dokumen'])); ?></div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-left">
                            <div class="kv-grid">
                                <div class="kv-label">Nama</div><div class="kv-value">: <?php echo htmlspecialchars($data['nama']); ?></div>
                                <div class="kv-label">Departemen</div><div class="kv-value">: <?php echo htmlspecialchars($data['departemen']); ?></div>
                                <div class="kv-label">Tanggal Perjalanan</div><div class="kv-value">: <?php echo htmlspecialchars($data['tanggal_perjalanan']); ?></div>
                                <div class="kv-label">Jumlah Hari</div><div class="kv-value">: <?php echo (int) $data['jumlah_hari']; ?> hari</div>
                                <div class="kv-label">Kota Asal</div><div class="kv-value">: <?php echo htmlspecialchars($data['kota_asal']); ?></div>
                                <div class="kv-label">Kota Tujuan</div><div class="kv-value">: <?php echo htmlspecialchars($data['kota_tujuan']); ?></div>
                            </div>
                            <?php if ($pengajuan): ?>
                            <div class="kv-full">
                                Terakhir diajukan oleh <strong><?php echo htmlspecialchars($pengajuan['pengaju']); ?></strong>
                                pada <?php echo date('d-m-Y H:i', strtotime($pengajuan['tanggal_pengajuan'])); ?>
                                <?php if (!empty($pengajuan['catatan_manager_hr'])): ?>
                                <div style="margin-top:6px"><em>Catatan sebelumnya: <?php echo htmlspecialchars($pengajuan['catatan_manager_hr']); ?></em></div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="info-right">
                            <div class="budget-card">
                                <div style="font-size:13px;color:#666">Budget Saat Ini</div>
                                <div id="budgetDisplay" class="budget-amount">Rp <?php echo number_format((float) $data['budget_total'], 0, ',', '.'); ?></div>
                                <div style="font-size:12px;color:#777;margin-top:6px">Total dihitung otomatis saat nominal diubah</div>
                            </div>
                        </div>
                    </div>

                    <form method="post" id="frmApprove" autocomplete="off">
                        <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered rincian-table">
                                <thead>
                                    <tr>
                                        <th style="width:6%; text-align:center">No</th>
                                        <th>Keterangan</th>
                                        <th style="width:18%; text-align:right">Nominal (input)</th>
                                        <th style="width:8%; text-align:center">Qty</th>
                                        <th style="width:18%; text-align:right">Perkiraan</th>
                                        <th style="width:18%; text-align:right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rincian as $it):
                                        $nominal = (float) $it['nominal'];
                                        $qty = (int) $it['qty'] ?: 1;
                                        $total = $nominal ? $nominal * $qty : ((float) $it['perkiraan'] * $qty);
                                    ?>
                                    <tr data-id="<?php echo (int) $it['id']; ?>">
                                        <td class="text-center"><?php echo (int) $it['nomor']; ?></td>
                                        <td><?php echo htmlspecialchars($it['ket']); ?></td>
                                        <td style="text-align:right">
                                            <input type="text" name="nominal[<?php echo (int) $it['id']; ?>]"
                                                value="<?php echo $nominal ? number_format($nominal, 0, ',', '.') : ''; ?>"
                                                class="nominal-field" data-qty="<?php echo $qty; ?>">
                                        </td>
                                        <td class="text-center"><?php echo $qty; ?></td>
                                        <td style="text-align:right">Rp <?php echo number_format((float) $it['perkiraan'], 0, ',', '.'); ?></td>
                                        <td class="text-right line-total">Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="form-group" style="margin-top:12px">
                            <label>Catatan Manager HR (opsional)</label>
                            <textarea name="catatan" class="form-control" rows="3" placeholder="Tambahkan catatan jika ada perubahan nominal atau catatan lainnya..."><?php echo htmlspecialchars($pengajuan['catatan_manager_hr'] ?? ''); ?></textarea>
                        </div>

                        <div class="actions">
                            <button type="submit" name="aksi" value="approve_with_changes" class="btn btn-success btn-lg">
                                <i class="fa fa-check"></i> Approve & Teruskan ke Direktur
                            </button>
                            <button type="submit" name="aksi" value="reject" class="btn btn-danger btn-lg" onclick="return confirm('Yakin ingin menolak pengajuan ini?');">
                                <i class="fa fa-times"></i> Reject
                            </button>
                            <a href="perjalanan_dinas_list.php" class="btn btn-default btn-lg">Batal / Kembali</a>
                        </div>

                        <div class="note">
                            <strong>Catatan:</strong> Anda dapat mengubah nominal langsung di tabel di atas. Perubahan akan tercatat dalam log sistem untuk audit. Klik "Approve" untuk menyetujui dan meneruskan ke Direktur, atau "Reject" untuk menolak pengajuan.
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
        $(function () {
            function formatRp(num) {
                num = parseFloat(num) || 0;
                return num.toLocaleString('id-ID', { maximumFractionDigits: 0 });
            }
            function unformat(v) { return parseInt((v + '').replace(/\D/g, '')) || 0; }

            function recalcAll() {
                var totalBudget = 0;
                $('.rincian-table tbody tr').each(function () {
                    var qty = parseInt($(this).find('.nominal-field').data('qty')) || 1;
                    var raw = unformat($(this).find('.nominal-field').val());
                    var perkiraanText = $(this).find('td').eq(4).text().replace(/\D/g, '');
                    var lineTotal = raw ? raw * qty : (parseInt(perkiraanText || 0) * qty);
                    $(this).find('.line-total').text('Rp ' + formatRp(lineTotal));
                    totalBudget += lineTotal;
                });
                $('#budgetDisplay').text('Rp ' + formatRp(totalBudget));
            }

            $(document).on('input', '.nominal-field', function () {
                var v = $(this).val().replace(/\D/g, '');
                if (v === '') $(this).val('');
                else $(this).val(formatRp(v));
                recalcAll();
            });

            recalcAll();
        });
</script>
<?php include('layout_bottom.php'); ?>