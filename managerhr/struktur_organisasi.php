<?php
include("sess_check.php");

// Buat tabel jika belum ada
$create_table = "CREATE TABLE IF NOT EXISTS struktur_org (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_node VARCHAR(100) NOT NULL,
    parent_id INT DEFAULT 0,
    keterangan VARCHAR(100)
)";
mysqli_query($conn, $create_table);

// Proses Tambah / Update Struktur
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_struktur'])) {
    $nama_node = mysqli_real_escape_string($conn, $_POST['nama_node']);
    $parent_id = (int)$_POST['parent_id'];
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    
    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        $edit_id = (int)$_POST['edit_id'];
        $query = "UPDATE struktur_org SET nama_node='$nama_node', parent_id=$parent_id, keterangan='$keterangan' WHERE id=$edit_id";
        if (mysqli_query($conn, $query)) {
            $msg = "<div class='alert alert-success alert-dismissable'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button><i class='fa fa-check'></i> Struktur berhasil diperbarui!</div>";
        }
    } else {
        $query = "INSERT INTO struktur_org (nama_node, parent_id, keterangan) VALUES ('$nama_node', $parent_id, '$keterangan')";
        if (mysqli_query($conn, $query)) {
            $msg = "<div class='alert alert-success alert-dismissable'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button><i class='fa fa-check'></i> Struktur baru berhasil ditambahkan!</div>";
        }
    }
}

// Data untuk Form Edit
$is_edit = false;
$edit_data = ['id' => '', 'nama_node' => '', 'parent_id' => 0, 'keterangan' => ''];
if (isset($_GET['edit'])) {
    $is_edit = true;
    $id_edit = (int)$_GET['edit'];
    $q_edit = mysqli_query($conn, "SELECT * FROM struktur_org WHERE id = $id_edit");
    if ($row = mysqli_fetch_assoc($q_edit)) {
        $edit_data = $row;
    }
}

// Proses Hapus Struktur
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    // Hapus juga semua child node yang menginduk ke ID ini (untuk mencegah yatim piatu)
    mysqli_query($conn, "DELETE FROM struktur_org WHERE parent_id = $id_hapus");
    mysqli_query($conn, "DELETE FROM struktur_org WHERE id = $id_hapus");
    echo "<script>window.location='struktur_organisasi.php';</script>";
    exit;
}

// Proses Reset/Kosongkan Struktur
if (isset($_GET['reset'])) {
    mysqli_query($conn, "TRUNCATE TABLE struktur_org");
    echo "<script>window.location='struktur_organisasi.php';</script>";
    exit;
}

$pagedesc = "Struktur Organisasi";
include("layout_top.php");
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Builder Struktur Organisasi</h1>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-12">
            <?php if (isset($msg)) echo $msg; ?>
        </div>
        
        <!-- Kolom Kiri: Bagan Org Chart -->
        <div class="col-lg-9">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-sitemap fa-fw"></i> Visualisasi Struktur Perusahaan
                    <div class="pull-right">
                        <button type="button" class="btn btn-info btn-xs" onclick="downloadChart()" title="Download Gambar PNG"><i class="fa fa-download"></i> PNG</button>
                        &nbsp;&nbsp;
                        <button type="button" class="btn btn-default btn-xs" onclick="zoomIn()" title="Perbesar (Zoom In)"><i class="fa fa-search-plus"></i></button>
                        <button type="button" class="btn btn-default btn-xs" onclick="zoomOut()" title="Perkecil (Zoom Out)"><i class="fa fa-search-minus"></i></button>
                        &nbsp;&nbsp;
                        <a href="struktur_organisasi.php?reset=1" onclick="return confirm('Apakah Anda yakin ingin menghapus SELURUH struktur? Ini tidak bisa dibatalkan!');" class="btn btn-danger btn-xs">Kosongkan Semua</a>
                    </div>
                </div>
                <div class="panel-body" style="overflow-x: auto; background-color: #f9f9f9; text-align: center;">
                    <div id="chart_div" style="min-height: 400px; padding: 40px; display: inline-block; text-align: left;"></div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Form Builder -->
        <div class="col-lg-3">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa <?php echo $is_edit ? 'fa-pencil' : 'fa-plus-circle'; ?> fa-fw"></i> <?php echo $is_edit ? 'Edit Divisi / Jabatan' : 'Tambah Divisi / Jabatan'; ?>
                </div>
                <div class="panel-body">
                    <p class="text-muted small">Buat struktur organisasi perusahaan Anda dari nol secara bebas.</p>
                    <form method="POST" action="struktur_organisasi.php">
                        <?php if ($is_edit) { ?>
                            <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
                        <?php } ?>
                        <div class="form-group">
                            <label>Nama Jabatan / Divisi</label>
                            <input type="text" name="nama_node" class="form-control" placeholder="Contoh: Direktur Utama, Tim IT" value="<?php echo htmlspecialchars($edit_data['nama_node']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Atasan (Induk)</label>
                            <select name="parent_id" class="form-control">
                                <option value="0">-- Posisi Tertinggi (Tanpa Atasan) --</option>
                                <?php
                                $nodes = mysqli_query($conn, "SELECT id, nama_node FROM struktur_org ORDER BY nama_node ASC");
                                while($n = mysqli_fetch_assoc($nodes)) {
                                    // Jangan sampai node menjadi atasan bagi dirinya sendiri
                                    if ($is_edit && $n['id'] == $edit_data['id']) continue;
                                    $selected = ($n['id'] == $edit_data['parent_id']) ? "selected" : "";
                                    echo "<option value='".$n['id']."' $selected>".$n['nama_node']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Keterangan Tambahan (Opsional)</label>
                            <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Divisi Operasional" value="<?php echo htmlspecialchars($edit_data['keterangan']); ?>">
                        </div>
                        <button type="submit" name="simpan_struktur" class="btn btn-primary btn-block"><i class="fa fa-save"></i> <?php echo $is_edit ? 'Simpan Perubahan' : 'Tambahkan ke Bagan'; ?></button>
                        <?php if ($is_edit) { ?>
                            <a href="struktur_organisasi.php" class="btn btn-default btn-block">Batal Edit</a>
                        <?php } ?>
                    </form>
                </div>
            </div>
            
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-list fa-fw"></i> Daftar Struktur
                </div>
                <div class="panel-body" style="max-height: 300px; overflow-y: auto; padding: 0;">
                    <table class="table table-striped table-hover" style="margin-bottom: 0;">
                        <tbody>
                            <?php
                            $list = mysqli_query($conn, "SELECT A.*, B.nama_node as atasan FROM struktur_org A LEFT JOIN struktur_org B ON A.parent_id = B.id ORDER BY A.parent_id ASC");
                            if (mysqli_num_rows($list) == 0) {
                                echo "<tr><td class='text-center text-muted'>Belum ada struktur dibuat.</td></tr>";
                            }
                            while($row = mysqli_fetch_assoc($list)) {
                                echo "<tr>";
                                echo "<td><b style='font-size:13px;'>".$row['nama_node']."</b><br><small class='text-muted'>Atasan: ".($row['atasan'] ? $row['atasan'] : 'Puncak')."</small></td>";
                                echo "<td width='70' class='text-right'>
                                        <a href='?edit=".$row['id']."' class='btn btn-warning btn-xs' title='Edit'><i class='fa fa-pencil'></i></a>
                                        <a href='?hapus=".$row['id']."' onclick='return confirm(\"Hapus ".$row['nama_node']." dan semua bawahannya?\");' class='btn btn-danger btn-xs' title='Hapus'><i class='fa fa-trash'></i></a>
                                      </td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
var currentZoom = 1.0;

function zoomIn() {
    currentZoom += 0.1;
    applyZoom();
}

function zoomOut() {
    currentZoom -= 0.1;
    if (currentZoom < 0.3) currentZoom = 0.3; // Batas zoom terkecil
    applyZoom();
}

function applyZoom() {
    var chartObj = document.getElementById('chart_div');
    chartObj.style.transform = 'scale(' + currentZoom + ')';
    chartObj.style.transformOrigin = 'top center';
    chartObj.style.transition = 'transform 0.2s ease-in-out';
}

function downloadChart() {
    var chartObj = document.getElementById('chart_div');
    var containerObj = chartObj.parentElement;
    
    // Simpan skala lama dan kembalikan ke 1.0 agar hasil gambarnya tidak pecah atau terpotong
    var oldZoom = currentZoom;
    currentZoom = 1.0;
    applyZoom();
    
    // Simpan scroll position lama
    var oldScrollLeft = containerObj.scrollLeft;
    containerObj.scrollLeft = 0; // Reset scroll ke kiri sementara untuk menghindari crop
    
    // Tunggu sebentar agar animasi zoom out selesai, lalu ambil screenshot
    setTimeout(function() {
        html2canvas(chartObj, {
            scale: 2, // Resolusi tinggi (Retina)
            backgroundColor: "#f9f9f9",
            width: chartObj.scrollWidth,      // Ambil lebar ASLI penuh
            height: chartObj.scrollHeight,    // Ambil tinggi ASLI penuh
            windowWidth: chartObj.scrollWidth,
            windowHeight: chartObj.scrollHeight,
            x: 0,
            y: 0
        }).then(function(canvas) {
            // Kembalikan ke skala dan scroll semula
            currentZoom = oldZoom;
            applyZoom();
            containerObj.scrollLeft = oldScrollLeft;
            
            // Buat link download
            var link = document.createElement('a');
            link.download = 'Struktur_Organisasi_Lengkap.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    }, 500);
}
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
  google.charts.load('current', {packages:["orgchart"]});
  google.charts.setOnLoadCallback(drawChart);

  function drawChart() {
    var data = new google.visualization.DataTable();
    data.addColumn('string', 'Name');
    data.addColumn('string', 'Manager');
    data.addColumn('string', 'ToolTip');

    // Data dari database
    data.addRows(
        <?php
        $chart_nodes = [];
        
        $qorg = mysqli_query($conn, "SELECT * FROM struktur_org");
        while ($ro = mysqli_fetch_assoc($qorg)) {
            $parent = ($ro['parent_id'] == 0) ? '' : 'ID_' . $ro['parent_id'];
            
            // Format tampilan kotak
            $f = '<div style="font-weight:bold; font-size:12px; color:#333; line-height:1.2;">' . htmlspecialchars($ro['nama_node']) . '</div>';
            if (!empty($ro['keterangan'])) {
                $f .= '<div style="font-size:10px; color:#777; margin-top:3px; line-height:1.1;">' . htmlspecialchars($ro['keterangan']) . '</div>';
            }
            
            $chart_nodes[] = [
                ['v' => 'ID_' . $ro['id'], 'f' => $f],
                $parent,
                $ro['nama_node']
            ];
        }
        
        if (empty($chart_nodes)) {
            // Placeholder jika kosong
            $chart_nodes[] = [
                ['v' => 'START', 'f' => '<div style="font-weight:bold; color:#777;">Bagan Kosong</div>'], '', ''
            ];
        }
        
        echo json_encode($chart_nodes);
        ?>
    );

    var chart = new google.visualization.OrgChart(document.getElementById('chart_div'));
    chart.draw(data, {
        'allowHtml': true, 
        'allowCollapse': true,
        'size': 'small',
        'nodeClass': 'my-node-class',
        'selectedNodeClass': 'my-selected-node-class'
    });
  }
</script>

<style>
/* Mempercantik kotak Google Chart */
.my-node-class {
    border: 1px solid #ccc !important;
    border-radius: 6px !important;
    background-color: #fff !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    padding: 6px 10px !important;
    min-width: 90px !important;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important;
}
.my-selected-node-class {
    border: 2px solid #337ab7 !important;
    background-color: #e2f0fb !important;
}
.google-visualization-orgchart-linebottom {
    border-bottom: 2px solid #999 !important;
}
.google-visualization-orgchart-lineleft {
    border-left: 2px solid #999 !important;
}
.google-visualization-orgchart-lineright {
    border-right: 2px solid #999 !important;
}
.google-visualization-orgchart-linetop {
    border-top: 2px solid #999 !important;
}
</style>

<?php
include("layout_bottom.php");
?>
