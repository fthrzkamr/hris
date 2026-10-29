<?php
include("sess_check.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: training_batch_add.php');
    exit;
}

// Get data from form (SAME training details for MULTIPLE employees)
$npp_arr = $_POST['npp'] ?? [];
$judul_training = $_POST['judul_training'] ?? '';
$tujuan_training = $_POST['tujuan_training'] ?? '';
$penyelenggara = $_POST['penyelenggara'] ?? '';
$lokasi_training = $_POST['lokasi_training'] ?? '';
$tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
$tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
$has_budget = isset($_POST['has_budget']) ? $_POST['has_budget'] : 0;
$budget_total = floatval($_POST['budget_total'] ?? 0);
$rincian_items = $_POST['rincian_item'] ?? [];
$rincian_nilai = $_POST['rincian_nilai'] ?? [];

// Validate
if (empty($npp_arr) || count($npp_arr) == 0) {
    $_SESSION['pesan'] = 'Pilih minimal 1 karyawan!';
    $_SESSION['type_pesan'] = 'danger';
    header('Location: training_batch_add.php');
    exit;
}

if (empty($judul_training) || empty($penyelenggara) || empty($lokasi_training) || empty($tanggal_mulai) || empty($tanggal_selesai)) {
    $_SESSION['pesan'] = 'Semua field wajib harus diisi!';
    $_SESSION['type_pesan'] = 'danger';
    header('Location: training_batch_add.php');
    exit;
}

// Get HR info from session
$added_by = $sess_mngname ?? 'Manager HR';
$tanggal_pengajuan = date('Y-m-d H:i:s');
$approved_date = date('Y-m-d H:i:s');

// Escape strings for SQL (still escape for any unprepared usage)
$judul_training = mysqli_real_escape_string($conn, $judul_training);
$tujuan_training = mysqli_real_escape_string($conn, $tujuan_training);
$penyelenggara = mysqli_real_escape_string($conn, $penyelenggara);
$lokasi_training = mysqli_real_escape_string($conn, $lokasi_training);

$success_count = 0;

// Start transaction
mysqli_begin_transaction($conn);

try {
    // prepare insert for pengajuan_training (include generated id_pengajuan, nama_karyawan, id_bagian)
    $sql_insert = "INSERT INTO pengajuan_training (
        id_pengajuan, npp, nama_karyawan, id_bagian, judul_training, tujuan_training, penyelenggara, lokasi_training,
        tanggal_mulai, tanggal_selesai, budget_total, tanggal_pengajuan, status, approved_by, approved_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = mysqli_prepare($conn, $sql_insert);
    if (!$stmt_insert) {
        throw new Exception("Prepare failed (pengajuan): " . mysqli_error($conn));
    }

    // prepare insert for training_rincian
    $sql_rincian = "INSERT INTO training_rincian (id_pengajuan, nama_item, nilai) VALUES (?, ?, ?)";
    $stmt_rincian = mysqli_prepare($conn, $sql_rincian);
    if (!$stmt_rincian) {
        throw new Exception("Prepare failed (rincian): " . mysqli_error($conn));
    }

    foreach ($npp_arr as $npp) {
        $npp_escaped = mysqli_real_escape_string($conn, $npp);

        // ambil nama karyawan dan id_bagian dari tabel employee
        $sql_emp = "SELECT nama_emp, nama_bagian FROM employee WHERE npp = '". $npp_escaped ."' LIMIT 1";
        $res_emp = mysqli_query($conn, $sql_emp);
        $row_emp = mysqli_fetch_assoc($res_emp);
        $nama_karyawan = $row_emp['nama_emp'] ?? '';
        $id_bagian = $row_emp['nama_bagian'] ?? null;

        // escape
        $nama_karyawan = mysqli_real_escape_string($conn, $nama_karyawan);
        $id_bagian_val = is_null($id_bagian) ? '' : (string)$id_bagian;

        // generate unique id_pengajuan (format: TRNYYYYmmddHHMMSS + random)
        $id_pengajuan = 'TRN' . date('YmdHis') . sprintf("%04d", rand(0, 9999));

        // bind and execute pengajuan insert
        $status = 'Completed';
        $bind = mysqli_stmt_bind_param(
            $stmt_insert,
            "ssssssssssdssss",
            $id_pengajuan,
            $npp_escaped,
            $nama_karyawan,
            $id_bagian_val,
            $judul_training,
            $tujuan_training,
            $penyelenggara,
            $lokasi_training,
            $tanggal_mulai,
            $tanggal_selesai,
            $budget_total,
            $tanggal_pengajuan,
            $status,
            $added_by,
            $approved_date
        );

        if (!$bind) {
            throw new Exception("Bind failed (pengajuan): " . mysqli_error($conn));
        }
        if (!mysqli_stmt_execute($stmt_insert)) {
            throw new Exception("Execute failed (pengajuan) for NPP $npp: " . mysqli_stmt_error($stmt_insert));
        }

        // if budget details provided, insert rincian using the same id_pengajuan
        if ($has_budget && !empty($rincian_items) && is_array($rincian_items)) {
            for ($j = 0; $j < count($rincian_items); $j++) {
                $item = trim($rincian_items[$j]);
                if ($item === '') continue;
                $item_name = mysqli_real_escape_string($conn, $item);
                $item_nilai = floatval($rincian_nilai[$j] ?? 0);

                $bind2 = mysqli_stmt_bind_param($stmt_rincian, "ssd", $id_pengajuan, $item_name, $item_nilai);
                if (!$bind2) {
                    throw new Exception("Bind failed (rincian): " . mysqli_error($conn));
                }
                if (!mysqli_stmt_execute($stmt_rincian)) {
                    throw new Exception("Execute failed (rincian) for NPP $npp: " . mysqli_stmt_error($stmt_rincian));
                }
            }
        }

        $success_count++;
        // small sleep to reduce chance of id collision
        usleep(10000);
    }

    mysqli_commit($conn);

    if ($success_count > 0) {
        $_SESSION['pesan'] = "Berhasil menambahkan training untuk $success_count karyawan dengan status Completed!";
        $_SESSION['type_pesan'] = 'success';
    } else {
        $_SESSION['pesan'] = "Tidak ada data yang ditambahkan.";
        $_SESSION['type_pesan'] = 'warning';
    }

} catch (Exception $e) {
    mysqli_rollback($conn);
    // jangan tampilkan atau echo detail error ke browser; berikan pesan umum
    $_SESSION['pesan'] = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi atau hubungi admin.';
    $_SESSION['type_pesan'] = 'danger';
}

header('Location: training_list.php');
exit;
?>
