<?php
	include("sess_check.php");
	require_once(file_exists(__DIR__ . "/libur_helper.php") ? __DIR__ . "/libur_helper.php" : dirname(__DIR__) . "/libur_helper.php");

	$id=$sess_mngid;
	$id_esc = mysqli_real_escape_string($conn, $id);

	$libur_nasional_json = json_encode(get_libur_nasional());

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
	$pagedesc = "Kalender Cuti";
	include("layout_top.php");
?>
<!-- top of file -->
		<!-- Page Content -->
		<div id="page-wrapper">
            <div class="container-fluid">
			  <div class="row">
                  <div class="col-lg-12">
                      <h1 class="page-header">Kalender Jadwal Cuti Karyawan</h1>
                  </div>
              </div>
              
			  <div class="row">
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
            .ios-calendar-day.libur-minggu { background: rgba(255, 59, 48, 0.05); }
            .ios-calendar-day.libur-minggu .day-number { color: #ff3b30; }
            .ios-calendar-day.libur-nasional { background: rgba(255, 59, 48, 0.08); }
            .ios-calendar-day.libur-nasional .day-number { color: #ff3b30; }
            .libur-tag {
                font-size: 9px;
                font-weight: 700;
                color: #ff3b30;
                background: rgba(255, 59, 48, 0.12);
                border-radius: 5px;
                padding: 2px 5px;
                margin-bottom: 4px;
                display: inline-block;
            }
            .ios-calendar-day-name.minggu { color: #ff3b30; }
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
                        <h3 class="ios-calendar-title"><i class="fa fa-calendar" style="color: #007aff;"></i> Jadwal Cuti</h3>
                        <div class="ios-calendar-nav">
                            <button class="ios-calendar-btn" onclick="prevMonth()"><i class="fa fa-chevron-left"></i></button>
                            <button class="ios-calendar-btn" onclick="nextMonth()"><i class="fa fa-chevron-right"></i></button>
                        </div>
                    </div>
                    <div id="calendarTitle" style="text-align: center; font-weight: 700; color: #1c1c1e; margin-bottom: 15px; font-size: 16px;"></div>
                    <div style="text-align: center; margin-bottom: 15px;">
                        <span class="libur-tag"><i class="fa fa-circle"></i> Minggu / Libur Nasional</span>
                    </div>
                    <div class="ios-calendar-grid">
                        <div class="ios-calendar-day-name minggu">Min</div>
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
			
				</div><!-- /.row -->
			</div><!-- /.container-fluid -->
        </div><!-- /#page-wrapper -->
<!-- bottom of file -->
<script>
const cutiBookings = <?php echo $cuti_json; ?>;
const liburNasional = <?php echo $libur_nasional_json; ?>;

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

        const isSunday = d.getDay() === 0;
        const isNationalHoliday = liburNasional.includes(dateStr);
        if (isSunday) {
            dayDiv.classList.add("libur-minggu");
        }
        if (isNationalHoliday) {
            dayDiv.classList.add("libur-nasional");
            const liburTag = document.createElement("div");
            liburTag.classList.add("libur-tag");
            liburTag.innerText = "Libur";
            dayDiv.appendChild(liburTag);
        }

        const dayBookings = (isSunday || isNationalHoliday) ? [] : getBookingsForDate(dateStr);
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
            if (isSunday || isNationalHoliday) return;

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

// Inisialisasi kalender
renderCalendar();
</script>

<?php
	include("layout_bottom.php");
?>
