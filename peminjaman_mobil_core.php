<?php
// Ensure database table exists and has approval columns
if (isset($conn)) {
    $createTable = "CREATE TABLE IF NOT EXISTS peminjaman_mobil (
        id INT AUTO_INCREMENT PRIMARY KEY,
        npp VARCHAR(50) NOT NULL,
        nama_emp VARCHAR(100) NOT NULL,
        mobil VARCHAR(100) NOT NULL,
        tgl_mulai DATE NOT NULL,
        tgl_selesai DATE NOT NULL,
        jam_mulai TIME NOT NULL,
        jam_selesai TIME NOT NULL,
        keperluan TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'Menunggu',
        keterangan TEXT,
        approve_hr VARCHAR(20) DEFAULT NULL,
        approve_hr_by VARCHAR(100) DEFAULT NULL,
        approve_hr_at DATETIME DEFAULT NULL,
        approve_ga VARCHAR(20) DEFAULT NULL,
        approve_ga_by VARCHAR(100) DEFAULT NULL,
        approve_ga_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_npp (npp),
        INDEX idx_dates (tgl_mulai, tgl_selesai)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $createTable);

    // Add columns if they don't exist yet (for existing installs)
    $cols = [
        'approve_hr VARCHAR(20) DEFAULT NULL', 
        'approve_hr_by VARCHAR(100) DEFAULT NULL', 
        'approve_hr_at DATETIME DEFAULT NULL', 
        'approve_ga VARCHAR(20) DEFAULT NULL', 
        'approve_ga_by VARCHAR(100) DEFAULT NULL', 
        'approve_ga_at DATETIME DEFAULT NULL', 
        'tujuan TEXT DEFAULT NULL',
        'km_awal INT DEFAULT NULL',
        'bensin_awal INT DEFAULT NULL'
    ];
    foreach ($cols as $col) {
        $colname = explode(' ', $col)[0];
        $checkCol = mysqli_query($conn, "SHOW COLUMNS FROM peminjaman_mobil LIKE '$colname'");
        if (mysqli_num_rows($checkCol) == 0) {
            mysqli_query($conn, "ALTER TABLE peminjaman_mobil ADD COLUMN $col");
        }
    }
}

// Determine current user
$active_npp  = $sess_mngid ?? $sess_admid ?? '';
$active_name = $sess_mngname ?? $sess_admname ?? '';
$active_role = strtolower($sess_jabatan ?? '');

// Role flags
$folder_name = basename(dirname($_SERVER['SCRIPT_FILENAME']));
$is_managerhr = ($folder_name === 'managerhr');
$is_ga        = ($folder_name === 'ga');
$is_approver  = ($is_managerhr || $is_ga || isset($sess_admid) || $active_npp == '26020216');
$is_admin_or_hr = $is_approver; // backward compat

$message = '';
$message_type = '';

$is_approval_page = (isset($pagedesc) && $pagedesc == "Approval Peminjaman Mobil");

// ---------- APPROVAL LOGIC ----------
if ($is_approver && isset($_POST['action_approval'])) {
    $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
    $action     = mysqli_real_escape_string($conn, $_POST['action_approval']);
    $now        = date('Y-m-d H:i:s');
    $actor      = mysqli_real_escape_string($conn, $active_name);

    if ($action == 'approve') {
        // Approval no longer allows switching mobil here. Switching is a separate action after approval.
        // Get booking to validate overlap (use booking's mobil)
        $q_book = mysqli_query($conn, "SELECT tgl_mulai, tgl_selesai, jam_mulai, jam_selesai, mobil FROM peminjaman_mobil WHERE id='$booking_id'");
        $book_data = mysqli_fetch_assoc($q_book);
        $start_dt = $book_data['tgl_mulai'] . ' ' . $book_data['jam_mulai'];
        $end_dt   = $book_data['tgl_selesai'] . ' ' . $book_data['jam_selesai'];
        $mobil_to_check = $book_data['mobil'];

        $query_overlap = "SELECT * FROM peminjaman_mobil 
                          WHERE mobil = '$mobil_to_check' 
                          AND status = 'Disetujui' 
                          AND id != '$booking_id'
                          AND CONCAT(tgl_mulai, ' ', jam_mulai) < '$end_dt' 
                          AND CONCAT(tgl_selesai, ' ', jam_selesai) > '$start_dt'";
        $res_overlap = mysqli_query($conn, $query_overlap);

        if (mysqli_num_rows($res_overlap) > 0) {
            $overlap_data = mysqli_fetch_assoc($res_overlap);
            $pinjam_oleh = $overlap_data['nama_emp'];
            $tgl_dari    = date('d/m/Y', strtotime($overlap_data['tgl_mulai']));
            $tgl_sampai  = date('d/m/Y', strtotime($overlap_data['tgl_selesai']));
            $message = "Gagal menyetujui! Mobil <strong>$mobil_to_check</strong> sudah dibooking oleh <strong>$pinjam_oleh</strong> untuk periode $tgl_dari s.d $tgl_sampai.";
            $message_type = "danger";
        } else {
            if ($is_managerhr) {
                mysqli_query($conn, "UPDATE peminjaman_mobil SET approve_hr='Disetujui', approve_hr_by='$actor', approve_hr_at='$now', status='Disetujui' WHERE id='$booking_id'");
                $message = "Persetujuan HR berhasil disimpan, pengajuan langsung disetujui.";
                $message_type = "success";
            } elseif ($is_ga) {
                mysqli_query($conn, "UPDATE peminjaman_mobil SET approve_ga='Disetujui', approve_ga_by='$actor', approve_ga_at='$now', status='Disetujui' WHERE id='$booking_id'");
                $message = "Persetujuan GA berhasil disimpan, pengajuan langsung disetujui.";
                $message_type = "success";
            } else {
                mysqli_query($conn, "UPDATE peminjaman_mobil SET approve_hr='Disetujui', approve_ga='Disetujui', approve_hr_by='$actor', approve_ga_by='$actor', approve_hr_at='$now', approve_ga_at='$now', status='Disetujui' WHERE id='$booking_id'");
                $message = "Peminjaman disetujui.";
                $message_type = "success";
            }
        }
    } elseif ($action == 'reject') {
        $reason = mysqli_real_escape_string($conn, $_POST['keterangan'] ?? '');
        if ($is_managerhr) {
            mysqli_query($conn, "UPDATE peminjaman_mobil SET approve_hr='Ditolak', approve_hr_by='$actor', approve_hr_at='$now', status='Ditolak', keterangan='$reason' WHERE id='$booking_id'");
        } elseif ($is_ga) {
            mysqli_query($conn, "UPDATE peminjaman_mobil SET approve_ga='Ditolak', approve_ga_by='$actor', approve_ga_at='$now', status='Ditolak', keterangan='$reason' WHERE id='$booking_id'");
        } else {
            mysqli_query($conn, "UPDATE peminjaman_mobil SET status='Ditolak', keterangan='$reason' WHERE id='$booking_id'");
        }
        $message = "Pengajuan telah ditolak.";
        $message_type = "warning";
    }
}
// ---------- POST-APPROVAL SWITCH ACTION ----------
if ($is_approver && isset($_POST['action_switch']) && $_POST['action_switch'] == 'switch') {
    $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
    $new_mobil  = mysqli_real_escape_string($conn, $_POST['new_mobil'] ?? '');
    $now        = date('Y-m-d H:i:s');
    $actor      = mysqli_real_escape_string($conn, $active_name);

    if (empty($new_mobil)) {
        $message = "Pilih mobil terlebih dahulu untuk melakukan switch.";
        $message_type = "danger";
    } else {
        $q_book = mysqli_query($conn, "SELECT status, keterangan, tgl_mulai, tgl_selesai, jam_mulai, jam_selesai, mobil FROM peminjaman_mobil WHERE id='$booking_id'");
        if (mysqli_num_rows($q_book) == 0) {
            $message = "Pengajuan tidak ditemukan.";
            $message_type = "danger";
        } else {
            $bd = mysqli_fetch_assoc($q_book);
            if ($bd['status'] != 'Disetujui') {
                $message = "Switch mobil hanya bisa dilakukan setelah pengajuan disetujui.";
                $message_type = "danger";
            } else {
                // check overlap for new mobil
                $start_dt = $bd['tgl_mulai'] . ' ' . $bd['jam_mulai'];
                $end_dt   = $bd['tgl_selesai'] . ' ' . $bd['jam_selesai'];
                $query_overlap = "SELECT * FROM peminjaman_mobil 
                                  WHERE mobil = '$new_mobil' 
                                  AND status IN ('Disetujui', 'Menunggu') 
                                  AND id != '$booking_id'
                                  AND CONCAT(tgl_mulai, ' ', jam_mulai) < '$end_dt' 
                                  AND CONCAT(tgl_selesai, ' ', jam_selesai) > '$start_dt'";
                $res_overlap = mysqli_query($conn, $query_overlap);
                if (mysqli_num_rows($res_overlap) > 0) {
                    $overlap_data = mysqli_fetch_assoc($res_overlap);
                    $pinjam_oleh = $overlap_data['nama_emp'];
                    $tgl_dari    = date('d/m/Y', strtotime($overlap_data['tgl_mulai']));
                    $tgl_sampai  = date('d/m/Y', strtotime($overlap_data['tgl_selesai']));
                    $message = "Gagal switch! Mobil <strong>$new_mobil</strong> sudah dibooking oleh <strong>$pinjam_oleh</strong> untuk periode $tgl_dari s.d $tgl_sampai.";
                    $message_type = "danger";
                } else {
                    mysqli_query($conn, "UPDATE peminjaman_mobil SET mobil='$new_mobil' WHERE id='$booking_id'");
                    $message = "Mobil berhasil di-switch menjadi <strong>$new_mobil</strong>.";
                    $message_type = "success";
                }
            }
        }
    }
}

// ---------- CANCEL ACTION (BY USER OR APPROVER) ----------
if (isset($_POST['action_cancel']) && $_POST['action_cancel'] == 'cancel') {
    $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
    $reason     = mysqli_real_escape_string($conn, trim($_POST['cancel_reason'] ?? ''));
    $now        = date('Y-m-d H:i:s');
    $actor      = mysqli_real_escape_string($conn, $active_name);

    if (empty($reason)) {
        $message = "Alasan pembatalan wajib diisi!";
        $message_type = "danger";
    } else {
        $q_chk = mysqli_query($conn, "SELECT * FROM peminjaman_mobil WHERE id='$booking_id'");
        if ($q_chk && mysqli_num_rows($q_chk) > 0) {
            $bd = mysqli_fetch_assoc($q_chk);
            if (!$is_approver && $bd['npp'] !== $active_npp) {
                $message = "Anda tidak memiliki hak untuk membatalkan pengajuan ini.";
                $message_type = "danger";
            } elseif (!$is_approver && $bd['status'] !== 'Menunggu') {
                $message = "Pengajuan yang sudah disetujui hanya dapat dibatalkan oleh Tim GA / HR.";
                $message_type = "danger";
            } else {
                $query_cancel = "UPDATE peminjaman_mobil SET status='Dibatalkan', keterangan='$reason' WHERE id='$booking_id'";
                if (mysqli_query($conn, $query_cancel)) {
                    $message = "Peminjaman mobil berhasil dibatalkan. Jadwal mobil telah dibebaskan kembali.";
                    $message_type = "success";
                } else {
                    $message = "Gagal membatalkan peminjaman: " . mysqli_error($conn);
                    $message_type = "danger";
                }
            }
        } else {
            $message = "Data peminjaman tidak ditemukan.";
            $message_type = "danger";
        }
    }
}

// ---------- EDIT BOOKING ACTION ----------
if (isset($_POST['action_edit']) && $_POST['action_edit'] == 'edit') {
    $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
    $edit_mobil = mysqli_real_escape_string($conn, $_POST['mobil'] ?? '');
    $edit_tgl_mulai = mysqli_real_escape_string($conn, $_POST['tgl_mulai'] ?? '');
    $edit_tgl_selesai = mysqli_real_escape_string($conn, $_POST['tgl_selesai'] ?? '');
    $edit_jam_mulai = mysqli_real_escape_string($conn, $_POST['jam_mulai'] ?? '');
    $edit_jam_selesai = mysqli_real_escape_string($conn, $_POST['jam_selesai'] ?? '');
    $edit_keperluan = mysqli_real_escape_string($conn, $_POST['keperluan'] ?? '');
    if ($edit_keperluan === 'Keperluan Kantor Lainnya' && !empty($_POST['detail_keperluan_lainnya'])) {
        $edit_detail_lain = mysqli_real_escape_string($conn, $_POST['detail_keperluan_lainnya']);
        $edit_keperluan = "Keperluan Kantor Lainnya ($edit_detail_lain)";
    }
    $edit_tujuan = mysqli_real_escape_string($conn, $_POST['tujuan'] ?? '');
    $edit_km_awal = intval($_POST['km_awal'] ?? 0);
    $edit_bensin_awal = intval($_POST['bensin_awal'] ?? 0);
    $now = date('Y-m-d H:i:s');
    $actor = mysqli_real_escape_string($conn, $active_name);

    $q_chk = mysqli_query($conn, "SELECT * FROM peminjaman_mobil WHERE id='$booking_id'");
    if (mysqli_num_rows($q_chk) == 0) {
        $message = "Data peminjaman tidak ditemukan.";
        $message_type = "danger";
    } else {
        $bd = mysqli_fetch_assoc($q_chk);
        if (!$is_approver && $bd['npp'] !== $active_npp) {
            $message = "Anda tidak memiliki akses untuk mengedit pengajuan ini.";
            $message_type = "danger";
        } elseif (!$is_approver && $bd['status'] !== 'Menunggu') {
            $message = "Pengajuan yang sudah disetujui hanya dapat diubah oleh Tim GA / HR.";
            $message_type = "danger";
        } elseif (empty($edit_mobil) || empty($edit_tgl_mulai) || empty($edit_tgl_selesai) || empty($edit_jam_mulai) || empty($edit_jam_selesai) || empty($edit_keperluan) || empty($edit_tujuan) || empty($edit_km_awal) || empty($edit_bensin_awal)) {
            $message = "Semua kolom input edit wajib diisi!";
            $message_type = "danger";
        } elseif ($edit_tgl_mulai > $edit_tgl_selesai) {
            $message = "Tanggal mulai tidak boleh melebihi tanggal selesai!";
            $message_type = "danger";
        } elseif ($edit_tgl_mulai == $edit_tgl_selesai && $edit_jam_mulai >= $edit_jam_selesai) {
            $message = "Jam mulai harus sebelum jam selesai pada hari yang sama!";
            $message_type = "danger";
        } else {
            // Check overlap
            $start_dt = "$edit_tgl_mulai $edit_jam_mulai";
            $end_dt   = "$edit_tgl_selesai $edit_jam_selesai";
            $query_overlap = "SELECT * FROM peminjaman_mobil 
                              WHERE mobil = '$edit_mobil' 
                              AND status IN ('Disetujui', 'Menunggu') 
                              AND id != '$booking_id'
                              AND CONCAT(tgl_mulai, ' ', jam_mulai) < '$end_dt' 
                              AND CONCAT(tgl_selesai, ' ', jam_selesai) > '$start_dt'";
            $res_overlap = mysqli_query($conn, $query_overlap);
            if (mysqli_num_rows($res_overlap) > 0) {
                $overlap_data = mysqli_fetch_assoc($res_overlap);
                $pinjam_oleh = $overlap_data['nama_emp'];
                $tgl_dari    = date('d/m/Y', strtotime($overlap_data['tgl_mulai']));
                $tgl_sampai  = date('d/m/Y', strtotime($overlap_data['tgl_selesai']));
                $message = "Gagal mengubah! Mobil <strong>$edit_mobil</strong> sudah dibooking oleh <strong>$pinjam_oleh</strong> untuk periode $tgl_dari s.d $tgl_sampai.";
                $message_type = "danger";
            } else {
                $q_upd = "UPDATE peminjaman_mobil SET 
                            mobil='$edit_mobil',
                            tgl_mulai='$edit_tgl_mulai',
                            tgl_selesai='$edit_tgl_selesai',
                            jam_mulai='$edit_jam_mulai',
                            jam_selesai='$edit_jam_selesai',
                            keperluan='$edit_keperluan',
                            tujuan='$edit_tujuan',
                            km_awal=$edit_km_awal,
                            bensin_awal=$edit_bensin_awal
                          WHERE id='$booking_id'";
                if (mysqli_query($conn, $q_upd)) {
                    $message = "Data peminjaman berhasil diperbarui.";
                    $message_type = "success";
                } else {
                    $message = "Gagal memperbarui data: " . mysqli_error($conn);
                    $message_type = "danger";
                }
            }
        }
    }
}

// ---------- MASTER MOBIL LOGIC ----------
if ($is_approver && isset($_POST['action_master']) && $_POST['action_master'] == 'update_status') {
    $id_mobil = mysqli_real_escape_string($conn, $_POST['id_mobil']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    mysqli_query($conn, "UPDATE master_mobil SET status='$new_status' WHERE id_mobil='$id_mobil'");
    $message = "Status mobil berhasil diperbarui menjadi '$new_status'.";
    $message_type = "success";
}

// ---------- MASTER MOBIL TABLE ----------
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_mobil (
    id_mobil INT AUTO_INCREMENT PRIMARY KEY,
    nama_mobil VARCHAR(100) NOT NULL,
    no_plat VARCHAR(20) NOT NULL UNIQUE,
    status ENUM('Tersedia','Tidak Tersedia','Dalam Perbaikan') DEFAULT 'Tersedia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed default cars if table empty
$chk_master = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM master_mobil"));
if ($chk_master['cnt'] == 0) {
    mysqli_query($conn, "INSERT INTO master_mobil (nama_mobil, no_plat, status) VALUES
        ('Toyota Avanza','B 1482 SFZ','Tersedia'),
        ('Honda CR-V','B 2930 KLS','Tersedia'),
        ('Mitsubishi Xpander','B 8831 UY','Tersedia'),
        ('Suzuki Ertiga','B 7741 BCD','Tersedia')");
}

// Load all cars with real-time availability for the booking form
// A car is 'sedang_dipinjam' if there's an active booking whose end time has not passed yet
$today = date('Y-m-d');
$q_cars = mysqli_query($conn, "
    SELECT m.*,
    CASE 
        WHEN m.status = 'Tidak Tersedia' OR m.status = 'Dalam Perbaikan' THEN 1 
        WHEN EXISTS (
            SELECT 1 FROM peminjaman_mobil p 
            WHERE CONCAT(m.nama_mobil,' (',m.no_plat,')') = p.mobil 
            AND p.status IN ('Menunggu', 'Disetujui') 
            AND CONCAT(p.tgl_selesai, ' ', p.jam_selesai) >= NOW()
        ) THEN 1
        ELSE 0 
    END AS sedang_dipinjam
    FROM master_mobil m ORDER BY m.nama_mobil
");
$all_cars = [];
if ($q_cars) {
    while ($rc = mysqli_fetch_assoc($q_cars)) $all_cars[] = $rc;
}

// Handle New Booking Request
if (isset($_POST['submit_booking'])) {
    $mobil     = mysqli_real_escape_string($conn, $_POST['mobil']);
    $tgl_mulai = mysqli_real_escape_string($conn, $_POST['tgl_mulai']);
    $tgl_selesai = mysqli_real_escape_string($conn, $_POST['tgl_selesai']);
    $jam_mulai = mysqli_real_escape_string($conn, $_POST['jam_mulai']);
    $jam_selesai = mysqli_real_escape_string($conn, $_POST['jam_selesai']);
    $keperluan = mysqli_real_escape_string($conn, $_POST['keperluan'] ?? '');   // dropdown
    if ($keperluan === 'Keperluan Kantor Lainnya' && !empty($_POST['detail_keperluan_lainnya'])) {
        $detail_lain = mysqli_real_escape_string($conn, $_POST['detail_keperluan_lainnya']);
        $keperluan = "Keperluan Kantor Lainnya ($detail_lain)";
    }
    $tujuan    = mysqli_real_escape_string($conn, $_POST['tujuan'] ?? ''); // free text
    $km_awal   = intval($_POST['km_awal'] ?? 0);
    $bensin_awal = intval($_POST['bensin_awal'] ?? 0);
    
    // Validasi input
    if (empty($mobil) || empty($tgl_mulai) || empty($tgl_selesai) || empty($jam_mulai) || empty($jam_selesai) || empty($keperluan) || empty($tujuan) || empty($km_awal) || empty($bensin_awal)) {
        $message = "Semua kolom input wajib diisi!";
        $message_type = "danger";
    } elseif ($tgl_mulai > $tgl_selesai) {
        $message = "Tanggal mulai tidak boleh melebihi tanggal selesai!";
        $message_type = "danger";
    } elseif ($tgl_mulai == $tgl_selesai && $jam_mulai >= $jam_selesai) {
        $message = "Jam mulai harus sebelum jam selesai pada hari yang sama!";
        $message_type = "danger";
    } else {
        // Cek apakah mobil sudah dibooking oleh orang lain pada rentang tanggal & jam yang SAMA (Status Disetujui / Menunggu)
        $start_dt = "$tgl_mulai $jam_mulai";
        $end_dt   = "$tgl_selesai $jam_selesai";
        $query_overlap = "SELECT * FROM peminjaman_mobil 
                          WHERE mobil = '$mobil' 
                          AND status IN ('Disetujui', 'Menunggu') 
                          AND CONCAT(tgl_mulai, ' ', jam_mulai) < '$end_dt' 
                          AND CONCAT(tgl_selesai, ' ', jam_selesai) > '$start_dt'";
        $res_overlap = mysqli_query($conn, $query_overlap);
        
        if (mysqli_num_rows($res_overlap) > 0) {
            $overlap_data = mysqli_fetch_assoc($res_overlap);
            $pinjam_oleh = $overlap_data['nama_emp'];
            $status_lap  = $overlap_data['status'];
            $tgl_dari    = date('d/m/Y', strtotime($overlap_data['tgl_mulai']));
            $tgl_sampai  = date('d/m/Y', strtotime($overlap_data['tgl_selesai']));
            
            if ($status_lap == 'Disetujui') {
                $message = "Mobil <strong>$mobil</strong> tidak tersedia — sudah dipinjam oleh <strong>$pinjam_oleh</strong> pada periode $tgl_dari s.d $tgl_sampai.";
            } else {
                $message = "Mobil <strong>$mobil</strong> sedang dalam pengajuan oleh <strong>$pinjam_oleh</strong> pada periode $tgl_dari s.d $tgl_sampai (Masih Menunggu Persetujuan). Silakan pilih mobil lain.";
            }
            $message_type = "danger";
        } else {
            // Determine initial status & auto-approval for 'Pengantaran Barang'
            if ($keperluan === 'Pengantaran Barang') {
                $status_init = 'Disetujui';
                $app_val     = "'Disetujui'";
                $app_by      = "'Sistem (Auto)'";
                $app_at      = "'" . date('Y-m-d H:i:s') . "'";
            } else {
                $status_init = 'Menunggu';
                $app_val     = "NULL";
                $app_by      = "NULL";
                $app_at      = "NULL";
            }

            // Insert data baru — simpan keperluan (dropdown) + tujuan (textarea) + km_awal & bensin_awal
            $query_insert = "INSERT INTO peminjaman_mobil (npp, nama_emp, mobil, tgl_mulai, tgl_selesai, jam_mulai, jam_selesai, keperluan, tujuan, km_awal, bensin_awal, status, approve_hr, approve_hr_by, approve_hr_at, approve_ga, approve_ga_by, approve_ga_at) 
                             VALUES ('$active_npp', '$active_name', '$mobil', '$tgl_mulai', '$tgl_selesai', '$jam_mulai', '$jam_selesai', '$keperluan', '$tujuan', $km_awal, $bensin_awal, '$status_init', $app_val, $app_by, $app_at, $app_val, $app_by, $app_at)";
            
            if (mysqli_query($conn, $query_insert)) {
                if ($status_init === 'Disetujui') {
                    $message = "Pengajuan peminjaman mobil untuk Pengantaran Barang berhasil disimpan dan disetujui secara otomatis!";
                } else {
                    $message = "Pengajuan peminjaman mobil berhasil dikirim!";
                }
                $message_type = "success";
            } else {
                $message = "Gagal mengirim pengajuan: " . mysqli_error($conn);
                $message_type = "danger";
            }
        }
    }
}

// Handle Delete/Cancel Booking by User
if (isset($_POST['hapus_booking'])) {
    $del_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
    // Pastikan hanya bisa dihapus jika statusnya masih "Menunggu" dan milik user login
    $q_cek = mysqli_query($conn, "SELECT status FROM peminjaman_mobil WHERE id='$del_id' AND npp='$active_npp'");
    if (mysqli_num_rows($q_cek) > 0) {
        $row_cek = mysqli_fetch_assoc($q_cek);
        if ($row_cek['status'] == 'Menunggu') {
            mysqli_query($conn, "DELETE FROM peminjaman_mobil WHERE id='$del_id'");
            $message = "Pengajuan peminjaman mobil berhasil dibatalkan dan dihapus.";
            $message_type = "success";
        } else {
            $message = "Gagal membatalkan. Pengajuan sudah diproses.";
            $message_type = "danger";
        }
    }
}

// Fetch all bookings for the calendar
$bookings = [];
$query_bookings = "SELECT b.*, IFNULL(e.nama_emp, b.nama_emp) AS nama_emp, e.telp_emp FROM peminjaman_mobil b 
                   LEFT JOIN employee e ON b.npp = e.npp 
                   WHERE b.status IN ('Disetujui', 'Menunggu') 
                   ORDER BY b.tgl_mulai ASC, b.jam_mulai ASC";
$res_bookings = mysqli_query($conn, $query_bookings);
if ($res_bookings) {
    while ($row = mysqli_fetch_assoc($res_bookings)) {
        $bookings[] = [
            'id' => $row['id'],
            'nama_emp' => $row['nama_emp'],
            'mobil' => $row['mobil'],
            'tgl_mulai' => $row['tgl_mulai'],
            'tgl_selesai' => $row['tgl_selesai'],
            'jam_mulai' => substr($row['jam_mulai'], 0, 5),
            'jam_selesai' => substr($row['jam_selesai'], 0, 5),
            'keperluan' => $row['keperluan'],
            'status' => $row['status'],
            'km_awal' => $row['km_awal'],
            'bensin_awal' => $row['bensin_awal']
        ];
    }
}

// Fetch bookings of active user
$my_bookings = [];
$query_my = "SELECT * FROM peminjaman_mobil WHERE npp = '$active_npp' ORDER BY created_at DESC";
$res_my = mysqli_query($conn, $query_my);
if ($res_my) {
    while ($row = mysqli_fetch_assoc($res_my)) {
        $my_bookings[] = $row;
    }
}

// Fetch pending approvals based on role
$pending_approvals = [];
$pending_count = 0;
if ($is_approver) {
    // Both HR and GA see all requests, sorted with 'Menunggu' first
    $query_pending = "SELECT b.*, IFNULL(e.nama_emp, b.nama_emp) AS nama_emp, e.telp_emp FROM peminjaman_mobil b 
                      LEFT JOIN employee e ON b.npp = e.npp 
                      ORDER BY CASE WHEN b.status = 'Menunggu' THEN 1 ELSE 2 END, b.created_at DESC";
    $res_pending = mysqli_query($conn, $query_pending);
    if ($res_pending) {
        while ($row = mysqli_fetch_assoc($res_pending)) {
            $pending_approvals[] = $row;
            if ($row['status'] == 'Menunggu') {
                $pending_count++;
            }
        }
    }
}

function phpRenderFuelBar($val) {
    $val = intval($val);
    if ($val === 0) return '<span class="text-muted">Tidak ada data</span>';
    $color = '#34c759'; // green
    if ($val <= 2) {
        $color = '#ff3b30'; // red
    } elseif ($val <= 5) {
        $color = '#ff9500'; // orange
    }
    $html = '<div style="display: inline-flex; gap: 2px; align-items: center; background: #e5e5ea; padding: 2px; border-radius: 4px; width: 60px; height: 10px; vertical-align: middle; margin-right: 4px;">';
    for ($i = 1; $i <= 8; $i++) {
        $activeColor = $i <= $val ? $color : '#c7c7cc';
        $html .= '<div style="width: 5px; height: 6px; background: ' . $activeColor . '; border-radius: 1px;"></div>';
    }
    $html .= '</div> <span style="font-size: 10px; font-weight: 700; color: ' . $color . '; vertical-align: middle;">' . $val . '/8 Bar</span>';
    return $html;
}

// Laporan Pemakaian: Fetch all approved trips chronologically (supports filtering by mobil)
$report_trips = [];
$is_report_allowed = ($active_npp === '26020216');

if ($is_report_allowed) {
    $filter_mobil = isset($_GET['filter_mobil']) ? mysqli_real_escape_string($conn, $_GET['filter_mobil']) : '';
    $where_clause = "WHERE status = 'Disetujui'";
    if ($filter_mobil !== '') {
        $where_clause .= " AND mobil = '$filter_mobil'";
    }

    $q_rep = mysqli_query($conn, "
        SELECT * FROM peminjaman_mobil 
        $where_clause 
        ORDER BY mobil ASC, tgl_mulai ASC, jam_mulai ASC
    ");
    if ($q_rep) {
        while ($r = mysqli_fetch_assoc($q_rep)) {
            $report_trips[] = $r;
        }
    }

    // Calculate differences per vehicle chronologically
    for ($idx = 0; $idx < count($report_trips); $idx++) {
        $current = &$report_trips[$idx];
        $current_car = $current['mobil'];
        
        // Find next trip of the SAME car
        $next = null;
        for ($j = $idx + 1; $j < count($report_trips); $j++) {
            if ($report_trips[$j]['mobil'] === $current_car) {
                $next = $report_trips[$j];
                break;
            }
        }
        
        if ($next) {
            $current['km_tempuh'] = $next['km_awal'] - $current['km_awal'];
            $current['bensin_terpakai'] = $current['bensin_awal'] - $next['bensin_awal'];
        } else {
            $current['km_tempuh'] = null;
            $current['bensin_terpakai'] = null;
        }
    }

    // Sort report_trips descending (latest first) by tgl_mulai and jam_mulai
    usort($report_trips, function($a, $b) {
        $dateA = $a['tgl_mulai'] . ' ' . $a['jam_mulai'];
        $dateB = $b['tgl_mulai'] . ' ' . $b['jam_mulai'];
        return strcmp($dateB, $dateA);
    });
}

$pagedesc = "Peminjaman Mobil";
?>

<style>
/* Custom styling for premium iOS look */
.ios-card {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
    border: 1px solid #f2f2f7;
    padding: 24px;
    margin-bottom: 25px;
    transition: all 0.3s ease;
}
.ios-calendar-container {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    color: #1c1c1e;
}
.ios-calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.ios-calendar-header h3 {
    font-size: 20px;
    font-weight: 700;
    margin: 0;
    color: #1c1c1e;
}
.ios-calendar-nav-btn {
    background: #f2f2f7;
    border: none;
    border-radius: 50%;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #007aff;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s;
}
.ios-calendar-nav-btn:hover {
    background: #e5e5ea;
    transform: scale(1.05);
}
.ios-calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    text-align: center;
    margin-bottom: 12px;
    border-bottom: 1px solid #f2f2f7;
    padding-bottom: 8px;
}
.ios-calendar-weekday {
    font-size: 11px;
    font-weight: 600;
    color: #8e8e93;
    text-transform: uppercase;
}
.ios-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 1px;
    background: #e5e5ea;
    border: 1px solid #e5e5ea;
    border-radius: 12px;
    overflow: hidden;
}
.ios-calendar-day {
    min-height: 110px;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: flex-start;
    padding: 6px 4px;
    position: relative;
    cursor: pointer;
    transition: background 0.2s;
    min-width: 0;
}
.ios-calendar-day:hover {
    background: #f2f2f7;
}
.ios-calendar-day.other-month {
    background: #f8f8fa;
    color: #c7c7cc;
}
.ios-calendar-day.today {
    background: rgba(0, 122, 255, 0.03);
}
.ios-calendar-day.today .day-number {
    background: #007aff;
    color: #ffffff;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ios-calendar-day.selected {
    background: rgba(0, 122, 255, 0.08);
}
.day-number {
    font-size: 13px;
    font-weight: 700;
    color: #1c1c1e;
    margin-bottom: 4px;
    align-self: flex-start;
    padding-left: 4px;
}
.other-month .day-number {
    color: #c7c7cc;
}

.ios-event-bar {
    font-size: 11px;
    font-weight: 500;
    padding: 4px 6px;
    border-radius: 4px;
    margin-top: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
    max-width: 100%;
    line-height: 1.3;
}
.ios-event-bar.disetujui {
    background: rgba(52, 199, 89, 0.15);
    color: #1a6b2d;
    border-left: 4px solid #34c759;
}
.ios-event-bar.menunggu {
    background: rgba(255, 149, 0, 0.15);
    color: #995c00;
    border-left: 4px solid #ff9500;
}
.event-car {
    font-weight: 700;
}
.event-name {
    font-weight: 400;
}

@media (max-width: 767px) {
    .ios-calendar-day {
        min-height: 75px;
        padding: 4px 2px;
    }
    .ios-event-bar {
        font-size: 8px;
        padding: 2px 3px;
    }
    .day-number {
        font-size: 11px;
    }
    .ios-calendar-container {
        padding: 10px;
    }
    .ios-card {
        padding: 12px;
        margin-bottom: 12px;
    }
}

/* Event List */
.ios-event-list-title {
    font-size: 16px;
    font-weight: 600;
    color: #8e8e93;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f2f2f7;
}
.ios-event-card {
    display: flex;
    padding: 12px;
    background: #f8f8fa;
    border-radius: 12px;
    margin-bottom: 10px;
    border-left: 4px solid #8e8e93;
    transition: all 0.2s;
}
.ios-event-card.status-disetujui {
    border-left-color: #34c759;
}
.ios-event-card.status-menunggu {
    border-left-color: #ff9500;
}
.ios-event-time {
    width: 65px;
    font-size: 13px;
    font-weight: 600;
    color: #1c1c1e;
}
.ios-event-time-end {
    font-size: 11px;
    color: #8e8e93;
    font-weight: 400;
}
.ios-event-body {
    flex-grow: 1;
    margin-left: 10px;
}
.ios-event-title {
    font-size: 14px;
    font-weight: 600;
    color: #1c1c1e;
    margin: 0 0 3px 0;
}
.ios-event-desc {
    font-size: 12px;
    color: #8e8e93;
    margin: 0;
}

/* Floating button */
.ios-btn-primary {
    background: #007aff;
    color: white;
    border: none;
    border-radius: 12px;
    padding: 12px 24px;
    font-weight: 600;
    font-size: 15px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 122, 255, 0.2);
}
.ios-btn-primary:hover {
    background: #0062cc;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 122, 255, 0.3);
    color: white;
    text-decoration: none;
}
.ios-btn-secondary {
    background: #f2f2f7;
    color: #007aff;
    border: none;
    border-radius: 12px;
    padding: 10px 20px;
    font-weight: 600;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    cursor: pointer;
}
.ios-btn-secondary:hover {
    background: #e5e5ea;
    text-decoration: none;
}

/* Modal and inputs */
.ios-modal-header {
    border-bottom: 1px solid #f2f2f7;
    padding-bottom: 15px;
}
.ios-modal-title {
    font-size: 18px;
    font-weight: 700;
    color: #1c1c1e;
    margin: 0;
}
.ios-form-group {
    margin-bottom: 18px;
}
.ios-form-label {
    font-size: 13px;
    font-weight: 600;
    color: #1c1c1e;
    margin-bottom: 6px;
    display: block;
}
.ios-input {
    width: 100%;
    padding: 11px 16px;
    background: #f2f2f7;
    border: 1px solid transparent;
    border-radius: 10px;
    font-size: 14px;
    color: #1c1c1e;
    transition: all 0.2s;
}
.ios-input:focus {
    background: #ffffff;
    border-color: #007aff;
    outline: none;
    box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.15);
}

/* Badge status */
.ios-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}
.ios-badge.disetujui {
    background: rgba(52, 199, 89, 0.15);
    color: #34c759;
}
.ios-badge.menunggu {
    background: rgba(255, 149, 0, 0.15);
    color: #ff9500;
}
.ios-badge.ditolak {
    background: rgba(255, 59, 48, 0.15);
    color: #ff3b30;
}
/* Fuel Bar Custom Styles */
.bensin-bar-segment.active-low, .edit-bensin-bar-segment.active-low {
    background-color: #ff3b30 !important;
    box-shadow: 0 0 6px rgba(255, 59, 48, 0.4);
}
.bensin-bar-segment.active-mid, .edit-bensin-bar-segment.active-mid {
    background-color: #ff9500 !important;
    box-shadow: 0 0 6px rgba(255, 149, 0, 0.4);
}
.bensin-bar-segment.active-high, .edit-bensin-bar-segment.active-high {
    background-color: #34c759 !important;
    box-shadow: 0 0 6px rgba(52, 199, 89, 0.4);
}
</style>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header" style="font-weight: 800; color: #1c1c1e; border-bottom: none; margin-bottom: 10px;">Peminjaman Mobil</h1>
        </div>
    </div>

    <?php if ($is_report_allowed): ?>
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-xs-12">
            <div style="display: flex; gap: 10px; border-bottom: 1px solid #e5e5ea; padding-bottom: 15px;">
                <a href="?view=calendar" class="ios-btn-secondary" style="background: <?php echo (!isset($_GET['view']) || $_GET['view'] !== 'report') ? '#007aff; color: white;' : '#f2f2f7; color: #007aff;'; ?> padding: 8px 16px; border-radius: 8px; font-weight: 600; text-decoration: none;">
                    <i class="fa fa-calendar"></i> &nbsp;Kalender &amp; Pengajuan
                </a>
                <a href="?view=report" class="ios-btn-secondary" style="background: <?php echo (isset($_GET['view']) && $_GET['view'] === 'report') ? '#007aff; color: white;' : '#f2f2f7; color: #007aff;'; ?> padding: 8px 16px; border-radius: 8px; font-weight: 600; text-decoration: none;">
                    <i class="fa fa-file-text-o"></i> &nbsp;Laporan Pemakaian
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alert Message -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissable" style="border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <i class="fa fa-info-circle"></i> &nbsp;<?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($is_report_allowed && isset($_GET['view']) && $_GET['view'] === 'report'): ?>
        <!-- Laporan Pemakaian View -->
        <div class="row">
            <div class="col-xs-12">
                <div class="ios-card">
                    <h4 style="font-weight: 700; color: #1c1c1e; margin-bottom: 20px;"><i class="fa fa-bar-chart" style="color:#007aff;"></i> Laporan Penggunaan &amp; Pemakaian Kendaraan</h4>
                    
                    <!-- Filter & Export Controls -->
                    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px; margin-bottom: 20px; background: #f8f8fa; padding: 15px; border-radius: 12px; border: 1px solid #e5e5ea;">
                        <form method="get" style="display: flex; align-items: center; gap: 10px; margin: 0;">
                            <input type="hidden" name="view" value="report">
                            <label for="filter_mobil" style="font-weight: 600; color: #3a3a3c; margin: 0; font-size: 13px;">Filter Mobil:</label>
                            <select name="filter_mobil" id="filter_mobil" class="form-control input-sm" style="width: 220px; height: 32px; border-radius: 8px;" onchange="this.form.submit()">
                                <option value="">-- Semua Mobil --</option>
                                <?php foreach ($all_cars as $car): 
                                    $car_label = $car['nama_mobil'] . ' (' . $car['no_plat'] . ')';
                                    $sel = (isset($_GET['filter_mobil']) && $_GET['filter_mobil'] === $car_label) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo htmlspecialchars($car_label); ?>" <?php echo $sel; ?>>
                                        <?php echo htmlspecialchars($car_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        
                        <div>
                            <?php 
                            $prefix = isset($path_prefix) ? $path_prefix : '';
                            $export_url = $prefix . 'peminjaman_mobil_export_xls.php?filter_mobil=' . urlencode(isset($_GET['filter_mobil']) ? $_GET['filter_mobil'] : '');
                            ?>
                            <a href="<?php echo $export_url; ?>" class="btn btn-success btn-sm" style="background-color: #34c759 !important; border-color: #34c759 !important; border-radius: 8px; font-weight: 600; padding: 6px 16px;">
                                <i class="fa fa-file-excel-o"></i> &nbsp;Export Excel
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover" id="dataTables-report">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center">No</th>
                                    <th width="15%">Tanggal Pinjam</th>
                                    <th width="15%">Peminjam</th>
                                    <th width="20%">Mobil</th>
                                    <th width="10%" class="text-center">KM Awal</th>
                                    <th width="10%" class="text-center">Bensin Awal</th>
                                    <th width="10%" class="text-center">KM Tempuh</th>
                                    <th width="10%" class="text-center">Bensin Terpakai</th>
                                    <th width="15%">Tujuan / Keperluan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($report_trips as $trip): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td data-order="<?php echo $trip['tgl_mulai'] . ' ' . $trip['jam_mulai']; ?>">
                                            <?php echo date('d/m/Y', strtotime($trip['tgl_mulai'])); ?><br>
                                            <small class="text-muted"><?php echo substr($trip['jam_mulai'], 0, 5) . ' - ' . substr($trip['jam_selesai'], 0, 5); ?></small>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($trip['nama_emp']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($trip['mobil']); ?></td>
                                        <td class="text-center"><?php echo number_format($trip['km_awal'], 0, ',', '.'); ?> KM</td>
                                        <td class="text-center"><?php echo phpRenderFuelBar($trip['bensin_awal']); ?></td>
                                        <td class="text-center">
                                            <?php 
                                            if ($trip['km_tempuh'] !== null) {
                                                if ($trip['km_tempuh'] >= 0) {
                                                    echo '<strong>' . number_format($trip['km_tempuh'], 0, ',', '.') . ' KM</strong>';
                                                } else {
                                                    echo '<span class="text-danger" title="Input salah (KM turun)"><i class="fa fa-warning"></i> ' . number_format($trip['km_tempuh'], 0, ',', '.') . ' KM</span>';
                                                }
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            if ($trip['bensin_terpakai'] !== null) {
                                                if ($trip['bensin_terpakai'] > 0) {
                                                    echo '<strong>' . $trip['bensin_terpakai'] . ' Bar</strong>';
                                                } elseif ($trip['bensin_terpakai'] < 0) {
                                                    echo '<span class="text-success" style="font-weight:bold;"><i class="fa fa-tint"></i> Refuel (+' . abs($trip['bensin_terpakai']) . ' Bar)</span>';
                                                } else {
                                                    echo '<span class="text-muted">0 Bar</span>';
                                                }
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($trip['keperluan']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($trip['tujuan'] ?? '-'); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>

    <?php if (!$is_approval_page): ?>
    <div class="row" style="margin-bottom: 15px;">
        <div class="col-xs-12 col-md-12">
            <!-- Action Button to Request -->
            <div class="ios-card text-center" style="padding: 20px 24px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <h4 style="font-weight: 700; color: #1c1c1e; margin-bottom: 10px;">Ingin Menggunakan Mobil?</h4>
                <p class="text-muted" style="font-size: 13px; margin-bottom: 15px;">Silahkan cek ketersediaan tanggal pada kalender, lalu ajukan peminjaman dengan menekan tombol dibawah.</p>
                <button class="ios-btn-primary" style="width: auto; padding: 10px 30px; font-size: 15px;" data-toggle="modal" data-target="#bookingModal">
                    <i class="fa fa-plus-circle"></i> Buat Pengajuan Baru
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- iOS Calendar (Only show if not approval page) -->
        <?php if (!$is_approval_page): ?>
        <div class="col-xs-12 col-md-12">
            <div class="ios-card">
                <div class="ios-calendar-container">
                    <div class="ios-calendar-header">
                        <h3 id="calendarMonthYear">Mei 2026</h3>
                        <div style="display: flex; gap: 8px;">
                            <button class="ios-calendar-nav-btn" onclick="prevMonth()"><i class="fa fa-chevron-left"></i></button>
                            <button class="ios-calendar-nav-btn" onclick="nextMonth()"><i class="fa fa-chevron-right"></i></button>
                        </div>
                    </div>
                    
                    <div class="ios-calendar-weekdays">
                        <div class="ios-calendar-weekday">Min</div>
                        <div class="ios-calendar-weekday">Sen</div>
                        <div class="ios-calendar-weekday">Sel</div>
                        <div class="ios-calendar-weekday">Rab</div>
                        <div class="ios-calendar-weekday">Kam</div>
                        <div class="ios-calendar-weekday">Jum</div>
                        <div class="ios-calendar-weekday">Sab</div>
                    </div>
                    
                    <div class="ios-calendar-grid" id="calendarDays">
                        <!-- Filled by JS -->
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div> <!-- Close top row -->

    <?php if (!$is_approval_page): ?>
    <!-- BOTTOM SECTION: My Bookings -->
    <div class="row" style="margin-top: 15px;">
    <?php endif; ?>


            <!-- Persetujuan/Approval Tab -->
            <?php if ($is_approver && ($is_managerhr || $is_ga || $active_npp == '26020216') && $is_approval_page): ?>
                <div class="row"><div class="col-xs-12 col-md-12">
                <?php
                    if ($active_npp == '26020216') {
                        $panel_title = 'Persetujuan Peminjaman Mobil';
                        $panel_color = '#007aff';
                    } else {
                        $panel_title = $is_managerhr ? 'Persetujuan Manager HR' : 'Persetujuan GA';
                        $panel_color = $is_managerhr ? '#007aff' : '#34c759';
                    }
                ?>
                <div class="panel panel-default" style="margin-top: 15px;">
                    <div class="panel-heading">
                        <b><i class="fa fa-check-square-o"></i> <?php echo $panel_title; ?></b>
                        <?php if ($pending_count > 0): ?>
                            <span class="badge" style="background-color: #ff9500; float: right;"><?php echo $pending_count; ?> Menunggu</span>
                        <?php endif; ?>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="dataTables-approval">
                                <thead>
                                    <tr>
                                        <th width="5%" class="text-center">No</th>
                                        <th width="15%">Nama</th>
                                        <th width="25%">Keterangan</th>
                                        <th width="20%">Tanggal Peminjaman</th>
                                        <th width="10%" class="text-center">Durasi</th>
                                        <th width="25%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                        <?php $no = 1; foreach ($pending_approvals as $pending): ?>
                                            <?php 
                                                // Calculate duration in days
                                                $start = new DateTime($pending['tgl_mulai']);
                                                $end = new DateTime($pending['tgl_selesai']);
                                                $diff = $start->diff($end);
                                                $durasi = $diff->days + 1;
                                            ?>
                                            <tr>
                                                <td class="text-center"><?php echo $no++; ?></td>
                                                <td><?php echo $pending['nama_emp']; ?></td>
                                                <td>
                                                    <strong>Mobil:</strong> <?php echo $pending['mobil']; ?><br>
                                                    <strong>KM Awal:</strong> <?php echo $pending['km_awal'] ? number_format($pending['km_awal'], 0, ',', '.') . ' KM' : '-'; ?><br>
                                                    <strong>Bensin Awal:</strong> <?php echo phpRenderFuelBar($pending['bensin_awal']); ?><br>
                                                    <strong>Keperluan:</strong> <?php echo htmlspecialchars($pending['keperluan']); ?><br>
                                                    <strong>Tujuan:</strong> <?php echo htmlspecialchars($pending['tujuan'] ?? '-'); ?>
                                                </td>
                                                <td data-order="<?php echo $pending['tgl_mulai'] . ' ' . $pending['jam_mulai']; ?>">
                                                    <?php echo date('d/m/Y', strtotime($pending['tgl_mulai'])) . ' - ' . date('d/m/Y', strtotime($pending['tgl_selesai'])); ?><br>
                                                    <small class="text-muted"><i class="fa fa-clock-o"></i> <?php echo substr($pending['jam_mulai'], 0, 5) . ' s/d ' . substr($pending['jam_selesai'], 0, 5); ?></small>
                                                </td>
                                                <td class="text-center"><?php echo $durasi; ?> Hari</td>
                                                <td class="text-center">
                                                    <?php if ($pending['status'] == 'Menunggu'): ?>
                                                        <form method="post" style="display: block; margin-bottom: 0;">
                                                            <input type="hidden" name="booking_id" value="<?php echo $pending['id']; ?>">
                                                            <input type="hidden" name="action_approval" value="approve">
                                                            <div style="display: flex; gap: 4px; justify-content: center; flex-wrap: wrap;">
                                                                <button type="submit" class="btn btn-success btn-xs" style="border-radius: 4px; padding: 4px 8px;" onclick="return confirm('Apakah Anda yakin menyetujui peminjaman ini?')"><i class="fa fa-check"></i> Setujui</button>
                                                                <button type="button" class="btn btn-danger btn-xs" style="border-radius: 4px; padding: 4px 8px;" onclick="openRejectModal('<?php echo $pending['id']; ?>')"><i class="fa fa-times"></i> Tolak</button>
                                                                <button type="button" class="btn btn-info btn-xs" style="border-radius: 4px; padding: 4px 8px;" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($pending), ENT_QUOTES, 'UTF-8'); ?>)"><i class="fa fa-pencil"></i> Edit</button>
                                                            </div>
                                                        </form>
                                                    <?php elseif ($pending['status'] == 'Disetujui'): ?>
                                                        <span class="label label-success" style="font-size: 11px; padding: 4px 8px; display: inline-block;"><i class="fa fa-check-circle"></i> Disetujui</span>
                                                        <?php if (!empty($pending['approve_hr']) || !empty($pending['approve_ga'])): ?>
                                                            <br><small class="text-muted" style="margin-top: 4px; display: inline-block; font-size: 11px;">
                                                                Oleh: <?php echo !empty($pending['approve_hr']) ? 'HR (' . htmlspecialchars($pending['approve_hr']) . ')' : 'GA (' . htmlspecialchars($pending['approve_ga']) . ')'; ?>
                                                            </small>
                                                        <?php endif; ?>

                                                        <!-- Post-approval: Switch Mobil, Edit, or Batalkan (Always available) -->
                                                        <div style="margin-top: 8px; background: #f8f8fa; padding: 8px; border-radius: 8px; border: 1px solid #e5e5ea;">
                                                            <form method="post" style="margin-bottom: 6px;">
                                                                <input type="hidden" name="booking_id" value="<?php echo $pending['id']; ?>">
                                                                <input type="hidden" name="action_switch" value="switch">
                                                                <div style="display: flex; gap: 4px;">
                                                                    <?php
                                                                        $available_cars = [];
                                                                        foreach ($all_cars as $car) {
                                                                            $carStr = $car['nama_mobil'] . ' (' . $car['no_plat'] . ')';
                                                                            $q_overlap = mysqli_query($conn, "SELECT 1 FROM peminjaman_mobil 
                                                                                                              WHERE mobil = '$carStr' 
                                                                                                              AND status IN ('Disetujui', 'Menunggu') 
                                                                                                              AND id != '{$pending['id']}'
                                                                                                              AND '{$pending['tgl_mulai']} {$pending['jam_mulai']}' < CONCAT(tgl_selesai, ' ', jam_selesai)
                                                                                                              AND '{$pending['tgl_selesai']} {$pending['jam_selesai']}' > CONCAT(tgl_mulai, ' ', jam_mulai)");
                                                                            if (mysqli_num_rows($q_overlap) == 0 && $car['status'] != 'Dalam Perbaikan' && $carStr != $pending['mobil']) {
                                                                                $available_cars[] = $carStr;
                                                                            }
                                                                        }
                                                                    ?>
                                                                    <select name="new_mobil" class="form-control input-sm" style="width: 100%; font-size: 11px; height: 28px; padding: 2px 6px;" title="Pilih mobil pengganti untuk pengajuan ini">
                                                                        <option value="">-- Ganti Unit Mobil --</option>
                                                                        <?php foreach ($available_cars as $avail_car): ?>
                                                                            <option value="<?php echo htmlspecialchars($avail_car); ?>"><?php echo htmlspecialchars($avail_car); ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <button type="submit" class="btn btn-primary btn-xs" style="padding: 4px 8px; font-size: 11px; white-space: nowrap;" onclick="return confirm('Lakukan pergantian mobil untuk pengajuan ini?')"><i class="fa fa-exchange"></i> Ganti</button>
                                                                </div>
                                                            </form>
                                                            <div style="display: flex; gap: 4px; justify-content: center;">
                                                                <button type="button" class="btn btn-info btn-xs" style="border-radius: 4px; padding: 3px 8px; font-size: 11px;" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($pending), ENT_QUOTES, 'UTF-8'); ?>)"><i class="fa fa-pencil"></i> Edit</button>
                                                                <button type="button" class="btn btn-danger btn-xs" style="border-radius: 4px; padding: 3px 8px; font-size: 11px;" onclick="openCancelModal('<?php echo $pending['id']; ?>')"><i class="fa fa-ban"></i> Batalkan</button>
                                                            </div>
                                                        </div>
                                                    <?php elseif ($pending['status'] == 'Dibatalkan'): ?>
                                                        <span class="label label-default" style="font-size: 11px; padding: 4px 8px; display: inline-block; background-color: #8e8e93;"><i class="fa fa-ban"></i> Dibatalkan</span>
                                                        <?php if (!empty($pending['keterangan'])): ?>
                                                            <br><small class="text-muted" style="margin-top: 4px; display: inline-block; font-size: 10px;">
                                                                <?php echo htmlspecialchars($pending['keterangan']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="label label-danger" style="font-size: 11px; padding: 4px 8px; display: inline-block;"><i class="fa fa-times-circle"></i> Ditolak</span>
                                                        <?php if (!empty($pending['keterangan'])): ?>
                                                            <br><small class="text-muted" style="margin-top: 4px; display: inline-block; font-size: 10px;">
                                                                Alasan: <?php echo htmlspecialchars($pending['keterangan']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                </div></div>


            <?php endif; ?>

            <!-- My Bookings List -->
            <?php if (!$is_approval_page): ?>
            <div class="col-xs-12 col-md-12" style="margin-top: 15px;">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <b><i class="fa fa-list"></i> Pengajuan Saya</b>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="dataTables-my">
                                <thead>
                                    <tr>
                                        <th width="5%" class="text-center">No</th>
                                        <th width="20%">Mobil</th>
                                        <th width="25%">Periode Peminjaman</th>
                                        <th width="15%">Waktu</th>
                                        <th width="15%">Keperluan</th>
                                        <th width="10%" class="text-center">Status</th>
                                        <th width="10%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>

                                        <?php $no = 1; foreach ($my_bookings as $my): ?>
                                            <tr>
                                                <td class="text-center"><?php echo $no++; ?></td>
                                                <td>
                                                    <strong><?php echo $my['mobil']; ?></strong><br>
                                                    <small class="text-muted">
                                                        <strong>KM Awal:</strong> <?php echo $my['km_awal'] ? number_format($my['km_awal'], 0, ',', '.') . ' KM' : '-'; ?> | 
                                                        <strong>Bensin:</strong> <?php echo $my['bensin_awal'] ? $my['bensin_awal'] . '/8 Bar' : '-'; ?>
                                                    </small>
                                                </td>
                                                <td data-order="<?php echo $my['tgl_mulai'] . ' ' . $my['jam_mulai']; ?>">
                                                    <i class="fa fa-calendar-o"></i> &nbsp;<?php echo date('d/m/Y', strtotime($my['tgl_mulai'])) . ' - ' . date('d/m/Y', strtotime($my['tgl_selesai'])); ?>
                                                </td>
                                                <td>
                                                    <i class="fa fa-clock-o"></i> &nbsp;<?php echo substr($my['jam_mulai'],0,5) . ' - ' . substr($my['jam_selesai'],0,5); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($my['keperluan']); ?></td>
                                                <td class="text-center">
                                                    <?php if ($my['status'] == 'Menunggu'): ?>
                                                        <span class="label label-warning" style="font-size: 11px; padding: 5px 10px; display: inline-block;"><i class="fa fa-hourglass-half"></i> Menunggu</span>
                                                    <?php elseif ($my['status'] == 'Disetujui'): ?>
                                                        <span class="label label-success" style="font-size: 11px; padding: 5px 10px; display: inline-block;"><i class="fa fa-check-circle"></i> Disetujui</span>
                                                    <?php elseif ($my['status'] == 'Dibatalkan'): ?>
                                                        <span class="label label-default" style="font-size: 11px; padding: 5px 10px; display: inline-block; background-color: #8e8e93;"><i class="fa fa-ban"></i> Dibatalkan</span>
                                                        <?php if (!empty($my['keterangan'])): ?>
                                                            <br><small class="text-muted" style="margin-top: 4px; display: inline-block; font-size: 10px;">
                                                                <strong>Catatan:</strong> <?php echo htmlspecialchars($my['keterangan']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="label label-danger" style="font-size: 11px; padding: 5px 10px; display: inline-block;"><i class="fa fa-times-circle"></i> Ditolak</span>
                                                        <?php if (!empty($my['keterangan'])): ?>
                                                            <br><small class="text-muted" style="margin-top: 4px; display: inline-block; font-size: 10px;">
                                                                <strong>Alasan:</strong> <?php echo htmlspecialchars($my['keterangan']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($my['status'] == 'Menunggu'): ?>
                                                        <div style="display: flex; gap: 4px; justify-content: center;">
                                                            <button type="button" class="btn btn-info btn-xs" style="border-radius: 4px;" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($my), ENT_QUOTES, 'UTF-8'); ?>)">
                                                                <i class="fa fa-pencil"></i> Edit
                                                            </button>
                                                            <button type="button" class="btn btn-danger btn-xs" style="border-radius: 4px;" onclick="openCancelModal('<?php echo $my['id']; ?>')">
                                                                <i class="fa fa-times"></i> Batal
                                                            </button>
                                                        </div>
                                                    <?php elseif ($my['status'] == 'Disetujui'): ?>
                                                        <span class="text-muted" style="font-size: 11px;">-</span>
                                                    <?php else: ?>
                                                        <span class="text-muted" style="font-size: 11px;">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- Close bottom row -->
        <?php endif; ?>

    <?php if ($is_approver): ?>
    <!-- Master Mobil Panel -->
    <div class="row"><div class="col-xs-12 col-md-12">
    <div class="panel panel-default" style="margin-top: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: none;">
        <div class="panel-heading" style="background-color: #ffffff; border-bottom: 1px solid #f2f2f7; padding: 16px 20px; border-radius: 12px 12px 0 0;">
            <b style="font-size: 15px; color: #1c1c1e;"><i class="fa fa-car" style="color: #007aff; margin-right: 6px;"></i> Master Data Kendaraan</b>
        </div>
        <div class="panel-body" style="padding: 20px;">
            <div class="table-responsive">
                <table class="table table-hover" id="dataTables-master" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center" style="border-bottom: 2px solid #f2f2f7; color: #8e8e93; font-weight: 600;">No</th>
                            <th width="25%" style="border-bottom: 2px solid #f2f2f7; color: #8e8e93; font-weight: 600;">Nama Mobil</th>
                            <th width="20%" style="border-bottom: 2px solid #f2f2f7; color: #8e8e93; font-weight: 600;">No. Plat</th>
                            <th width="20%" class="text-center" style="border-bottom: 2px solid #f2f2f7; color: #8e8e93; font-weight: 600;">Status</th>
                            <th width="30%" class="text-center" style="border-bottom: 2px solid #f2f2f7; color: #8e8e93; font-weight: 600;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $q_master = mysqli_query($conn, "SELECT * FROM master_mobil ORDER BY nama_mobil ASC");
                        $nom = 1;
                        while ($rm = mysqli_fetch_assoc($q_master)): 
                            $bg_status = ($rm['status'] == 'Tersedia') ? 'success' : (($rm['status'] == 'Dalam Perbaikan') ? 'danger' : 'warning');
                        ?>
                            <tr>
                                <td class="text-center" style="vertical-align: middle; color: #3a3a3c;"><?php echo $nom++; ?></td>
                                <td style="vertical-align: middle;"><strong style="color: #1c1c1e; font-size: 14px;"><?php echo $rm['nama_mobil']; ?></strong></td>
                                <td style="vertical-align: middle; color: #3a3a3c;"><span style="background: #f2f2f7; padding: 4px 8px; border-radius: 6px; font-family: monospace; font-size: 13px;"><?php echo $rm['no_plat']; ?></span></td>
                                <td class="text-center" style="vertical-align: middle;">
                                    <span class="label label-<?php echo $bg_status; ?>" style="font-size: 11px; padding: 5px 10px; display: inline-block; border-radius: 10px; font-weight: 600; letter-spacing: 0.3px;"><?php echo $rm['status']; ?></span>
                                </td>
                                <td class="text-center" style="vertical-align: middle;">
                                    <form method="post" style="display: flex; align-items: center; justify-content: center; gap: 6px; margin: 0;">
                                        <input type="hidden" name="action_master" value="update_status">
                                        <input type="hidden" name="id_mobil" value="<?php echo $rm['id_mobil']; ?>">
                                        <select name="new_status" class="form-control input-sm" style="width: 140px; font-size: 13px; height: 32px; border-radius: 6px; box-shadow: none; border-color: #d1d1d6;">
                                            <option value="Tersedia" <?php echo ($rm['status'] == 'Tersedia' || $rm['status'] == 'Tidak Tersedia') ? 'selected' : ''; ?>>Tersedia</option>
                                            <option value="Dalam Perbaikan" <?php echo ($rm['status'] == 'Dalam Perbaikan') ? 'selected' : ''; ?>>Dalam Perbaikan</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm" style="padding: 5px 12px; font-size: 12px; border-radius: 6px; font-weight: 600; background-color: #007aff; border-color: #007aff; box-shadow: 0 2px 6px rgba(0,122,255,0.2);"><i class="fa fa-save"></i> Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div></div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal: New Booking Form (Apple Slide Sheet Style) -->
<div class="modal fade" id="bookingModal" tabindex="-1" role="dialog" aria-labelledby="bookingModalLabel" aria-hidden="true" style="font-family: -apple-system, BlinkMacSystemFont, sans-serif;">
    <div class="modal-dialog" role="document" style="max-width: 500px;">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15); overflow: hidden;">
            <div class="modal-header ios-modal-header" style="background: white; border-bottom: 1px solid #f2f2f7; padding: 20px;">
                <h4 class="modal-title ios-modal-title" id="bookingModalLabel"><i class="fa fa-car" style="color: #007aff;"></i> &nbsp;Form Pengajuan Peminjaman</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 24px; font-weight: 300; opacity: 0.5;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" id="formBooking">
                <div class="modal-body" style="padding: 24px;">

                    <!-- CATATAN SYARAT PEMINJAMAN -->
                    <div style="background: #fff8f0; border: 1px solid #ff9500; border-radius: 12px; padding: 14px; margin-bottom: 18px;">
                        <p style="font-weight: 700; color: #ff9500; margin: 0 0 8px 0; font-size: 13px;">
                            <i class="fa fa-exclamation-triangle"></i> &nbsp;Catatan &amp; Syarat Peminjaman
                        </p>
                        <ul style="font-size: 12px; color: #3a3a3c; margin: 0; padding-left: 16px; line-height: 1.8;">
                            <li>Bensin <strong>harus dikembalikan</strong> seperti kondisi awal saat dipinjam.</li>
                            <li>Wajib <strong>menjaga kebersihan</strong> kendaraan selama peminjaman.</li>
                            <li><strong>Tidak merokok</strong> di dalam kendaraan.</li>
                            <li>Bila ada kendala pada kendaraan, segera hubungi <strong>Tim GA</strong> (nomor terkait).</li>
                            <li>Peminjam wajib <strong>memiliki SIM</strong> yang masih berlaku.</li>
                            <li>Bila terjadi kerusakan atau kecelakaan, <strong>karyawan peminjam sepenuhnya bertanggung jawab</strong>.</li>
                        </ul>
                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ffd59e;">
                            <label style="font-size: 12px; color: #3a3a3c; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="chkSetuju" required style="width: 16px; height: 16px;">
                                <span>Saya telah membaca dan <strong>menyetujui</strong> seluruh syarat di atas.</span>
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-6" style="padding-right: 8px;">
                            <div class="ios-form-group">
                                <label class="ios-form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="date" class="ios-input" name="tgl_mulai" required>
                            </div>
                        </div>
                        <div class="col-xs-6" style="padding-left: 8px;">
                            <div class="ios-form-group">
                                <label class="ios-form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                                <input type="date" class="ios-input" name="tgl_selesai" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-6" style="padding-right: 8px;">
                            <div class="ios-form-group">
                                <label class="ios-form-label">Jam Mulai <span class="text-danger">*</span></label>
                                <input type="time" class="ios-input" name="jam_mulai" required>
                            </div>
                        </div>
                        <div class="col-xs-6" style="padding-left: 8px;">
                            <div class="ios-form-group">
                                <label class="ios-form-label">Jam Selesai <span class="text-danger">*</span></label>
                                <input type="time" class="ios-input" name="jam_selesai" required>
                            </div>
                        </div>
                    </div>

                    <div class="ios-form-group">
                        <label class="ios-form-label">Pilih Kendaraan <span class="text-danger">*</span></label>
                        <select class="ios-input" name="mobil" required>
                            <option value="">-- Pilih Mobil --</option>
                            <?php foreach ($all_cars as $car):
                                $car_label = $car['nama_mobil'] . ' (' . $car['no_plat'] . ')';
                                $broken    = ($car['status'] == 'Dalam Perbaikan');
                                $disabled  = $broken ? 'disabled' : '';
                                $info      = $broken ? ' [Dalam Perbaikan]' : '';
                            ?>
                            <option value="<?php echo $car_label; ?>" <?php echo $disabled; ?>>
                                <?php echo $car_label . $info; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ios-form-group">
                        <label class="ios-form-label">Tujuan <span class="text-danger">*</span></label>
                        <textarea class="ios-input" name="tujuan" rows="3" placeholder="Jelaskan tujuan penggunaan kendaraan (lokasi, acara, dll)..." required></textarea>
                    </div>

                    <div class="ios-form-group">
                        <label class="ios-form-label">Keperluan <span class="text-danger">*</span></label>
                        <select class="ios-input" name="keperluan" id="select_keperluan" required>
                            <option value="">-- Pilih Keperluan --</option>
                            <option value="Kunjungan Cabang">Kunjungan Cabang</option>
                            <option value="Pengantaran Barang">Pengantaran Barang</option>
                            <option value="Kunjungan Outlet / Upcountry">Kunjungan Outlet / Upcountry</option>
                            <option value="Keperluan Personal">Keperluan Personal</option>
                            <option value="Keperluan Kantor Lainnya">Keperluan Kantor Lainnya</option>
                        </select>
                    </div>

                    <div class="ios-form-group" id="container_keperluan_lainnya" style="display: none;">
                        <label class="ios-form-label">Detail Keperluan Kantor Lainnya <span class="text-danger">*</span></label>
                        <input type="text" class="ios-input" name="detail_keperluan_lainnya" id="input_detail_keperluan_lainnya" placeholder="Tuliskan detail keperluan kantor lainnya...">
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <div class="ios-form-group">
                                <label class="ios-form-label">Kilometer Awal <span class="text-danger">*</span></label>
                                <input type="number" class="ios-input" name="km_awal" placeholder="Masukkan kilometer awal pada dashboard..." min="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="ios-form-group">
                        <label class="ios-form-label">Bar Bensin Awal <span class="text-danger">*</span></label>
                        <div style="display: flex; gap: 4px; align-items: center; background: #f2f2f7; padding: 12px; border-radius: 12px; border: 1px solid #e5e5ea;">
                            <input type="hidden" name="bensin_awal" id="bensin_awal" required>
                            <span style="font-size: 13px; font-weight: bold; color: #ff3b30; width: 20px; text-align: center;">E</span>
                            <div style="display: flex; flex: 1; gap: 4px;" id="bensinAwalBarContainer">
                                <?php for ($b = 1; $b <= 8; $b++): ?>
                                    <div class="bensin-bar-segment" data-val="<?php echo $b; ?>" style="height: 24px; flex: 1; background: #c7c7cc; border-radius: 4px; cursor: pointer; transition: all 0.2s;"></div>
                                <?php endfor; ?>
                            </div>
                            <span style="font-size: 13px; font-weight: bold; color: #34c759; width: 20px; text-align: center;">F</span>
                        </div>
                        <div style="font-size: 12px; color: #8e8e93; margin-top: 5px; text-align: center;" id="bensinAwalLabel">Silakan klik bar di atas (1 s/d 8 Bar)</div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f2f2f7; padding: 20px; background: #f8f8fa; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="ios-btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="submit_booking" class="ios-btn-primary">Kirim Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Reject Approval Form -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true" style="font-family: -apple-system, BlinkMacSystemFont, sans-serif;">
    <div class="modal-dialog" role="document" style="max-width: 400px;">
        <div class="modal-content" style="border-radius: 20px; border: none; overflow: hidden;">
            <div class="modal-header ios-modal-header" style="background: white; border-bottom: 1px solid #f2f2f7; padding: 20px;">
                <h4 class="modal-title ios-modal-title" id="rejectModalLabel" style="color: #ff3b30;">Tolak Pengajuan</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body" style="padding: 20px;">
                    <input type="hidden" name="booking_id" id="reject_booking_id">
                    <input type="hidden" name="action_approval" value="reject">
                    <div class="ios-form-group">
                        <label class="ios-form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="ios-input" name="keterangan" rows="4" placeholder="Tulis alasan penolakan pengajuan peminjaman mobil ini..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f2f2f7; padding: 15px; background: #f8f8fa; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="ios-btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="ios-btn-primary" style="background: #ff3b30; box-shadow: 0 4px 12px rgba(255, 59, 48, 0.2);">Tolak Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Cancel Booking Form -->
<div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true" style="font-family: -apple-system, BlinkMacSystemFont, sans-serif;">
    <div class="modal-dialog" role="document" style="max-width: 420px;">
        <div class="modal-content" style="border-radius: 20px; border: none; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header ios-modal-header" style="background: white; border-bottom: 1px solid #f2f2f7; padding: 20px;">
                <h4 class="modal-title ios-modal-title" id="cancelModalLabel" style="color: #ff3b30;"><i class="fa fa-times-circle"></i> &nbsp;Batalkan Peminjaman</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 24px; font-weight: 300; opacity: 0.5;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body" style="padding: 20px;">
                    <input type="hidden" name="booking_id" id="cancel_booking_id">
                    <input type="hidden" name="action_cancel" value="cancel">
                    <div style="background: #fff5f5; border: 1px solid #ff3b30; border-radius: 10px; padding: 12px; margin-bottom: 15px; font-size: 12px; color: #c53030;">
                        <i class="fa fa-info-circle"></i> Peminjaman yang dibatalkan akan melepaskan jadwal peminjaman mobil sehingga dapat digunakan untuk peminjaman lainnya.
                    </div>
                    <div class="ios-form-group">
                        <label class="ios-form-label">Alasan Pembatalan <span class="text-danger">*</span></label>
                        <textarea class="ios-input" name="cancel_reason" id="cancel_reason" rows="3" placeholder="Tulis alasan pembatalan (misal: Mobil rusak, Agenda dibatalkan, dialihkan, dll)..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f2f2f7; padding: 15px 20px; background: #f8f8fa; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="ios-btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="ios-btn-primary" style="background: #ff3b30; box-shadow: 0 4px 12px rgba(255, 59, 48, 0.2);"><i class="fa fa-ban"></i> Ya, Batalkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Booking Form (Apple Slide Sheet Style) -->
<div class="modal fade" id="editBookingModal" tabindex="-1" role="dialog" aria-labelledby="editBookingModalLabel" aria-hidden="true" style="font-family: -apple-system, BlinkMacSystemFont, sans-serif;">
    <div class="modal-dialog" role="document" style="max-width: 500px;">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15); overflow: hidden;">
            <div class="modal-header ios-modal-header" style="background: white; border-bottom: 1px solid #f2f2f7; padding: 20px;">
                <h4 class="modal-title ios-modal-title" id="editBookingModalLabel"><i class="fa fa-pencil-square-o" style="color: #007aff;"></i> &nbsp;Edit Data Peminjaman</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 24px; font-weight: 300; opacity: 0.5;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" id="formEditBooking">
                <div class="modal-body" style="padding: 24px;">
                    <input type="hidden" name="booking_id" id="edit_booking_id">
                    <input type="hidden" name="action_edit" value="edit">

                    <div class="row">
                        <div class="col-xs-6" style="padding-right: 8px;">
                            <div class="ios-form-group">
                                <label class="ios-form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="date" class="ios-input" name="tgl_mulai" id="edit_tgl_mulai" required>
                            </div>
                        </div>
                        <div class="col-xs-6" style="padding-left: 8px;">
                            <div class="ios-form-group">
                                <label class="ios-form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                                <input type="date" class="ios-input" name="tgl_selesai" id="edit_tgl_selesai" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-6" style="padding-right: 8px;">
                            <div class="ios-form-group">
                                <label class="ios-form-label">Jam Mulai <span class="text-danger">*</span></label>
                                <input type="time" class="ios-input" name="jam_mulai" id="edit_jam_mulai" required>
                            </div>
                        </div>
                        <div class="col-xs-6" style="padding-left: 8px;">
                            <div class="ios-form-group">
                                <label class="ios-form-label">Jam Selesai <span class="text-danger">*</span></label>
                                <input type="time" class="ios-input" name="jam_selesai" id="edit_jam_selesai" required>
                            </div>
                        </div>
                    </div>

                    <div class="ios-form-group">
                        <label class="ios-form-label">Pilih Kendaraan <span class="text-danger">*</span></label>
                        <select class="ios-input" name="mobil" id="edit_mobil" required>
                            <option value="">-- Pilih Mobil --</option>
                            <?php foreach ($all_cars as $car):
                                $car_label = $car['nama_mobil'] . ' (' . $car['no_plat'] . ')';
                                $broken    = ($car['status'] == 'Dalam Perbaikan');
                                $disabled  = $broken ? 'disabled' : '';
                                $info      = $broken ? ' [Dalam Perbaikan]' : '';
                            ?>
                            <option value="<?php echo $car_label; ?>" <?php echo $disabled; ?>>
                                <?php echo $car_label . $info; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ios-form-group">
                        <label class="ios-form-label">Tujuan <span class="text-danger">*</span></label>
                        <textarea class="ios-input" name="tujuan" id="edit_tujuan" rows="3" placeholder="Jelaskan tujuan penggunaan kendaraan..." required></textarea>
                    </div>

                    <div class="ios-form-group">
                        <label class="ios-form-label">Keperluan <span class="text-danger">*</span></label>
                        <select class="ios-input" name="keperluan" id="edit_select_keperluan" required>
                            <option value="">-- Pilih Keperluan --</option>
                            <option value="Kunjungan Cabang">Kunjungan Cabang</option>
                            <option value="Pengantaran Barang">Pengantaran Barang</option>
                            <option value="Kunjungan Outlet / Upcountry">Kunjungan Outlet / Upcountry</option>
                            <option value="Keperluan Personal">Keperluan Personal</option>
                            <option value="Keperluan Kantor Lainnya">Keperluan Kantor Lainnya</option>
                        </select>
                    </div>

                    <div class="ios-form-group" id="edit_container_keperluan_lainnya" style="display: none;">
                        <label class="ios-form-label">Detail Keperluan Kantor Lainnya <span class="text-danger">*</span></label>
                        <input type="text" class="ios-input" name="detail_keperluan_lainnya" id="edit_input_detail_keperluan_lainnya" placeholder="Tuliskan detail keperluan kantor lainnya...">
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <div class="ios-form-group">
                                <label class="ios-form-label">Kilometer Awal <span class="text-danger">*</span></label>
                                <input type="number" class="ios-input" name="km_awal" id="edit_km_awal" placeholder="Masukkan kilometer awal pada dashboard..." min="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="ios-form-group">
                        <label class="ios-form-label">Bar Bensin Awal <span class="text-danger">*</span></label>
                        <div style="display: flex; gap: 4px; align-items: center; background: #f2f2f7; padding: 12px; border-radius: 12px; border: 1px solid #e5e5ea;">
                            <input type="hidden" name="bensin_awal" id="edit_bensin_awal" required>
                            <span style="font-size: 13px; font-weight: bold; color: #ff3b30; width: 20px; text-align: center;">E</span>
                            <div style="display: flex; flex: 1; gap: 4px;" id="editBensinAwalBarContainer">
                                <?php for ($b = 1; $b <= 8; $b++): ?>
                                    <div class="edit-bensin-bar-segment" data-val="<?php echo $b; ?>" style="height: 24px; flex: 1; background: #c7c7cc; border-radius: 4px; cursor: pointer; transition: all 0.2s;"></div>
                                <?php endfor; ?>
                            </div>
                            <span style="font-size: 13px; font-weight: bold; color: #34c759; width: 20px; text-align: center;">F</span>
                        </div>
                        <div style="font-size: 12px; color: #8e8e93; margin-top: 5px; text-align: center;" id="editBensinAwalLabel">Silakan klik bar di atas (1 s/d 8 Bar)</div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f2f2f7; padding: 20px; background: #f8f8fa; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="ios-btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="ios-btn-primary"><i class="fa fa-save"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Booking Details Modal (Apple Slide Sheet Style) -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1" role="dialog" aria-labelledby="bookingDetailsModalLabel" aria-hidden="true" style="font-family: -apple-system, BlinkMacSystemFont, sans-serif;">
    <div class="modal-dialog" role="document" style="max-width: 450px;">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15); overflow: hidden;">
            <div class="modal-header ios-modal-header" style="background: white; border-bottom: 1px solid #f2f2f7; padding: 20px;">
                <h4 class="modal-title ios-modal-title" id="bookingDetailsModalLabel" style="font-weight: 700; color: #1c1c1e;"><i class="fa fa-info-circle" style="color: #007aff;"></i> &nbsp;Detail Peminjaman</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 24px; font-weight: 300; opacity: 0.5;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 24px; background: #f8f8fa;">
                <div id="modalDetailsDate" style="font-size: 14px; font-weight: 700; color: #8e8e93; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px;"></div>
                <div id="modalDetailsContainer" style="max-height: 400px; overflow-y: auto; padding-right: 2px;">
                    <!-- Dynamic details populated by JS -->
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f2f2f7; padding: 15px 20px; background: white; display: flex; justify-content: flex-end;">
                <button type="button" class="ios-btn-primary" data-dismiss="modal" style="padding: 10px 24px; font-size: 14px; box-shadow: none;">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// JSON booking data passed from PHP
const bookings = <?php echo json_encode($bookings); ?>;

// Get current date context
let currentDate = new Date();
// Format: 2026-05-18 to 2026-05-18
let selectedDate = new Date();

const monthNames = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember"
];

function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    // Set month/year header
    document.getElementById("calendarMonthYear").innerText = `${monthNames[month]} ${year}`;

    // Clear days
    const calendarDays = document.getElementById("calendarDays");
    calendarDays.innerHTML = "";

    // Get first day of the month
    const firstDayIndex = new Date(year, month, 1).getDay();

    // Get last day of current month
    const lastDay = new Date(year, month + 1, 0).getDate();

    // Get last day of previous month
    const prevLastDay = new Date(year, month, 0).getDate();

    // Render empty spaces for previous month
    for (let x = firstDayIndex; x > 0; x--) {
        const dayDiv = document.createElement("div");
        dayDiv.classList.add("ios-calendar-day", "other-month");
        const numDiv = document.createElement("div");
        numDiv.classList.add("day-number");
        numDiv.innerText = prevLastDay - x + 1;
        dayDiv.appendChild(numDiv);
        calendarDays.appendChild(dayDiv);
    }

    // Render current month days
    for (let i = 1; i <= lastDay; i++) {
        const dayDiv = document.createElement("div");
        dayDiv.classList.add("ios-calendar-day");
        
        const numDiv = document.createElement("div");
        numDiv.classList.add("day-number");
        numDiv.innerText = i;
        dayDiv.appendChild(numDiv);

        // Current date check
        const d = new Date(year, month, i);
        const dateStr = formatDate(d);

        // Highlight today
        const today = new Date();
        if (d.getDate() === today.getDate() && d.getMonth() === today.getMonth() && d.getFullYear() === today.getFullYear()) {
            dayDiv.classList.add("today");
        }

        // Highlight selected day
        if (d.getDate() === selectedDate.getDate() && d.getMonth() === selectedDate.getMonth() && d.getFullYear() === selectedDate.getFullYear()) {
            dayDiv.classList.add("selected");
        }

        // Check if there are bookings on this date
        const dayBookings = getBookingsForDate(dateStr);
        if (dayBookings.length > 0) {
            dayBookings.forEach(b => {
                const eventBar = document.createElement("div");
                eventBar.classList.add("ios-event-bar", b.status.toLowerCase());
                
                // Get short car name (e.g. Avanza, CR-V)
                let carShort = b.mobil.split(' ')[0] || b.mobil;
                // Get first name of borrower
                let nameShort = b.nama_emp.split(' ')[0] || b.nama_emp;
                
                eventBar.innerHTML = `<i class="fa fa-car" style="font-size: 9px;"></i> <span class="event-car">${carShort}</span> - <span class="event-name">${nameShort}</span>`;
                // Removed keperluan as per user request
                eventBar.title = `Mobil: ${b.mobil}\nPeminjam: ${b.nama_emp}\nWaktu: ${b.jam_mulai} - ${b.jam_selesai}`;
                dayDiv.appendChild(eventBar);
            });
        }

        // Click event listener
        dayDiv.addEventListener("click", () => {
            selectedDate = new Date(year, month, i);
            renderCalendar();
            
            const selectedDateStr = formatDate(selectedDate);
            const dayBookings = getBookingsForDate(selectedDateStr);
            if (dayBookings.length > 0) {
                showBookingDetails(selectedDateStr);
            }
        });

        calendarDays.appendChild(dayDiv);
    }

    // Fill remaining spaces for next month days (up to 42 total slots grid)
    const totalSlots = firstDayIndex + lastDay;
    const nextDays = 42 - totalSlots;
    for (let j = 1; j <= nextDays; j++) {
        const dayDiv = document.createElement("div");
        dayDiv.classList.add("ios-calendar-day", "other-month");
        const numDiv = document.createElement("div");
        numDiv.classList.add("day-number");
        numDiv.innerText = j;
        dayDiv.appendChild(numDiv);
        calendarDays.appendChild(dayDiv);
    }
}

function prevMonth() {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar();
}

function nextMonth() {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar();
}

// Ensure correct date formatted as local string YYYY-MM-DD
function formatDate(date) {
    const y = date.getFullYear();
    let m = date.getMonth() + 1;
    let d = date.getDate();
    
    if (m < 10) m = `0${m}`;
    if (d < 10) d = `0${d}`;
    
    return `${y}-${m}-${d}`;
}

function getBookingsForDate(dateStr) {
    return bookings.filter(b => {
        return (dateStr >= b.tgl_mulai && dateStr <= b.tgl_selesai);
    });
}

function renderFuelBar(bars) {
    const val = parseInt(bars) || 0;
    if (val === 0) return '<span class="text-muted">Tidak ada data bensin</span>';
    
    let color = '#34c759'; // green
    if (val <= 2) {
        color = '#ff3b30'; // red
    } else if (val <= 5) {
        color = '#ff9500'; // orange
    }
    
    let barHtml = `<div style="display: inline-flex; gap: 2px; align-items: center; background: #e5e5ea; padding: 3px; border-radius: 4px; width: 80px; height: 14px; vertical-align: middle; margin-right: 5px;">`;
    for (let i = 1; i <= 8; i++) {
        const activeColor = i <= val ? color : '#c7c7cc';
        barHtml += `<div style="width: 7px; height: 8px; background: ${activeColor}; border-radius: 1px;"></div>`;
    }
    barHtml += `</div><span style="font-size: 11px; font-weight: 700; color: ${color}; vertical-align: middle;">${val}/8 Bar</span>`;
    return barHtml;
}

function showBookingDetails(dateStr) {
    const dayBookings = getBookingsForDate(dateStr);
    if (dayBookings.length === 0) return;

    const dateObj = new Date(dateStr);
    const formattedDate = `${dateObj.getDate()} ${monthNames[dateObj.getMonth()]} ${dateObj.getFullYear()}`;
    document.getElementById("modalDetailsDate").innerText = formattedDate;

    const container = document.getElementById("modalDetailsContainer");
    container.innerHTML = "";

    dayBookings.forEach(b => {
        const item = document.createElement("div");
        item.style.background = "#ffffff";
        item.style.borderRadius = "14px";
        item.style.padding = "16px";
        item.style.marginBottom = "12px";
        item.style.boxShadow = "0 4px 12px rgba(0,0,0,0.02)";
        item.style.borderLeft = `5px solid ${b.status.toLowerCase() === 'disetujui' ? '#34c759' : '#ff9500'}`;
        item.style.borderTop = "1px solid #f2f2f7";
        item.style.borderRight = "1px solid #f2f2f7";
        item.style.borderBottom = "1px solid #f2f2f7";
        
        item.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span class="ios-badge ${b.status.toLowerCase()}" style="font-size: 11px; padding: 3px 8px;">${b.status}</span>
                <span style="font-size: 12px; font-weight: 600; color: #8e8e93;"><i class="fa fa-clock-o"></i> ${b.jam_mulai} - ${b.jam_selesai}</span>
            </div>
            <h5 style="font-weight: 700; font-size: 15px; margin: 0 0 8px 0; color: #1c1c1e;">${b.mobil}</h5>
            <p style="font-size: 13px; margin: 2px 0; color: #3a3a3c;"><strong>Peminjam:</strong> ${b.nama_emp}</p>
            <p style="font-size: 13px; margin: 4px 0 2px 0; color: #3a3a3c;"><strong>KM Awal:</strong> ${b.km_awal ? Number(b.km_awal).toLocaleString('id-ID') + ' KM' : '-'}</p>
            <div style="margin-top: 4px; font-size: 13px; color: #3a3a3c;">
                <strong>Bensin Awal:</strong> ${renderFuelBar(b.bensin_awal)}
            </div>
        `;
        container.appendChild(item);
    });

    $('#bookingDetailsModal').modal('show');
}

function openRejectModal(bookingId) {
    document.getElementById("reject_booking_id").value = bookingId;
    $('#rejectModal').modal('show');
}

function openCancelModal(bookingId) {
    document.getElementById("cancel_booking_id").value = bookingId;
    document.getElementById("cancel_reason").value = "";
    $('#cancelModal').modal('show');
}

function setEditFuelBar(val) {
    val = parseInt(val) || 0;
    $('#edit_bensin_awal').val(val);
    let colorClass = 'active-high';
    if (val <= 2) {
        colorClass = 'active-low';
    } else if (val <= 5) {
        colorClass = 'active-mid';
    }
    $('.edit-bensin-bar-segment').each(function() {
        const segVal = parseInt($(this).data('val'));
        $(this).removeClass('active-low active-mid active-high');
        if (segVal <= val) {
            $(this).addClass(colorClass);
        }
    });
    if (val > 0) {
        $('#editBensinAwalLabel').html(`<strong>Bensin terpilih: ${val} Bar</strong>`);
    } else {
        $('#editBensinAwalLabel').text('Silakan klik bar di atas (1 s/d 8 Bar)');
    }
}

function openEditModal(data) {
    if (typeof data === 'string') {
        data = JSON.parse(data);
    }
    document.getElementById("edit_booking_id").value = data.id;
    document.getElementById("edit_tgl_mulai").value = data.tgl_mulai;
    document.getElementById("edit_tgl_selesai").value = data.tgl_selesai;
    document.getElementById("edit_jam_mulai").value = (data.jam_mulai || '').substring(0, 5);
    document.getElementById("edit_jam_selesai").value = (data.jam_selesai || '').substring(0, 5);
    document.getElementById("edit_mobil").value = data.mobil;
    document.getElementById("edit_tujuan").value = data.tujuan || '';
    document.getElementById("edit_km_awal").value = data.km_awal || '';

    // Keperluan handling
    let kep = data.keperluan || '';
    let selectKep = document.getElementById("edit_select_keperluan");
    let containerLain = document.getElementById("edit_container_keperluan_lainnya");
    let inputLain = document.getElementById("edit_input_detail_keperluan_lainnya");

    if (kep.indexOf('Keperluan Kantor Lainnya') === 0) {
        selectKep.value = 'Keperluan Kantor Lainnya';
        containerLain.style.display = 'block';
        inputLain.required = true;
        let match = kep.match(/\((.*?)\)/);
        inputLain.value = match ? match[1] : '';
    } else {
        selectKep.value = kep;
        containerLain.style.display = 'none';
        inputLain.required = false;
        inputLain.value = '';
    }

    // Set fuel bar
    setEditFuelBar(data.bensin_awal);

    $('#editBookingModal').modal('show');
}

// Initial render
window.addEventListener('load', function() {
    renderCalendar();
});

// Instant Frontend Validation on Input Change (Booking Form)
function checkOverlapInstantly() {
    const mobilSelect = document.querySelector('#formBooking select[name="mobil"]');
    const tglMulai = document.querySelector('#formBooking input[name="tgl_mulai"]');
    const tglSelesai = document.querySelector('#formBooking input[name="tgl_selesai"]');
    const jamMulai = document.querySelector('#formBooking input[name="jam_mulai"]');
    const jamSelesai = document.querySelector('#formBooking input[name="jam_selesai"]');
    
    if (!mobilSelect || !tglMulai || !tglSelesai || !jamMulai || !jamSelesai) return;
    if (!mobilSelect.value || !tglMulai.value || !tglSelesai.value || !jamMulai.value || !jamSelesai.value) return;

    const newStart = `${tglMulai.value} ${jamMulai.value}`;
    const newEnd   = `${tglSelesai.value} ${jamSelesai.value}`;

    // Check for overlap with 'Disetujui' or 'Menunggu' bookings using Date+Time
    const overlap = bookings.find(b => {
        if (b.mobil !== mobilSelect.value || (b.status !== 'Disetujui' && b.status !== 'Menunggu')) return false;
        
        const existStart = `${b.tgl_mulai} ${b.jam_mulai}`;
        const existEnd   = `${b.tgl_selesai} ${b.jam_selesai}`;
        
        // Overlap formula: start1 < end2 AND end1 > start2
        return (existStart < newEnd && existEnd > newStart);
    });

    if (overlap) {
        const jamKet = `(jam ${overlap.jam_mulai} s.d ${overlap.jam_selesai})`;
        if (overlap.status === 'Disetujui') {
            alert(`TIDAK TERSEDIA:\n\nMobil ${mobilSelect.value} sudah dipinjam oleh ${overlap.nama_emp} pada tanggal ${overlap.tgl_mulai} s.d ${overlap.tgl_selesai} ${jamKet}.\n\nSilakan pilih jam/tanggal atau mobil yang lain.`);
        } else {
            alert(`SEDANG DIAJUKAN:\n\nMobil ${mobilSelect.value} sedang dalam antrean pengajuan oleh ${overlap.nama_emp} pada tanggal ${overlap.tgl_mulai} s.d ${overlap.tgl_selesai} ${jamKet}.\n\nSilakan pilih jam/tanggal atau mobil yang lain agar tidak bentrok.`);
        }
        // Reset dates to force user to choose valid datetime slot
        tglMulai.value = '';
        tglSelesai.value = '';
    }
}

// Instant Frontend Validation on Input Change (Edit Form)
function checkEditOverlapInstantly() {
    const editBookingId = document.getElementById("edit_booking_id").value;
    const mobilSelect = document.getElementById("edit_mobil");
    const tglMulai = document.getElementById("edit_tgl_mulai");
    const tglSelesai = document.getElementById("edit_tgl_selesai");
    const jamMulai = document.getElementById("edit_jam_mulai");
    const jamSelesai = document.getElementById("edit_jam_selesai");
    
    if (!mobilSelect || !tglMulai || !tglSelesai || !jamMulai || !jamSelesai) return;
    if (!mobilSelect.value || !tglMulai.value || !tglSelesai.value || !jamMulai.value || !jamSelesai.value) return;

    const newStart = `${tglMulai.value} ${jamMulai.value}`;
    const newEnd   = `${tglSelesai.value} ${jamSelesai.value}`;

    const overlap = bookings.find(b => {
        if (String(b.id) === String(editBookingId)) return false;
        if (b.mobil !== mobilSelect.value || (b.status !== 'Disetujui' && b.status !== 'Menunggu')) return false;
        
        const existStart = `${b.tgl_mulai} ${b.jam_mulai}`;
        const existEnd   = `${b.tgl_selesai} ${b.jam_selesai}`;
        
        return (existStart < newEnd && existEnd > newStart);
    });

    if (overlap) {
        const jamKet = `(jam ${overlap.jam_mulai} s.d ${overlap.jam_selesai})`;
        if (overlap.status === 'Disetujui') {
            alert(`TIDAK TERSEDIA:\n\nMobil ${mobilSelect.value} sudah dipinjam oleh ${overlap.nama_emp} pada tanggal ${overlap.tgl_mulai} s.d ${overlap.tgl_selesai} ${jamKet}.\n\nSilakan pilih jam/tanggal atau mobil yang lain.`);
        } else {
            alert(`SEDANG DIAJUKAN:\n\nMobil ${mobilSelect.value} sedang dalam antrean pengajuan oleh ${overlap.nama_emp} pada tanggal ${overlap.tgl_mulai} s.d ${overlap.tgl_selesai} ${jamKet}.\n\nSilakan pilih jam/tanggal atau mobil yang lain agar tidak bentrok.`);
        }
        tglMulai.value = '';
        tglSelesai.value = '';
    }
}

if (document.querySelector('#formBooking select[name="mobil"]')) {
    document.querySelector('#formBooking select[name="mobil"]').addEventListener('change', checkOverlapInstantly);
}
if (document.querySelector('#formBooking input[name="tgl_mulai"]')) {
    document.querySelector('#formBooking input[name="tgl_mulai"]').addEventListener('change', function() {
        const tglSelesai = document.querySelector('#formBooking input[name="tgl_selesai"]');
        if(this.value && !tglSelesai.value) {
            tglSelesai.value = this.value; // Auto-fill tgl_selesai
        }
        checkOverlapInstantly();
    });
}
if (document.querySelector('#formBooking input[name="tgl_selesai"]')) {
    document.querySelector('#formBooking input[name="tgl_selesai"]').addEventListener('change', checkOverlapInstantly);
}
if (document.querySelector('#formBooking input[name="jam_mulai"]')) {
    document.querySelector('#formBooking input[name="jam_mulai"]').addEventListener('change', checkOverlapInstantly);
}
if (document.querySelector('#formBooking input[name="jam_selesai"]')) {
    document.querySelector('#formBooking input[name="jam_selesai"]').addEventListener('change', checkOverlapInstantly);
}
if (document.querySelector('#select_keperluan')) {
    document.querySelector('#select_keperluan').addEventListener('change', function() {
        const detailContainer = document.getElementById('container_keperluan_lainnya');
        const detailInput = document.getElementById('input_detail_keperluan_lainnya');
        if (detailContainer && detailInput) {
            if (this.value === 'Keperluan Kantor Lainnya') {
                detailContainer.style.display = 'block';
                detailInput.required = true;
            } else {
                detailContainer.style.display = 'none';
                detailInput.required = false;
                detailInput.value = '';
            }
        }
    });
}

// Edit Form Event Listeners
if (document.getElementById('edit_mobil')) {
    document.getElementById('edit_mobil').addEventListener('change', checkEditOverlapInstantly);
}
if (document.getElementById('edit_tgl_mulai')) {
    document.getElementById('edit_tgl_mulai').addEventListener('change', function() {
        const tglSelesai = document.getElementById('edit_tgl_selesai');
        if(this.value && !tglSelesai.value) {
            tglSelesai.value = this.value;
        }
        checkEditOverlapInstantly();
    });
}
if (document.getElementById('edit_tgl_selesai')) {
    document.getElementById('edit_tgl_selesai').addEventListener('change', checkEditOverlapInstantly);
}
if (document.getElementById('edit_jam_mulai')) {
    document.getElementById('edit_jam_mulai').addEventListener('change', checkEditOverlapInstantly);
}
if (document.getElementById('edit_jam_selesai')) {
    document.getElementById('edit_jam_selesai').addEventListener('change', checkEditOverlapInstantly);
}
if (document.getElementById('edit_select_keperluan')) {
    document.getElementById('edit_select_keperluan').addEventListener('change', function() {
        const detailContainer = document.getElementById('edit_container_keperluan_lainnya');
        const detailInput = document.getElementById('edit_input_detail_keperluan_lainnya');
        if (detailContainer && detailInput) {
            if (this.value === 'Keperluan Kantor Lainnya') {
                detailContainer.style.display = 'block';
                detailInput.required = true;
            } else {
                detailContainer.style.display = 'none';
                detailInput.required = false;
                detailInput.value = '';
            }
        }
    });
}

// Initialize DataTables & Fuel Bars
$(document).ready(function() {
    var dtConfig = {
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
        language: {
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Data tidak ditemukan",
            info: "Menampilkan halaman _PAGE_ dari _PAGES_",
            infoEmpty: "Tidak ada data yang tersedia",
            infoFiltered: "(difilter dari _MAX_ total data)",
            search: "Cari:",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    };
    if ($('#dataTables-approval').length) {
        $('#dataTables-approval').DataTable(Object.assign({}, dtConfig, {
            order: [[3, 'desc']] // Sort by Tanggal Peminjaman descending (latest first)
        }));
    }
    if ($('#dataTables-my').length) {
        $('#dataTables-my').DataTable(Object.assign({}, dtConfig, {
            order: [[2, 'desc']] // Sort by Periode Peminjaman descending (latest first)
        }));
    }
    if ($('#dataTables-master').length) {
        $('#dataTables-master').DataTable(Object.assign({}, dtConfig, {
            order: [[0, 'asc']]
        }));
    }
    if ($('#dataTables-report').length) {
        $('#dataTables-report').DataTable(Object.assign({}, dtConfig, {
            order: [[1, 'desc']] // Sort by Tanggal Pinjam descending (latest first)
        }));
    }
    // Booking Form Fuel Bar Selector Logic
    $('.bensin-bar-segment').on('click', function() {
        const val = parseInt($(this).data('val'));
        $('#bensin_awal').val(val);
        
        let colorClass = 'active-high';
        if (val <= 2) {
            colorClass = 'active-low';
        } else if (val <= 5) {
            colorClass = 'active-mid';
        }
        
        $('.bensin-bar-segment').each(function() {
            const segVal = parseInt($(this).data('val'));
            $(this).removeClass('active-low active-mid active-high');
            if (segVal <= val) {
                $(this).addClass(colorClass);
            }
        });
        
        $('#bensinAwalLabel').html(`<strong>Bensin terpilih: ${val} Bar</strong>`);
    });

    // Edit Form Fuel Bar Selector Logic
    $('.edit-bensin-bar-segment').on('click', function() {
        const val = parseInt($(this).data('val'));
        setEditFuelBar(val);
    });
});
</script>
