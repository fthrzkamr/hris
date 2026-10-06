<?php
require_once 'sess_check.php';
require_once 'dist/config/koneksi.php';
require_once 'vendorpdf/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID Calon Karyawan tidak ditemukan.");
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM test_calon_karyawan WHERE id = $id";
$query = mysqli_query($conn, $sql);
$data = mysqli_fetch_array($query);

if (!$data) {
    die("Data hasil test tidak ditemukan.");
}

// Map scores: earth=D, fire=I, air=C, water=S
$scores = [
    'Dominance (D)'         => intval($data['skor_earth']),
    'Influence (I)'         => intval($data['skor_fire']),
    'Conscientiousness (C)' => intval($data['skor_air']),
    'Steadiness (S)'        => intval($data['skor_water'])
];

// Sort scores to find the dominant type
arsort($scores);
$dominant = key($scores);
$dominant_score = current($scores);

// Interpretations
$interpretations = [
    'Dominance (D)' => [
        'title' => 'DOMINANCE (D) — Tegas & Berorientasi Hasil',
        'color' => '#2e7d32',
        'desc' => 'Calon karyawan dengan tipe Dominance cenderung mandiri, tegas, berorientasi pada hasil, kompetitif, dan berani menghadapi tantangan. Mereka menyukai kendali atas situasi dan berfokus pada penyelesaian tugas secara cepat dan efisien.',
        'strengths' => 'Pengambil keputusan yang cepat, berani mengambil risiko, fokus pada tujuan akhir, mandiri, dan bermotivasi tinggi.',
        'weaknesses' => 'Cenderung tidak sabar, kurang memperhatikan detail kecil, kurang sensitif terhadap perasaan orang lain, dan skeptis.'
    ],
    'Influence (I)' => [
        'title' => 'INFLUENCE (I) — Antusias & Persuasif',
        'color' => '#c62828',
        'desc' => 'Calon karyawan dengan tipe Influence sangat antusias, optimis, ramah, persuasif, ekspresif, dan berorientasi pada hubungan antar-manusia. Mereka senang berkolaborasi, membangun jaringan, dan memotivasi orang lain.',
        'strengths' => 'Komunikator yang sangat baik, pandai membangun hubungan, kreatif, optimis, dan mampu memotivasi tim.',
        'weaknesses' => 'Cenderung kurang teratur, mudah kehilangan fokus pada detail, kesulitan mengelola waktu, dan cenderung menghindari konflik.'
    ],
    'Conscientiousness (C)' => [
        'title' => 'CONSCIENTIOUSNESS (C) — Teliti & Analitis',
        'color' => '#f9a825',
        'desc' => 'Calon karyawan dengan tipe Conscientiousness sangat teliti, analitis, sistematis, akurat, dan berorientasi pada kualitas. Mereka sangat patuh pada peraturan, mengutamakan data dan fakta, serta bekerja secara terstruktur.',
        'strengths' => 'Sangat akurat dan detail, analitis, logis, disiplin tinggi, dan berkomitmen pada standar kualitas kerja.',
        'weaknesses' => 'Cenderung perfeksionis berlebihan, lambat dalam mengambil keputusan karena analisis berlebih (analysis paralysis), dan kaku terhadap perubahan mendadak.'
    ],
    'Steadiness (S)' => [
        'title' => 'STEADINESS (S) — Sabar & Mendukung',
        'color' => '#1565c0',
        'desc' => 'Calon karyawan dengan tipe Steadiness adalah pribadi yang tenang, sabar, loyal, kooperatif, pendengar yang baik, dan sangat mendukung rekan kerja. Mereka menyukai stabilitas dan keharmonisan lingkungan kerja.',
        'strengths' => 'Pemain tim yang sangat baik, loyal, sabar, andal, konsisten, dan mampu menstabilkan suasana kerja.',
        'weaknesses' => 'Cenderung lambat beradaptasi dengan perubahan, kesulitan menolak permintaan (sungkan), pasif-agresif saat tertekan, dan menghindari konfrontasi.'
    ]
];

$interp = $interpretations[$dominant] ?? $interpretations['Dominance (D)'];

$maxScore = 36;
$earth_pct = round(($data['skor_earth'] / $maxScore) * 100);
$fire_pct = round(($data['skor_fire'] / $maxScore) * 100);
$air_pct = round(($data['skor_air'] / $maxScore) * 100);
$water_pct = round(($data['skor_water'] / $maxScore) * 100);

// Generate HTML Content for PDF
$html = '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil Test DISC - ' . htmlspecialchars($data['nama']) . '</title>
    <style>
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            color: #333333;
            line-height: 1.4;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #007aff;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            color: #1c1c1e;
            text-transform: uppercase;
            margin: 0;
            padding: 0;
        }
        .header-subtitle {
            font-size: 10px;
            color: #666;
            margin-top: 4px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 6px 10px;
            background: #f5f5f7;
            border: 1px solid #e3e3e8;
        }
        .meta-label {
            font-weight: bold;
            color: #555;
            width: 25%;
            font-size: 10px;
            text-transform: uppercase;
        }
        .meta-value {
            color: #1c1c1e;
            font-weight: bold;
            width: 25%;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1c1c1e;
            border-bottom: 1px solid #e3e3e8;
            padding-bottom: 5px;
            margin-top: 20px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        .chart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .chart-table th, .chart-table td {
            border: 1px solid #e3e3e8;
            padding: 8px 10px;
        }
        .chart-table th {
            background-color: #f5f5f7;
            font-weight: bold;
            color: #1c1c1e;
            text-align: left;
        }
        .progress-container {
            background-color: #e3e3e8;
            border-radius: 4px;
            width: 100%;
            height: 12px;
        }
        .progress-bar {
            height: 12px;
            border-radius: 4px;
        }
        .score-badge {
            font-weight: bold;
            font-size: 11px;
        }
        .interpretation-box {
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            color: #333;
            background-color: #f8f9fa;
            border-left: 5px solid ' . $interp['color'] . ';
        }
        .interp-title {
            font-size: 12px;
            font-weight: bold;
            margin-top: 0;
            margin-bottom: 10px;
            color: ' . $interp['color'] . ';
        }
        .interp-text {
            font-size: 11px;
            line-height: 1.5;
            margin-bottom: 10px;
        }
        .details-list {
            margin-top: 8px;
            padding-left: 15px;
        }
        .details-list li {
            margin-bottom: 4px;
        }
        .footer-table {
            width: 100%;
            margin-top: 40px;
            font-size: 10px;
        }
        .signature-title {
            font-weight: bold;
            margin-bottom: 50px;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <div class="header-title">Hasil Instrumen DISC Personality</div>
                <div class="header-subtitle">Sistem Rekrutmen &amp; Seleksi Karyawan DF Group</div>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: middle;">
                <span style="font-size: 14px; font-weight: bold; color: #007aff;">DF GROUP</span>
            </td>
        </tr>
    </table>

    <!-- Meta Information -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Nama Calon</td>
            <td class="meta-value">' . htmlspecialchars($data['nama']) . '</td>
            <td class="meta-label">Tanggal Lahir</td>
            <td class="meta-value">' . date('d-m-Y', strtotime($data['tgl_lahir'])) . '</td>
        </tr>
        <tr>
            <td class="meta-label">Posisi Dilamar</td>
            <td class="meta-value">' . htmlspecialchars($data['bagian']) . '</td>
            <td class="meta-label">Tanggal Test</td>
            <td class="meta-value">' . date('d-m-Y', strtotime($data['tgl_test'])) . '</td>
        </tr>
    </table>

    <!-- DISC Profile Scores & Charts -->
    <div class="section-title">Komposisi Skor Kepribadian DISC</div>
    <table class="chart-table">
        <thead>
            <tr>
                <th style="width: 25%;">Aspek Kepribadian</th>
                <th style="width: 12%; text-align: center;">Skor (Max 36)</th>
                <th style="width: 50%;">Grafik Persentase</th>
                <th style="width: 13%; text-align: center;">Persentase</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong style="color: #2e7d32;">Dominance (D)</strong></td>
                <td style="text-align: center;" class="score-badge">' . $data['skor_earth'] . '</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-bar" style="background-color: #2e7d32; width: ' . $earth_pct . '%;"></div>
                    </div>
                </td>
                <td style="text-align: center; font-weight: bold;">' . $earth_pct . '%</td>
            </tr>
            <tr>
                <td><strong style="color: #c62828;">Influence (I)</strong></td>
                <td style="text-align: center;" class="score-badge">' . $data['skor_fire'] . '</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-bar" style="background-color: #c62828; width: ' . $fire_pct . '%;"></div>
                    </div>
                </td>
                <td style="text-align: center; font-weight: bold;">' . $fire_pct . '%</td>
            </tr>
            <tr>
                <td><strong style="color: #f9a825;">Conscientiousness (C)</strong></td>
                <td style="text-align: center;" class="score-badge">' . $data['skor_air'] . '</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-bar" style="background-color: #f9a825; width: ' . $air_pct . '%;"></div>
                    </div>
                </td>
                <td style="text-align: center; font-weight: bold;">' . $air_pct . '%</td>
            </tr>
            <tr>
                <td><strong style="color: #1565c0;">Steadiness (S)</strong></td>
                <td style="text-align: center;" class="score-badge">' . $data['skor_water'] . '</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-bar" style="background-color: #1565c0; width: ' . $water_pct . '%;"></div>
                    </div>
                </td>
                <td style="text-align: center; font-weight: bold;">' . $water_pct . '%</td>
            </tr>
        </tbody>
    </table>

    <!-- Interpretation Box -->
    <div class="section-title">Interpretasi Kepribadian Dominan</div>
    <div class="interpretation-box">
        <div class="interp-title">' . $interp['title'] . ' (Skor: ' . $dominant_score . ')</div>
        <div class="interp-text">' . $interp['desc'] . '</div>
        <div style="font-weight: bold; margin-top: 10px; font-size: 10px; text-transform: uppercase;">Kekuatan Utama:</div>
        <div class="interp-text" style="color: #2e7d32; font-weight: bold;">' . $interp['strengths'] . '</div>
        <div style="font-weight: bold; margin-top: 10px; font-size: 10px; text-transform: uppercase;">Area Pengembangan / Kelemahan:</div>
        <div class="interp-text" style="color: #c62828; font-weight: bold;">' . $interp['weaknesses'] . '</div>
    </div>

    <!-- Signatures -->
    <table class="footer-table">
        <tr>
            <td style="width: 50%;">
                <div>Dicetak secara otomatis oleh HRIS DF Group</div>
                <div>Tanggal Cetak: ' . date('d-m-Y H:i') . '</div>
            </td>
            <td style="width: 50%; text-align: right;">
                <div class="signature-title">Mengetahui,</div>
                <div style="margin-top: 60px; font-weight: bold; text-decoration: underline;">Tim HRD &amp; Rekrutmen</div>
                <div>DF Group Indonesia</div>
            </td>
        </tr>
    </table>

</body>
</html>
';

// Setup Dompdf options
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output inline to browser
$filename = "Hasil_DISC_" . str_replace(" ", "_", $data['nama']) . ".pdf";
$dompdf->stream($filename, array("Attachment" => false));
?>
