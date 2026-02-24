<?php
include("sess_check.php");
include __DIR__ . '/dist/config/koneksi.php';

// helper generate doc no
function generate_doc_no()
{
    try { $bytes = random_bytes(4); return 'PMT' . strtoupper(bin2hex($bytes)); }
    catch (Exception $e) { return 'PMT' . strtoupper(uniqid()); }
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die('ID tidak valid.');
}

// fetch current record
$stmt = mysqli_prepare($conn, "SELECT * FROM permintaan_karyawan WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($res);
if (!$data) {
    die('Data tidak ditemukan.');
}

// fetch job duties
$duties_stmt = mysqli_prepare($conn, "SELECT tugas FROM job_duties_karyawan WHERE permintaan_id = ? ORDER BY nomor_urut");
mysqli_stmt_bind_param($duties_stmt, 'i', $id);
mysqli_stmt_execute($duties_stmt);
$duties_res = mysqli_stmt_get_result($duties_stmt);
$existing_duties = [];
while ($d = mysqli_fetch_assoc($duties_res)) {
    $existing_duties[] = $d['tugas'];
}

// fetch skills
$skills_stmt = mysqli_prepare($conn, "SELECT skill FROM skills_karyawan WHERE permintaan_id = ? ORDER BY nomor");
mysqli_stmt_bind_param($skills_stmt, 'i', $id);
mysqli_stmt_execute($skills_stmt);
$skills_res = mysqli_stmt_get_result($skills_stmt);
$existing_skills = [];
while ($s = mysqli_fetch_assoc($skills_res)) {
    $existing_skills[] = $s['skill'];
}

// fetch latest submission status
$sstmt = mysqli_prepare($conn, "SELECT status FROM permintaan_pengajuan WHERE id_permintaan = ? ORDER BY id DESC LIMIT 1");
mysqli_stmt_bind_param($sstmt, 'i', $id);
mysqli_stmt_execute($sstmt);
$rs = mysqli_stmt_get_result($sstmt);
$sub = mysqli_fetch_assoc($rs);
$status_latest = $sub['status'] ?? '';
$message = $_GET['msg'] ?? '';

// buat tabel riwayat perubahan jika belum ada (kolom Bahasa Indonesia)
$createHist = "CREATE TABLE IF NOT EXISTS permintaan_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permintaan_id INT NOT NULL,
    diubah_oleh VARCHAR(50),
    diubah_pada DATETIME,
    catatan TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $createHist);

// Handle POST actions: save update or buat_revisi (BEFORE layout_top to allow header redirects)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // jika buat revisi (duplicate record)
    if ($action === 'buat_revisi') {
        // copy fields
        $new_doc = generate_doc_no();
        $now_date = date('Y-m-d');
        $copy_stmt = mysqli_prepare($conn, "INSERT INTO permintaan_karyawan
            (no_dokumen,revisi,tanggal_dokumen,jabatan,tgl_mulai,jumlah_dibutuhkan,untuk,jumlah_sekarang,alasan,gender,usia,pendidikan,jurusan,pengalaman,tinggi,berat,rentang_gaji,lain_lain)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($copy_stmt, 'sssssisissssssssss',
            $new_doc, $data['revisi'], $now_date, $data['jabatan'], $data['tgl_mulai'], $data['jumlah_dibutuhkan'], $data['untuk'], $data['jumlah_sekarang'], $data['alasan'], $data['gender'], $data['usia'], $data['pendidikan'], $data['jurusan'], $data['pengalaman'], $data['tinggi'], $data['berat'], $data['rentang_gaji'], $data['lain_lain']
        );
        $ok = mysqli_stmt_execute($copy_stmt);
        if ($ok) {
            $new_id = mysqli_insert_id($conn);
            // copy duties
            $dq = mysqli_query($conn, "SELECT nomor_urut,tugas FROM job_duties_karyawan WHERE permintaan_id=" . (int)$id . " ORDER BY nomor_urut");
            if ($dq) {
                $dins = mysqli_prepare($conn, "INSERT INTO job_duties_karyawan (permintaan_id, nomor_urut, tugas) VALUES (?,?,?)");
                while ($r = mysqli_fetch_assoc($dq)) {
                    mysqli_stmt_bind_param($dins, 'iis', $new_id, $r['nomor_urut'], $r['tugas']);
                    mysqli_stmt_execute($dins);
                }
            }
            // copy skills
            $sq = mysqli_query($conn, "SELECT nomor,skill FROM skills_karyawan WHERE permintaan_id=" . (int)$id . " ORDER BY nomor");
            if ($sq) {
                $sins = mysqli_prepare($conn, "INSERT INTO skills_karyawan (permintaan_id, nomor, skill) VALUES (?,?,?)");
                while ($r = mysqli_fetch_assoc($sq)) {
                    mysqli_stmt_bind_param($sins, 'iis', $new_id, $r['nomor'], $r['skill']);
                    mysqli_stmt_execute($sins);
                }
            }
            header('Location: permintaan_karyawan_edit.php?id=' . $new_id . '&msg=' . rawurlencode('Revisi dibuat'));
            exit;
        } else {
            $message = 'Gagal membuat revisi: ' . mysqli_error($conn);
        }
    }

    // jika menyimpan perubahan (hanya diizinkan bila belum diajukan atau ditolak)
    if ($action === 'save') {
        if ($status_latest === 'DIAJUKAN' || $status_latest === 'DISETUJUI') {
            $message = 'Tidak diizinkan mengubah data setelah pengajuan.';
        } else {
            // ambil input serupa dengan form
            $jabatan = $_POST['jabatan'] ?? '';
            $tgl_mulai = $_POST['tgl_mulai'] ?? null;
            $jumlah_dibutuhkan = $_POST['jumlah_dibutuhkan'] ?? null;
            $untuk_raw = $_POST['untuk'] ?? '';
            $untuk_lain = $_POST['untuk_lain'] ?? '';
            $untuk = ($untuk_raw === 'Lain-lain') ? $untuk_lain : $untuk_raw;
            $jumlah_sekarang = $_POST['jumlah_sekarang'] ?? null;
            $alasan = $_POST['alasan'] ?? '';
            $gender = $_POST['gender'] ?? '';
            $age = $_POST['age'] ?? '';
            $education = $_POST['education'] ?? '';
            $education_lain = $_POST['education_lain'] ?? '';
            if ($education === 'Lain-lain') $education = $education_lain;
            $jurusan = $_POST['jurusan'] ?? '';
            $experiences = $_POST['experiences'] ?? '';
            $height = $_POST['height'] ?? '';
            $weight = $_POST['weight'] ?? '';
            $range_salary = $_POST['range_salary'] ?? '';
            $other = $_POST['other'] ?? '';

            // duties
            $duties = [];
            for ($i = 1; $i <= 15; $i++) {
                $k = 'duty' . $i;
                if (!empty($_POST[$k])) $duties[] = trim($_POST[$k]);
            }
            // skills
            $skillsArr = [];
            for ($s = 1; $s <= 15; $s++) {
                if (!empty($_POST['skill' . $s])) $skillsArr[] = trim($_POST['skill' . $s]);
            }

            // update main record
            $ustmt = mysqli_prepare($conn, "UPDATE permintaan_karyawan SET jabatan=?, tgl_mulai=?, jumlah_dibutuhkan=?, untuk=?, jumlah_sekarang=?, alasan=?, gender=?, usia=?, pendidikan=?, jurusan=?, pengalaman=?, tinggi=?, berat=?, rentang_gaji=?, lain_lain=? WHERE id=?");
            mysqli_stmt_bind_param($ustmt, 'sssssssssssssssi', $jabatan, $tgl_mulai, $jumlah_dibutuhkan, $untuk, $jumlah_sekarang, $alasan, $gender, $age, $education, $jurusan, $experiences, $height, $weight, $range_salary, $other, $id);
            $ok = mysqli_stmt_execute($ustmt);
            if ($ok) {
                // replace duties
                mysqli_query($conn, "DELETE FROM job_duties_karyawan WHERE permintaan_id=" . (int)$id);
                if (!empty($duties)) {
                    $dins = mysqli_prepare($conn, "INSERT INTO job_duties_karyawan (permintaan_id, nomor_urut, tugas) VALUES (?,?,?)");
                    foreach ($duties as $idx => $t) {
                        $num = $idx + 1;
                        mysqli_stmt_bind_param($dins, 'iis', $id, $num, $t);
                        mysqli_stmt_execute($dins);
                    }
                }
                // replace skills
                mysqli_query($conn, "DELETE FROM skills_karyawan WHERE permintaan_id=" . (int)$id);
                if (!empty($skillsArr)) {
                    $sins = mysqli_prepare($conn, "INSERT INTO skills_karyawan (permintaan_id, nomor, skill) VALUES (?,?,?)");
                    foreach ($skillsArr as $idx => $sk) {
                        $num = $idx + 1;
                        mysqli_stmt_bind_param($sins, 'iis', $id, $num, $sk);
                        mysqli_stmt_execute($sins);
                    }
                }
                // tambahkan log riwayat sederhana
                $note = 'Diubah oleh ' . ($sess_admuser ?? 'SYSTEM') . ' pada ' . date('Y-m-d H:i:s');
                $hstmt = mysqli_prepare($conn, "INSERT INTO permintaan_history (permintaan_id, diubah_oleh, diubah_pada, catatan) VALUES (?,?,NOW(),?)");
                mysqli_stmt_bind_param($hstmt, 'iss', $id, $sess_admuser, $note);
                mysqli_stmt_execute($hstmt);

                header('Location: permintaan_karyawan_list.php?msg=' . rawurlencode('Perubahan disimpan'));
                exit;
            } else {
                $message = 'Gagal menyimpan perubahan: ' . mysqli_error($conn);
            }
        }
    }
}

// Calculate counts for JavaScript
$duty_count = count($existing_duties);
$skill_count = count($existing_skills);

// Now include layout AFTER all redirect logic is complete
$pagedesc = 'Edit Permintaan Karyawan';
$menuparent = 'approval';
include("layout_top.php");

?>
<style>
    body { font-family: Arial, Helvetica, sans-serif; color: #111; }
    .container { max-width: 1000px; margin: 20px auto; padding: 10px; }
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .title { flex: 1; text-align: center; font-weight: 700; font-size: 22px; }
    table.form { width: 100%; border-collapse: collapse; margin-top: 12px; }
    table.form td, table.form th { border: 1px solid #000; padding: 6px; font-size: 13px; }
    .center { text-align: center; }
    .small { font-size: 12px; }
    .duties tr td { border: 1px solid #000; height: 24px; }
    .duties-number { width: 40px; text-align: center; }
    .info-box { background-color: #f5f5f5; padding: 12px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #337ab7; }
    .alert { padding: 12px; margin-bottom: 15px; border-radius: 4px; }
    .alert-warning { background-color: #fcf8e3; border: 1px solid #faebcc; color: #8a6d3b; }
    .alert-success { background-color: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d; }
    .btn { display: inline-block; padding: 6px 12px; margin: 4px; font-size: 14px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
    .btn-primary { background-color: #337ab7; color: white; }
    .btn-default { background-color: #fff; border: 1px solid #ccc; color: #333; }
    .btn-success { background-color: #5cb85c; color: white; }
    .btn:hover { opacity: 0.9; }
</style>

<div id="page-wrapper">
    <div class="container">
        <div class="header">
            <div style="width:18%">
                <div style="border:1px solid #000;padding:8px;font-size:12px;text-align:left">
                    <strong>No. Dokumen:</strong> <?php echo htmlspecialchars($data['no_dokumen']); ?><br>
                    <strong>Revisi:</strong> <?php echo htmlspecialchars($data['revisi']); ?><br>
                    <strong>Tanggal:</strong> <?php echo date('d-m-Y', strtotime($data['tanggal_dokumen'])); ?>
                    <?php if ($status_latest): ?>
                        <br><strong>Status:</strong> <?php echo htmlspecialchars($status_latest); ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="title">UBAH PERMINTAAN KARYAWAN BARU</div>
            <div style="width:18%"></div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($status_latest === 'DIAJUKAN' || $status_latest === 'DISETUJUI'): ?>
            <div class="alert alert-warning">
                <strong><i class="fa fa-exclamation-triangle"></i> Status: <?php echo htmlspecialchars($status_latest); ?></strong><br>
                Permintaan yang sudah diajukan atau disetujui tidak dapat diedit. Anda dapat membuat revisi baru.
            </div>
            <form method="post" style="margin-top: 15px;">
                <input type="hidden" name="action" value="buat_revisi">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-copy"></i> Buat Revisi dari Permintaan Ini
                </button>
                <a href="permintaan_karyawan_list.php" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Kembali
                </a>
            </form>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="action" value="save">
                
                <table class="form">
                    <tr>
                        <td style="width:20%">Jabatan</td>
                        <td style="width:40%">: <input type="text" style="width:95%" name="jabatan" value="<?php echo htmlspecialchars($data['jabatan']); ?>" required></td>
                        <td style="width:20%">Tanggal Mulai Bekerja</td>
                        <td>: <input type="date" name="tgl_mulai" value="<?php echo htmlspecialchars($data['tgl_mulai']); ?>"></td>
                    </tr>
                    <tr>
                        <td>Jumlah dibutuhkan</td>
                        <td>: <input type="number" name="jumlah_dibutuhkan" style="width:80px" value="<?php echo htmlspecialchars($data['jumlah_dibutuhkan']); ?>" min="1" required> orang</td>
                        <td>Untuk</td>
                        <td>: 
                            <select name="untuk" id="untuk_select" required>
                                <option value="">-- Pilih --</option>
                                <option value="PENAMBAHAN" <?php echo ($data['untuk'] == 'PENAMBAHAN') ? 'selected' : ''; ?>>Penambahan</option>
                                <option value="PENGGANTIAN" <?php echo ($data['untuk'] == 'PENGGANTIAN') ? 'selected' : ''; ?>>Penggantian</option>
                                <option value="Lain-lain" <?php echo (!in_array($data['untuk'], ['PENAMBAHAN', 'PENGGANTIAN'])) ? 'selected' : ''; ?>>Lain-lain</option>
                            </select>
                            <input type="text" name="untuk_lain" id="untuk_lain" placeholder="Jelaskan jika Lain-lain" style="width:35%;margin-left:8px;<?php echo (!in_array($data['untuk'], ['PENAMBAHAN', 'PENGGANTIAN'])) ? '' : 'display:none;'; ?>" value="<?php echo (!in_array($data['untuk'], ['PENAMBAHAN', 'PENGGANTIAN'])) ? htmlspecialchars($data['untuk']) : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>Jumlah Karyawan Sekarang</td>
                        <td>: <input type="number" name="jumlah_sekarang" style="width:80px" value="<?php echo htmlspecialchars($data['jumlah_sekarang']); ?>" min="0"> orang</td>
                        <td>Alasan</td>
                        <td>: <input type="text" name="alasan" style="width:95%" value="<?php echo htmlspecialchars($data['alasan']); ?>" required></td>
                    </tr>
                </table>

                <table class="form" style="margin-top:18px">
                    <tr><th colspan="4" class="center">Job Duties</th></tr>
                    <?php 
                    $duty_count = max(count($existing_duties), 3);
                    for ($i = 0; $i < $duty_count; $i++): 
                        $duty_val = isset($existing_duties[$i]) ? $existing_duties[$i] : '';
                    ?>
                    <tr class="duties">
                        <td class="duties-number"><?php echo ($i + 1); ?></td>
                        <td colspan="3"><input type="text" name="duty<?php echo ($i + 1); ?>" style="width:100%" value="<?php echo htmlspecialchars($duty_val); ?>"></td>
                    </tr>
                    <?php endfor; ?>
                    <tr>
                        <td colspan="4" class="center">
                            <button type="button" class="btn btn-success" id="addDuty" style="font-size:12px;padding:4px 8px">
                                <i class="fa fa-plus"></i> Tambah Tugas
                            </button>
                        </td>
                    </tr>
                </table>

                <table class="form" style="margin-top:12px">
                    <tr><th colspan="4" class="center">Requirements</th></tr>
                    <tr>
                        <td style="width:18%">Jenis Kelamin</td>
                        <td style="width:32%">: 
                            <select name="gender" required>
                                <option value="">-- Pilih --</option>
                                <option value="L" <?php echo ($data['gender'] == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="P" <?php echo ($data['gender'] == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                                <option value="L/P" <?php echo ($data['gender'] == 'L/P') ? 'selected' : ''; ?>>Laki-laki / Perempuan</option>
                            </select>
                        </td>
                        <td style="width:18%">Usia</td>
                        <td>: <input type="number" name="age" style="width:80px" value="<?php echo htmlspecialchars($data['usia']); ?>" min="17" max="65"> tahun</td>
                    </tr>
                    <tr>
                        <td>Pendidikan</td>
                        <td>: 
                            <select name="education" id="education_select" required>
                                <option value="">-- Pilih --</option>
                                <option value="SD" <?php echo ($data['pendidikan'] == 'SD') ? 'selected' : ''; ?>>SD</option>
                                <option value="SMP" <?php echo ($data['pendidikan'] == 'SMP') ? 'selected' : ''; ?>>SMP</option>
                                <option value="SMA/SMK" <?php echo ($data['pendidikan'] == 'SMA/SMK') ? 'selected' : ''; ?>>SMA/SMK</option>
                                <option value="D3" <?php echo ($data['pendidikan'] == 'D3') ? 'selected' : ''; ?>>D3</option>
                                <option value="S1" <?php echo ($data['pendidikan'] == 'S1') ? 'selected' : ''; ?>>S1</option>
                                <option value="S2" <?php echo ($data['pendidikan'] == 'S2') ? 'selected' : ''; ?>>S2</option>
                                <option value="Lain-lain" <?php echo (!in_array($data['pendidikan'], ['SD','SMP','SMA/SMK','D3','S1','S2'])) ? 'selected' : ''; ?>>Lain-lain</option>
                            </select>
                            <input type="text" name="education_lain" id="education_lain" placeholder="Sebutkan" style="width:35%;margin-left:8px;<?php echo (!in_array($data['pendidikan'], ['SD','SMP','SMA/SMK','D3','S1','S2'])) ? '' : 'display:none;'; ?>" value="<?php echo (!in_array($data['pendidikan'], ['SD','SMP','SMA/SMK','D3','S1','S2'])) ? htmlspecialchars($data['pendidikan']) : ''; ?>">
                        </td>
                        <td>Jurusan</td>
                        <td>: <input type="text" name="jurusan" style="width:90%" value="<?php echo htmlspecialchars($data['jurusan']); ?>"></td>
                    </tr>
                    <tr>
                        <td>Pengalaman</td>
                        <td>: <input type="number" name="experiences" style="width:80px" value="<?php echo htmlspecialchars($data['pengalaman']); ?>" min="0"> tahun</td>
                        <td>Tinggi dan Berat</td>
                        <td>: <input type="number" name="height" style="width:60px" value="<?php echo htmlspecialchars($data['tinggi']); ?>" min="100" max="250"> cm &nbsp; <input type="number" name="weight" style="width:60px" value="<?php echo htmlspecialchars($data['berat']); ?>" min="30" max="200"> kg</td>
                    </tr>
                    <tr>
                        <td>Rentang Gaji</td>
                        <td>: Rp <input type="number" name="range_salary" style="width:150px" value="<?php echo htmlspecialchars($data['rentang_gaji']); ?>" min="0" step="100000"></td>
                        <td>Keahlian dan Kemampuan</td>
                        <td>:
                            <div id="skills-container">
                                <?php 
                                $skill_count = max(count($existing_skills), 3);
                                for ($s = 0; $s < $skill_count; $s++): 
                                    $skill_val = isset($existing_skills[$s]) ? $existing_skills[$s] : '';
                                ?>
                                    <div style="margin-top:4px"><?php echo ($s + 1); ?>. <input type="text" name="skill<?php echo ($s + 1); ?>" style="width:70%" value="<?php echo htmlspecialchars($skill_val); ?>"></div>
                                <?php endfor; ?>
                            </div>
                            <button type="button" class="btn btn-success" id="addSkill" style="font-size:11px;padding:3px 8px;margin-top:6px">
                                <i class="fa fa-plus"></i> Tambah
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>Lain-lain</td>
                        <td colspan="3">: <input type="text" name="other" style="width:95%" value="<?php echo htmlspecialchars($data['lain_lain']); ?>"></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="center">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan Perubahan</button>
                            <a href="permintaan_karyawan_list.php" class="btn btn-default"><i class="fa fa-times"></i> Batal</a>
                        </td>
                    </tr>
                </table>
            </form>
        <?php endif; ?>

        <p class="small" style="margin-top:18px">Form ini digunakan untuk mengubah permintaan karyawan baru yang telah tersimpan.</p>
    </div>
</div>

<script>
// Toggle untuk field lain-lain
document.getElementById('untuk_select').addEventListener('change', function() {
    var lainField = document.getElementById('untuk_lain');
    if (this.value === 'Lain-lain') {
        lainField.style.display = 'inline';
    } else {
        lainField.style.display = 'none';
    }
});

document.getElementById('education_select').addEventListener('change', function() {
    var lainField = document.getElementById('education_lain');
    if (this.value === 'Lain-lain') {
        lainField.style.display = 'inline';
    } else {
        lainField.style.display = 'none';
    }
});

// Tambah duty baru
var dutyCount = <?php echo $duty_count; ?>;
document.getElementById('addDuty').addEventListener('click', function() {
    if (dutyCount >= 15) {
        alert('Maksimal 15 tugas pekerjaan');
        return;
    }
    dutyCount++;
    var table = this.closest('table');
    var lastRow = table.rows[table.rows.length - 2]; // row before button row
    var newRow = table.insertRow(table.rows.length - 1);
    newRow.className = 'duties';
    newRow.innerHTML = '<td class="duties-number">' + dutyCount + '</td>' +
        '<td colspan="3"><input type="text" name="duty' + dutyCount + '" style="width:100%"></td>';
});

// Tambah skill baru
var skillCount = <?php echo $skill_count; ?>;
document.getElementById('addSkill').addEventListener('click', function() {
    if (skillCount >= 15) {
        alert('Maksimal 15 keahlian');
        return;
    }
    skillCount++;
    var container = document.getElementById('skills-container');
    var newDiv = document.createElement('div');
    newDiv.style.marginTop = '4px';
    newDiv.innerHTML = skillCount + '. <input type="text" name="skill' + skillCount + '" style="width:70%">';
    container.appendChild(newDiv);
});
</script>

<?php include 'layout_bottom.php'; ?>
