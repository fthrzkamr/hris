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
    $jam_masuk_raw = isset($_POST['jam_masuk']) ? trim($_POST['jam_masuk']) : '';
    $jam_pulang_raw = isset($_POST['jam_pulang']) ? trim($_POST['jam_pulang']) : '';

    if ($id <= 0) {
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_message'] = 'ID absensi tidak valid.';
        header('Location: insentif_karyawan_list.php');
        exit();
    }

    // Normalize time inputs (expect HH:MM or HH:MM:SS). If empty, will fallback to existing db values later.
    $jam_masuk = null;
    if ($jam_masuk_raw !== '') {
        $jam_masuk = date('H:i:s', strtotime($jam_masuk_raw));
    }

    $jam_pulang = null;
    if ($jam_pulang_raw !== '') {
        $jam_pulang = date('H:i:s', strtotime($jam_pulang_raw));
    }

    // Fetch existing record to get current jam_masuk and jam_pulang if user didn't provide them
    $res = mysqli_query($conn, "SELECT npp, tanggal, jam_masuk, jam_pulang FROM absensi_karyawan WHERE id=" . intval($id));
    if (!$res || mysqli_num_rows($res) == 0) {
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_message'] = 'Data absensi tidak ditemukan.';
        header('Location: insentif_karyawan_list.php');
        exit();
    }

    $row = mysqli_fetch_assoc($res);
    // Use provided values when available, otherwise keep existing
    if ($jam_masuk === null) $jam_masuk = $row['jam_masuk'];
    if ($jam_pulang === null) $jam_pulang = $row['jam_pulang'];

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
    $jam_kerja_normal = '08:30:00';
    $jam_pulang_normal = '16:00:00';

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

    $jam_masuk_sql = $jam_masuk ? "'" . mysqli_real_escape_string($conn, $jam_masuk) . "'" : "NULL";
    $jam_pulang_sql = $jam_pulang ? "'" . mysqli_real_escape_string($conn, $jam_pulang) . "'" : "NULL";
    $durasi_sql = $durasi_kerja ? "'" . mysqli_real_escape_string($conn, $durasi_kerja) . "'" : "NULL";

    $upd = "UPDATE absensi_karyawan SET jam_masuk=$jam_masuk_sql, jam_pulang=$jam_pulang_sql, durasi_kerja=$durasi_sql, status_absensi='" . mysqli_real_escape_string($conn, $status_absensi) . "', updated_at=CURRENT_TIMESTAMP WHERE id=" . intval($id);
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
                                    <label>Jam Masuk (ubah jika salah)</label>
                                    <input type="time" name="jam_masuk" class="form-control" value="<?php echo $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : ''; ?>">
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
