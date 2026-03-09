<?php
include("sess_check.php");
include('dist/config/koneksi.php');

// Get ID early for POST processing
$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);

// Proses POST (HR mengisi nominal dan ajukan ke Manager HR) - BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $id > 0) {
    $nominals = $_POST['nominal'] ?? [];
    $user_nama = $sess_admname ?? 'HR Admin';
    $user_npp = $sess_admid ?? $_SESSION['admin'] ?? null;

    // Update nominal di tabel perjalanan_rincian
    foreach ($nominals as $rid => $val) {
        $rid = intval($rid);
        $val_clean = preg_replace('/[^0-9]/', '', $val);
        $nominal_new = $val_clean === '' ? '0' : $val_clean;

        // Ambil data rincian lama
        $stmt_r = mysqli_prepare($conn, "SELECT qty FROM perjalanan_rincian WHERE id = ?");
        mysqli_stmt_bind_param($stmt_r, 'i', $rid);
        mysqli_stmt_execute($stmt_r);
        $res_r = mysqli_stmt_get_result($stmt_r);
        $old = mysqli_fetch_assoc($res_r);
        if (!$old) continue;

        $qty = (float) ($old['qty'] ?? 1);
        $total_new = (string) ((float) $nominal_new * $qty);

        // Update rincian
        $upd = mysqli_prepare($conn, "UPDATE perjalanan_rincian SET nominal = ?, total = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, 'ssi', $nominal_new, $total_new, $rid);
        mysqli_stmt_execute($upd);
    }

    // Recalculate budget_total
    $stmt_sum = mysqli_prepare($conn, "SELECT SUM(CAST(REPLACE(total,',','') AS DECIMAL(20,2))) AS s FROM perjalanan_rincian WHERE perjalanan_id = ?");
    mysqli_stmt_bind_param($stmt_sum, 'i', $id);
    mysqli_stmt_execute($stmt_sum);
    $res_sum = mysqli_stmt_get_result($stmt_sum);
    $sum = mysqli_fetch_assoc($res_sum);
    $budget_total = (float) ($sum['s'] ?? 0);
    
    $upd_budget = mysqli_prepare($conn, "UPDATE perjalanan_dinas SET budget_total = ? WHERE id = ?");
    mysqli_stmt_bind_param($upd_budget, 'di', $budget_total, $id);
    mysqli_stmt_execute($upd_budget);

    // Check if pengajuan exists
    $stmt_check = mysqli_prepare($conn, "SELECT id FROM perjalanan_pengajuan WHERE id_perjalanan = ? ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt_check, 'i', $id);
    mysqli_stmt_execute($stmt_check);
    $res_check = mysqli_stmt_get_result($stmt_check);
    $existing = mysqli_fetch_assoc($res_check);

    if ($existing) {
        // UPDATE existing record
        $pengajuan_id = $existing['id'];
        $stmt_upd = mysqli_prepare($conn, "UPDATE perjalanan_pengajuan 
            SET npp = ?, pengaju = ?, tanggal_pengajuan = NOW(), status = ?
            WHERE id = ?");
        $status = 'DIAJUKAN';
        mysqli_stmt_bind_param($stmt_upd, 'sssi', $user_npp, $user_nama, $status, $pengajuan_id);
        mysqli_stmt_execute($stmt_upd);
    } else {
        // INSERT new record
        $stmt_ins = mysqli_prepare($conn, "INSERT INTO perjalanan_pengajuan 
            (id_perjalanan, npp, pengaju, tanggal_pengajuan, status) 
            VALUES (?, ?, ?, NOW(), 'DIAJUKAN')");
        mysqli_stmt_bind_param($stmt_ins, 'iss', $id, $user_npp, $user_nama);
        mysqli_stmt_execute($stmt_ins);
    }

    header("Location: perjalanan_dinas_list.php?msg=" . urlencode("Berhasil diajukan ke Manager HR"));
    exit;
}

// Now safe to include layout (after redirect handling)
$pagedesc = 'Pengisian Nominal - Pengajuan Perjalanan Dinas';
$menuparent = 'perjalanan_dinas';
include("layout_top.php");

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
                <div class="panel-heading">Pengisian Nominal Anggaran</div>
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
                        </div>

                        <div class="info-right">
                            <div class="budget-card">
                                <div style="font-size:13px;color:#666">Budget Total</div>
                                <div id="budgetDisplay" class="budget-amount">Rp <?php echo number_format((float) $data['budget_total'], 0, ',', '.'); ?></div>
                                <div style="font-size:12px;color:#777;margin-top:6px">Akan dihitung otomatis saat nominal diisi</div>
                            </div>
                        </div>
                    </div>

                    <form method="post" id="frmApprove" autocomplete="off">
                        <input type="hidden" name="aksi" value="ajukan">
                        <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered rincian-table">
                                <thead>
                                    <tr>
                                        <th style="width:6%; text-align:center">No</th>
                                        <th>Keterangan</th>
                                        <th style="width:18%; text-align:right">Nominal (Rp)</th>
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
                                                class="nominal-field" data-qty="<?php echo $qty; ?>" placeholder="Masukkan nominal">
                                        </td>
                                        <td class="text-center"><?php echo $qty; ?></td>
                                        <td style="text-align:right">Rp <?php echo number_format((float) $it['perkiraan'], 0, ',', '.'); ?></td>
                                        <td class="text-right line-total">Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="actions">
                            <button type="submit" id="btnSubmit" class="btn btn-primary btn-lg">
                                <i class="fa fa-paper-plane"></i> Ajukan ke Manager HR
                            </button>
                            <a href="perjalanan_dinas_list.php" class="btn btn-default btn-lg">
                                <i class="fa fa-arrow-left"></i> Kembali
                            </a>
                        </div>

                        <div class="note">
                            <strong>Instruksi:</strong> Isi nominal untuk setiap item rincian, lalu klik "Ajukan ke Manager HR" untuk mengirim ke proses approval.
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

            $('#frmApprove').on('submit', function (e) {
                e.preventDefault();
                
                Swal.fire({
                    title: 'Ajukan ke Manager HR?',
                    text: 'Data nominal akan dikirim untuk proses approval Manager HR',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, ajukan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#3085d6'
                }).then(function (res) {
                    if (res.isConfirmed) {
                        $('#frmApprove')[0].submit();
                    }
                });
            });
        });
</script>
<?php include('layout_bottom.php'); ?>