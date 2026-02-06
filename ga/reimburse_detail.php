<!-- Printing -->
<link rel="stylesheet" href="css/printing.css">
		
        <?php
        include("sess_check.php");
        include("dist/function/format_tanggal.php");
        if($_GET) {
            $kode = $_GET['code'];
            $sql = "SELECT rembes.*, employee.* FROM rembes, employee WHERE rembes.npp=employee.npp AND rembes.id_rmbs='". $_GET['code'] ."'";
            $query = mysqli_query($conn,$sql);
            $result = mysqli_fetch_array($query);
            $rembes = $result['total_kwitansi'];
            $a		=(80 / 100);
            $p		= $rembes * $a ;
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
            <h4 class="modal-title" id="myModalLabel">Detail Data Reimburse</h4>
        </div>
        <div><br/>
        <table width="100%">
            <tr>
                <td width="20%"><b>Fasilitas Kesehatan</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo $result['fasilitas_kesehatan'];?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
        
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Nama Dokter</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo $result['nama_dokter'];?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Tanggal Pemeriksaan</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo IndonesiaTgl($result['tanggal_pemeriksaan']);?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Total Kwitansi</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo $result['total_kwitansi'];?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Total Reimburse</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo $p?></td>
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