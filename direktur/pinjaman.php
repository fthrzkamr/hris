<?php
include("sess_check.php");

if (isset($row_sess['status_karyawan']) && $row_sess['status_karyawan'] == 'Magang') {
	header("location: index.php");
	exit;
}

$pagedesc = "Data Pinjaman";
include("layout_top.php");
include("dist/function/format_tanggal.php");
include("dist/function/format_rupiah.php");

if (isset($_GET['success']) && $_GET['success'] == 1) {
    echo "<script>
        setTimeout(() => {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data pinjaman berhasil diperbarui.',
                showConfirmButton: false,
                timer: 3000
            });
        }, 500);
    </script>";
}
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
        /* Warna abu-abu */
        color:rgb(255, 255, 255);
        /* Warna teks hitam */
    }

    .status-display.lunas {
        background-color:rgb(11, 142, 11);
        /* Warna hijau */
        color: #ffffff;
        /* Warna teks putih */
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Pinjaman Karyawan</h1>
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
                            <h4 class="mb-0">Daftar Pinjaman</h4>
                            <a href="pinjaman_tambah.php" class="btn btn-primary btn-sm">+ Tambah Pinjaman</a>
                        </div>
                        <table class="table table-striped table-bordered table-hover" id="tabel-data">
                            <br>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Jumlah</th>
                                    <th>Tenor (x)</th>
                                    <th>Cicilan</th>
                                    <th>Sisa Pinjaman</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                $sql = "SELECT p.id_pinjaman, p.npp, e.nama_emp, p.tanggal_pengajuan, p.jumlah_pinjaman, p.tenor, p.status, p.cicilan_per_bulan
                                        FROM pinjaman p
                                        JOIN employee e ON p.npp = e.npp
                                        ORDER BY p.tanggal_pengajuan DESC";
                                $ress = mysqli_query($conn, $sql) or die(mysqli_error($conn));
                                while ($data = mysqli_fetch_array($ress)) {
                                    // Hitung total angsuran yang sudah dibayar
                                    $id_pinjaman = $data['id_pinjaman'];
                                    $sql_angsuran = "SELECT SUM(jumlah_angsuran) AS total_dibayar 
                                                    FROM angsuran_pinjaman 
                                                    WHERE id_pinjaman = '$id_pinjaman' AND status = 'dibayar'";
                                    $res_angsuran = mysqli_query($conn, $sql_angsuran);
                                    $angsuran = mysqli_fetch_assoc($res_angsuran);
                                    $total_dibayar = $angsuran['total_dibayar'] ?? 0;

                                    // Hitung sisa pinjaman
                                    $sisa_pinjaman = $data['jumlah_pinjaman'] - $total_dibayar;
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $i ?></td>
                                        <td class="text-center"><?= $data['nama_emp'] ?></td>
                                        <td class="text-center"><?= format_tanggal($data['tanggal_pengajuan']) ?></td>
                                        <td class="text-center"><?= format_rupiah($data['jumlah_pinjaman']) ?></td>
                                        <td class="text-center"><?= $data['tenor'] ?>x</td>
                                        <td class="text-center"><?= format_rupiah($data['cicilan_per_bulan']) ?></td>
                                        <td class="text-center"><?= format_rupiah($sisa_pinjaman) ?></td> <!-- sisa pinjaman ditampilkan -->
                                        <td class="text-center status <?= $data['status'] ?>">
                                            <div class="status-display <?= $data['status'] ?>">
                                                <?= ucfirst($data['status']) ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <a href="pinjaman_hapus.php?id=<?= $data['id_pinjaman'] ?>" class="btn btn-warning btn-xs">Hapus</a>
                                        </td>
                                    </tr>
                                <?php $i++;
                                } ?>
                            </tbody>
                        </table>
                    </div> <!-- /.panel-body -->
                </div> <!-- /.panel -->
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#tabel-data').DataTable({
            "responsive": true,
            "processing": true
        });
    });
</script>

<?php include("layout_bottom.php"); ?>