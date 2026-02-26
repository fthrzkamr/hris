<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Pengajuan Training Menunggu Approval";
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
                <h1 class="page-header">Pengajuan Training Menunggu Approval</h1>
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
                                WHERE pt.status IS NULL OR pt.status = 'Pending'
                                ORDER BY pt.tanggal_pengajuan DESC";
                        $Qry = mysqli_query($conn, $Sql);
                    ?>		
                            
                        <table class="table table-striped table-bordered table-hover" id="tabel-data">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="12%">Tanggal Pengajuan</th>
                                    <th width="15%">Nama Karyawan</th>
                                    <th width="12%">Bagian</th>
                                    <th width="20%">Judul Training</th>
                                    <th width="12%">Tanggal Training</th>
                                    <th width="12%">Budget</th>
                                    <th width="12%">Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $i=1;
                                    while($data = mysqli_fetch_array($Qry)){
                                        echo '<tr>';
                                        echo '<td class="text-center">'. $i .'</td>';
                                        echo '<td class="text-center">'. IndonesiaTgl(date('Y-m-d', strtotime($data['tanggal_pengajuan']))) .'</td>';
                                        echo '<td>'. $data['nama_emp'] .'</td>';
                                        echo '<td>'. $data['nama_bagian'] .'</td>';
                                        echo '<td>'. $data['judul_training'] .'</td>';
                                        echo '<td class="text-center">'. IndonesiaTgl($data['tanggal_mulai']) .' s/d '. IndonesiaTgl($data['tanggal_selesai']) .'</td>';
                                        echo '<td class="text-right">'. format_rupiah($data['budget_total']) .'</td>';
                                        echo '<td class="text-center">
                                              <a href="training_review.php?id='. $data['id_pengajuan'] .'" class="btn btn-primary btn-xs">
                                                <i class="fa fa-check"></i> Review
                                              </a>
                                              </td>';
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

<?php include("layout_bottom.php"); ?>
