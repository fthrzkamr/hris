<?php
	require_once(file_exists(__DIR__ . "/libur_helper.php") ? __DIR__ . "/libur_helper.php" : dirname(__DIR__) . "/libur_helper.php");
	include("sess_check.php");

	// Deskripsi halaman
	$pagedesc = "Approved";
	include("layout_top.php");
	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");
	$id = $sess_mngid;
?>
<!-- Top of file -->
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Data Cuti Approved</h1>
            </div><!-- /.col-lg-12 -->
        </div><!-- /.row -->
        
        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                    <?php
                        $Sql = "SELECT cuti.*, employee.* FROM cuti, employee 
                                WHERE cuti.npp=employee.npp 
                                AND cuti.npp='$id' 
                                ORDER BY cuti.tgl_pengajuan DESC";
                        $Qry = mysqli_query($conn, $Sql);
                    ?>                        
                        <table class="table table-striped table-bordered table-hover" id="tabel-data">
                            <thead>
                                <tr>
                                    <th width="1%">No</th>
                                    <th width="5%">No Cuti</th>
                                    <th width="5%">Tgl Pengajuan</th>
                                    <th width="5%">Tgl Awal</th>
                                    <th width="5%">Tgl Akhir</th>
                                    <th width="5%">Durasi</th>
                                    <th width="5%">Status</th>
                                    <th width="10%">Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $i=1;
                                    while($data = mysqli_fetch_array($Qry)){
                                        // Cek apakah cuti sudah Approved atau Rejected
                                            // Hitung selisih hari antara tanggal pemeriksaan dan hari ini
                                        $tanggal_pemeriksaan = new DateTime($data['tgl_pengajuan']);
                                        $tanggal_sekarang = new DateTime();
                                        $selisih_hari = $tanggal_pemeriksaan->diff($tanggal_sekarang)->days;

                                        $isDisabled = ($data['stt_cuti'] == 'Approved' || $data['stt_cuti'] == 'Rejected' || $selisih_hari >= 7) ? 'disabled-link' : '';

                                        // Hitung ulang durasi (tanpa hari Minggu & Libur Nasional) agar data lama yang belum
                                        // memakai perhitungan libur nasional tetap tampil benar
                                        $durasi_tampil = hitung_durasi_cuti($data['tgl_awal'], $data['tgl_akhir']);

                                        echo '<tr>';
                                        echo '<td class="text-center">'. $i .'</td>';
                                        echo '<td class="text-center">'. $data['no_cuti'] .'</td>';
                                        echo '<td class="text-center">'. IndonesiaTgl($data['tgl_pengajuan']) .'</td>';
                                        echo '<td class="text-center">'. IndonesiaTgl($data['tgl_awal']) .'</td>';
                                        echo '<td class="text-center">'. IndonesiaTgl($data['tgl_akhir']) .'</td>';
                                        echo '<td class="text-center">'. $durasi_tampil .' Hari</td>';
                                        echo '<td class="text-center">'. $data['stt_cuti'] .'</td>';
                                        echo '<td class="text-center">
                                              <a href="#myModal" data-toggle="modal" data-load-code="'.$data['no_cuti'].'" data-remote-target="#myModal .modal-body" class="btn btn-primary btn-xs">Detail</a>
                                              <a href="app_cetak.php?no='.$data['no_cuti'].'" target="_blank" class="btn btn-warning btn-xs">Cetak</a>
                                              <a href="cuti_hapus.php?no_cuti='.$data['no_cuti'].'" onclick="return confirm(\'Apakah anda yakin akan menghapus cuti No. '.$data['no_cuti'].'?\');" class="btn btn-danger btn-xs '.$isDisabled.'">Hapus</a>
                                              </td>';
                                        echo '</tr>';                                                
                                        $i++;
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Large modal -->
                    <div class="modal fade bs-example-modal" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-body">
                                    <p>Sedang memproses…</p>
                                </div>
                            </div>
                        </div>
                    </div>    
                </div><!-- /.panel -->
            </div><!-- /.col-lg-12 -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div><!-- /#page-wrapper -->

<!-- Bottom of file -->
<style>
    .disabled-link {
        pointer-events: none;
        opacity: 0.5;
    }
</style>

<script type="text/javascript">
	$(document).ready(function() {
		$('#tabel-data').DataTable({
			"responsive": true,
			"processing": true,
			"columnDefs": [
				{ "orderable": false, "targets": [] }
			]
		});
		
		$('#tabel-data').parent().addClass("table-responsive");
	});

	var app = {
			code: '0'
		};
		
		$('[data-load-code]').on('click',function(e) {
					e.preventDefault();
					var $this = $(this);
					var code = $this.data('load-code');
					if(code) {
						$($this.data('remote-target')).load('cuti_detail.php?code='+code);
						app.code = code;
						
					}
		});		

</script>
<?php
	include("layout_bottom.php");
?>