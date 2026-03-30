<?php
$pagedesc = "Form Perjalanan Dinas";
$menuparent = "perjalanan_dinas";
include("sess_check.php");
include("layout_top.php");

?>
<div id="page-wrapper">
<?php

// Get user data from session
$nama_user = isset($sess_mngname) ? $sess_mngname : '';
$departemen_user = isset($sess_bagian) ? $sess_bagian : '';
$npp_user = isset($sess_mngid) ? $sess_mngid : '';

// If sess_bagian is empty or numeric, try to get nama_bagian by joining employee -> bagian
// prefer bagian.nama_bagian when available, otherwise fall back to employee.nama_bagian
if (empty($departemen_user) || is_numeric($departemen_user)) {
    $npp_esc = mysqli_real_escape_string($conn, $npp_user);
    $query_dept = "SELECT COALESCE(b.nama_bagian, e.nama_bagian) AS nama_bagian
                   FROM employee e
                   LEFT JOIN bagian b ON b.id_bagian = e.nama_bagian OR b.nama_bagian = e.nama_bagian
                   WHERE e.npp = '" . $npp_esc . "' LIMIT 1";
    $result_dept = mysqli_query($conn, $query_dept);
    if ($result_dept && mysqli_num_rows($result_dept) > 0) {
        $row_dept = mysqli_fetch_assoc($result_dept);
        $departemen_user = $row_dept['nama_bagian'];
    }
}

// kota tujuan akan diinput manual oleh user (tidak perlu query daftar kota)
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">Form Perjalanan Dinas</h1>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="row">
        <div class="col-lg-12">
            <div class="alert alert-success alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="fa fa-check"></i> <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="row">
        <div class="col-lg-12">
            <div class="alert alert-danger alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-plane fa-fw"></i> Form Pengajuan Perjalanan Dinas
            </div>
            <div class="panel-body">
                <form action="submit_perjalanan_dinas.php" method="POST" id="perjalananForm" role="form">

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label>Nama <span class="text-danger">*</span></label>
                                <input type="text" name="nama" class="form-control"
                                    value="<?php echo htmlspecialchars($nama_user); ?>" readonly
                                    style="background-color: #f5f5f5; cursor: not-allowed;">
                                <p class="help-block">Nama diambil dari akun yang sedang login</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label>Departemen <span class="text-danger">*</span></label>
                                <input type="text" name="departemen" class="form-control"
                                    value="<?php echo htmlspecialchars($departemen_user); ?>" readonly
                                    style="background-color: #f5f5f5; cursor: not-allowed;">
                                <p class="help-block">Departemen diambil dari data kepegawaian Anda</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-6">
                            <div class="form-group">
                                <label>Tanggal Perjalanan</label>
                                <input type="date" name="tanggal_perjalanan" class="form-control">
                                <p class="help-block">Kosongkan jika tanggal fleksibel</p>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6">
                            <div class="form-group">
                                <label>Jumlah Hari <span class="text-danger">*</span></label>
                                <input type="number" name="jumlah_hari" class="form-control" min="1"
                                    placeholder="Jumlah hari perjalanan" required>
                                <p class="help-block">Termasuk hari menginap</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-6">
                            <div class="form-group">
                                <label>Kota Asal</label>
                                <input type="text" name="kota_asal" class="form-control"
                                    placeholder="Kota keberangkatan">
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6">
                            <div class="form-group">
                                <label>Kota Tujuan <span class="text-danger">*</span></label>
                                <input type="text" name="kota_tujuan" class="form-control"
                                    placeholder="Kota tujuan perjalanan" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label>Tujuan Perjalanan Bisnis <span class="text-danger">*</span></label>
                                <textarea name="tujuan" class="form-control" rows="4"
                                    placeholder="Tujuan singkat dan alasan perjalanan (mis. Kunjungan pelanggan, training)"
                                    required></textarea>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-lg-12">
                            <h4><i class="fa fa-money"></i> Rincian Perkiraan Biaya</h4>
                            <p class="text-muted">Isi keterangan, qty, dan perkiraan sebagai estimasi. Nilai nominal
                                akhir akan ditentukan
                                oleh HR dan Manager HR.</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="rincianTable">
                                    <thead>
                                        <tr class="bg-primary">
                                            <th style="width:50px">#</th>
                                            <th>Keterangan</th>
                                            <th style="width:100px">Qty</th>
                                            <th style="width:150px">Perkiraan (Rp)</th>
                                            <th style="width:80px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="nomor text-center">1</td>
                                            <td><input type="text" name="ket[]" class="form-control input-sm"
                                                    placeholder="Contoh: HOTEL" required></td>
                                            <td><input type="number" name="qty[]" class="form-control input-sm" min="1"
                                                    value="1" required></td>
                                            <td><input type="text" name="perkiraan[]" class="form-control input-sm currency-input"
                                                    value="0" placeholder="0" required></td>
                                            <td class="text-center"><button type="button"
                                                    class="btn btn-xs btn-danger remove-row"><i
                                                        class="fa fa-trash"></i></button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <button type="button" id="addRow" class="btn btn-success btn-sm">
                                <i class="fa fa-plus"></i> Tambah Baris
                            </button>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-send"></i> Ajukan Perjalanan Dinas
                                </button>
                                <a href="index.php" class="btn btn-default">
                                    <i class="fa fa-times"></i> Batal
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div><!-- /.panel-body -->
        </div><!-- /.panel -->
    </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

<style>
    /* Custom styling for form responsiveness */
    @media (max-width: 768px) {

        #rincianTable th,
        #rincianTable td {
            font-size: 12px;
            padding: 5px;
        }

        #rincianTable input {
            font-size: 12px;
            padding: 4px;
        }

        .panel-heading {
            font-size: 14px;
        }

        h1.page-header {
            font-size: 24px;
        }
    }

    @media (max-width: 480px) {
        #rincianTable {
            font-size: 11px;
        }

        .btn-sm {
            font-size: 11px;
            padding: 4px 8px;
        }
    }

    /* Improve table appearance */
    #rincianTable thead tr {
        background-color: #337ab7;
        color: white;
    }

    #rincianTable tbody tr:hover {
        background-color: #f5f5f5;
    }


    /* Ensure inputs don't overflow their cells and are touch-friendly */
    #rincianTable input.form-control {
        height: 30px;
        width: 100%;
        box-sizing: border-box;
    }

    /* Allow horizontal scrolling on very small screens only; otherwise let table use 100% width */
    @media (max-width: 767px) {
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* reduce font and padding for table on small screens */
        #rincianTable th,
        #rincianTable td {
            font-size: 12px;
            padding: 6px;
        }
    }

    /* Use full width by default and allow cells to wrap; no forced min-width so the layout adapts */
    #rincianTable {
        width: 100%;
        table-layout: auto;
    }

    /* Ensure the page header text wraps instead of being clipped by sidebar */
    .page-header {
        white-space: normal;
        overflow: visible;
    }

    /* Required field indicator */
    .text-danger {
        color: #d9534f;
    }

    /* Help text styling */
    .help-block {
        font-size: 12px;
        color: #737373;
        margin-top: 5px;
    }
</style>

<script>
    $(document).ready(function () {
        // Add row functionality
        $('#addRow').on('click', function () {
            var tbody = $('#rincianTable tbody');
            var rowCount = tbody.find('tr').length + 1;
            var newRow = `
            <tr>
                <td class="nomor text-center">${rowCount}</td>
                <td><input type="text" name="ket[]" class="form-control input-sm" placeholder="Contoh: TRANSPORTASI" required></td>
                <td><input type="number" name="qty[]" class="form-control input-sm" min="1" value="1" required></td>
                <td><input type="text" name="perkiraan[]" class="form-control input-sm currency-input" value="0" placeholder="0" required></td>
                <td class="text-center"><button type="button" class="btn btn-xs btn-danger remove-row"><i class="fa fa-trash"></i></button></td>
            </tr>
        `;
            tbody.append(newRow);
            updateRowNumbers();
        });

        // Remove row functionality
        $(document).on('click', '.remove-row', function () {
            var tbody = $('#rincianTable tbody');
            if (tbody.find('tr').length > 1) {
                $(this).closest('tr').remove();
                updateRowNumbers();
            } else {
                alert('Minimal harus ada 1 baris rincian biaya!');
            }
        });

        // Update row numbers
        function updateRowNumbers() {
            $('#rincianTable tbody tr').each(function (index) {
                $(this).find('.nomor').text(index + 1);
            });
        }

        // Currency formatting functions
        function formatCurrency(num) {
            // Remove non-digit characters
            num = num.toString().replace(/\D/g, '');
            // Format with thousand separator (dot)
            return num.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        // Auto-format currency input on keyup
        $(document).on('keyup', '.currency-input', function () {
            var val = $(this).val();
            var formatted = formatCurrency(val);
            $(this).val(formatted);
        });

        // Initialize existing currency fields
        $('.currency-input').each(function() {
            var val = $(this).val();
            if (val && val !== '0') {
                $(this).val(formatCurrency(val));
            }
        });

        // Form validation and confirmation
        $('#perjalananForm').on('submit', function (e) {
            var rowCount = $('#rincianTable tbody tr').length;
            if (rowCount < 1) {
                e.preventDefault();
                alert('Minimal harus ada 1 baris rincian biaya!');
                return false;
            }

            // Calculate total
            var total = 0;
            $('#rincianTable tbody tr').each(function () {
                var qty = parseInt($(this).find('input[name="qty[]"]').val()) || 0;
                var harga = parseInt($(this).find('input[name="perkiraan[]"]').val().replace(/\./g, '')) || 0;
                total += (qty * harga);
            });

            var confirmation = confirm('Anda akan mengajukan perjalanan dinas dengan estimasi biaya Rp ' + total.toLocaleString('id-ID') + '. Pengajuan akan langsung dikirim untuk persetujuan. Lanjutkan?');
            if (!confirmation) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>

<?php include("layout_bottom.php"); ?>

</div>