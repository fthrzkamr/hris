<?php
include("sess_check.php");
include("dist/config/koneksi.php");
header('Content-Type: application/json');

try {
    $input = $_POST;
    $action = isset($input['action']) ? trim($input['action']) : '';
    
    // ACTION: Update Total Dibayarkan (Manual Override)
    if ($action === 'update_total_dibayarkan') {
        $npp = isset($input['npp']) ? trim($input['npp']) : '';
        $periode = isset($input['periode']) ? trim($input['periode']) : '';
        $jumlah_dibayarkan = isset($input['jumlah_dibayarkan']) ? intval($input['jumlah_dibayarkan']) : 0;

        if ($npp === '' || $periode === '') {
            echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
            exit();
        }

        // Direct update jumlah_dibayarkan (manual override, no recalculation)
        $npp_esc = mysqli_real_escape_string($conn, $npp);
        $periode_esc = mysqli_real_escape_string($conn, $periode);
        
        $update_sql = "UPDATE transaksi_insentif_kurir 
                       SET jumlah_dibayarkan = ? 
                       WHERE npp = ? AND periode = ? 
                       LIMIT 1";
        
        $stmt = mysqli_prepare($conn, $update_sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Prepare statement gagal: ' . mysqli_error($conn)]);
            exit();
        }
        
        mysqli_stmt_bind_param($stmt, 'iss', $jumlah_dibayarkan, $npp_esc, $periode_esc);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true,
                'npp' => $npp,
                'periode' => $periode,
                'jumlah_dibayarkan' => $jumlah_dibayarkan,
                'message' => 'Total dibayarkan berhasil diupdate (manual override)'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update gagal: ' . mysqli_stmt_error($stmt)]);
        }
        
        mysqli_stmt_close($stmt);
        exit();
    }
    
    // ACTION: Update Telat (akumulasi * 1000 => denda_telat)
    if ($action === 'update_telat') {
        $npp = isset($input['npp']) ? trim($input['npp']) : '';
        $periode = isset($input['periode']) ? trim($input['periode']) : '';
        $akumulasi = isset($input['akumulasi_telat']) ? intval($input['akumulasi_telat']) : 0;
        $hari_telat = isset($input['hari_telat']) ? intval($input['hari_telat']) : 0;

        if ($npp === '' || $periode === '') {
            echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
            exit();
        }

        // Hitung denda otomatis: 1 menit = Rp 1.000
        $denda_telat = intval($akumulasi) * 1000;

        // Ambil nilai komponen yang diperlukan untuk rekalkulasi jumlah_dibayarkan
        $npp_esc = mysqli_real_escape_string($conn, $npp);
        $periode_esc = mysqli_real_escape_string($conn, $periode);
        $q = mysqli_query($conn, "SELECT uang_makan, bonus_insentif_titik, bonus_insentif_full_masuk, uang_lembur FROM transaksi_insentif_kurir WHERE npp = '$npp_esc' AND periode = '$periode_esc' LIMIT 1");
        if (!$q || mysqli_num_rows($q) == 0) {
            echo json_encode(['success' => false, 'message' => 'Baris transaksi tidak ditemukan']);
            exit();
        }
        $r = mysqli_fetch_assoc($q);
        $uang_makan = floatval($r['uang_makan'] ?? 0);
        $bonus_titik = floatval($r['bonus_insentif_titik'] ?? 0);
        $bonus_full = floatval($r['bonus_insentif_full_masuk'] ?? 0);
        $uang_lembur = floatval($r['uang_lembur'] ?? 0);

        // Recalculate jumlah_dibayarkan sesuai frontend formula:
        // total = uang_makan + bonus_titik + bonus_full + uang_lembur - denda_telat
        $jumlah = intval($uang_makan + $bonus_titik + $bonus_full + $uang_lembur - $denda_telat);
        if ($jumlah < 0) $jumlah = $jumlah; // tetap boleh negatif jika sesuai kebijakan

        // Update database (prepared statement)
        $update_sql = "UPDATE transaksi_insentif_kurir
                       SET akumulasi_telat = ?, hari_telat = ?, denda_telat = ?, jumlah_dibayarkan = ?, updated_at = NOW()
                       WHERE npp = ? AND periode = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Prepare statement gagal: ' . mysqli_error($conn)]);
            exit();
        }
        // types: int,int,int,int,string,string => 'iiiiss'
        mysqli_stmt_bind_param($stmt, 'iiiiss', $akumulasi, $hari_telat, $denda_telat, $jumlah, $npp, $periode);
        $ok = mysqli_stmt_execute($stmt);
        if (!$ok) {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => false, 'message' => 'Update gagal: ' . $err]);
            exit();
        }
        mysqli_stmt_close($stmt);

        // Kembalikan response lengkap
        echo json_encode([
            'success' => true,
            'npp' => $npp,
            'periode' => $periode,
            'akumulasi_telat' => $akumulasi,
            'hari_telat' => $hari_telat,
            'denda_telat' => $denda_telat,
            'jumlah_dibayarkan' => $jumlah
        ]);
        exit();
    }
    
    // ACTION: Update Telat & Denda (Manual Edit)
    if ($action === 'update_telat') {
        $npp = isset($input['npp']) ? trim($input['npp']) : '';
        $periode = isset($input['periode']) ? trim($input['periode']) : '';
        $akumulasi_telat = isset($input['akumulasi_telat']) ? intval($input['akumulasi_telat']) : 0;
        $hari_telat = isset($input['hari_telat']) ? intval($input['hari_telat']) : 0;
        $denda_telat = isset($input['denda_telat']) ? intval($input['denda_telat']) : 0;

        if ($npp === '' || $periode === '') {
            echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
            exit();
        }

        // Load settings untuk mendapatkan bonus full hadir
        $settings = [];
        $rs_settings = mysqli_query($conn, "SELECT nama_variabel, nilai_angka, nominal_rp FROM pengaturan_insentif_kurir");
        if ($rs_settings) {
            while ($r = mysqli_fetch_assoc($rs_settings)) {
                $k = strtoupper(trim($r['nama_variabel']));
                if (isset($r['nominal_rp']) && $r['nominal_rp'] !== null && $r['nominal_rp'] !== '') 
                    $settings[$k] = intval($r['nominal_rp']);
                elseif (isset($r['nilai_angka']) && $r['nilai_angka'] !== null && $r['nilai_angka'] !== '') 
                    $settings[$k] = intval($r['nilai_angka']);
            }
        }

        // Fetch current record untuk recalc bonus full hadir dan jumlah dibayarkan
        $npp_esc = mysqli_real_escape_string($conn, $npp);
        $periode_esc = mysqli_real_escape_string($conn, $periode);
        
        $query_current = "SELECT bonus_insentif_titik, uang_lembur, uang_makan, hari_cuti, hari_sakit
                          FROM transaksi_insentif_kurir 
                          WHERE npp = '$npp_esc' AND periode = '$periode_esc' 
                          LIMIT 1";
        
        $result_current = mysqli_query($conn, $query_current);
        
        if (!$result_current) {
            echo json_encode(['success' => false, 'message' => 'Query gagal: ' . mysqli_error($conn)]);
            exit();
        }
        
        $current = mysqli_fetch_assoc($result_current);
        
        if (!$current) {
            echo json_encode(['success' => false, 'message' => 'Baris transaksi tidak ditemukan']);
            exit();
        }

        $bonus_titik = floatval($current['bonus_insentif_titik'] ?? 0);
        $uang_lembur = floatval($current['uang_lembur'] ?? 0);
        $uang_makan = floatval($current['uang_makan'] ?? 0);
        $hari_cuti = intval($current['hari_cuti'] ?? 0);
        $hari_sakit = intval($current['hari_sakit'] ?? 0);

        // HITUNG OTOMATIS BONUS FULL HADIR BERDASARKAN CUTI + SAKIT + TELAT
        $bonus_full_hadir_calculated = 0;
        
        // Jika tidak ada cuti, sakit, dan telat -> dapat bonus full hadir
        if ($hari_cuti === 0 && $hari_sakit === 0 && $hari_telat === 0) {
            // Resolve tipe per NPP (kategori_npp preferred, fallback employee)
            $tipe = null;
            $q = mysqli_query($conn, "SELECT tipe FROM kategori_npp WHERE npp='$npp_esc' LIMIT 1");
            if ($q && mysqli_num_rows($q) > 0) {
                $rt = mysqli_fetch_assoc($q);
                $tipe = trim($rt['tipe']);
            }
            if (empty($tipe)) {
                $qe = mysqli_query($conn, "SELECT tipe FROM employee WHERE npp='$npp_esc' LIMIT 1");
                if ($qe && mysqli_num_rows($qe) > 0) {
                    $re = mysqli_fetch_assoc($qe);
                    $tipe = trim($re['tipe']);
                }
            }

            // Get bonus amount based on tipe
            if ($tipe === 'motor') {
                $bonus_full_hadir_calculated = intval($settings['BONUS_FULL_HADIR_MOTOR'] ?? 250000);
            } else {
                $bonus_full_hadir_calculated = intval($settings['BONUS_FULL_HADIR_MOBIL'] ?? 300000);
            }

            // Exclude specific NPPs from receiving Bonus Full Hadir
            $exempt_full_hadir = array('22910033');
            if (in_array($npp, $exempt_full_hadir, true)) {
                $bonus_full_hadir_calculated = 0;
            }
        }

        $bonus_full = floatval($bonus_full_hadir_calculated);

        // jumlah_dibayarkan = uang_makan + (bonus_titik + bonus_full + uang_lembur) - denda_telat
        $insentif_total = $bonus_titik + $bonus_full + $uang_lembur;
        $jumlah = intval($uang_makan + $insentif_total - $denda_telat);

        // Update transaksi_insentif_kurir: akumulasi_telat, hari_telat, denda_telat, bonus_full_hadir, jumlah_dibayarkan
        $update_sql = "UPDATE transaksi_insentif_kurir 
                       SET akumulasi_telat = ?, 
                           hari_telat = ?, 
                           denda_telat = ?,
                           bonus_insentif_full_masuk = ?,
                           jumlah_dibayarkan = ?,
                           updated_at = NOW() 
                       WHERE npp = ? AND periode = ?";
        
        $u = mysqli_prepare($conn, $update_sql);
        // types: akumulasi_telat(int), hari_telat(int), denda_telat(int), bonus_full(int), jumlah(int), npp(string), periode(string)
        mysqli_stmt_bind_param($u, 'iiiiiss', $akumulasi_telat, $hari_telat, $denda_telat, $bonus_full_hadir_calculated, $jumlah, $npp, $periode);
        $ok = mysqli_stmt_execute($u);
        mysqli_stmt_close($u);

        if (!$ok) {
            echo json_encode(['success' => false, 'message' => 'Update gagal: ' . mysqli_error($conn)]);
            exit();
        }

        echo json_encode([
            'success' => true,
            'npp' => $npp,
            'periode' => $periode,
            'akumulasi_telat' => $akumulasi_telat,
            'hari_telat' => $hari_telat,
            'denda_telat' => $denda_telat,
            'bonus_full_hadir' => $bonus_full_hadir_calculated,
            'jumlah_dibayarkan' => $jumlah
        ]);
        exit();
    }
    
    if ($action === 'update_cuti_sakit_makan') {
        // Update hari_cuti & hari_sakit di transaksi_insentif_kurir
        $npp = isset($input['npp']) ? trim($input['npp']) : '';
        $periode = isset($input['periode']) ? trim($input['periode']) : '';
        $hari_cuti = isset($input['hari_cuti']) ? intval($input['hari_cuti']) : 0;
        $hari_sakit = isset($input['hari_sakit']) ? intval($input['hari_sakit']) : 0;

        if ($npp === '' || $periode === '') {
            echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
            exit();
        }

        // Load settings untuk mendapatkan POTONGAN_MAKAN_PER_HARI
        $settings = [];
        $rs_settings = mysqli_query($conn, "SELECT nama_variabel, nilai_angka, nominal_rp FROM pengaturan_insentif_kurir");
        if ($rs_settings) {
            while ($r = mysqli_fetch_assoc($rs_settings)) {
                $k = strtoupper(trim($r['nama_variabel']));
                if (isset($r['nominal_rp']) && $r['nominal_rp'] !== null && $r['nominal_rp'] !== '') 
                    $settings[$k] = intval($r['nominal_rp']);
                elseif (isset($r['nilai_angka']) && $r['nilai_angka'] !== null && $r['nilai_angka'] !== '') 
                    $settings[$k] = intval($r['nilai_angka']);
            }
        }
        $potongan_per_hari = isset($settings['POTONGAN_MAKAN_PER_HARI']) ? intval($settings['POTONGAN_MAKAN_PER_HARI']) : 15000;
        $uang_makan_base = isset($settings['UANG_MAKAN_BULANAN']) ? intval($settings['UANG_MAKAN_BULANAN']) : 300000;

        // Fetch current record dengan uang_makan_base dari database (bukan dari input manual)
        $npp_esc = mysqli_real_escape_string($conn, $npp);
        $periode_esc = mysqli_real_escape_string($conn, $periode);
        
        $query_current = "SELECT bonus_insentif_titik, bonus_insentif_full_masuk, uang_lembur, denda_telat, hari_telat,
                          (uang_makan + potongan_makan) as uang_makan_base_db
                          FROM transaksi_insentif_kurir 
                          WHERE npp = '$npp_esc' AND periode = '$periode_esc' 
                          LIMIT 1";
        
        $result_current = mysqli_query($conn, $query_current);
        
        if (!$result_current) {
            echo json_encode(['success' => false, 'message' => 'Query gagal: ' . mysqli_error($conn)]);
            exit();
        }
        
        $current = mysqli_fetch_assoc($result_current);
        
        if (!$current) {
            echo json_encode(['success' => false, 'message' => 'Baris transaksi tidak ditemukan']);
            exit();
        }

        // Get uang makan base from database (original value before any deductions)
        $uang_makan_base_employee = intval($current['uang_makan_base_db'] ?? $uang_makan_base);
        
        // Check if this NPP is exempt from uang makan
        $exempt_uang_makan = array('22910033');
        if (in_array($npp, $exempt_uang_makan, true)) {
            $uang_makan_base_employee = 0;
        }

        $bonus_titik = floatval($current['bonus_insentif_titik'] ?? 0);
        $uang_lembur = floatval($current['uang_lembur'] ?? 0);
        $denda_telat = floatval($current['denda_telat'] ?? 0);
        $hari_telat = intval($current['hari_telat'] ?? 0);

        // HITUNG OTOMATIS BONUS FULL HADIR BERDASARKAN CUTI + SAKIT + TELAT
        $bonus_full_hadir_calculated = 0;
        
        // Jika tidak ada cuti, sakit, dan telat -> dapat bonus full hadir
        if ($hari_cuti === 0 && $hari_sakit === 0 && $hari_telat === 0) {
            // Resolve tipe per NPP (kategori_npp preferred, fallback employee)
            $tipe = null;
            $q = mysqli_query($conn, "SELECT tipe FROM kategori_npp WHERE npp='$npp_esc' LIMIT 1");
            if ($q && mysqli_num_rows($q) > 0) {
                $qr = mysqli_fetch_assoc($q);
                $tipe = $qr['tipe'];
            }
            if (empty($tipe)) {
                $qe = mysqli_query($conn, "SELECT jabatan, nama_bagian FROM employee WHERE npp='$npp_esc' LIMIT 1");
                if ($qe && mysqli_num_rows($qe) > 0) {
                    $er = mysqli_fetch_assoc($qe);
                    $jab = strtolower($er['jabatan'] ?? '');
                    $nb = strtolower($er['nama_bagian'] ?? '');
                    if (strpos($jab, 'driver') !== false || strpos($jab, 'sopir') !== false || strpos($nb, 'mobil') !== false || strpos($jab, 'mobil') !== false) {
                        $tipe = 'mobil';
                    } else {
                        $tipe = 'motor';
                    }
                } else {
                    $tipe = 'motor';
                }
            }

            // Get bonus amount based on tipe
            if ($tipe === 'motor') {
                $bonus_full_hadir_calculated = intval($settings['BONUS_FULL_HADIR_MOTOR'] ?? 250000);
            } else {
                $bonus_full_hadir_calculated = intval($settings['BONUS_FULL_HADIR'] ?? 250000);
            }

            // Exclude specific NPPs from receiving Bonus Full Hadir
            $exempt_full_hadir = array('22910033');
            if (in_array($npp, $exempt_full_hadir, true)) {
                $bonus_full_hadir_calculated = 0;
            }
        }

        $bonus_full = floatval($bonus_full_hadir_calculated);

        // HITUNG OTOMATIS UANG MAKAN BERDASARKAN CUTI + SAKIT
        $total_hari_tidak_hadir = $hari_cuti + $hari_sakit;
        $potongan_makan = $total_hari_tidak_hadir * $potongan_per_hari;
        $uang_makan_final = $uang_makan_base_employee - $potongan_makan;
        
        // Untuk NPP yang exempt dari uang makan, pastikan tidak pernah negatif
        if (in_array($npp, $exempt_uang_makan, true)) {
            $uang_makan_final = 0;
            $potongan_makan = 0;
        }

        // jumlah_dibayarkan = uang_makan_final + (bonus_titik + bonus_full + uang_lembur) - denda_telat
        $insentif_total = $bonus_titik + $bonus_full + $uang_lembur;
        $jumlah = intval($uang_makan_final + $insentif_total - $denda_telat);

        // update transaksi_insentif_kurir: hari_cuti, hari_sakit, bonus_full_hadir, potongan_makan, uang_makan, jumlah_dibayarkan
        $update_sql = "UPDATE transaksi_insentif_kurir 
                       SET hari_cuti = ?, 
                           hari_sakit = ?, 
                           bonus_insentif_full_masuk = ?,
                           potongan_makan = ?,
                           uang_makan = ?, 
                           jumlah_dibayarkan = ?, 
                           updated_at = NOW() 
                       WHERE npp = ? AND periode = ?";
        
        $u = mysqli_prepare($conn, $update_sql);
        // binding: int(cuti), int(sakit), int(bonus_full_calculated), int(potongan), int(uang_makan_final), int(jumlah), string(npp), string(periode)
        mysqli_stmt_bind_param($u, 'iiiiiiss', $hari_cuti, $hari_sakit, $bonus_full_hadir_calculated, $potongan_makan, $uang_makan_final, $jumlah, $npp, $periode);
        $ok = mysqli_stmt_execute($u);
        mysqli_stmt_close($u);

        if (!$ok) {
            echo json_encode(['success' => false, 'message' => 'Update gagal: ' . mysqli_error($conn)]);
            exit();
        }

        echo json_encode([
            'success' => true,
            'npp' => $npp,
            'periode' => $periode,
            'hari_cuti' => $hari_cuti,
            'hari_sakit' => $hari_sakit,
            'bonus_full_hadir' => $bonus_full_hadir_calculated,
            'potongan_makan' => $potongan_makan,
            'uang_makan' => $uang_makan_final,
            'jumlah_dibayarkan' => $jumlah
        ]);
        exit();
    }
    
    // --- START: update lembur (separate kategori, accepts partial inputs) ---
    if ($action === 'update_lembur') {
        $npp = isset($input['npp']) ? trim($input['npp']) : '';
        $periode = isset($input['periode']) ? trim($input['periode']) : '';

        if ($npp === '' || $periode === '') {
            echo json_encode(['success' => false, 'message' => 'Parameter lembur tidak lengkap (npp/periode)']);
            exit();
        }

        // optional counts (may be absent)
        $has_op = array_key_exists('jumlah_operasional', $input);
        $has_ambil = array_key_exists('jumlah_ambil', $input);
        $has_lain = array_key_exists('jumlah_lain', $input);

        $j_op_in = $has_op ? intval($input['jumlah_operasional']) : null;
        $j_ambil_in = $has_ambil ? intval($input['jumlah_ambil']) : null;
        $j_lain_in = $has_lain ? intval($input['jumlah_lain']) : null;

        // ambil rate dari pengaturan (LEMBUR)
        $rates = [
            'RATE_LEMBUR_OPERASIONAL' => 0,
            'RATE_LEMBUR_AMBIL_BARANG' => 0,
            'RATE_LEMBUR_LAINNYA' => 0
        ];
        $rs = mysqli_query($conn, "SELECT nama_variabel, nominal_rp FROM pengaturan_insentif_kurir WHERE kategori='LEMBUR' AND is_active=1");
        if ($rs) {
            while ($r = mysqli_fetch_assoc($rs)) {
                $key = strtoupper(trim($r['nama_variabel']));
                if (isset($rates[$key])) $rates[$key] = intval($r['nominal_rp']);
            }
            mysqli_free_result($rs);
        }

        $rate_op = $rates['RATE_LEMBUR_OPERASIONAL'];
        $rate_ambil = $rates['RATE_LEMBUR_AMBIL_BARANG'];
        $rate_lain = $rates['RATE_LEMBUR_LAINNYA'];

        // ambil current record (per-kategori jika ada), fallback 0
        $npp_esc = mysqli_real_escape_string($conn, $npp);
        $periode_esc = mysqli_real_escape_string($conn, $periode);
        $qtr = mysqli_query($conn, "SELECT 
                COALESCE(lembur_operasional,0) AS lembur_operasional_amt, 
                COALESCE(lembur_ambil_barang,0) AS lembur_ambil_amt,
                COALESCE(lembur_lainnya,0) AS lembur_lain_amt,
                COALESCE(uang_lembur,0) AS uang_lembur_total,
                COALESCE(uang_makan,0) AS uang_makan,
                COALESCE(bonus_insentif_titik,0) AS bonus_titik,
                COALESCE(bonus_insentif_full_masuk,0) AS bonus_full,
                COALESCE(denda_telat,0) AS denda_telat,
                COALESCE(hari_hadir,0) AS hari_hadir
            FROM transaksi_insentif_kurir 
            WHERE npp='$npp_esc' AND periode='$periode_esc' LIMIT 1");
        if (!$qtr || mysqli_num_rows($qtr) == 0) {
            echo json_encode(['success' => false, 'message' => 'Baris transaksi tidak ditemukan']);
            exit();
        }
        $rtr = mysqli_fetch_assoc($qtr);

        $cur_op_amt = intval($rtr['lembur_operasional_amt']);
        $cur_ambil_amt = intval($rtr['lembur_ambil_amt']);
        $cur_lain_amt = intval($rtr['lembur_lain_amt']);

        // Derive current counts from stored amounts when needed (rate > 0)
        $cur_op_cnt = ($rate_op > 0) ? intval(round($cur_op_amt / $rate_op)) : 0;
        $cur_ambil_cnt = ($rate_ambil > 0) ? intval(round($cur_ambil_amt / $rate_ambil)) : 0;
        $cur_lain_cnt = ($rate_lain > 0) ? intval(round($cur_lain_amt / $rate_lain)) : 0;

        // Determine final counts: use provided input when present, otherwise keep current
        $final_op_cnt = ($j_op_in !== null) ? max(0, $j_op_in) : $cur_op_cnt;
        $final_ambil_cnt = ($j_ambil_in !== null) ? max(0, $j_ambil_in) : $cur_ambil_cnt;
        $final_lain_cnt = ($j_lain_in !== null) ? max(0, $j_lain_in) : $cur_lain_cnt;

        // calculate amounts
        $lembur_operasional_amt = intval($final_op_cnt * $rate_op);
        $lembur_ambil_amt = intval($final_ambil_cnt * $rate_ambil);
        $lembur_lain_amt = intval($final_lain_cnt * $rate_lain);
        $uang_lembur_total = intval($lembur_operasional_amt + $lembur_ambil_amt + $lembur_lain_amt);

        // other components for recalc jumlah_dibayarkan
        $uang_makan = floatval($rtr['uang_makan'] ?? 0);
        $bonus_titik = floatval($rtr['bonus_titik'] ?? 0);
        $bonus_full = floatval($rtr['bonus_full'] ?? 0);
        $denda_telat = floatval($rtr['denda_telat'] ?? 0);
        $hari_hadir = intval($rtr['hari_hadir'] ?? 0);

        // recalc jumlah sesuai business rule
        $insentif_total = $bonus_titik + $bonus_full + $uang_lembur_total;
        if ($hari_hadir > 0) {
            $jumlah = intval($uang_makan + max(0, $insentif_total - $denda_telat));
        } else {
            $jumlah = intval($uang_makan + $insentif_total - $denda_telat);
        }

        // update transaksi: simpan per-kategori amounts (and total uang_lembur)
        $u = mysqli_prepare($conn, "UPDATE transaksi_insentif_kurir 
            SET lembur_operasional = ?, lembur_ambil_barang = ?, lembur_lainnya = ?, uang_lembur = ?, jumlah_dibayarkan = ?, updated_at = NOW() 
            WHERE npp = ? AND periode = ? LIMIT 1");
        if (!$u) {
            echo json_encode(['success' => false, 'message' => 'Prepare update gagal: ' . mysqli_error($conn)]);
            exit();
        }
        // types: 5 ints (op_amt, ambil_amt, lain_amt, uang_lembur_total, jumlah) + 2 strings (npp, periode)
        mysqli_stmt_bind_param($u, 'iiiiiss', $lembur_operasional_amt, $lembur_ambil_amt, $lembur_lain_amt, $uang_lembur_total, $jumlah, $npp, $periode);
        $ok = mysqli_stmt_execute($u);
        mysqli_stmt_close($u);

        if (!$ok) {
            echo json_encode(['success' => false, 'message' => 'Update lembur gagal: ' . mysqli_error($conn)]);
            exit();
        }

        echo json_encode([
            'success' => true,
            'npp' => $npp,
            'periode' => $periode,
            'jumlah_operasional' => $final_op_cnt,
            'jumlah_ambil' => $final_ambil_cnt,
            'jumlah_lain' => $final_lain_cnt,
            'lembur_operasional_amt' => $lembur_operasional_amt,
            'lembur_ambil_amt' => $lembur_ambil_amt,
            'lembur_lain_amt' => $lembur_lain_amt,
            'uang_lembur' => $uang_lembur_total,
            'jumlah_dibayarkan' => $jumlah
        ]);
        exit();
    }
    // --- END update lembur ---
    
    $npp = isset($input['npp']) ? trim($input['npp']) : '';
    $periode = isset($input['periode']) ? trim($input['periode']) : '';
    $aktual = isset($input['aktual']) ? intval($input['aktual']) : 0;
    $target = isset($input['target']) ? intval($input['target']) : 0;

    if ($npp === '' || $periode === '') {
        echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
        exit();
    }

    // Load settings for calculation
    $settings = [];
    $rs = mysqli_query($conn, "SELECT nama_variabel, nilai_angka, nominal_rp FROM pengaturan_insentif_kurir");
    if ($rs) {
        while ($r = mysqli_fetch_assoc($rs)) {
            $k = strtoupper(trim($r['nama_variabel']));
            if (isset($r['nominal_rp']) && $r['nominal_rp'] !== null && $r['nominal_rp'] !== '') 
                $settings[$k] = intval($r['nominal_rp']);
            elseif (isset($r['nilai_angka']) && $r['nilai_angka'] !== null && $r['nilai_angka'] !== '') 
                $settings[$k] = intval($r['nilai_angka']);
        }
    }

    // Read rate per titik and caps from settings
    $rate = isset($settings['RATE_PER_TITIK_LEBIH']) ? intval($settings['RATE_PER_TITIK_LEBIH']) : 20000;
    $count_cap = isset($settings['BATAS_ATAS_BONUS_TITIK']) ? intval($settings['BATAS_ATAS_BONUS_TITIK']) : 0;
    $amount_cap = isset($settings['MAX_BONUS_INSENTIF']) ? intval($settings['MAX_BONUS_INSENTIF']) : 0;

    // Fetch existing row - use direct query
    $npp_esc = mysqli_real_escape_string($conn, $npp);
    $periode_esc = mysqli_real_escape_string($conn, $periode);
    
    $query = "SELECT id, bonus_insentif_full_masuk, uang_lembur, denda_telat, uang_makan, potongan_makan, hari_hadir 
              FROM transaksi_insentif_kurir 
              WHERE npp = '$npp_esc' AND periode = '$periode_esc' 
              LIMIT 1";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception('Query gagal: ' . mysqli_error($conn));
    }
    
    $row = mysqli_fetch_assoc($result);
    
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        exit();
    }

    $bonus_full = floatval($row['bonus_insentif_full_masuk'] ?? 0);
    $uang_lembur = floatval($row['uang_lembur'] ?? 0);
    $denda_telat = floatval($row['denda_telat'] ?? 0);
    $uang_makan = floatval($row['uang_makan'] ?? 0);
    $hari_hadir = intval($row['hari_hadir'] ?? 0);

    // Calculate bonus from excess titik
    $kelebihan = max(0, $aktual - $target);
    $eligible_count = ($count_cap > 0) ? min($kelebihan, $count_cap) : $kelebihan;
    $raw_bonus = $eligible_count * $rate;
    $bonus_titik = ($amount_cap > 0) ? min($raw_bonus, $amount_cap) : $raw_bonus;

    // Recalculate jumlah_dibayarkan
    $other_net = $bonus_titik + $bonus_full + $uang_lembur - $denda_telat;
    
    if ($hari_hadir > 0) {
        $jumlah = $uang_makan + max(0, $other_net);
    } else {
        $jumlah = $uang_makan + $other_net;
    }

    // Update DB
    $update_query = "UPDATE transaksi_insentif_kurir 
                     SET total_titik = $aktual, 
                         target_titik = $target, 
                         bonus_insentif_titik = $bonus_titik, 
                         jumlah_dibayarkan = $jumlah, 
                         updated_at = NOW() 
                     WHERE npp = '$npp_esc' AND periode = '$periode_esc'";
    
    $update_result = mysqli_query($conn, $update_query);
    
    if (!$update_result) {
        throw new Exception('Update gagal: ' . mysqli_error($conn));
    }

    // Return updated values
    echo json_encode([
        'success' => true,
        'npp' => $npp,
        'periode' => $periode,
        'total_titik' => $aktual,
        'target_titik' => $target,
        'kelebihan' => $kelebihan,
        'bonus_insentif_titik' => $bonus_titik,
        'jumlah_dibayarkan' => $jumlah
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
