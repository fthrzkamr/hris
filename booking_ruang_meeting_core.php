<?php
// Ensure database tables exist
if (isset($conn)) {
    $createTable = "CREATE TABLE IF NOT EXISTS booking_ruang_meeting (
        id INT AUTO_INCREMENT PRIMARY KEY,
        npp VARCHAR(50) NOT NULL,
        nama_emp VARCHAR(100) NOT NULL,
        ruangan VARCHAR(100) NOT NULL,
        tgl_mulai DATE NOT NULL,
        tgl_selesai DATE NOT NULL,
        jam_mulai TIME NOT NULL,
        jam_selesai TIME NOT NULL,
        agenda TEXT NOT NULL,
        jumlah_peserta INT DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'Disetujui',
        keterangan TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_npp (npp),
        INDEX idx_dates (tgl_mulai, tgl_selesai)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $createTable);

    $createMaster = "CREATE TABLE IF NOT EXISTS master_ruangan (
        id_ruangan INT AUTO_INCREMENT PRIMARY KEY,
        nama_ruangan VARCHAR(100) NOT NULL,
        lokasi VARCHAR(100) DEFAULT NULL,
        kapasitas INT DEFAULT NULL,
        status ENUM('Tersedia','Tidak Tersedia') DEFAULT 'Tersedia'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $createMaster);
}

// Determine current user
$active_npp  = $sess_mngid ?? $sess_admid ?? '';
$active_name = $sess_mngname ?? $sess_admname ?? '';

// Role flag: yang mengelola master data ruangan (bukan approval booking — booking bersifat self-service)
$is_admin_ruangan = ($active_npp === '24940107');

$message = '';
$message_type = '';

// ---------- MASTER RUANGAN: TAMBAH ----------
if ($is_admin_ruangan && isset($_POST['action_master']) && $_POST['action_master'] == 'add_ruangan') {
    $nama_ruangan = mysqli_real_escape_string($conn, trim($_POST['nama_ruangan'] ?? ''));
    $lokasi       = mysqli_real_escape_string($conn, trim($_POST['lokasi'] ?? ''));
    $kapasitas    = intval($_POST['kapasitas'] ?? 0);

    if (empty($nama_ruangan)) {
        $message = "Nama ruangan wajib diisi!";
        $message_type = "danger";
    } else {
        $kap_val = $kapasitas > 0 ? $kapasitas : 'NULL';
        mysqli_query($conn, "INSERT INTO master_ruangan (nama_ruangan, lokasi, kapasitas, status) VALUES ('$nama_ruangan', '$lokasi', $kap_val, 'Tersedia')");
        $message = "Ruangan '$nama_ruangan' berhasil ditambahkan.";
        $message_type = "success";
    }
}

// ---------- MASTER RUANGAN: UPDATE STATUS ----------
if ($is_admin_ruangan && isset($_POST['action_master']) && $_POST['action_master'] == 'update_status') {
    $id_ruangan = mysqli_real_escape_string($conn, $_POST['id_ruangan']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    mysqli_query($conn, "UPDATE master_ruangan SET status='$new_status' WHERE id_ruangan='$id_ruangan'");
    $message = "Status ruangan berhasil diperbarui menjadi '$new_status'.";
    $message_type = "success";
}

// ---------- CANCEL ACTION (BY USER OR ADMIN) ----------
if (isset($_POST['action_cancel']) && $_POST['action_cancel'] == 'cancel') {
    $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
    $reason     = mysqli_real_escape_string($conn, trim($_POST['cancel_reason'] ?? ''));

    if (empty($reason)) {
        $message = "Alasan pembatalan wajib diisi!";
        $message_type = "danger";
    } else {
        $q_chk = mysqli_query($conn, "SELECT * FROM booking_ruang_meeting WHERE id='$booking_id'");
        if ($q_chk && mysqli_num_rows($q_chk) > 0) {
            $bd = mysqli_fetch_assoc($q_chk);
            if (!$is_admin_ruangan && $bd['npp'] !== $active_npp) {
                $message = "Anda tidak memiliki hak untuk membatalkan booking ini.";
                $message_type = "danger";
            } elseif ($bd['status'] !== 'Disetujui') {
                $message = "Booking ini sudah tidak aktif.";
                $message_type = "danger";
            } elseif ($bd['tgl_selesai'] < date('Y-m-d')) {
                $message = "Booking yang jadwalnya sudah lewat tidak dapat dibatalkan.";
                $message_type = "danger";
            } else {
                mysqli_query($conn, "UPDATE booking_ruang_meeting SET status='Dibatalkan', keterangan='$reason' WHERE id='$booking_id'");
                $message = "Booking ruang meeting berhasil dibatalkan. Jadwal ruangan telah dibebaskan kembali.";
                $message_type = "success";
            }
        } else {
            $message = "Data booking tidak ditemukan.";
            $message_type = "danger";
        }
    }
}

// ---------- EDIT BOOKING ACTION ----------
if (isset($_POST['action_edit']) && $_POST['action_edit'] == 'edit') {
    $booking_id       = mysqli_real_escape_string($conn, $_POST['booking_id']);
    $edit_ruangan     = mysqli_real_escape_string($conn, $_POST['ruangan'] ?? '');
    $edit_tgl_mulai   = mysqli_real_escape_string($conn, $_POST['tgl_mulai'] ?? '');
    $edit_tgl_selesai = mysqli_real_escape_string($conn, $_POST['tgl_selesai'] ?? '');
    $edit_jam_mulai   = mysqli_real_escape_string($conn, $_POST['jam_mulai'] ?? '');
    $edit_jam_selesai = mysqli_real_escape_string($conn, $_POST['jam_selesai'] ?? '');
    $edit_agenda      = mysqli_real_escape_string($conn, $_POST['agenda'] ?? '');
    $edit_peserta     = intval($_POST['jumlah_peserta'] ?? 0);

    $q_chk = mysqli_query($conn, "SELECT * FROM booking_ruang_meeting WHERE id='$booking_id'");
    if (mysqli_num_rows($q_chk) == 0) {
        $message = "Data booking tidak ditemukan.";
        $message_type = "danger";
    } else {
        $bd = mysqli_fetch_assoc($q_chk);
        if (!$is_admin_ruangan && $bd['npp'] !== $active_npp) {
            $message = "Anda tidak memiliki akses untuk mengubah booking ini.";
            $message_type = "danger";
        } elseif ($bd['status'] !== 'Disetujui') {
            $message = "Booking yang sudah dibatalkan tidak dapat diubah.";
            $message_type = "danger";
        } elseif ($bd['tgl_selesai'] < date('Y-m-d')) {
            $message = "Booking yang jadwalnya sudah lewat tidak dapat diubah.";
            $message_type = "danger";
        } elseif (empty($edit_ruangan) || empty($edit_tgl_mulai) || empty($edit_tgl_selesai) || empty($edit_jam_mulai) || empty($edit_jam_selesai) || empty($edit_agenda)) {
            $message = "Semua kolom input edit wajib diisi!";
            $message_type = "danger";
        } elseif ($edit_tgl_mulai > $edit_tgl_selesai) {
            $message = "Tanggal mulai tidak boleh melebihi tanggal selesai!";
            $message_type = "danger";
        } elseif ($edit_tgl_mulai == $edit_tgl_selesai && $edit_jam_mulai >= $edit_jam_selesai) {
            $message = "Jam mulai harus sebelum jam selesai pada hari yang sama!";
            $message_type = "danger";
        } else {
            $query_overlap = "SELECT * FROM booking_ruang_meeting
                              WHERE ruangan = '$edit_ruangan'
                              AND status = 'Disetujui'
                              AND id != '$booking_id'
                              AND TIMESTAMP(tgl_mulai, jam_mulai) < TIMESTAMP('$edit_tgl_selesai', '$edit_jam_selesai')
                              AND TIMESTAMP(tgl_selesai, jam_selesai) > TIMESTAMP('$edit_tgl_mulai', '$edit_jam_mulai')";
            $res_overlap = mysqli_query($conn, $query_overlap);
            if (mysqli_num_rows($res_overlap) > 0) {
                $overlap_data = mysqli_fetch_assoc($res_overlap);
                $dipakai_oleh = $overlap_data['nama_emp'];
                $tgl_dari    = date('d/m/Y', strtotime($overlap_data['tgl_mulai']));
                $tgl_sampai  = date('d/m/Y', strtotime($overlap_data['tgl_selesai']));
                $message = "Gagal mengubah! Ruangan <strong>$edit_ruangan</strong> sudah dibooking oleh <strong>$dipakai_oleh</strong> untuk periode $tgl_dari s.d $tgl_sampai.";
                $message_type = "danger";
            } else {
                $q_upd = "UPDATE booking_ruang_meeting SET
                            ruangan='$edit_ruangan',
                            tgl_mulai='$edit_tgl_mulai',
                            tgl_selesai='$edit_tgl_selesai',
                            jam_mulai='$edit_jam_mulai',
                            jam_selesai='$edit_jam_selesai',
                            agenda='$edit_agenda',
                            jumlah_peserta=$edit_peserta
                          WHERE id='$booking_id'";
                if (mysqli_query($conn, $q_upd)) {
                    $message = "Data booking berhasil diperbarui.";
                    $message_type = "success";
                } else {
                    $message = "Gagal memperbarui data: " . mysqli_error($conn);
                    $message_type = "danger";
                }
            }
        }
    }
}

// Load all rooms with real-time availability
$q_rooms = mysqli_query($conn, "
    SELECT m.*,
    CASE
        WHEN m.status = 'Tidak Tersedia' THEN 1
        WHEN EXISTS (
            SELECT 1 FROM booking_ruang_meeting b
            WHERE b.ruangan = m.nama_ruangan
            AND b.status = 'Disetujui'
            AND CONCAT(b.tgl_selesai, ' ', b.jam_selesai) >= NOW()
        ) THEN 1
        ELSE 0
    END AS sedang_dipakai
    FROM master_ruangan m ORDER BY m.nama_ruangan
");
$all_rooms = [];
if ($q_rooms) {
    while ($rr = mysqli_fetch_assoc($q_rooms)) $all_rooms[] = $rr;
}

// ---------- NEW BOOKING ----------
if (isset($_POST['submit_booking'])) {
    $ruangan     = mysqli_real_escape_string($conn, $_POST['ruangan']);
    $tgl_mulai   = mysqli_real_escape_string($conn, $_POST['tgl_mulai']);
    $tgl_selesai = mysqli_real_escape_string($conn, $_POST['tgl_selesai']);
    $jam_mulai   = mysqli_real_escape_string($conn, $_POST['jam_mulai']);
    $jam_selesai = mysqli_real_escape_string($conn, $_POST['jam_selesai']);
    $agenda      = mysqli_real_escape_string($conn, $_POST['agenda'] ?? '');
    $jumlah_peserta = intval($_POST['jumlah_peserta'] ?? 0);

    if (empty($ruangan) || empty($tgl_mulai) || empty($tgl_selesai) || empty($jam_mulai) || empty($jam_selesai) || empty($agenda)) {
        $message = "Semua kolom input wajib diisi!";
        $message_type = "danger";
    } elseif ($tgl_mulai > $tgl_selesai) {
        $message = "Tanggal mulai tidak boleh melebihi tanggal selesai!";
        $message_type = "danger";
    } elseif ($tgl_mulai == $tgl_selesai && $jam_mulai >= $jam_selesai) {
        $message = "Jam mulai harus sebelum jam selesai pada hari yang sama!";
        $message_type = "danger";
    } else {
        $query_overlap = "SELECT * FROM booking_ruang_meeting
                          WHERE ruangan = '$ruangan'
                          AND status = 'Disetujui'
                          AND TIMESTAMP(tgl_mulai, jam_mulai) < TIMESTAMP('$tgl_selesai', '$jam_selesai')
                          AND TIMESTAMP(tgl_selesai, jam_selesai) > TIMESTAMP('$tgl_mulai', '$jam_mulai')";
        $res_overlap = mysqli_query($conn, $query_overlap);

        if (mysqli_num_rows($res_overlap) > 0) {
            $overlap_data = mysqli_fetch_assoc($res_overlap);
            $dipakai_oleh = $overlap_data['nama_emp'];
            $tgl_dari    = date('d/m/Y', strtotime($overlap_data['tgl_mulai']));
            $tgl_sampai  = date('d/m/Y', strtotime($overlap_data['tgl_selesai']));
            $message = "Ruangan <strong>$ruangan</strong> tidak tersedia — sudah dibooking oleh <strong>$dipakai_oleh</strong> untuk periode $tgl_dari s.d $tgl_sampai (jam " . substr($overlap_data['jam_mulai'],0,5) . " - " . substr($overlap_data['jam_selesai'],0,5) . ").";
            $message_type = "danger";
        } else {
            $query_insert = "INSERT INTO booking_ruang_meeting (npp, nama_emp, ruangan, tgl_mulai, tgl_selesai, jam_mulai, jam_selesai, agenda, jumlah_peserta, status)
                             VALUES ('$active_npp', '$active_name', '$ruangan', '$tgl_mulai', '$tgl_selesai', '$jam_mulai', '$jam_selesai', '$agenda', $jumlah_peserta, 'Disetujui')";
            if (mysqli_query($conn, $query_insert)) {
                $message = "Booking ruang meeting berhasil disimpan!";
                $message_type = "success";
            } else {
                $message = "Gagal menyimpan booking: " . mysqli_error($conn);
                $message_type = "danger";
            }
        }
    }
}

// Fetch bookings for the calendar (aktif + yang pernah dibatalkan, untuk riwayat)
$bookings = [];
$query_bookings = "SELECT b.*, IFNULL(e.nama_emp, b.nama_emp) AS nama_emp FROM booking_ruang_meeting b
                   LEFT JOIN employee e ON b.npp = e.npp
                   WHERE b.status IN ('Disetujui', 'Dibatalkan')
                   ORDER BY b.tgl_mulai ASC, b.jam_mulai ASC";
$res_bookings = mysqli_query($conn, $query_bookings);
if ($res_bookings) {
    while ($row = mysqli_fetch_assoc($res_bookings)) {
        $bookings[] = [
            'id' => $row['id'],
            'nama_emp' => $row['nama_emp'],
            'ruangan' => $row['ruangan'],
            'tgl_mulai' => $row['tgl_mulai'],
            'tgl_selesai' => $row['tgl_selesai'],
            'jam_mulai' => substr($row['jam_mulai'], 0, 5),
            'jam_selesai' => substr($row['jam_selesai'], 0, 5),
            'agenda' => $row['agenda'],
            'jumlah_peserta' => $row['jumlah_peserta'],
            'status' => $row['status'],
            'keterangan' => $row['keterangan'],
        ];
    }
}

// Fetch bookings of active user
$my_bookings = [];
$query_my = "SELECT * FROM booking_ruang_meeting WHERE npp = '$active_npp' ORDER BY created_at DESC";
$res_my = mysqli_query($conn, $query_my);
if ($res_my) {
    while ($row = mysqli_fetch_assoc($res_my)) {
        $my_bookings[] = $row;
    }
}

$pagedesc = "Booking Ruang Meeting";
?>

<style>
.ios-card {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
    border: 1px solid #f2f2f7;
    padding: 24px;
    margin-bottom: 25px;
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
    background: rgba(0, 122, 255, 0.12);
    color: #004a99;
    border-left: 4px solid #007aff;
}
.event-room {
    font-weight: 700;
}
.event-name {
    font-weight: 400;
}
.ios-event-bar.cancelled {
    background: rgba(142, 142, 147, 0.12);
    color: #8e8e93;
    border-left: 4px solid #8e8e93;
    text-decoration: line-through;
}
@media (max-width: 767px) {
    .ios-calendar-day { min-height: 75px; padding: 4px 2px; }
    .ios-event-bar { font-size: 8px; padding: 2px 3px; }
    .day-number { font-size: 11px; }
    .ios-calendar-container { padding: 10px; }
    .ios-card { padding: 12px; margin-bottom: 12px; }
}
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
.ios-badge.dibatalkan {
    background: rgba(142, 142, 147, 0.15);
    color: #8e8e93;
}
</style>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header" style="font-weight: 800; color: #1c1c1e; border-bottom: none; margin-bottom: 10px;">Booking Ruang Meeting</h1>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissable" style="border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <i class="fa fa-info-circle"></i> &nbsp;<?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="row" style="margin-bottom: 15px;">
        <div class="col-xs-12 col-md-12">
            <div class="ios-card text-center" style="padding: 20px 24px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <h4 style="font-weight: 700; color: #1c1c1e; margin-bottom: 10px;">Ingin Menggunakan Ruang Meeting?</h4>
                <p class="text-muted" style="font-size: 13px; margin-bottom: 15px;">Silahkan cek ketersediaan pada kalender, lalu ajukan booking dengan menekan tombol dibawah. Booking langsung terkonfirmasi jika tidak bentrok.</p>
                <button class="ios-btn-primary" style="width: auto; padding: 10px 30px; font-size: 15px;" data-toggle="modal" data-target="#bookingModal">
                    <i class="fa fa-plus-circle"></i> Buat Booking Baru
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-md-12">
            <div class="ios-card">
                <div class="ios-calendar-container">
                    <div class="ios-calendar-header">
                        <h3 id="calendarMonthYear">-</h3>
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
                    <div class="ios-calendar-grid" id="calendarDays"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row" style="margin-top: 15px;">
        <div class="col-xs-12 col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <b><i class="fa fa-list"></i> Booking Saya</b>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover" id="dataTables-my">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center">No</th>
                                    <th width="15%">Ruangan</th>
                                    <th width="20%">Periode</th>
                                    <th width="10%">Waktu</th>
                                    <th width="20%">Agenda</th>
                                    <th width="10%" class="text-center">Peserta</th>
                                    <th width="10%" class="text-center">Status</th>
                                    <th width="10%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($my_bookings as $my): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($my['ruangan']); ?></strong></td>
                                        <td data-order="<?php echo $my['tgl_mulai'] . ' ' . $my['jam_mulai']; ?>">
                                            <i class="fa fa-calendar-o"></i> &nbsp;<?php echo date('d/m/Y', strtotime($my['tgl_mulai'])) . ' - ' . date('d/m/Y', strtotime($my['tgl_selesai'])); ?>
                                        </td>
                                        <td><?php echo substr($my['jam_mulai'],0,5) . ' - ' . substr($my['jam_selesai'],0,5); ?></td>
                                        <td><?php echo htmlspecialchars($my['agenda']); ?></td>
                                        <td class="text-center"><?php echo $my['jumlah_peserta'] ? $my['jumlah_peserta'] : '-'; ?></td>
                                        <td class="text-center">
                                            <?php if ($my['status'] == 'Disetujui'): ?>
                                                <span class="label label-success" style="font-size: 11px; padding: 5px 10px; display: inline-block;"><i class="fa fa-check-circle"></i> Disetujui</span>
                                            <?php else: ?>
                                                <span class="label label-default" style="font-size: 11px; padding: 5px 10px; display: inline-block; background-color: #8e8e93;"><i class="fa fa-ban"></i> Dibatalkan</span>
                                                <?php if (!empty($my['keterangan'])): ?>
                                                    <br><small class="text-muted" style="margin-top: 4px; display: inline-block; font-size: 10px;">
                                                        <strong>Catatan:</strong> <?php echo htmlspecialchars($my['keterangan']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php $my_expired = ($my['tgl_selesai'] < date('Y-m-d')); ?>
                                            <?php if ($my['status'] == 'Disetujui' && !$my_expired): ?>
                                                <div style="display: flex; gap: 4px; justify-content: center;">
                                                    <button type="button" class="btn btn-info btn-xs" style="border-radius: 4px;" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($my), ENT_QUOTES, 'UTF-8'); ?>)">
                                                        <i class="fa fa-pencil"></i> Edit
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-xs" style="border-radius: 4px;" onclick="openCancelModal('<?php echo $my['id']; ?>')">
                                                        <i class="fa fa-times"></i> Batal
                                                    </button>
                                                </div>
                                            <?php elseif ($my['status'] == 'Disetujui' && $my_expired): ?>
                                                <span class="text-muted" style="font-size: 11px;" title="Jadwal sudah lewat"><i class="fa fa-lock"></i> Selesai</span>
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
    </div>

    <?php if ($is_admin_ruangan): ?>
    <div class="row">
        <div class="col-xs-12 col-md-12">
            <div class="panel panel-default" style="margin-top: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: none;">
                <div class="panel-heading" style="background-color: #ffffff; border-bottom: 1px solid #f2f2f7; padding: 16px 20px; border-radius: 12px 12px 0 0; display:flex; justify-content: space-between; align-items:center;">
                    <b style="font-size: 15px; color: #1c1c1e;"><i class="fa fa-building" style="color: #007aff; margin-right: 6px;"></i> Master Data Ruangan</b>
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addRoomModal"><i class="fa fa-plus"></i> Tambah Ruangan</button>
                </div>
                <div class="panel-body" style="padding: 20px;">
                    <div class="table-responsive">
                        <table class="table table-hover" id="dataTables-master" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center">No</th>
                                    <th width="25%">Nama Ruangan</th>
                                    <th width="20%">Lokasi</th>
                                    <th width="10%" class="text-center">Kapasitas</th>
                                    <th width="15%" class="text-center">Status</th>
                                    <th width="25%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $nom = 1;
                                foreach ($all_rooms as $rm):
                                    $bg_status = ($rm['status'] == 'Tersedia') ? 'success' : 'warning';
                                ?>
                                    <tr>
                                        <td class="text-center"><?php echo $nom++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($rm['nama_ruangan']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($rm['lokasi'] ?? '-'); ?></td>
                                        <td class="text-center"><?php echo $rm['kapasitas'] ? $rm['kapasitas'] . ' orang' : '-'; ?></td>
                                        <td class="text-center">
                                            <span class="label label-<?php echo $bg_status; ?>" style="font-size: 11px; padding: 5px 10px; display: inline-block;"><?php echo $rm['status']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <form method="post" style="display: flex; align-items: center; justify-content: center; gap: 6px; margin: 0;">
                                                <input type="hidden" name="action_master" value="update_status">
                                                <input type="hidden" name="id_ruangan" value="<?php echo $rm['id_ruangan']; ?>">
                                                <select name="new_status" class="form-control input-sm" style="width: 140px; font-size: 13px; height: 32px;">
                                                    <option value="Tersedia" <?php echo ($rm['status'] == 'Tersedia') ? 'selected' : ''; ?>>Tersedia</option>
                                                    <option value="Tidak Tersedia" <?php echo ($rm['status'] == 'Tidak Tersedia') ? 'selected' : ''; ?>>Tidak Tersedia</option>
                                                </select>
                                                <button type="submit" class="btn btn-primary btn-sm" style="padding: 5px 12px; font-size: 12px;"><i class="fa fa-save"></i> Simpan</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal: New Booking Form -->
<div class="modal fade" id="bookingModal" tabindex="-1" role="dialog" aria-labelledby="bookingModalLabel" aria-hidden="true" style="font-family: -apple-system, BlinkMacSystemFont, sans-serif;">
    <div class="modal-dialog" role="document" style="max-width: 500px;">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15); overflow: hidden;">
            <div class="modal-header ios-modal-header" style="background: white; border-bottom: 1px solid #f2f2f7; padding: 20px;">
                <h4 class="modal-title ios-modal-title" id="bookingModalLabel"><i class="fa fa-building" style="color: #007aff;"></i> &nbsp;Form Booking Ruang Meeting</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 24px; font-weight: 300; opacity: 0.5;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" id="formBooking">
                <div class="modal-body" style="padding: 24px;">
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
                        <label class="ios-form-label">Pilih Ruangan <span class="text-danger">*</span></label>
                        <select class="ios-input" name="ruangan" required>
                            <option value="">-- Pilih Ruangan --</option>
                            <?php foreach ($all_rooms as $room):
                                $unavailable = ($room['status'] == 'Tidak Tersedia');
                                $disabled  = $unavailable ? 'disabled' : '';
                                $info      = $unavailable ? ' [Tidak Tersedia]' : ($room['kapasitas'] ? ' - ' . $room['kapasitas'] . ' orang' : '');
                            ?>
                            <option value="<?php echo htmlspecialchars($room['nama_ruangan']); ?>" <?php echo $disabled; ?>>
                                <?php echo htmlspecialchars($room['nama_ruangan']) . $info; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ios-form-group">
                        <label class="ios-form-label">Agenda / Topik Meeting <span class="text-danger">*</span></label>
                        <textarea class="ios-input" name="agenda" rows="3" placeholder="Jelaskan agenda atau topik meeting..." required></textarea>
                    </div>
                    <div class="ios-form-group">
                        <label class="ios-form-label">Jumlah Peserta</label>
                        <input type="number" class="ios-input" name="jumlah_peserta" placeholder="Perkiraan jumlah peserta" min="1">
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f2f2f7; padding: 20px; background: #f8f8fa; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="ios-btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="submit_booking" class="ios-btn-primary">Kirim Booking</button>
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
                <h4 class="modal-title ios-modal-title" id="cancelModalLabel" style="color: #ff3b30;"><i class="fa fa-times-circle"></i> &nbsp;Batalkan Booking</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 24px; font-weight: 300; opacity: 0.5;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body" style="padding: 20px;">
                    <input type="hidden" name="booking_id" id="cancel_booking_id">
                    <input type="hidden" name="action_cancel" value="cancel">
                    <div style="background: #fff5f5; border: 1px solid #ff3b30; border-radius: 10px; padding: 12px; margin-bottom: 15px; font-size: 12px; color: #c53030;">
                        <i class="fa fa-info-circle"></i> Booking yang dibatalkan akan melepaskan jadwal ruangan sehingga dapat digunakan untuk booking lainnya.
                    </div>
                    <div class="ios-form-group">
                        <label class="ios-form-label">Alasan Pembatalan <span class="text-danger">*</span></label>
                        <textarea class="ios-input" name="cancel_reason" id="cancel_reason" rows="3" placeholder="Tulis alasan pembatalan (misal: Agenda dibatalkan, dialihkan online, dll)..." required></textarea>
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

<!-- Modal: Edit Booking Form -->
<div class="modal fade" id="editBookingModal" tabindex="-1" role="dialog" aria-labelledby="editBookingModalLabel" aria-hidden="true" style="font-family: -apple-system, BlinkMacSystemFont, sans-serif;">
    <div class="modal-dialog" role="document" style="max-width: 500px;">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15); overflow: hidden;">
            <div class="modal-header ios-modal-header" style="background: white; border-bottom: 1px solid #f2f2f7; padding: 20px;">
                <h4 class="modal-title ios-modal-title" id="editBookingModalLabel"><i class="fa fa-pencil-square-o" style="color: #007aff;"></i> &nbsp;Edit Booking Ruang Meeting</h4>
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
                        <label class="ios-form-label">Pilih Ruangan <span class="text-danger">*</span></label>
                        <select class="ios-input" name="ruangan" id="edit_ruangan" required>
                            <option value="">-- Pilih Ruangan --</option>
                            <?php foreach ($all_rooms as $room):
                                $unavailable = ($room['status'] == 'Tidak Tersedia');
                                $disabled  = $unavailable ? 'disabled' : '';
                                $info      = $unavailable ? ' [Tidak Tersedia]' : ($room['kapasitas'] ? ' - ' . $room['kapasitas'] . ' orang' : '');
                            ?>
                            <option value="<?php echo htmlspecialchars($room['nama_ruangan']); ?>" <?php echo $disabled; ?>>
                                <?php echo htmlspecialchars($room['nama_ruangan']) . $info; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ios-form-group">
                        <label class="ios-form-label">Agenda / Topik Meeting <span class="text-danger">*</span></label>
                        <textarea class="ios-input" name="agenda" id="edit_agenda" rows="3" required></textarea>
                    </div>
                    <div class="ios-form-group">
                        <label class="ios-form-label">Jumlah Peserta</label>
                        <input type="number" class="ios-input" name="jumlah_peserta" id="edit_jumlah_peserta" min="1">
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

<!-- Modal: Booking Details -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1" role="dialog" aria-labelledby="bookingDetailsModalLabel" aria-hidden="true" style="font-family: -apple-system, BlinkMacSystemFont, sans-serif;">
    <div class="modal-dialog" role="document" style="max-width: 450px;">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15); overflow: hidden;">
            <div class="modal-header ios-modal-header" style="background: white; border-bottom: 1px solid #f2f2f7; padding: 20px;">
                <h4 class="modal-title ios-modal-title" id="bookingDetailsModalLabel" style="font-weight: 700; color: #1c1c1e;"><i class="fa fa-info-circle" style="color: #007aff;"></i> &nbsp;Detail Booking</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 24px; font-weight: 300; opacity: 0.5;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 24px; background: #f8f8fa;">
                <div id="modalDetailsDate" style="font-size: 14px; font-weight: 700; color: #8e8e93; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px;"></div>
                <div id="modalDetailsContainer" style="max-height: 400px; overflow-y: auto; padding-right: 2px;"></div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f2f2f7; padding: 15px 20px; background: white; display: flex; justify-content: flex-end;">
                <button type="button" class="ios-btn-primary" data-dismiss="modal" style="padding: 10px 24px; font-size: 14px; box-shadow: none;">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php if ($is_admin_ruangan): ?>
<!-- Modal: Tambah Ruangan -->
<div class="modal fade" id="addRoomModal" tabindex="-1" role="dialog" aria-labelledby="addRoomModalLabel" aria-hidden="true" style="font-family: -apple-system, BlinkMacSystemFont, sans-serif;">
    <div class="modal-dialog" role="document" style="max-width: 450px;">
        <div class="modal-content" style="border-radius: 20px; border: none; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header ios-modal-header" style="background: white; border-bottom: 1px solid #f2f2f7; padding: 20px;">
                <h4 class="modal-title ios-modal-title" id="addRoomModalLabel"><i class="fa fa-plus-circle" style="color: #007aff;"></i> &nbsp;Tambah Ruangan Baru</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 24px; font-weight: 300; opacity: 0.5;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body" style="padding: 20px;">
                    <input type="hidden" name="action_master" value="add_ruangan">
                    <div class="ios-form-group">
                        <label class="ios-form-label">Nama Ruangan <span class="text-danger">*</span></label>
                        <input type="text" class="ios-input" name="nama_ruangan" placeholder="Contoh: Ruang Meeting Lt. 2" required>
                    </div>
                    <div class="ios-form-group">
                        <label class="ios-form-label">Lokasi</label>
                        <input type="text" class="ios-input" name="lokasi" placeholder="Contoh: Lantai 2, Gedung A">
                    </div>
                    <div class="ios-form-group">
                        <label class="ios-form-label">Kapasitas</label>
                        <input type="number" class="ios-input" name="kapasitas" placeholder="Perkiraan jumlah orang" min="1">
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f2f2f7; padding: 15px 20px; background: #f8f8fa; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="ios-btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="ios-btn-primary"><i class="fa fa-save"></i> Simpan Ruangan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const bookings = <?php echo json_encode($bookings); ?>;

let currentDate = new Date();
let selectedDate = new Date();

const monthNames = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember"
];

function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    document.getElementById("calendarMonthYear").innerText = `${monthNames[month]} ${year}`;

    const calendarDays = document.getElementById("calendarDays");
    calendarDays.innerHTML = "";

    const firstDayIndex = new Date(year, month, 1).getDay();
    const lastDay = new Date(year, month + 1, 0).getDate();
    const prevLastDay = new Date(year, month, 0).getDate();

    for (let x = firstDayIndex; x > 0; x--) {
        const dayDiv = document.createElement("div");
        dayDiv.classList.add("ios-calendar-day", "other-month");
        const numDiv = document.createElement("div");
        numDiv.classList.add("day-number");
        numDiv.innerText = prevLastDay - x + 1;
        dayDiv.appendChild(numDiv);
        calendarDays.appendChild(dayDiv);
    }

    for (let i = 1; i <= lastDay; i++) {
        const dayDiv = document.createElement("div");
        dayDiv.classList.add("ios-calendar-day");

        const numDiv = document.createElement("div");
        numDiv.classList.add("day-number");
        numDiv.innerText = i;
        dayDiv.appendChild(numDiv);

        const d = new Date(year, month, i);
        const dateStr = formatDate(d);

        const today = new Date();
        if (d.getDate() === today.getDate() && d.getMonth() === today.getMonth() && d.getFullYear() === today.getFullYear()) {
            dayDiv.classList.add("today");
        }

        if (d.getDate() === selectedDate.getDate() && d.getMonth() === selectedDate.getMonth() && d.getFullYear() === selectedDate.getFullYear()) {
            dayDiv.classList.add("selected");
        }

        const dayBookings = getBookingsForDate(dateStr);
        if (dayBookings.length > 0) {
            dayBookings.forEach(b => {
                const eventBar = document.createElement("div");
                const isCancelled = b.status === 'Dibatalkan';
                eventBar.classList.add("ios-event-bar");
                if (isCancelled) eventBar.classList.add("cancelled");

                let roomShort = b.ruangan.split(' ').slice(0, 2).join(' ') || b.ruangan;
                let nameShort = b.nama_emp.split(' ')[0] || b.nama_emp;
                let icon = isCancelled ? 'fa-ban' : 'fa-building';

                eventBar.innerHTML = `<i class="fa ${icon}" style="font-size: 9px;"></i> <span class="event-room">${roomShort}</span> - <span class="event-name">${nameShort}</span>`;
                eventBar.title = `Ruangan: ${b.ruangan}\nPIC: ${b.nama_emp}\nWaktu: ${b.jam_mulai} - ${b.jam_selesai}` + (isCancelled ? `\nStatus: Dibatalkan` : '');
                dayDiv.appendChild(eventBar);
            });
        }

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

function formatDate(date) {
    const y = date.getFullYear();
    let m = date.getMonth() + 1;
    let d = date.getDate();
    if (m < 10) m = `0${m}`;
    if (d < 10) d = `0${d}`;
    return `${y}-${m}-${d}`;
}

function getBookingsForDate(dateStr) {
    return bookings.filter(b => (dateStr >= b.tgl_mulai && dateStr <= b.tgl_selesai));
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
        const isCancelled = b.status === 'Dibatalkan';
        item.style.background = "#ffffff";
        item.style.borderRadius = "14px";
        item.style.padding = "16px";
        item.style.marginBottom = "12px";
        item.style.boxShadow = "0 4px 12px rgba(0,0,0,0.02)";
        item.style.borderLeft = isCancelled ? "5px solid #8e8e93" : "5px solid #34c759";
        item.style.borderTop = "1px solid #f2f2f7";
        item.style.borderRight = "1px solid #f2f2f7";
        item.style.borderBottom = "1px solid #f2f2f7";
        if (isCancelled) item.style.opacity = "0.7";

        item.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span class="ios-badge ${isCancelled ? 'dibatalkan' : 'disetujui'}" style="font-size: 11px; padding: 3px 8px;">${isCancelled ? 'Dibatalkan' : 'Disetujui'}</span>
                <span style="font-size: 12px; font-weight: 600; color: #8e8e93;"><i class="fa fa-clock-o"></i> ${b.jam_mulai} - ${b.jam_selesai}</span>
            </div>
            <h5 style="font-weight: 700; font-size: 15px; margin: 0 0 8px 0; color: #1c1c1e; ${isCancelled ? 'text-decoration: line-through;' : ''}">${b.ruangan}</h5>
            <p style="font-size: 13px; margin: 2px 0; color: #3a3a3c;"><strong>PIC:</strong> ${b.nama_emp}</p>
            <p style="font-size: 13px; margin: 2px 0; color: #3a3a3c;"><strong>Agenda:</strong> ${b.agenda}</p>
            <p style="font-size: 13px; margin: 2px 0; color: #3a3a3c;"><strong>Peserta:</strong> ${b.jumlah_peserta ? b.jumlah_peserta + ' orang' : '-'}</p>
            ${isCancelled && b.keterangan ? `<p style="font-size: 12px; margin: 6px 0 0 0; color: #8e8e93;"><strong>Alasan dibatalkan:</strong> ${b.keterangan}</p>` : ''}
        `;
        container.appendChild(item);
    });

    $('#bookingDetailsModal').modal('show');
}

function openCancelModal(bookingId) {
    document.getElementById("cancel_booking_id").value = bookingId;
    document.getElementById("cancel_reason").value = "";
    $('#cancelModal').modal('show');
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
    document.getElementById("edit_ruangan").value = data.ruangan;
    document.getElementById("edit_agenda").value = data.agenda || '';
    document.getElementById("edit_jumlah_peserta").value = data.jumlah_peserta || '';

    $('#editBookingModal').modal('show');
}

window.addEventListener('load', function() {
    renderCalendar();
});

function checkOverlapInstantly() {
    const ruanganSelect = document.querySelector('#formBooking select[name="ruangan"]');
    const tglMulai = document.querySelector('#formBooking input[name="tgl_mulai"]');
    const tglSelesai = document.querySelector('#formBooking input[name="tgl_selesai"]');
    const jamMulai = document.querySelector('#formBooking input[name="jam_mulai"]');
    const jamSelesai = document.querySelector('#formBooking input[name="jam_selesai"]');

    if (!ruanganSelect || !tglMulai || !tglSelesai || !jamMulai || !jamSelesai) return;
    if (!ruanganSelect.value || !tglMulai.value || !tglSelesai.value || !jamMulai.value || !jamSelesai.value) return;

    const newStart = `${tglMulai.value} ${jamMulai.value}`;
    const newEnd   = `${tglSelesai.value} ${jamSelesai.value}`;

    const overlap = bookings.find(b => {
        if (b.status !== 'Disetujui') return false;
        if (b.ruangan !== ruanganSelect.value) return false;
        const existStart = `${b.tgl_mulai} ${b.jam_mulai}`;
        const existEnd   = `${b.tgl_selesai} ${b.jam_selesai}`;
        return (existStart < newEnd && existEnd > newStart);
    });

    if (overlap) {
        const jamKet = `(jam ${overlap.jam_mulai} s.d ${overlap.jam_selesai})`;
        alert(`TIDAK TERSEDIA:\n\nRuangan ${ruanganSelect.value} sudah dibooking oleh ${overlap.nama_emp} pada tanggal ${overlap.tgl_mulai} s.d ${overlap.tgl_selesai} ${jamKet}.\n\nSilakan pilih jam/tanggal atau ruangan yang lain.`);
        tglMulai.value = '';
        tglSelesai.value = '';
    }
}

function checkEditOverlapInstantly() {
    const editBookingId = document.getElementById("edit_booking_id").value;
    const ruanganSelect = document.getElementById("edit_ruangan");
    const tglMulai = document.getElementById("edit_tgl_mulai");
    const tglSelesai = document.getElementById("edit_tgl_selesai");
    const jamMulai = document.getElementById("edit_jam_mulai");
    const jamSelesai = document.getElementById("edit_jam_selesai");

    if (!ruanganSelect || !tglMulai || !tglSelesai || !jamMulai || !jamSelesai) return;
    if (!ruanganSelect.value || !tglMulai.value || !tglSelesai.value || !jamMulai.value || !jamSelesai.value) return;

    const newStart = `${tglMulai.value} ${jamMulai.value}`;
    const newEnd   = `${tglSelesai.value} ${jamSelesai.value}`;

    const overlap = bookings.find(b => {
        if (b.status !== 'Disetujui') return false;
        if (String(b.id) === String(editBookingId)) return false;
        if (b.ruangan !== ruanganSelect.value) return false;
        const existStart = `${b.tgl_mulai} ${b.jam_mulai}`;
        const existEnd   = `${b.tgl_selesai} ${b.jam_selesai}`;
        return (existStart < newEnd && existEnd > newStart);
    });

    if (overlap) {
        const jamKet = `(jam ${overlap.jam_mulai} s.d ${overlap.jam_selesai})`;
        alert(`TIDAK TERSEDIA:\n\nRuangan ${ruanganSelect.value} sudah dibooking oleh ${overlap.nama_emp} pada tanggal ${overlap.tgl_mulai} s.d ${overlap.tgl_selesai} ${jamKet}.\n\nSilakan pilih jam/tanggal atau ruangan yang lain.`);
        tglMulai.value = '';
        tglSelesai.value = '';
    }
}

if (document.querySelector('#formBooking select[name="ruangan"]')) {
    document.querySelector('#formBooking select[name="ruangan"]').addEventListener('change', checkOverlapInstantly);
}
if (document.querySelector('#formBooking input[name="tgl_mulai"]')) {
    document.querySelector('#formBooking input[name="tgl_mulai"]').addEventListener('change', function() {
        const tglSelesai = document.querySelector('#formBooking input[name="tgl_selesai"]');
        if (this.value && !tglSelesai.value) {
            tglSelesai.value = this.value;
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

if (document.getElementById('edit_ruangan')) {
    document.getElementById('edit_ruangan').addEventListener('change', checkEditOverlapInstantly);
}
if (document.getElementById('edit_tgl_mulai')) {
    document.getElementById('edit_tgl_mulai').addEventListener('change', function() {
        const tglSelesai = document.getElementById('edit_tgl_selesai');
        if (this.value && !tglSelesai.value) {
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
            paginate: { first: "Pertama", last: "Terakhir", next: "Selanjutnya", previous: "Sebelumnya" }
        }
    };
    if ($('#dataTables-my').length) {
        $('#dataTables-my').DataTable(Object.assign({}, dtConfig, { order: [[2, 'desc']] }));
    }
    if ($('#dataTables-master').length) {
        $('#dataTables-master').DataTable(Object.assign({}, dtConfig, { order: [[0, 'asc']] }));
    }
});
</script>
