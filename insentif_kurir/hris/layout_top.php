<!-- Insentif menu Tambahin di layout_top.php paling bawah -->
<?php
if (isset($menuparent) && $menuparent == "insentif") {
    echo '<li class="active">';
} else {
    echo '<li>';
}
?>
<a href="#"><i class="fa fa-download fa-fw"></i>&nbsp;Insentif<span class="fa arrow"></span></a>
<ul class="nav nav-second-level">
    <?php
    if ($pagedesc == "Upload Insentif Kurir") {
        echo '<li><a href="insentif_kurir_upload.php" class="active">Upload Insentif Kurir</a></li>';
    } else {
        echo '<li><a href="insentif_kurir_upload.php">Upload Insentif Kurir</a></li>';
    }
    if ($pagedesc == "Daftar Insentif Kurir") {
        echo '<li><a href="insentif_kurir_list.php" class="active">Daftar Insentif Kurir</a></li>';
    } else {
        echo '<li><a href="insentif_kurir_list.php">Daftar Insentif Kurir</a></li>';
    }
    // if ($pagedesc == "Pengaturan Insentif Kurir") {
    //     echo '<li><a href="pengaturan_insentif_kurir.php" class="active">Pengaturan Insentif Kurir</a></li>';
    // } else {
    //     echo '<li><a href="pengaturan_insentif_kurir.php">Pengaturan Insentif Kurir</a></li>';
    // }
    ?>
</ul><!-- /.nav-second-level -->
</li>