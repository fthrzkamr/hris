<?php
# Pengaturan tanggal komputer
date_default_timezone_set("Asia/Jakarta");

# Fungsi untuk membuat kode automatis
if (!function_exists('buatKode')) {
function buatKode($tabel, $inisial){
	global $conn;
	$struktur	= mysqli_query($conn, "SELECT * FROM $tabel LIMIT 1");
	$field		= mysqli_fetch_field_direct($struktur,0)->name;

	// membaca panjang kolom kunci (cara 1)
	$panjang	= mysqli_fetch_field_direct($struktur,0)->length;
	
	// membaca panjang kolom kunci (cara 2)
	//$hasil 	= mysql_fetch_field($struktur,0);
	//$panjang	= $hasil->max_length; 
	

 	$qry	= mysqli_query($conn, "SELECT MAX(".$field.") FROM ".$tabel);
 	$row	= mysqli_fetch_array($qry); 
 	if ($row[0]=="") {
 		$angka=0;
 	}
 	else {
 		$angka		= substr($row[0], strlen($inisial));
 	}
	
 	$angka++;
 	$angka	=strval($angka); 
 	$tmp	="";
 	for($i=1; $i<=($panjang-strlen($inisial)-strlen($angka)); $i++) {
		$tmp=$tmp."0";	
	}
 	return $inisial.$tmp.$angka;
}
}

# Fungsi untuk membalik tanggal dari format Indo (d-m-Y) -> English (Y-m-d)
if (!function_exists('InggrisTgl')) {
function InggrisTgl($tanggal){
	$tgl=substr($tanggal,0,2);
	$bln=substr($tanggal,3,2);
	$thn=substr($tanggal,6,4);
	$tanggal="$thn-$bln-$tgl";
	return $tanggal;
}
}

# Fungsi untuk membalik tanggal dari format English (Y-m-d) -> Indo (d-m-Y)
if (!function_exists('IndonesiaTgl')) {
function IndonesiaTgl($tanggal){
	$tgl=substr($tanggal,8,2);
	$bln=substr($tanggal,5,2);
	$thn=substr($tanggal,0,4);
	$tanggal="$tgl-$bln-$thn";
	return $tanggal;
}
}

# Fungsi untuk membalik tanggal dari format English (Y-m-d) -> Indo (d-m-Y)
if (!function_exists('Indonesia2Tgl')) {
function Indonesia2Tgl($tanggal){
	$namaBln = array("01" => "Januari", "02" => "Februari", "03" => "Maret", "04" => "April", "05" => "Mei", "06" => "Juni", 
					 "07" => "Juli", "08" => "Agustus", "09" => "September", "10" => "Oktober", "11" => "November", "12" => "Desember");
					 
	$tgl=substr($tanggal,8,2);
	$bln=substr($tanggal,5,2);
	$thn=substr($tanggal,0,4);
	$tanggal ="$tgl ".$namaBln[$bln]." $thn";
	return $tanggal;
}
}

if (!function_exists('hitungHari')) {
function hitungHari($myDate1, $myDate2){
        $myDate1 = strtotime($myDate1);
        $myDate2 = strtotime($myDate2);
 
        return ($myDate2 - $myDate1)/ (24 *3600);
}
}

# Fungsi untuk membuat format rupiah pada angka (uang)
if (!function_exists('format_angka')) {
function format_angka($angka) {
	$hasil =  number_format($angka,0, ",",".");
	return $hasil;
}
}

# Fungsi untuk format tanggal, dipakai plugins Callendar
if (!function_exists('form_tanggal')) {
function form_tanggal($nama,$value=''){
	echo" <input type='text' name='$nama' id='$nama' size='11' maxlength='20' value='$value'/>&nbsp;
	<img src='images/calendar-add-icon.png' align='top' style='cursor:pointer; margin-top:7px;' alt='kalender'onclick=\"displayCalendar(document.getElementById('$nama'),'dd-mm-yyyy',this)\"/>			
	";
}
}

if (!function_exists('angkaTerbilang')) {
function angkaTerbilang($x){
  $abil = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
  if ($x < 12)
    return " " . $abil[$x];
  elseif ($x < 20)
    return angkaTerbilang($x - 10) . " belas";
  elseif ($x < 100)
    return angkaTerbilang($x / 10) . " puluh" . angkaTerbilang($x % 10);
  elseif ($x < 200)
    return " seratus" . angkaTerbilang($x - 100);
  elseif ($x < 1000)
    return angkaTerbilang($x / 100) . " ratus" . angkaTerbilang($x % 100);
  elseif ($x < 2000)
    return " seribu" . angkaTerbilang($x - 1000);
  elseif ($x < 1000000)
    return angkaTerbilang($x / 1000) . " ribu" . angkaTerbilang($x % 1000);
  elseif ($x < 1000000000)
    return angkaTerbilang($x / 1000000) . " juta" . angkaTerbilang($x % 1000000);
}
}

/**
 * Auto-generate Biaya Luar Kota (Uang Harian) untuk perjalanan dinas saat tahap approval jika belum ada.
 * Plafon Uang Harian:
 * - Manager: Rp 125.000 / hari
 * - Leader / Asmen / SPV: Rp 100.000 / hari
 * - Staff / Officer / Lainnya: Rp 75.000 / hari
 */
if (!function_exists('auto_generate_biaya_luar_kota')) {
function auto_generate_biaya_luar_kota($conn, $perjalanan_id) {
    $perjalanan_id = (int)$perjalanan_id;
    if ($perjalanan_id <= 0) return false;

    // 1. Cek apakah rincian biaya luar kota / uang harian sudah pernah dibuat untuk perjalanan ini
    $check = mysqli_query($conn, "SELECT id FROM perjalanan_rincian WHERE perjalanan_id = $perjalanan_id AND (LOWER(ket) LIKE '%luar kota%' OR LOWER(ket) LIKE '%uang harian%') LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        return false; // Sudah ada, jangan duplikasi
    }

    // 2. Ambil data perjalanan
    $q_trip = mysqli_query($conn, "SELECT id, npp, nama, departemen, jumlah_hari FROM perjalanan_dinas WHERE id = $perjalanan_id LIMIT 1");
    if (!$q_trip || mysqli_num_rows($q_trip) == 0) return false;
    $trip = mysqli_fetch_assoc($q_trip);
    $jumlah_hari = max(1, (int)$trip['jumlah_hari']);

    // 3. Ambil seluruh peserta perjalanan dinas (Pengaju + Rekan)
    $peserta = [];
    $q_karyawan = mysqli_query($conn, "SELECT npp, nama, departemen, is_pemohon FROM perjalanan_karyawan WHERE perjalanan_id = $perjalanan_id ORDER BY is_pemohon DESC, id ASC");
    if ($q_karyawan && mysqli_num_rows($q_karyawan) > 0) {
        while ($row_k = mysqli_fetch_assoc($q_karyawan)) {
            $peserta[] = $row_k;
        }
    } else {
        // Fallback untuk data lama
        $peserta[] = [
            'npp' => $trip['npp'],
            'nama' => $trip['nama'],
            'departemen' => $trip['departemen'],
            'is_pemohon' => 1
        ];
    }

    // 4. Cari nomor urut terakhir di perjalanan_rincian
    $q_max = mysqli_query($conn, "SELECT COALESCE(MAX(nomor), 0) AS max_no FROM perjalanan_rincian WHERE perjalanan_id = $perjalanan_id");
    $row_max = mysqli_fetch_assoc($q_max);
    $next_nomor = (int)$row_max['max_no'];

    // 5. Generate biaya luar kota untuk masing-masing peserta
    foreach ($peserta as $p) {
        $p_npp = mysqli_real_escape_string($conn, $p['npp']);
        $p_nama = $p['nama'];

        // Cek jabatan di tabel employee
        $q_emp = mysqli_query($conn, "SELECT jabatan FROM employee WHERE npp = '$p_npp' LIMIT 1");
        $jabatan = '';
        if ($q_emp && $row_e = mysqli_fetch_assoc($q_emp)) {
            $jabatan = trim($row_e['jabatan'] ?? '');
        }

        // Tentukan tarif uang harian berdasarkan jabatan
        $j_lower = strtolower($jabatan);
        if (strpos($j_lower, 'manager') !== false || strpos($j_lower, 'manajer') !== false || strpos($j_lower, 'head') !== false || strpos($j_lower, 'direktur') !== false) {
            $rate = 125000;
        } elseif (strpos($j_lower, 'asmen') !== false || strpos($j_lower, 'leader') !== false || strpos($j_lower, 'supervisor') !== false || strpos($j_lower, 'spv') !== false || strpos($j_lower, 'koor') !== false) {
            $rate = 100000;
        } else {
            $rate = 75000; // Staff / Officer / Lainnya
        }

        $next_nomor++;
        $total_harian = $jumlah_hari * $rate;
        $ket_title = "Biaya Luar Kota (" . $p_nama . ($jabatan ? " - " . $jabatan : "") . ")";

        $sql_ins = "INSERT INTO perjalanan_rincian 
            (perjalanan_id, nomor, ket, nominal, qty, perkiraan, total, keterangan, kategori_biaya, npp_karyawan, nama_karyawan) 
            VALUES (
                $perjalanan_id, 
                $next_nomor, 
                '" . mysqli_real_escape_string($conn, $ket_title) . "', 
                '$rate', 
                $jumlah_hari, 
                '$rate', 
                '$total_harian', 
                'Standar Plafon Uang Harian', 
                'personal', 
                '$p_npp', 
                '" . mysqli_real_escape_string($conn, $p_nama) . "'
            )";
        mysqli_query($conn, $sql_ins);
    }

    // 6. Recalculate budget_total di tabel perjalanan_dinas
    mysqli_query($conn, "UPDATE perjalanan_dinas SET budget_total = (
        SELECT COALESCE(SUM(CAST(REPLACE(total,',','') AS DECIMAL(20,2))), 0) 
        FROM perjalanan_rincian WHERE perjalanan_id = $perjalanan_id
    ) WHERE id = $perjalanan_id");

    return true;
}
}
?>