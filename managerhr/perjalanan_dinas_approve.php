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
    
    if ($last && $last['status'] === 'REALISASI_VERIFIED_HR') {
        $pengajuan_id = (int)$last['id'];
        if ($aksi === 'approve_realisasi') {
            $status = 'REALISASI_SELESAI';
            $approval_manager = 'APPROVED';
        } else {
            $status = 'REALISASI_DITOLAK';
            $approval_manager = 'REJECTED';
        }
        
        $upd = mysqli_prepare($conn, "UPDATE perjalanan_pengajuan SET approval_manager_hr = ?, approver_manager_hr = ?, tanggal_approval_manager_hr = NOW(), catatan_manager_hr = ?, status = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, 'ssssi', $approval_manager, $user_nama, $catatan, $status, $pengajuan_id);
        $ok = mysqli_stmt_execute($upd);
        if ($ok) {
            header("Location: perjalanan_dinas_list.php?msg=" . urlencode("Approval realisasi berhasil: $status"));
            exit;
        } else {
            $err = mysqli_error($conn);
            header("Location: perjalanan_dinas_list.php?err=" . urlencode("Gagal menyimpan approval: $err"));
            exit;
        }
    }


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
$is_realisasi_mode = $pengajuan && $pengajuan['status'] === 'REALISASI_VERIFIED_HR';
$prefix = is_file(__DIR__ . '/../dist/config/koneksi.php') ? '../' : '';
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
                <div class="panel-heading"><?php echo $is_realisasi_mode ? "Review & Approve Realisasi" : "Review & Approve Anggaran"; ?></div>
                <div class="panel-body">
                    <div class="info-header">
                        <div class="logo"><img src="foto/logo-dua.webp" alt="logo"></div>
                        <div class="doc-title"><?php echo $is_realisasi_mode ? "Review Realisasi Perjalanan Dinas" : "Form Anggaran Perjalanan Dinas"; ?></div>
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

                    <?php if ($is_realisasi_mode): ?>
                    <form method="post" id="frmApprove" autocomplete="off">
                        <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
                        <input type="hidden" name="aksi" id="formAksi" value="approve_realisasi">

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered rincian-table">
                                <thead>
                                    <tr class="info" style="background-color: #d9edf7; color: #31708f;">
                                        <th style="width:5%;text-align:center">No</th>
                                        <th>Keterangan</th>
                                        <th style="width:5%;text-align:center">Qty</th>
                                        <th style="width:12%;text-align:right">Nominal Budget</th>
                                        <th style="width:12%;text-align:right">Total Budget</th>
                                        <th style="width:12%;text-align:right">Nominal Realisasi</th>
                                        <th style="width:12%;text-align:right">Total Realisasi</th>
                                        <th style="width:25%;text-align:center">Bukti / Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_budget = 0;
                                    $total_real = 0;
                                    foreach ($rincian as $it):
                                        $nominal = (float) ($it['nominal'] ?? 0);
                                        $qty = (int) ($it['qty'] ?? 1) ?: 1;
                                        $total_budget += $nominal * $qty;
                                        
                                        $nominal_real = (float) ($it['nominal_realisasi'] ?? 0);
                                        $total_real_item = isset($it['total_realisasi']) && $it['total_realisasi'] !== null ? (float) $it['total_realisasi'] : ($nominal_real * $qty);
                                        $total_real += $total_real_item;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo (int) ($it['nomor'] ?? 0); ?></td>
                                        <td><?php echo htmlspecialchars($it['ket'] ?? ''); ?></td>
                                        <td class="text-center"><?php echo $qty; ?></td>
                                        <td style="text-align:right">Rp <?php echo number_format($nominal, 0, ',', '.'); ?></td>
                                        <td style="text-align:right">Rp <?php echo number_format($nominal * $qty, 0, ',', '.'); ?></td>
                                        <td style="text-align:right; font-weight:bold; color:#31708f;">Rp <?php echo number_format($nominal_real, 0, ',', '.'); ?></td>
                                        <td style="text-align:right; font-weight:bold; color:#31708f;">Rp <?php echo number_format($total_real_item, 0, ',', '.'); ?></td>
                                        <td>
                                            <?php if (!empty($it['bukti_realisasi'])): 
                                                $buktis = json_decode($it['bukti_realisasi'] ?? '', true) ?: [];
                                                foreach ($buktis as $b):
                                                    if (strpos($b, 'uploads/bukti_realisasi/') !== 0) continue; ?>
                                                    <a href="<?php echo htmlspecialchars($prefix . $b); ?>" target="_blank" style="margin-right: 5px;">
                                                        <img src="<?php echo htmlspecialchars($prefix . $b); ?>" style="width: 40px; height: 40px; object-fit: cover; border: 1px solid #ccc; border-radius: 4px;">
                                                    </a>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <?php if (!empty($it['keterangan'])): ?>
                                                <div style="font-size:11px; color:#555;"><?php echo htmlspecialchars($it['keterangan']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr style="background-color: #f5f5f5; font-weight:bold">
                                        <td colspan="4" style="text-align:right">TOTAL BUDGET:</td>
                                        <td style="text-align:right">Rp <?php echo number_format($total_budget, 0, ',', '.'); ?></td>
                                        <td style="text-align:right; color:#31708f;">TOTAL REALISASI:</td>
                                        <td style="text-align:right; color:#31708f;">Rp <?php echo number_format($total_real, 0, ',', '.'); ?></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <?php 
                        $selisih = $total_real - $total_budget;
                        ?>
                        <div class="alert alert-info" style="margin-top: 15px;">
                            <strong>Selisih Anggaran vs Realisasi:</strong><br>
                            <?php 
                            if ($selisih < 0) {
                                echo 'Kurang Bayar (Sisa Uang Muka dikembalikan ke Perusahaan): Rp ' . number_format(abs($selisih), 0, ',', '.');
                            } elseif ($selisih > 0) {
                                echo 'Lebih Bayar (Reimbursement dari Perusahaan): Rp ' . number_format($selisih, 0, ',', '.');
                            } else {
                                echo 'Sesuai Budget (Tidak ada selisih)';
                            }
                            ?>
                        </div>

                        <div class="form-group" style="margin-top:15px">
                            <label>Catatan / Alasan Penolakan (Hanya wajib diisi jika menolak realisasi)</label>
                            <textarea name="catatan" id="catatanTolak" class="form-control" placeholder="Masukkan alasan penolakan di sini..."></textarea>
                        </div>

                        <div class="actions">
                            <button type="button" id="btnApproveReal" class="btn btn-success btn-lg">
                                <i class="fa fa-check"></i> Setujui Laporan Realisasi
                            </button>
                            <button type="button" id="btnRejectReal" class="btn btn-danger btn-lg">
                                <i class="fa fa-times"></i> Tolak Realisasi
                            </button>
                            <a href="perjalanan_dinas_list.php" class="btn btn-default btn-lg">
                                <i class="fa fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                    <?php else: ?>
                    <form method="post" id="frmApprove" autocomplete="off">
                        <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered rincian-table">
                                        <thead>
                                            <tr>
                                                <th style="width:6%; text-align:center">No</th>
                                                <th>Keterangan</th>
                                                <th style="width:16%; text-align:right">Nominal (input)</th>
                                                <th style="width:8%; text-align:center">Qty</th>
                                                <th style="width:16%; text-align:right">Perkiraan</th>
                                                <th style="width:16%; text-align:right">Total</th>
                                                <th style="width:8%; text-align:center">Aksi</th>
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
                                        <td class="text-center">
                                            <button type="button" class="btn btn-xs btn-warning btnEditApprove"
                                                data-id="<?php echo (int) $it['id']; ?>"
                                                data-ket="<?php echo htmlspecialchars($it['ket'], ENT_QUOTES); ?>"
                                                data-nominal="<?php echo htmlspecialchars($it['nominal']); ?>"
                                                data-qty="<?php echo htmlspecialchars($it['qty']); ?>"
                                                data-perkiraan="<?php echo htmlspecialchars($it['perkiraan']); ?>"
                                                data-keterangan="<?php echo htmlspecialchars($it['keterangan'], ENT_QUOTES); ?>">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-xs btn-danger btnDeleteApprove" data-id="<?php echo (int) $it['id']; ?>">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
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
                            <button type="button" id="btnAddItemApprove" class="btn btn-primary btn-lg"><i class="fa fa-plus"></i> Tambah Item</button>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
        $(function () {
            // Realisasi Actions
            $('#btnApproveReal').on('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Setujui Realisasi?',
                    text: 'Laporan realisasi akan disetujui secara final',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, setujui',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#5cb85c'
                }).then(function(res) {
                    if (res.isConfirmed) {
                        $('#formAksi').val('approve_realisasi');
                        $('#frmApprove')[0].submit();
                    }
                });
            });

            $('#btnRejectReal').on('click', function(e) {
                e.preventDefault();
                var catatan = $('#catatanTolak').val().trim();
                if (catatan === '') {
                    Swal.fire({
                        title: 'Catatan Wajib Diisi',
                        text: 'Silakan masukkan alasan penolakan pada kolom catatan.',
                        icon: 'warning',
                        confirmButtonColor: '#d9534f'
                    });
                    return;
                }
                
                Swal.fire({
                    title: 'Tolak Realisasi?',
                    text: 'Laporan realisasi ini akan ditolak dan dikembalikan ke user',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, tolak',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#d9534f'
                }).then(function(res) {
                    if (res.isConfirmed) {
                        $('#formAksi').val('reject_realisasi');
                        $('#frmApprove')[0].submit();
                    }
                });
            });

            // Handlers for add/edit/delete rincian within approve page
            $('#btnAddItemApprove').on('click', function () {
                $('#rincianFormApprove')[0].reset();
                $('#rincian_id_approve').val('');
                $('#rincianModalApprove .modal-title').text('Tambah Item');
                $('#rincianModalApprove').modal('show');
            });

            $(document).on('click', '.btnEditApprove', function () {
                $('#rincian_id_approve').val($(this).data('id'));
                $('#ket_approve').val($(this).data('ket'));
                $('#nominal_approve').val($(this).data('nominal'));
                $('#qty_approve').val($(this).data('qty'));
                $('#perkiraan_approve').val($(this).data('perkiraan'));
                $('#keterangan_approve').val($(this).data('keterangan'));
                $('#rincianModalApprove .modal-title').text('Edit Item');
                $('#rincianModalApprove').modal('show');
            });

            $('#rincianFormApprove').on('submit', function (e) {
                e.preventDefault();
                var fd = $(this).serialize();
                $.post('perjalanan_rincian_save.php', fd, function (res) {
                    if (res && res.success) {
                        if (res.budget_total !== undefined) {
                            var fmt = res.budget_total.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                            $('#budgetDisplay').text('Rp ' + fmt);
                        }
                        // update table row or append new row
                        if (res.row) {
                            var row = res.row;
                            var rowHtml = '<tr data-id="' + row.id + '">';
                            rowHtml += '<td class="text-center">' + (row.nomor ? row.nomor : '') + '</td>';
                            rowHtml += '<td>' + (row.ket ? $('<div>').text(row.ket).html() : '') + '</td>';
                            rowHtml += '<td class="text-right"><input type="text" name="nominal[' + row.id + ']" value="' + (row.nominal ? Number(row.nominal).toLocaleString('id-ID') : '') + '" class="nominal-field" data-qty="' + row.qty + '"></td>';
                            rowHtml += '<td class="text-center">' + (row.qty ? row.qty : '') + '</td>';
                            rowHtml += '<td class="text-right">Rp ' + (row.perkiraan ? Number(row.perkiraan).toLocaleString('id-ID') : '0') + '</td>';
                            rowHtml += '<td class="text-right line-total">Rp ' + (row.total ? Number(row.total).toLocaleString('id-ID') : '0') + '</td>';
                            rowHtml += '<td class="text-center"><button type="button" class="btn btn-xs btn-warning btnEditApprove" data-id="' + row.id + '" data-ket="' + $('<div>').text(row.ket).html() + '" data-nominal="' + row.nominal + '" data-qty="' + row.qty + '" data-perkiraan="' + row.perkiraan + '" data-keterangan="' + $('<div>').text(row.keterangan).html() + '"><i class="fa fa-edit"></i></button> '
                            rowHtml += '<button type="button" class="btn btn-xs btn-danger btnDeleteApprove" data-id="' + row.id + '"><i class="fa fa-trash"></i></button></td>';
                            rowHtml += '</tr>';

                            // if row exists, replace; otherwise insert before last summary row
                            var existing = $('.rincian-table tbody tr[data-id="' + row.id + '"]');
                            if (existing.length) {
                                existing.replaceWith(rowHtml);
                            } else {
                                $('.rincian-table tbody tr').last().before(rowHtml);
                            }
                        }
                        $('#rincianModalApprove').modal('hide');
                        Swal.fire({ icon: 'success', title: 'Tersimpan', showConfirmButton: false, timer: 900 });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res && res.error ? res.error : 'Gagal menyimpan item' });
                    }
                }, 'json').fail(function () { Swal.fire({ icon: 'error', title: 'Request gagal' }); });
            });

            $(document).on('click', '.btnDeleteApprove', function () {
                var id = parseInt($(this).data('id')) || parseInt($(this).attr('data-id')) || 0;
                if (!id || isNaN(id) || id <= 0) {
                    Swal.fire({ icon: 'error', title: 'ID tidak valid', text: 'ID item tidak valid untuk dihapus.' });
                    return;
                }
                Swal.fire({
                    title: 'Hapus item?',
                    text: 'Item akan dihapus permanen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Hapus',
                    cancelButtonText: 'Batal'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        $.post('perjalanan_rincian_delete.php', { id: id }, function (res) {
                                if (res && res.success) {
                                if (res.budget_total !== undefined) {
                                    var fmt = res.budget_total.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                    $('#budgetDisplay').text('Rp ' + fmt);
                                }
                                // remove row from table
                                $('.rincian-table tbody tr[data-id="' + id + '"]').remove();
                                Swal.fire({ icon: 'success', title: 'Terhapus', showConfirmButton: false, timer: 900 });
                            } else {
                                Swal.fire({ icon: 'error', title: 'Gagal', text: res && res.error ? res.error : 'Gagal menghapus' });
                            }
                        }, 'json').fail(function () { Swal.fire({ icon: 'error', title: 'Request gagal' }); });
                    }
                });
            });
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
<!-- Modal for add/edit rincian (moved outside the approval form) -->
<div id="rincianModalApprove" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <form id="rincianFormApprove">
            <input type="hidden" name="id" id="rincian_id_approve" value="">
            <input type="hidden" name="perjalanan_id" value="<?php echo (int) $id; ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah / Edit Item</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Keterangan</label><input name="ket" id="ket_approve" class="form-control" required></div>
                    <div class="form-group"><label>Nominal</label><input type="text" inputmode="numeric" pattern="[0-9.]*" name="nominal" id="nominal_approve" class="form-control" oninput="this.value=this.value.replace(/[^0-9\.]/g,'');"></div>
                    <div class="form-group"><label>Qty</label><input type="number" step="1" min="0" name="qty" id="qty_approve" class="form-control" oninput="this.value=this.value.replace(/[^0-9]/g,'');"></div>
                    <div class="form-group"><label>Perkiraan</label><input type="text" inputmode="numeric" pattern="[0-9.]*" name="perkiraan" id="perkiraan_approve" class="form-control" oninput="this.value=this.value.replace(/[^0-9\.]/g,'');"></div>
                    <div class="form-group"><label>Keterangan Tambahan</label><input name="keterangan" id="keterangan_approve" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php include('layout_bottom.php'); ?>