<?php
// Session check - Only Manager and Leader can access
session_start();
$chk_sess = $_SESSION['financear'];
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

$pagedesc = "Form Permintaan Karyawan Baru";
include 'layout_top.php';

function generate_doc_no()
{
    try {
        $bytes = random_bytes(4);
        return 'PMT' . strtoupper(bin2hex($bytes));
    } catch (Exception $e) {
        return 'PMT' . strtoupper(uniqid());
    }
}
$doc_no = generate_doc_no();
$revision = '0';
$doc_date_display = date('d-m-Y');
$doc_date_db = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ensure tables exist
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
            created_by VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $createSql);

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
    $jumlah_dibutuhkan = (isset($_POST['jumlah_dibutuhkan']) && $_POST['jumlah_dibutuhkan'] !== '') ? (int) $_POST['jumlah_dibutuhkan'] : 0;
    
    $untuk_raw = $_POST['untuk'] ?? '';
    $untuk_lain = $_POST['untuk_lain'] ?? '';
    $untuk = ($untuk_raw === 'Lain-lain') ? trim($untuk_lain) : $untuk_raw;
    
    $jumlah_sekarang = (isset($_POST['jumlah_sekarang']) && $_POST['jumlah_sekarang'] !== '') ? (int) $_POST['jumlah_sekarang'] : 0;
    $alasan = $_POST['alasan'] ?? '';

    $duties = [];
    for ($i = 1; $i <= 10; $i++) {
        if (!empty($_POST['duty' . $i])) $duties[] = trim($_POST['duty' . $i]);
    }
    $job_duties = implode("\n", $duties);

    $gender = $_POST['gender'] ?? '';
    $age = $_POST['age'] ?? '';
    $education_raw = $_POST['education'] ?? '';
    $education_lain = $_POST['education_lain'] ?? '';
    $education = ($education_raw === 'Lain-lain') ? trim($education_lain) : $education_raw;
    
    $jurusan = $_POST['jurusan'] ?? '';
    $experiences = $_POST['experiences'] ?? '';
    $height = $_POST['height'] ?? '';
    $weight = $_POST['weight'] ?? '';
    $range_salary = $_POST['range_salary'] ?? '';

    $skillsArr = [];
    for ($s = 1; $s <= 3; $s++) {
        if (!empty($_POST['skill' . $s])) $skillsArr[] = $_POST['skill' . $s];
    }
    $skills = implode("; ", $skillsArr);

    $other = $_POST['other'] ?? '';

    $up = function ($s) {
        return ($s === null) ? null : mb_strtoupper((string)$s, 'UTF-8');
    };

    $jabatan = $up($jabatan);
    $unit_kerja = $up($unit_kerja);
    $untuk = $up($untuk);
    $alasan = $up($alasan);
    $job_duties = $up($job_duties);
    $gender = strtoupper((string)$gender);
    $education = $up($education);
    $jurusan = $up($jurusan);
    $skills = $up($skills);
    $other = $up($other);

    $stmt = mysqli_prepare($conn, "INSERT INTO permintaan_karyawan
            (no_dokumen,revisi,tanggal_dokumen,jabatan,unit_kerja,tgl_mulai,jumlah_dibutuhkan,untuk,jumlah_sekarang,alasan,gender,usia,pendidikan,jurusan,pengalaman,tinggi,berat,rentang_gaji,lain_lain,created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $types = 'ssssssisisi' . str_repeat('s', 9);
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

        // ensure pengajuan table exists
        $createPengajuan = "CREATE TABLE IF NOT EXISTS permintaan_pengajuan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_permintaan INT NOT NULL,
            npp VARCHAR(50),
            pengaju VARCHAR(100),
            tanggal_pengajuan DATETIME,
            status VARCHAR(50),
            catatan TEXT,
            FOREIGN KEY (id_permintaan) REFERENCES permintaan_karyawan(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        mysqli_query($conn, $createPengajuan);

        // Auto-submit pengajuan
        $user_pengaju = isset($sess_mngname) ? $sess_mngname : (isset($sess_admname) ? $sess_admname : 'SYSTEM');
        $npp_pengaju = isset($sess_mngid) ? $sess_mngid : (isset($sess_admid) ? $sess_admid : null);
        $status_pengajuan = 'DIAJUKAN';
        $stmt_pengajuan = mysqli_prepare($conn, "INSERT INTO permintaan_pengajuan (id_permintaan, npp, pengaju, tanggal_pengajuan, status) VALUES (?,?,?,NOW(),?)");
        mysqli_stmt_bind_param($stmt_pengajuan, 'isss', $saved_id, $npp_pengaju, $user_pengaju, $status_pengajuan);
        mysqli_stmt_execute($stmt_pengajuan);

        if (!empty($duties)) {
            $dstmt = mysqli_prepare($conn, "INSERT INTO job_duties_karyawan (permintaan_id, nomor_urut, tugas) VALUES (?,?,?)");
            foreach ($duties as $idx => $t) {
                $t_up = $up($t);
                $num = $idx + 1;
                mysqli_stmt_bind_param($dstmt, 'iis', $saved_id, $num, $t_up);
                mysqli_stmt_execute($dstmt);
            }
        }

        if (!empty($skillsArr)) {
            $sstmt = mysqli_prepare($conn, "INSERT INTO skills_karyawan (permintaan_id, nomor, skill) VALUES (?,?,?)");
            foreach ($skillsArr as $idx => $sk) {
                $sk_up = $up($sk);
                $num = $idx + 1;
                mysqli_stmt_bind_param($sstmt, 'iis', $saved_id, $num, $sk_up);
                mysqli_stmt_execute($sstmt);
            }
        }
        echo "<script>
            $(document).ready(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Data permintaan karyawan telah disimpan.',
                }).then(function() {
                    window.location.href = 'permintaan_karyawan_list.php';
                });
            });
        </script>";
    } else {
        $error = mysqli_error($conn);
    }
}
?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Form Permintaan Karyawan Baru</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-edit fa-fw"></i> Detail Permintaan
                    <div class="pull-right">
                        <span class="label label-info">No. Dokumen: <?php echo $doc_no; ?></span>
                        <span class="label label-warning">Revisi: <?php echo $revision; ?></span>
                    </div>
                </div>
                <div class="panel-body">
                    <form role="form" method="post" id="formPermintaan">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Jabatan <span class="text-danger">*</span></label>
                                    <input class="form-control" name="jabatan" placeholder="Contoh: Staff Administrasi" required>
                                </div>
                                <div class="form-group">
                                    <label>Unit Kerja <span class="text-danger">*</span></label>
                                    <input class="form-control" name="unit_kerja" placeholder="Contoh: IT / HR / Finance" required>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Mulai Bekerja <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="tgl_mulai" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Jumlah Dibutuhkan <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="jumlah_dibutuhkan" placeholder="0" required>
                                </div>
                                <div class="form-group">
                                    <label>Untuk <span class="text-danger">*</span></label>
                                    <select class="form-control" name="untuk" id="untuk_select" required>
                                        <option value="">-- Pilih --</option>
                                        <option value="Penambahan">Penambahan</option>
                                        <option value="Penggantian">Penggantian</option>
                                        <option value="Lain-lain">Lain-lain</option>
                                    </select>
                                    <textarea class="form-control mt-2" name="untuk_lain" id="untuk_lain" style="display:none; margin-top:10px;" placeholder="Jelaskan alasan lainnya..."></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Jml. Karyawan Sekarang</label>
                                            <input type="number" class="form-control" name="jumlah_sekarang" placeholder="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Alasan <span class="text-danger">*</span></label>
                                            <input class="form-control" name="alasan" placeholder="Alasan permintaan" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="panel panel-info">
                                    <div class="panel-heading">
                                        <i class="fa fa-list fa-fw"></i> Job Duties (Tugas-tugas Pekerjaan)
                                    </div>
                                    <div class="panel-body">
                                        <div class="row">
                                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <div class="input-group">
                                                            <span class="input-group-addon"><?php echo $i; ?></span>
                                                            <input type="text" class="form-control" name="duty<?php echo $i; ?>" placeholder="Tugas ke-<?php echo $i; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="panel panel-success">
                                    <div class="panel-heading">
                                        <i class="fa fa-user fa-fw"></i> Requirements (Persyaratan)
                                    </div>
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Jenis Kelamin <span class="text-danger">*</span></label>
                                                    <div>
                                                        <label class="radio-inline">
                                                            <input type="radio" name="gender" value="L" required> Laki-laki
                                                        </label>
                                                        <label class="radio-inline">
                                                            <input type="radio" name="gender" value="P"> Perempuan
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Usia <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="age" placeholder="Contoh: 22 - 30 Tahun" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Pendidikan <span class="text-danger">*</span></label>
                                                    <select class="form-control" name="education" id="education_select" required>
                                                        <option value="">-- Pilih --</option>
                                                        <option value="D3">D3</option>
                                                        <option value="S1">S1</option>
                                                        <option value="S2">S2</option>
                                                        <option value="Lain-lain">Lain-lain</option>
                                                    </select>
                                                    <input type="text" class="form-control" name="education_lain" id="education_lain" style="display:none; margin-top:10px;" placeholder="Tulis pendidikan...">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Jurusan <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="jurusan" placeholder="Contoh: Teknik Informatika" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Pengalaman (Tahun) <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="experiences" placeholder="Contoh: Minimal 2 Tahun" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Tinggi Badan (cm)</label>
                                                    <input type="number" class="form-control" name="height" placeholder="0">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Berat Badan (kg)</label>
                                                    <input type="number" class="form-control" name="weight" placeholder="0">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Rentang Gaji</label>
                                                    <input type="text" class="form-control" name="range_salary" placeholder="Contoh: 4.500.000 - 5.500.000">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Keahlian dan Kemampuan</label>
                                                    <input type="text" class="form-control mb-2" name="skill1" placeholder="1. Contoh: Microsoft Office" style="margin-bottom:5px">
                                                    <input type="text" class="form-control mb-2" name="skill2" placeholder="2. Contoh: Komunikasi Baik" style="margin-bottom:5px">
                                                    <input type="text" class="form-control" name="skill3" placeholder="3. Contoh: Analisa Data">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Lain-lain</label>
                                                    <textarea class="form-control" name="other" rows="4" placeholder="Keterangan tambahan lainnya..."></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 text-center" style="margin-bottom: 50px;">
                                <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-save"></i> Simpan Permintaan</button>
                                <button type="reset" class="btn btn-default btn-lg"><i class="fa fa-refresh"></i> Reset</button>
                                <button type="button" onclick="window.print()" class="btn btn-info btn-lg hidden-print"><i class="fa fa-print"></i> Cetak</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Toggle Lain-lain untuk field 'Untuk'
        $('#untuk_select').change(function() {
            if ($(this).val() == 'Lain-lain') {
                $('#untuk_lain').show().attr('required', true);
            } else {
                $('#untuk_lain').hide().removeAttr('required');
            }
        });

        // Toggle Lain-lain untuk field 'Pendidikan'
        $('#education_select').change(function() {
            if ($(this).val() == 'Lain-lain') {
                $('#education_lain').show().attr('required', true);
            } else {
                $('#education_lain').hide().removeAttr('required');
            }
        });

        // Validasi minimal 1 Job Duty
        $('#formPermintaan').submit(function(e) {
            var dutyFilled = false;
            for (var i = 1; i <= 10; i++) {
                if ($('input[name="duty' + i + '"]').val().trim() !== "") {
                    dutyOk = true;
                    dutyFilled = true;
                    break;
                }
            }

            if (!dutyFilled) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Peringatan',
                    text: 'Minimal isi satu Job Duty (Tugas Pekerjaan)!',
                });
            }
        });
    });
</script>

<?php include 'layout_bottom.php'; ?>
