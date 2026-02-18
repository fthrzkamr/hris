<?php
// Export monthly insentif_kurir to Excel matching table columns
include("sess_check.php");
include("../dist/config/koneksi.php");

require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// Read filters
$filter_periode = isset($_GET['periode']) ? $_GET['periode'] : '';
$filter_npp = isset($_GET['npp']) ? $_GET['npp'] : '';

// Build base query
$sql_base = "FROM transaksi_insentif_kurir t
    LEFT JOIN employee e ON t.npp = e.npp
    LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
    WHERE 1=1";

if (!empty($filter_periode)) {
    $sql_base .= " AND t.periode = '" . mysqli_real_escape_string($conn, $filter_periode) . "'";
}
if (!empty($filter_npp)) {
    $sql_base .= " AND t.npp LIKE '%" . mysqli_real_escape_string($conn, $filter_npp) . "%'";
}

$sql = "SELECT t.*, e.nama_emp, b.nama_bagian, e.cabang " . $sql_base . " ORDER BY t.periode DESC, t.npp ASC";
$res = mysqli_query($conn, $sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Rekap Bulanan');

// First header row (groups)
$sheet->setCellValue('A1', 'No');
$sheet->setCellValue('B1', 'NPP');
$sheet->setCellValue('C1', 'Nama Karyawan');
$sheet->setCellValue('D1', 'Bagian');
$sheet->setCellValue('E1', 'Cabang');
$sheet->setCellValue('F1', 'Periode');
$sheet->setCellValue('G1', 'Performa');
$sheet->setCellValue('K1', 'Komponen Pembayaran');
$sheet->setCellValue('Q1', 'Total Dibayarkan');
$sheet->setCellValue('R1', 'Status');

// Second header row (subcolumns)
$sheet->setCellValue('G2', 'Total Titik');
$sheet->setCellValue('H2', 'Target Titik');
$sheet->setCellValue('I2', 'Kelebihan');
$sheet->setCellValue('J2', 'Akumulasi Telat (mnt)');

$sheet->setCellValue('K2', 'Bonus Titik');
$sheet->setCellValue('L2', 'Bonus Full Hadir');
$sheet->setCellValue('M2', 'Uang Lembur');
$sheet->setCellValue('N2', 'Denda Telat');
$sheet->setCellValue('O2', 'Potongan Makan');
$sheet->setCellValue('P2', 'Uang Makan');

// Merge group headers
$sheet->mergeCells('A1:A2');
$sheet->mergeCells('B1:B2');
$sheet->mergeCells('C1:C2');
$sheet->mergeCells('D1:D2');
$sheet->mergeCells('E1:E2');
$sheet->mergeCells('F1:F2');
$sheet->mergeCells('G1:J1');
$sheet->mergeCells('K1:P1');
$sheet->mergeCells('Q1:Q2');
$sheet->mergeCells('R1:R2');

// Header style
$sheet->getStyle('A1:R2')->applyFromArray([
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
]);

// Auto-size cols
foreach (range('A', 'R') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

$rowNum = 3;
$no = 1;
if ($res && mysqli_num_rows($res) > 0) {
    while ($r = mysqli_fetch_assoc($res)) {
        // Format periode nicely
        $periode_display = $r['periode'];
        $pobj = DateTime::createFromFormat('Y-m', $r['periode']);
        if ($pobj) $periode_display = $pobj->format('M Y');

        $total_titik = intval($r['total_titik'] ?? 0);
        $target_titik = intval($r['target_titik'] ?? 0);
        $kelebihan = max(0, $total_titik - $target_titik);
        $akumulasi_telat = intval($r['akumulasi_telat'] ?? 0);

        $bonus_titik = floatval($r['bonus_insentif_titik'] ?? 0);
        $bonus_full = floatval($r['bonus_insentif_full_masuk'] ?? 0);
        $uang_lembur = floatval($r['uang_lembur'] ?? 0);
        $denda = floatval($r['denda_telat'] ?? 0);
        $potongan = floatval($r['potongan_makan'] ?? 0);
        $uang_makan = floatval($r['uang_makan'] ?? 0);
        $total_dibayar = floatval($r['jumlah_dibayarkan'] ?? 0);

        // Status text
        $status_text = '';
        if (isset($r['status_target'])) $status_text = $r['status_target'];
        else {
            // fallback: use simple logic
            if ($target_titik <= 0) $status_text = 'Belum Tercapai';
            else {
                $persen = ($target_titik>0) ? (($total_titik/$target_titik)*100) : 0;
                $status_text = ($total_titik >= $target_titik || $persen >= 75) ? 'Tercapai' : 'Belum Tercapai';
            }
        }

        $sheet->setCellValue('A' . $rowNum, $no++);
        $sheet->setCellValue('B' . $rowNum, $r['npp']);
        $sheet->setCellValue('C' . $rowNum, $r['nama_emp'] ?? '-');
        $sheet->setCellValue('D' . $rowNum, $r['nama_bagian'] ?? '-');
        $sheet->setCellValue('E' . $rowNum, $r['cabang'] ?? '-');
        $sheet->setCellValue('F' . $rowNum, $periode_display);

        $sheet->setCellValueExplicit('G' . $rowNum, $total_titik, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('H' . $rowNum, $target_titik, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('I' . $rowNum, $kelebihan, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('J' . $rowNum, $akumulasi_telat, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

        $sheet->setCellValueExplicit('K' . $rowNum, $bonus_titik, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('L' . $rowNum, $bonus_full, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('M' . $rowNum, $uang_lembur, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('N' . $rowNum, $denda, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('O' . $rowNum, $potongan, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('P' . $rowNum, $uang_makan, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

        $sheet->setCellValueExplicit('Q' . $rowNum, $total_dibayar, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValue('R' . $rowNum, $status_text);

        // Number format for currency columns
        foreach (['K','L','M','N','O','P','Q'] as $c) {
            $sheet->getStyle($c . $rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        }

        $rowNum++;
    }
}

// Freeze and autofilter
$sheet->freezePane('A3');
$sheet->setAutoFilter('A2:R2');

// Output
$filename = 'insentif_kurir_';
if ($filter_periode) $filename .= $filter_periode . '_';
$filename .= date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit();
?>
