<?php
// Export Absensi Kurir to Excel with merged header rows
include(__DIR__ . '/sess_check.php');
include(__DIR__ . '/dist/config/koneksi.php');

require_once __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// Read filters
$filter_tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : '';
$filter_tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';
$filter_npp = isset($_GET['npp']) ? $_GET['npp'] : '';

// Build base query
$sql_base = "FROM absensi_kurir a
    LEFT JOIN employee e ON a.npp = e.npp
    LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
    WHERE 1=1";

if (!empty($filter_tanggal_awal)) {
    $sql_base .= " AND a.tanggal_absen >= '" . mysqli_real_escape_string($conn, $filter_tanggal_awal) . "'";
}
if (!empty($filter_tanggal_akhir)) {
    $sql_base .= " AND a.tanggal_absen <= '" . mysqli_real_escape_string($conn, $filter_tanggal_akhir) . "'";
}
if (!empty($filter_npp)) {
    $sql_base .= " AND a.npp LIKE '%" . mysqli_real_escape_string($conn, $filter_npp) . "%'";
}

$sql = "SELECT a.*, e.nama_emp, b.nama_bagian, e.cabang " . $sql_base . " ORDER BY a.tanggal_absen DESC, a.npp ASC";
$res = mysqli_query($conn, $sql);

// Prepare spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Absensi Harian');

// Header rows (two rows, with merges to match UI grouping)
$sheet->setCellValue('A1', 'No');
$sheet->setCellValue('B1', 'Tanggal');
$sheet->setCellValue('C1', 'NPP');
$sheet->setCellValue('D1', 'Nama');
$sheet->setCellValue('E1', 'Tugas');
$sheet->setCellValue('F1', 'Jam Kerja');
$sheet->setCellValue('H1', 'Status');
$sheet->setCellValue('K1', 'Komponen Finansial (Rp)');

// second header row
$sheet->setCellValue('F2', 'Masuk');
$sheet->setCellValue('G2', 'Pulang');
$sheet->setCellValue('H2', 'Hadir');
$sheet->setCellValue('I2', 'Telat');
$sheet->setCellValue('J2', 'Cuti');
$sheet->setCellValue('K2', 'Bonus Titik');
$sheet->setCellValue('L2', 'Bonus Full Hadir');
$sheet->setCellValue('M2', 'Makan');
$sheet->setCellValue('N2', 'Lembur');
$sheet->setCellValue('O2', 'Denda');
$sheet->setCellValue('P2', 'Menit');

// Merge cells to create grouped headers
$sheet->mergeCells('A1:A2');
$sheet->mergeCells('B1:B2');
$sheet->mergeCells('C1:C2');
$sheet->mergeCells('D1:D2');
$sheet->mergeCells('E1:E2');
$sheet->mergeCells('F1:G1');
$sheet->mergeCells('H1:J1');
$sheet->mergeCells('K1:P1');

// Style header
$headerStyle = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
];
$sheet->getStyle('A1:P2')->applyFromArray($headerStyle);

// Column widths
$cols = range('A', 'P');
foreach ($cols as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Write data starting row 3
$rowNum = 3;
$no = 1;

// If there are rows, iterate
if ($res && mysqli_num_rows($res) > 0) {
    while ($r = mysqli_fetch_assoc($res)) {
        $tanggal_formatted = date('d/m/Y', strtotime($r['tanggal_absen']));
        $hari_en = date('D', strtotime($r['tanggal_absen']));
        $hari_indo_map = ['Sun'=>'Min','Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab'];
        $hari_id = isset($hari_indo_map[$hari_en]) ? $hari_indo_map[$hari_en] : $hari_en;
        $tanggal_display = $tanggal_formatted . ' (' . $hari_id . ')';

        $jam_masuk = $r['jam_masuk'] ? date('H:i', strtotime($r['jam_masuk'])) : '-';
        $jam_pulang = $r['jam_pulang'] ? date('H:i', strtotime($r['jam_pulang'])) : '-';

        // Status
        $is_cuti = !empty($r['is_cuti']);
        $is_sakit = !empty($r['is_sakit']);
        if ($is_cuti) {
            $hadir = '-';
            $telat = '-';
            $cuti = $is_sakit ? 'Sakit' : 'Cuti';
        } else {
            $hadir = !empty($r['is_hadir']) ? 'Hadir' : 'Tidak';
            $telat = !empty($r['is_late']) ? (($r['menit_terlambat'] ?? 0) . ' mnt') : 'Tepat Waktu';
            $cuti = '-';
        }

        // Financial columns: match list view (bonus columns shown as '-'; makan shows negative only)
        $bonus_titik_cell = '-';
        $bonus_full_cell = '-';

        $uang_makan_val = floatval($r['uang_makan'] ?? 0);
        $makan_cell = ($uang_makan_val < 0) ? $uang_makan_val : null;
        $lembur_cell = floatval($r['uang_lembur'] ?? 0);
        $denda_cell = floatval($r['denda_telat'] ?? 0);
        $menit_cell = intval($r['menit_terlambat'] ?? 0);

        // Fill cells
        $sheet->setCellValue('A' . $rowNum, $no++);
        $sheet->setCellValue('B' . $rowNum, $tanggal_display);
        $sheet->setCellValue('C' . $rowNum, $r['npp']);
        $sheet->setCellValue('D' . $rowNum, $r['nama_emp'] ?? '-');
        $sheet->setCellValue('E' . $rowNum, $r['jenis_tugas'] ?? '-');
        $sheet->setCellValue('F' . $rowNum, $jam_masuk);
        $sheet->setCellValue('G' . $rowNum, $jam_pulang);
        $sheet->setCellValue('H' . $rowNum, $hadir);
        $sheet->setCellValue('I' . $rowNum, $telat);
        $sheet->setCellValue('J' . $rowNum, $cuti);

        // Bonus fields left as text dash to mirror UI
        $sheet->setCellValue('K' . $rowNum, $bonus_titik_cell);
        $sheet->setCellValue('L' . $rowNum, $bonus_full_cell);

        // Numeric fields
        if ($makan_cell !== null) {
            $sheet->setCellValueExplicit('M' . $rowNum, $makan_cell, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        } else {
            $sheet->setCellValue('M' . $rowNum, '-');
        }
        $sheet->setCellValueExplicit('N' . $rowNum, $lembur_cell, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('O' . $rowNum, $denda_cell, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('P' . $rowNum, $menit_cell, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

        // Apply number format for numeric currency columns (M,N,O)
        $sheet->getStyle('M' . $rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle('N' . $rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle('O' . $rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        $rowNum++;
    }
}

// Auto-filter and freeze top rows
$sheet->setAutoFilter('A2:P2');
$sheet->freezePane('A3');

// Output to browser
$filename = 'absensi_kurir_';
if ($filter_tanggal_awal) $filename .= $filter_tanggal_awal . '_';
if ($filter_tanggal_akhir) $filename .= $filter_tanggal_akhir . '_';
$filename .= date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit();
?>