-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for u9621710_hris
CREATE DATABASE IF NOT EXISTS `u9621710_hris` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `u9621710_hris`;

-- Dumping structure for table u9621710_hris.absensi_karyawan
CREATE TABLE IF NOT EXISTS `absensi_karyawan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `npp` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `durasi_kerja` time DEFAULT NULL,
  `status_absensi` enum('Hadir','Terlambat','Tidak Hadir','Pulang Awal') COLLATE utf8mb4_general_ci DEFAULT 'Hadir',
  `keterangan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_npp_tanggal` (`npp`,`tanggal`),
  KEY `idx_npp` (`npp`),
  KEY `idx_tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table u9621710_hris.admin
CREATE TABLE IF NOT EXISTS `admin` (
  `id_adm` int NOT NULL AUTO_INCREMENT,
  `nama_adm` varchar(50) NOT NULL,
  `telp_adm` varchar(15) NOT NULL,
  `user_adm` varchar(50) NOT NULL,
  `pass_adm` varchar(100) NOT NULL,
  `foto_adm` text NOT NULL,
  PRIMARY KEY (`id_adm`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table u9621710_hris.angsuran_pinjaman
CREATE TABLE IF NOT EXISTS `angsuran_pinjaman` (
  `id_angsuran` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `id_pinjaman` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `bulan` int NOT NULL,
  `tahun` int NOT NULL,
  `jumlah_angsuran` int NOT NULL,
  `status` enum('belum','dibayar') COLLATE utf8mb4_general_ci DEFAULT 'belum',
  `tanggal_potong` date DEFAULT NULL,
  PRIMARY KEY (`id_angsuran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table u9621710_hris.bagian
CREATE TABLE IF NOT EXISTS `bagian` (
  `id_bagian` int NOT NULL AUTO_INCREMENT,
  `nama_bagian` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id_bagian`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table u9621710_hris.cuti
CREATE TABLE IF NOT EXISTS `cuti` (
  `no_cuti` varchar(30) NOT NULL,
  `npp` varchar(20) NOT NULL,
  `tgl_pengajuan` date NOT NULL,
  `tgl_awal` date NOT NULL,
  `tgl_akhir` date NOT NULL,
  `durasi` int NOT NULL,
  `keterangan` text NOT NULL,
  `leader` varchar(20) NOT NULL,
  `manager` varchar(30) NOT NULL,
  `spv` varchar(20) NOT NULL,
  `stt_cuti` varchar(50) NOT NULL,
  `tipe_cuti` varchar(100) NOT NULL,
  `ket_reject` text NOT NULL,
  `hrd_app` int NOT NULL,
  `lead_app` int NOT NULL,
  `spv_app` int NOT NULL,
  `mng_app` int NOT NULL,
  PRIMARY KEY (`no_cuti`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table u9621710_hris.employee
CREATE TABLE IF NOT EXISTS `employee` (
  `npp` varchar(20) NOT NULL,
  `nama_emp` varchar(100) NOT NULL,
  `jk_emp` varchar(20) NOT NULL,
  `telp_emp` varchar(20) NOT NULL,
  `nama_bagian` varchar(50) NOT NULL,
  `alamat` text NOT NULL,
  `hak_akses` varchar(20) NOT NULL,
  `jml_cuti` int NOT NULL,
  `password` varchar(100) NOT NULL,
  `foto_emp` text NOT NULL,
  `nomor_ktp` varchar(50) NOT NULL,
  `kota_lahir` varchar(50) NOT NULL,
  `alamat_tinggal_sekarang` text NOT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `pendidikan_terakhir` varchar(25) NOT NULL,
  `nama_institusi` varchar(25) NOT NULL,
  `tanggal_masuk_karyawan` date DEFAULT NULL,
  `norek_mandiri` varchar(30) NOT NULL,
  `aktif` varchar(20) NOT NULL,
  `id_adm` int NOT NULL,
  `cabang` varchar(50) NOT NULL,
  `kesehatan` int NOT NULL,
  `plafond` int NOT NULL,
  `status_kawin` enum('belum menikah','sudah menikah') NOT NULL,
  `nama_pasangan` varchar(30) NOT NULL,
  `pekerjaan` varchar(40) NOT NULL,
  `nomor_tlp` varchar(15) NOT NULL,
  `nama_anak` varchar(70) NOT NULL,
  `nama_koordinator` varchar(50) NOT NULL,
  `nama_manager` varchar(50) NOT NULL,
  `status_rem` varchar(45) NOT NULL,
  `jabatan` varchar(30) NOT NULL,
  `status_ptkp` varchar(20) NOT NULL,
  `jurusan` varchar(50) NOT NULL,
  `nomor_npwp` varchar(50) NOT NULL,
  `bpjs_kesehatan` varchar(50) NOT NULL,
  `nomor_bpjs_ktr` varchar(50) NOT NULL,
  `nama_bank` varchar(30) NOT NULL,
  `agama` varchar(15) NOT NULL,
  `nomor_emrg_pr` varchar(20) DEFAULT NULL,
  `nomor_emrg_kd` varchar(20) DEFAULT NULL,
  `gol_darah` varchar(5) NOT NULL,
  `nomor_kk` varchar(50) NOT NULL,
  `status_karyawan` varchar(50) NOT NULL,
  `plafond_kacamata` int unsigned NOT NULL,
  `kacamata` bigint unsigned NOT NULL,
  `gaji_pokok` bigint NOT NULL,
  `tunj_jabatan` bigint NOT NULL,
  `tunj_transport` bigint NOT NULL,
  `tunj_kinerja` bigint NOT NULL,
  `total_gaji` bigint NOT NULL,
  PRIMARY KEY (`npp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table u9621710_hris.insentif_kurir
CREATE TABLE IF NOT EXISTS `insentif_kurir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `npp` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `periode` date NOT NULL,
  `total_titik` int NOT NULL DEFAULT '0',
  `target_titik` int NOT NULL DEFAULT '0',
  `persentase_pencapaian` decimal(5,2) DEFAULT '0.00',
  `status_target` enum('Tercapai','Tidak Tercapai') COLLATE utf8mb4_general_ci DEFAULT 'Tidak Tercapai',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_npp_periode` (`npp`,`periode`),
  KEY `idx_npp` (`npp`),
  KEY `idx_periode` (`periode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table u9621710_hris.kacamata
CREATE TABLE IF NOT EXISTS `kacamata` (
  `id_kacamata` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `npp` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_karyawan` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `jenis_kacamata` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_fasilitas` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `alamat_fasilitas` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_pengajuan` datetime NOT NULL,
  `total_kwintansi` decimal(10,2) DEFAULT NULL,
  `no_kwintansi` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `foto` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `reject` varchar(50) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table u9621710_hris.koordinator
CREATE TABLE IF NOT EXISTS `koordinator` (
  `id_koordinator` int NOT NULL AUTO_INCREMENT,
  `nama_koordinator` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `akses` varchar(12) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_koordinator`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table u9621710_hris.laporan_potongan
CREATE TABLE IF NOT EXISTS `laporan_potongan` (
  `id_laporan` int NOT NULL AUTO_INCREMENT,
  `npp` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `p_keterlambatan` int DEFAULT NULL,
  `p_pinjaman` int DEFAULT NULL,
  `p_lain` int DEFAULT NULL,
  `desc_lain` text COLLATE utf8mb4_general_ci,
  `p_hasil` int DEFAULT NULL,
  PRIMARY KEY (`id_laporan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table u9621710_hris.lembur
CREATE TABLE IF NOT EXISTS `lembur` (
  `id_lmbr` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `npp` varchar(56) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_karyawan` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `tujuan_lembur` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `cabang` varchar(44) COLLATE utf8mb4_general_ci NOT NULL,
  `tgl_lembur` datetime NOT NULL,
  `jam_mulai_lembur` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `jam_berakhir_lembur` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_koordinator` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `alasan_lembur` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `jumlah` int NOT NULL,
  `reject` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_lmbr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table u9621710_hris.manager
CREATE TABLE IF NOT EXISTS `manager` (
  `id_manager` int NOT NULL AUTO_INCREMENT,
  `nama_manager` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `akses` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_manager`),
  KEY `id` (`id_manager`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table u9621710_hris.pengajuan_sistem
CREATE TABLE IF NOT EXISTS `pengajuan_sistem` (
  `no_pengajuan` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `npp` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_karyawan` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi_pengajuan` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `nama_bagian` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `status_pengajuan` enum('belum dikerjakan','proses pengerjaan','sudah selesai') COLLATE utf8mb4_general_ci NOT NULL,
  `tgl_pengajuan` date NOT NULL,
  PRIMARY KEY (`no_pengajuan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table u9621710_hris.pinjaman
CREATE TABLE IF NOT EXISTS `pinjaman` (
  `id_pinjaman` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `npp` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_pengajuan` date NOT NULL,
  `jumlah_pinjaman` int NOT NULL,
  `tenor` int NOT NULL,
  `cicilan_per_bulan` int NOT NULL,
  `keterangan` text COLLATE utf8mb4_general_ci,
  `status` enum('aktif','lunas','ditolak') COLLATE utf8mb4_general_ci DEFAULT 'aktif',
  `tanggal_lunas` date DEFAULT NULL,
  PRIMARY KEY (`id_pinjaman`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table u9621710_hris.potongan
CREATE TABLE IF NOT EXISTS `potongan` (
  `id_potongan` varchar(15) COLLATE utf8mb4_general_ci NOT NULL,
  `npp` varchar(15) COLLATE utf8mb4_general_ci NOT NULL,
  `p_keterlambatan` bigint NOT NULL,
  `p_pinjaman` bigint NOT NULL,
  `p_lain` bigint NOT NULL,
  `desc_lain` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `p_hasil` bigint NOT NULL,
  `tanggal` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table u9621710_hris.rembes
CREATE TABLE IF NOT EXISTS `rembes` (
  `id_rmbs` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `npp` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_fasilitas_kesehatan` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `fasilitas_kesehatan` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_anggota_keluarga` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `hubungan_keluarga` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_dokter` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_pemeriksaan` datetime NOT NULL,
  `total_kwitansi` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `no_kwitansi` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `foto` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `reject` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_rmbs`),
  UNIQUE KEY `id_rmbs_2` (`id_rmbs`),
  KEY `id_rmbs` (`id_rmbs`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
