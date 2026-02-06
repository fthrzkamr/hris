<!-- Printing -->
<link rel="stylesheet" href="css/printing.css">
		
        <?php
        include("sess_check.php");
        include("dist/function/format_tanggal.php");
        if($_GET) {
            $kode = $_GET['code'];
            $sql = "SELECT kacamata.*, employee.* FROM kacamata, employee WHERE kacamata.npp=employee.npp AND kacamata.id_kacamata='". $_GET['code'] ."'";
            $query = mysqli_query($conn,$sql);
            $result = mysqli_fetch_array($query);
            $kacamata = $result['total_kwintansi'];
            $a		=(80 / 100);
            $p		= $kacamata * $a ;
        }
        else {
            echo "Nomor Transaksi Tidak Terbaca";
            exit;
        }
        ?>
        <html>
        <head>
        </head>
        <body>
        <div id="section-to-print">
        <div id="only-on-print">
        </div>
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
            <h4 class="modal-title" id="myModalLabel">Detail Data Kacamata</h4>
        </div>
        <div><br/>
        <table width="100%">
            <tr>
                <td width="20%"><b>Nama Karyawan</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo $result['nama_karyawan'];?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Jenis Kacamata</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo $result['jenis_kacamata'];?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Nama Tempat Fasilitas</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo $result['nama_fasilitas'];?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Alamat Tempat Fasilitas</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo $result['alamat_fasilitas'];?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Tanggal Pemeriksaan</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo IndonesiaTgl($result['tanggal_pengajuan']);?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Total Kwitansi</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%">Rp <?php echo number_format($result['total_kwintansi'], 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Total Reimburse</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%">Rp <?php echo number_format($p, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Alasan Reject</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo $result['reject'];?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
          
        </table>
        </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
        
        </body>
        </html>