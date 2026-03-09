<?php
include("sess_check.php");

// deskripsi halaman
$pagedesc = "Tambah Training Multiple Karyawan";
$menuparent = "training";
include("layout_top.php");
include("dist/function/format_tanggal.php");

// Ambil daftar karyawan aktif
$sql_karyawan = "SELECT e.npp, e.nama_emp, b.nama_bagian 
                 FROM employee e 
                 LEFT JOIN bagian b ON e.nama_bagian = b.id_bagian 
                 WHERE e.aktif = 'Aktif' 
                 ORDER BY e.nama_emp ASC";
$qry_karyawan = mysqli_query($conn, $sql_karyawan);
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    .rincian-budget-row {
        margin-bottom: 10px;
        padding: 10px;
        background: #f9f9f9;
        border-radius: 3px;
    }
    
    .budget-section {
        margin-top: 20px;
        padding: 15px;
        background: #f5f5f5;
        border-radius: 5px;
    }
</style>

<script type="text/javascript">
$(document).ready(function() {
    // Initialize select2 for multi-select karyawan
    $('#select_karyawan').select2({
        placeholder: 'Pilih karyawan (bisa lebih dari satu)',
        allowClear: true,
        width: '100%'
    });
    
    // Toggle budget section
    $('#chk_has_budget').change(function() {
        if ($(this).is(':checked')) {
            $('#budget-section').show();
        } else {
            $('#budget-section').hide();
            $('#rincian-container').html('');
            $('#budget_total').val('0');
        }
    });
    
    // Add rincian budget item
    $('#btn-add-rincian').click(function() {
        addRincianRow();
    });
    
    // Remove rincian budget item
    $(document).on('click', '.btn-remove-rincian', function() {
        $(this).closest('.rincian-budget-row').remove();
        calculateTotal();
    });
    
    // Calculate total budget when nilai changes
    $(document).on('input', '.input-nilai-rincian', function() {
        calculateTotal();
    });
    
    // Form validation
    $('#form-batch-training').on('submit', function(e) {
        let selectedKaryawan = $('#select_karyawan').val();
        if (!selectedKaryawan || selectedKaryawan.length === 0) {
            alert('Pilih minimal 1 karyawan!');
            e.preventDefault();
            return false;
        }
        
        // Disable submit button to prevent double submit
        $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
        
        return true;
    });
});

function addRincianRow() {
    let html = `
    <div class="rincian-budget-row">
        <div class="row">
            <div class="col-md-6">
                <input type="text" name="rincian_item[]" class="form-control" placeholder="Nama Item Budget" required>
            </div>
            <div class="col-md-4">
                <input type="number" name="rincian_nilai[]" class="form-control input-nilai-rincian" 
                       placeholder="Nilai (Rp)" min="0" step="1000" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-sm btn-remove-rincian">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
    `;
    $('#rincian-container').append(html);
}

function calculateTotal() {
    let total = 0;
    $('.input-nilai-rincian').each(function() {
        let val = parseFloat($(this).val()) || 0;
        total += val;
    });
    $('#budget_total').val(total);
}
</script>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">
                    <i class="fa fa-plus-circle"></i> <?php echo $pagedesc; ?>
                </h1>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <?php include("layout_alert.php"); ?>
                
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    <strong>Info:</strong> Fitur ini untuk menambahkan data training SAMA untuk BANYAK karyawan sekaligus. 
                    Data akan tersimpan dengan status <strong>Completed</strong> tanpa proses approval.
                </div>
            </div>
        </div>
        
        <form method="POST" action="training_batch_insert.php" id="form-batch-training">
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <i class="fa fa-users"></i> Form Tambah Training Batch
                        </div>
                        <div class="panel-body">
                            
                            <!-- Select Multiple Karyawan -->
                            <div class="form-group">
                                <label>Pilih Karyawan <span class="text-danger">*</span></label>
                                <select name="npp[]" id="select_karyawan" class="form-control" multiple required>
                                    <?php 
                                    while($k = mysqli_fetch_array($qry_karyawan)): 
                                    ?>
                                    <option value="<?php echo $k['npp']; ?>">
                                        <?php echo $k['npp'] . ' - ' . $k['nama_emp'] . ' (' . ($k['nama_bagian'] ?? '-') . ')'; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="help-block">Pilih satu atau lebih karyawan yang akan mengikuti training yang sama.</small>
                            </div>
                            
                            <hr>
                            <h4><strong>Detail Training</strong></h4>
                            
                            <div class="form-group">
                                <label>Judul Training <span class="text-danger">*</span></label>
                                <input type="text" name="judul_training" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Tujuan Training</label>
                                <textarea name="tujuan_training" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Penyelenggara <span class="text-danger">*</span></label>
                                        <input type="text" name="penyelenggara" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Lokasi Training <span class="text-danger">*</span></label>
                                        <input type="text" name="lokasi_training" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal Mulai <span class="text-danger">*</span></label>
                                        <input type="date" name="tanggal_mulai" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal Selesai <span class="text-danger">*</span></label>
                                        <input type="date" name="tanggal_selesai" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <!-- Budget Section (Optional) -->
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="chk_has_budget" name="has_budget" value="1">
                                    Tambahkan Budget (Opsional)
                                </label>
                            </div>
                            
                            <div id="budget-section" class="budget-section" style="display:none;">
                                <h5><strong>Rincian Budget</strong></h5>
                                <div id="rincian-container">
                                    <!-- Rincian items will be added here -->
                                </div>
                                <button type="button" class="btn btn-success btn-sm" id="btn-add-rincian">
                                    <i class="fa fa-plus"></i> Tambah Item Budget
                                </button>
                                <div class="form-group" style="margin-top:15px;">
                                    <label><strong>Total Budget:</strong></label>
                                    <input type="text" name="budget_total" id="budget_total" class="form-control" readonly value="0">
                                </div>
                            </div>
                            
                        </div>
                        
                        <div class="panel-footer">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa fa-save"></i> Simpan Training untuk Semua Karyawan Terpilih
                            </button>
                            <a href="training_list.php" class="btn btn-default btn-lg">
                                <i class="fa fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include("layout_bottom.php"); ?>
