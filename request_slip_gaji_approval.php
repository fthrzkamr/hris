<?php
include("sess_check.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_request = mysqli_real_escape_string($conn, $_POST['id_request']);
    $action = mysqli_real_escape_string($conn, $_POST['action']);
    $approved_by = isset($sess_admname) ? $sess_admname : (isset($sess_mngname) ? $sess_mngname : 'SYSTEM');
    $tanggal_approval = date('Y-m-d H:i:s');

    if ($action == 'approve') {
        $sql_update = "UPDATE request_slip_gaji 
                       SET status = 'approved', 
                           approved_by = ?, 
                           tanggal_approval = ?,
                           updated_at = NOW()
                       WHERE id_request = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "ssi", $approved_by, $tanggal_approval, $id_request);

        if (mysqli_stmt_execute($stmt_update)) {
            $_SESSION['message'] = 'Request slip gaji berhasil disetujui!';
            $_SESSION['status'] = 'success';
        } else {
            $_SESSION['message'] = 'Gagal menyetujui request. Error: ' . mysqli_error($conn);
            $_SESSION['status'] = 'danger';
        }

    } elseif ($action == 'approve_with_data') {
        // Approve dengan input data gaji
        $npp = mysqli_real_escape_string($conn, $_POST['npp']);
        $bulan = mysqli_real_escape_string($conn, $_POST['bulan']);
        $tahun = mysqli_real_escape_string($conn, $_POST['tahun']);
        
        // Ambil data input gaji
        $gaji_pokok = floatval($_POST['gaji_pokok']);
        $tunj_transport = floatval($_POST['tunj_transport']);
        $tunj_jabatan = floatval($_POST['tunj_jabatan']);
        $tunj_kinerja = floatval($_POST['tunj_kinerja']);
        $tunj_bpjs_kesehatan = floatval($_POST['tunj_bpjs_kesehatan']);
        $tunj_bpjs_tk = floatval($_POST['tunj_bpjs_tk']);
        $tunj_pajak_pph21 = floatval($_POST['tunj_pajak_pph21']);
        
        $p_bpjs_kesehatan = floatval($_POST['p_bpjs_kesehatan']);
        $p_bpjs_tk = floatval($_POST['p_bpjs_tk']);
        $p_pajak_pph21 = floatval($_POST['p_pajak_pph21']);
        $p_keterlambatan = floatval($_POST['p_keterlambatan']);
        $p_pinjaman = floatval($_POST['p_pinjaman']);
        
        $catatan = isset($_POST['catatan']) ? mysqli_real_escape_string($conn, $_POST['catatan']) : '';
        
        // Hitung total
        $total_pendapatan = $gaji_pokok + $tunj_transport + $tunj_jabatan + $tunj_kinerja + $tunj_bpjs_kesehatan + $tunj_bpjs_tk + $tunj_pajak_pph21;
        $total_potongan = $p_bpjs_kesehatan + $p_bpjs_tk + $p_pajak_pph21 + $p_keterlambatan + $p_pinjaman;
        $gaji_bersih = $total_pendapatan - $total_potongan;
        
        // Validasi gaji pokok
        if ($gaji_pokok <= 0) {
            $_SESSION['message'] = 'Gaji pokok harus diisi dan lebih besar dari 0!';
            $_SESSION['status'] = 'danger';
            header("location: request_slip_gaji_approval_list.php");
            exit;
        }
        
        // Buat tanggal untuk laporan_potongan (hari terakhir bulan tersebut)
        $tanggal_laporan = date('Y-m-t', strtotime("$tahun-$bulan-01"));
        
        // Gabungkan catatan dengan info tunjangan dan potongan
        $desc_lain = "Tunj.BPJS Kes: Rp " . number_format($tunj_bpjs_kesehatan, 0, ',', '.') . 
                     " | Tunj.BPJS TK: Rp " . number_format($tunj_bpjs_tk, 0, ',', '.') . 
                     " | Tunj.Pajak: Rp " . number_format($tunj_pajak_pph21, 0, ',', '.') .
                     " | P.BPJS Kes: Rp " . number_format($p_bpjs_kesehatan, 0, ',', '.') . 
                     " | P.BPJS TK: Rp " . number_format($p_bpjs_tk, 0, ',', '.') . 
                     " | P.Pajak: Rp " . number_format($p_pajak_pph21, 0, ',', '.');
        if (!empty($catatan)) {
            $desc_lain .= " | Catatan: " . $catatan;
        }
        
        // Cek apakah data sudah ada di laporan_potongan
        $sql_check = "SELECT COUNT(*) as total FROM laporan_potongan 
                      WHERE npp = ? AND YEAR(tanggal) = ? AND MONTH(tanggal) = ?";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "sss", $npp, $tahun, $bulan);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $data_check = mysqli_fetch_assoc($result_check);
        
        $success = false;
        
        // Hitung p_lain untuk kompatibilitas dengan struktur lama (BPJS + Pajak potongan)
        $p_lain = $p_bpjs_kesehatan + $p_bpjs_tk + $p_pajak_pph21;
        
        if ($data_check['total'] > 0) {
            // Data sudah ada, lakukan UPDATE
            $sql_gaji = "UPDATE laporan_potongan 
                         SET p_keterlambatan = ?,
                             p_pinjaman = ?,
                             p_lain = ?,
                             desc_lain = ?,
                             p_hasil = ?
                         WHERE npp = ? AND YEAR(tanggal) = ? AND MONTH(tanggal) = ?";
            $stmt_gaji = mysqli_prepare($conn, $sql_gaji);
            mysqli_stmt_bind_param($stmt_gaji, "ddddsss", 
                $p_keterlambatan, $p_pinjaman, $p_lain, $desc_lain, $gaji_bersih, 
                $npp, $tahun, $bulan);
            $success = mysqli_stmt_execute($stmt_gaji);
            $action_type = "diperbarui";
        } else {
            // Data belum ada, lakukan INSERT
            $sql_gaji = "INSERT INTO laporan_potongan 
                         (npp, tanggal, p_keterlambatan, p_pinjaman, p_lain, desc_lain, p_hasil)
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_gaji = mysqli_prepare($conn, $sql_gaji);
            mysqli_stmt_bind_param($stmt_gaji, "ssdddds", 
                $npp, $tanggal_laporan, $p_keterlambatan, $p_pinjaman, $p_lain, 
                $desc_lain, $gaji_bersih);
            $success = mysqli_stmt_execute($stmt_gaji);
            $action_type = "ditambahkan";
        }
        
        if ($success) {
            // Update data gaji di tabel employee juga (gaji pokok, tunjangan dasar tanpa BPJS & Pajak)
            $sql_update_emp = "UPDATE employee 
                              SET gaji_pokok = ?, 
                                  tunj_jabatan = ?, 
                                  tunj_kinerja = ?, 
                                  tunj_transport = ?,
                                  total_gaji = ?
                              WHERE npp = ?";
            // Total gaji dasar (tanpa tunjangan BPJS & Pajak karena itu kompensasi perusahaan)
            $total_gaji = $gaji_pokok + $tunj_jabatan + $tunj_kinerja + $tunj_transport;
            $stmt_update_emp = mysqli_prepare($conn, $sql_update_emp);
            mysqli_stmt_bind_param($stmt_update_emp, "ddddds", 
                $gaji_pokok, $tunj_jabatan, $tunj_kinerja, $tunj_transport, $total_gaji, $npp);
            mysqli_stmt_execute($stmt_update_emp);

            // Update status request menjadi approved
            $sql_update = "UPDATE request_slip_gaji 
                           SET status = 'approved', 
                               approved_by = ?, 
                               tanggal_approval = ?,
                               updated_at = NOW()
                           WHERE id_request = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "ssi", $approved_by, $tanggal_approval, $id_request);
            
            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['message'] = "Data slip gaji berhasil $action_type dan request telah disetujui!";
                $_SESSION['status'] = 'success';

            } else {
                $_SESSION['message'] = "Data slip gaji $action_type tetapi gagal update status request. Error: " . mysqli_error($conn);
                $_SESSION['status'] = 'warning';
            }
        } else {
            $_SESSION['message'] = 'Gagal menyimpan data slip gaji. Error: ' . mysqli_error($conn);
            $_SESSION['status'] = 'danger';
        }
        
    } elseif ($action == 'reject') {
        // Reject request
        $keterangan_reject = mysqli_real_escape_string($conn, $_POST['keterangan_reject']);
        
        if (empty($keterangan_reject)) {
            $_SESSION['message'] = 'Alasan penolakan harus diisi!';
            $_SESSION['status'] = 'warning';
            header("location: request_slip_gaji_approval_list.php");
            exit;
        }
        
        $sql_update = "UPDATE request_slip_gaji 
                       SET status = 'rejected', 
                           keterangan_reject = ?,
                           approved_by = ?, 
                           tanggal_approval = ?,
                           updated_at = NOW()
                       WHERE id_request = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "sssi", $keterangan_reject, $approved_by, $tanggal_approval, $id_request);
        
        if (mysqli_stmt_execute($stmt_update)) {
            $_SESSION['message'] = 'Request slip gaji ditolak.';
            $_SESSION['status'] = 'success';
        } else {
            $_SESSION['message'] = 'Gagal menolak request. Error: ' . mysqli_error($conn);
            $_SESSION['status'] = 'danger';
        }
    }

    header("location: request_slip_gaji_approval_list.php");

} else {
    header("location: request_slip_gaji_approval_list.php");
}
?>
