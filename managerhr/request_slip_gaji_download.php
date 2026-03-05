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

$id_request = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_request == 0) {
    echo "<script>alert('ID Request tidak valid!'); window.close();</script>";
    exit;
}

// Ambil data request slip gaji
$sql_check = "SELECT * FROM request_slip_gaji 
              WHERE id_request = ? AND status = 'approved'";
$stmt_check = mysqli_prepare($conn, $sql_check);
mysqli_stmt_bind_param($stmt_check, "i", $id_request);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) == 0) {
    echo "<script>
        alert('Request tidak ditemukan atau belum disetujui.');
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
            preg_match('/Rp ([\d.,]+)/', $part, $matches);
            if (isset($matches[1])) $tunj_bpjs_kesehatan = floatval(str_replace(['.', ','], '', $matches[1]));
        } elseif (strpos($part, 'Tunj.BPJS TK:') !== false) {
            preg_match('/Rp ([\d.,]+)/', $part, $matches);
            if (isset($matches[1])) $tunj_bpjs_tk = floatval(str_replace(['.', ','], '', $matches[1]));
        } elseif (strpos($part, 'Tunj.Pajak:') !== false) {
            preg_match('/Rp ([\d.,]+)/', $part, $matches);
            if (isset($matches[1])) $tunj_pajak_pph21 = floatval(str_replace(['.', ','], '', $matches[1]));
        } elseif (strpos($part, 'P.BPJS Kes:') !== false) {
            preg_match('/Rp ([\d.,]+)/', $part, $matches);
            if (isset($matches[1])) $p_bpjs_kesehatan = floatval(str_replace(['.', ','], '', $matches[1]));
        } elseif (strpos($part, 'P.BPJS TK:') !== false) {
            preg_match('/Rp ([\d.,]+)/', $part, $matches);
            if (isset($matches[1])) $p_bpjs_tk = floatval(str_replace(['.', ','], '', $matches[1]));
        } elseif (strpos($part, 'P.Pajak:') !== false) {
            preg_match('/Rp ([\d.,]+)/', $part, $matches);
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
            padding: 20px;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 2px solid #000;
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
            font-size: 16px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px;
            font-size: 12px;
        }
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .salary-table th {
            background-color: #f0f0f0;
            padding: 10px;
            border: 1px solid #000;
            text-align: left;
            font-weight: bold;
            font-size: 13px;
        }
        .salary-table td {
            padding: 8px 10px;
            border: 1px solid #000;
            font-size: 12px;
        }
        .salary-table td.label {
            width: 45%;
        }
        .salary-table td.value {
            width: 20%;
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f8f8;
        }
        .gaji-bersih-row {
            background-color: #d4edda;
            font-weight: bold;
            font-size: 13px;
        }
        .note {
            margin-top: 20px;
            font-size: 11px;
            font-style: italic;
            text-align: center;
            color: #666;
        }
    </style>

    <div class='container'>
        <div class='header'>
            <h2>SLIP GAJI KARYAWAN</h2>
            <h3>PT. DUA FARMA GROUP</h3>
            <p style='margin: 5px 0; font-size: 13px;'>Periode: {$periode_bulan}</p>
        </div>

        <table class='info-table'>
            <tr>
                <td style='width: 15%;'><strong>Nama</strong></td>
                <td style='width: 35%;'>: {$data['nama_emp']}</td>
                <td style='width: 15%;'><strong>NPP</strong></td>
                <td style='width: 35%;'>: {$data['npp']}</td>
            </tr>
        </table>

        <table class='salary-table'>
            <thead>
                <tr>
                    <th colspan='2'>Pendapatan</th>
                    <th colspan='2'>Potongan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class='label'>Gaji Pokok</td>
                    <td class='value'>" . format_rupiah($data['gaji_pokok']) . "</td>
                    <td class='label'>Potongan BPJS Kesehatan</td>
                    <td class='value'>" . format_rupiah($p_bpjs_kesehatan) . "</td>
                </tr>
                <tr>
                    <td class='label'>Tunjangan Transportasi</td>
                    <td class='value'>" . format_rupiah($data['tunj_transport']) . "</td>
                    <td class='label'>Potongan BPJS TK</td>
                    <td class='value'>" . format_rupiah($p_bpjs_tk) . "</td>
                </tr>
                <tr>
                    <td class='label'>Tunjangan Jabatan</td>
                    <td class='value'>" . format_rupiah($data['tunj_jabatan']) . "</td>
                    <td class='label'>Potongan Pajak Pph 21</td>
                    <td class='value'>" . format_rupiah($p_pajak_pph21) . "</td>
                </tr>
                <tr>
                    <td class='label'>Tunjangan Kinerja</td>
                    <td class='value'>" . format_rupiah($data['tunj_kinerja']) . "</td>
                    <td class='label'>Potongan Keterlambatan</td>
                    <td class='value'>" . format_rupiah($data['p_keterlambatan']) . "</td>
                </tr>
                <tr>
                    <td class='label'>Tunjangan BPJS Kesehatan</td>
                    <td class='value'>" . format_rupiah($tunj_bpjs_kesehatan) . "</td>
                    <td class='label'>Angsuran Pinjaman Kantor</td>
                    <td class='value'>" . format_rupiah($data['p_pinjaman']) . "</td>
                </tr>
                <tr>
                    <td class='label'>Tunjangan BPJS TK</td>
                    <td class='value'>" . format_rupiah($tunj_bpjs_tk) . "</td>
                    <td class='label'></td>
                    <td class='value'></td>
                </tr>
                <tr>
                    <td class='label'>Tunjangan Pajak Pph 21</td>
                    <td class='value'>" . format_rupiah($tunj_pajak_pph21) . "</td>
                    <td class='label'></td>
                    <td class='value'></td>
                </tr>
                <tr class='total-row'>
                    <td class='label'>Total Pendapatan</td>
                    <td class='value'>" . format_rupiah($total_pendapatan) . "</td>
                    <td class='label'>Total Potongan</td>
                    <td class='value'>" . format_rupiah($total_potongan) . "</td>
                </tr>
                <tr class='gaji-bersih-row'>
                    <td colspan='3' style='text-align: center;'>Gaji Bersih</td>
                    <td class='value'>" . format_rupiah($gaji_bersih) . "</td>
                </tr>
            </tbody>
        </table>

        <p class='note'>
            Catatan: Slip gaji ini dianggap sah tanpa memerlukan tanda tangan.<br>
            Dokumen diunduh pada: " . date('d/m/Y H:i:s') . " dari Sistem HRIS PT. Dua Farma Group
        </p>
    </div>
";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "Slip_Gaji_{$data['npp']}_{$tahun}_{$bulan}.pdf";
$dompdf->stream($filename, ["Attachment" => true]);
?>
