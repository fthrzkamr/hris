<!-- Printing -->
<link rel="stylesheet" href="css/printing.css">
        
<?php
include("sess_check.php");
include("dist/function/format_tanggal.php");
include("dist/function/format_rupiah.php");

if(!isset($_GET['code']) || trim($_GET['code']) == "") {
    echo '<div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Detail Pengajuan Training</h4>
          </div>
          <div class="modal-body">
            <div class="alert alert-danger">ID Pengajuan Tidak Terbaca</div>
          </div>';
    exit;
}

$kode = mysqli_real_escape_string($conn, $_GET['code']);
$sql = "SELECT pt.*, e.nama_emp, e.npp, b.nama_bagian
        FROM pengajuan_training pt
        LEFT JOIN employee e ON pt.npp = e.npp
        LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
        WHERE pt.id_pengajuan='". $kode ."' LIMIT 1";
$query = mysqli_query($conn,$sql);
$result = mysqli_fetch_array($query);

$SqlRincian = "SELECT * FROM training_rincian WHERE id_pengajuan='$kode'";
$QryRincian = mysqli_query($conn, $SqlRincian);

// status badge
$status = $result['status'] ?: 'Pending';
$badge = 'label-warning';
if($status == 'Approved') $badge = 'label-success';
elseif($status == 'Completed') $badge = 'label-primary';
elseif($status == 'Rejected') $badge = 'label-danger';
?>
<style>
    .td-label { font-weight:600; width:28%; vertical-align:top; }
    #section-to-print { padding:10px; }
    @media print {
        .modal-footer, .close { display:none !important; }
    }
</style>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4 class="modal-title"><i class="fa fa-graduation-cap"></i> Detail Pengajuan Training</h4>
</div>

<div class="modal-body">
    <div id="section-to-print">
        <div class="row" style="margin-bottom:8px;">
            <div class="col-xs-12 text-right">
                <span class="label <?php echo $badge; ?>"><?php echo htmlspecialchars($status); ?></span>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <table class="table table-borderless" style="margin-bottom:0;">
                    <tr>
                        <td class="td-label">ID Pengajuan</td>
                        <td><?php echo htmlspecialchars($result['id_pengajuan']); ?></td>
                    </tr>
                    <tr>
                        <td class="td-label">Tanggal Pengajuan</td>
                        <td><?php echo IndonesiaTgl(date('Y-m-d', strtotime($result['tanggal_pengajuan']))); ?></td>
                    </tr>
                    <tr>
                        <td class="td-label">NPP</td>
                        <td><?php echo htmlspecialchars($result['npp']); ?></td>
                    </tr>
                    <tr>
                        <td class="td-label">Nama Karyawan</td>
                        <td><?php echo htmlspecialchars($result['nama_emp']); ?></td>
                    </tr>
                    <tr>
                        <td class="td-label">Bagian</td>
                        <td><?php echo htmlspecialchars($result['nama_bagian']); ?></td>
                    </tr>
                </table>
            </div>

            <div class="col-sm-6">
                <table class="table table-borderless" style="margin-bottom:0;">
                    <tr>
                        <td class="td-label">Judul Training</td>
                        <td><?php echo htmlspecialchars($result['judul_training']); ?></td>
                    </tr>
                    <tr>
                        <td class="td-label">Penyelenggara</td>
                        <td><?php echo htmlspecialchars($result['penyelenggara']); ?></td>
                    </tr>
                    <tr>
                        <td class="td-label">Lokasi</td>
                        <td><?php echo htmlspecialchars($result['lokasi_training']); ?></td>
                    </tr>
                    <tr>
                        <td class="td-label">Tanggal</td>
                        <td><?php echo IndonesiaTgl($result['tanggal_mulai']) . ' s/d ' . IndonesiaTgl($result['tanggal_selesai']); ?></td>
                    </tr>
                    <tr>
                        <td class="td-label">Total Budget</td>
                        <td><?php echo format_rupiah($result['budget_total']); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <hr>

        <h5><strong>Tujuan Training</strong></h5>
        <p style="white-space:pre-wrap;"><?php echo nl2br(htmlspecialchars($result['tujuan_training'])); ?></p>

        <h5 style="margin-top:14px;"><strong>Rincian Budget</strong></h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width:5%;">No</th>
                        <th>Item</th>
                        <th class="text-right" style="width:25%;">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $hasRincian = false;
                    while($rincian = mysqli_fetch_array($QryRincian)){
                        $hasRincian = true;
                        echo '<tr>';
                        echo '<td class="text-center">'. $no .'</td>';
                        echo '<td>'. htmlspecialchars($rincian['nama_item']) .'</td>';
                        echo '<td class="text-right">'. format_rupiah($rincian['nilai']) .'</td>';
                        echo '</tr>';
                        $no++;
                    }
                    if(!$hasRincian){
                        echo '<tr><td colspan="3" class="text-center">Tidak ada rincian budget.</td></tr>';
                    }
                    ?>
                    <tr>
                        <td colspan="2" class="text-right"><strong>TOTAL</strong></td>
                        <td class="text-right"><strong><?php echo format_rupiah($result['budget_total']); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php if(!empty($result['reject_reason'])): ?>
            <hr>
            <h5><strong>Alasan Penolakan</strong></h5>
            <p style="white-space:pre-wrap;"><?php echo nl2br(htmlspecialchars($result['reject_reason'])); ?></p>
        <?php endif; ?>

        <?php if(!empty($result['approved_by'])): ?>
            <hr>
            <p><strong>Diproses Oleh:</strong> <?php echo htmlspecialchars($result['approved_by']); ?> &nbsp; | &nbsp; <strong>Tanggal:</strong> <?php echo IndonesiaTgl(date('Y-m-d', strtotime($result['approved_date']))); ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="modal-footer">
    <button type="button" id="btn-print" class="btn btn-default"><i class="fa fa-print"></i> Print</button>
    <button type="button" class="btn btn-primary" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
</div>

<script>
document.getElementById('btn-print').addEventListener('click', function(){
    var content = document.getElementById('section-to-print').innerHTML;
    var w = window.open('', '_blank', 'width=900,height=700');
    w.document.write('<html><head><title>Print - Pengajuan Training</title>');
    // include minimal bootstrap styles for print readability
    w.document.write('<link rel="stylesheet" href="libs/bootstrap/dist/css/bootstrap.min.css">');
    w.document.write('<style>body{padding:10px;font-family:Arial,Helvetica,sans-serif;} table{width:100%;border-collapse:collapse;} .text-right{text-align:right;} .text-center{text-align:center;}</style>');
    w.document.write('</head><body>');
    w.document.write(content);
    w.document.write('</body></html>');
    w.document.close();
    w.focus();
    setTimeout(function(){ w.print(); w.close(); }, 500);
});
</script>
