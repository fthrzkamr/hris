<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Data Pengajuan Training";
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
                <h1 class="page-header">Data Pengajuan Training Karyawan</h1>
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
                        $Sql = "SELECT pt.*, e.nama_emp, e.npp, b.nama_bagian 
                                FROM pengajuan_training pt
                                LEFT JOIN employee e ON pt.npp = e.npp
                                LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian
                                ORDER BY pt.tanggal_pengajuan DESC";
                        $Qry = mysqli_query($conn, $Sql);
                    ?>		
                            
                        <table class="table table-striped table-bordered table-hover" id="tabel-data">
                            <thead>
                                <tr>
                                    <th width="4%">No</th>
                                    <th width="10%">Tgl Pengajuan</th>
                                    <th width="12%">Nama Karyawan</th>
                                    <th width="10%">Bagian</th>
                                    <th width="18%">Judul Training</th>
                                    <th width="12%">Tanggal Training</th>
                                    <th width="10%">Lokasi</th>
                                    <th width="10%">Budget</th>
                                    <th width="8%">Status</th>
                                    <th width="8%">Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $i=1;
                                    while($data = mysqli_fetch_array($Qry)){
                                        $status = $data['status'] ?: 'Pending';
                                        $badge = 'default';
                                        if($status == 'Approved') $badge = 'success';
                                        elseif($status == 'Rejected') $badge = 'danger';
                                        elseif($status == 'Pending') $badge = 'warning';
                                        
                                        echo '<tr>';
                                        echo '<td class="text-center">'. $i .'</td>';
                                        echo '<td class="text-center">'. IndonesiaTgl(date('Y-m-d', strtotime($data['tanggal_pengajuan']))) .'</td>';
                                        echo '<td>'. $data['nama_emp'] .'</td>';
                                        echo '<td>'. $data['nama_bagian'] .'</td>';
                                        echo '<td>'. $data['judul_training'] .'</td>';
                                        echo '<td class="text-center">'. IndonesiaTgl($data['tanggal_mulai']) .' s/d<br>'. IndonesiaTgl($data['tanggal_selesai']) .'</td>';
                                        echo '<td>'. $data['lokasi_training'] .'</td>';
                                        echo '<td class="text-right">'. format_rupiah($data['budget_total']) .'</td>';
                                        echo '<td class="text-center"><span class="label label-'. $badge .'">'. $status .'</span></td>';
                                        echo '<td class="text-center">
                                              <a href="#" class="btn btn-info btn-xs" data-toggle="modal" data-target="#myModal" data-id="'. $data['id_pengajuan'] .'">
                                                <i class="fa fa-eye"></i> Detail
                                              </a>';
                                        
                                        // Tombol Review untuk pending
                                        if($status == 'Pending'){
                                            echo ' <a href="training_review.php?id='. $data['id_pengajuan'] .'" class="btn btn-primary btn-xs">
                                                    <i class="fa fa-check"></i> Review
                                                  </a>';
                                        }
                                        
                                        echo '</td>';
                                        echo '</tr>';												
                                        $i++;
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
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
