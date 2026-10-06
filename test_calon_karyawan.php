<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("dist/config/koneksi.php");

// Ambil IP pengguna
$user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$user_ip = trim(explode(',', $user_ip)[0]); // Ambil IP pertama jika ada proxy
$user_ip = mysqli_real_escape_string($conn, $user_ip);

// Limit: max 5 submission per IP per hari
$LIMIT_PER_DAY = 5;

$finished = isset($_GET['selesai']) && $_GET['selesai'] == '1';

// PRG: Baca hasil dari session setelah redirect
$success = false;
$submitted_data = [];
if (isset($_GET['hasil']) && $_GET['hasil'] == '1' && isset($_SESSION['disc_result'])) {
    $success = true;
    $submitted_data = $_SESSION['disc_result'];
    unset($_SESSION['disc_result']); // Hapus session setelah dibaca — refresh tidak akan re-submit
}

// 1. Create table dynamically if not exists
if (isset($conn)) {
    $createTableQuery = "CREATE TABLE IF NOT EXISTS test_calon_karyawan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(100) NOT NULL,
        tgl_lahir DATE NOT NULL,
        tgl_test DATE NOT NULL,
        bagian VARCHAR(100) NOT NULL,
        skor_earth INT NOT NULL,
        skor_air INT NOT NULL,
        skor_water INT NOT NULL,
        skor_fire INT NOT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $createTableQuery);

    // Tambah kolom ip_address jika belum ada (untuk tabel lama)
    $cek_ip_col = mysqli_query($conn, "SHOW COLUMNS FROM test_calon_karyawan LIKE 'ip_address'");
    if (mysqli_num_rows($cek_ip_col) == 0) {
        mysqli_query($conn, "ALTER TABLE test_calon_karyawan ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL AFTER skor_fire");
    }
}

// 2. Cek limit IP hari ini
$ip_blocked = false;
$ip_count_today = 0;
if (isset($conn)) {
    $res_ip = mysqli_query($conn, "SELECT COUNT(*) as total FROM test_calon_karyawan WHERE ip_address = '$user_ip' AND DATE(created_at) = CURDATE()");
    if ($res_ip) {
        $ip_count_today = (int) mysqli_fetch_assoc($res_ip)['total'];
        if ($ip_count_today >= $LIMIT_PER_DAY) {
            $ip_blocked = true;
        }
    }
}


// 2. Fetch existing departments/bagian for the dropdown
$bagian_options = [];
if (isset($conn)) {
    $q_bagian = mysqli_query($conn, "SELECT DISTINCT nama_bagian FROM bagian ORDER BY nama_bagian ASC");
    if ($q_bagian) {
        while ($row = mysqli_fetch_assoc($q_bagian)) {
            $bagian_options[] = $row['nama_bagian'];
        }
    }
}

// 9 Rows DISC words: Col 0=D (Dominance), Col 1=I (Influence), Col 2=C (Conscientiousness), Col 3=S (Steadiness)
$disc_rows = [
    ['Tegas', 'Antusias', 'Hati-hati', 'Sabar'],
    ['Langsung', 'Optimis', 'Analitis', 'Stabil'],
    ['Berani', 'Ramah', 'Teliti', 'Setia'],
    ['Kompetitif', 'Persuasif', 'Akurat', 'Kooperatif'],
    ['Mandiri', 'Ekspresif', 'Sistematis', 'Harmonis'],
    ['Kuat', 'Percaya diri', 'Terstruktur', 'Penuh dukungan'],
    ['Dominan', 'Menginspirasi', 'Perfeksionis', 'Konsisten'],
    ['Hasil', 'Spontan', 'Kualitas', 'Dapat diandalkan'],
    ['Agresif', 'Bergaul', 'Logis', 'Pendengar yang baik']
];

$error_msg = "";
if (isset($_POST['submit_test'])) {
    $nama = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $tgl_lahir = mysqli_real_escape_string($conn, $_POST['tgl_lahir']);
    $tgl_test = mysqli_real_escape_string($conn, $_POST['tgl_test']);
    $bagian = mysqli_real_escape_string($conn, trim($_POST['bagian']));

    // Initialize scores — D=col0(earth), I=col1(fire), C=col2(air), S=col3(water)
    $skor_earth = 0; // D - Dominance
    $skor_fire  = 0; // I - Influence
    $skor_air   = 0; // C - Conscientiousness
    $skor_water = 0; // S - Steadiness

    $isValid = true;

    // Sum scores from 9 rows and validate each row on backend
    for ($i = 0; $i < 9; $i++) {
        $r0 = intval($_POST["row_{$i}_col_0"] ?? 0);
        $r1 = intval($_POST["row_{$i}_col_1"] ?? 0);
        $r2 = intval($_POST["row_{$i}_col_2"] ?? 0);
        $r3 = intval($_POST["row_{$i}_col_3"] ?? 0);

        $row_vals = [$r0, $r1, $r2, $r3];
        $unique_vals = array_unique($row_vals);
        
        if (count($unique_vals) < 4 || min($row_vals) < 1 || max($row_vals) > 4) {
            $isValid = false;
            $error_msg = "Terdeteksi manipulasi data! Setiap baris wajib diisi angka 1, 2, 3, dan 4 secara unik.";
            break;
        }

        $skor_earth += $r0; // D
        $skor_fire  += $r1; // I
        $skor_air   += $r2; // C
        $skor_water += $r3; // S
    }

    if ($isValid) {
        // Insert to DB
        $query = "INSERT INTO test_calon_karyawan (nama, tgl_lahir, tgl_test, bagian, skor_earth, skor_air, skor_water, skor_fire, ip_address) 
                  VALUES ('$nama', '$tgl_lahir', '$tgl_test', '$bagian', $skor_earth, $skor_air, $skor_water, $skor_fire, '$user_ip')";
        
        if (mysqli_query($conn, $query)) {

            // Find dominant DISC type
            $scores = [
                'Dominance (D)' => $skor_earth,
                'Influence (I)' => $skor_fire,
                'Conscientiousness (C)' => $skor_air,
                'Steadiness (S)' => $skor_water
            ];
            arsort($scores);
            $dominant = key($scores);

            // PRG Pattern: simpan hasil ke session lalu redirect ke GET
            // Ini mencegah double submit saat browser di-refresh
            $_SESSION['disc_result'] = [
                'nama'     => $nama,
                'tgl_lahir'=> date('d-m-Y', strtotime($tgl_lahir)),
                'tgl_test' => date('d-m-Y', strtotime($tgl_test)),
                'bagian'   => $bagian,
                'skor_d'   => $skor_earth,
                'skor_i'   => $skor_fire,
                'skor_c'   => $skor_air,
                'skor_s'   => $skor_water,
                'dominant' => $dominant
            ];
            header("Location: test_calon_karyawan.php?hasil=1");
            exit;

        } else {
            $error_msg = "Gagal menyimpan data ke database. Silakan coba beberapa saat lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DISC PERSONALITY TEST</title>
    <!-- Bootstrap Core CSS -->
    <link href="libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="libs/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #121212;
            color: #ffffff;
            padding-bottom: 60px;
            letter-spacing: 0.2px;
            overflow-x: hidden;
        }
        .test-container {
            max-width: 860px;
            margin: 30px auto;
            padding: 32px;
            background: #1c1c1e;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.5);
            border: 1px solid #2c2c2e;
        }
        .tetramap-title {
            font-weight: 800;
            font-size: 22px;
            letter-spacing: 1.5px;
            text-align: center;
            margin-bottom: 30px;
            color: #ffffff;
            text-transform: uppercase;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 25px;
            font-size: 15px;
        }
        .meta-table td {
            padding: 8px 0;
            vertical-align: middle;
        }
        .meta-label {
            width: 160px;
            min-width: 120px;
            font-weight: 600;
            color: #aeaeb2;
            white-space: nowrap;
        }
        .meta-underline {
            border-bottom: 1px dotted #aeaeb2;
            padding: 2px 10px;
            font-weight: 700;
            color: #ffffff;
            word-break: break-word;
        }
        .meta-input {
            background: transparent;
            border: none;
            border-bottom: 1px dotted #ffffff;
            color: #ffffff;
            font-weight: 700;
            outline: none;
            width: 100%;
            padding: 2px 4px;
        }
        .meta-select {
            background: #2c2c2e;
            color: #ffffff;
            border: none;
            border-bottom: 1px dotted #ffffff;
            font-weight: 700;
            outline: none;
            width: 100%;
            padding: 2px 4px;
        }
        .instruction-box {
            color: #e5e5ea;
            font-size: 13.5px;
            line-height: 1.7;
            margin-bottom: 25px;
            border-bottom: 1px dashed #3a3a3c;
            padding-bottom: 20px;
        }
        .example-box {
            border: 1px solid #48484a;
            padding: 18px;
            border-radius: 12px;
            margin-bottom: 30px;
            background: rgba(255,255,255,0.03);
        }
        .example-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        .example-item {
            border: 1px solid #ffffff;
            padding: 8px 10px;
            text-align: center;
            font-size: 13px;
            border-radius: 6px;
            background: rgba(255,255,255,0.05);
        }
        .example-item strong {
            font-weight: 800;
            margin-right: 6px;
            background: #ffffff;
            color: #121212;
            padding: 2px 5px;
            border-radius: 3px;
        }
        /* DISC Grid */
        .grid-table {
            width: 100%;
            margin-bottom: 30px;
        }
        .grid-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 10px;
        }
        .grid-cell {
            border: 1px solid #ffffff;
            background: rgba(255,255,255,0.02);
            border-radius: 8px;
            padding: 12px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            transition: all 0.2s ease;
            min-width: 0;
        }
        .grid-cell:hover {
            background: rgba(255,255,255,0.05);
        }
        .grid-cell.active {
            border-color: #0a84ff;
            background: rgba(10, 132, 255, 0.05);
        }
        .grid-cell.error {
            border-color: #ff453a;
            background: rgba(255, 69, 58, 0.05);
        }
        .word-label {
            font-weight: 600;
            font-size: 13px;
            color: #ffffff;
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .grid-select {
            background: transparent;
            color: #ffffff;
            border: 1px solid #545456;
            border-radius: 6px;
            padding: 4px 2px;
            width: 46px;
            min-width: 40px;
            flex-shrink: 0;
            font-weight: 700;
            outline: none;
            text-align: center;
            cursor: pointer;
            -webkit-appearance: none;
            background-color: #2c2c2e;
        }
        .grid-select:focus {
            border-color: #0a84ff;
        }
        .triangles-container {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 30px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px dashed #3a3a3c;
            flex-wrap: wrap;
        }
        .triangle-wrapper {
            text-align: center;
            width: 100px;
            position: relative;
        }
        .triangle-svg {
            width: 75px;
            height: 75px;
            margin: 0 auto 8px;
            transition: all 0.3s ease;
        }
        .triangle-label {
            font-size: 10px;
            font-weight: 700;
            color: #aeaeb2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-apple {
            background-color: #ffffff;
            color: #121212;
            font-weight: 700;
            border-radius: 10px;
            padding: 14px 28px;
            font-size: 15px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(255,255,255,0.15);
        }
        .btn-apple:hover {
            background-color: #f5f5f7;
            color: #121212;
            text-decoration: none;
            transform: translateY(-1px);
        }
        .btn-apple:disabled {
            background-color: #3a3a3c;
            color: #8e8e93;
            cursor: not-allowed;
            box-shadow: none;
        }
        /* Sticky Progress CSS */
        .progress-bar-container {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(28, 28, 30, 0.97);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 12px 0;
            border-bottom: 1px solid #2c2c2e;
            margin-bottom: 25px;
        }
        .progress-bar-custom {
            height: 6px;
            background-color: #3a3a3c;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 8px;
        }
        .progress-fill {
            height: 100%;
            background-color: #ffffff;
            width: 0%;
            transition: width 0.3s ease;
        }
        .result-number {
            position: absolute;
            left: 0;
            right: 0;
            font-weight: 800;
            font-size: 20px;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0,0,0,0.6);
        }

        /* ===== RESPONSIVE: TABLET ===== */
        @media (max-width: 768px) {
            .test-container {
                margin: 16px;
                padding: 22px 14px;
                border-radius: 14px;
            }
            .tetramap-title {
                font-size: 18px;
                letter-spacing: 1px;
                margin-bottom: 22px;
            }
            .grid-row {
                gap: 6px;
            }
            .grid-cell {
                padding: 10px 7px;
                gap: 5px;
            }
            .word-label {
                font-size: 12px;
            }
            .grid-select {
                width: 38px;
                min-width: 34px;
                font-size: 13px;
                padding: 3px 2px;
            }
            .example-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .meta-label {
                width: 130px;
                min-width: 100px;
                font-size: 13px;
            }
            .triangles-container {
                gap: 20px;
            }
            .triangle-wrapper {
                width: 80px;
            }
            .triangle-svg {
                width: 65px;
                height: 65px;
            }
        }

        /* ===== RESPONSIVE: MOBILE ===== */
        @media (max-width: 480px) {
            body {
                padding-bottom: 40px;
            }
            .test-container {
                margin: 8px;
                padding: 14px 8px;
                border-radius: 12px;
            }
            .tetramap-title {
                font-size: 14px;
                letter-spacing: 0.5px;
                margin-bottom: 16px;
            }
            /* Stack meta-table labels above value on very small screens */
            .meta-table,
            .meta-table tbody,
            .meta-table tr,
            .meta-table td {
                display: block;
                width: 100%;
            }
            .meta-table tr {
                margin-bottom: 8px;
            }
            .meta-table td:nth-child(2) {
                display: none;
            }
            .meta-label {
                width: 100%;
                font-size: 11px;
                padding-bottom: 2px;
                color: #8e8e93;
            }
            .meta-underline {
                padding: 2px 0;
                font-size: 13px;
            }
            /* Keep 4 columns but much smaller cells */
            .grid-row {
                grid-template-columns: repeat(4, 1fr);
                gap: 4px;
                margin-bottom: 4px;
            }
            .grid-cell {
                padding: 7px 4px;
                border-radius: 6px;
                gap: 3px;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
            }
            .word-label {
                font-size: 10px;
                white-space: normal;
                text-overflow: unset;
                overflow: visible;
                line-height: 1.2;
            }
            .grid-select {
                width: 36px;
                min-width: 32px;
                font-size: 12px;
                padding: 2px 1px;
            }
            /* Example grid 2 columns */
            .example-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
            }
            .example-item {
                font-size: 11px;
                padding: 6px 6px;
            }
            .example-item strong {
                margin-right: 3px;
                padding: 1px 4px;
            }
            /* Buttons */
            .btn-apple {
                width: 100%;
                text-align: center;
                padding: 13px 12px;
                font-size: 14px;
            }
            /* Triangles */
            .triangles-container {
                gap: 10px;
                padding-top: 18px;
                margin-top: 20px;
            }
            .triangle-wrapper {
                width: 62px;
            }
            .triangle-svg {
                width: 50px;
                height: 50px;
            }
            .triangle-label {
                font-size: 8px;
            }
            /* Progress bar text smaller */
            .progress-bar-container span {
                font-size: 10px !important;
            }
            /* Submit button area */
            #tetramapForm > .test-container > div:last-of-type {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navbar -->
    <nav class="navbar navbar-default navbar-static-top" style="background: #1c1c1e; border-bottom: 1px solid #2c2c2e; padding: 10px 0; margin-bottom: 0;">
        <div class="container">
            <div class="navbar-header">
                <?php if (!$success && !$finished): ?>
                <a class="navbar-brand" id="navbar-back-link" href="login.php" style="font-weight: 800; color: #ffffff !important; letter-spacing: -0.5px;">
                    <i class="fa fa-chevron-left" style="font-size: 14px;"></i> &nbsp;
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($ip_blocked): ?>
            <!-- ========== BLOCKED VIEW ========== -->
            <div class="test-container text-center" style="max-width: 560px; margin-top: 60px;">
                <div style="font-size: 64px; color: #ff453a; margin-bottom: 20px;">
                    <i class="fa fa-ban"></i>
                </div>
                <h2 class="tetramap-title" style="color: #ff453a; margin-bottom: 15px;">AKSES DIBATASI</h2>
                <p style="color: #aeaeb2; font-size: 15px; line-height: 1.8; margin-bottom: 10px;">
                    IP Anda telah melakukan <strong style="color:#ffffff;"><?php echo $ip_count_today; ?> dari <?php echo $LIMIT_PER_DAY; ?></strong> pengisian test hari ini.
                </p>
                <p style="color: #636366; font-size: 13px;">
                    Batas maksimum <strong><?php echo $LIMIT_PER_DAY; ?> pengisian per hari</strong> dari satu jaringan telah tercapai.<br>
                    Silakan coba kembali besok atau hubungi Tim HRD.
                </p>
            </div>
        <?php elseif (!empty($error_msg)): ?>
            <div class="alert alert-danger" style="border-radius: 12px; margin-top: 20px; font-weight: 600; background-color: rgba(255, 69, 58, 0.1); border: 1px solid #ff453a; color: #ff453a;">
                <i class="fa fa-exclamation-triangle"></i> &nbsp;<?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if ($finished): ?>
            <!-- ==================== THANK YOU VIEW ==================== -->
            <div class="test-container text-center" style="max-width: 600px; margin-top: 60px;">
                <div style="font-size: 72px; color: #30d158; margin-bottom: 25px;">
                    <i class="fa fa-heart"></i>
                </div>
                <h2 class="tetramap-title" style="margin-bottom: 15px; font-weight: 800;">TERIMA KASIH</h2>
                <p style="color: #aeaeb2; font-size: 15px; line-height: 1.8; margin-bottom: 30px;">
                    Tes instrumen TetraMap Anda telah berhasil dikirim dan tersimpan dengan aman.<br>
                    Anda diperbolehkan untuk menutup tab atau jendela browser ini sekarang. Semoga sukses!
                </p>
            </div>

        <?php elseif ($success): ?>
            <!-- ==================== GORGEOUS HIGH-FIDELITY SUCCESS VIEW ==================== -->
            <div class="test-container" style="max-width: 760px;">
                <div style="font-size: 60px; color: #30d158; text-align: center; margin-bottom: 20px;">
                    <i class="fa fa-check-circle"></i>
                </div>
                <h2 class="tetramap-title" style="margin-bottom: 10px; font-weight: 800;">TERIMA KASIH</h2>
 
                <!-- Identitas Underline -->
                <table class="meta-table" style="max-width: 600px; margin: 0 auto 35px;">
                    <tr>
                        <td class="meta-label">Nama</td>
                        <td style="width: 10px;">:</td>
                        <td class="meta-underline"><?php echo htmlspecialchars($submitted_data['nama']); ?></td>
                    </tr>
                    <tr>
                        <td class="meta-label">Posisi Yang Dilamar</td>
                        <td>:</td>
                        <td class="meta-underline"><?php echo htmlspecialchars($submitted_data['bagian']); ?></td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tanggal Test</td>
                        <td>:</td>
                        <td class="meta-underline"><?php echo htmlspecialchars($submitted_data['tgl_test']); ?></td>
                    </tr>
                </table>
 
                <h4 style="font-weight: 700; text-align: center; margin-bottom: 30px; letter-spacing: 0.5px;">HASIL ANALISIS INSTRUMEN</h4>
 
                <div class="triangles-container" style="margin-bottom: 40px; border-top: none; padding-top: 0;">
                    <!-- D - Dominance (upright ▲) -->
                    <div class="triangle-wrapper">
                        <div class="triangle-svg">
                            <svg viewBox="0 0 100 100" style="width: 100%; height: 100%;">
                                <polygon points="50,10 5,90 95,90" fill="#2e7d32" stroke="#ffffff" stroke-width="2.5"/>
                                <text x="50" y="68" fill="#ffffff" font-size="24" font-weight="800" text-anchor="middle" font-family="'Inter', sans-serif"><?php echo $submitted_data['skor_d']; ?></text>
                            </svg>
                        </div>
                        <span class="triangle-label" style="color:#2e7d32;">D - Dominance</span>
                    </div>

                    <!-- I - Influence (inverted ▽) -->
                    <div class="triangle-wrapper">
                        <div class="triangle-svg">
                            <svg viewBox="0 0 100 100" style="width: 100%; height: 100%;">
                                <polygon points="5,10 95,10 50,90" fill="#c62828" stroke="#ffffff" stroke-width="2.5"/>
                                <text x="50" y="44" fill="#ffffff" font-size="24" font-weight="800" text-anchor="middle" font-family="'Inter', sans-serif"><?php echo $submitted_data['skor_i']; ?></text>
                            </svg>
                        </div>
                        <span class="triangle-label" style="color:#c62828;">I - Influence</span>
                    </div>

                    <!-- C - Conscientiousness (upright ▲) -->
                    <div class="triangle-wrapper">
                        <div class="triangle-svg">
                            <svg viewBox="0 0 100 100" style="width: 100%; height: 100%;">
                                <polygon points="50,10 5,90 95,90" fill="#f9a825" stroke="#ffffff" stroke-width="2.5"/>
                                <text x="50" y="68" fill="#ffffff" font-size="24" font-weight="800" text-anchor="middle" font-family="'Inter', sans-serif"><?php echo $submitted_data['skor_c']; ?></text>
                            </svg>
                        </div>
                        <span class="triangle-label" style="color:#f9a825;">C - Conscientiousness</span>
                    </div>

                    <!-- S - Steadiness (inverted ▽) -->
                    <div class="triangle-wrapper">
                        <div class="triangle-svg">
                            <svg viewBox="0 0 100 100" style="width: 100%; height: 100%;">
                                <polygon points="5,10 95,10 50,90" fill="#1565c0" stroke="#ffffff" stroke-width="2.5"/>
                                <text x="50" y="44" fill="#ffffff" font-size="24" font-weight="800" text-anchor="middle" font-family="'Inter', sans-serif"><?php echo $submitted_data['skor_s']; ?></text>
                            </svg>
                        </div>
                        <span class="triangle-label" style="color:#1565c0;">S - Steadiness</span>
                    </div>
                </div>
 
                <div style="background: rgba(255,255,255,0.03); border: 1px solid #2c2c2e; border-radius: 12px; padding: 20px; font-size: 14px; line-height: 1.7; margin-bottom: 30px;">
                    <p style="margin: 0 0 8px 0; font-weight: 700; color: #ffffff;">
                        <i class="fa fa-info-circle" style="color:#0a84ff;"></i> &nbsp;Profil Utama: <?php echo $submitted_data['dominant']; ?>
                    </p>
                    <p style="margin: 0; color: #aeaeb2;">
                        Hasil instrumen kepribadian ini menggambarkan kecenderungan cara berkomunikasi, pemecahan masalah, dan gaya kolaborasi Anda dalam lingkungan profesional. Tim HRD kami akan meninjau profil ini sebagai bagian dari proses evaluasi kerja.
                    </p>
                </div>
 
                <div style="text-align: center;">
                    <a href="test_calon_karyawan.php?selesai=1" class="btn-apple" style="box-shadow: none; border: 1px solid #ffffff; background: transparent; color: #ffffff;">Tutup Halaman</a>
                </div>
            </div>

        <?php else: ?>
            <!-- ==================== HIGH-FIDELITY DARK MODE INSTRUMENT FORM ==================== -->
            <div id="step-1-container" class="test-container" style="max-width: 600px;">
                <h2 class="tetramap-title" style="margin-bottom: 10px; font-weight: 800;">TETRAMAP INSTRUMENT</h2>
                <p style="color: #aeaeb2; text-align: center; font-size: 14px; margin-bottom: 35px;">Formulir Identifikasi Calon Karyawan Baru</p>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="meta-label" style="display:block; margin-bottom: 8px;">Nama Lengkap *</label>
                    <input type="text" id="cand_nama" class="meta-input" placeholder="Tuliskan nama lengkap Anda" required autocomplete="off" style="font-size: 15px; padding: 6px 0;">
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="meta-label" style="display:block; margin-bottom: 8px;">Tanggal Lahir *</label>
                    <input type="date" id="cand_tgl_lahir" class="meta-input" required style="font-size: 15px; padding: 6px 0; color-scheme: dark;">
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="meta-label" style="display:block; margin-bottom: 8px;">Tanggal Test *</label>
                    <input type="date" id="cand_tgl_test" class="meta-input" value="<?php echo date('Y-m-d'); ?>" readonly style="font-size: 15px; padding: 6px 0; color-scheme: dark; opacity: 0.6;">
                </div>

                <div class="form-group" style="margin-bottom: 35px;">
                    <label class="meta-label" style="display:block; margin-bottom: 8px;">Posisi Yang Dilamar *</label>
                    <select id="cand_bagian" class="meta-select" required style="font-size: 15px; padding: 6px 0;">
                        <option value="">-- Pilih Posisi --</option>
                        <?php foreach ($bagian_options as $bo): ?>
                            <option value="<?php echo htmlspecialchars($bo); ?>"><?php echo htmlspecialchars($bo); ?></option>
                        <?php endforeach; ?>
                        <option value="Lainnya">Lainnya / Posisi Baru</option>
                    </select>
                </div>

                <!-- Custom input for Other department if selected -->
                <div class="form-group" id="other_bagian_group" style="margin-bottom: 35px; display: none;">
                    <label class="meta-label" style="display:block; margin-bottom: 8px;">Tuliskan Nama Posisi *</label>
                    <input type="text" id="cand_bagian_other" class="meta-input" placeholder="Tuliskan nama posisi" style="font-size: 15px; padding: 6px 0;">
                </div>

                <div style="text-align: right;">
                    <button type="button" class="btn-apple" onclick="goToStep2()">Mulai Pengisian &nbsp;<i class="fa fa-arrow-right"></i></button>
                </div>
            </div>

            <form method="post" id="tetramapForm" style="display: none;">
                <!-- Sticky Progress Indicator -->
                <div class="progress-bar-container">
                    <div class="container" style="max-width: 860px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 700; font-size: 13.5px;"><i class="fa fa-pencil"></i> &nbsp;TetraMap Instrument Progress</span>
                            <span id="progress-text" style="font-weight: 700; font-size: 13.5px; color: #aeaeb2;">0 dari 9 Baris Selesai</span>
                        </div>
                        <div class="progress-bar-custom">
                            <div class="progress-fill" id="progress-fill"></div>
                        </div>
                    </div>
                </div>

                <div class="test-container" style="margin-top: 0;">
                    <!-- Hidden inputs populated from Step 1 -->
                    <input type="hidden" name="nama" id="form_nama">
                    <input type="hidden" name="tgl_lahir" id="form_tgl_lahir">
                    <input type="hidden" name="tgl_test" id="form_tgl_test">
                    <input type="hidden" name="bagian" id="form_bagian">

                    <h2 class="tetramap-title">TETRAMAP INSTRUMENT</h2>

                    <!-- Identitas Underline display -->
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">Nama</td>
                            <td style="width: 10px;">:</td>
                            <td class="meta-underline" id="display_nama">-</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Posisi Yang Dilamar</td>
                            <td>:</td>
                            <td class="meta-underline" id="display_bagian">-</td>
                        </tr>
                    </table>

                    <div class="instruction-box">
                        Urutkan Skor dari 1 sampai dengan 4. Skor 4 untuk yang paling menggambarkan Anda hingga skor 1 untuk yang paling bukan menggambarkan diri Anda.
                    </div>

                    <div class="example-box">
                        <span style="font-weight: 700; font-size: 13px; color: #ffffff; text-transform: uppercase; letter-spacing: 0.5px;">Contoh:</span>
                        <div class="example-grid">
                            <div class="example-item"><strong>1</strong> Sensitif</div>
                            <div class="example-item"><strong>4</strong> Mandiri</div>
                            <div class="example-item"><strong>2</strong> Pemalu</div>
                            <div class="example-item"><strong>3</strong> Suka bergaul</div>
                        </div>
                    </div>

                    <h5 style="font-weight: 700; letter-spacing: 0.5px; margin-bottom: 20px; color: #aeaeb2; text-transform: uppercase; font-size: 12px;">Selamat mengerjakan!</h5>

                    <!-- The 9 Rows Grid exactly matching the user's layout but fully responsive -->
                    <div style="margin-bottom: 30px;">
                        <?php foreach ($disc_rows as $idx => $row_words): ?>
                            <div class="grid-row" id="row-container-<?php echo $idx; ?>">
                                <?php foreach ($row_words as $col_idx => $word): ?>
                                    <div class="grid-cell" id="cell-<?php echo $idx; ?>-<?php echo $col_idx; ?>">
                                        <span class="word-label"><?php echo $word; ?></span>
                                        <select name="row_<?php echo $idx; ?>_col_<?php echo $col_idx; ?>" 
                                                class="grid-select rank-select-row-<?php echo $idx; ?>" 
                                                onchange="validateRow(<?php echo $idx; ?>, <?php echo $col_idx; ?>)" required>
                                            <option value="">-</option>
                                            <option value="4">4</option>
                                            <option value="3">3</option>
                                            <option value="2">2</option>
                                            <option value="1">1</option>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Actions -->
                    <div style="display: flex; justify-content: flex-end; align-items: center; margin-top: 40px; border-top: 1px solid #2c2c2e; padding-top: 25px; flex-wrap: wrap; gap: 10px;">
                        <button type="submit" name="submit_test" id="submitBtn" class="btn-apple" disabled>Kirim Jawaban Tes &nbsp;<i class="fa fa-paper-plane"></i></button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- Script validation logic -->
    <script>
        // Toggle Other position input
        document.getElementById('cand_bagian').addEventListener('change', function() {
            const otherGroup = document.getElementById('other_bagian_group');
            if (this.value === 'Lainnya') {
                otherGroup.style.display = 'block';
                document.getElementById('cand_bagian_other').required = true;
            } else {
                otherGroup.style.display = 'none';
                document.getElementById('cand_bagian_other').required = false;
            }
        });

        function goToStep2() {
            const nama = document.getElementById('cand_nama').value.trim();
            const tgl_lahir = document.getElementById('cand_tgl_lahir').value;
            const tgl_test = document.getElementById('cand_tgl_test').value;
            let bagian = document.getElementById('cand_bagian').value;

            if (bagian === 'Lainnya') {
                bagian = document.getElementById('cand_bagian_other').value.trim();
            }

            if (!nama || !tgl_lahir || !bagian) {
                alert('Silakan lengkapi seluruh kolom identitas diri terlebih dahulu!');
                return;
            }

            // Transfer details to form
            document.getElementById('form_nama').value = nama;
            document.getElementById('form_tgl_lahir').value = tgl_lahir;
            document.getElementById('form_tgl_test').value = tgl_test;
            document.getElementById('form_bagian').value = bagian;

            // Set static display values
            document.getElementById('display_nama').innerText = nama;
            document.getElementById('display_bagian').innerText = bagian;

            // Hide navbar back link so they cannot exit the test
            const backLink = document.getElementById('navbar-back-link');
            if (backLink) {
                backLink.style.display = 'none';
            }

            // Animate transition
            document.getElementById('step-1-container').style.display = 'none';
            document.getElementById('tetramapForm').style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function updateDropdownOptions(rowIdx) {
            const selects = document.querySelectorAll(`.rank-select-row-${rowIdx}`);
            
            // Get currently chosen values in this row
            const chosen = [];
            selects.forEach(sel => {
                if (sel.value) {
                    chosen.push(sel.value);
                }
            });

            // Update options for each select in the row
            selects.forEach(sel => {
                const currentVal = sel.value;
                const options = sel.querySelectorAll('option');
                
                options.forEach(opt => {
                    const optVal = opt.value;
                    if (!optVal) return; // Skip placeholder

                    // Disable/hide if chosen by ANOTHER dropdown in this row
                    if (chosen.includes(optVal) && optVal !== currentVal) {
                        opt.disabled = true;
                        opt.style.display = 'none';
                    } else {
                        opt.disabled = false;
                        opt.style.display = 'block';
                    }
                });
            });
        }

        // Validate individual rows for uniqueness (TetraMap requires unique 1,2,3,4 ranking per row)
        function validateRow(rowIdx, colIdx) {
            // Dynamically disable chosen values for other dropdowns in the same row
            updateDropdownOptions(rowIdx);

            const selects = document.querySelectorAll(`.rank-select-row-${rowIdx}`);
            const rowCells = [
                document.getElementById(`cell-${rowIdx}-0`),
                document.getElementById(`cell-${rowIdx}-1`),
                document.getElementById(`cell-${rowIdx}-2`),
                document.getElementById(`cell-${rowIdx}-3`)
            ];

            let values = [];
            let hasEmpty = false;

            selects.forEach(s => {
                if (!s.value) {
                    hasEmpty = true;
                } else {
                    values.push(parseInt(s.value));
                }
            });

            // If empty, clean status
            if (hasEmpty) {
                rowCells.forEach(cell => {
                    cell.classList.remove('active', 'error');
                });
                updateOverallProgress();
                return;
            }

            // Check unique values
            const uniqueValues = new Set(values);
            if (uniqueValues.size < 4) {
                rowCells.forEach(cell => {
                    cell.classList.remove('active');
                    cell.classList.add('error');
                });
            } else {
                rowCells.forEach(cell => {
                    cell.classList.remove('error');
                    cell.classList.add('active');
                });
            }

            updateOverallProgress();
        }

        function updateOverallProgress() {
            let validRowsCount = 0;
            let totalRows = 9;

            for (let i = 0; i < totalRows; i++) {
                const selects = document.querySelectorAll(`.rank-select-row-${i}`);
                let values = [];
                let hasEmpty = false;

                selects.forEach(s => {
                    if (!s.value) hasEmpty = true;
                    else values.push(s.value);
                });

                const uniqueValues = new Set(values);
                if (!hasEmpty && uniqueValues.size === 4) {
                    validRowsCount++;
                }
            }

            // Update Progress Bar
            const percent = (validRowsCount / totalRows) * 100;
            document.getElementById('progress-fill').style.width = `${percent}%`;
            document.getElementById('progress-text').innerText = `${validRowsCount} dari ${totalRows} Baris Selesai`;

            // Enable submit if all rows are valid
            const submitBtn = document.getElementById('submitBtn');
            if (validRowsCount === totalRows) {
                submitBtn.disabled = false;
                submitBtn.style.backgroundColor = '#ffffff';
                submitBtn.style.color = '#121212';
                submitBtn.style.boxShadow = "0 8px 24px rgba(255, 255, 255, 0.25)";
            } else {
                submitBtn.disabled = true;
                submitBtn.style.boxShadow = "none";
            }
        }
    </script>

</body>
</html>
