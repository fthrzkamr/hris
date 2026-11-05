<?php
include('sess_check.php');
if (is_file(__DIR__ . '/dist/config/koneksi.php')) {
    include_once __DIR__ . '/dist/config/koneksi.php';
} elseif (is_file(__DIR__ . '/../dist/config/koneksi.php')) {
    include_once __DIR__ . '/../dist/config/koneksi.php';
}
if (is_file(__DIR__ . '/dist/config/library.php')) {
    include_once __DIR__ . '/dist/config/library.php';
} elseif (is_file(__DIR__ . '/../dist/config/library.php')) {
    include_once __DIR__ . '/../dist/config/library.php';
}

// Ensure request method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form_perjalanan_dinas.php');
    exit;
}

// Get NPP from session
$npp = $sess_mngid ?? ($sess_kryid ?? ($sess_spvid ?? ($sess_admid ?? ($sess_dirid ?? ($_SESSION['npp'] ?? null)))));
if (!$npp) {
    header('Location: form_perjalanan_dinas.php?error=' . urlencode('Session tidak valid. Silakan login kembali.'));
    exit;
}

function input($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : null;
}

function clean_digits($val) {
    if ($val === null) return 0;
    $clean = preg_replace('/[^0-9]/', '', (string)$val);
    return $clean === '' ? 0 : (int)$clean;
}

$nama = mysqli_real_escape_string($conn, input('nama'));
$departemen = mysqli_real_escape_string($conn, input('departemen'));
$tanggal_perjalanan = input('tanggal_perjalanan');
$jumlah_hari = (int) input('jumlah_hari');
$kota_asal = mysqli_real_escape_string($conn, input('kota_asal'));
$kota_tujuan = mysqli_real_escape_string($conn, input('kota_tujuan'));
$tujuan = mysqli_real_escape_string($conn, input('tujuan'));

// Basic validation
if (empty($nama) || empty($departemen) || $jumlah_hari < 1 || empty($kota_tujuan) || empty($tujuan)) {
    header('Location: form_perjalanan_dinas.php?error=' . urlencode('Lengkapi field yang wajib diisi.'));
    exit;
}

// Data rekan perjalanan dinas (opsional)
$rekan_npps = isset($_POST['rekan_npp']) && is_array($_POST['rekan_npp']) ? $_POST['rekan_npp'] : [];
$rekan_namas = isset($_POST['rekan_nama']) && is_array($_POST['rekan_nama']) ? $_POST['rekan_nama'] : [];
$rekan_depts = isset($_POST['rekan_dept']) && is_array($_POST['rekan_dept']) ? $_POST['rekan_dept'] : [];

$valid_rekans = [];
foreach ($rekan_npps as $idx => $rnpp) {
    $rnpp = trim($rnpp);
    if ($rnpp !== '' && $rnpp !== $npp && !isset($valid_rekans[$rnpp])) {
        // Ambil nama & departemen dari POST atau query jika kosong
        $r_nama = trim($rekan_namas[$idx] ?? '');
        $r_dept = trim($rekan_depts[$idx] ?? '');
        if ($r_nama === '') {
            $q_emp = mysqli_query($conn, "SELECT e.nama_emp, COALESCE(b.nama_bagian, e.nama_bagian) AS nama_bagian FROM employee e LEFT JOIN bagian b ON b.id_bagian = e.nama_bagian OR b.nama_bagian = e.nama_bagian WHERE e.npp = '" . mysqli_real_escape_string($conn, $rnpp) . "' LIMIT 1");
            if ($q_emp && $row_e = mysqli_fetch_assoc($q_emp)) {
                $r_nama = $row_e['nama_emp'];
                $r_dept = $row_e['nama_bagian'];
            }
        }
        $valid_rekans[$rnpp] = [
            'npp' => $rnpp,
            'nama' => $r_nama,
            'dept' => $r_dept
        ];
    }
}

// Generate document number
$no_dokumen = 'PJ' . strtoupper(substr(md5(uniqid()), 0, 8));

// Ensure tables exist
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS perjalanan_pengajuan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_perjalanan INT NOT NULL,
    npp VARCHAR(50),
    pengaju VARCHAR(100),
    tanggal_pengajuan DATETIME,
    status VARCHAR(50),
    catatan TEXT,
    approver VARCHAR(100),
    tanggal_approval DATETIME,
    FOREIGN KEY (id_perjalanan) REFERENCES perjalanan_dinas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS perjalanan_karyawan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    perjalanan_id INT NOT NULL,
    npp VARCHAR(50) NOT NULL,
    nama VARCHAR(150) NOT NULL,
    departemen VARCHAR(150) NULL,
    is_pemohon TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_perjalanan_id (perjalanan_id),
    KEY idx_npp (npp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Memulai transaksi database
mysqli_begin_transaction($conn);
try {
    $now = date('Y-m-d H:i:s');
    $tanggal_perjalanan_sql = !empty($tanggal_perjalanan) ? "'" . mysqli_real_escape_string($conn, $tanggal_perjalanan) . "'" : 'NULL';

    // 1. Insert header perjalanan_dinas (budget_total awal 0, di-update setelah rincian dihitung)
    $sql = "INSERT INTO perjalanan_dinas (no_dokumen, revisi, tanggal_dokumen, nama, npp, departemen, tanggal_perjalanan, jumlah_hari, kota_asal, kota_tujuan, tujuan, budget_total, created_at) 
            VALUES ('" . mysqli_real_escape_string($conn, $no_dokumen) . "', 0, '" . $now . "', '" . $nama . "', '" . mysqli_real_escape_string($conn, $npp) . "', '" . $departemen . "', " . $tanggal_perjalanan_sql . ", '" . (int)$jumlah_hari . "', '" . $kota_asal . "', '" . $kota_tujuan . "', '" . $tujuan . "', '0', '" . $now . "')";

    if (!mysqli_query($conn, $sql)) {
        throw new Exception('Gagal menyimpan data perjalanan: ' . mysqli_error($conn));
    }

    $perjalanan_id = mysqli_insert_id($conn);

    // 2. Insert ke perjalanan_karyawan
    // A. Pengaju Utama (is_pemohon = 1)
    $sql_k_utama = "INSERT INTO perjalanan_karyawan (perjalanan_id, npp, nama, departemen, is_pemohon) 
                    VALUES (" . (int)$perjalanan_id . ", '" . mysqli_real_escape_string($conn, $npp) . "', '" . $nama . "', '" . $departemen . "', 1)";
    if (!mysqli_query($conn, $sql_k_utama)) {
        throw new Exception('Gagal menyimpan peserta utama: ' . mysqli_error($conn));
    }

    // B. Rekan-rekan yang diikutkan (is_pemohon = 0)
    foreach ($valid_rekans as $vr) {
        $sql_k_rekan = "INSERT INTO perjalanan_karyawan (perjalanan_id, npp, nama, departemen, is_pemohon) 
                        VALUES (" . (int)$perjalanan_id . ", '" . mysqli_real_escape_string($conn, $vr['npp']) . "', '" . mysqli_real_escape_string($conn, $vr['nama']) . "', '" . mysqli_real_escape_string($conn, $vr['dept']) . "', 0)";
        if (!mysqli_query($conn, $sql_k_rekan)) {
            throw new Exception('Gagal menyimpan rekan peserta: ' . mysqli_error($conn));
        }
    }

    // 3. Insert ke perjalanan_rincian (Per Masing-masing Karyawan)
    $budget_total_val = 0;
    $nomor_item = 1;

    // Biaya Personal per masing-masing karyawan (Pengaju Utama & Rekan Peserta)
    $personal_kets = isset($_POST['personal_ket']) && is_array($_POST['personal_ket']) ? $_POST['personal_ket'] : [];
    $personal_qtys = isset($_POST['personal_qty']) && is_array($_POST['personal_qty']) ? $_POST['personal_qty'] : [];
    $personal_perks = isset($_POST['personal_perkiraan']) && is_array($_POST['personal_perkiraan']) ? $_POST['personal_perkiraan'] : [];

    // Daftar semua peserta yang dicek biaya personalnya (Pengaju Utama + Rekan)
    // Hindari array_merge yang mereset key numerik (NPP) di PHP
    $semua_peserta = [];
    $semua_peserta[] = ['npp' => (string)$npp, 'nama' => $nama, 'dept' => $departemen];
    foreach ($valid_rekans as $vr) {
        $semua_peserta[] = $vr;
    }

    foreach ($semua_peserta as $p_data) {
        $curr_npp = (string)$p_data['npp'];
        if (isset($personal_kets[$curr_npp]) && is_array($personal_kets[$curr_npp])) {
            $pk_arr = $personal_kets[$curr_npp];
            $pq_arr = $personal_qtys[$curr_npp] ?? [];
            $pp_arr = $personal_perks[$curr_npp] ?? [];

            for ($j = 0; $j < count($pk_arr); $j++) {
                $p_ket = trim($pk_arr[$j] ?? '');
                if ($p_ket === '') continue;

                $p_qty = isset($pq_arr[$j]) ? max(1, (int)$pq_arr[$j]) : 1;
                $p_perk = clean_digits($pp_arr[$j] ?? 0);
                $p_total = $p_qty * $p_perk;
                $budget_total_val += $p_total;

                $sql_rp = "INSERT INTO perjalanan_rincian (perjalanan_id, nomor, ket, nominal, qty, perkiraan, total, keterangan, kategori_biaya, npp_karyawan, nama_karyawan) 
                           VALUES (" . (int)$perjalanan_id . ", " . (int)$nomor_item . ", '" . mysqli_real_escape_string($conn, $p_ket) . "', '" . (int)$p_perk . "', " . (int)$p_qty . ", '" . (int)$p_perk . "', '" . (int)$p_total . "', '', 'personal', '" . mysqli_real_escape_string($conn, $curr_npp) . "', '" . mysqli_real_escape_string($conn, $p_data['nama']) . "')";
                if (!mysqli_query($conn, $sql_rp)) {
                    throw new Exception('Gagal menyimpan rincian biaya: ' . mysqli_error($conn));
                }
                $nomor_item++;
            }
        }
    }

    // Dukungan backward-compatibility jika ada form lama yang mengirimkan ket[] standar
    if ($nomor_item === 1 && isset($_POST['ket']) && is_array($_POST['ket'])) {
        $legacy_kets = $_POST['ket'];
        $legacy_qtys = $_POST['qty'] ?? [];
        $legacy_perks = $_POST['perkiraan'] ?? [];

        for ($k = 0; $k < count($legacy_kets); $k++) {
            $l_ket = trim($legacy_kets[$k] ?? '');
            if ($l_ket === '') continue;

            $l_qty = isset($legacy_qtys[$k]) ? max(1, (int)$legacy_qtys[$k]) : 1;
            $l_perk = clean_digits($legacy_perks[$k] ?? 0);
            $l_total = $l_qty * $l_perk;
            $budget_total_val += $l_total;

            $sql_legacy = "INSERT INTO perjalanan_rincian (perjalanan_id, nomor, ket, nominal, qty, perkiraan, total, keterangan, kategori_biaya, npp_karyawan, nama_karyawan) 
                           VALUES (" . (int)$perjalanan_id . ", " . (int)$nomor_item . ", '" . mysqli_real_escape_string($conn, $l_ket) . "', '0', " . (int)$l_qty . ", '" . (int)$l_perk . "', '" . (int)$l_total . "', '', 'personal', '" . mysqli_real_escape_string($conn, $npp) . "', '" . $nama . "')";
            if (!mysqli_query($conn, $sql_legacy)) {
                throw new Exception('Gagal menyimpan rincian: ' . mysqli_error($conn));
            }
            $nomor_item++;
        }
    }

    // Otomatis generate uang harian / biaya luar kota untuk seluruh peserta
    if (function_exists('auto_generate_biaya_luar_kota')) {
        auto_generate_biaya_luar_kota($conn, $perjalanan_id);
    }

    // 4. Update budget_total di tabel perjalanan_dinas sesuai total kalkulasi
    mysqli_query($conn, "UPDATE perjalanan_dinas SET budget_total = (
        SELECT COALESCE(SUM(CAST(REPLACE(total,',','') AS DECIMAL(20,2))), 0) 
        FROM perjalanan_rincian WHERE perjalanan_id = " . (int)$perjalanan_id . "
    ) WHERE id = " . (int)$perjalanan_id);

    // 5. Insert pengajuan awal dengan status DIAJUKAN
    $user_submitter = $sess_mngname ?? ($sess_kryname ?? ($sess_spvname ?? ($sess_admname ?? ($sess_dirname ?? $nama))));
    $status = 'DIAJUKAN';
    $sql_pengajuan = "INSERT INTO perjalanan_pengajuan (id_perjalanan, npp, pengaju, tanggal_pengajuan, status) 
                      VALUES (" . (int)$perjalanan_id . ", '" . mysqli_real_escape_string($conn, $npp) . "', '" . mysqli_real_escape_string($conn, $user_submitter) . "', '" . $now . "', '" . $status . "')";
    if (!mysqli_query($conn, $sql_pengajuan)) {
        throw new Exception('Gagal menyimpan status pengajuan: ' . mysqli_error($conn));
    }

    mysqli_commit($conn);
    header('Location: perjalanan_dinas_list.php?success=' . urlencode('Pengajuan perjalanan dinas berhasil diajukan dengan nomor dokumen: ' . $no_dokumen));
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log($e->getMessage());
    header('Location: form_perjalanan_dinas.php?error=' . urlencode('Terjadi kesalahan saat menyimpan: ' . $e->getMessage()));
    exit;
}
?>
