<?php
include("sess_check.php");
include("dist/function/format_rupiah.php");

// Set header untuk Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Slip_Gaji_" . date('Y-m-d_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

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
    die("Request tidak ditemukan atau belum disetujui.");
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
    die("Data gaji tidak ditemukan untuk periode yang dipilih!");
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
?>

<style>
    @media print {
        @page { margin: 1cm; }
    }
    table { 
        border-collapse: collapse; 
        width: 100%; 
        font-family: Arial, sans-serif;
    }
    th, td { 
        border: 1px solid #000; 
        padding: 8px; 
        text-align: left;
    }
    th { 
        background-color: #f0f0f0; 
        font-weight: bold;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .total-row { 
        background-color: #f8f8f8; 
        font-weight: bold;
    }
    .gaji-bersih-row { 
        background-color: #d4edda; 
        font-weight: bold;
    }
</style>

<h2 style="text-align: center;">SLIP GAJI KARYAWAN</h2>
<h3 style="text-align: center;">PT. DUA FARMA GROUP</h3>
<p style="text-align: center; margin: 5px 0;">Periode: <?php echo $periode_bulan; ?></p>
<br>

<table style="border: 0; margin-bottom: 15px;">
    <tr>
        <td style="border: 0; width: 15%;"><strong>Nama</strong></td>
        <td style="border: 0; width: 35%;">: <?php echo $data['nama_emp']; ?></td>
        <td style="border: 0; width: 15%;"><strong>NPP</strong></td>
        <td style="border: 0; width: 35%;">: <?php echo $data['npp']; ?></td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th colspan="2" class="text-center">Pendapatan</th>
            <th colspan="2" class="text-center">Potongan</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="width: 40%;">Gaji Pokok</td>
            <td style="width: 15%;" class="text-right"><?php echo number_format($data['gaji_pokok'], 0, ',', '.'); ?></td>
            <td style="width: 30%;">Potongan BPJS Kesehatan</td>
            <td style="width: 15%;" class="text-right"><?php echo number_format($p_bpjs_kesehatan, 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td>Tunjangan Transportasi</td>
            <td class="text-right"><?php echo number_format($data['tunj_transport'], 0, ',', '.'); ?></td>
            <td>Potongan BPJS TK</td>
            <td class="text-right"><?php echo number_format($p_bpjs_tk, 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td>Tunjangan Jabatan</td>
            <td class="text-right"><?php echo number_format($data['tunj_jabatan'], 0, ',', '.'); ?></td>
            <td>Potongan Pajak Pph 21</td>
            <td class="text-right"><?php echo number_format($p_pajak_pph21, 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td>Tunjangan Kinerja</td>
            <td class="text-right"><?php echo number_format($data['tunj_kinerja'], 0, ',', '.'); ?></td>
            <td>Potongan Keterlambatan</td>
            <td class="text-right"><?php echo number_format($data['p_keterlambatan'], 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td>Tunjangan BPJS Kesehatan</td>
            <td class="text-right"><?php echo number_format($tunj_bpjs_kesehatan, 0, ',', '.'); ?></td>
            <td>Angsuran Pinjaman Kantor</td>
            <td class="text-right"><?php echo number_format($data['p_pinjaman'], 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td>Tunjangan BPJS TK</td>
            <td class="text-right"><?php echo number_format($tunj_bpjs_tk, 0, ',', '.'); ?></td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>Tunjangan Pajak Pph 21</td>
            <td class="text-right"><?php echo number_format($tunj_pajak_pph21, 0, ',', '.'); ?></td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
        <tr class="total-row">
            <td><strong>Total Pendapatan</strong></td>
            <td class="text-right"><strong><?php echo number_format($total_pendapatan, 0, ',', '.'); ?></strong></td>
            <td><strong>Total Potongan</strong></td>
            <td class="text-right"><strong><?php echo number_format($total_potongan, 0, ',', '.'); ?></strong></td>
        </tr>
        <tr class="gaji-bersih-row">
            <td colspan="3" class="text-center"><strong>Gaji Bersih</strong></td>
            <td class="text-right"><strong><?php echo number_format($gaji_bersih, 0, ',', '.'); ?></strong></td>
        </tr>
    </tbody>
</table>

<p style="margin-top: 20px; font-size: 11px; font-style: italic; text-align: center;">
    Catatan: Slip gaji ini dianggap sah tanpa memerlukan tanda tangan.<br>
    Dokumen diunduh pada: <?php echo date('d/m/Y H:i:s'); ?> dari Sistem HRIS PT. Dua Farma Group
</p>
