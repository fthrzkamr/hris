<?php
include("sess_check.php");
require __DIR__ . '/../vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($conn)) {
    die("Koneksi database tidak ditemukan!");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_excel'])) {
    $file = $_FILES['file_excel']['tmp_name'];

    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    for ($i = 1; $i < count($rows); $i++) {
        $npp = mysqli_real_escape_string($conn, $rows[$i][0]);

        $sql = "SELECT total_gaji FROM employee WHERE npp='$npp'";
        $result = mysqli_query($conn, $sql);
        $emp = mysqli_fetch_assoc($result);
        if (!$emp) continue;

        $total_gaji = (int)$emp['total_gaji'];

        $p_keterlambatan = (int)$rows[$i][1] * 5000;
        $p_lain = (int)$rows[$i][2];
        $desc_lain = mysqli_real_escape_string($conn, $rows[$i][3]);
        $tanggal = date('Y-m-d', strtotime($rows[$i][4]));

        // Default pinjaman nol, nanti dicek jika aktif
        $p_pinjaman = 0;

        // Cek pinjaman aktif
        $sql_pinjaman = "SELECT * FROM pinjaman WHERE npp='$npp' AND status='aktif'";
        $result_pinjaman = mysqli_query($conn, $sql_pinjaman);

        if (mysqli_num_rows($result_pinjaman) > 0) {
            $data_pinjaman = mysqli_fetch_assoc($result_pinjaman);
            $id_pinjaman = $data_pinjaman['id_pinjaman'];
            $tenor = (int)$data_pinjaman['tenor'];
            $cicilan_per_bulan = (int)$data_pinjaman['cicilan_per_bulan'];
            $p_pinjaman = $cicilan_per_bulan;

            $bulan = date('m', strtotime($tanggal));
            $tahun = date('Y', strtotime($tanggal));
            $sql_cek_angsuran = "SELECT * FROM angsuran_pinjaman 
                                 WHERE id_pinjaman='$id_pinjaman' AND bulan='$bulan' AND tahun='$tahun'";
            $result_cek = mysqli_query($conn, $sql_cek_angsuran);

            if (mysqli_num_rows($result_cek) == 0) {
                $npp_last4 = substr($npp, -4);
                $count_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM angsuran_pinjaman");
                $num = mysqli_fetch_assoc($count_q)['total'] + 1;
                $id_angsuran = "ANG/" . str_pad($num, 4, "0", STR_PAD_LEFT) . "/" . $npp_last4;

                $sql_insert_angsuran = "INSERT INTO angsuran_pinjaman (id_angsuran, id_pinjaman, bulan, tahun, jumlah_angsuran, status, tanggal_potong)
                VALUES ('$id_angsuran', '$id_pinjaman', '$bulan', '$tahun', '$cicilan_per_bulan', 'dibayar', '$tanggal')";

                mysqli_query($conn, $sql_insert_angsuran);

                $sql_count_angsuran = "SELECT COUNT(*) as total FROM angsuran_pinjaman WHERE id_pinjaman='$id_pinjaman'";
                $result_count_angsuran = mysqli_query($conn, $sql_count_angsuran);
                $total_angsuran = mysqli_fetch_assoc($result_count_angsuran)['total'];

                if ($total_angsuran >= $tenor) {
                    $sql_lunas = "UPDATE pinjaman SET status='lunas', tanggal_lunas='$tanggal' WHERE id_pinjaman='$id_pinjaman'";
                    mysqli_query($conn, $sql_lunas);
                }
            }
        }

        $total_potongan = $p_keterlambatan + $p_pinjaman + $p_lain;
        $gaji_setelah_potongan = $total_gaji - $total_potongan;

        $sql_check = "SELECT * FROM potongan WHERE npp='$npp'";
        $result_check = mysqli_query($conn, $sql_check);
        $existing_data = mysqli_fetch_assoc($result_check);

        if ($existing_data) {
            $sql_update = "UPDATE potongan SET 
                           p_keterlambatan = '$p_keterlambatan',
                           p_pinjaman = '$p_pinjaman',
                           p_lain = '$p_lain',
                           desc_lain = '$desc_lain',
                           p_hasil = '$gaji_setelah_potongan',
                           tanggal = '$tanggal'
                           WHERE npp = '$npp'";
            mysqli_query($conn, $sql_update);
        } else {
            $npp_last3 = substr($npp, -3);
            $sql_count = "SELECT COUNT(*) as jumlah FROM potongan";
            $result_count = mysqli_query($conn, $sql_count);
            $row_count = mysqli_fetch_assoc($result_count);
            $nomor_urut = $row_count['jumlah'] + 1;
            $nomor_urut_str = str_pad($nomor_urut, 4, "0", STR_PAD_LEFT);
            $id_potongan = "PTN/" . $nomor_urut_str . "/" . $npp_last3;

            $sql_insert = "INSERT INTO potongan (id_potongan, npp, p_keterlambatan, p_pinjaman, p_lain, desc_lain, p_hasil, tanggal) 
                           VALUES ('$id_potongan', '$npp', '$p_keterlambatan', '$p_pinjaman', '$p_lain', '$desc_lain', '$gaji_setelah_potongan', '$tanggal')";
            mysqli_query($conn, $sql_insert);
        }

        $sql_laporan = "INSERT INTO laporan_potongan (npp, tanggal, p_keterlambatan, p_pinjaman, p_lain, desc_lain, p_hasil)
                        VALUES ('$npp', '$tanggal', '$p_keterlambatan', '$p_pinjaman', '$p_lain', '$desc_lain', '$gaji_setelah_potongan')";
        mysqli_query($conn, $sql_laporan);
    }

    header("Location: tunjangan.php?success=1");
    exit();
}
