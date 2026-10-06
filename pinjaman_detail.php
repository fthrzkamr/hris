<?php
include("sess_check.php");

if (isset($row_sess['status_karyawan']) && $row_sess['status_karyawan'] == 'Magang') {
	header("location: index.php");
	exit;
}

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Ambil data pinjaman
    $sql = "SELECT p.*, e.nama_emp 
            FROM pinjaman p 
            JOIN employee e ON p.npp = e.npp 
            WHERE p.id_pinjaman = '$id'";
    $ress = mysqli_query($conn, $sql);
    $data = mysqli_fetch_array($ress);
}

$pagedesc = "Detail Pinjaman";
$menuparent = "pinjaman";
include("layout_top.php");
include("dist/function/format_tanggal.php");
include("dist/function/format_rupiah.php");
?>

<style>
    .status-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 11px;
    }
    .status-belum {
        background-color: #d9534f;
        color: white;
    }
    .status-dibayar {
        background-color: #5cb85c;
        color: white;
    }
</style>

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Detail Pinjaman</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading"><h3>Informasi Pinjaman</h3></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table">
                                    <tr>
                                        <th width="200">ID Pinjaman</th>
                                        <td><?= $data['id_pinjaman'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>NPP</th>
                                        <td><?= $data['npp'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Nama Karyawan</th>
                                        <td><?= $data['nama_emp'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Tanggal Pengajuan</th>
                                        <td><?= format_tanggal($data['tanggal_pengajuan']) ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table">
                                    <tr>
                                        <th width="200">Jumlah Pinjaman</th>
                                        <td><?= format_rupiah($data['jumlah_pinjaman']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Tenor</th>
                                        <td><?= $data['tenor'] ?> Bulan</td>
                                    </tr>
                                    <tr>
                                        <th>Cicilan per Bulan</th>
                                        <td><?= format_rupiah($data['cicilan_per_bulan']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <?php
                                            $label_class = 'default';
                                            if ($data['status'] == 'aktif') $label_class = 'danger';
                                            elseif ($data['status'] == 'lunas') $label_class = 'success';
                                            elseif ($data['status'] == 'menunggu') $label_class = 'warning';
                                            elseif ($data['status'] == 'ditolak') $label_class = 'default';
                                            ?>
                                            <span class="label label-<?= $label_class ?>"><?= ucfirst($data['status']) ?></span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <?php if ($data['keterangan']) { ?>
                        <div class="row">
                            <div class="col-md-12">
                                <strong>Keterangan:</strong>
                                <p><?= nl2br($data['keterangan']) ?></p>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading"><h3>Jadwal Angsuran</h3></div>
                    <div class="panel-body">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th class="text-center">No</th>
                                    <th class="text-center">Bulan</th>
                                    <th class="text-center">Tahun</th>
                                    <th class="text-center">Tanggal Potong</th>
                                    <th class="text-center">Jumlah Angsuran</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_angsuran = "SELECT * FROM angsuran_pinjaman 
                                                WHERE id_pinjaman = '$id' 
                                                ORDER BY tahun ASC, bulan ASC";
                                $res_angsuran = mysqli_query($conn, $sql_angsuran);
                                
                                if (mysqli_num_rows($res_angsuran) > 0) {
                                    $no = 1;
                                    $bulan_nama = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                                    while ($angsuran = mysqli_fetch_array($res_angsuran)) {
                                        ?>
                                        <tr>
                                            <td class="text-center"><?= $no++ ?></td>
                                            <td class="text-center"><?= $bulan_nama[$angsuran['bulan']] ?></td>
                                            <td class="text-center"><?= $angsuran['tahun'] ?></td>
                                            <td class="text-center"><?= format_tanggal($angsuran['tanggal_potong']) ?></td>
                                            <td class="text-center"><?= format_rupiah($angsuran['jumlah_angsuran']) ?></td>
                                            <td class="text-center">
                                                <span class="status-badge status-<?= $angsuran['status'] ?>">
                                                    <?= $angsuran['status'] == 'dibayar' ? 'LUNAS' : 'BELUM DIBAYAR' ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center">Belum ada jadwal angsuran</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="panel-footer">
                        <a href="pinjaman.php" class="btn btn-default">Kembali</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("layout_bottom.php"); ?>
