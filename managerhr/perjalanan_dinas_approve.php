<?php
include("sess_check.php"); // sesuaikan path jika perlu
// DB
include('/dist/config/koneksi.php');

// Pastikan hanya Manager HR / yang berwenang mengakses (opsional, sesuaikan role)
$allowed_roles = ['managerhr','admin']; // sesuaikan nama role/cek session Anda
// contoh cek sederhana:
// if(!in_array($sess_level, $allowed_roles)){ header("Location: ../index.php"); exit; }

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id <= 0){
    echo "ID perjalanan tidak valid.";
    exit;
}

// Proses POST (handle approve / revisi / reject)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])){
    $aksi = $_POST['aksi'];
    $catatan = trim($_POST['catatan'] ?? '');
    $user_nama = $sess_admname ?? 'Manager HR';
    $user_npp = $sess_admuser ?? null;

    // Ambil data pengajuan terakhir (opsional)
    $stmt_last = mysqli_prepare($conn, "SELECT id, status FROM perjalanan_pengajuan WHERE id_perjalanan = ? ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt_last, 'i', $id);
    mysqli_stmt_execute($stmt_last);
    $res_last = mysqli_stmt_get_result($stmt_last);
    $last = mysqli_fetch_assoc($res_last);

    // Jika aksi revisi, update rincian sesuai input nominal[]
    if($aksi === 'revisi' || $aksi === 'approve_with_changes'){
        $nominals = $_POST['nominal'] ?? [];
        foreach($nominals as $rid => $val){
            $rid = intval($rid);
            $val_clean = preg_replace('/[^0-9]/','',$val);
            $nominal_new = $val_clean === '' ? '0' : $val_clean;

            // ambil rincian lama
            $stmt_r = mysqli_prepare($conn, "SELECT nominal, qty, total, keterangan FROM perjalanan_rincian WHERE id = ?");
            mysqli_stmt_bind_param($stmt_r, 'i', $rid);
            mysqli_stmt_execute($stmt_r);
            $res_r = mysqli_stmt_get_result($stmt_r);
            $old = mysqli_fetch_assoc($res_r);
            if(!$old) continue;

            $old_nominal = $old['nominal'] ?? '0';
            $old_total = $old['total'] ?? '0';
            $qty = (float) ($old['qty'] ?? 1);

            // simpan log perubahan jika ada perubahan nominal
            if($old_nominal !== $nominal_new){
                $note_log = "Revisi nominal oleh $user_nama. " . ($catatan ? "Catatan: $catatan" : "");
                $ins_log = mysqli_prepare($conn, "INSERT INTO perjalanan_rincian_log (rincian_id, perjalanan_id, old_nominal, old_total, old_keterangan, changed_by, note) VALUES (?,?,?,?,?,?,?)");
                mysqli_stmt_bind_param($ins_log, 'iisssss', $rid, $id, $old_nominal, $old_total, $old['keterangan'], $user_nama, $note_log);
                @mysqli_stmt_execute($ins_log);
            }

            // hitung total baru dan update rincian
            $total_new = (string) ( (float)$nominal_new * $qty );
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

    // Insert perjalanan_pengajuan record sesuai aksi
    if($aksi === 'approve' || $aksi === 'approve_with_changes'){
        $status = 'APPROVED_HR'; // diteruskan ke Direktur
        $approval_manager = 'APPROVED';
    } elseif($aksi === 'revisi'){
        $status = 'DITOLAK'; // dikembalikan ke HR untuk revisi
        $approval_manager = 'REVISI';
    } elseif($aksi === 'reject'){
        $status = 'DITOLAK';
        $approval_manager = 'REJECTED';
    } else {
        $status = 'DITOLAK';
        $approval_manager = 'REJECTED';
    }

    $ins = mysqli_prepare($conn, "INSERT INTO perjalanan_pengajuan (id_perjalanan, npp, pengaju, tanggal_pengajuan, status, approval_manager_hr, approver_manager_hr, tanggal_approval_manager_hr, catatan_manager_hr) VALUES (?,?,?,?,?, ?,?, NOW(), ?)");
    $tanggal_pengajuan = date('Y-m-d H:i:s');
    mysqli_stmt_bind_param($ins, 'issssss', $id, $user_npp, $user_nama, $tanggal_pengajuan, $status, $approval_manager, $user_nama, $catatan);
    $ok = mysqli_stmt_execute($ins);

    if($ok){
        // Redirect kembali ke list dengan pesan sukses
        header("Location: perjalanan_dinas_list.php?msg=" . urlencode("Proses approval berhasil: $status"));
        exit;
    } else {
        $err = mysqli_error($conn);
        header("Location: perjalanan_dinas_list.php?err=" . urlencode("Gagal menyimpan approval: $err"));
        exit;
    }
}

// Ambil data perjalanan & rincian untuk ditampilkan
$stmt = mysqli_prepare($conn, "SELECT * FROM perjalanan_dinas WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($res);
if(!$data){
    echo "Data perjalanan tidak ditemukan.";
    exit;
}

$stmt_r = mysqli_prepare($conn, "SELECT * FROM perjalanan_rincian WHERE perjalanan_id = ? ORDER BY nomor");
mysqli_stmt_bind_param($stmt_r, 'i', $id);
mysqli_stmt_execute($stmt_r);
$res_r = mysqli_stmt_get_result($stmt_r);
$rincian = [];
while($r = mysqli_fetch_assoc($res_r)) $rincian[] = $r;

// ambil pengajuan terakhir
$stmt_p = mysqli_prepare($conn, "SELECT * FROM perjalanan_pengajuan WHERE id_perjalanan = ? ORDER BY id DESC LIMIT 1");
mysqli_stmt_bind_param($stmt_p, 'i', $id);
mysqli_stmt_execute($stmt_p);
$res_p = mysqli_stmt_get_result($stmt_p);
$pengajuan = mysqli_fetch_assoc($res_p);

// Tampilkan form approval (Manager HR)
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Approve Perjalanan Dinas - Manager HR</title>
<link rel="stylesheet" href="../dist/css/bootstrap.min.css">
<script src="../dist/js/jquery.min.js"></script>
<script src="../dist/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container" style="max-width:900px;margin-top:20px">
    <a href="perjalanan_dinas_list.php" class="btn btn-default">&larr; Kembali</a>
    <h3 style="margin-top:10px">Review & Approve Perjalanan Dinas</h3>

    <div class="panel panel-info" style="margin-top:10px">
        <div class="panel-heading">Informasi Perjalanan</div>
        <div class="panel-body">
            <table class="table table-condensed">
                <tr><th>No. Dokumen</th><td><?php echo htmlspecialchars($data['no_dokumen']); ?></td></tr>
                <tr><th>Nama</th><td><?php echo htmlspecialchars($data['nama']); ?></td></tr>
                <tr><th>Departemen</th><td><?php echo htmlspecialchars($data['departemen']); ?></td></tr>
                <tr><th>Tanggal Perjalanan</th><td><?php echo htmlspecialchars($data['tanggal_perjalanan']); ?></td></tr>
                <tr><th>Budget Saat Ini</th><td>Rp <?php echo number_format((float)$data['budget_total'],0,',','.'); ?></td></tr>
                <?php if($pengajuan): ?>
                    <tr><th>Terakhir Diajukan Oleh</th><td><?php echo htmlspecialchars($pengajuan['pengaju']).' pada '.date('d-m-Y H:i', strtotime($pengajuan['tanggal_pengajuan'])); ?></td></tr>
                    <?php if(!empty($pengajuan['catatan'])): ?>
                        <tr><th>Catatan Pengaju</th><td><?php echo htmlspecialchars($pengajuan['catatan']); ?></td></tr>
                    <?php endif; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <form method="post">
        <div class="panel panel-default">
            <div class="panel-heading">Rincian Anggaran (ubah bila perlu)</div>
            <div class="panel-body">
                <table class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th style="width:6%">No</th>
                            <th>Keterangan</th>
                            <th style="width:18%;text-align:right">Nominal (input)</th>
                            <th style="width:8%;text-align:center">Qty</th>
                            <th style="width:18%;text-align:right">Perkiraan</th>
                            <th style="width:18%;text-align:right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rincian as $it): ?>
                        <tr>
                            <td class="text-center"><?php echo (int)$it['nomor']; ?></td>
                            <td><?php echo htmlspecialchars($it['ket']); ?></td>
                            <td style="text-align:right">
                                <input type="text" name="nominal[<?php echo (int)$it['id']; ?>]" class="form-control input-sm text-right nominal-field" value="<?php echo number_format((float)$it['nominal'],0,',','.'); ?>">
                            </td>
                            <td class="text-center"><?php echo (int)$it['qty']; ?></td>
                            <td style="text-align:right">Rp <?php echo number_format((float)$it['perkiraan'],0,',','.'); ?></td>
                            <td style="text-align:right">Rp <?php echo number_format((float)$it['total'],0,',','.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="form-group">
                    <label>Catatan Manager HR (opsional)</label>
                    <textarea name="catatan" class="form-control" rows="3"><?php echo htmlspecialchars($pengajuan['catatan_manager_hr'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <input type="hidden" name="aksi" id="aksi" value="approve">
                    <button type="submit" class="btn btn-success" onclick="$('#aksi').val('approve_with_changes')">Setujui & Teruskan ke Direktur</button>
                    <button type="submit" class="btn btn-warning" onclick="$('#aksi').val('revisi')">Minta Revisi ke HR</button>
                    <button type="submit" class="btn btn-danger" onclick="if(!confirm('Yakin menolak pengajuan ini?')) return false; $('#aksi').val('reject')">Tolak</button>
                </div>

                <p class="help-block"><strong>Catatan:</strong> Gunakan tombol "Minta Revisi" untuk mengembalikan ke HR jika nominal perlu diubah oleh HR. Gunakan "Setujui" untuk meneruskan ke Direktur.</p>
            </div>
        </div>
    </form>
</div>

<script>
// format numeric simple (replace non-digit)
$('.nominal-field').on('input', function(){
    var v = $(this).val().replace(/\D/g,'');
    if(v === '') v = '0';
    var formatted = v.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    $(this).val(formatted);
});
</script>
</body>
</html>