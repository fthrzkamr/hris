<?php
// Export monthly insentif_kurir to Excel matching table columns
include("sess_check.php");
include("dist/config/koneksi.php");

require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

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
$sheet->setCellValue('S1', 'Total Dibayarkan');

// Second header row (subcolumns)
$sheet->setCellValue('G2', 'Total Titik');
$sheet->setCellValue('H2', 'Target Titik');
$sheet->setCellValue('I2', 'Kelebihan (neg jika kurang)');
$sheet->setCellValue('J2', 'Akumulasi Telat (mnt)');

$sheet->setCellValue('K2', 'Bonus Titik');
$sheet->setCellValue('L2', 'Bonus Full Hadir');
$sheet->setCellValue('M2', 'Lembur Operasional');
$sheet->setCellValue('N2', 'Lembur Ambil Barang');
$sheet->setCellValue('O2', 'Lembur Lainnya');
$sheet->setCellValue('P2', 'Denda Telat');
$sheet->setCellValue('Q2', 'Potongan Makan');
$sheet->setCellValue('R2', 'Uang Makan');

// Merge group headers
$sheet->mergeCells('A1:A2');
$sheet->mergeCells('B1:B2');
$sheet->mergeCells('C1:C2');
$sheet->mergeCells('D1:D2');
$sheet->mergeCells('E1:E2');
$sheet->mergeCells('F1:F2');
$sheet->mergeCells('G1:J1');
$sheet->mergeCells('K1:R1');
$sheet->mergeCells('S1:S2');

// Header style (group row)
$groupHeaderStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2F75B5']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

// Subheader style
$subHeaderStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$sheet->getStyle('A1:F1')->applyFromArray($groupHeaderStyle);
$sheet->getStyle('G1:J1')->applyFromArray($groupHeaderStyle);
$sheet->getStyle('K1:R1')->applyFromArray($groupHeaderStyle);
$sheet->getStyle('S1:S2')->applyFromArray($groupHeaderStyle);

$sheet->getStyle('A2:F2')->applyFromArray($subHeaderStyle);
$sheet->getStyle('G2:J2')->applyFromArray($subHeaderStyle);
$sheet->getStyle('K2:R2')->applyFromArray($subHeaderStyle);

// Auto-size cols and set wrap/align defaults
foreach (range('A', 'S') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $sheet->getStyle($col)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
}

// Row start
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
        // show negative when kurang (don't clamp to 0)
        $kelebihan = ($total_titik - $target_titik);
        $akumulasi_telat = intval($r['akumulasi_telat'] ?? 0);

        $bonus_titik = floatval($r['bonus_insentif_titik'] ?? 0);
        $bonus_full = floatval($r['bonus_insentif_full_masuk'] ?? 0);
        // New per-category lembur fields
        $lembur_operasional = floatval($r['lembur_operasional'] ?? $r['lembur_operasional_amt'] ?? 0);
        $lembur_ambil = floatval($r['lembur_ambil_barang'] ?? $r['lembur_ambil_amt'] ?? 0);
        $lembur_lain = floatval($r['lembur_lainnya'] ?? $r['lembur_lain_amt'] ?? 0);
        // total uang lembur column may still exist as uang_lembur
        $uang_lembur = floatval($r['uang_lembur'] ?? ($lembur_operasional + $lembur_ambil + $lembur_lain));
        $denda = floatval($r['denda_telat'] ?? 0);
        $potongan = floatval($r['potongan_makan'] ?? 0);
        $uang_makan = floatval($r['uang_makan'] ?? 0);
        $total_dibayar = floatval($r['jumlah_dibayarkan'] ?? 0);

        // Set values
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
        $sheet->setCellValueExplicit('M' . $rowNum, $lembur_operasional, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('N' . $rowNum, $lembur_ambil, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('O' . $rowNum, $lembur_lain, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('P' . $rowNum, $denda, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('Q' . $rowNum, $potongan, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('R' . $rowNum, $uang_makan, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

        $sheet->setCellValueExplicit('S' . $rowNum, $total_dibayar, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

        // Currency columns (keep thousand separator; show cents if any)
        foreach (['K','L','M','N','O','P','Q','R','S'] as $c) {
            $sheet->getStyle($c . $rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            $sheet->getStyle($c . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // Performance / integer columns (no decimal places)
        foreach (['G','H','I','J'] as $c) {
            $sheet->getStyle($c . $rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER); // no decimals
            $sheet->getStyle($c . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Alternating row fill for readability
        $fillColor = ($rowNum % 2 === 0) ? 'FFFFFF' : 'F7FBFF'; // light blue tint for odd rows
        $sheet->getStyle("A{$rowNum}:S{$rowNum}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($fillColor);

        // Thin border for the row
        $sheet->getStyle("A{$rowNum}:S{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $rowNum++;
    }
}

// Freeze and autofilter
$sheet->freezePane('A3');
$sheet->setAutoFilter('A2:S2');

// Final touches: set column G:S to wrap text for headers/data where needed
$sheet->getStyle('A1:S' . ($rowNum))->getAlignment()->setWrapText(true);

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
