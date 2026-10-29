<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header"><i class="fa fa-pencil-square-o"></i> Hasil Test Calon Karyawan (DISC)</h1>
            </div><!-- /.col-lg-12 -->
        </div><!-- /.row -->
        
        <div class="row">
            <div class="col-lg-12">
                <?php include("layout_alert.php"); ?>
            </div>
        </div>
        
        <!-- Tabel: Hasil Test Calon Karyawan -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default" style="border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); overflow: hidden;">
                    <div class="panel-heading" style="background-color: #f8f9fa; font-weight: 700; color: #333;">
                        <i class="fa fa-list"></i> &nbsp;Daftar Hasil Instrumen DISC Personality
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="tabel-data">
                                <thead>
                                    <tr>
                                        <th width="3%" class="text-center">No</th>
                                        <th width="20%">Nama Calon</th>
                                        <th width="12%" class="text-center">Tgl Lahir</th>
                                        <th width="12%" class="text-center">Tgl Test</th>
                                        <th width="15%">Posisi / Bagian</th>
                                        <th width="23%" class="text-center">Skor (2 Tertinggi)</th>
                                        <th width="15%" class="text-center">Dominan</th>
                                        <th width="10%" class="text-center">Opsi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;
                                    $sql = "SELECT * FROM test_calon_karyawan ORDER BY id DESC";
                                    $ress = mysqli_query($conn, $sql);
                                    if ($ress) {
                                        while ($data = mysqli_fetch_array($ress)) {
                                            // DISC mapping: earth=D, fire=I, air=C, water=S
                                            $scores = [
                                                'Dominance'        => intval($data['skor_earth']),
                                                'Influence'        => intval($data['skor_fire']),
                                                'Conscientiousness'=> intval($data['skor_air']),
                                                'Steadiness'       => intval($data['skor_water'])
                                            ];
                                            arsort($scores);
                                            
                                            // Get Top 2 Scores
                                            $top2 = array_slice($scores, 0, 2, true);
                                            $elem_colors = [
                                                'Dominance'         => '#2e7d32',
                                                'Influence'         => '#c62828',
                                                'Conscientiousness' => '#f9a825',
                                                'Steadiness'        => '#1565c0'
                                            ];
                                            
                                            $score_html = [];
                                            foreach($top2 as $k => $v) {
                                                $score_html[] = '<span style="color: '.$elem_colors[$k].'; font-weight: 700;">'.$k.': '.$v.'</span>';
                                            }
                                            $skor_display = implode(" / ", $score_html);

                                            // Dominant is the highest score
                                            $dominant = key($scores);

                                            // Determine badge color
                                            $badge_class = 'label-default';
                                            if ($dominant == 'Dominance') {
                                                $badge_class = 'label-success';
                                            } else if ($dominant == 'Influence') {
                                                $badge_class = 'label-danger';
                                            } else if ($dominant == 'Conscientiousness') {
                                                $badge_class = 'label-warning';
                                            } else if ($dominant == 'Steadiness') {
                                                $badge_class = 'label-primary';
                                            }
                                            ?>
                                            <tr>
                                                <td class="text-center"><?php echo $i; ?></td>
                                                <td><strong><?php echo htmlspecialchars($data['nama']); ?></strong></td>
                                                <td class="text-center"><?php echo date('d-m-Y', strtotime($data['tgl_lahir'])); ?></td>
                                                <td class="text-center"><?php echo date('d-m-Y', strtotime($data['tgl_test'])); ?></td>
                                                <td><?php echo htmlspecialchars($data['bagian']); ?></td>
                                                <td class="text-center" style="font-size: 13px;">
                                                    <?php echo $skor_display; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="label <?php echo $badge_class; ?>" style="font-size: 11px; padding: 4px 8px; display: inline-block;">
                                                        <?php echo $dominant . ' (' . $scores[$dominant] . ')'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-info btn-xs" onclick="showDetailModal(<?php echo htmlspecialchars(json_encode($data)); ?>)">
                                                        <i class="fa fa-eye"></i> Detail
                                                    </button>
                                                    <a href="test_calon_pdf.php?id=<?php echo $data['id']; ?>" target="_blank" class="btn btn-primary btn-xs">
                                                        <i class="fa fa-file-pdf-o"></i> PDF
                                                    </a>
                                                    <a href="test_calon_hapus.php?id=<?php echo $data['id']; ?>" class="btn btn-danger btn-xs" onclick="return confirm('Apakah Anda yakin ingin menghapus data test calon karyawan ini?')">
                                                        <i class="fa fa-trash"></i> Hapus
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php
                                            $i++;
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Test Calon -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document" style="max-width: 600px;">
        <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background-color: #f8f9fa; border-bottom: 1px solid #eee; padding: 18px 24px;">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true" style="font-size: 24px; font-weight: 300;">&times;</button>
                <h4 class="modal-title" id="detailModalLabel" style="font-weight: 700; color: #1c1c1e;"><i class="fa fa-bar-chart" style="color: #007aff;"></i> &nbsp;Detail Hasil DISC Personality</h4>
            </div>
            <div class="modal-body" style="padding: 24px;">
                
                <!-- Identitas -->
                <div style="background: #f5f5f7; border-radius: 12px; padding: 16px; margin-bottom: 20px; border: 1px solid #e3e3e8;">
                    <div class="row">
                        <div class="col-xs-6" style="margin-bottom: 8px;">
                            <small class="text-muted" style="display:block; font-size:10px; text-transform:uppercase; font-weight:700;">Nama Calon Karyawan</small>
                            <span id="modal-nama" style="font-weight:700; font-size:14px; color:#1c1c1e;">-</span>
                        </div>
                        <div class="col-xs-6" style="margin-bottom: 8px;">
                            <small class="text-muted" style="display:block; font-size:10px; text-transform:uppercase; font-weight:700;">Posisi Dilamar</small>
                            <span id="modal-bagian" style="font-weight:700; font-size:14px; color:#1c1c1e;">-</span>
                        </div>
                        <div class="col-xs-6">
                            <small class="text-muted" style="display:block; font-size:10px; text-transform:uppercase; font-weight:700;">Tanggal Lahir</small>
                            <span id="modal-tgl-lahir" style="font-weight:600; font-size:13px; color:#3a3a3c;">-</span>
                        </div>
                        <div class="col-xs-6">
                            <small class="text-muted" style="display:block; font-size:10px; text-transform:uppercase; font-weight:700;">Tanggal Test</small>
                            <span id="modal-tgl-test" style="font-weight:600; font-size:13px; color:#3a3a3c;">-</span>
                        </div>
                    </div>
                </div>

                <h5 style="font-weight: 700; margin-bottom: 15px; color: #1c1c1e;">Komposisi DISC Personality</h5>
                
                <!-- D - Dominance -->
                <div style="margin-bottom: 14px;">
                    <div style="display: flex; justify-content: space-between; font-weight: 600; font-size: 13px; margin-bottom: 4px;">
                        <span style="color:#2e7d32;"><strong>D</strong> &nbsp;Dominance — Tegas &amp; Berorientasi Hasil</span>
                        <span id="modal-earth-val">0</span>
                    </div>
                    <div class="progress" style="height: 12px; border-radius: 6px; background-color: #e3e3e8; margin-bottom: 0;">
                        <div class="progress-bar" id="modal-earth-bar" role="progressbar" style="width: 0%; background-color:#2e7d32; border-radius: 6px;"></div>
                    </div>
                </div>

                <!-- I - Influence -->
                <div style="margin-bottom: 14px;">
                    <div style="display: flex; justify-content: space-between; font-weight: 600; font-size: 13px; margin-bottom: 4px;">
                        <span style="color:#c62828;"><strong>I</strong> &nbsp;Influence — Antusias &amp; Persuasif</span>
                        <span id="modal-air-val">0</span>
                    </div>
                    <div class="progress" style="height: 12px; border-radius: 6px; background-color: #e3e3e8; margin-bottom: 0;">
                        <div class="progress-bar" id="modal-air-bar" role="progressbar" style="width: 0%; background-color:#c62828; border-radius: 6px;"></div>
                    </div>
                </div>

                <!-- C - Conscientiousness -->
                <div style="margin-bottom: 14px;">
                    <div style="display: flex; justify-content: space-between; font-weight: 600; font-size: 13px; margin-bottom: 4px;">
                        <span style="color:#f9a825;"><strong>C</strong> &nbsp;Conscientiousness — Teliti &amp; Analitis</span>
                        <span id="modal-water-val">0</span>
                    </div>
                    <div class="progress" style="height: 12px; border-radius: 6px; background-color: #e3e3e8; margin-bottom: 0;">
                        <div class="progress-bar" id="modal-water-bar" role="progressbar" style="width: 0%; background-color:#f9a825; border-radius: 6px;"></div>
                    </div>
                </div>

                <!-- S - Steadiness -->
                <div style="margin-bottom: 25px;">
                    <div style="display: flex; justify-content: space-between; font-weight: 600; font-size: 13px; margin-bottom: 4px;">
                        <span style="color:#1565c0;"><strong>S</strong> &nbsp;Steadiness — Sabar &amp; Mendukung</span>
                        <span id="modal-fire-val">0</span>
                    </div>
                    <div class="progress" style="height: 12px; border-radius: 6px; background-color: #e3e3e8; margin-bottom: 0;">
                        <div class="progress-bar" id="modal-fire-bar" role="progressbar" style="width: 0%; background-color:#1565c0; border-radius: 6px;"></div>
                    </div>
                </div>

                <!-- Dominant Interpretation -->
                <div id="modal-interpretation-box" class="alert" style="border-radius: 12px; margin-bottom: 0; padding: 15px; border: none;">
                    <h5 style="font-weight: 700; margin-top: 0;" id="modal-dominant-title">Elemen Dominan: -</h5>
                    <p style="margin: 0; font-size: 13px; line-height: 1.6;" id="modal-dominant-desc">-</p>
                </div>

            </div>
            <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #eee; padding: 15px 24px;">
                <a id="modal-pdf-btn" href="#" target="_blank" class="btn btn-primary" style="border-radius: 8px; font-weight: 600;">
                    <i class="fa fa-file-pdf-o"></i> Download PDF
                </a>
                <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 8px; font-weight: 600;">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        let day = '' + d.getDate();
        let month = '' + (d.getMonth() + 1);
        const year = d.getFullYear();

        if (day.length < 2) day = '0' + day;
        if (month.length < 2) month = '0' + month;

        return [day, month, year].join('-');
    }

    function showDetailModal(data) {
        // Set basic details
        document.getElementById('modal-nama').innerText = data.nama;
        document.getElementById('modal-bagian').innerText = data.bagian;
        document.getElementById('modal-tgl-lahir').innerText = formatDate(data.tgl_lahir);
        document.getElementById('modal-tgl-test').innerText = formatDate(data.tgl_test);

        // DISC mapping: earth=D, fire=I, air=C, water=S
        const earth = parseInt(data.skor_earth) || 0; // D
        const air   = parseInt(data.skor_fire)  || 0; // I
        const water = parseInt(data.skor_air)   || 0; // C
        const fire  = parseInt(data.skor_water) || 0; // S

        document.getElementById('modal-earth-val').innerText = earth;
        document.getElementById('modal-air-val').innerText   = air;
        document.getElementById('modal-water-val').innerText = water;
        document.getElementById('modal-fire-val').innerText  = fire;

        const maxScore = 36;
        document.getElementById('modal-earth-bar').style.width = ((earth / maxScore) * 100) + '%';
        document.getElementById('modal-air-bar').style.width   = ((air   / maxScore) * 100) + '%';
        document.getElementById('modal-water-bar').style.width = ((water / maxScore) * 100) + '%';
        document.getElementById('modal-fire-bar').style.width  = ((fire  / maxScore) * 100) + '%';

        const scores = {
            'Dominance': earth, 'Influence': air,
            'Conscientiousness': water, 'Steadiness': fire
        };
        let dominant = 'Dominance', maxVal = -1;
        for (const [key, val] of Object.entries(scores)) {
            if (val > maxVal) { maxVal = val; dominant = key; }
        }

        const titleElem = document.getElementById('modal-dominant-title');
        const descElem  = document.getElementById('modal-dominant-desc');
        const alertBox  = document.getElementById('modal-interpretation-box');
        alertBox.style = ''; titleElem.style = '';

        titleElem.innerText = `Tipe Dominan: ${dominant}`;

        if (dominant === 'Dominance') {
            alertBox.className = "alert alert-success";
            descElem.innerText = "Karakteristik: Berani, tegas, langsung, berorientasi hasil, kompetitif, dan menyukai tantangan. Fokus pada penyelesaian tugas secara cepat dan efisien.";
        } else if (dominant === 'Influence') {
            alertBox.className = "alert alert-danger";
            descElem.innerText = "Karakteristik: Antusias, optimis, persuasif, ekspresif, dan mudah bergaul. Sangat baik dalam memotivasi orang lain dan menciptakan suasana positif.";
        } else if (dominant === 'Conscientiousness') {
            alertBox.className = "alert alert-warning";
            descElem.innerText = "Karakteristik: Teliti, analitis, sistematis, akurat, dan perfeksionis. Sangat fokus pada kualitas, data, dan kejelasan prosedur.";
        } else if (dominant === 'Steadiness') {
            alertBox.className = "alert alert-info";
            descElem.innerText = "Karakteristik: Sabar, setia, kooperatif, pendengar yang baik, dan mendukung orang lain. Sangat baik dalam kolaborasi tim dan menjaga keharmonisan.";
        }


        // Update PDF button link
        document.getElementById('modal-pdf-btn').href = 'test_calon_pdf.php?id=' + data.id;

        // Show Modal
        $('#detailModal').modal('show');
    }
</script>
