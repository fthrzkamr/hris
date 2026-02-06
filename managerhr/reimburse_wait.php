<?php
include("sess_check.php");

// deskripsi halaman
$pagedesc = "Waiting Approval";
include("layout_top.php");
include("dist/function/format_tanggal.php");
include("dist/function/format_rupiah.php");
$id = $sess_mngid;
?>
<!-- top of file -->
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Data Approval Reimburse</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="panel-heading">
                            <a href="reimburse_xls.php" class="btn btn-success">Data Reimburse.xls</a>
                        </div>

                        <?php
                        $Sql = "SELECT rembes.*, employee.* FROM rembes, employee 
                                WHERE rembes.npp=employee.npp 
                                AND YEAR(rembes.tanggal_pemeriksaan) = 2025
                                ORDER BY
                                    CASE 
                                        WHEN rembes.status = 'Menunggu di Approve' THEN 1 
                                        ELSE 2 
                                    END,
                                    rembes.tanggal_pemeriksaan DESC";

                        $Qry = mysqli_query($conn, $Sql);
                        ?>                        
                        <table class="table table-striped table-bordered table-hover" id="tabel-data">
                            <thead>
                                <tr>
                                    <th width="1%">No</th>
                                    <th width="10%">Nama Karyawan</th>
                                    <th width="15%">Nama Dokter</th>
                                    <th width="5%">Tgl Pemeriksaan</th>
                                    <th width="5%">Total Kwitansi</th>
                                    <th width="5%">Total Rembes</th>
                                    <th width="5%">Sisa Limit</th>
                                    <th width="10%">Status</th>
                                    <th width="10%">Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                while ($data = mysqli_fetch_array($Qry)) {
                                    $limit  = $data['kesehatan'];
                                    $rembes = $data['total_kwitansi'];
                                    $a      = (80 / 100);
                                    $p      = $rembes * $a;
                                    $file_bukti = $data['foto']; // Nama file yang tersimpan di database
                                    echo '<tr>';
                                    echo '<td class="text-center">' . $i . '</td>';
                                    echo '<td class="text-center"><a href="#myModal" data-toggle="modal" data-load-npp="' . $data['npp'] . '" data-remote-target="#myModal .modal-body">' . $data['nama_emp'] . '</a></td>';
                                    echo '<td class="text-center">' . $data['nama_dokter'] . '</td>';
                                    echo '<td class="text-center">' . IndonesiaTgl($data['tanggal_pemeriksaan']) . '</td>';
                                    echo '<td class="text-center">' . number_format($data['total_kwitansi'], 0, ".", ".") . '</td>';
                                    echo '<td class="text-center">' . number_format($p, 0, ".", ".") . '</td>';
                                    echo '<td class="text-center">' . number_format($data['kesehatan'], 0, ".", ".") . '</td>';
                                    echo '<td class="text-center">' . $data['status'] . '</td>';
                                    echo '<td class="text-center">';
                                    ?>
                                    <a href="reimburse_review.php?no=<?php echo $data['id_rmbs']; ?>" class="btn btn-primary btn-xs">Review</a>
                                    <?php
                                    echo '</td>';
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
                </div>
            </div>
        </div>
    </div>
</div>

<!-- bottom of file -->
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

    var app = { code: '0' };

    $('[data-load-code]').on('click', function(e) {
        e.preventDefault();
        var $this = $(this);
        var code = $this.data('load-code');
        if (code) {
            $($this.data('remote-target')).load('reimburse_detail.php?code=' + code);
            app.code = code;
        }
    });

    $('[data-load-npp]').on('click', function(e) {
        e.preventDefault();
        var $this = $(this);
        var npp = $this.data('load-npp');
        if (npp) {
            $($this.data('remote-target')).load('karyawan_detail.php?code=' + npp);
            app.npp = npp;
        }
    });
</script>

<?php
include("layout_bottom.php");
?>
