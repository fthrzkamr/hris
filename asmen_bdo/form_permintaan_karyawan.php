<?php
// Session check - Only Manager and Leader can access
session_start();
$chk_sess = $_SESSION['asmen_bdo'];
include("dist/config/koneksi.php");
include("dist/config/library.php");

// Get employee data including jabatan
$sql_sess = "SELECT * FROM employee WHERE npp='". $chk_sess ."'";
$ress_sess = mysqli_query($conn, $sql_sess);
$row_sess = mysqli_fetch_array($ress_sess);

$sess_mngid = $row_sess['npp'];
$sess_mngname = $row_sess['nama_emp'];
$sess_jabatan = $row_sess['jabatan'];

// Check if not logged in
if(! isset($chk_sess)) {
    header("location: ../login.php?login=false");
    exit();
}

// Check if jabatan is Manager or Leader
if($sess_jabatan !== 'Manager' && $sess_jabatan !== 'Leader') {
    header("location: index.php?error=access_denied");
    exit();
}

// Simple printable form for new employee request with saving to database

function generate_doc_no()
{
    try {
        // secure random 8-hex chars (16 hex chars -> 8 bytes) for uniqueness
        $bytes = random_bytes(4);
        return 'PMT' . strtoupper(bin2hex($bytes));
    } catch (Exception $e) {
        // fallback
        return 'PMT' . strtoupper(uniqid());
    }
}
$doc_no = generate_doc_no();
$revision = '0';
$doc_date_display = date('d-m-Y');
$doc_date_db = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ensure main table exists (column names in Bahasa Indonesia)
    $createSql = "CREATE TABLE IF NOT EXISTS permintaan_karyawan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            no_dokumen VARCHAR(50),
            revisi VARCHAR(20),
            tanggal_dokumen DATE,
            jabatan VARCHAR(255),
            unit_kerja VARCHAR(255),
            tgl_mulai DATE,
            jumlah_dibutuhkan INT,
            untuk VARCHAR(255),
            jumlah_sekarang INT,
            alasan TEXT,
            gender VARCHAR(20),
            usia VARCHAR(50),
            pendidikan VARCHAR(50),
            jurusan VARCHAR(255),
            pengalaman VARCHAR(50),
            tinggi VARCHAR(20),
            berat VARCHAR(20),
            rentang_gaji VARCHAR(100),
            lain_lain TEXT,
            created_by VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $createSql);

    // create related tables for duties and skills
    $createDuties = "CREATE TABLE IF NOT EXISTS job_duties_karyawan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            permintaan_id INT NOT NULL,
            nomor_urut INT NOT NULL,
            tugas TEXT,
            FOREIGN KEY (permintaan_id) REFERENCES permintaan_karyawan(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $createDuties);

    $createSkills = "CREATE TABLE IF NOT EXISTS skills_karyawan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            permintaan_id INT NOT NULL,
            nomor INT NOT NULL,
            skill VARCHAR(255),
            FOREIGN KEY (permintaan_id) REFERENCES permintaan_karyawan(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $createSkills);

    // collect inputs
    $jabatan = $_POST['jabatan'] ?? '';
    $unit_kerja = $_POST['unit_kerja'] ?? '';
    $tgl_mulai = isset($_POST['tgl_mulai']) ? trim($_POST['tgl_mulai']) : '';
    if ($tgl_mulai === '') $tgl_mulai = null;
    // Ensure integer fields are properly converted
    $jumlah_dibutuhkan = isset($_POST['jumlah_dibutuhkan']) && $_POST['jumlah_dibutuhkan'] !== '' ? (int)$_POST['jumlah_dibutuhkan'] : 0;
    // handle 'Untuk' options: Penambahan, Penggantian, Lain-lain (with free text)
    $untuk_raw = $_POST['untuk'] ?? '';
    $untuk_lain = $_POST['untuk_lain'] ?? '';
    if ($untuk_raw === 'Lain-lain') {
        $untuk = trim($untuk_lain);
    } else {
        $untuk = $untuk_raw;
    }
    $jumlah_sekarang = isset($_POST['jumlah_sekarang']) && $_POST['jumlah_sekarang'] !== '' ? (int)$_POST['jumlah_sekarang'] : 0;
    $alasan = $_POST['alasan'] ?? '';

    $duties = [];
    for ($i = 1; $i <= 10; $i++) {
        $k = 'duty' . $i;
        if (!empty($_POST[$k]))
            $duties[] = trim($_POST[$k]);
    }
    $job_duties = implode("\n", $duties);

    $gender = $_POST['gender'] ?? '';
    $age = $_POST['age'] ?? '';
    // handle Pendidikan options: D3, S1, S2, Lain-lain
    $education_raw = $_POST['education'] ?? '';
    $education_lain = $_POST['education_lain'] ?? '';
    if ($education_raw === 'Lain-lain') {
        $education = trim($education_lain);
    } else {
        $education = $education_raw;
    }
    $jurusan = $_POST['jurusan'] ?? '';
    $experiences = $_POST['experiences'] ?? '';
    $height = $_POST['height'] ?? '';
    $weight = $_POST['weight'] ?? '';
    $range_salary = $_POST['range_salary'] ?? '';

    $skillsArr = [];
    for ($s = 1; $s <= 3; $s++) {
        if (!empty($_POST['skill' . $s]))
            $skillsArr[] = $_POST['skill' . $s];
    }
    $skills = implode("; ", $skillsArr);

    $other = $_POST['other'] ?? '';

    // Normalize textual inputs to uppercase for consistency
    // helper uses multibyte-safe uppercasing
    $up = function ($s) {
        if ($s === null) return null;
        return mb_strtoupper((string)$s, 'UTF-8');
    };

    $jabatan = $up($jabatan);
    $unit_kerja = $up($unit_kerja);
    $untuk = $up($untuk);
    $alasan = $up($alasan);
    // uppercase each duty line
    $job_duties = $up($job_duties);
    // keep gender as single uppercase letter
    $gender = strtoupper((string)$gender);
    $education = $up($education);
    $jurusan = $up($jurusan);
    $skills = $up($skills);
    $other = $up($other);

    // insert into permintaan_karyawan (main record)
    $stmt = mysqli_prepare($conn, "INSERT INTO permintaan_karyawan
            (no_dokumen,revisi,tanggal_dokumen,jabatan,unit_kerja,tgl_mulai,jumlah_dibutuhkan,untuk,jumlah_sekarang,alasan,gender,usia,pendidikan,jurusan,pengalaman,tinggi,berat,rentang_gaji,lain_lain,created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $types = 'ssssssisisssssssssss';  // added unit_kerja
    mysqli_stmt_bind_param(
        $stmt,
        $types,
        $doc_no,
        $revision,
        $doc_date_db,
        $jabatan,
        $unit_kerja,
        $tgl_mulai,
        $jumlah_dibutuhkan,
        $untuk,
        $jumlah_sekarang,
        $alasan,
        $gender,
        $age,
        $education,
        $jurusan,
        $experiences,
        $height,
        $weight,
        $range_salary,
        $other,
        $sess_mngid
    );
    $ok = mysqli_stmt_execute($stmt);
    if ($ok) {
        $saved = true;
        $saved_id = mysqli_insert_id($conn);

        // insert individual duties into job_duties_karyawan
        if (!empty($duties) && is_array($duties)) {
            $dstmt = mysqli_prepare($conn, "INSERT INTO job_duties_karyawan (permintaan_id, nomor_urut, tugas) VALUES (?,?,?)");
            foreach ($duties as $idx => $t) {
                $t_up = $up($t);
                $num = $idx + 1;
                mysqli_stmt_bind_param($dstmt, 'iis', $saved_id, $num, $t_up);
                mysqli_stmt_execute($dstmt);
            }
            if (isset($dstmt)) mysqli_stmt_close($dstmt);
        }

        // insert skills into skills_karyawan
        if (!empty($skillsArr) && is_array($skillsArr)) {
            $sstmt = mysqli_prepare($conn, "INSERT INTO skills_karyawan (permintaan_id, nomor, skill) VALUES (?,?,?)");
            foreach ($skillsArr as $idx => $sk) {
                $sk_up = $up($sk);
                $num = $idx + 1;
                mysqli_stmt_bind_param($sstmt, 'iis', $saved_id, $num, $sk_up);
                mysqli_stmt_execute($sstmt);
            }
            if (isset($sstmt)) mysqli_stmt_close($sstmt);
        }

    } else {
        $error = mysqli_error($conn);
    }
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Form Permintaan Karyawan Baru</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
        }

        .hint {
            font-size: 12px;
            color: #666;
            margin-left: 8px
        }

        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 10px
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .title {
            flex: 1;
            text-align: center;
            font-weight: 700;
            font-size: 22px
        }

        table.form {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px
        }

        table.form td,
        table.form th {
            border: 1px solid #000;
            padding: 6px;
            font-size: 13px
        }

        .no-border td {
            border: 0
        }

        .duties tr td {
            border: 1px solid #000;
            height: 24px
        }

        .duties-number {
            width: 40px;
            text-align: center
        }

        .center {
            text-align: center
        }

        .small {
            font-size: 12px
        }

        .checkbox {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1px solid #000;
            margin-right: 6px;
            vertical-align: middle
        }

        .print-controls {
            margin-bottom: 8px
        }

        .print-value {
            display: none;
            margin-left: 6px;
            font-weight: 600
        }

        .radio-text { display:inline }

        @media print {

            .print-controls,
            .hint {
                display: none !important;
            }

            /* hide the green saved notice when printing */
            .saved-notice {
                display: none !important;
            }

            /* hide document metadata column when printing */
            .doc-meta {
                display: none !important;
            }

            select,
            input[type="text"],
            input[type="number"],
            input[type="date"],
            textarea,
            input[type="radio"],
            button {
                display: none !important;
            }

            .print-value {
                display: inline !important;
            }
            .radio-text { display:none !important; }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="print-controls">
            <button onclick="window.location.href='index.php'" style="margin-right:10px">Kembali</button>
            <button onclick="window.print()">Cetak / Print</button>
        </div>

        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <?php if (!empty($saved)): ?>
            <div class="saved-notice" style="padding:8px;background:#e6ffe6;border:1px solid #0a0;color:#060;margin-bottom:10px">Data tersimpan </div>
        <?php elseif (!empty($error)): ?>
            <div style="padding:8px;background:#ffe6e6;border:1px solid #a00;color:#800;margin-bottom:10px">Terjadi
                kesalahan: <?php echo htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="header">
            <div class="doc-meta" style="width:18%">
                <div style="border:1px solid #000;padding:8px;font-size:12px;text-align:left">
                    <strong>No. Dokumen:</strong> <?php echo htmlspecialchars($doc_no) ?><br>
                    <strong>Revisi:</strong> <?php echo htmlspecialchars($revision) ?><br>
                    <strong>Tanggal Dokumen:</strong> <?php echo htmlspecialchars($doc_date_display) ?>
                </div>
            </div>
            <div class="title">FORM PERMINTAAN KARYAWAN BARU</div>
            <div style="width:18%"></div>
        </div>

        <form method="post" action="">
            <table class="form">
                <tr>
                    <td style="width:20%">Jabatan</td>
                    <td style="width:40%">: <input type="text" style="width:95%" name="jabatan"
                            value="<?php echo htmlspecialchars($_POST['jabatan'] ?? '') ?>"><span class="hint">Contoh:
                            Staff Administrasi (maks 255 karakter)</span></td>
                    <td style="width:20%">Unit Kerja</td>
                    <td>: <input type="text" style="width:95%" name="unit_kerja"
                            value="<?php echo htmlspecialchars($_POST['unit_kerja'] ?? '') ?>"><span class="hint">Contoh: IT, HR, Finance, dll.</span></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td style="width:20%">Tanggal Mulai Bekerja</td>
                    <td>: <input type="date" name="tgl_mulai"
                            value="<?php echo htmlspecialchars($_POST['tgl_mulai'] ?? '') ?>"><span class="hint">Format:
                            YYYY-MM-DD</span></td>
                </tr>
                <tr>
                    <td>Jumlah dibutuhkan</td>
                    <td>: <input type="number" name="jumlah_dibutuhkan" style="width:80px"
                            value="<?php echo htmlspecialchars($_POST['jumlah_dibutuhkan'] ?? '') ?>"><span
                            class="hint">Angka saja</span></td>
                    <td>Untuk</td>
                    <td>:
                        <?php
                        $options = ['Penambahan', 'Penggantian', 'Lain-lain'];
                        $post_untuk = $_POST['untuk'] ?? '';
                        // if user submitted a custom value without selecting, treat as Lain-lain
                        $select_val = in_array($post_untuk, $options) ? $post_untuk : ($post_untuk !== '' ? 'Lain-lain' : '');
                        $lain_val = '';
                        if ($select_val === 'Lain-lain') {
                            $lain_val = $_POST['untuk_lain'] ?? ($_POST['untuk'] ?? '');
                        }
                        ?>
                        <select name="untuk" id="untuk_select">
                            <option value="">--Pilih--</option>
                            <?php foreach ($options as $opt): ?>
                                <option value="<?php echo $opt ?>" <?php echo ($select_val === $opt) ? 'selected' : ''; ?>>
                                    <?php echo $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                        <textarea name="untuk_lain" id="untuk_lain" placeholder="Jelaskan jika Lain-lain"
                            style="width:35%;margin-left:8px;min-height:60px;"><?php echo htmlspecialchars($lain_val) ?></textarea>
                        <div><span class="hint">Pilih 'Penambahan' jika menambah karyawan, 'Penggantian' jika mengganti,
                                atau jelaskan pada Lain-lain.</span></div>
                        <script>
                            (function () {
                                function toggle() {
                                    var sel = document.getElementById('untuk_select');
                                    var lain = document.getElementById('untuk_lain');
                                    if (!sel || !lain) return;
                                    lain.style.display = sel.value === 'Lain-lain' ? 'inline-block' : 'none';
                                }
                                document.getElementById('untuk_select').addEventListener('change', toggle);
                                window.addEventListener('load', toggle);
                            })();
                        </script>
                    </td>
                </tr>
                <tr>
                    <td>Jumlah Karyawan Sekarang</td>
                    <td>: <input type="number" name="jumlah_sekarang" style="width:80px"
                            value="<?php echo htmlspecialchars($_POST['jumlah_sekarang'] ?? '') ?>"><span
                            class="hint">Angka saja</span></td>
                    <td>Alasan</td>
                    <td>: <input type="text" name="alasan" style="width:60%"
                            value="<?php echo htmlspecialchars($_POST['alasan'] ?? '') ?>"><span class="hint">Singkat,
                            maks 255 karakter</span></td>
                </tr>
            </table>

            <table class="form" style="margin-top:18px">
                <tr>
                    <th colspan="4" class="center">Job Duties*</th>
                </tr>
                <tr>
                    <td colspan="4" class="center"><span class="hint">Isi setiap tugas singkat (mis. menjawab telepon,
                            input data). Maks ~200 karakter per baris.</span></td>
                </tr>
                <tr class="duties">
                    <td class="duties-number">1</td>
                    <td colspan="3"><input type="text" name="duty1" style="width:100%"
                            value="<?php echo htmlspecialchars($_POST['duty1'] ?? '') ?>"></td>
                </tr>
                <tr class="duties">
                    <td class="duties-number">2</td>
                    <td colspan="3"><input type="text" name="duty2" style="width:100%"
                            value="<?php echo htmlspecialchars($_POST['duty2'] ?? '') ?>"></td>
                </tr>
                <tr class="duties">
                    <td class="duties-number">3</td>
                    <td colspan="3"><input type="text" name="duty3" style="width:100%"
                            value="<?php echo htmlspecialchars($_POST['duty3'] ?? '') ?>"></td>
                </tr>
                <tr class="duties">
                    <td class="duties-number">4</td>
                    <td colspan="3"><input type="text" name="duty4" style="width:100%"
                            value="<?php echo htmlspecialchars($_POST['duty4'] ?? '') ?>"></td>
                </tr>
                <tr class="duties">
                    <td class="duties-number">5</td>
                    <td colspan="3"><input type="text" name="duty5" style="width:100%"
                            value="<?php echo htmlspecialchars($_POST['duty5'] ?? '') ?>"></td>
                </tr>
                <tr class="duties">
                    <td class="duties-number">6</td>
                    <td colspan="3"><input type="text" name="duty6" style="width:100%"
                            value="<?php echo htmlspecialchars($_POST['duty6'] ?? '') ?>"></td>
                </tr>
                <tr class="duties">
                    <td class="duties-number">7</td>
                    <td colspan="3"><input type="text" name="duty7" style="width:100%"
                            value="<?php echo htmlspecialchars($_POST['duty7'] ?? '') ?>"></td>
                </tr>
                <tr class="duties">
                    <td class="duties-number">8</td>
                    <td colspan="3"><input type="text" name="duty8" style="width:100%"
                            value="<?php echo htmlspecialchars($_POST['duty8'] ?? '') ?>"></td>
                </tr>
                <tr class="duties">
                    <td class="duties-number">9</td>
                    <td colspan="3"><input type="text" name="duty9" style="width:100%"
                            value="<?php echo htmlspecialchars($_POST['duty9'] ?? '') ?>"></td>
                </tr>
                <tr class="duties">
                    <td class="duties-number">10</td>
                    <td colspan="3"><input type="text" name="duty10" style="width:100%"
                            value="<?php echo htmlspecialchars($_POST['duty10'] ?? '') ?>"></td>
                </tr>
                <tr>
                    <td colspan="4" class="center small">*jika berbeda dari pencarian sebelumnya</td>
                </tr>
            </table>

            <table class="form" style="margin-top:12px">
                <tr>
                    <th colspan="4" class="center">Requirements</th>
                </tr>
                <tr>
                    <td style="width:18%">Jenis Kelamin</td>
                    <td style="width:32%">:
                        <label><input type="radio" name="gender" value="L" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'L') ? 'checked' : '' ?>><span class="radio-text"> L</span></label>
                        &nbsp;
                        <label><input type="radio" name="gender" value="P" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'P') ? 'checked' : '' ?>><span class="radio-text"> P</span></label>
                        <span class="hint">L = Laki-laki, P = Perempuan</span>
                    </td>
                    <td style="width:18%">Usia</td>
                    <td>: <input type="text" name="age" style="width:100px"
                            value="<?php echo htmlspecialchars($_POST['age'] ?? '') ?>"></td>
                </tr>
                <tr>
                    <td>Pendidikan</td>
                    <td>:
                        <?php
                        $edu_opts = ['D3', 'S1', 'S2', 'Lain-lain'];
                        $post_edu = $_POST['education'] ?? '';
                        $edu_select = in_array($post_edu, $edu_opts) ? $post_edu : ($post_edu !== '' ? 'Lain-lain' : '');
                        $edu_lain_val = '';
                        if ($edu_select === 'Lain-lain') {
                            $edu_lain_val = $_POST['education_lain'] ?? ($_POST['education'] ?? '');
                        }
                        ?>
                        <select name="education" id="education_select">
                            <option value="">--Pilih--</option>
                            <?php foreach ($edu_opts as $eo): ?>
                                <option value="<?php echo $eo ?>" <?php echo ($edu_select === $eo) ? 'selected' : ''; ?>>
                                    <?php echo $eo ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="education_lain" id="education_lain"
                            placeholder="Jelaskan jika Lain-lain" style="width:35%;margin-left:8px;"
                            value="<?php echo htmlspecialchars($edu_lain_val) ?>">
                        <span class="hint">Pilih tingkat pendidikan. Jika Lain-lain, tulis keterangan.</span>
                        <script>
                            (function () {
                                function toggleEdu() {
                                    var sel = document.getElementById('education_select');
                                    var lain = document.getElementById('education_lain');
                                    if (!sel || !lain) return;
                                    lain.style.display = sel.value === 'Lain-lain' ? 'inline-block' : 'none';
                                }
                                document.getElementById('education_select').addEventListener('change', toggleEdu);
                                window.addEventListener('load', toggleEdu);
                            })();
                        </script>
                    </td>
                    <td>Jurusan</td>
                    <td>: <input type="text" name="jurusan"
                            value="<?php echo htmlspecialchars($_POST['jurusan'] ?? '') ?>"></td>
                </tr>
                <tr>
                    <td>Pengalaman</td>
                    <td>: <input type="text" name="experiences" style="width:120px"
                            value="<?php echo htmlspecialchars($_POST['experiences'] ?? '') ?>"> tahun <span
                            class="hint">Isi angka tahun (mis. 2)</span></td>
                    <td>Tinggi dan Berat</td>
                    <td>: <input type="text" name="height" style="width:60px"
                            value="<?php echo htmlspecialchars($_POST['height'] ?? '') ?>"> cm &nbsp; <input type="text"
                            name="weight" style="width:60px"
                            value="<?php echo htmlspecialchars($_POST['weight'] ?? '') ?>"> kg <span class="hint">Angka
                            saja</span></td>
                </tr>
                <tr>
                    <td>Rentang Gaji</td>
                    <td>: Rp <input type="text" name="range_salary" style="width:150px"
                            value="<?php echo htmlspecialchars($_POST['range_salary'] ?? '') ?>"><span class="hint">Angka
                            tanpa titik/koma, mis. 5000000</span></td>
                    <td>Keahlian dan Kemampuan</td>
                    <td>:
                        <div style="margin-top:6px">1. <input type="text" name="skill1" style="width:70%"
                                value="<?php echo htmlspecialchars($_POST['skill1'] ?? '') ?>"></div>
                        <div>2. <input type="text" name="skill2" style="width:70%"
                                value="<?php echo htmlspecialchars($_POST['skill2'] ?? '') ?>"></div>
                        <div>3. <input type="text" name="skill3" style="width:70%"
                                value="<?php echo htmlspecialchars($_POST['skill3'] ?? '') ?>"></div>
                        <div><span class="hint">Contoh: Microsoft Office, Komunikasi, SQL</span></div>
                    </td>
                </tr>
                <tr>
                    <td>Lain-lain</td>
                    <td colspan="3">: <input type="text" name="other" style="width:95%"
                            value="<?php echo htmlspecialchars($_POST['other'] ?? '') ?>"></td>
                </tr>
                <tr>
                    <td colspan="4" class="center">
                        <button type="submit">Simpan</button>
                        <button type="reset" id="btnReset" style="margin-left:10px">Reset</button>
                    </td>
                </tr>
            </table>
        </form>

        <script>
            // Sync visible input/select/textarea values into adjacent .print-value spans
            function syncPrintValues() {
                var form = document.querySelector('form');
                if (!form) return;
                var processedRadio = {};
                var elems = form.querySelectorAll('input, select, textarea');
                elems.forEach(function (el) {
                    var type = (el.type || el.tagName).toLowerCase();
                    if (type === 'submit' || type === 'button' || type === 'file' || el.type === 'hidden') return;
                    // handle radios once per name
                    if (el.type === 'radio') {
                        if (processedRadio[el.name]) return;
                        processedRadio[el.name] = true;
                        var val = '';
                        var checked = form.querySelector('input[name="' + el.name + '"]:checked');
                        if (checked) val = checked.value;
                        // place span after the last radio of the group if present
                        var group = form.querySelectorAll('input[name="' + el.name + '"]');
                        var last = group[group.length - 1];
                        ensurePrintSpan(last, val);
                        return;
                    }
                    // special handling for certain selects to combine with related textarea
                    if (el.id === 'untuk_select') {
                        var val = '';
                        if (el.value === 'Lain-lain') {
                            var t = document.getElementById('untuk_lain'); if (t) val = t.value || '';
                        } else {
                            val = el.options[el.selectedIndex] ? el.options[el.selectedIndex].text : el.value;
                        }
                        ensurePrintSpan(el, val);
                        return;
                    }
                    if (el.id === 'education_select') {
                        var val = '';
                        if (el.value === 'Lain-lain') { var t = document.getElementById('education_lain'); if (t) val = t.value || ''; }
                        else { val = el.options[el.selectedIndex] ? el.options[el.selectedIndex].text : el.value; }
                        ensurePrintSpan(el, val);
                        return;
                    }
                    // generic value
                    var v = '';
                    if (el.tagName.toLowerCase() === 'select') v = el.options[el.selectedIndex] ? el.options[el.selectedIndex].text : '';
                    else if (el.tagName.toLowerCase() === 'textarea') v = el.value;
                    else v = el.value;
                    ensurePrintSpan(el, v);
                });
            }
            function ensurePrintSpan(el, text) {
                if (!el) return;
                var next = el.nextElementSibling;
                if (next && next.classList && next.classList.contains('print-value')) {
                    next.textContent = text || '';
                    return next;
                }
                // create span and insert after element
                var sp = document.createElement('span');
                sp.className = 'print-value';
                sp.textContent = text || '';
                if (el.nextSibling) el.parentNode.insertBefore(sp, el.nextSibling);
                else el.parentNode.appendChild(sp);
                return sp;
            }

            // Sync before printing and on load
            document.addEventListener('DOMContentLoaded', syncPrintValues);
            if (window.matchMedia) {
                var mq = window.matchMedia('print');
                if (mq && mq.addListener) mq.addListener(function (m) { if (m.matches) syncPrintValues(); });
            }
            window.addEventListener('beforeprint', syncPrintValues);

            // also sync when user clicks the Cetak button
            document.querySelectorAll('.print-controls button').forEach(function (b) {
                b.addEventListener('click', function (e) { syncPrintValues(); });
            });

            // when the form is reset, update the printed value spans after reset completes
            var theForm = document.querySelector('form');
            if (theForm) {
                theForm.addEventListener('reset', function () {
                    // allow the browser to perform reset first
                    setTimeout(syncPrintValues, 0);
                });
            }

            // Client-side validation with SweetAlert2: require all fields
            document.querySelector('form').addEventListener('submit', function (ev) {
                ev.preventDefault();
                syncPrintValues();
                var missing = [];

                var fieldNames = {
                    jabatan: 'Jabatan',
                    tgl_mulai: 'Tanggal Mulai Bekerja',
                    jumlah_dibutuhkan: 'Jumlah dibutuhkan',
                    untuk: 'Untuk',
                    untuk_lain: 'Untuk (keterangan)',
                    jumlah_sekarang: 'Jumlah Karyawan Sekarang',
                    alasan: 'Alasan',
                    gender: 'Jenis Kelamin',
                    age: 'Usia',
                    education: 'Pendidikan',
                    education_lain: 'Pendidikan (keterangan)',
                    jurusan: 'Jurusan',
                    experiences: 'Pengalaman (tahun)',
                    height: 'Tinggi (cm)',
                    weight: 'Berat (kg)',
                    range_salary: 'Rentang Gaji',
                    skill1: 'Keahlian 1', skill2: 'Keahlian 2', skill3: 'Keahlian 3',
                    other: 'Lain-lain'
                };

                // Helper to check a single field by name
                function checkField(name){
                    var el = document.getElementsByName(name)[0];
                    if(!el) return false;
                    var tag = el.tagName.toLowerCase();
                    if(tag === 'input'){
                        if(el.type === 'radio'){
                            return document.querySelector('input[name="'+name+'"]:checked') != null;
                        }
                        return el.value.toString().trim() !== '';
                    }else if(tag === 'select' || tag === 'textarea'){
                        return el.value.toString().trim() !== '';
                    }
                    return false;
                }

                // Validate all named fields
                for(var key in fieldNames){
                    if(!Object.prototype.hasOwnProperty.call(fieldNames, key)) continue;
                    // special cases
                    if(key === 'untuk_lain') continue; // handled with 'untuk'
                    if(key === 'education_lain') continue; // handled with education

                    var ok = checkField(key);
                    if(!ok){
                        // if untuk and value is Lain-lain, require untuk_lain
                        if(key === 'untuk'){
                            var sel = document.getElementsByName('untuk')[0];
                            var val = sel ? sel.value : '';
                            if(val === 'Lain-lain'){
                                var ok2 = checkField('untuk_lain');
                                if(!ok2) missing.push(fieldNames['untuk_lain']);
                            }
                            if(val === '') missing.push(fieldNames[key]);
                        } else if(key === 'education'){
                            var es = document.getElementsByName('education')[0];
                            var valE = es ? es.value : '';
                            if(valE === 'Lain-lain'){
                                var ok3 = checkField('education_lain');
                                if(!ok3) missing.push(fieldNames['education_lain']);
                            }
                            if(valE === '') missing.push(fieldNames[key]);
                        } else {
                            missing.push(fieldNames[key]);
                        }
                    }
                }

                // Require at least one Job Duty (duty1..duty10)
                var dutyOk = false;
                for (var i = 1; i <= 10; i++) {
                    var d = document.getElementsByName('duty' + i)[0];
                    if (d && d.value.toString().trim() !== '') { dutyOk = true; break; }
                }
                if (!dutyOk) {
                    missing.push('Job Duties (minimal 1)');
                }

                if(missing.length){
                    Swal.fire({
                        icon: 'error',
                        title: 'Field wajib belum lengkap',
                        html: '<p>Silakan lengkapi field berikut:</p><ul style="text-align:left">'+ missing.map(function(m){ return '<li>'+m+'</li>'; }).join('') +'</ul>'
                    });
                    return false;
                }

                // all required fields present -> submit
                ev.target.submit();
            });
        </script>

        <p class="small">Form ini dibuat untuk permintaan karyawan baru. Silakan lengkapi data di atas dan cetak untuk
            proses persetujuan.</p>
    </div>
</body>

</html>
