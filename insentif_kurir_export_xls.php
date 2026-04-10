<?php
// Export monthly insentif_kurir to Excel matching table columns
include(__DIR__ . '/sess_check.php');
include(__DIR__ . '/dist/config/koneksi.php');

require_once __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Read filters
$filter_periode = isset($_GET['periode']) ? $_GET['periode'] : '';
$filter_npp = isset($_GET['npp']) ? $_GET['npp'] : '';

// Fetch lembur rates from settings (same as list page)
$rs_lembur = mysqli_query($conn, "SELECT nama_variabel, nominal_rp FROM pengaturan_insentif_kurir WHERE kategori='LEMBUR' AND is_active=1");
$rates = array('RATE_LEMBUR_OPERASIONAL' => 0, 'RATE_LEMBUR_AMBIL_BARANG' => 0, 'RATE_LEMBUR_LAINNYA' => 0);
if ($rs_lembur) {
    while ($rle = mysqli_fetch_assoc($rs_lembur)) {
        $rates[$rle['nama_variabel']] = floatval($rle['nominal_rp']);
    }
}
$rate_op = floatval($rates['RATE_LEMBUR_OPERASIONAL']);
$rate_ambil = floatval($rates['RATE_LEMBUR_AMBIL_BARANG']);
$rate_lain = floatval($rates['RATE_LEMBUR_LAINNYA']);

// Load titik cap from settings (fallback to 25)
$cap_titik = 25;
$rs_cap = mysqli_query($conn, "SELECT nilai_angka, nominal_rp FROM pengaturan_insentif_kurir WHERE nama_variabel='BATAS_ATAS_BONUS_TITIK' LIMIT 1");
if ($rs_cap && mysqli_num_rows($rs_cap) > 0) {
    $rc = mysqli_fetch_assoc($rs_cap);
    if (!empty($rc['nilai_angka']))
        $cap_titik = intval($rc['nilai_angka']);
    elseif (!empty($rc['nominal_rp']))
        $cap_titik = intval($rc['nominal_rp']);
}

// Build base query with LEFT JOIN aggregated lembur amounts per npp+periode (Approved only)
$sql_base = "FROM transaksi_insentif_kurir t
    LEFT JOIN employee e ON t.npp = e.npp
    LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
    LEFT JOIN (
        SELECT l.npp, DATE_FORMAT(l.tgl_lembur, '%Y-%m') AS periode,
            SUM(CASE WHEN LOWER(l.tujuan_lembur) LIKE '%operasional%' THEN (l.jumlah * {$rate_op}) ELSE 0 END) AS lembur_operasional_amt,
            SUM(CASE WHEN LOWER(l.tujuan_lembur) LIKE '%ambil%' OR LOWER(l.tujuan_lembur) LIKE '%pickup%' THEN (l.jumlah * {$rate_ambil}) ELSE 0 END) AS lembur_ambil_amt,
            SUM(CASE WHEN LOWER(l.tujuan_lembur) LIKE '%lain%' OR LOWER(l.tujuan_lembur) LIKE '%lainnya%' THEN (l.jumlah * {$rate_lain}) ELSE 0 END) AS lembur_lain_amt
        FROM lembur l
        WHERE l.status = 'Approved'
        GROUP BY l.npp, DATE_FORMAT(l.tgl_lembur, '%Y-%m')
    ) lb ON lb.npp = t.npp AND lb.periode = t.periode
    WHERE 1=1";

if (!empty($filter_periode)) {
    $sql_base .= " AND t.periode = '" . mysqli_real_escape_string($conn, $filter_periode) . "'";
}
if (!empty($filter_npp)) {
    $sql_base .= " AND t.npp LIKE '%" . mysqli_real_escape_string($conn, $filter_npp) . "%'";
}

$sql = "SELECT t.*,
    COALESCE(lb.lembur_operasional_amt, t.lembur_operasional) AS lembur_operasional,
    COALESCE(lb.lembur_ambil_amt, t.lembur_ambil_barang) AS lembur_ambil_barang,
    COALESCE(lb.lembur_lain_amt, t.lembur_lainnya) AS lembur_lainnya,
    COALESCE((COALESCE(lb.lembur_operasional_amt,0) + COALESCE(lb.lembur_ambil_amt,0) + COALESCE(lb.lembur_lain_amt,0)), t.uang_lembur) AS uang_lembur,
    e.nama_emp, b.nama_bagian, e.cabang " . $sql_base . " ORDER BY t.periode DESC, t.npp ASC";
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
$sheet->setCellValue('P1', 'Komponen Pembayaran');
$sheet->setCellValue('Y1', 'Total Dibayarkan');

// Second header row (subcolumns)
$sheet->setCellValue('G2', 'Aktual Titik');
$sheet->setCellValue('H2', 'Target Titik');
$sheet->setCellValue('I2', 'Kelebihan (neg jika kurang)');
$sheet->setCellValue('J2', 'Titik Max');
$sheet->setCellValue('K2', 'Akumulasi Telat (mnt)');
$sheet->setCellValue('L2', 'Hadir');
$sheet->setCellValue('M2', 'Telat');
$sheet->setCellValue('N2', 'Cuti');
$sheet->setCellValue('O2', 'Sakit');

$sheet->setCellValue('P2', 'Bonus Titik');
$sheet->setCellValue('Q2', 'Bonus Full Hadir');
$sheet->setCellValue('R2', 'Lembur Operasional');
$sheet->setCellValue('S2', 'Lembur Ambil Barang');
$sheet->setCellValue('T2', 'Lembur Lainnya');
$sheet->setCellValue('U2', 'Total Lembur');
$sheet->setCellValue('V2', 'Denda Telat');
$sheet->setCellValue('W2', 'Potongan Makan');
$sheet->setCellValue('X2', 'Uang Makan');

// Merge group headers
$sheet->mergeCells('A1:A2');
$sheet->mergeCells('B1:B2');
$sheet->mergeCells('C1:C2');
$sheet->mergeCells('D1:D2');
$sheet->mergeCells('E1:E2');
$sheet->mergeCells('F1:F2');
$sheet->mergeCells('G1:O1');
$sheet->mergeCells('P1:X1');
$sheet->mergeCells('Y1:Y2');

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
$sheet->getStyle('G1:O1')->applyFromArray($groupHeaderStyle);
$sheet->getStyle('P1:X1')->applyFromArray($groupHeaderStyle);
$sheet->getStyle('Y1:Y2')->applyFromArray($groupHeaderStyle);

$sheet->getStyle('A2:F2')->applyFromArray($subHeaderStyle);
$sheet->getStyle('G2:O2')->applyFromArray($subHeaderStyle);
$sheet->getStyle('P2:X2')->applyFromArray($subHeaderStyle);

// Auto-size cols and set wrap/align defaults
foreach (range('A', 'Y') as $col) {
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
        $hari_hadir = intval($r['hari_hadir'] ?? 0);
        $hari_telat = intval($r['hari_telat'] ?? 0);
        $hari_cuti = intval($r['hari_cuti'] ?? 0);
        $hari_sakit = intval($r['hari_sakit'] ?? 0);

        $bonus_titik = floatval($r['bonus_insentif_titik'] ?? 0);
        $bonus_full = floatval($r['bonus_insentif_full_masuk'] ?? 0);

        $lembur_operasional = floatval($r['lembur_operasional'] ?? 0);
        $lembur_ambil = floatval($r['lembur_ambil_barang'] ?? 0);
        $lembur_lain = floatval($r['lembur_lainnya'] ?? 0);
        $uang_lembur = floatval($r['uang_lembur'] ?? ($lembur_operasional + $lembur_ambil + $lembur_lain));

        $denda = floatval($r['denda_telat'] ?? 0);
        $potongan = floatval($r['potongan_makan'] ?? 0);
        $uang_makan = floatval($r['uang_makan'] ?? 0);
        $total_dibayar = max(0, floatval($r['jumlah_dibayarkan'] ?? 0));

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
        $sheet->setCellValueExplicit('J' . $rowNum, $cap_titik, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('K' . $rowNum, $akumulasi_telat, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('L' . $rowNum, $hari_hadir, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('M' . $rowNum, $hari_telat, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('N' . $rowNum, $hari_cuti, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('O' . $rowNum, $hari_sakit, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

        $sheet->setCellValueExplicit('P' . $rowNum, $bonus_titik, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('Q' . $rowNum, $bonus_full, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('R' . $rowNum, $lembur_operasional, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('S' . $rowNum, $lembur_ambil, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('T' . $rowNum, $lembur_lain, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('U' . $rowNum, $uang_lembur, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('V' . $rowNum, $denda, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('W' . $rowNum, $potongan, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('X' . $rowNum, $uang_makan, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

        $sheet->setCellValueExplicit('Y' . $rowNum, $total_dibayar, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

        // Currency columns (keep thousand separator; show cents if any)
        foreach (['P','Q','R','S','T','U','V','W','X','Y'] as $c) {
            $sheet->getStyle($c . $rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            $sheet->getStyle($c . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // Performance / integer columns (no decimal places)
        foreach (['G','H','I','J','K','L','M','N','O'] as $c) {
            $sheet->getStyle($c . $rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER); // no decimals
            $sheet->getStyle($c . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Alternating row fill for readability
        $fillColor = ($rowNum % 2 === 0) ? 'FFFFFF' : 'F7FBFF'; // light blue tint for odd rows
        $sheet->getStyle("A{$rowNum}:Y{$rowNum}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($fillColor);

        // Thin border for the row
        $sheet->getStyle("A{$rowNum}:Y{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $rowNum++;
    }
}

// Freeze and autofilter
$sheet->freezePane('A3');
$sheet->setAutoFilter('A2:Y2');

// Final touches
$sheet->getStyle('A1:Y' . ($rowNum))->getAlignment()->setWrapText(true);

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
