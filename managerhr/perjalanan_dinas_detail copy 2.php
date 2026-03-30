<?php
include("sess_check.php");
$pagedesc = 'Detail Perjalanan Dinas';
$menuparent = 'perjalanan_dinas';
include("layout_top.php");

// DB connection
include __DIR__ . '/dist/config/koneksi.php';

// Get ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo '<div class="alert alert-danger">ID tidak valid.</div>';
    include 'layout_bottom.php';
    exit;
}

// Fetch main record
$stmt = mysqli_prepare($conn, "SELECT * FROM perjalanan_dinas WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo '<div class="alert alert-danger">Data tidak ditemukan.</div>';
    include 'layout_bottom.php';
    exit;
}

// Fetch rincian
$stmt_rincian = mysqli_prepare($conn, "SELECT * FROM perjalanan_rincian WHERE perjalanan_id = ? ORDER BY nomor");
mysqli_stmt_bind_param($stmt_rincian, 'i', $id);
mysqli_stmt_execute($stmt_rincian);
$result_rincian = mysqli_stmt_get_result($stmt_rincian);
$rincian_items = [];
while ($row = mysqli_fetch_assoc($result_rincian)) {
    $rincian_items[] = $row;
}

// Fetch submission status
$stmt_status = mysqli_prepare($conn, "SELECT * FROM perjalanan_pengajuan WHERE id_perjalanan = ? ORDER BY id DESC LIMIT 1");
mysqli_stmt_bind_param($stmt_status, 'i', $id);
mysqli_stmt_execute($stmt_status);
$result_status = mysqli_stmt_get_result($stmt_status);
$submission = mysqli_fetch_assoc($result_status);

// Check if can approve (status = DIAJUKAN)
$can_approve = ($submission && $submission['status'] === 'DIAJUKAN');
?>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <ol class="breadcrumb" style="margin-bottom:12px">
            <li><a href="index.php">Beranda</a></li>
            <li><a href="perjalanan_dinas_list.php">Perjalanan Dinas</a></li>
            <li class="active">Detail</li>
        </ol>

        <div class="row">
            <div class="col-lg-12">
                <h3 class="page-header" style="margin-top:0">Detail Perjalanan Dinas</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-md-9">
                <?php if ($submission): ?>
                    <div class="perj-status alert alert-info no-print">
                        <strong>Status:</strong>
                        <span class="badge"><?php echo htmlspecialchars($submission['status']); ?></span>
                        <small class="text-muted"> — Diajukan oleh <?php echo htmlspecialchars($submission['pengaju']); ?>
                            pada <?php echo date('d-m-Y H:i', strtotime($submission['tanggal_pengajuan'])); ?></small>
                        <?php if (!empty($submission['catatan'])): ?>
                            <div style="margin-top:6px"><strong>Catatan:</strong>
                                <?php echo htmlspecialchars($submission['catatan']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Tambah / Edit Rincian Modal -->
                <div id="rincianModal" class="modal fade" tabindex="-1" role="dialog">
                    <div class="modal-dialog">
                        <form id="rincianForm">
                            <input type="hidden" name="id" id="rincian_id" value="">
                            <input type="hidden" name="perjalanan_id" value="<?php echo (int) $id; ?>">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title">Tambah / Edit Item</h4>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group"><label>Keterangan</label><input name="ket" id="ket"
                                            class="form-control" required></div>
                                    <div class="form-group"><label>Nominal</label><input type="text" inputmode="numeric"
                                            pattern="[0-9.]*" name="nominal" id="nominal" class="form-control"
                                            oninput="this.value=this.value.replace(/[^0-9\.]/g,'');"></div>
                                    <div class="form-group"><label>Qty</label><input type="number" step="1" min="0"
                                            name="qty" id="qty" class="form-control"
                                            oninput="this.value=this.value.replace(/[^0-9]/g,'');"></div>
                                    <div class="form-group"><label>Perkiraan</label><input type="text"
                                            inputmode="numeric" pattern="[0-9.]*" name="perkiraan" id="perkiraan"
                                            class="form-control"
                                            oninput="this.value=this.value.replace(/[^0-9\.]/g,'');"></div>
                                    <div class="form-group"><label>Keterangan Tambahan</label><input name="keterangan"
                                            id="keterangan" class="form-control"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div class="panel-body">
                        <h4 class="mt-0">Ringkasan</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-condensed">
                                <tr>
                                    <th style="width:22%">Nama</th>
                                    <td><?php echo htmlspecialchars($data['nama']); ?></td>
                                    <th>Departemen</th>
                                    <td><?php echo htmlspecialchars($data['departemen']); ?></td>
                                </tr>
                                <tr>
                                    <th>Tanggal Perjalanan</th>
                                    <td><?php echo htmlspecialchars($data['tanggal_perjalanan']); ?></td>
                                    <th>Jumlah Hari</th>
                                    <td><?php echo htmlspecialchars($data['jumlah_hari']); ?> hari</td>
                                </tr>
                                <tr>
                                    <th>Kota Asal</th>
                                    <td><?php echo htmlspecialchars($data['kota_asal']); ?></td>
                                    <th>Kota Tujuan</th>
                                    <td><?php echo htmlspecialchars($data['kota_tujuan']); ?></td>
                                </tr>
                                <tr>
                                    <th>Tujuan</th>
                                    <td colspan="3"><?php echo nl2br(htmlspecialchars($data['tujuan'])); ?></td>
                                </tr>
                            </table>
                        </div>

                        <h4 style="margin-top:18px">Rincian Anggaran</h4>
                        <div class="table-responsive">
                            <table class="table table-striped table-condensed rincian-table">
                                <thead>
                                    <tr>
                                        <th class="text-center">NO</th>
                                        <th>KETERANGAN</th>
                                        <th class="text-right">NOMINAL</th>
                                        <th class="text-center">QTY</th>
                                        <th class="text-right">PERKIRAAN</th>
                                        <th class="text-right">TOTAL</th>
                                        <th>KETERANGAN</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($rincian_items)):
                                        foreach ($rincian_items as $item): ?>
                                            <tr>
                                                <td class="text-center"><?php echo htmlspecialchars($item['nomor']); ?></td>
                                                <td><?php echo htmlspecialchars($item['ket']); ?></td>
                                                <td class="text-right">
                                                    <?php echo (!empty($item['nominal']) && $item['nominal'] != '0') ? number_format((float) $item['nominal'], 0, ',', '.') : '-'; ?>
                                                </td>
                                                <td class="text-center"><?php echo htmlspecialchars($item['qty']); ?></td>
                                                <td class="text-right">
                                                    <?php echo number_format((float) $item['perkiraan'], 0, ',', '.'); ?></td>
                                                <td class="text-right">
                                                    <?php $nilai = !empty($item['nominal']) && $item['nominal'] != '0' ? (float) $item['nominal'] : (float) $item['perkiraan'];
                                                    echo number_format($nilai * (float) $item['qty'], 0, ',', '.'); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['keterangan']); ?></td>
                                                <td class="text-center">
                                                    <button class="btn btn-xs btn-warning btnEdit"
                                                        data-id="<?php echo (int) $item['id']; ?>"
                                                        data-ket="<?php echo htmlspecialchars($item['ket'], ENT_QUOTES); ?>"
                                                        data-nominal="<?php echo htmlspecialchars($item['nominal']); ?>"
                                                        data-qty="<?php echo htmlspecialchars($item['qty']); ?>"
                                                        data-perkiraan="<?php echo htmlspecialchars($item['perkiraan']); ?>"
                                                        data-keterangan="<?php echo htmlspecialchars($item['keterangan'], ENT_QUOTES); ?>">
                                                        <i class="fa fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-xs btn-danger btnDelete"
                                                        data-id="<?php echo (int) $item['id']; ?>">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Tidak ada rincian anggaran</td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td colspan="5" class="text-right"><strong>BUDGET TOTAL:</strong></td>
                                        <td id="budget_total" class="text-right">
                                            <strong><?php echo number_format((float) $data['budget_total'], 0, ',', '.'); ?></strong>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="no-print perj-actions">
                    <button id="btnAddItem" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Tambah
                        Item</button>
                    <?php if ($can_approve): ?>
                        <a href="perjalanan_dinas_approve.php?id=<?php echo $id; ?>" class="btn btn-success btn-sm"><i
                                class="fa fa-check"></i> Review & Approve</a>
                    <?php endif; ?>
                    <a href="perjalanan_dinas_list.php" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i>
                        Kembali ke List</a>
                </div>
            </div>

            <div class="col-md-3">
                <div class="perj-dok-box">
                    <h5 style="margin-top:0">Detail Dokumen</h5>
                    <p><strong>No. Dokumen:</strong><br><?php echo htmlspecialchars($data['no_dokumen']); ?></p>
                    <p><strong>Revisi:</strong><br><?php echo htmlspecialchars($data['revisi']); ?></p>
                    <p><strong>Tanggal:</strong><br><?php echo date('d-m-Y', strtotime($data['tanggal_dokumen'])); ?>
                    </p>
                    <hr>
                    <p style="font-size:13px"><strong>Rekening (Transfer)</strong><br>Mandiri — 60012166181<br>A/n
                        Auliya Nurul Haqim</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(function () {
        // Open add modal
        $('#btnAddItem').on('click', function () {
            $('#rincianForm')[0].reset();
            $('#rincian_id').val('');
            $('#rincianModal .modal-title').text('Tambah Item');
            $('#rincianModal').modal('show');
        });

        // Edit
        $(document).on('click', '.btnEdit', function () {
            $('#rincian_id').val($(this).data('id'));
            $('#ket').val($(this).data('ket'));
            $('#nominal').val($(this).data('nominal'));
            $('#qty').val($(this).data('qty'));
            $('#perkiraan').val($(this).data('perkiraan'));
            $('#keterangan').val($(this).data('keterangan'));
            $('#rincianModal .modal-title').text('Edit Item');
            $('#rincianModal').modal('show');
        });

        // Submit add/edit
        $('#rincianForm').on('submit', function (e) {
            e.preventDefault();
            var fd = $(this).serialize();
            $.post('perjalanan_rincian_save.php', fd, function (res) {
                if (res && res.success) {
                    // update budget total in DOM if provided
                    if (res.budget_total !== undefined) {
                        var fmt = res.budget_total.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        $('#budget_total').html('<strong>' + fmt + '</strong>');
                    }
                    Swal.fire({ icon: 'success', title: 'Tersimpan', showConfirmButton: false, timer: 900 }).then(function () { location.reload(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res && res.error ? res.error : 'Gagal menyimpan item' });
                }
            }, 'json').fail(function () { Swal.fire({ icon: 'error', title: 'Request gagal' }); });
        });

        // Delete
        $(document).on('click', '.btnDelete', function () {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Hapus item?',
                text: 'Item akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) {
                    $.post('perjalanan_rincian_delete.php', { id: id }, function (res) {
                        if (res && res.success) {
                            if (res.budget_total !== undefined) {
                                var fmt = res.budget_total.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                $('#budget_total').html('<strong>' + fmt + '</strong>');
                            }
                            Swal.fire({ icon: 'success', title: 'Terhapus', showConfirmButton: false, timer: 900 }).then(function () { location.reload(); });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Gagal', text: res && res.error ? res.error : 'Gagal menghapus' });
                        }
                    }, 'json').fail(function () { Swal.fire({ icon: 'error', title: 'Request gagal' }); });
                }
            });
        });
    });
</script>

<?php include("layout_bottom.php"); ?>