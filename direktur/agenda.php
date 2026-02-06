<?php
	include("sess_check.php");
    $json = array();
    $Sql = "SELECT cuti.*, employee.* FROM cuti, employee WHERE cuti.npp=employee.npp ORDER BY employee.nama_emp DESC ";
    $Qry = mysqli_query($conn, $Sql);
    
    while ($row = mysqli_fetch_assoc($Qry)) 
    {
       if ($row['stt_cuti']== 'Approved') 
       {
            $json[] = array(
                'backgroundColor' => 'rgb(176,235,180)',
                'textColor'       => 'rgb(0,0,0)',
                'borderColor'     => 'rgb(255,255,255)',
                'title'           => $row['cabang']." ".$row['nama_emp'] ,
                'start'           => $row['tgl_awal'],
                'end'             => $row['tgl_akhir'],
            );
       }else if ($row['stt_cuti']== 'Menunggu Approval HRD') {

            $json[] = array(
                'backgroundColor' => 'rgb(251,176,64)',
                'borderColor'     => 'rgb(251,176,64)',
                'textColor'       => 'rgb(0,0,0)',
                'title'           => $row['cabang']." ".$row['nama_emp'] ,
                'start'           => $row['tgl_awal'],
                'end'             => $row['tgl_akhir'],
            );
       }
    }
echo json_encode($json);


?>