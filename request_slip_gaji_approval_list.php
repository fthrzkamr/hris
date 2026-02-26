<?php
include("sess_check.php");

// Deskripsi halaman
$pagedesc = "Approval Request Slip Gaji";
include("layout_top.php");

// Bulan dalam bahasa Indonesia
$bulan_list = [
    "01" => "Januari", "02" => "Februari", "03" => "Maret",
    "04" => "April", "05" => "Mei", "06" => "Juni",
    "07" => "Juli", "08" => "Agustus", "09" => "September",
    "10" => "Oktober", "11" => "November", "12" => "Desember"
];

// Filter status
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'pending';

// Ambil semua request slip gaji
$sql = "SELECT r.*, e.nama_bagian, b.nama_bagian as nama_bagian_join, e.cabang 
    FROM request_slip_gaji r
    LEFT JOIN employee e ON r.npp = e.npp
    LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
    WHERE r.status = ?
    ORDER BY r.tanggal_request DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $filter_status);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Count pending requests
$sql_count = "SELECT COUNT(*) as total FROM request_slip_gaji WHERE status = 'pending'";
$result_count = mysqli_query($conn, $sql_count);
$count_pending = mysqli_fetch_assoc($result_count)['total'];
?>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">

        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">
                    Approval Request Slip Gaji 
                    <?php if ($count_pending > 0) { ?>
                        <span class="badge" style="background-color: #d9534f;"><?php echo $count_pending; ?></span>
                    <?php } ?>
                </h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <!-- Info Panel -->
        <!-- <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle fa-fw"></i> <strong>Perhatian:</strong> Pastikan data gaji karyawan di sistem sudah tersedia sebelum menyetujui request. Karyawan hanya dapat mendownload slip gaji setelah data gaji tersedia di system.
                </div>
            </div>
        </div> -->

        <!-- Filter Status -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="btn-group" role="group">
                            <a href="?status=pending" class="btn btn-<?php echo ($filter_status == 'pending') ? 'warning' : 'default'; ?>">
                                <i class="fa fa-clock-o fa-fw"></i> Pending (<?php echo $count_pending; ?>)
                            </a>
                            <a href="?status=approved" class="btn btn-<?php echo ($filter_status == 'approved') ? 'success' : 'default'; ?>">
                                <i class="fa fa-check fa-fw"></i> Disetujui
                            </a>
                            <a href="?status=rejected" class="btn btn-<?php echo ($filter_status == 'rejected') ? 'danger' : 'default'; ?>">
                                <i class="fa fa-times fa-fw"></i> Ditolak
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Request -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-list fa-fw"></i> Daftar Request Slip Gaji - Status: <strong><?php echo ucfirst($filter_status); ?></strong>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="dataTables">
                                <thead>
                                    <tr>
                                        <th class="text-center" width="5%">No</th>
                                        <th class="text-center">NPP</th>
                                        <th class="text-center">Nama Karyawan</th>
                                        <th class="text-center">Bagian</th>
                                        <th class="text-center">Cabang</th>
                                        <th class="text-center">Periode</th>
                                        <th class="text-center">Tanggal Request</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center" width="15%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if (mysqli_num_rows($result) > 0) {
                                        $no = 1;
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $periode = $bulan_list[$row['bulan']] . " " . $row['tahun'];
                                            $tanggal_request = date('d/m/Y H:i', strtotime($row['tanggal_request']));
                                            
                                            // Status badge
                                            if ($row['status'] == 'approved') {
                                                $status_badge = '<span class="label label-success">Disetujui</span>';
                                            } elseif ($row['status'] == 'rejected') {
                                                $status_badge = '<span class="label label-danger">Ditolak</span>';
                                            } else {
                                                $status_badge = '<span class="label label-warning">Pending</span>';
                                            }
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td class="text-center"><?php echo $row['npp']; ?></td>
                                        <td><?php echo $row['nama_karyawan']; ?></td>
                                        <td class="text-center"><?php echo $row['nama_bagian_join'] ?: '-'; ?></td>
                                        <td class="text-center"><?php echo $row['cabang'] ?: '-'; ?></td>
                                        <td class="text-center"><strong><?php echo $periode; ?></strong></td>
                                        <td class="text-center"><?php echo $tanggal_request; ?></td>
                                        <td class="text-center"><?php echo $status_badge; ?></td>
                                        <td class="text-center">
                                            <?php if ($row['status'] == 'pending') { ?>
                                                <button type="button" class="btn btn-xs btn-primary" 
                                                        onclick="processRequest(<?php echo $row['id_request']; ?>, '<?php echo $row['npp']; ?>', '<?php echo $row['nama_karyawan']; ?>', '<?php echo $periode; ?>', '<?php echo $row['bulan']; ?>', '<?php echo $row['tahun']; ?>')">
                                                    <i class="fa fa-edit fa-fw"></i> Proses & Input Gaji
                                                </button>
                                                <button type="button" class="btn btn-xs btn-danger" 
                                                        onclick="rejectRequest(<?php echo $row['id_request']; ?>, '<?php echo $row['npp']; ?>', '<?php echo $row['nama_karyawan']; ?>', '<?php echo $periode; ?>')">
                                                    <i class="fa fa-times fa-fw"></i> Tolak
                                                </button>
                                            <?php } else { ?>
                                                <span class="text-muted"><em>
                                                    <?php 
                                                    if ($row['status'] == 'approved') {
                                                        echo 'Disetujui oleh: ' . $row['approved_by'];
                                                    } else {
                                                        echo 'Ditolak';
                                                    }
                                                    ?>
                                                </em></span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php 
                                        }
                                    } else {
                                    ?>
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <em>Tidak ada data request dengan status <?php echo $filter_status; ?>.</em>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.container-fluid -->
</div><!-- /#page-wrapper -->

<!-- Modal Process & Input Gaji -->
<div class="modal fade" id="processModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="post" action="request_slip_gaji_approval.php" id="processForm">
                <div class="modal-header bg-primary" style="background-color: #337ab7; color: white;">
                    <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Proses & Input Data Slip Gaji</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_request" id="process_id">
                    <input type="hidden" name="npp" id="process_npp">
                    <input type="hidden" name="bulan" id="process_bulan">
                    <input type="hidden" name="tahun" id="process_tahun">
                    <input type="hidden" name="action" value="approve_with_data">
                    
                    <!-- Info Karyawan -->
                    <div class="alert alert-info">
                        <strong><i class="fa fa-user"></i> Data Karyawan:</strong><br>
                        <table class="table table-condensed" style="margin-top: 10px; margin-bottom: 0;">
                            <tr>
                                <td width="120"><strong>NPP</strong></td>
                                <td>: <span id="process_npp_display"></span></td>
                                <td width="120"><strong>Periode</strong></td>
                                <td>: <span id="process_periode"></span></td>
                            </tr>
                            <tr>
                                <td><strong>Nama</strong></td>
                                <td colspan="3">: <span id="process_nama"></span></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Form Input Gaji -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="panel panel-success">
                                <div class="panel-heading">
                                    <strong><i class="fa fa-plus-circle"></i> PENDAPATAN</strong>
                                </div>
                                <div class="panel-body">
                                    <div class="form-group">
                                        <label>Gaji Pokok <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="gaji_pokok" id="gaji_pokok" required 
                                               min="0" placeholder="0" onchange="hitungTotal()">
                                    </div>
                                    <div class="form-group">
                                        <label>Tunjangan Jabatan</label>
                                        <input type="number" class="form-control" name="tunj_jabatan" id="tunj_jabatan" 
                                               min="0" value="0" placeholder="0" onchange="hitungTotal()">
                                    </div>
                                    <div class="form-group">
                                        <label>Tunjangan Kinerja</label>
                                        <input type="number" class="form-control" name="tunj_kinerja" id="tunj_kinerja" 
                                               min="0" value="0" placeholder="0" onchange="hitungTotal()">
                                    </div>
                                    <div class="form-group">
                                        <label>Tunjangan Transport</label>
                                        <input type="number" class="form-control" name="tunj_transport" id="tunj_transport" 
                                               min="0" value="0" placeholder="0" onchange="hitungTotal()">
                                    </div>
                                    <div class="form-group">
                                        <label>Lembur</label>
                                        <input type="number" class="form-control" name="lembur" id="lembur" 
                                               min="0" value="0" placeholder="0" onchange="hitungTotal()">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="panel panel-danger">
                                <div class="panel-heading">
                                    <strong><i class="fa fa-minus-circle"></i> POTONGAN</strong>
                                </div>
                                <div class="panel-body">
                                    <div class="form-group">
                                        <label>Potongan Keterlambatan</label>
                                        <input type="number" class="form-control" name="p_keterlambatan" id="p_keterlambatan" 
                                               min="0" value="0" placeholder="0" onchange="hitungTotal()">
                                    </div>
                                    <div class="form-group">
                                        <label>Potongan Pinjaman</label>
                                        <input type="number" class="form-control" name="p_pinjaman" id="p_pinjaman" 
                                               min="0" value="0" placeholder="0" onchange="hitungTotal()">
                                    </div>
                                    <div class="form-group">
                                        <label>Potongan Lain-lain</label>
                                        <input type="number" class="form-control" name="p_lain" id="p_lain" 
                                               min="0" value="0" placeholder="0" onchange="hitungTotal()">
                                    </div>
                                    <div class="form-group">
                                        <label>Potongan Kesehatan</label>
                                        <input type="number" class="form-control" name="p_kesehatan" id="p_kesehatan" 
                                               min="0" value="0" placeholder="0" onchange="hitungTotal()">
                                    </div>
                                    <div class="form-group">
                                        <label>Potongan Absensi</label>
                                        <input type="number" class="form-control" name="p_absensi" id="p_absensi" 
                                               min="0" value="0" placeholder="0" onchange="hitungTotal()">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Gaji -->
                    <div class="alert alert-success" style="background-color: #d4edda; border-color: #c3e6cb;">
                        <div class="row">
                            <div class="col-md-4">
                                <strong><i class="fa fa-calculator"></i> Total Pendapatan:</strong><br>
                                <h4 class="text-success" id="display_total_pendapatan" style="margin: 5px 0;">Rp 0</h4>
                            </div>
                            <div class="col-md-4">
                                <strong><i class="fa fa-calculator"></i> Total Potongan:</strong><br>
                                <h4 class="text-danger" id="display_total_potongan" style="margin: 5px 0;">Rp 0</h4>
                            </div>
                            <div class="col-md-4">
                                <strong><i class="fa fa-money"></i> GAJI BERSIH:</strong><br>
                                <h3 class="text-success" id="display_gaji_bersih" style="margin: 5px 0; font-weight: bold;">Rp 0</h3>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Catatan (Opsional)</label>
                        <textarea class="form-control" name="catatan" rows="2" placeholder="Catatan tambahan untuk slip gaji ini..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fa fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-success" id="btnSubmitProcess">
                        <i class="fa fa-check"></i> Simpan & Setujui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reject -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="request_slip_gaji_approval.php">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-times-circle"></i> Tolak Request Slip Gaji</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_request" id="reject_id">
                    <input type="hidden" name="action" value="reject">
                    <p>Apakah Anda yakin ingin menolak request slip gaji ini?</p>
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">NPP</th>
                            <td id="reject_npp"></td>
                        </tr>
                        <tr>
                            <th>Nama Karyawan</th>
                            <td id="reject_nama"></td>
                        </tr>
                        <tr>
                            <th>Periode</th>
                            <td id="reject_periode"></td>
                        </tr>
                    </table>
                    <div class="form-group">
                        <label>Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="keterangan_reject" rows="3" required placeholder="Masukkan alasan penolakan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-times fa-fw"></i> Tolak Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function processRequest(id, npp, nama, periode, bulan, tahun) {
    // Set hidden fields
    document.getElementById('process_id').value = id;
    document.getElementById('process_npp').value = npp;
    document.getElementById('process_bulan').value = bulan;
    document.getElementById('process_tahun').value = tahun;
    
    // Set display fields
    document.getElementById('process_npp_display').innerText = npp;
    document.getElementById('process_nama').innerText = nama;
    document.getElementById('process_periode').innerText = periode;
    
    // Reset form
    document.getElementById('processForm').reset();
    document.getElementById('process_id').value = id;
    document.getElementById('process_npp').value = npp;
    document.getElementById('process_bulan').value = bulan;
    document.getElementById('process_tahun').value = tahun;
    
    // Reset calculated values
    hitungTotal();
    
    // Show modal
    $('#processModal').modal('show');
}

function rejectRequest(id, npp, nama, periode) {
    document.getElementById('reject_id').value = id;
    document.getElementById('reject_npp').innerText = npp;
    document.getElementById('reject_nama').innerText = nama;
    document.getElementById('reject_periode').innerText = periode;
    $('#rejectModal').modal('show');
}

function hitungTotal() {
    // Ambil nilai pendapatan
    var gajiPokok = parseFloat(document.getElementById('gaji_pokok').value) || 0;
    var tunjJabatan = parseFloat(document.getElementById('tunj_jabatan').value) || 0;
    var tunjKinerja = parseFloat(document.getElementById('tunj_kinerja').value) || 0;
    var tunjTransport = parseFloat(document.getElementById('tunj_transport').value) || 0;
    var lembur = parseFloat(document.getElementById('lembur').value) || 0;
    
    // Ambil nilai potongan
    var pKeterlambatan = parseFloat(document.getElementById('p_keterlambatan').value) || 0;
    var pPinjaman = parseFloat(document.getElementById('p_pinjaman').value) || 0;
    var pLain = parseFloat(document.getElementById('p_lain').value) || 0;
    var pKesehatan = parseFloat(document.getElementById('p_kesehatan').value) || 0;
    var pAbsensi = parseFloat(document.getElementById('p_absensi').value) || 0;
    
    // Hitung total
    var totalPendapatan = gajiPokok + tunjJabatan + tunjKinerja + tunjTransport + lembur;
    var totalPotongan = pKeterlambatan + pPinjaman + pLain + pKesehatan + pAbsensi;
    var gajiBersih = totalPendapatan - totalPotongan;
    
    // Format rupiah
    document.getElementById('display_total_pendapatan').innerText = formatRupiah(totalPendapatan);
    document.getElementById('display_total_potongan').innerText = formatRupiah(totalPotongan);
    document.getElementById('display_gaji_bersih').innerText = formatRupiah(gajiBersih);
}

function formatRupiah(angka) {
    var number_string = angka.toString().replace(/[^,\d]/g, ''),
        split = number_string.split(','),
        sisa = split[0].length % 3,
        rupiah = split[0].substr(0, sisa),
        ribuan = split[0].substr(sisa).match(/\d{3}/gi);
    
    if (ribuan) {
        separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }
    
    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    return 'Rp ' + rupiah;
}

// Validasi form sebelum submit
document.getElementById('processForm').addEventListener('submit', function(e) {
    var gajiPokok = parseFloat(document.getElementById('gaji_pokok').value) || 0;
    
    if (gajiPokok <= 0) {
        e.preventDefault();
        alert('Gaji pokok harus diisi dan lebih besar dari 0!');
        document.getElementById('gaji_pokok').focus();
        return false;
    }
    
    var confirm_msg = 'Apakah Anda yakin data yang diinput sudah benar?\n\n';
    confirm_msg += 'Data ini akan disimpan dan request akan disetujui.';
    
    if (!confirm(confirm_msg)) {
        e.preventDefault();
        return false;
    }
    
    // Disable button untuk prevent double submit
    document.getElementById('btnSubmitProcess').disabled = true;
    document.getElementById('btnSubmitProcess').innerHTML = '<i class="fa fa-spinner fa-spin"></i> Memproses...';
    
    return true;
});
</script>

<?php include("layout_bottom.php"); ?>
