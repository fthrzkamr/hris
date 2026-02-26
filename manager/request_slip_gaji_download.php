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

// Ambil NPP user yang login
$npp_login = $_SESSION['manager'];
$id_request = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_request == 0) {
    echo "<script>alert('ID Request tidak valid!'); window.close();</script>";
    exit;
}

// VALIDASI KEAMANAN: Cek apakah request ini milik user yang login
$sql_check = "SELECT * FROM request_slip_gaji 
              WHERE id_request = ? AND npp = ? AND status = 'approved'";
$stmt_check = mysqli_prepare($conn, $sql_check);
mysqli_stmt_bind_param($stmt_check, "is", $id_request, $npp_login);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) == 0) {
    echo "<script>
        alert('Akses ditolak! Request tidak ditemukan atau belum disetujui.');
        window.close();
    </script>";
    exit;
}

$request_data = mysqli_fetch_assoc($result_check);
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
mysqli_stmt_bind_param($stmt, "sss", $npp_login, $tahun, $bulan);
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

$html = "
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            padding-top: 30px;
        }
        .container {
            width: 100%;
            max-width: 700px;
            padding: 20px;
            border: 1px solid #000;
            box-sizing: border-box;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            padding: 8px;
            border: 1px solid #000;
            text-align: left;
            font-size: 12px;
            word-wrap: break-word;
            white-space: normal;
        }
        .text-center {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
        }
        .note {
            margin-top: 20px;
            font-size: 12px;
            font-style: italic;
            text-align: center;
            color: red;
        }
        .highlight {
            background-color: #d4edda;
            text-align: center;
            font-weight: bold;
        }
        .wrap-text {
            word-wrap: break-word;
            white-space: normal;
            max-width: 200px;
        }
        .header-info {
            margin-bottom: 15px;
            font-size: 11px;
            color: #666;
        }
    </style>

    <div class='container'>
        <h2 class='text-center'>SLIP GAJI KARYAWAN</h2>
        <h3 class='text-center' style='font-size: 16px; margin-top: 5px;'>PT. DUA FARMA GROUP</h3>
        <h6 class='text-center'>Periode Bulan: {$periode_bulan}</h6>
        <div class='header-info'>
            <em>Dokumen ini dihasilkan dari sistem HRIS dan telah diverifikasi</em>
        </div>
        <br />
        <table>
            <tr>
                <th>Nama</th>
                <td>{$data['nama_emp']}</td>
                <th>NPP</th>
                <td>{$data['npp']}</td>
            </tr>
            <tr>
                <th>Gaji Pokok</th>
                <td>" . format_rupiah($data['gaji_pokok']) . "</td>
                <th>Potongan Keterlambatan</th>
                <td>" . format_rupiah($data['p_keterlambatan']) . "</td>
            </tr>
            <tr>
                <th>Tunjangan Tetap - Jabatan</th>
                <td>" . format_rupiah($data['tunj_jabatan']) . "</td>
                <th>Potongan Pinjaman</th>
                <td>" . format_rupiah($data['p_pinjaman']) . "</td>
            </tr>
            <tr>
                <th>Tunjangan Tidak Tetap - Kinerja</th>
                <td>" . format_rupiah($data['tunj_kinerja']) . "</td>
                <th>Potongan Lain-Lain</th>
                <td>" . format_rupiah($data['p_lain']) . "</td>
            </tr>
            <tr>
                <th>Tunjangan Tidak Tetap - Transport</th>
                <td>" . format_rupiah($data['tunj_transport']) . "</td>
                <th>Ket. Potongan Lain</th>
                <td class='wrap-text'>" . htmlspecialchars($data['desc_lain']) . "</td>
            </tr>
            <tr>
                <th>Total Gaji</th>
                <td>" . format_rupiah($data['gaji_pokok'] + $data['tunj_jabatan'] + $data['tunj_transport'] + $data['tunj_kinerja']) . "</td>
                <th>Total Potongan</th>
                <td>" . format_rupiah($data['p_keterlambatan'] + $data['p_pinjaman'] + $data['p_lain']) . "</td>
            </tr>
            <tr class='highlight'>
                <td class='text-center' colspan='4'>Gaji Yang Diterima: " . format_rupiah($data['p_hasil']) . "</td>
            </tr>
        </table>

        <p class='note'>Catatan: Slip gaji ini dianggap sah tanpa memerlukan tanda tangan dari pejabat yang berwenang. <br>
        Dokumen diunduh pada: " . date('d/m/Y H:i:s') . "</p>
    </div>
";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "Slip_Gaji_{$data['npp']}_{$tahun}_{$bulan}.pdf";
$dompdf->stream($filename, ["Attachment" => true]);
?>
