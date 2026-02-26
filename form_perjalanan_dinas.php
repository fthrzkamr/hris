<?php
// Simple printable form for business trip budget with saving to database

function generate_doc_no()
{
    try {
        $bytes = random_bytes(4);
        return 'PJD' . strtoupper(bin2hex($bytes));
    } catch (Exception $e) {
        return 'PJD' . strtoupper(uniqid());
    }
}
$doc_no = generate_doc_no();
$revision = '0';
$doc_date_display = date('d-m-Y');

$saved = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/dist/config/koneksi.php';

    // create main table
    $createMain = "CREATE TABLE IF NOT EXISTS perjalanan_dinas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        no_dokumen VARCHAR(50),
        revisi VARCHAR(20),
        tanggal_dokumen DATE,
        nama VARCHAR(255),
        npp VARCHAR(50),
        departemen VARCHAR(255),
        tanggal_perjalanan VARCHAR(100),
        jumlah_hari INT,
        kota_asal VARCHAR(255),
        kota_tujuan VARCHAR(255),
        tujuan TEXT,
        budget_total VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $createMain);

    // create rincian table
    $createRincian = "CREATE TABLE IF NOT EXISTS perjalanan_rincian (
        id INT AUTO_INCREMENT PRIMARY KEY,
        perjalanan_id INT NOT NULL,
        nomor INT,
        ket VARCHAR(255),
        nominal VARCHAR(100),
        qty INT,
        perkiraan VARCHAR(100),
        total VARCHAR(100),
        keterangan VARCHAR(255),
        FOREIGN KEY (perjalanan_id) REFERENCES perjalanan_dinas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $createRincian);

    // collect inputs
    $nama = $_POST['nama'] ?? '';
    $npp = $_POST['npp'] ?? '';
    $departemen = $_POST['departemen'] ?? '';
    $tanggal = $_POST['tanggal'] ?? '';
    $jumlah_hari = !empty($_POST['jumlah_hari']) ? intval($_POST['jumlah_hari']) : null;
    $kota_asal = $_POST['kota_asal'] ?? '';
    $kota_tujuan = $_POST['kota_tujuan'] ?? '';
    $tujuan = $_POST['tujuan'] ?? '';
    $budget_total = $_POST['budget_total'] ?? '';
    $doc_date_db = date('Y-m-d');

    // insert main record
    $stmt = mysqli_prepare($conn, "INSERT INTO perjalanan_dinas (no_dokumen,revisi,tanggal_dokumen,nama,npp,departemen,tanggal_perjalanan,jumlah_hari,kota_asal,kota_tujuan,tujuan,budget_total) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'sssssssissss', $doc_no, $revision, $doc_date_db, $nama, $npp, $departemen, $tanggal, $jumlah_hari, $kota_asal, $kota_tujuan, $tujuan, $budget_total);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok) {
        $saved = true;
        $perjalanan_id = mysqli_insert_id($conn);

        // insert rincian rows
        $kets = $_POST['ket'] ?? [];
        $nominals = $_POST['nominal'] ?? [];
        $qtys = $_POST['qty'] ?? [];
        $perkiraans = $_POST['perkiraan'] ?? [];
        $totals = $_POST['total'] ?? [];
        $keterangans = $_POST['keterangan'] ?? [];

        $rstmt = mysqli_prepare($conn, "INSERT INTO perjalanan_rincian (perjalanan_id, nomor, ket, nominal, qty, perkiraan, total, keterangan) VALUES (?,?,?,?,?,?,?,?)");
        foreach ($kets as $i => $v) {
            $v_trim = trim($v);
            if ($v_trim === '') continue;
            $nom = $nominals[$i] ?? '';
            $q = isset($qtys[$i]) && $qtys[$i] !== '' ? intval($qtys[$i]) : null;
            $pr = $perkiraans[$i] ?? '';
            $to = $totals[$i] ?? '';
            $ketx = $keterangans[$i] ?? '';
            $num = $i + 1;
            mysqli_stmt_bind_param($rstmt, 'iississs', $perjalanan_id, $num, $v_trim, $nom, $q, $pr, $to, $ketx);
            mysqli_stmt_execute($rstmt);
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
    <title>Form Anggaran Perjalanan Dinas</title>
    <link href="libs/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
        }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 10px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .logo {
            width: 140px;
        }
        .logo img {
            max-width: 100%;
            height: auto;
        }
        .title {
            flex: 1;
            text-align: center;
            font-weight: 700;
            font-size: 18px;
        }
        .doc-box {
            border: 1px solid #000;
            padding: 8px;
            font-size: 11px;
            width: 140px;
        }
        table.form {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        table.form td, table.form th {
            border: 1px solid #000;
            padding: 6px;
            font-size: 13px;
        }
        .rincian-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .rincian-table td, .rincian-table th {
            border: 1px solid #000;
            padding: 6px;
            font-size: 12px;
        }
        .signature {
            height: 70px;
            text-align: center;
        }
        .print-controls {
            margin-bottom: 10px;
        }
        .notes {
            font-size: 11px;
            margin-top: 10px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        .notes h4 {
            margin-top: 0;
            font-size: 12px;
        }
        @page { size: A4 portrait; margin: 10mm; }
        @media print {
            html, body { height: auto; }
            body { font-size: 11px; -webkit-print-color-adjust: exact; }
            .print-controls, .notes, .saved-notice { display: none !important; }
            input[type="text"], input[type="number"], input[type="date"], button { display: none !important; }
            .print-value { display: inline !important; }
            .field-hint { display: none !important; }

            /* Compact table styles so more fits on one page */
            .container { max-width: 100%; margin: 0; padding: 6px; }
            table, th, td { font-size: 11px; }
            .rincian-table th, .rincian-table td { padding: 4px; }

            /* Prevent unwanted breaks inside key blocks and rows */
            .rincian-table tr, .rincian-table, .header, .doc-box, .director-approval {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                -webkit-column-break-inside: avoid !important;
                -webkit-page-break-inside: avoid !important;
            }

            /* If browser still splits, reduce signature height slightly for print */
            .director-approval .signature { height: 100px; }
        }
        .print-value {
            display: none;
            margin-left: 6px;
            font-weight: 600;
        }
        .field-hint { font-size:11px; color:#666; margin-top:4px; display:block; }
        /* field hints already hidden in print rules above */
    </style>
</head>
<body>
    <div class="container">
        <div class="print-controls">
            <button onclick="window.print()">Cetak / Print</button>
        </div>

        <?php if (!empty($saved)): ?>
            <div class="saved-notice" style="padding:8px;background:#e6ffe6;border:1px solid #0a0;color:#060;margin-bottom:10px">Data tersimpan</div>
        <?php elseif (!empty($error)): ?>
            <div style="padding:8px;background:#ffe6e6;border:1px solid #a00;color:#800;margin-bottom:10px">Terjadi kesalahan: <?php echo htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="header">
            <div class="logo">
                <img src="foto/logo-dua.webp" alt="Logo">
            </div>
            <div class="title">
                FORM ANGGARAN<br>
                PERJALANAN BISNIS (DINAS)
            </div>
            <div class="doc-box">
                <div><strong>No. Dokumen:</strong> <?php echo htmlspecialchars($doc_no) ?></div>
                <div><strong>Revisi:</strong> <?php echo htmlspecialchars($revision) ?></div>
                <div><strong>Tanggal:</strong> <?php echo htmlspecialchars($doc_date_display) ?></div>
            </div>
        </div>

        <div class="notes">
            <h4>Ketentuan Perjalanan Dinas:</h4>
            <p><strong>Hotel (per malam):</strong></p>
            <ul style="margin:5px 0 10px 20px">
                <li>Manager Level: Rp 500.000</li>
                <li>Asisten Manager: Rp 400.000</li>
                <li>Team Leader: Rp 350.000</li>
            </ul>
            <p><strong>Uang Makan (per hari):</strong></p>
            <ul style="margin:5px 0 10px 20px">
                <li>Manager: Rp 125.000/day</li>
                <li>Asisten Manager: Rp 100.000/day</li>
                <li>Staff: Rp 75.000/day</li>
            </ul>
            <p><strong>Definisi Up Country:</strong> Menginap dan minimal jarak 120 kilometer</p>
        </div>

        <?php
        // Siapkan daftar nama karyawan untuk autocomplete (datalist)
        // Bahasa: komentar ini menggunakan Bahasa Indonesia.
        $employee_names = [];
        $employee_npps = [];
        if (!isset($conn) && file_exists(__DIR__ . '/dist/config/koneksi.php')) {
            include __DIR__ . '/dist/config/koneksi.php';
        }
        if (isset($conn)) {
            $candidates = ['employee','employees','karyawan','pegawai','tbl_employee','mst_employee'];
            $found = '';
            foreach ($candidates as $t) {
                $t_esc = mysqli_real_escape_string($conn, $t);
                $res = @mysqli_query($conn, "SHOW TABLES LIKE '" . $t_esc . "'");
                if ($res && mysqli_num_rows($res) > 0) { $found = $t; break; }
            }
            if ($found) {
                // Cek apakah kolom 'aktif' ada di tabel; jika ada, hanya ambil baris dengan aktif='Aktif'
                $hasAktif = false;
                $colChk = @mysqli_query($conn, "SHOW COLUMNS FROM `" . mysqli_real_escape_string($conn, $found) . "` LIKE 'aktif'");
                if ($colChk && mysqli_num_rows($colChk) > 0) {
                    $hasAktif = true;
                }

                // Cek apakah kolom npp ada
                $hasNpp = false;
                $colNppChk = @mysqli_query($conn, "SHOW COLUMNS FROM `" . mysqli_real_escape_string($conn, $found) . "` LIKE 'npp'");
                if ($colNppChk && mysqli_num_rows($colNppChk) > 0) {
                    $hasNpp = true;
                }

                // Susun query: ambil nama_emp dan npp jika ada
                $q = "SELECT DISTINCT nama_emp" . ($hasNpp ? ", npp" : "") . " FROM `" . $found . "` WHERE nama_emp IS NOT NULL AND nama_emp<>''";
                if ($hasAktif) {
                    $q .= " AND (aktif IS NULL OR BINARY aktif = 'Aktif' OR TRIM(aktif) = '')";
                }
                $q .= " ORDER BY nama_emp LIMIT 1000";

                $res2 = @mysqli_query($conn, $q);
                if ($res2) {
                    while ($r = mysqli_fetch_assoc($res2)) {
                        $employee_names[] = $r['nama_emp'];
                        if ($hasNpp) $employee_npps[$r['nama_emp']] = $r['npp'];
                    }
                }
            }
        }
        ?>

        <form method="post" action="">
            <script>
            // Force uppercase for all text inputs except 'Nama' (name="nama")
            document.addEventListener('DOMContentLoaded', function() {
                var form = document.querySelector('form');
                if (!form) return;
                form.querySelectorAll('input[type="text"]').forEach(function(input) {
                    if (input.name && input.name.toLowerCase() === 'nama') return; // skip 'Nama'
                    input.addEventListener('input', function() {
                        this.value = this.value.toUpperCase();
                    });
                });
                // On submit, force all text inputs except 'Nama' to uppercase (for autofill/paste)
                form.addEventListener('submit', function() {
                    form.querySelectorAll('input[type="text"]').forEach(function(input) {
                        if (input.name && input.name.toLowerCase() === 'nama') return;
                        input.value = input.value.toUpperCase();
                    });
                });
            });
            </script>
            <table class="form">
                <tr>
                    <td style="width:160px">Nama</td>
                    <td style="width:10px">:</td>
                    <td>
                        <input type="text" name="nama" id="nama-input" list="employees-list" style="width:95%" value="<?php echo htmlspecialchars($_POST['nama'] ?? '') ?>">
                        <span class="field-hint">Nama lengkap sesuai identitas (KTP). Maks 255 karakter.</span>
                        <input type="hidden" name="npp" id="npp-input" value="<?php echo htmlspecialchars($_POST['npp'] ?? '') ?>">
                    </td>
                    <td style="width:160px">Departemen</td>
                    <td style="width:10px">:</td>
                    <td>
                        <input type="text" name="departemen" style="width:95%" value="<?php echo htmlspecialchars($_POST['departemen'] ?? '') ?>">
                        <span class="field-hint">Nama departemen atau unit kerja (mis. Sales, Finance).</span>
                    </td>
                </tr>
                <tr>
                    <td>Tanggal Perjalanan</td>
                    <td>:</td>
                    <td>
                        <input type="date" name="tanggal" style="width:95%" placeholder="dd-mm-yyyy" value="<?php echo htmlspecialchars($_POST['tanggal'] ?? '') ?>">
                        <span class="field-hint">Tanggal perjalanan (format dd-mm-yyyy). Kosongkan jika tanggal fleksibel.</span>
                    </td>
                    <td>Jumlah Hari</td>
                    <td>:</td>
                    <td>
                        <input type="number" name="jumlah_hari" style="width:95%" value="<?php echo htmlspecialchars($_POST['jumlah_hari'] ?? '') ?>">
                        <span class="field-hint">Jumlah hari perjalanan (angka bulat, termasuk hari menginap).</span>
                    </td>
                </tr>
                <tr>
                    <td>Kota Asal</td>
                    <td>:</td>
                    <td>
                        <input type="text" name="kota_asal" style="width:95%" value="<?php echo htmlspecialchars($_POST['kota_asal'] ?? '') ?>">
                        <span class="field-hint">Kota keberangkatan (nama kota saja).</span>
                    </td>
                    <td>Kota Tujuan</td>
                    <td>:</td>
                    <td>
                        <input type="text" name="kota_tujuan" style="width:95%" value="<?php echo htmlspecialchars($_POST['kota_tujuan'] ?? '') ?>">
                        <span class="field-hint">Kota tujuan perjalanan.</span>
                    </td>
                </tr>
                <tr>
                    <td>Tujuan Perjalanan Bisnis</td>
                    <td>:</td>
                    <td colspan="4">
                        <input type="text" name="tujuan" style="width:98%" value="<?php echo htmlspecialchars($_POST['tujuan'] ?? '') ?>">
                        <span class="field-hint">Tujuan singkat dan alasan perjalanan (mis. Kunjungan pelanggan, training).</span>
                    </td>
                </tr>
            </table>

            <table class="rincian-table">
                <thead>
                    <tr style="background:#f0f0f0;">
                        <th style="width:40px">NO</th>
                        <th>KETERANGAN</th>
                        <th style="width:100px">Nominal</th>
                        <th style="width:60px">Qty</th>
                        <th style="width:100px">Perkiraan</th>
                        <th style="width:100px">TOTAL</th>
                        <th style="width:140px">KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $posted_kets = $_POST['ket'] ?? [];
                    $posted_nominals = $_POST['nominal'] ?? [];
                    $posted_qtys = $_POST['qty'] ?? [];
                    $posted_perkiraans = $_POST['perkiraan'] ?? [];
                    $posted_totals = $_POST['total'] ?? [];
                    $posted_keterangans = $_POST['keterangan'] ?? [];
                    for ($i=0;$i<7;$i++): 
                        $num = $i + 1;
                    ?>
                    <tr>
                        <td style="text-align:center"><?= $num; ?></td>
                        <td>
                            <input type="text" name="ket[]" class="row-ket" style="width:95%" value="<?php echo htmlspecialchars($posted_kets[$i] ?? '') ?>">
                            <span class="field-hint">Jenis biaya (mis. Hotel, Transport, Uang Makan).</span>
                        </td>
                        <td>
                            <input type="text" name="nominal[]" class="row-nominal" inputmode="numeric" style="width:90%" value="<?php echo htmlspecialchars($posted_nominals[$i] ?? '') ?>">
                            <span class="field-hint">Nominal per unit (angka, contoh: 500000).</span>
                        </td>
                        <td>
                            <input type="number" name="qty[]" class="row-qty" style="width:90%" value="<?php echo htmlspecialchars($posted_qtys[$i] ?? '') ?>">
                            <span class="field-hint">Jumlah unit atau malam (angka bulat).</span>
                        </td>
                        <td>
                            <input type="text" name="perkiraan[]" class="row-perkiraan" style="width:90%" value="<?php echo htmlspecialchars($posted_perkiraans[$i] ?? '') ?>">
                            <span class="field-hint">Perkiraan cost jika tidak memakai Qty (opsional).</span>
                        </td>
                        <td>
                            <input type="text" name="total[]" class="row-total" readonly style="width:90%" value="<?php echo htmlspecialchars($posted_totals[$i] ?? '') ?>">
                            <span class="field-hint">Total baris (otomatis: Nominal × Qty atau Perkiraan).</span>
                        </td>
                        <td>
                            <input type="text" name="keterangan[]" class="row-keterangan" style="width:95%" value="<?php echo htmlspecialchars($posted_keterangans[$i] ?? '') ?>">
                            <span class="field-hint">Keterangan tambahan (opsional).</span>
                        </td>
                    </tr>
                    <?php endfor; ?>
                    <tr>
                        <td colspan="5" style="text-align:right;font-weight:bold;">BUDGET TOTAL</td>
                        <td colspan="2">
                            <input type="text" id="budget_total" name="budget_total" readonly style="width:95%" value="<?php echo htmlspecialchars($_POST['budget_total'] ?? '') ?>">
                            <span class="field-hint">Jumlah keseluruhan semua baris (otomatis).</span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top:15px; font-size:12px">
                <strong>Rekening (Transfer):</strong><br>
                Trf Ke rek. Mandiri an. Auliya Nurul Haqim Acc. 60012166181
            </div>

            <div style="margin-top:15px; font-size:12px">
                <strong>Note:</strong>
                <ol style="margin:5px 0 0 20px; padding:0">
                    <li>Lampirkan dokumen - dokumen yang di perlukan.</li>
                    <li>Biaya - biaya yang mungkin akan terjadi dapat di tambahkan.</li>
                    <li>Rencana anggaran "sementara" 3 hari kerja sebelum perjalanan.</li>
                </ol>
            </div>

            <table style="width:100%; margin-top:20px; border:0; table-layout:fixed;">
                <tr>
                    <td style="width:25%; text-align:center; border:0">Diusulkan Oleh,</td>
                    <td style="width:25%; text-align:center; border:0">Mengetahui HRGA Manager,</td>
                    <td style="width:25%; text-align:center; border:0">Mengetahui FA Manager,</td>
                </tr>
                <tr>
                    <td class="signature" style="border:0; height:80px"></td>
                    <td class="signature" style="border:0; height:80px"></td>
                    <td class="signature" style="border:0; height:80px"></td>
                </tr>
                <tr>
                    <td style="text-align:center; border:0; font-size:11px">Nama : <input type="text" name="sign_nama_1" style="width:88%" value=""></td>
                    <td style="text-align:center; border:0; font-size:11px">Nama : Auliya Nurul Haqim</td>
                    <td style="text-align:center; border:0; font-size:11px">Nama : A. Arief Ananto</td>
                </tr>
                <tr>
                    <td style="text-align:center; border:0; font-size:11px">Tanggal : <span class="sign-date"><?php echo htmlspecialchars($doc_date_display) ?></span></td>
                    <td style="text-align:center; border:0; font-size:11px">Tanggal : <span class="sign-date">&nbsp;</span></td>
                    <td style="text-align:center; border:0; font-size:11px">Tanggal : <span class="sign-date">&nbsp;</span></td>
                </tr>
            </table>

            <div class="director-approval" style="margin-top:18px; text-align:center">
                <div style="font-weight:600;">Disetujui Oleh Direktur,</div>
                <div class="signature" style="height:90px; margin-top:6px"></div>
                <div style="margin-top:6px; font-size:11px">Nama : Lucky Hafiansyah</div>
                <div style="font-size:11px">Tanggal : </div>
            </div>

            <div style="margin-top:20px; text-align:center">
                <button type="submit" style="padding:8px 20px">Simpan</button>
                <button type="reset" style="padding:8px 20px; margin-left:10px">Reset</button>
            </div>
        </form>

        <datalist id="employees-list">
            <?php foreach ($employee_names as $e): ?>
                <option value="<?php echo htmlspecialchars($e) ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <script>
        // Map nama to NPP for autofill
        var namaToNpp = {};
        <?php foreach ($employee_npps as $ename => $npp): ?>
            namaToNpp[<?php echo json_encode($ename); ?>] = <?php echo json_encode($npp); ?>;
        <?php endforeach; ?>
        document.addEventListener('DOMContentLoaded', function() {
            var namaInput = document.getElementById('nama-input');
            var nppInput = document.getElementById('npp-input');
            if (namaInput && nppInput) {
                function updateNpp() {
                    var v = namaInput.value;
                    nppInput.value = namaToNpp[v] || '';
                }
                namaInput.addEventListener('input', updateNpp);
                // Set initial value if POST
                updateNpp();
            }
        });
        </script>

        

        <script>
            // Sync form values for printing
            function syncPrintValues() {
                var form = document.querySelector('form');
                if (!form) return;
                // Sertakan input date juga
                var elems = form.querySelectorAll('input[type="text"], input[type="number"], input[type="date"]');
                elems.forEach(function (el) {
                    var v = el.value || '';
                    // Jika input date, ubah format dari yyyy-mm-dd ke dd-mm-YYYY untuk pencetakan
                    if (el.type === 'date' && v) {
                        var m = v.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                        if (m) v = m[3] + '-' + m[2] + '-' + m[1];
                    }
                    var next = el.nextElementSibling;
                    if (next && next.classList && next.classList.contains('print-value')) {
                        next.textContent = v;
                    } else {
                        var sp = document.createElement('span');
                        sp.className = 'print-value';
                        sp.textContent = v;
                        if (el.nextSibling) el.parentNode.insertBefore(sp, el.nextSibling);
                        else el.parentNode.appendChild(sp);
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', syncPrintValues);
            window.addEventListener('beforeprint', syncPrintValues);

            document.querySelector('form').addEventListener('reset', function () {
                setTimeout(syncPrintValues, 0);
            });

            // --- Automatic calculation: Nominal x Qty per row, and Budget Total ---
            function parseNumber(v) {
                if (v === null || v === undefined) return 0;
                v = v.toString();
                var num = v.replace(/[^0-9\-]/g, '');
                if (num === '') return 0;
                return parseInt(num, 10) || 0;
            }

            function formatRupiah(n) {
                n = parseInt(n) || 0;
                return 'Rp ' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function computeAll() {
                var tbody = document.querySelector('.rincian-table tbody');
                if (!tbody) return;
                var rows = tbody.querySelectorAll('tr');
                var sum = 0;
                rows.forEach(function (tr) {
                    // skip the last total row if present (contains inputs in colspan)
                    var totalInput = tr.querySelector('.row-total');
                    if (!totalInput) return;
                    var nominalInput = tr.querySelector('.row-nominal');
                    var qtyInput = tr.querySelector('.row-qty');
                    var perkiraanInput = tr.querySelector('.row-perkiraan');
                    var n = parseNumber(nominalInput ? nominalInput.value : 0);
                    var q = qtyInput ? (parseInt(qtyInput.value) || 0) : 0;
                    var p = parseNumber(perkiraanInput ? perkiraanInput.value : 0);
                    var rowTotal = 0;
                    if (q > 0) rowTotal = n * q;
                    else if (p > 0) rowTotal = p;
                    else rowTotal = n;
                    sum += rowTotal;
                    totalInput.value = rowTotal > 0 ? formatRupiah(rowTotal) : '';
                });
                var bt = document.getElementById('budget_total');
                if (bt) bt.value = sum > 0 ? formatRupiah(sum) : '';
            }

            function attachCalcListeners() {
                document.querySelectorAll('.row-nominal, .row-qty, .row-perkiraan').forEach(function (el) {
                    el.addEventListener('input', function () {
                        computeAll();
                        syncPrintValues();
                    });
                });
            }

            document.addEventListener('DOMContentLoaded', function () { computeAll(); attachCalcListeners(); syncPrintValues(); });
            window.addEventListener('beforeprint', function () { computeAll(); syncPrintValues(); });

            // Auto-fill "Diusulkan Oleh" signature name from main "Nama" field
            document.addEventListener('DOMContentLoaded', function() {
                var namaInput = document.querySelector('input[name="nama"]');
                var signNama1 = document.querySelector('input[name="sign_nama_1"]');
                
                if (namaInput && signNama1) {
                    namaInput.addEventListener('input', function() {
                        signNama1.value = namaInput.value;
                        syncPrintValues();
                    });
                    
                    // Set initial value if nama already has value (from POST)
                    if (namaInput.value) {
                        signNama1.value = namaInput.value;
                    }
                }
            });
        </script>
        <!-- SweetAlert2 for nicer submit notifications -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                <?php if (!empty($saved)): ?>
                    Swal.fire({
                        icon: 'success',
                        title: 'Data tersimpan',
                        text: 'Data perjalanan dinas berhasil disimpan.',
                        confirmButtonText: 'OK'
                    });
                <?php elseif (!empty($error)): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Terjadi kesalahan',
                        text: <?php echo json_encode($error) ?>,
                        confirmButtonText: 'OK'
                    });
                <?php endif; ?>
            });
        </script>
        <script>
            // Client-side validation and confirmation before submit
            document.addEventListener('DOMContentLoaded', function () {
                var form = document.querySelector('form');
                if (!form) return;
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var missing = [];
                    var nama = (form.querySelector('input[name="nama"]')?.value || '').trim();
                    var departemen = (form.querySelector('input[name="departemen"]')?.value || '').trim();
                    if (!nama) missing.push('Nama harus diisi');
                    if (!departemen) missing.push('Departemen harus diisi');

                    // check at least one rincian row has content
                    var rows = document.querySelectorAll('.rincian-table tbody tr');
                    var anyRow = false;
                    rows.forEach(function (tr) {
                        var ket = (tr.querySelector('.row-ket')?.value || '').trim();
                        var nom = parseNumber(tr.querySelector('.row-nominal')?.value || '0');
                        var qty = parseInt(tr.querySelector('.row-qty')?.value || 0) || 0;
                        var pr = parseNumber(tr.querySelector('.row-perkiraan')?.value || '0');
                        if (ket !== '' || nom > 0 || qty > 0 || pr > 0) anyRow = true;
                    });
                    if (!anyRow) missing.push('Isi minimal satu baris rincian');

                    var bt = document.getElementById('budget_total');
                    var btVal = bt ? parseNumber(bt.value || '0') : 0;
                    if (btVal <= 0) missing.push('Budget total harus lebih dari 0');

                    if (missing.length > 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Data belum lengkap',
                            html: '<div style="text-align:left"><ul>' + missing.map(function (m) { return '<li>' + m + '</li>'; }).join('') + '</ul></div>'
                        });
                        return;
                    }

                    Swal.fire({
                        title: 'Konfirmasi simpan',
                        text: 'Pastikan data sudah benar. Lanjutkan menyimpan? ',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Simpan',
                        cancelButtonText: 'Batal'
                    }).then(function (res) {
                        if (res.isConfirmed) {
                            // submit without re-triggering our handler
                            form.removeEventListener('submit', arguments.callee);
                            form.submit();
                        }
                    });
                });
            });
        </script>
    </div>
</body>
</html>
