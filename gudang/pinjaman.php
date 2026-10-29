<?php
include("sess_check.php");

if (isset($row_sess['status_karyawan']) && $row_sess['status_karyawan'] == 'Magang') {
	header("location: index.php");
	exit;
}

// Deskripsi halaman
$pagedesc = "Laporan Gaji";
include("layout_top.php");
include("dist/function/format_rupiah.php");
?>
<style>
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
<?php


// Ambil NPP user yang login
$npp = $_SESSION['gudang'];

// Tahun & Bulan aktif
$tahunSekarang = isset($_GET['tahun']) ? $_GET['tahun'] : date("Y");
$bulanSekarang = isset($_GET['bulan']) ? $_GET['bulan'] : date("m");

// Bulan dalam bahasa Indonesia
$bulan = [
    "01" => "Januari",
    "02" => "Februari",
    "03" => "Maret",
    "04" => "April",
    "05" => "Mei",
    "06" => "Juni",
    "07" => "Juli",
    "08" => "Agustus",
    "09" => "September",
    "10" => "Oktober",
    "11" => "November",
    "12" => "Desember"
];

// Ambil data gaji
$sql = "SELECT e.npp, e.nama_emp, e.gaji_pokok, e.tunj_jabatan, e.tunj_kinerja, e.tunj_transport, e.total_gaji, 
               p.p_hasil, p.tanggal, p.p_keterlambatan, p.p_pinjaman, p.p_lain, p.desc_lain
        FROM employee e 
        LEFT JOIN laporan_potongan p ON e.npp = p.npp
        WHERE e.npp = ? AND DATE_FORMAT(p.tanggal, '%Y-%m') = ? 
        ORDER BY e.npp";
$stmt = mysqli_prepare($conn, $sql);
$periode = "$tahunSekarang-$bulanSekarang";
mysqli_stmt_bind_param($stmt, "ss", $npp, $periode);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">

        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Laporan Gaji</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <!-- Filter Form -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <form method="get" name="laporan">
                            <div class="form-group row">
                                <div class="col-sm-4">
                                    <label>Pilih Tahun</label>
                                    <select class="form-control" name="tahun" onchange="this.form.submit()">
                                        <?php for ($i = 2023; $i <= date("Y"); $i++): ?>
                                            <option value="<?= $i ?>" <?= ($i == $tahunSekarang ? "selected" : "") ?>><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <label>Pilih Bulan</label>
                                    <select class="form-control" name="bulan" onchange="this.form.submit()">
                                        <?php foreach ($bulan as $key => $namaBulan): ?>
                                            <option value="<?= $key ?>" <?= ($key == $bulanSekarang ? "selected" : "") ?>><?= $namaBulan ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-sm-4 text-right">
                                    <label>&nbsp;</label><br>
                                    <a href="gaji_download.php?tahun=<?= $tahunSekarang ?>&bulan=<?= $bulanSekarang ?>" class="btn btn-primary">Download Slip Gaji</a>
                                </div>
                            </div>
                        </form>

                        <!-- Tampilkan Pinjaman Aktif -->
                        <div style="margin-bottom: 15px; text-align: right;">
                            <a href="pinjaman_tambah.php" class="btn btn-primary btn-sm">+ Ajukan Pinjaman</a>
                        </div>
                        <?php
                        $sql_pinjaman = "SELECT p.id_pinjaman, p.jumlah_pinjaman, p.cicilan_per_bulan, p.tenor
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
                                <div class="panel-heading"><strong>Pinjaman Aktif</strong></div>
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

                                                    $sisa_pinjaman = $data['jumlah_pinjaman'] - $total_dibayar;
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
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>

        <!-- Tampilkan Slip Gaji -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <?php while ($row = mysqli_fetch_assoc($result)):
                        $gaji_bersih = $row['total_gaji'] - ($row['p_hasil'] ?? 0);
                        $tanggalSlip = $row['tanggal'];
                        $bulanIndo = $bulan[date("m", strtotime($tanggalSlip))];
                    ?>
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Data Slip Gaji - <?= $row['nama_emp']; ?> (<?= $row['npp']; ?>) Periode Bulan <?= $bulanIndo; ?>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>Nama</th>
                                            <td><?= $row['nama_emp']; ?></td>
                                            <th>NIP</th>
                                            <td><?= $row['npp']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Gaji Pokok</th>
                                            <td>Rp <?= number_format($row['gaji_pokok'], 0, ',', '.'); ?></td>
                                            <th>Potongan Keterlambatan</th>
                                            <td>Rp <?= number_format($row['p_keterlambatan'], 0, ',', '.'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Tunjangan Jabatan</th>
                                            <td>Rp <?= number_format($row['tunj_jabatan'], 0, ',', '.'); ?></td>
                                            <th>Potongan Pinjaman</th>
                                            <td>Rp <?= number_format($row['p_pinjaman'], 0, ',', '.'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Tunjangan Kinerja</th>
                                            <td>Rp <?= number_format($row['tunj_kinerja'], 0, ',', '.'); ?></td>
                                            <th>Potongan Lain</th>
                                            <td>Rp <?= number_format($row['p_lain'], 0, ',', '.'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Tunjangan Transport</th>
                                            <td>Rp <?= number_format($row['tunj_transport'], 0, ',', '.'); ?></td>
                                            <th>Keterangan Potongan Lain</th>
                                            <td><?= nl2br($row['desc_lain']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Total Gaji</th>
                                            <td>Rp <?= number_format($row['total_gaji'], 0, ',', '.'); ?></td>
                                            <th>Total Potongan</th>
                                            <td>Rp <?= number_format($gaji_bersih, 0, ',', '.'); ?></td>
                                        </tr>
                                        <tr class="table-success">
                                            <th colspan="4" class="text-center fs-5">
                                                Gaji Diterima: <strong>Rp <?= number_format($row['p_hasil'] ?? 0, 0, ',', '.'); ?></strong>
                                            </th>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

    </div><!-- /.container-fluid -->
</div><!-- /#page-wrapper -->

<?php include("layout_bottom.php"); ?>