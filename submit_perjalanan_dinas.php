<?php
include('sess_check.php');
include('dist/config/koneksi.php');

// ensure request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form_perjalanan_dinas.php');
    exit;
}

// Get NPP from session
$npp = isset($sess_mngid) ? $sess_mngid : (isset($sess_admid) ? $sess_admid : null);
if (!$npp) {
    header('Location: form_perjalanan_dinas.php?error=' . urlencode('Session tidak valid. Silakan login kembali.'));
    exit;
}

// simple helper
function input($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : null;
}

$nama = mysqli_real_escape_string($conn, input('nama'));
$departemen = mysqli_real_escape_string($conn, input('departemen'));
$tanggal_perjalanan = input('tanggal_perjalanan'); // can be empty
$jumlah_hari = (int) input('jumlah_hari');
$kota_asal = mysqli_real_escape_string($conn, input('kota_asal'));
$kota_tujuan = mysqli_real_escape_string($conn, input('kota_tujuan'));
$tujuan = mysqli_real_escape_string($conn, input('tujuan'));

// rincian arrays
$kets = isset($_POST['ket']) ? $_POST['ket'] : [];
$qtys = isset($_POST['qty']) ? $_POST['qty'] : [];
$perks = isset($_POST['perkiraan']) ? $_POST['perkiraan'] : [];

// basic validation
if (empty($nama) || empty($departemen) || $jumlah_hari < 1 || empty($kota_tujuan) || empty($tujuan)) {
    header('Location: form_perjalanan_dinas.php?error=' . urlencode('Lengkapi field yang wajib.'));
    exit;
}

// sanitize numeric string: remove any non-digit characters
function clean_digits($val) {
    if ($val === null) return 0;
    // remove everything except digits
    $clean = preg_replace('/[^0-9]/', '', (string)$val);
    return $clean === '' ? 0 : (int)$clean;
}

// compute budget_total from perkiraan * qty, ensuring stored values contain only digits
$budget_total_val = 0;
for ($i = 0; $i < count($kets); $i++) {
    $q = isset($qtys[$i]) ? (int)$qtys[$i] : 0;
    $p_raw = isset($perks[$i]) ? $perks[$i] : 0;
    $p = clean_digits($p_raw); // perkiraan sanitized to digits only
    $line_total = $q * $p;
    $budget_total_val += $line_total;
    // overwrite arrays with sanitized numeric values for insertion later
    $qtys[$i] = $q;
    $perks[$i] = $p;
}

// budget_total as plain integer (digits only)
$budget_total_str = $budget_total_val;

// generate document number
$no_dokumen = 'PJ' . strtoupper(substr(md5(uniqid()), 0, 8));

// ensure pengajuan table exists
$createPengajuan = "CREATE TABLE IF NOT EXISTS perjalanan_pengajuan (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $createPengajuan);

// insert into perjalanan_dinas and perjalanan_rincian inside transaction
mysqli_begin_transaction($conn);
try {
    $now = date('Y-m-d H:i:s');
    $tanggal_perjalanan_sql = $tanggal_perjalanan ? "'" . mysqli_real_escape_string($conn, $tanggal_perjalanan) . "'" : 'NULL';

    $sql = "INSERT INTO perjalanan_dinas (no_dokumen, revisi, tanggal_dokumen, nama, npp, departemen, tanggal_perjalanan, jumlah_hari, kota_asal, kota_tujuan, tujuan, budget_total, created_at) VALUES ('" .
           mysqli_real_escape_string($conn, $no_dokumen) . "', 0, '" . $now . "', '" . $nama . "', '" . mysqli_real_escape_string($conn, $npp) . "', '" . $departemen . "', " . $tanggal_perjalanan_sql . ", '" .
           (int)$jumlah_hari . "', '" . $kota_asal . "', '" . $kota_tujuan . "', '" . $tujuan . "', '" . $budget_total_str . "', '" . $now . "')";

    if (!mysqli_query($conn, $sql)) {
        throw new Exception('Gagal menyimpan perjalanan: ' . mysqli_error($conn));
    }

    $perjalanan_id = mysqli_insert_id($conn);

    // insert rincian
    for ($i = 0; $i < count($kets); $i++) {
        $ket = mysqli_real_escape_string($conn, $kets[$i]);
        $qty = isset($qtys[$i]) ? (int)$qtys[$i] : 0;
        $perk = isset($perks[$i]) ? (float)$perks[$i] : 0;
        $total = $qty * $perk;

        // nominal (final) left 0 — HR/Manager HR will set nominal later
        $nominal = 0;

        $sql2 = "INSERT INTO perjalanan_rincian (perjalanan_id, nomor, ket, nominal, qty, perkiraan, total, keterangan) VALUES (" .
                (int)$perjalanan_id . ", " . ($i+1) . ", '" . $ket . "', '" . $nominal . "', '" . $qty . "', '" . $perk . "', '" . $total . "', '')";

        if (!mysqli_query($conn, $sql2)) {
            throw new Exception('Gagal menyimpan rincian: ' . mysqli_error($conn));
        }
    }

    // Auto-submit with status DIAJUKAN
    $user = isset($sess_mngname) ? $sess_mngname : $nama;
    $status = 'DIAJUKAN';
    $sql3 = "INSERT INTO perjalanan_pengajuan (id_perjalanan, npp, pengaju, tanggal_pengajuan, status) VALUES (" .
            (int)$perjalanan_id . ", '" . mysqli_real_escape_string($conn, $npp) . "', '" . 
            mysqli_real_escape_string($conn, $user) . "', '" . $now . "', '" . $status . "')";
    
    if (!mysqli_query($conn, $sql3)) {
        throw new Exception('Gagal menyimpan pengajuan: ' . mysqli_error($conn));
    }

    mysqli_commit($conn);
    header('Location: perjalanan_dinas_saya.php?success=' . urlencode('Pengajuan berhasil diajukan dengan nomor dokumen: ' . $no_dokumen));
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log($e->getMessage());
    header('Location: form_perjalanan_dinas.php?error=' . urlencode('Terjadi kesalahan saat menyimpan: ' . $e->getMessage()));
    exit;
}

