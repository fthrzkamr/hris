<?php
// session check
include("sess_check.php");
$pagedesc = "Update Insentif Kurir";
$menuparent = "insentif";
include("layout_top.php");

include("../dist/config/koneksi.php");

// Helper: check if column exists
function column_exists($conn, $table, $column) {
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `" . $table . "` LIKE '" . mysqli_real_escape_string($conn, $column) . "'");
    return ($res && mysqli_num_rows($res) > 0);
}

// Process POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $total_titik = isset($_POST['total_titik']) ? intval($_POST['total_titik']) : 0;

    if ($id <= 0) {
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_message'] = 'ID tidak valid.';
        header('Location: insentif_kurir_list.php'); exit();
    }

    // Fetch current record including target_titik and npp
    $res = mysqli_query($conn, "SELECT * FROM insentif_kurir WHERE id=" . intval($id));
    if (!$res || mysqli_num_rows($res) == 0) {
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_message'] = 'Data tidak ditemukan.';
        header('Location: insentif_kurir_list.php'); exit();
    }
    $row = mysqli_fetch_assoc($res);
    $target = intval($row['target_titik']);

    // Recalculate percentage and status
    $persentase = ($target > 0) ? (($total_titik / $target) * 100) : 0;
    $status = ($total_titik >= $target) ? 'Tercapai' : 'Tidak Tercapai';

    // Determine rate_per_titik: try employee.rate_per_titik else default 1000
    $rate = 1000;
    $emp = mysqli_query($conn, "SELECT rate_per_titik FROM employee WHERE npp='" . mysqli_real_escape_string($conn, $row['npp']) . "'");
    if ($emp && mysqli_num_rows($emp) > 0) {
        $erow = mysqli_fetch_assoc($emp);
        if (!empty($erow['rate_per_titik'])) $rate = floatval($erow['rate_per_titik']);
    }

    // Calculate total_uang if column exists (no denda applied)
    $total_uang = null;
    if (column_exists($conn, 'insentif_kurir', 'total_uang')) {
        $total_uang = ($total_titik * $rate);
    }

    // Build UPDATE statement dynamically depending on available columns
    $updates = [];
    $updates[] = "total_titik=" . intval($total_titik);
    $updates[] = "persentase_pencapaian=" . floatval($persentase);
    $updates[] = "status_target='" . mysqli_real_escape_string($conn, $status) . "'";
    if ($total_uang !== null) {
        $updates[] = "total_uang=" . floatval($total_uang);
    }
    $updates[] = "updated_at=CURRENT_TIMESTAMP";

    $sql = "UPDATE insentif_kurir SET " . implode(', ', $updates) . " WHERE id=" . intval($id);
    if (mysqli_query($conn, $sql)) {
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'Data insentif berhasil diperbarui.';
    } else {
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_message'] = 'Gagal memperbarui: ' . mysqli_error($conn);
    }

    header('Location: insentif_kurir_list.php'); exit();
}

// GET form
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { echo '<script>alert("ID tidak valid"); window.location="insentif_kurir_list.php";</script>'; exit(); }

$res = mysqli_query($conn, "SELECT ik.*, e.nama_emp FROM insentif_kurir ik LEFT JOIN employee e ON ik.npp = e.npp WHERE ik.id=" . intval($id));
if (!$res || mysqli_num_rows($res) == 0) { echo '<script>alert("Data tidak ditemukan"); window.location="insentif_kurir_list.php";</script>'; exit(); }
$row = mysqli_fetch_assoc($res);

?>
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Edit Insentif Kurir</h1>
                </div>
            </div>

            <?php include("layout_alert.php"); ?>

            <div class="row">
                <div class="col-lg-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">Form Edit Insentif</div>
                        <div class="panel-body">
                            <form method="POST" action="insentif_kurir_update.php">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                <div class="form-group">
                                    <label>NPP</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['npp']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Nama</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['nama_emp']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Periode</label>
                                    <input type="date" class="form-control" value="<?php echo htmlspecialchars($row['periode']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Total Titik</label>
                                    <input type="number" name="total_titik" class="form-control" value="<?php echo htmlspecialchars($row['total_titik']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Target Titik</label>
                                    <input type="number" class="form-control" value="<?php echo htmlspecialchars($row['target_titik']); ?>" readonly>
                                </div>
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                <a href="insentif_kurir_list.php" class="btn btn-default">Batal</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php include("layout_bottom.php"); ?>

<?php
// end file
?>
<?php
// session check
include("sess_check.php");
$pagedesc = "Update Absensi Karyawan";
$menuparent = "insentif";
include("layout_top.php");

include(__DIR__ . "/../dist/config/koneksi.php");

// If form submitted (POST) -> process update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $jam_pulang_raw = isset($_POST['jam_pulang']) ? trim($_POST['jam_pulang']) : '';

    if ($id <= 0) {
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_message'] = 'ID absensi tidak valid.';
        header('Location: insentif_karyawan_list.php');
        exit();
    }

    // Normalize time input (expect HH:MM or HH:MM:SS)
    $jam_pulang = null;
    if ($jam_pulang_raw !== '') {
        $t = date('H:i:s', strtotime($jam_pulang_raw));
        $jam_pulang = $t;
    }

    // Fetch existing record to get jam_masuk and tanggal
    $res = mysqli_query($conn, "SELECT npp, tanggal, jam_masuk FROM absensi_karyawan WHERE id=" . intval($id));
    if (!$res || mysqli_num_rows($res) == 0) {
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_message'] = 'Data absensi tidak ditemukan.';
        header('Location: insentif_karyawan_list.php');
        exit();
    }

    $row = mysqli_fetch_assoc($res);
    $jam_masuk = $row['jam_masuk'];

    // Recalculate durasi and status
    $durasi_kerja = null;
    if ($jam_masuk && $jam_pulang) {
        $masuk_ts = strtotime($jam_masuk);
        $pulang_ts = strtotime($jam_pulang);
        $diff = $pulang_ts - $masuk_ts;
        if ($diff > 0) {
            $h = floor($diff / 3600);
            $m = floor(($diff % 3600) / 60);
            $durasi_kerja = sprintf('%02d:%02d:00', $h, $m);
        }
    }

    $status_absensi = 'Hadir';
    $jam_kerja_normal = '08:00:00';
    $jam_pulang_normal = '17:00:00';

    if (empty($jam_masuk)) {
        $status_absensi = 'Tidak Hadir';
    } else {
        if ($jam_masuk > $jam_kerja_normal) {
            $status_absensi = 'Terlambat';
        }
        if ($jam_pulang && $jam_pulang < $jam_pulang_normal) {
            $status_absensi = 'Pulang Awal';
        }
    }

    $jam_pulang_sql = $jam_pulang ? "'" . mysqli_real_escape_string($conn, $jam_pulang) . "'" : "NULL";
    $durasi_sql = $durasi_kerja ? "'" . mysqli_real_escape_string($conn, $durasi_kerja) . "'" : "NULL";

    $upd = "UPDATE absensi_karyawan SET jam_pulang=$jam_pulang_sql, durasi_kerja=$durasi_sql, status_absensi='" . mysqli_real_escape_string($conn, $status_absensi) . "', updated_at=CURRENT_TIMESTAMP WHERE id=" . intval($id);
    if (mysqli_query($conn, $upd)) {
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'Data absensi berhasil diperbarui.';
    } else {
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_message'] = 'Gagal memperbarui data: ' . mysqli_error($conn);
    }

    header('Location: insentif_karyawan_list.php');
    exit();
}

// If GET id provided -> show form
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo '<script>alert("ID tidak valid"); window.location="insentif_karyawan_list.php";</script>';
    exit();
}

$res = mysqli_query($conn, "SELECT ak.*, e.nama_emp FROM absensi_karyawan ak LEFT JOIN employee e ON ak.npp = e.npp WHERE ak.id=" . intval($id));
if (!$res || mysqli_num_rows($res) == 0) {
    echo '<script>alert("Data tidak ditemukan"); window.location="insentif_karyawan_list.php";</script>';
    exit();
}

$row = mysqli_fetch_assoc($res);

?>
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Edit Absensi Karyawan</h1>
                </div>
            </div>

            <?php include("layout_alert.php"); ?>

            <div class="row">
                <div class="col-lg-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">Form Edit Absensi</div>
                        <div class="panel-body">
                            <form method="POST" action="insentif_karyawan_update.php">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                <div class="form-group">
                                    <label>NPP</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['npp']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Nama</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['nama_emp']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal</label>
                                    <input type="date" class="form-control" value="<?php echo htmlspecialchars($row['tanggal']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Jam Masuk</label>
                                    <input type="time" class="form-control" value="<?php echo $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : ''; ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Jam Pulang (ubah jika salah)</label>
                                    <input type="time" name="jam_pulang" class="form-control" value="<?php echo $row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : ''; ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                <a href="insentif_karyawan_list.php" class="btn btn-default">Batal</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php include("layout_bottom.php"); ?>

<?php
// end of file
?>
