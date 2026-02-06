<!-- Printing -->
<link rel="stylesheet" href="css/printing.css">
		
        <?php
        include("sess_check.php");
        include("dist/function/format_tanggal.php");
        if($_GET) {
            $kode = $_GET['code'];
            $sql = "SELECT lembur.*, employee.* FROM lembur, employee WHERE lembur.npp=employee.npp AND lembur.id_lmbr='". $_GET['code'] ."'";
            $query = mysqli_query($conn,$sql);
            $result = mysqli_fetch_array($query);
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
            <h4 class="modal-title" id="myModalLabel">Detail Data Lembur</h4>
        </div>
        <div><br/>
        <table width="100%">
            <tr>
                <td width="20%"><b>No. Lembur</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo $result['id_lmbr'];?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>NPP</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo $result['npp'];?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Nama Karyawan</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo $result['nama_karyawan'];?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Tanggal Lembur</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo IndonesiaTgl($result['tgl_lembur']);?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Jam Mulai Lembur</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo $result['jam_mulai_lembur'];?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Jam Berakhir Lembur</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo $result['jam_berakhir_lembur'];?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Cabang</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo $result['cabang'];?></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td width="20%"><b>Nama Koordinator</b></td>
                <td width="2%"><b>:</b></td>
                <td width="78%"><?php echo $result['nama_koordinator'];?></td>
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