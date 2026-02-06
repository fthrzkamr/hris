<?php
include("sess_check.php");

// Deskripsi halaman
$pagedesc = "Data Reimburse";
include("layout_top.php");
include("dist/function/format_tanggal.php");
include("dist/function/format_rupiah.php");
$id = $sess_mngid;
?>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Data Rincian Pengajuan Kacamata</h1>
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
                        $Sql = "SELECT kacamata.*, employee.* FROM kacamata, employee 
                                WHERE kacamata.npp=employee.npp AND kacamata.npp='$id' 
                                ORDER BY kacamata.tanggal_pengajuan DESC";
                        $Qry = mysqli_query($conn, $Sql);
                        ?>                        
                        <table class="table table-striped table-bordered table-hover" id="tabel-data">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Karyawan</th>
                                    <th>Jenis Kacamata</th>
                                    <th>Tempat Fasilitas</th>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Total Kwitansi</th>
                                    <th>Total Reimburse</th>
                                    <th>Status</th>
                                    <th>Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                while ($data = mysqli_fetch_array($Qry)) {
                                    $rembes = $data['total_kwintansi'];
                                    $a = (80 / 100);
                                    $p = $rembes * $a;
                                    $status = $data['status']; // Ambil status

                                    echo '<tr>';
                                    echo '<td class="text-center">' . $i . '</td>';
                                    echo '<td class="text-center">' . $data['nama_emp'] . '</td>';
                                    echo '<td class="text-center">' . $data['jenis_kacamata'] . '</td>';
                                    echo '<td class="text-center">' . $data['nama_fasilitas'] . '</td>';
                                    echo '<td class="text-center">' . IndonesiaTgl($data['tanggal_pengajuan']) . '</td>';
                                    echo '<td class="text-center">' . number_format($data['total_kwintansi'], 0, ".", ".") . '</td>';
                                    echo '<td class="text-center">' . number_format($p, 0, ".", ".") . '</td>';
                                    echo '<td class="text-center"><strong>' . $status . '</strong></td>';
                                    echo '<td class="text-center">
                                            <a href="#myModal" data-toggle="modal" data-load-code="' . $data['id_kacamata'] . '" data-remote-target="#myModal .modal-body" class="btn btn-primary btn-xs">Detail</a>';

                                    // Tombol Hapus (Nonaktif jika status "Rejected" atau "Approved")
                                    $disabled = ($status == 'Rejected' || $status == 'Approved') ? 'disabled' : '';
                                    echo '<a href="kacamata_hapus.php?id_kacamata=' . $data['id_kacamata'] . '" onclick="return confirm(\'Apakah Anda yakin akan menghapus Data Reimburse ' . $data['id_kacamata'] . '?\');" class="btn btn-danger btn-xs" ' . $disabled . '>Hapus</a>';

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
            $($this.data('remote-target')).load('kacamata_detail.php?code=' + code);
            app.code = code;
        }
    });
</script>

<?php include("layout_bottom.php"); ?>
