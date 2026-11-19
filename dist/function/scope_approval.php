<?php
// Skop data persetujuan (cuti & lembur) berdasarkan akun yang login.
// Akun hanya melihat pengajuan karyawan di bawah koordinator / manager miliknya
// (dicocokkan lewat NPP, atau lewat nama jika kolom npp di tabel koordinator/manager masih kosong).
// Jika akun belum terdaftar sebagai koordinator/manager -> tidak ada data (AND 1=0).
// Hasil: $scope_filter (potongan SQL, memakai alias tabel `employee`), $scope_id_koor, $scope_id_man.
$scope_filter  = " AND 1=0 ";
$scope_id_koor = '-1';
$scope_id_man  = '-1';
if (isset($conn) && isset($sess_mngid)) {
    $__npp  = mysqli_real_escape_string($conn, $sess_mngid);
    $__nama = mysqli_real_escape_string($conn, trim($sess_mngname ?? ''));

    $__q = mysqli_query($conn, "SELECT id_koordinator FROM koordinator WHERE npp='$__npp' OR ((npp='' OR npp IS NULL) AND TRIM(LOWER(nama_koordinator))=LOWER('$__nama')) LIMIT 1");
    if ($__q && ($__r = mysqli_fetch_assoc($__q))) $scope_id_koor = $__r['id_koordinator'];

    $__q = mysqli_query($conn, "SELECT id_manager FROM manager WHERE npp='$__npp' OR ((npp='' OR npp IS NULL) AND TRIM(LOWER(nama_manager))=LOWER('$__nama')) LIMIT 1");
    if ($__q && ($__r = mysqli_fetch_assoc($__q))) $scope_id_man = $__r['id_manager'];

    $__cond = [];
    if ($scope_id_koor != '-1') $__cond[] = "employee.nama_koordinator='" . mysqli_real_escape_string($conn, $scope_id_koor) . "'";
    if ($scope_id_man  != '-1') $__cond[] = "employee.nama_manager='" . mysqli_real_escape_string($conn, $scope_id_man) . "'";
    if (!empty($__cond)) $scope_filter = " AND (" . implode(' OR ', $__cond) . ") ";
}
?>