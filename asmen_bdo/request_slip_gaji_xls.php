<?php
include("sess_check.php");
include("dist/function/format_rupiah.php");

// Ambil NPP user yang login (KEAMANAN PENTING!)
$npp_login = $_SESSION['asmen_bdo'];
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
$sql = "SELECT laporan_potongan.*, employee.nama_emp, employee.gaji_pokok, employee.tunj_jabatan, employee.tunj_kinerja, employee.tunj_transport
        FROM laporan_potongan 
        JOIN employee ON laporan_potongan.npp = employee.npp
        WHERE laporan_potongan.npp = ? AND YEAR(laporan_potongan.tanggal) = ? 
              AND MONTH(laporan_potongan.tanggal) = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sss", $npp, $tahun, $bulan);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    echo "<script>
        alert('Data gaji tidak ditemukan untuk periode yang dipilih!');
        window.close();
    </script>";
    exit;
}

$data = mysqli_fetch_assoc($result);

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

// Bulan dalam bahasa Indonesia
$bulan_indo = [
    "01" => "Januari", "02" => "Februari", "03" => "Maret",
    "04" => "April", "05" => "Mei", "06" => "Juni",
    "07" => "Juli", "08" => "Agustus", "09" => "September",
    "10" => "Oktober", "11" => "November", "12" => "Desember"
];

$periode = $bulan_indo[$bulan] . " " . $tahun;

// Set header untuk download Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Slip_Gaji_" . date('Y-m-d_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .total-row {
            background-color: #e8e8e8;
            font-weight: bold;
        }
        .gaji-bersih {
            background-color: #d4edda;
            font-weight: bold;
            font-size: 12pt;
        }
        @media print {
            body {
                margin: 1cm;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>SLIP GAJI KARYAWAN</h2>
        <h3>PT. DUA FARMA GROUP</h3>
        <h4>Periode: <?php echo $periode; ?></h4>
    </div>

    <table>
        <tr>
            <th>NPP</th>
            <td><?php echo $data['npp']; ?></td>
            <th>Nama Karyawan</th>
            <td><?php echo $data['nama_emp']; ?></td>
        </tr>
    </table>
    <br>

    <table>
        <thead>
            <tr>
                <th colspan="2">PENDAPATAN</th>
                <th colspan="2">POTONGAN</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Gaji Pokok</td>
                <td class="text-right"><?php echo format_rupiah($data['gaji_pokok']); ?></td>
                <td>Potongan BPJS Kesehatan</td>
                <td class="text-right"><?php echo format_rupiah($p_bpjs_kesehatan); ?></td>
            </tr>
            <tr>
                <td>Tunjangan Transportasi</td>
                <td class="text-right"><?php echo format_rupiah($data['tunj_transport']); ?></td>
                <td>Potongan BPJS Ketenagakerjaan</td>
                <td class="text-right"><?php echo format_rupiah($p_bpjs_tk); ?></td>
            </tr>
            <tr>
                <td>Tunjangan Jabatan</td>
                <td class="text-right"><?php echo format_rupiah($data['tunj_jabatan']); ?></td>
                <td>Potongan Pajak PPh 21</td>
                <td class="text-right"><?php echo format_rupiah($p_pajak_pph21); ?></td>
            </tr>
            <tr>
                <td>Tunjangan Kinerja</td>
                <td class="text-right"><?php echo format_rupiah($data['tunj_kinerja']); ?></td>
                <td>Potongan Keterlambatan</td>
                <td class="text-right"><?php echo format_rupiah($data['p_keterlambatan']); ?></td>
            </tr>
            <tr>
                <td>Tunjangan BPJS Kesehatan</td>
                <td class="text-right"><?php echo format_rupiah($tunj_bpjs_kesehatan); ?></td>
                <td>Angsuran Pinjaman Kantor</td>
                <td class="text-right"><?php echo format_rupiah($data['p_pinjaman']); ?></td>
            </tr>
            <tr>
                <td>Tunjangan BPJS Ketenagakerjaan</td>
                <td class="text-right"><?php echo format_rupiah($tunj_bpjs_tk); ?></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Tunjangan Pajak PPh 21</td>
                <td class="text-right"><?php echo format_rupiah($tunj_pajak_pph21); ?></td>
                <td></td>
                <td></td>
            </tr>
            <tr class="total-row">
                <td>Total Pendapatan</td>
                <td class="text-right"><?php echo format_rupiah($total_pendapatan); ?></td>
                <td>Total Potongan</td>
                <td class="text-right"><?php echo format_rupiah($total_potongan); ?></td>
            </tr>
            <tr class="gaji-bersih">
                <td colspan="4" class="text-center">
                    GAJI BERSIH: <?php echo format_rupiah($gaji_bersih); ?>
                </td>
            </tr>
        </tbody>
    </table>

    <br>
    <p style="font-size: 10pt; text-align: center; color: #666;">
        Slip gaji ini dicetak otomatis dari sistem HRIS PT. Dua Farma Group<br>
        Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?>
    </p>
</body>
</html>
