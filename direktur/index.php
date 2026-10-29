<?php
	include("sess_check.php");

	$id=$sess_mngid;
	$id_esc = mysqli_real_escape_string($conn, $id);

	$sql_g = "SELECT A.*, COALESCE(B.nama_bagian, A.nama_bagian) AS nama_bagian FROM employee AS A LEFT JOIN bagian AS B ON A.nama_bagian = B.id_bagian WHERE A.npp='$id_esc'";
	$ress_g = mysqli_query($conn, $sql_g);
	$res = mysqli_fetch_array($ress_g);

	// Fetch all cuti for the calendar
	$cuti_events = [];
	$query_cuti = "SELECT c.*, e.nama_emp FROM cuti c JOIN employee e ON c.npp = e.npp WHERE c.stt_cuti = 'Disetujui' OR c.stt_cuti = 'Approved' OR c.stt_cuti LIKE '%Menunggu%'";
	$res_cuti = mysqli_query($conn, $query_cuti);
	if ($res_cuti) {
		while ($row = mysqli_fetch_assoc($res_cuti)) {
			$cuti_events[] = [
				'id' => $row['no_cuti'],
				'nama_emp' => $row['nama_emp'],
				'keterangan' => $row['keterangan'],
				'tgl_mulai' => $row['tgl_awal'],
				'tgl_selesai' => $row['tgl_akhir'],
				'status' => $row['stt_cuti']
			];
		}
	}
	$cuti_json = json_encode($cuti_events);
	// deskripsi halaman
	$pagedesc = "Beranda";
	include("layout_top.php");
	include("dist/function/format_rupiah.php");
?>
<!-- top of file -->
		<!-- Page Content -->
		<div id="page-wrapper">
            <div class="container-fluid">
			  <div class="row">
<div class="col-lg-12">
<div class="panel panel-default">
	<div class="panel-body">
		<style>
			.beranda-welcome-title {
				text-align: center;
				margin-top: 10px;
				margin-bottom: 15px;
				font-weight: 600;
				font-size: 26px;
			}
			.beranda-profile-wrap {
				display: flex;
				align-items: center;
				justify-content: center;
				gap: 40px;
				padding: 15px 20px;
				flex-wrap: wrap;
			}
			.beranda-profile-img {
				width: 160px;
				height: 160px;
				border-radius: 12px;
				object-fit: cover;
				box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
				flex-shrink: 0;
			}
			.beranda-profile-table {
				border-collapse: collapse;
			}
			.beranda-profile-table th,
			.beranda-profile-table td {
				padding: 8px 0;
				font-size: 15px;
				vertical-align: middle;
				text-align: left;
			}
			.beranda-profile-table .label-col {
				font-weight: 700;
				color: #555;
				min-width: 85px;
			}
			.beranda-profile-table .sep-col {
				width: 28px;
				text-align: center;
				font-weight: 700;
				color: #555;
			}
			.beranda-profile-table .val-col {
				font-weight: 700;
				color: #222;
			}
			@media (max-width: 576px) {
				.beranda-welcome-title {
					font-size: 20px;
				}
				.beranda-profile-wrap {
					flex-direction: column;
					gap: 18px;
					padding: 10px;
				}
				.beranda-profile-img {
					width: 135px;
					height: 135px;
				}
				.beranda-profile-table th,
				.beranda-profile-table td {
					font-size: 14px;
					padding: 6px 0;
				}
			}
		</style>
		<h2 class="beranda-welcome-title">Selamat Datang, <?php echo htmlspecialchars($res['nama_emp'] ?? ''); ?> !</h2>
		<hr/>
		<?php
		$foto_emp = trim($res['foto_emp'] ?? '');
		if ($foto_emp !== '' && is_file(__DIR__ . "/../foto/" . $foto_emp)) {
			$foto_src = "../foto/" . rawurlencode($foto_emp);
		} elseif ($foto_emp !== '' && is_file(__DIR__ . "/../foto/karyawan/" . $foto_emp)) {
			$foto_src = "../foto/karyawan/" . rawurlencode($foto_emp);
		} else {
			$foto_src = "../foto/default-user.svg";
		}
		?>
		<div class="beranda-profile-wrap">
			<img src="<?php echo $foto_src; ?>" alt="Foto Karyawan" class="beranda-profile-img" onerror="this.onerror=null;this.src='../foto/default-user.svg';" />
			<table class="beranda-profile-table">
				<tr>
					<th class="label-col">NIK</th>
					<td class="sep-col">:</td>
					<td class="val-col"><?php echo htmlspecialchars($res['npp'] ?? ''); ?></td>
				</tr>
				<tr>
					<th class="label-col">CABANG</th>
					<td class="sep-col">:</td>
					<td class="val-col"><?php echo htmlspecialchars($res['cabang'] ?? ''); ?></td>
				</tr>
				<tr>
					<th class="label-col">BAGIAN</th>
					<td class="sep-col">:</td>
					<td class="val-col"><?php echo htmlspecialchars($res['nama_bagian'] ?? ''); ?></td>
				</tr>
			</table>
		</div>
	</div>
</div>
</div>

            <style>
            /* Custom iOS Calendar Styles */
            .ios-card {
                background: #ffffff;
                border-radius: 16px;
                padding: 24px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.03);
                border: 1px solid #f2f2f7;
            }
            .ios-calendar-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }
            .ios-calendar-title {
                font-size: 18px;
                font-weight: 700;
                color: #1c1c1e;
                margin: 0;
            }
            .ios-calendar-nav {
                display: flex;
                gap: 10px;
            }
            .ios-calendar-btn {
                background: #f2f2f7;
                border: none;
                border-radius: 10px;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #007aff;
                cursor: pointer;
                transition: background 0.2s;
            }
            .ios-calendar-btn:hover { background: #e5e5ea; }
            .ios-calendar-grid {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                gap: 8px;
            }
            .ios-calendar-day-name {
                text-align: center;
                font-size: 12px;
                font-weight: 600;
                color: #8e8e93;
                margin-bottom: 10px;
            }
            .ios-calendar-day {
                background: #f9f9fb;
                border-radius: 12px;
                min-height: 80px;
                padding: 8px;
                transition: all 0.2s;
                cursor: pointer;
                border: 2px solid transparent;
            }
            .ios-calendar-day:hover { background: #f2f2f7; }
            .ios-calendar-day.today {
                background: rgba(0, 122, 255, 0.05);
                border-color: rgba(0, 122, 255, 0.3);
            }
            .ios-calendar-day.other-month { opacity: 0.4; pointer-events: none; }
            .ios-calendar-day.selected { border-color: #007aff; background: #ffffff; box-shadow: 0 4px 12px rgba(0, 122, 255, 0.1); }
            .day-number {
                font-size: 14px;
                font-weight: 600;
                color: #1c1c1e;
                margin-bottom: 5px;
            }
            .ios-event-bar {
                font-size: 10px;
                font-weight: 600;
                padding: 4px 6px;
                border-radius: 6px;
                margin-bottom: 4px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .ios-event-bar.disetujui, .ios-event-bar.approved {
                background: rgba(52, 199, 89, 0.15);
                color: #34c759;
            }
            .ios-event-bar.menunggu, .ios-event-bar.pending {
                background: rgba(255, 149, 0, 0.15);
                color: #ff9500;
            }
            .ios-event-bar i { margin-right: 3px; }
            </style>

            <div class="col-lg-12" style="margin-bottom: 20px;">
                <div class="ios-card">
                    <div class="ios-calendar-header">
                        <h3 class="ios-calendar-title"><i class="fa fa-calendar" style="color: #007aff;"></i> Kalender Cuti Karyawan</h3>
                        <div class="ios-calendar-nav">
                            <button class="ios-calendar-btn" onclick="prevMonth()"><i class="fa fa-chevron-left"></i></button>
                            <button class="ios-calendar-btn" onclick="nextMonth()"><i class="fa fa-chevron-right"></i></button>
                        </div>
                    </div>
                    <div id="calendarTitle" style="text-align: center; font-weight: 700; color: #1c1c1e; margin-bottom: 15px; font-size: 16px;"></div>
                    <div class="ios-calendar-grid">
                        <div class="ios-calendar-day-name">Min</div>
                        <div class="ios-calendar-day-name">Sen</div>
                        <div class="ios-calendar-day-name">Sel</div>
                        <div class="ios-calendar-day-name">Rab</div>
                        <div class="ios-calendar-day-name">Kam</div>
                        <div class="ios-calendar-day-name">Jum</div>
                        <div class="ios-calendar-day-name">Sab</div>
                    </div>
                    <div class="ios-calendar-grid" id="calendarDays">
                        <!-- Days generated by JS -->
                    </div>
                </div>
            </div>
            
            <!-- Modal Cuti Details -->
            <div class="modal fade" id="cutiDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.15);">
                        <div class="modal-header" style="background-color: #f8f9fa; border-bottom: 1px solid #eee; padding: 18px 24px;">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true" style="font-size: 24px;">&times;</button>
                            <h4 class="modal-title" style="font-weight: 700; color: #1c1c1e;"><i class="fa fa-calendar-check-o" style="color: #34c759;"></i> Detail Cuti: <span id="modalDetailsDate"></span></h4>
                        </div>
                        <div class="modal-body" style="padding: 24px; background: #f9f9fb;" id="modalDetailsContainer">
                            <!-- Injected by JS -->
                        </div>
                        <div class="modal-footer" style="background-color: #ffffff; border-top: 1px solid #eee; padding: 15px 24px;">
                            <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 8px; font-weight: 600;">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
			
								<div class="row">
					<div class="col-lg-4 col-md-4">
						<div class="panel panel-primary">
							<a href="cuti_create.php">
								<div class="panel-footer">
									<span class="pull-left">Pengajuan Cuti</span>
									<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
									<div class="clearfix"></div>
								</div>
							</a>
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3"></div>
									<div class="col-xs-9 text-right">
										<div class="huge text-bold"></div>
										<div><h4>Data Cuti</h4></div>
									</div>
								</div>
							</div>
							<a href="cuti_app.php">
								<div class="panel-footer">
									<span class="pull-left">Lihat Rincian</span>
									<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
									<div class="clearfix"></div>
								</div>
							</a>
						</div>
					</div>

					<div class="col-lg-4 col-md-4">
						<div class="panel panel-yellow">
							<a href="lembur_create.php">
								<div class="panel-footer">
									<span class="pull-left">Pengajuan Lembur</span>
									<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
									<div class="clearfix"></div>
								</div>
							</a>
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3"></div>
									<div class="col-xs-9 text-right bold">
										<div class="huge text-bold"></div>
										<div><h4>Data Lemburan</h4></div>
									</div>
								</div>
							</div>
							<a href="lembur_waitapp.php">
								<div class="panel-footer">
									<span class="pull-left">Lihat Rincian</span>
									<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
									<div class="clearfix"></div>
								</div>
							</a>
						</div>
					</div>

					<?php if ((int)($res['kesehatan'] ?? 0) > 0 || (int)($res['plafond'] ?? 0) > 0): ?>
					<div class="col-lg-4 col-md-4">
						<div class="panel panel-red">
							<a href="reimburse_create.php">
								<div class="panel-footer">
									<span class="pull-left">Pengajuan Reimburse</span>
									<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
									<div class="clearfix"></div>
								</div>
							</a>
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3"></div>
									<div class="col-xs-12 text-right">
										<div class="huge text-bold"></div>
										<div><h4>Reimburse</h4></div>
									</div>
								</div>
							</div>
							<a href="reimburse_waitapp.php">
								<div class="panel-footer">
									<span class="pull-left">Lihat Rincian</span>
									<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
									<div class="clearfix"></div>
								</div>
							</a>
						</div>
					</div>
					<?php endif; ?>

					<div class="col-lg-4 col-md-4">
						<div class="panel panel-green">
							<a href="pengajuan_sistem_tambah.php">
								<div class="panel-footer">
									<span class="pull-left">Pengajuan Sistem</span>
									<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
									<div class="clearfix"></div>
								</div>
							</a>
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3"></div>
									<div class="col-xs-12 text-right">
										<div class="huge text-bold"></div>
										<div><h4>Sistem</h4></div>
									</div>
								</div>
							</div>
							<a href="pengajuan_sistem.php">
								<div class="panel-footer">
									<span class="pull-left">Lihat Rincian</span>
									<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
									<div class="clearfix"></div>
								</div>
							</a>
						</div>
					</div>

					<?php if ((int)($res['kacamata'] ?? 0) > 0 || (int)($res['plafond_kacamata'] ?? 0) > 0): ?>
					<div class="col-lg-4 col-md-4">
						<div class="panel panel-red">
							<a href="kacamata_create.php">
								<div class="panel-footer">
									<span class="pull-left">Pengajuan Reimburse Kacamata</span>
									<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
									<div class="clearfix"></div>
								</div>
							</a>
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3"></div>
									<div class="col-xs-12 text-right">
										<div class="huge text-bold"></div>
										<div><h4>Reimburse Kacamata</h4></div>
									</div>
								</div>
							</div>
							<a href="kacamata_rincian.php">
								<div class="panel-footer">
									<span class="pull-left">Lihat Rincian Kacamata</span>
									<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
									<div class="clearfix"></div>
								</div>
							</a>
						</div>
					</div>
					<?php endif; ?>
				</div><!-- /.row -->
			</div><!-- /.container-fluid -->
        </div><!-- /#page-wrapper -->
<!-- bottom of file -->
<script>
const cutiBookings = <?php echo $cuti_json; ?>;

let currentDate = new Date();
let selectedDate = new Date();

const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

function renderCalendar() {
    const calendarDays = document.getElementById("calendarDays");
    const calendarTitle = document.getElementById("calendarTitle");
    
    calendarDays.innerHTML = "";
    
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    calendarTitle.innerText = `${monthNames[month]} ${year}`;
    
    const firstDayIndex = new Date(year, month, 1).getDay();
    const lastDay = new Date(year, month + 1, 0).getDate();
    const prevLastDay = new Date(year, month, 0).getDate();
    
    for (let x = firstDayIndex; x > 0; x--) {
        const dayDiv = document.createElement("div");
        dayDiv.classList.add("ios-calendar-day", "other-month");
        dayDiv.innerHTML = `<div class="day-number">${prevLastDay - x + 1}</div>`;
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
                const statusClass = b.status.toLowerCase().includes('menunggu') ? 'menunggu' : 'disetujui';
                eventBar.classList.add("ios-event-bar", statusClass);
                let nameShort = b.nama_emp.split(' ')[0] || b.nama_emp;
                eventBar.innerHTML = `<i class="fa fa-user" style="font-size: 9px;"></i> ${nameShort}`;
                eventBar.title = `Karyawan: ${b.nama_emp}\nKeterangan: ${b.keterangan}\nStatus: ${b.status}`;
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
        dayDiv.innerHTML = `<div class="day-number">${j}</div>`;
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
    return cutiBookings.filter(b => {
        return (dateStr >= b.tgl_mulai && dateStr <= b.tgl_selesai);
    });
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
        
        const isPending = b.status.toLowerCase().includes('menunggu') || b.status.toLowerCase().includes('pending');
        const borderColor = isPending ? '#ff9500' : '#34c759';
        const badgeBg = isPending ? 'rgba(255, 149, 0, 0.15)' : 'rgba(52, 199, 89, 0.15)';
        const badgeColor = isPending ? '#ff9500' : '#34c759';
        
        item.style.borderLeft = `5px solid ${borderColor}`;
        
        // Format dates nicely
        const start = new Date(b.tgl_mulai);
        const end = new Date(b.tgl_selesai);
        const dateRangeStr = `${start.getDate()} ${monthNames[start.getMonth()]} - ${end.getDate()} ${monthNames[end.getMonth()]} ${end.getFullYear()}`;

        item.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 11px; padding: 4px 10px; border-radius: 20px; font-weight: 600; background: ${badgeBg}; color: ${badgeColor};">${b.status}</span>
                <span style="font-size: 12px; font-weight: 600; color: #8e8e93;"><i class="fa fa-calendar-o"></i> ${dateRangeStr}</span>
            </div>
            <h5 style="font-weight: 700; font-size: 15px; margin: 0 0 8px 0; color: #1c1c1e;">${b.nama_emp}</h5>
            <p style="font-size: 13px; margin: 2px 0; color: #3a3a3c;"><strong>Keterangan:</strong> ${b.keterangan}</p>
        `;
        container.appendChild(item);
    });

    $('#cutiDetailsModal').modal('show');
}

window.addEventListener('load', function() {
    renderCalendar();
});
</script>
<?php
	include("layout_bottom.php");
?>