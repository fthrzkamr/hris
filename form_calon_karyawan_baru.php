<?php
// No login required - public form for job applicants
include("dist/config/koneksi.php");

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data (non-sensitive fields only)
    $nama_emp = trim($_POST['nama_emp'] ?? '');
    $jk_emp = $_POST['jk_emp'] ?? '';
    $telp_emp = trim($_POST['telp_emp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $nomor_ktp = trim($_POST['nomor_ktp'] ?? '');
    $kota_lahir = trim($_POST['kota_lahir'] ?? '');
    $alamat_tinggal_sekarang = trim($_POST['alamat_tinggal_sekarang'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $pendidikan_terakhir = $_POST['pendidikan_terakhir'] ?? '';
    $nama_institusi = trim($_POST['nama_institusi'] ?? '');
    $jurusan = trim($_POST['jurusan'] ?? '');
    $status_kawin = $_POST['status_kawin'] ?? 'belum menikah';
    $nama_pasangan = trim($_POST['nama_pasangan'] ?? '');
    $pekerjaan = trim($_POST['pekerjaan'] ?? '');
    $nomor_tlp = trim($_POST['nomor_tlp'] ?? '');
    $nama_anak = trim($_POST['nama_anak'] ?? '');
    $agama = $_POST['agama'] ?? '';
    $gol_darah = $_POST['gol_darah'] ?? '';
    $nomor_kk = trim($_POST['nomor_kk'] ?? '');
    $nomor_npwp = trim($_POST['nomor_npwp'] ?? '');
    $bpjs_kesehatan = trim($_POST['bpjs_kesehatan'] ?? '');
    $nama_bank = trim($_POST['nama_bank'] ?? '');
    $norek_mandiri = trim($_POST['norek_mandiri'] ?? '');
    $nomor_emrg_pr = trim($_POST['nomor_emrg_pr'] ?? '');
    $nomor_emrg_kd = trim($_POST['nomor_emrg_kd'] ?? '');
    $cabang = $_POST['cabang'] ?? '';
    $nama_bagian = $_POST['nama_bagian'] ?? '';

    // Generate NPP for applicant: Format YYDDMMXX (8 digits)
    // YY = 2 digit tahun (26 untuk 2026)
    // DDMM = 4 digit tanggal lahir (1503 untuk 15 Maret)
    // XX = 2 digit urutan (01-99)
    $tahun_2digit = date('y'); // 26 for 2026
    
    // Extract birth date components (DDMM format)
    if (!empty($tanggal_lahir)) {
        $tgl_lahir_obj = new DateTime($tanggal_lahir);
        $ddmm = $tgl_lahir_obj->format('dm'); // Format: 1503 for March 15
        
        // Get last NPP with same year and birth date prefix
        $prefix = $tahun_2digit . $ddmm;
        $query_last_npp = mysqli_query($conn, "SELECT npp FROM employee WHERE npp LIKE '{$prefix}%' ORDER BY npp DESC LIMIT 1");
        
        if ($query_last_npp && mysqli_num_rows($query_last_npp) > 0) {
            $last_npp = mysqli_fetch_assoc($query_last_npp)['npp'];
            // Extract last 2 digits and increment
            $urutan = intval(substr($last_npp, 6)) + 1;
        } else {
            // First applicant with this birth date
            $urutan = 1;
        }
        
        // Format: 26150301, 26150302, etc. (8 digits total)
        $temp_npp = $prefix . str_pad($urutan, 2, '0', STR_PAD_LEFT);
    } else {
        // Fallback if no birth date (should not happen due to validation)
        $temp_npp = $tahun_2digit . '000001';
    }

    // Handle photo upload
    $foto_emp = '';
    if (isset($_FILES['foto_emp']) && $_FILES['foto_emp']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['foto_emp']['tmp_name'];
        $file_extension = strtolower(pathinfo($_FILES['foto_emp']['name'], PATHINFO_EXTENSION));
        
        // Validate file type (only images)
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($file_extension, $allowed_extensions)) {
            // Create filename: foto{npp}.{extension}
            $foto_filename = 'foto' . $temp_npp . '.' . $file_extension;
            
            // Create directory if not exists
            $upload_dir = __DIR__ . '/foto/karyawan';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $upload_path = $upload_dir . '/' . $foto_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $foto_emp = 'foto/karyawan/' . $foto_filename;
            } else {
                $error = 'Gagal mengupload foto';
            }
        } else {
            $error = 'Format foto tidak valid. Gunakan JPG, PNG, GIF, atau WEBP';
        }
    }

    // Validate required fields
    if (!empty($error)) {
        // Error already set from file upload
    } else if (empty($nama_emp) || empty($jk_emp) || empty($telp_emp) || empty($alamat) || empty($nomor_ktp) || 
        empty($kota_lahir) || empty($alamat_tinggal_sekarang) || empty($tanggal_lahir) || 
        empty($nomor_kk) || empty($agama) || empty($gol_darah) || empty($status_kawin) || empty($nomor_tlp) || 
        empty($pendidikan_terakhir) || empty($nama_institusi) || empty($jurusan) || 
        empty($nama_pasangan) || empty($pekerjaan) || empty($nama_anak) ||
        empty($nomor_emrg_pr) || empty($nomor_emrg_kd) || empty($nomor_npwp) || 
        empty($bpjs_kesehatan) || empty($nama_bank) || empty($norek_mandiri) || empty($cabang) || empty($nama_bagian)) {
        $error = 'Mohon lengkapi semua data yang wajib diisi';
    } else if (empty($foto_emp)) {
        $error = 'Foto wajib diupload';
    } else {
        // Insert into employee table with status 'Calon Karyawan'
        $stmt = mysqli_prepare($conn, "INSERT INTO employee 
            (npp, nama_emp, jk_emp, telp_emp, alamat, nomor_ktp, kota_lahir, alamat_tinggal_sekarang, 
            tanggal_lahir, pendidikan_terakhir, nama_institusi, jurusan, status_kawin, nama_pasangan, 
            pekerjaan, nomor_tlp, nama_anak, agama, gol_darah, nomor_kk, nomor_npwp, bpjs_kesehatan, 
            nama_bank, nomor_emrg_pr, nomor_emrg_kd, hak_akses, status_karyawan, aktif, jml_cuti, 
            nama_bagian, password, foto_emp, norek_mandiri, id_adm, cabang, kesehatan, plafond, 
            nama_koordinator, nama_manager, status_rem, jabatan, status_ptkp, nomor_bpjs_ktr, 
            plafond_kacamata, kacamata, gaji_pokok, tunj_jabatan, tunj_transport, tunj_kinerja, total_gaji)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $hak_akses = 'Manager';
        $status_karyawan = 'Calon Karyawan';
        $aktif = 'Menunggu Review';
        $jml_cuti = 0;
        // $nama_bagian already collected from POST
        $password = $temp_npp; // Password sama dengan NPP
        // $foto_emp already set from upload
        // $norek_mandiri already collected from POST
        $id_adm = 0;
        // $cabang already collected from POST
        $kesehatan = 0;
        $plafond = 0;
        $nama_koordinator = '';
        $nama_manager = '';
        $status_rem = 'N/A';
        $jabatan = '';
        $status_ptkp = '';
        $nomor_bpjs_ktr = '';
        $plafond_kacamata = 0;
        $kacamata = 0;
        $gaji_pokok = 0;
        $tunj_jabatan = 0;
        $tunj_transport = 0;
        $tunj_kinerja = 0;
        $total_gaji = 0;

        mysqli_stmt_bind_param(
            $stmt,
            'ssssssssssssssssssssssssssssissssisiissssssiiiiiii',
            $temp_npp,
            $nama_emp,
            $jk_emp,
            $telp_emp,
            $alamat,
            $nomor_ktp,
            $kota_lahir,
            $alamat_tinggal_sekarang,
            $tanggal_lahir,
            $pendidikan_terakhir,
            $nama_institusi,
            $jurusan,
            $status_kawin,
            $nama_pasangan,
            $pekerjaan,
            $nomor_tlp,
            $nama_anak,
            $agama,
            $gol_darah,
            $nomor_kk,
            $nomor_npwp,
            $bpjs_kesehatan,
            $nama_bank,
            $nomor_emrg_pr,
            $nomor_emrg_kd,
            $hak_akses,
            $status_karyawan,
            $aktif,
            $jml_cuti,
            $nama_bagian,
            $password,
            $foto_emp,
            $norek_mandiri,
            $id_adm,
            $cabang,
            $kesehatan,
            $plafond,
            $nama_koordinator,
            $nama_manager,
            $status_rem,
            $jabatan,
            $status_ptkp,
            $nomor_bpjs_ktr,
            $plafond_kacamata,
            $kacamata,
            $gaji_pokok,
            $tunj_jabatan,
            $tunj_transport,
            $tunj_kinerja,
            $total_gaji
        );

        if (mysqli_stmt_execute($stmt)) {
            $success = true;
        } else {
            $error = 'Gagal menyimpan data: ' . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form Calon Karyawan Baru - HRIS </title>

    <link href="libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="libs/font-awesome/css/font-awesome.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }

        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            margin: 0 auto;
            max-width: 900px;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }

        .form-header h2 {
            color: #667eea;
            font-weight: bold;
            margin: 0 0 10px 0;
        }

        .form-header p {
            color: #666;
            margin: 0;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section-title {
            background: #667eea;
            color: white;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }

        .required-mark {
            color: red;
            font-weight: bold;
        }

        .form-control {
            border-radius: 5px;
        }

        .btn-submit {
            background: #667eea;
            border: none;
            padding: 12px 40px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
        }

        .btn-submit:hover {
            background: #764ba2;
        }

        .alert {
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h2><i class="fa fa-file-text"></i> Form Calon Karyawan Baru</h2>
                <p>Human Resources Information System</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i> <strong>Terima kasih!</strong> Data Anda telah berhasil dikirim.
                    Tim HR kami akan menghubungi Anda segera. Nomor referensi Anda:
                    <strong><?php echo $temp_npp; ?></strong>
                </div>
                <div class="text-center" style="margin-top: 20px;">
                    <a href="form_calon_karyawan_baru.php" class="btn btn-primary">Kembali ke Form</a>
                </div>
            <?php else: ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i> <strong>Error:</strong>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">

                    <div class="alert alert-info" style="margin-top: 30px;">
                        <i class="fa fa-info-circle"></i> <strong>Catatan:</strong>
                        Field yang bertanda <span class="required-mark">*</span> wajib diisi.
                        Data yang Anda kirimkan akan diproses oleh tim HR kami.
                    </div>
                    <!-- 1. PROFIL KARYAWAN -->
                    <div class="form-section">
                        <div class="form-section-title">1. Profil Karyawan</div>

                        <div class="form-group">
                            <label>Foto <span class="required-mark">*</span></label>
                            <input type="file" name="foto_emp" class="form-control" required accept="image/*">
                            <small class="text-muted">Format: JPG, PNG, GIF, atau WEBP (Maksimal 2MB)</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nama Lengkap <span class="required-mark">*</span></label>
                                    <input type="text" name="nama_emp" class="form-control" required
                                        value="<?php echo htmlspecialchars($_POST['nama_emp'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Jenis Kelamin <span class="required-mark">*</span></label>
                                    <select name="jk_emp" class="form-control" required>
                                        <option value="">-- Pilih --</option>
                                        <option value="Laki-Laki" <?php echo (($_POST['jk_emp'] ?? '') === 'Laki-Laki') ? 'selected' : ''; ?>>Laki-Laki</option>
                                        <option value="Perempuan" <?php echo (($_POST['jk_emp'] ?? '') === 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tempat Lahir <span class="required-mark">*</span></label>
                                    <input type="text" name="kota_lahir" class="form-control" required
                                        value="<?php echo htmlspecialchars($_POST['kota_lahir'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tanggal Lahir <span class="required-mark">*</span></label>
                                    <input type="date" name="tanggal_lahir" class="form-control" required
                                        value="<?php echo htmlspecialchars($_POST['tanggal_lahir'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Alamat Sesuai KTP <span class="required-mark">*</span></label>
                            <textarea name="alamat" class="form-control" required
                                rows="2"><?php echo htmlspecialchars($_POST['alamat'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Alamat Tinggal Sekarang <span class="required-mark">*</span></label>
                            <textarea name="alamat_tinggal_sekarang" class="form-control" required
                                rows="2"><?php echo htmlspecialchars($_POST['alamat_tinggal_sekarang'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>No. KTP <span class="required-mark">*</span></label>
                                    <input type="text" name="nomor_ktp" class="form-control" required maxlength="16"
                                        value="<?php echo htmlspecialchars($_POST['nomor_ktp'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>No. Kartu Keluarga <span class="required-mark">*</span></label>
                                    <input type="text" name="nomor_kk" class="form-control" required maxlength="16"
                                        value="<?php echo htmlspecialchars($_POST['nomor_kk'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Agama <span class="required-mark">*</span></label>
                                    <select name="agama" class="form-control" required>
                                        <option value="">-- Pilih --</option>
                                        <option value="Islam" <?php echo (($_POST['agama'] ?? '') === 'Islam') ? 'selected' : ''; ?>>Islam</option>
                                        <option value="Kristen" <?php echo (($_POST['agama'] ?? '') === 'Kristen') ? 'selected' : ''; ?>>Kristen</option>
                                        <option value="Katolik" <?php echo (($_POST['agama'] ?? '') === 'Katolik') ? 'selected' : ''; ?>>Katolik</option>
                                        <option value="Hindu" <?php echo (($_POST['agama'] ?? '') === 'Hindu') ? 'selected' : ''; ?>>Hindu</option>
                                        <option value="Buddha" <?php echo (($_POST['agama'] ?? '') === 'Buddha') ? 'selected' : ''; ?>>Buddha</option>
                                        <option value="Konghucu" <?php echo (($_POST['agama'] ?? '') === 'Konghucu') ? 'selected' : ''; ?>>Konghucu</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Golongan Darah <span class="required-mark">*</span></label>
                                    <select name="gol_darah" class="form-control" required>
                                        <option value="">-- Pilih --</option>
                                        <option value="A" <?php echo (($_POST['gol_darah'] ?? '') === 'A') ? 'selected' : ''; ?>>A</option>
                                        <option value="B" <?php echo (($_POST['gol_darah'] ?? '') === 'B') ? 'selected' : ''; ?>>B</option>
                                        <option value="AB" <?php echo (($_POST['gol_darah'] ?? '') === 'AB') ? 'selected' : ''; ?>>AB</option>
                                        <option value="O" <?php echo (($_POST['gol_darah'] ?? '') === 'O') ? 'selected' : ''; ?>>O</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Status Perkawinan <span class="required-mark">*</span></label>
                                    <select name="status_kawin" class="form-control" required>
                                        <option value="belum menikah" <?php echo (($_POST['status_kawin'] ?? 'belum menikah') === 'belum menikah') ? 'selected' : ''; ?>>Belum Menikah</option>
                                        <option value="sudah menikah" <?php echo (($_POST['status_kawin'] ?? '') === 'sudah menikah') ? 'selected' : ''; ?>>Sudah Menikah</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>No. HP/Telepon <span class="required-mark">*</span></label>
                                    <input type="text" name="telp_emp" class="form-control" required
                                        value="<?php echo htmlspecialchars($_POST['telp_emp'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>No. HP Alternatif <span class="required-mark">*</span></label>
                                    <input type="text" name="nomor_tlp" class="form-control" required
                                        value="<?php echo htmlspecialchars($_POST['nomor_tlp'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. PENDIDIKAN -->
                    <div class="form-section">
                        <div class="form-section-title">2. Pendidikan Terakhir</div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Jenjang Pendidikan <span class="required-mark">*</span></label>
                                    <select name="pendidikan_terakhir" class="form-control" required>
                                        <option value="">-- Pilih --</option>
                                        <option value="SD" <?php echo (($_POST['pendidikan_terakhir'] ?? '') === 'SD') ? 'selected' : ''; ?>>SD</option>
                                        <option value="SMP" <?php echo (($_POST['pendidikan_terakhir'] ?? '') === 'SMP') ? 'selected' : ''; ?>>SMP</option>
                                        <option value="SMA" <?php echo (($_POST['pendidikan_terakhir'] ?? '') === 'SMA') ? 'selected' : ''; ?>>SMA</option>
                                        <option value="SMK" <?php echo (($_POST['pendidikan_terakhir'] ?? '') === 'SMK') ? 'selected' : ''; ?>>SMK</option>
                                        <option value="D3" <?php echo (($_POST['pendidikan_terakhir'] ?? '') === 'D3') ? 'selected' : ''; ?>>D3</option>
                                        <option value="S1" <?php echo (($_POST['pendidikan_terakhir'] ?? '') === 'S1') ? 'selected' : ''; ?>>S1</option>
                                        <option value="S2" <?php echo (($_POST['pendidikan_terakhir'] ?? '') === 'S2') ? 'selected' : ''; ?>>S2</option>
                                        <option value="S3" <?php echo (($_POST['pendidikan_terakhir'] ?? '') === 'S3') ? 'selected' : ''; ?>>S3</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Nama Institusi/Sekolah <span class="required-mark">*</span></label>
                                    <input type="text" name="nama_institusi" class="form-control" required
                                        value="<?php echo htmlspecialchars($_POST['nama_institusi'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Jurusan <span class="required-mark">*</span></label>
                            <input type="text" name="jurusan" class="form-control" required
                                value="<?php echo htmlspecialchars($_POST['jurusan'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- 3. KELUARGA -->
                    <div class="form-section">
                        <div class="form-section-title">3. Data Keluarga</div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nama Pasangan <span class="required-mark">*</span></label>
                                    <input type="text" name="nama_pasangan" class="form-control" required
                                        value="<?php echo htmlspecialchars($_POST['nama_pasangan'] ?? ''); ?>">
                                    <small class="text-muted">Isi "-" jika belum menikah</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Pekerjaan Pasangan <span class="required-mark">*</span></label>
                                    <input type="text" name="pekerjaan" class="form-control" required
                                        value="<?php echo htmlspecialchars($_POST['pekerjaan'] ?? ''); ?>">
                                    <small class="text-muted">Isi "-" jika belum menikah</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Nama Anak <span class="required-mark">*</span></label>
                            <textarea name="nama_anak" class="form-control" required
                                rows="2"><?php echo htmlspecialchars($_POST['nama_anak'] ?? ''); ?></textarea>
                            <small class="text-muted">Pisahkan dengan koma jika lebih dari satu. Isi "-" jika belum ada</small>
                        </div>
                    </div>

                    <!-- 4. KONTAK DARURAT -->
                    <div class="form-section">
                        <div class="form-section-title">4. Kontak Darurat</div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nomor Kontak Darurat 1 <span class="required-mark">*</span></label>
                                    <input type="text" name="nomor_emrg_pr" class="form-control" required
                                        value="<?php echo htmlspecialchars($_POST['nomor_emrg_pr'] ?? ''); ?>">
                                    <small class="text-muted">Contoh: Orang tua/Saudara</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nomor Kontak Darurat 2 <span class="required-mark">*</span></label>
                                    <input type="text" name="nomor_emrg_kd" class="form-control" required
                                        value="<?php echo htmlspecialchars($_POST['nomor_emrg_kd'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. DATA TAMBAHAN -->
                    <div class="form-section">
                        <div class="form-section-title">5. Data Tambahan</div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>No. NPWP <span class="required-mark">*</span></label>
                                    <input type="text" name="nomor_npwp" class="form-control" required
                                        value="<?php echo htmlspecialchars($_POST['nomor_npwp'] ?? ''); ?>">
                                    <small class="text-muted">Isi "-" jika belum memiliki</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>No. BPJS Kesehatan <span class="required-mark">*</span></label>
                                    <input type="text" name="bpjs_kesehatan" class="form-control" required
                                        value="<?php echo htmlspecialchars($_POST['bpjs_kesehatan'] ?? ''); ?>">
                                    <small class="text-muted">Isi "-" jika belum memiliki</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nama Bank <span class="required-mark">*</span></label>
                                    <input type="text" name="nama_bank" class="form-control" required
                                        placeholder="Contoh: BCA, Mandiri, BRI, BNI"
                                        value="<?php echo htmlspecialchars($_POST['nama_bank'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nomor Rekening <span class="required-mark">*</span></label>
                                    <input type="text" name="norek_mandiri" class="form-control" required
                                        placeholder="Masukkan nomor rekening"
                                        value="<?php echo htmlspecialchars($_POST['norek_mandiri'] ?? ''); ?>">
                                    <small class="text-muted">Sesuai nama bank di atas</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Penempatan Kerja <span class="required-mark">*</span></label>
                                    <select name="cabang" class="form-control" required>
                                        <option value="">-- Pilih Cabang --</option>
                                        <?php
                                        $query_cabang = mysqli_query($conn, "SELECT nama_cabang FROM master_cabang ORDER BY nama_cabang ASC");
                                        if ($query_cabang) {
                                            while ($row_cabang = mysqli_fetch_assoc($query_cabang)) {
                                                $selected = (($_POST['cabang'] ?? '') === $row_cabang['nama_cabang']) ? 'selected' : '';
                                                echo '<option value="' . htmlspecialchars($row_cabang['nama_cabang']) . '" ' . $selected . '>' . htmlspecialchars($row_cabang['nama_cabang']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Bagian/Departemen <span class="required-mark">*</span></label>
                                    <select name="nama_bagian" class="form-control" required>
                                        <option value="">-- Pilih Bagian --</option>
                                        <?php
                                        $query_bagian = mysqli_query($conn, "SELECT id_bagian, nama_bagian FROM bagian ORDER BY nama_bagian ASC");
                                        if ($query_bagian) {
                                            while ($row_bagian = mysqli_fetch_assoc($query_bagian)) {
                                                $selected = (($_POST['nama_bagian'] ?? '') === $row_bagian['nama_bagian']) ? 'selected' : '';
                                                echo '<option value="' . htmlspecialchars($row_bagian['nama_bagian']) . '" ' . $selected . '>' . htmlspecialchars($row_bagian['nama_bagian']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-submit">
                            <i class="fa fa-paper-plane"></i> Kirim Data
                        </button>
                    </div>


                </form>

            <?php endif; ?>
        </div>
    </div>

    <script src="libs/jquery/dist/jquery.min.js"></script>
    <script src="libs/bootstrap/dist/js/bootstrap.min.js"></script>
</body>

</html>