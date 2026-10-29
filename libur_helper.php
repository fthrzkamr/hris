<?php
// libur_helper.php
// File terpusat untuk daftar Hari Libur Nasional dan kalkulasi durasi cuti

if (!function_exists('get_libur_nasional')) {
    function get_libur_nasional() {
        return [
            '2026-01-01', // Tahun Baru
            '2026-01-16', // Isra Miraj
            '2026-02-17', // Tahun Baru Imlek
            '2026-03-19', // Hari Raya Nyepi
            '2026-03-20', // Idul Fitri
            '2026-03-21', // Idul Fitri
            '2026-04-03', // Wafat Isa Almasih
            '2026-05-01', // Hari Buruh
            '2026-05-14', // Kenaikan Isa Almasih
            '2026-05-27', // Idul Adha
            '2026-06-01', // Hari Lahir Pancasila
            '2026-06-16', // Tahun Baru Islam
            '2026-08-17', // Hari Kemerdekaan RI
            '2026-08-25', // Maulid Nabi Muhammad SAW
            '2026-12-25'  // Hari Natal
        ];
    }
}

if (!function_exists('hitung_durasi_cuti')) {
    function hitung_durasi_cuti($mulai, $akhir) {
        $libur_nasional = get_libur_nasional();
        $start  = new DateTime($mulai);
        $finish = new DateTime($akhir);
        $durasi = 0;
        $current = clone $start;
        while ($current <= $finish) {
            $tglStr = $current->format('Y-m-d');
            // Lewati hari Minggu (0 = Minggu) dan Libur Nasional
            if ($current->format('w') != 0 && !in_array($tglStr, $libur_nasional)) {
                $durasi++;
            }
            $current->modify('+1 day');
        }
        return $durasi;
    }
}
?>
