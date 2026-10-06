<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Status Pengajuan Training";
	$menuparent = "training";
	include("layout_top.php");
	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");
?>
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Status Pengajuan Training Saya</h1>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                    <?php
                        // Ambil data pengajuan training milik user yang login
                        $Sql = "SELECT pt.*, e.nama_emp, e.npp, b.nama_bagian 
                                FROM pengajuan_training pt
                                LEFT JOIN employee e ON pt.npp = e.npp
                                LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
                                WHERE pt.npp = '$sess_mngid'
                                ORDER BY pt.tanggal_pengajuan DESC";
                        $Qry = mysqli_query($conn, $Sql);
                        
                        // Cek apakah ada data
                        $total_data = mysqli_num_rows($Qry);
                    ?>		
                    
                    <?php if($total_data > 0){ ?>
                        <table class="table table-striped table-bordered table-hover" id="tabel-data">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="10%">Tgl Pengajuan</th>
                                    <th width="22%">Judul Training</th>
                                    <th width="10%">Tanggal Mulai</th>
                                    <th width="10%">Tanggal Selesai</th>
                                    <th width="12%">Budget</th>
                                    <th width="12%">Status</th>
                                    <!-- <th width="12%">Diproses Oleh</th> -->
                                    <th width="9%">Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $i=1;
                                    while($data = mysqli_fetch_array($Qry)){
                                        $status = $data['status'] ?: 'Pending';
                                        $badge = 'default';
                                        $icon = 'clock-o';
                                        
                                        if($status == 'Approved'){
                                            $badge = 'success';
                                            $icon = 'check-circle';
                                        }elseif($status == 'Rejected'){
                                            $badge = 'danger';
                                            $icon = 'times-circle';
                                        }elseif($status == 'Pending'){
                                            $badge = 'warning';
                                            $icon = 'clock-o';
                                        }
                                        
                                        echo '<tr>';
                                        echo '<td class="text-center">'. $i .'</td>';
                                        echo '<td class="text-center">'. IndonesiaTgl(date('Y-m-d', strtotime($data['tanggal_pengajuan']))) .'</td>';
                                        echo '<td>'. $data['judul_training'] .'</td>';
                                        echo '<td class="text-center">'. IndonesiaTgl($data['tanggal_mulai']) .'</td>';
                                        echo '<td class="text-center">'. IndonesiaTgl($data['tanggal_selesai']) .'</td>';
                                        echo '<td class="text-right">'. format_rupiah($data['budget_total']) .'</td>';
                                        echo '<td class="text-center"><span class="label label-'. $badge .'"><i class="fa fa-'. $icon .'"></i> '. $status .'</span></td>';
                                        // echo '<td class="text-center">'. ($data['approved_by'] ?: '-') .'</td>';
                                        echo '<td class="text-center">
                                              <a href="#" class="btn btn-info btn-xs" data-toggle="modal" data-target="#myModal" data-id="'. $data['id_pengajuan'] .'" title="Lihat Detail">
                                                <i class="fa fa-eye"></i> Detail
                                              </a>';
                                        
                                        echo '</td>';
                                        echo '</tr>';												
                                        $i++;
                                    }
                                ?>
                            </tbody>
                        </table>
                    <?php } else { ?>
                        <div class="alert alert-info text-center">
                            <i class="fa fa-info-circle fa-2x"></i>
                            <h4>Belum Ada Pengajuan Training</h4>
                            <p>Anda belum pernah mengajukan training. Silakan buat pengajuan baru.</p>
                            <a href="form_pengajuan_training.php" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Buat Pengajuan Baru
                            </a>
                        </div>
                    <?php } ?>
                    
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Info Box -->
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-info">
                    <h4><i class="fa fa-info-circle"></i> Informasi Status</h4>
                    <ul>
                        <li><span class="label label-warning"><i class="fa fa-clock-o"></i> Pending</span> - Pengajuan sedang menunggu approval dari Manager HR/HR</li>
                        <li><span class="label label-success"><i class="fa fa-check-circle"></i> Approved</span> - Pengajuan telah disetujui</li>
                        <li><span class="label label-danger"><i class="fa fa-times-circle"></i> Rejected</span> - Pengajuan ditolak (lihat detail untuk alasan penolakan)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade bs-example-modal" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body">
                <p>Sedang memproses…</p>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function(){
    $('#myModal').on('show.bs.modal', function (e) {
        var id = $(e.relatedTarget).data('id');
        $.get('training_detail.php?code=' + id, function(data){
            $('#myModal .modal-content').html(data);
        });
    });
});
</script>

<?php include("layout_bottom.php"); ?>
