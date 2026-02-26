<!-- Printing -->
<link rel="stylesheet" href="css/printing.css">
        
<?php
include("sess_check.php");
include("dist/function/format_tanggal.php");
include("dist/function/format_rupiah.php");

if($_GET) {
    $kode = $_GET['code'];
    $sql = "SELECT pt.*, e.nama_emp, e.npp, b.nama_bagian
            FROM pengajuan_training pt
            LEFT JOIN employee e ON pt.npp = e.npp
            LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
            WHERE pt.id_pengajuan='". $_GET['code'] ."'";
    $query = mysqli_query($conn,$sql);
    $result = mysqli_fetch_array($query);
    
    // Ambil rincian budget
    $SqlRincian = "SELECT * FROM training_rincian WHERE id_pengajuan='$kode'";
    $QryRincian = mysqli_query($conn, $SqlRincian);
}
else {
    echo "ID Pengajuan Tidak Terbaca";
    exit;
}
?>
<html>
<head>
</head>
<body>
<div id="section-to-print">
<div id="only-on-print">
</div>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
    <h4 class="modal-title" id="myModalLabel">Detail Pengajuan Training</h4>
</div>
<div><br/>
<table width="100%">
    <tr>
        <td width="25%"><b>ID Pengajuan</b></td>
        <td width="2%"><b>:</b></td>
        <td width="73%"><?php echo $result['id_pengajuan'];?></td>
    </tr>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td width="25%"><b>Tanggal Pengajuan</b></td>
        <td width="2%"><b>:</b></td>
        <td width="73%"><?php echo IndonesiaTgl(date('Y-m-d', strtotime($result['tanggal_pengajuan'])));?></td>
    </tr>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td width="25%"><b>NPP</b></td>
        <td width="2%"><b>:</b></td>
        <td width="73%"><?php echo $result['npp'];?></td>
    </tr>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td width="25%"><b>Nama Karyawan</b></td>
        <td width="2%"><b>:</b></td>
        <td width="73%"><?php echo $result['nama_emp'];?></td>
    </tr>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td width="25%"><b>Bagian</b></td>
        <td width="2%"><b>:</b></td>
        <td width="73%"><?php echo $result['nama_bagian'];?></td>
    </tr>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="3"><hr></td>
    </tr>
    <tr>
        <td width="25%"><b>Judul Training</b></td>
        <td width="2%"><b>:</b></td>
        <td width="73%"><?php echo $result['judul_training'];?></td>
    </tr>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td width="25%"><b>Tujuan Training</b></td>
        <td width="2%"><b>:</b></td>
        <td width="73%"><?php echo nl2br($result['tujuan_training']);?></td>
    </tr>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td width="25%"><b>Penyelenggara</b></td>
        <td width="2%"><b>:</b></td>
        <td width="73%"><?php echo $result['penyelenggara'];?></td>
    </tr>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td width="25%"><b>Tanggal Training</b></td>
        <td width="2%"><b>:</b></td>
        <td width="73%"><?php echo IndonesiaTgl($result['tanggal_mulai']) .' s/d '. IndonesiaTgl($result['tanggal_selesai']);?></td>
    </tr>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td width="25%"><b>Lokasi Training</b></td>
        <td width="2%"><b>:</b></td>
        <td width="73%"><?php echo $result['lokasi_training'];?></td>
    </tr>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="3"><hr></td>
    </tr>
    <tr>
        <td colspan="3"><b>Rincian Budget:</b></td>
    </tr>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
</table>

<table width="100%" class="table table-bordered">
    <thead>
        <tr>
            <th width="5%">No</th>
            <th width="60%">Item</th>
            <th width="35%" class="text-right">Nilai</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        while($rincian = mysqli_fetch_array($QryRincian)){
            echo '<tr>';
            echo '<td class="text-center">'. $no .'</td>';
            echo '<td>'. $rincian['nama_item'] .'</td>';
            echo '<td class="text-right">'. format_rupiah($rincian['nilai']) .'</td>';
            echo '</tr>';
            $no++;
        }
        ?>
        <tr>
            <td colspan="2" class="text-right"><strong>TOTAL BUDGET</strong></td>
            <td class="text-right"><strong><?php echo format_rupiah($result['budget_total']);?></strong></td>
        </tr>
    </tbody>
</table>

<table width="100%">
    <tr>
        <td colspan="3"><hr></td>
    </tr>
    <tr>
        <td width="25%"><b>Status</b></td>
        <td width="2%"><b>:</b></td>
        <td width="73%">
            <?php 
            $status = $result['status'] ?: 'Pending';
            if($status == 'Approved'){
                echo '<span class="label label-success">Disetujui</span>';
            }elseif($status == 'Rejected'){
                echo '<span class="label label-danger">Ditolak</span>';
            }else{
                echo '<span class="label label-warning">Menunggu Approval</span>';
            }
            ?>
        </td>
    </tr>
    <?php if($result['approved_by']){ ?>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td width="25%"><b>Diproses Oleh</b></td>
        <td width="2%"><b>:</b></td>
        <td width="73%"><?php echo $result['approved_by'];?></td>
    </tr>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td width="25%"><b>Tanggal Approval</b></td>
        <td width="2%"><b>:</b></td>
        <td width="73%"><?php echo IndonesiaTgl(date('Y-m-d', strtotime($result['approved_date'])));?></td>
    </tr>
    <?php } ?>
    <?php if($result['reject_reason']){ ?>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td width="25%"><b>Alasan Penolakan</b></td>
        <td width="2%"><b>:</b></td>
        <td width="73%"><?php echo nl2br($result['reject_reason']);?></td>
    </tr>
    <?php } ?>
</table>

</div>
</div>
</body>
</html>
