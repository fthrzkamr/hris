<?php
include("sess_check.php");

if (isset($row_sess['status_karyawan']) && $row_sess['status_karyawan'] == 'Magang') {
	header("location: index.php");
	exit;
}

// Deskripsi halaman
$pagedesc = "Pinjaman";
include("layout_top.php");
include("dist/function/format_rupiah.php");
// Ambil NPP user yang login
$npp = $sess_mngid;

?>
<style>
    .panel-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .status-display {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 5px;
        font-weight: bold;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        font-size: 10px;
    }

    .status-display.aktif {
        background-color:rgb(149, 0, 0);
        color:rgb(255, 255, 255);
    }

    .status-display.lunas {
        background-color:rgb(11, 142, 11);
        color: #ffffff;
    }

    .status-display.menunggu {
        background-color: #f0ad4e;
        color: #ffffff;
    }

    .status-display.ditolak {
        background-color: #d9534f;
        color: #ffffff;
    }
</style>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">

        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Pinjaman</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="panel-heading" style="padding-left:0; padding-right:0; margin-bottom:15px;">
                            <h4 style="margin:0;">Jadwal Pinjaman Anda</h4>
                            <a href="pinjaman_tambah.php" class="btn btn-primary btn-sm">+ Ajukan Pinjaman</a>
                        </div>
                        
                        <?php
                        $sql_pinjaman = "SELECT p.id_pinjaman, p.jumlah_pinjaman, p.cicilan_per_bulan, p.tenor, p.status
                                         FROM pinjaman p
                                         WHERE p.npp = ? 
                                         ORDER BY p.tanggal_pengajuan DESC";
                        $stmt_pinjaman = mysqli_prepare($conn, $sql_pinjaman);
                        mysqli_stmt_bind_param($stmt_pinjaman, "s", $npp);
                        mysqli_stmt_execute($stmt_pinjaman);
                        $res_pinjaman = mysqli_stmt_get_result($stmt_pinjaman);

                        if (mysqli_num_rows($res_pinjaman) > 0):
                        ?>
                            <div class="panel panel-info mt-4">
                                <div class="panel-heading"><strong>Daftar Pinjaman Anda</strong></div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>ID Pinjaman</th>
                                                    <th>Total Pinjaman</th>
                                                    <th>Cicilan/Bulan</th>
                                                    <th>Tenor</th>
                                                    <th>Sudah Dibayar</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                while ($data = mysqli_fetch_array($res_pinjaman)) {
                                                    $id_pinjaman = $data['id_pinjaman'];
                                                    $sql_angsuran = "SELECT SUM(jumlah_angsuran) AS total_dibayar FROM angsuran_pinjaman WHERE id_pinjaman = '$id_pinjaman' AND status = 'dibayar'";
                                                    $res_angsuran = mysqli_query($conn, $sql_angsuran);
                                                    $angsuran = mysqli_fetch_assoc($res_angsuran);
                                                    $total_dibayar = $angsuran['total_dibayar'] ?? 0;
                                                ?>
                                                    <tr>
                                                        <td class="text-center"><?= $data['id_pinjaman']; ?></td>
                                                        <td class="text-center"><?= format_rupiah($data['jumlah_pinjaman']); ?></td>
                                                        <td class="text-center"><?= format_rupiah($data['cicilan_per_bulan']); ?></td>
                                                        <td class="text-center"><?= $data['tenor']; ?>x</td>
                                                        <td class="text-center"><?= format_rupiah($total_dibayar); ?></td>
                                                        <td class="text-center">
                                                            <div class="status-display <?= $data['status']; ?>">
                                                                <?= ucfirst($data['status']); ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Anda tidak memiliki riwayat pengajuan pinjaman saat ini.
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.container-fluid -->
</div><!-- /#page-wrapper -->

<?php include("layout_bottom.php"); ?>