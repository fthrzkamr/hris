-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 03, 2024 at 03:25 AM
-- Server version: 10.4.21-MariaDB
-- PHP Version: 7.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hris`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_adm` int(11) NOT NULL,
  `nama_adm` varchar(50) NOT NULL,
  `telp_adm` varchar(15) NOT NULL,
  `user_adm` varchar(50) NOT NULL,
  `pass_adm` varchar(100) NOT NULL,
  `foto_adm` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_adm`, `nama_adm`, `telp_adm`, `user_adm`, `pass_adm`, `foto_adm`) VALUES
(1, 'Administrator', '08962878534', '19840001', '19840001', '');

-- --------------------------------------------------------

--
-- Table structure for table `cuti`
--

CREATE TABLE `cuti` (
  `no_cuti` varchar(30) NOT NULL,
  `npp` varchar(20) NOT NULL,
  `tgl_pengajuan` date NOT NULL,
  `tgl_awal` date NOT NULL,
  `tgl_akhir` date NOT NULL,
  `durasi` int(11) NOT NULL,
  `keterangan` text NOT NULL,
  `leader` varchar(20) NOT NULL,
  `manager` varchar(30) NOT NULL,
  `spv` varchar(20) NOT NULL,
  `stt_cuti` varchar(50) NOT NULL,
  `tipe_cuti` varchar(100) NOT NULL,
  `ket_reject` text NOT NULL,
  `hrd_app` int(2) NOT NULL,
  `lead_app` int(2) NOT NULL,
  `spv_app` int(2) NOT NULL,
  `mng_app` int(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `cuti`
--

INSERT INTO `cuti` (`no_cuti`, `npp`, `tgl_pengajuan`, `tgl_awal`, `tgl_akhir`, `durasi`, `keterangan`, `leader`, `manager`, `spv`, `stt_cuti`, `tipe_cuti`, `ket_reject`, `hrd_app`, `lead_app`, `spv_app`, `mng_app`) VALUES
('01112023101113', '21900024', '2023-11-01', '2023-11-01', '2023-11-01', 1, 'masih kurang sehat', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('01112023141717', '21930023', '2023-11-01', '2023-11-02', '2023-11-03', 2, 'kontrol gigi', '', '', '', 'Rejected', 'cuti tahunan', '', 0, 0, 0, 0),
('01112023154715', '21930023', '2023-11-01', '2023-11-02', '2023-11-03', 2, 'kontrol gigi', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('01122023143713', '22910033', '2023-12-01', '2023-12-06', '2023-12-06', 1, 'Antar adik wisuda', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('02012024081502', '21780020', '2024-01-02', '2024-01-02', '2024-01-02', 1, 'Anak sakit ', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('02012024094856', '20910012', '2024-01-02', '2024-01-04', '2024-01-05', 2, 'acara keluarga', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('02042023193631', '21850015', '2023-04-02', '2023-04-03', '2023-04-04', 2, 'bapa masuk rumah sakit', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('02062023153702', '21780020', '2023-06-02', '2023-06-08', '2023-06-08', 1, 'Wisuda Anak', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('02102023065056', '21020016', '2023-10-02', '2023-10-02', '2023-10-02', 1, 'Orang tua sakit', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('03012023122124', '21020016', '2023-01-03', '2023-01-04', '2023-01-04', 1, 'Acara keluarga', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('03022023222328', '20910009', '2023-02-03', '2023-02-06', '2023-02-06', 1, 'Izin ada urusan keluarga ', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('03052023155804', '20920008', '2023-05-03', '2023-05-19', '2023-05-22', 4, 'Mau istirahat', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('03052023161803', '21000025', '2023-05-03', '2023-05-08', '2023-05-08', 1, 'Menghadiri pernikahan sodara.', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('03092023115005', '22010028', '2023-09-03', '2023-09-04', '2023-09-05', 2, 'Mengurus suami di rumah sakit', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('03102023095536', '22910038', '2023-10-03', '2023-10-04', '2023-10-04', 1, 'Kontrol Dokter Pasca Rawat Inap', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('03102023112619', '20990013', '2023-10-03', '2023-10-05', '2023-10-07', 3, 'nengok orang tua habis tindakan dan mengantar kontrol ', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('03112023102051', '21780020', '2023-11-03', '2023-11-03', '2023-11-03', 1, 'Merawat anak sakit', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('04012024104718', '20990013', '2024-01-04', '2024-01-05', '2024-01-06', 2, 'd', '', '', '', 'Rejected', 'cuti menikah', 'sss', 0, 0, 0, 0),
('04032023130454', '22010028', '2023-03-04', '2023-03-06', '2023-03-06', 1, 'Ingin mengurus surat pindah alamat tempat tinggal', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('04102023175814', '21000025', '2023-10-04', '2023-10-09', '2023-10-10', 2, 'Jenguk orang tua', '', '', '', 'Rejected', 'cuti tahunan', '', 0, 0, 0, 0),
('04122023075458', '22900030', '2023-12-04', '2023-12-04', '2023-12-04', 1, 'Urusan keluarga', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('05052023095134', '21900024', '2023-05-05', '2023-05-08', '2023-05-09', 2, 'adik menikah ', '', '', '', 'Rejected', 'cuti tahunan', '', 0, 0, 0, 0),
('05052023101727', '21900024', '2023-05-05', '2023-05-05', '2023-05-06', 2, 'mudik lebaran ', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('05052023101805', '21900024', '2023-05-05', '2023-05-08', '2023-05-08', 1, 'adik nikahan ', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('05052023112836', '21020016', '2023-05-05', '2023-05-08', '2023-05-08', 1, 'Acara keluarga', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('05112023192629', '22930032', '2023-11-05', '2023-11-07', '2023-11-07', 1, 'Periksa istri', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('05122023152751', '22910038', '2023-12-05', '2023-12-07', '2023-12-07', 1, 'Kontrol kandungan (antri bpjs)', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('06042023152731', '21900024', '2023-04-06', '2023-04-08', '2023-04-09', 2, 'Ada keperluan keluarga ', '', '', '', 'Rejected', 'cuti tahunan', '', 0, 0, 0, 0),
('06042023152740', '21900024', '2023-04-06', '2023-04-08', '2023-04-09', 2, 'Ada keperluan keluarga ', '', '', '', 'Rejected', 'cuti tahunan', '', 0, 0, 0, 0),
('06042023153321', '21900024', '2023-04-06', '2023-04-08', '2023-04-08', 1, 'Keperluan keluarga ', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('06122022060615', '20910012', '2022-12-06', '2022-12-06', '2022-12-06', 1, 'Mengantar orang tua ke rumah sakit', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('07062023125955', '19930005', '2023-06-07', '2023-06-20', '2023-09-17', 90, 'Cuti lahiran', '', '', '', 'Approved', 'cuti hamil', '', 1, 0, 0, 0),
('08092023151955', '21970019', '2023-09-08', '2023-09-09', '2023-09-09', 1, 'cuti kuliah', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('08092023152015', '21970019', '2023-09-08', '2023-09-11', '2023-09-11', 1, 'cuti kuliah', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('09022023181711', '21900024', '2023-02-09', '2023-02-11', '2023-02-11', 1, 'Keperluan keluarga ', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('10062023110607', '20990013', '2023-06-10', '2023-06-16', '2023-06-17', 2, 'Ada Keperluan Keluarga', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('10062023201201', '21930023', '2023-06-10', '2023-06-24', '2023-06-26', 3, 'acara keluarga', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('10102023081445', '21000025', '2023-10-10', '2023-10-16', '2023-10-17', 2, 'Jenguk orang tua', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('10122023173303', '22930032', '2023-12-10', '2023-12-11', '2023-12-11', 1, 'Cuti ada keperluan keluarga ', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('11092023172758', '20920008', '2023-09-11', '2023-09-13', '2023-09-13', 1, 'ada keperluan', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('11112022142309', 'tegar', '2022-11-11', '2022-11-12', '2022-11-17', 6, 'ppp', '', '', '', 'Rejected', 'cuti tahunan', 'uji coba', 0, 0, 0, 0),
('12042023103827', '20990013', '2023-04-12', '2023-05-11', '2023-05-13', 3, 'ADA ACARA KELUARGA', '', '', '', 'Rejected', 'cuti tahunan', 'ga jadi cuti', 0, 0, 0, 0),
('12042023200347', '21900024', '2023-04-12', '2023-04-26', '2023-04-29', 4, 'Mudik lebaran ', '', '', '', 'Rejected', 'cuti tahunan', 'gagal', 0, 0, 0, 0),
('12122023192608', '20920008', '2023-12-12', '2023-12-13', '2023-12-13', 1, 'Istirahat', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('14112023070809', '20920008', '2023-11-14', '2023-11-17', '2023-11-17', 1, 'Ujian SKD', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('15012024141347', '20990013', '2024-01-15', '2024-01-15', '2024-01-16', 2, 'x', '', '', '', 'Menunggu Approval HRD', 'cuti tahunan', '', 0, 0, 0, 0),
('15032023094820', '20910012', '2023-03-15', '2023-03-17', '2023-03-17', 1, 'Keperluan keluarga', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('15092023134851', '21930023', '2023-09-15', '2023-09-16', '2023-09-16', 1, 'kontrol berobat', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('15112023134755', '22910038', '2023-11-15', '2023-11-16', '2023-11-17', 2, 'acara sekolah anak (parenting)', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('16122022085728', 'tegar', '2022-12-16', '2023-01-02', '2023-01-03', 2, 'cuti tahunan ', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('17112023134827', '22910033', '2023-11-17', '2023-11-18', '2023-11-18', 1, '1 hari', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('18042023115003', '21900024', '2023-04-18', '2023-04-26', '2023-04-28', 3, 'cuti keluarga', '', '', '', 'Rejected', 'cuti tahunan', '', 0, 0, 0, 0),
('18042023115301', '21900024', '2023-04-18', '2023-04-26', '2023-04-27', 2, 'CUTI TAHUNAN', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('18122023154909', '21020016', '2023-12-18', '2023-12-19', '2023-12-19', 1, 'Medical check up orang tua', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('19032023120023', '21850015', '2023-03-19', '2023-03-21', '2023-03-22', 2, 'izin ke bandung., ziarah ke makam orang tua', '', '', '', 'Rejected', 'cuti tahunan', '', 0, 0, 0, 0),
('19032023120130', '21850015', '2023-03-19', '2023-03-23', '2023-03-24', 2, 'izin pulang ke bandung', '', '', '', 'Rejected', 'cuti tahunan', '', 0, 0, 0, 0),
('19102023091233', '20920008', '2023-10-19', '2023-10-23', '2023-10-23', 1, '-', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('19112022112916', '20910012', '2022-11-19', '2022-11-23', '2022-11-23', 1, 'Mengantar orang tua kontrol jantung di RS Harapan Kita', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('19122022140906', '20910012', '2022-12-19', '2022-12-20', '2022-12-20', 1, 'Mengantar orang tua kontrol', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('20012023145424', '20910012', '2023-01-20', '2023-02-22', '2023-02-24', 3, 'Keperluan keluarga', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('20072023085856', '21970019', '2023-07-20', '2023-07-21', '2023-07-22', 2, 'Ada Urusan Keluarga', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('21062023085602', '21900024', '2023-06-21', '2023-06-22', '2023-06-24', 3, 'orang tua sakit', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('21062023151831', '20910009', '2023-06-21', '2023-07-06', '2023-07-10', 5, 'Mudik bersama kekuarga besar', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('21082023142033', '21930023', '2023-08-21', '2023-08-26', '2023-08-28', 3, 'keperluan keluarga', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('21112022090542', '21850015', '2022-11-21', '2022-11-28', '2022-12-01', 4, '28-29 november prepare, akad 30 november, 1 november beres-beres..', '', '', '', 'Approved', 'cuti menikah', '', 1, 0, 0, 0),
('22112023164110', '21930023', '2023-11-22', '2023-11-28', '2023-11-28', 1, 'acara keluarga', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('22112023164138', '21930023', '2023-11-22', '2023-12-05', '2023-12-05', 1, 'cabut gigi', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('23112023074620', '21780020', '2023-11-23', '2023-11-23', '2023-11-23', 1, 'Istirahat ', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('24092023182657', '22010031', '2023-09-24', '2023-09-30', '2023-09-30', 1, 'Keluarga Inti Nikah ', '', '', '', 'Rejected', 'cuti tahunan', '', 0, 0, 0, 0),
('26112022090313', '22010028', '2022-11-26', '2022-11-30', '2022-12-02', 3, 'Cuti menikah', '', '', '', 'Approved', 'cuti menikah', '', 1, 0, 0, 0),
('28022023114349', '21970019', '2023-02-28', '2023-03-04', '2023-03-07', 3, 'Cuti menikah', '', '', '', 'Approved', 'cuti menikah', '', 1, 0, 0, 0),
('28022023114433', '21970019', '2023-02-28', '2023-03-08', '2023-03-11', 4, 'Cuti Menikah Tambahan', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('28032023141149', '19930005', '2023-03-28', '2023-04-26', '2023-04-28', 3, 'cuti mudik', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('29082023195228', '22010028', '2023-08-29', '2023-08-30', '2023-08-31', 2, 'Suami sakit', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('30102023174642', '22010031', '2023-10-30', '2023-11-08', '2023-11-09', 2, 'Nikahan Keluarga', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('31082023210633', '22010028', '2023-08-31', '2023-09-01', '2023-09-02', 2, 'Suami masuk rumah sakit', '', '', '', 'Approved', 'cuti tahunan', '', 1, 0, 0, 0),
('31102023104931', '21930023', '2023-10-31', '2023-11-01', '2023-11-02', 2, 'kontrol gigi ', '', '', '', 'Rejected', 'cuti tahunan', '', 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `npp` varchar(20) NOT NULL,
  `nama_emp` varchar(100) NOT NULL,
  `jk_emp` varchar(20) NOT NULL,
  `telp_emp` varchar(20) NOT NULL,
  `divisi` varchar(50) NOT NULL,
  `jabatan` varchar(50) NOT NULL,
  `alamat` text NOT NULL,
  `hak_akses` varchar(20) NOT NULL,
  `jml_cuti` int(11) NOT NULL,
  `password` varchar(100) NOT NULL,
  `foto_emp` text NOT NULL,
  `nomor_ktp` varchar(50) NOT NULL,
  `kota_lahir` varchar(50) NOT NULL,
  `alamat_tinggal_sekarang` varchar(50) NOT NULL,
  `tanggal_lahir` date NOT NULL DEFAULT ,
  `pendidikan_terakhir` varchar(25) NOT NULL,
  `nama_institusi` varchar(25) NOT NULL,
  `tanggal_masuk_karyawan` date NOT NULL DEFAULT ,
  `status_bpjs` varchar(20) NOT NULL,
  `asuransi_lain` varchar(50) NOT NULL,
  `norek_mandiri` int(50) NOT NULL,
  `active` varchar(20) NOT NULL,
  `id_adm` int(11) NOT NULL,
  `cabang` varchar(50) NOT NULL,
  `kesehatan` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`npp`, `nama_emp`, `jk_emp`, `telp_emp`, `divisi`, `jabatan`, `alamat`, `hak_akses`, `jml_cuti`, `password`, `foto_emp`, `nomor_ktp`, `kota_lahir`, `alamat_tinggal_sekarang`, `tanggal_lahir`, `pendidikan_terakhir`, `nama_institusi`, `tanggal_masuk_karyawan`, `status_bpjs`, `asuransi_lain`, `norek_mandiri`, `active`, `id_adm`, `cabang`, `kesehatan`) VALUES
('19880003', 'M. Dimas Tri S', 'Laki-Laki', '82180747831', 'UMUM', 'UMUM', 'Jl. Kautamaan Istri No. 10, Kelurahan Balonggede, Kecamatan Regol, Kota Bandung\r\n', 'pegawai', 12, '19880003', 'fototegar.webp', '2147483647', 'Bandung', 'Jl. H. Brit II, Blok 64 A No. 4,  Kel. Meruya Utar', '1988-12-06', 'S1', 'Univ. Widyatama Bandung', '2019-06-24', 'AKTIF', 'AKTIF', 2147483647, 'Aktif', 0, '', 0),
('19930005', 'Oktaviani', 'Perempuan', '89628008659', 'Oprasional', 'Oprasional', 'Meruya Selatan Rt.003 Rw.007, Kelurahan Meruya Selatan, Kecamatan Kembangan, Jakarta Barat\r\n', 'Leader', 9, '19930005', 'foto19930005m.png', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('20840010', 'Heri Susanto', 'Perempuan', '8819917150', 'SALES', 'SALES', 'kp. Pompa, Kel. Karangsatria, Kec. Tambun Utara, Kota Bekasi\r\n', 'Manager', 12, '20840010', 'foto20840010m.png', '2147483647', 'PURWOREJO', 'Prm. Panjibuono, kel. Kedung pengawas, kec. Babela', '1984-12-23', 'S1', 'Univ. Mercubuana Yogyakar', '2020-06-17', '---', 'AKTIF', 2147483647, 'Aktif', 0, '', 0),
('20910009', 'Wahyu Pandu Kuncoro', 'Laki-Laki', '89667982735', 'SALES', 'SALES', 'Alam Indah Blok L1 No. 13 RT 001 RW 008, Kel. Poris Pelawad Indah, Kec. Cipondoh, Kota Tangerang\r\n', 'Manager', 6, '20910009', 'foto20910009m.png', '2147483647', 'TANGGERANG', 'Alam Indah Blok L1 No. 13 RT 001 RW 008, Kel. Pori', '1991-05-21', 'S1', 'Univ. Islam Syekh-Yusuf T', '2020-06-10', '---', 'AKTIF', 2147483647, 'Aktif', 0, '', 0),
('20910012', 'Yudha Amandangi Syahputera', 'Laki-Laki', '81315593764', 'APOTEKER', 'APOTEKER', 'Jl. Cucur Timur VI Blok A9 No. 23, Kel. Pondok Karya, Kec Pondok Aren, Tangerang Selatan\r\n', 'Manager', 7, '20910012', 'foto20910012m.png', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('20920008', 'Saputra Pratama', 'Laki-Laki', '82112383800', 'FINANCE', 'FINANCE', 'Jl Jati Padang III RT 007 RW 005 No 114, Kelurahan Jati Padang, Kecamatan Pasar Minggu, Jakarta Selatan\r\n', 'pegawai', 5, '20920008', 'foto20920008m.png', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('20990013', 'Tegar Satya Negara', 'Laki-Laki', '085775122588', 'IT PROGRAMMER', 'IT PROGRAMMER', 'Desa Jagung RT 03 RW 03 KECAMATAN KESESI ', 'pegawai', 7, 'tegar', 'fototegar.webp', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('21000025', 'Aji Bakhri Syaeful', 'Laki-Laki', '083816091228', 'Operational', 'Kebersihan & keamanan', 'KP. Langkap RT/RW 010/005 Kel/Des Cadassari Kec. Tegal Waru', 'Manager', 9, '21000025', 'fototegar.webp', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('21020016', 'Diyah Sekar Melati', 'Laki-Laki', '88212596181', 'FINANCE', 'FINANCE', 'Kebagusan besar rt 001/05, Pasar Minggu\r\n', 'Manager', 4, '21020016', 'foto21020016m.png', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('21780020', 'YULI YUNISTA', 'Perempuan', '08979762886', 'SALES', 'SALES', 'Jl. Kayu Manis No. 8 RT/RW 009/005 Kel. Balekambang. Kec. Kramatjati Jakarta Timur\r\n', 'Manager', 9, '21780020', 'foto217800202.jpg', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('21850015', 'Ari Ardiansyah', 'Laki-Laki', '87777716538', 'Oprasional', 'Oprasional', '\"Tubagus Ismail dalam RT. 002 RW. 001 Kel. Lebak Gede Kec. Coblong\r\nKota Bandung, Prov. Jawa Barat\"\r\n', 'Manager', 0, '21850015', 'foto21850015m.png', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('21900024', 'Dila Restu Pamungkas', 'Laki-Laki', '089671819657', 'Kurir', 'Kurir', 'Jl. Pala Manis RT/RW 003/006 Kel/Desa Cirimekr Kec. Cibinong\r\n', 'Manager', 3, '21900024', 'foto21900024.jpeg', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('21930023', 'Resyafutra', 'Laki-Laki', '082125325055', 'Operational', 'Kurir', 'Perum. Taman Cibinong Asri Blok C3 No. 32 RT/RW 001/019 Kel/Desa Karadenan Kec. Cibinong Jabar Kab. Bogor', 'Manager', 9, '21930023', 'foto21930023t.jpg', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('21970019', 'Nur Meinanda Handi Resmana', 'Perempuan', '85692350910', 'Apoteker', 'Asisten Apoteker / Admin Gudang', 'Jl. Anggrek Kp. Bulak RT. 004 RW. 003 Kel. Pondok Kacang Timur Kec. Pondok Aren Banten , Kota Tangerang Selatan\r\n', 'Manager', 0, '21970019', 'foto21970019.jpeg', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('21980021', 'Muhammad Abdullah', 'Laki-Laki', '089605905415', 'Operational', 'Sales / Marketing', 'Pondok Rajeg Indah Blok D No. 45 RT/RW 002/009, Kec. Cibinong Kab. Bogor, Jawa Barat', 'Manager', 11, '21980021', 'foto21980021t.jpg', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('21990018', 'Habbi Yuda Rizkih', 'Laki-Laki', '88809399577', 'Oprasional', 'Gudang', 'Jl. H. Maskur RT/RW 002/002 Kel/Desa Pinang Kec. Pinang Kota Tangerang\r\n', 'Manager', 7, '21990018', 'fototegar.webp', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('22010028', 'Wike Mardianti', 'Perempuan', '895635328933', 'FINANCE', 'FINANCE', 'Bojong RT/RW 003/015 Kel. Kunciran Indah Kec. Pinang Kota Tangerang, Banten\r\n', 'Manager', 6, '22010028', 'foto22010028.jpeg', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('22010031', 'Farid Rahman Hakim', 'Laki-Laki', '089508278026', 'Operational', 'Kurir', 'Kp. Kayu Gede RT/RW 004/022 Kel/ Pakujaya Kec. Serpong Utara Kota Tangerang Selatan, Banten', 'Manager', 8, '22010031', 'foto22010031t.jpg', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('22830034', 'Tri Budi Kartono', 'Laki-Laki', '087789498318', 'Marketing', 'Sales / Marketing', 'Pejuang Jaya Blok A/103 RT 006 RW 001 Kel. Pejuang, Kec. Medan Satria, Kota Bekasi', 'Manager', 12, '22830034', 'foto22830034t.jpg', '3275062104830015', 'Ciamis', 'Pejuang Jaya Blok A/103 RT 006 RW 001 Kel. Pejuang', '1983-04-21', 'SMA', 'SMA', '2022-11-07', 'Aktif', 'Tidak ada', 2147483647, 'Aktif', 0, '', 0),
('22900030', 'Nurrohmat', 'Laki-Laki', '088213977551', 'Operational', 'Kurir', 'Dukuh I RT/RW 013/005 Kel. Demen, Kec. Temon Kab. Kulon Progo DIY', 'Manager', 7, '22900030', 'foto22900030t.jpg', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('22910033', 'Nurmansyah', 'Laki-Laki', '085881667669', 'Operational', 'Kurir', 'Ps. Minggu Kembangan RT 007 RW 001 Kel/Desa Kembangan Selatan Kec. Kembangan Jakarta Barat', 'Manager', 12, '22910033', 'foto22910033t.jpg', '3173082610910006', 'Jakarta', 'Ps. Minggu Kembangan RT 007 RW 001 Kel/Desa Kemban', '1991-10-26', 'SMA', 'SMA', '2022-09-26', 'Aktif', 'Tidak ada', 2147483647, 'Aktif', 0, '', 0),
('22910038', 'Tiara Puspita Sari', 'Perempuan', '081382852291', 'HR & Data Analyst', 'HR & Data Analyst', 'Komp. Yonhub AD E/45 RT/RW 007/004 Kel/Desa Sukabumi Utara Kec. Kebon Jeruk Jakarta Barat\r\n', 'Manager', 10, '22910038', 'foto22910038.jpeg', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('22930032', 'Albian Zain', 'Laki-Laki', '089652299646', 'Operational', 'Kurir', 'Cipayung RT/RW 003/007 Kel. Pondok Rajeg Kec. Cibinong Kab. Bogor Jabar', 'Manager', 11, '22930032', 'foto22930032t.jpg', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('22980029', 'Barry Rizki R', 'Laki-Laki', '089602917433', 'Operational', 'Kurir', 'Meruya Selatan GG. Asem RT/RW 003/007 Kel./Desa meruya Selatan Kec. Kembangan Jakarta Barat', 'Manager', 8, '22980029', 'foto22980029t.jpg', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('22990037', 'Abdul', 'Laki-Laki', '085810148279', 'Operational', 'Kurir', 'Kp. Kebun Teh RT/RW 006/003 Kel/Desa Cinangka Kec. Ciampea Kab. Bogor', 'Manager', 11, '22990037', 'foto22990037t.jpg', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 800),
('23010043', 'Sekar Kinasih', 'Perempuan', '081389131081', 'Finance', 'Staff Accounting/finance', 'Ciledug Indah II DB-15/5 RT/RW 002/007 Kel/Desa Pedurenan Kec. Karang Tengah Kota Tangerang', 'Manager', 12, '23010043', 'foto23010043t.jpg', '3671114107010196', 'Purwakarta', 'Ciledug Indah II DB-15/5 RT/RW 002/007 Kel/Desa Pe', '2021-07-01', 'SMA', 'SMA', '2023-02-22', 'NonAktif', 'Tidak ada', 2147483647, 'Aktif', 0, '', 0),
('23780072', 'Achmad Arief Ananto', 'Laki-Laki', '081212976883', 'Accounting', 'Manager Finance', 'Candi Sawangan Cluster Kamila II/B 19 No.31 RT.007/005 Bojongsari Baru, Bojong sari', 'Supervisor', 12, '23780072', 'fototegar.webp', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 1, '', 0),
('23900048', 'Devi Riansyah Achmad', 'Laki-Laki', '081779514485', 'Operational', 'Kurir', 'Meruya Utara Rt.007/011, Meruya Utara, Kembangan Jakarta Barat', 'Manager', 8, '23900048', 'foto23900048t.jpg', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('23920040', 'Isnainul Fajri', 'Laki-Laki', '081285957873', 'Apoteker', 'Apoteker Penanggung Jawab', 'Kp. Cipedak RT.006/009, Srengseng Sawah, Jagakarsa', 'Manager', 10, '23920040', 'foto23920040t.jpg', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('23920047', 'Firmansyah', 'Laki-Laki', '089655894178', 'Operational', 'Kurir', 'Jl. H.Riwan II No. C113B RT. 004/014, Kunciran Indah , Pinang ', 'Manager', 10, '23920047', 'foto23920047t.jpg', '', '', '', '0000-00-00', '', '', '0000-00-00', '', '', 0, 'Aktif', 0, '', 0),
('23970041', 'Faisal Maulana', 'Laki-Laki', '08980371331', 'Operational', 'Kurir', 'Pondok Serut RT/RW 008/003 Kel/Desa Pakujaya Kec. Serpong Prov Banten Kota Tangerang', 'Manager', 12, '23970041', 'foto23970041t.jpg', '3674022503970001', 'Tangerang', 'Pondok Serut RT/RW 008/003 Kel/Desa Pakujaya Kec. ', '1997-03-25', 'SMA', 'SMA', '2023-02-20', 'Belum di daftarkan', 'Tidak ada', 2147483647, 'Aktif', 0, '', 6000),
('Admin', 'Admin', 'Laki-laki', '08599999', 'HR', 'HR', 'Jakarta Indonesia', 'Admin', 12, 'admin', 'kosong', '3333', 'Jakarta', 'Jakarta', '2024-02-01', 's1', 'Jakarta', '2024-02-01', 'aktif', 'ff', 555, 'active', 1, 'jakarta', 0);

-- --------------------------------------------------------

--
-- Table structure for table `koordinator`
--

CREATE TABLE `koordinator` (
  `id` int(11) NOT NULL,
  `nama_koordinator` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL,
  `Akses` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `lembur`
--

CREATE TABLE `lembur` (
  `id_lmbr` varchar(80) NOT NULL,
  `npp` varchar(56) NOT NULL,
  `nama_karyawan` varchar(50) NOT NULL,
  `tujuan_lembur` varchar(50) NOT NULL,
  `cabang` varchar(44) NOT NULL,
  `tgl_lembur` datetime NOT NULL,
  `jam_mulai_lembur` varchar(50) NOT NULL,
  `jam_berakhir_lembur` varchar(50) NOT NULL,
  `nama_koordinator` varchar(50) NOT NULL,
  `alasan_lembur` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL,
  `jumlah` int(15) NOT NULL,
  `reject` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `lembur`
--

INSERT INTO `lembur` (`id_lmbr`, `npp`, `nama_karyawan`, `tujuan_lembur`, `cabang`, `tgl_lembur`, `jam_mulai_lembur`, `jam_berakhir_lembur`, `nama_koordinator`, `alasan_lembur`, `status`, `jumlah`, `reject`) VALUES
('09012024092938', '20990013', 'sadada', 'Lembur Hari Libur Nasional', 'Cibinong', '2024-01-09 00:00:00', '09:29', '09:29', 'asdasd', 'asdasd', 'Rejected', 0, ''),
('11012024154716', '20990013', 'tegarsa', 'Lembur Oprasional', 'Cibinong', '2024-01-11 00:00:00', '15:47', '16:48', '33', '33', 'Approved', 2, '');

-- --------------------------------------------------------

--
-- Table structure for table `manager`
--

CREATE TABLE `manager` (
  `id` int(11) NOT NULL,
  `nama_manager` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL,
  `akses` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `rembes`
--

CREATE TABLE `rembes` (
  `id_rmbs` varchar(50) NOT NULL,
  `npp` varchar(50) NOT NULL,
  `fasilitas_kesehatan` varchar(100) NOT NULL,
  `nama_anggota_keluarga` varchar(50) NOT NULL,
  `hubungan_keluarga` varchar(50) NOT NULL,
  `nama_dokter` varchar(50) NOT NULL,
  `tanggal_pemeriksaan` datetime NOT NULL,
  `total_kwitansi` varchar(50) NOT NULL,
  `no_kwitansi` varchar(11) NOT NULL,
  `foto` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `reject` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `rembes`
--

INSERT INTO `rembes` (`id_rmbs`, `npp`, `fasilitas_kesehatan`, `nama_anggota_keluarga`, `hubungan_keluarga`, `nama_dokter`, `tanggal_pemeriksaan`, `total_kwitansi`, `no_kwitansi`, `foto`, `status`, `reject`) VALUES
('29012024145202', '20990013', 'dgdfg', 'dfg', 'Anak', 'dfg', '2024-01-29 00:00:00', '345345', 'sdfs', 'foto20990013Screenshot 2024-01-29 at 10.00.50.png', 'Rejected', 's'),
('29012024145227', '20990013', 'dasd', 'asd', 'Anak', 'asda', '2024-01-29 00:00:00', '11111', 'dasda', 'foto20990013Screenshot 2024-01-29 at 14.52.05.png', 'Approved', 'kosong'),
('29012024151407', '19880003', 'asdas', 'asdas', 'Anak', 'adasd', '2024-01-29 00:00:00', '13123', '213123', 'foto19880003Screenshot 2024-01-29 at 10.00.50.png', 'Approved', 'kosong');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_adm`);

--
-- Indexes for table `cuti`
--
ALTER TABLE `cuti`
  ADD PRIMARY KEY (`no_cuti`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`npp`);

--
-- Indexes for table `koordinator`
--
ALTER TABLE `koordinator`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lembur`
--
ALTER TABLE `lembur`
  ADD PRIMARY KEY (`id_lmbr`);

--
-- Indexes for table `manager`
--
ALTER TABLE `manager`
  ADD KEY `id` (`id`);

--
-- Indexes for table `rembes`
--
ALTER TABLE `rembes`
  ADD PRIMARY KEY (`id_rmbs`),
  ADD UNIQUE KEY `id_rmbs_2` (`id_rmbs`),
  ADD KEY `id_rmbs` (`id_rmbs`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_adm` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
