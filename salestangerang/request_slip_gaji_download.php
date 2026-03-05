<?php
require '../vendorpdf/autoload.php';
include("sess_check.php");
include("dist/function/format_tanggal.php");
include("dist/function/format_rupiah.php");

use Dompdf\Dompdf;

function get_bulan_indo($tanggal) {
    $bulan = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    $bulan_inggris = date("F", strtotime($tanggal));
    return $bulan[$bulan_inggris];
}

// Ambil NPP user yang login (KEAMANAN PENTING!)
$npp_login = $_SESSION['salestangerang'];
$id_request = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_request == 0) {
    echo "<script>alert('ID Request tidak valid!'); window.close();</script>";
    exit;
}

// VALIDASI KEAMANAN: Hanya bisa download slip gaji milik sendiri
$sql_check = "SELECT * FROM request_slip_gaji 
              WHERE id_request = ? AND npp = ? AND status = 'approved'";
$stmt_check = mysqli_prepare($conn, $sql_check);
mysqli_stmt_bind_param($stmt_check, "is", $id_request, $npp_login);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) == 0) {
    echo "<script>
        alert('Akses ditolak! Anda hanya dapat download slip gaji milik Anda sendiri.');
        window.close();
    </script>";
    exit;
}

$request_data = mysqli_fetch_assoc($result_check);
$npp = $request_data['npp'];
$tahun = $request_data['tahun'];
$bulan = $request_data['bulan'];

// Ambil data gaji dari laporan_potongan
$sql = "SELECT laporan_potongan.*, employee.nama_emp, employee.gaji_pokok, employee.tunj_jabatan, 
               employee.tunj_kinerja, employee.tunj_transport
        FROM laporan_potongan 
        JOIN employee ON laporan_potongan.npp = employee.npp
        WHERE laporan_potongan.npp = ? AND YEAR(laporan_potongan.tanggal) = ? 
              AND MONTH(laporan_potongan.tanggal) = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sss", $npp, $tahun, $bulan);
mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($query) == 0) {
    echo "<script>
        alert('Data gaji tidak ditemukan untuk periode yang dipilih!');
        window.close();
    </script>";
    exit;
}

$data = mysqli_fetch_array($query);
$periode_bulan = get_bulan_indo($data['tanggal']) . " " . date("Y", strtotime($data['tanggal']));

// Parse desc_lain untuk mendapatkan nilai BPJS dan Pajak
$tunj_bpjs_kesehatan = 0;
$tunj_bpjs_tk = 0;
$tunj_pajak_pph21 = 0;
$p_bpjs_kesehatan = 0;
$p_bpjs_tk = 0;
$p_pajak_pph21 = 0;

if (!empty($data['desc_lain'])) {
    $desc_parts = explode('|', $data['desc_lain']);
    foreach ($desc_parts as $part) {
        $part = trim($part);
        if (strpos($part, 'Tunj.BPJS Kes:') !== false) {
            preg_match('/Rp\s*([\d.,]+)/', $part, $matches);
            if (isset($matches[1])) $tunj_bpjs_kesehatan = floatval(str_replace(['.', ','], '', $matches[1]));
        } elseif (strpos($part, 'Tunj.BPJS TK:') !== false) {
            preg_match('/Rp\s*([\d.,]+)/', $part, $matches);
            if (isset($matches[1])) $tunj_bpjs_tk = floatval(str_replace(['.', ','], '', $matches[1]));
        } elseif (strpos($part, 'Tunj.Pajak:') !== false) {
            preg_match('/Rp\s*([\d.,]+)/', $part, $matches);
            if (isset($matches[1])) $tunj_pajak_pph21 = floatval(str_replace(['.', ','], '', $matches[1]));
        } elseif (strpos($part, 'P.BPJS Kes:') !== false) {
            preg_match('/Rp\s*([\d.,]+)/', $part, $matches);
            if (isset($matches[1])) $p_bpjs_kesehatan = floatval(str_replace(['.', ','], '', $matches[1]));
        } elseif (strpos($part, 'P.BPJS TK:') !== false) {
            preg_match('/Rp\s*([\d.,]+)/', $part, $matches);
            if (isset($matches[1])) $p_bpjs_tk = floatval(str_replace(['.', ','], '', $matches[1]));
        } elseif (strpos($part, 'P.Pajak:') !== false) {
            preg_match('/Rp\s*([\d.,]+)/', $part, $matches);
            if (isset($matches[1])) $p_pajak_pph21 = floatval(str_replace(['.', ','], '', $matches[1]));
        }
    }
}

// Hitung total
$total_pendapatan = $data['gaji_pokok'] + $data['tunj_transport'] + $data['tunj_jabatan'] + 
                    $data['tunj_kinerja'] + $tunj_bpjs_kesehatan + $tunj_bpjs_tk + $tunj_pajak_pph21;
$total_potongan = $p_bpjs_kesehatan + $p_bpjs_tk + $p_pajak_pph21 + 
                  $data['p_keterlambatan'] + $data['p_pinjaman'];
$gaji_bersih = $total_pendapatan - $total_potongan;

$html = "
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
        }
        .header h3 {
            margin: 5px 0;
            font-size: 14px;
            font-weight: normal;
        }
        .info-box {
            border: 1px solid #333;
            padding: 10px;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            margin: 5px 0;
        }
        .info-label {
            width: 30%;
            font-weight: bold;
        }
        .info-value {
            width: 70%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            background-color: #e8e8e8;
            font-weight: bold;
        }
        .gaji-bersih-row {
            background-color: #d4edda;
            font-weight: bold;
            font-size: 12px;
        }
        .footer {
            margin-top: 20px;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
    </style>

    <div class='container'>
        <div class='header'>
            <h2>SLIP GAJI KARYAWAN</h2>
            <h3>PT. DUA FARMA GROUP</h3>
            <h3>Periode: {$periode_bulan}</h3>
        </div>

        <div class='info-box'>
            <div class='info-row'>
                <div class='info-label'>NPP</div>
                <div class='info-value'>: {$data['npp']}</div>
            </div>
            <div class='info-row'>
                <div class='info-label'>Nama Karyawan</div>
                <div class='info-value'>: {$data['nama_emp']}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width='50%'>PENDAPATAN</th>
                    <th width='50%'>POTONGAN</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <table style='border: none; width: 100%;'>
                            <tr style='border: none;'>
                                <td style='border: none; padding: 4px;'>Gaji Pokok</td>
                                <td style='border: none; padding: 4px; text-align: right;'>" . format_rupiah($data['gaji_pokok']) . "</td>
                            </tr>
                            <tr style='border: none;'>
                                <td style='border: none; padding: 4px;'>Tunjangan Transportasi</td>
                                <td style='border: none; padding: 4px; text-align: right;'>" . format_rupiah($data['tunj_transport']) . "</td>
                            </tr>
                            <tr style='border: none;'>
                                <td style='border: none; padding: 4px;'>Tunjangan Jabatan</td>
                                <td style='border: none; padding: 4px; text-align: right;'>" . format_rupiah($data['tunj_jabatan']) . "</td>
                            </tr>
                            <tr style='border: none;'>
                                <td style='border: none; padding: 4px;'>Tunjangan Kinerja</td>
                                <td style='border: none; padding: 4px; text-align: right;'>" . format_rupiah($data['tunj_kinerja']) . "</td>
                            </tr>
                            <tr style='border: none;'>
                                <td style='border: none; padding: 4px;'>Tunjangan BPJS Kesehatan</td>
                                <td style='border: none; padding: 4px; text-align: right;'>" . format_rupiah($tunj_bpjs_kesehatan) . "</td>
                            </tr>
                            <tr style='border: none;'>
                                <td style='border: none; padding: 4px;'>Tunjangan BPJS Ketenagakerjaan</td>
                                <td style='border: none; padding: 4px; text-align: right;'>" . format_rupiah($tunj_bpjs_tk) . "</td>
                            </tr>
                            <tr style='border: none;'>
                                <td style='border: none; padding: 4px;'>Tunjangan Pajak PPh 21</td>
                                <td style='border: none; padding: 4px; text-align: right;'>" . format_rupiah($tunj_pajak_pph21) . "</td>
                            </tr>
                        </table>
                    </td>
                    <td>
                        <table style='border: none; width: 100%;'>
                            <tr style='border: none;'>
                                <td style='border: none; padding: 4px;'>Potongan BPJS Kesehatan</td>
                                <td style='border: none; padding: 4px; text-align: right;'>" . format_rupiah($p_bpjs_kesehatan) . "</td>
                            </tr>
                            <tr style='border: none;'>
                                <td style='border: none; padding: 4px;'>Potongan BPJS Ketenagakerjaan</td>
                                <td style='border: none; padding: 4px; text-align: right;'>" . format_rupiah($p_bpjs_tk) . "</td>
                            </tr>
                            <tr style='border: none;'>
                                <td style='border: none; padding: 4px;'>Potongan Pajak PPh 21</td>
                                <td style='border: none; padding: 4px; text-align: right;'>" . format_rupiah($p_pajak_pph21) . "</td>
                            </tr>
                            <tr style='border: none;'>
                                <td style='border: none; padding: 4px;'>Potongan Keterlambatan</td>
                                <td style='border: none; padding: 4px; text-align: right;'>" . format_rupiah($data['p_keterlambatan']) . "</td>
                            </tr>
                            <tr style='border: none;'>
                                <td style='border: none; padding: 4px;'>Angsuran Pinjaman Kantor</td>
                                <td style='border: none; padding: 4px; text-align: right;'>" . format_rupiah($data['p_pinjaman']) . "</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr class='total-row'>
                    <td class='text-right'>Total Pendapatan: " . format_rupiah($total_pendapatan) . "</td>
                    <td class='text-right'>Total Potongan: " . format_rupiah($total_potongan) . "</td>
                </tr>
                <tr class='gaji-bersih-row'>
                    <td colspan='2' class='text-center'>GAJI BERSIH: " . format_rupiah($gaji_bersih) . "</td>
                </tr>
            </tbody>
        </table>

        <div class='footer'>
            <p>Slip gaji ini dicetak otomatis dari sistem HRIS PT. Dua Farma Group</p>
            <p>Dicetak pada: " . date('d/m/Y H:i:s') . "</p>
        </div>
    </div>
";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "Slip_Gaji_{$data['npp']}_{$tahun}_{$bulan}.pdf";
$dompdf->stream($filename, ["Attachment" => true]);
?>
