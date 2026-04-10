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
CREATE DATABASE IF NOT EXISTS `u9621710_hris` /*!40100 DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `u9621710_hris`;

-- Dumping structure for table u9621710_hris.absensi_kurir
CREATE TABLE IF NOT EXISTS `absensi_kurir` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `npp` varchar(20) NOT NULL,
  `tanggal_absen` date NOT NULL,
  `jam_absen` varchar(11) NOT NULL,
  `jam_masuk` time DEFAULT NULL COMMENT 'Jam masuk kurir (parsed dari jam_absen)',
  `jam_pulang` time DEFAULT NULL COMMENT 'Jam pulang kurir (parsed dari jam_absen)',
  `is_hadir` tinyint(1) DEFAULT '1' COMMENT '1=Hadir, 0=Alpha/Cuti',
  `is_late` tinyint(1) DEFAULT '0' COMMENT '1=Terlambat, 0=Tepat Waktu',
  `menit_terlambat` int DEFAULT '0' COMMENT 'Berapa menit terlambat dari batas jam masuk',
  `jenis_tugas` varchar(50) NOT NULL,
  `area_cabang` varchar(50) DEFAULT NULL,
  `total_aktual_titik` int DEFAULT '0',
  `target_titik` int DEFAULT '0',
  `insentif_titik` decimal(15,2) DEFAULT '0.00' COMMENT 'Bonus dari kelebihan titik harian (jika ada)',
  `denda_telat` decimal(15,2) DEFAULT '0.00' COMMENT 'Potongan denda keterlambatan harian',
  `uang_makan` decimal(15,2) DEFAULT '0.00' COMMENT 'Allowance uang makan harian (positif=dapat, negatif=potong)',
  `uang_lembur` decimal(15,2) DEFAULT '0.00' COMMENT 'Total uang lembur harian (dari tabel lembur)',
  `grand_total_harian` decimal(15,2) DEFAULT '0.00' COMMENT 'Total pendapatan harian (insentif+makan+lembur-denda)',
  `is_cuti` tinyint(1) DEFAULT '0' COMMENT '1=Sedang Cuti Approved, 0=Tidak Cuti',
  `is_sakit` tinyint(1) NOT NULL DEFAULT '0',
  `keterangan_cuti` varchar(255) DEFAULT NULL,
  `lembur_operasional` int DEFAULT '0',
  `lembur_ambil_barang` int DEFAULT '0',
  `lembur_lainnya` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique_daily` (`npp`,`tanggal_absen`)
) ENGINE=InnoDB AUTO_INCREMENT=437 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table u9621710_hris.transaksi_insentif_kurir
CREATE TABLE IF NOT EXISTS `transaksi_insentif_kurir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `npp` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nomor Pokok Pegawai',
  `periode` char(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Format YYYY-MM untuk periode bulanan',
  `total_titik` int DEFAULT '0' COMMENT 'Total pencapaian titik bulanan (SUM harian)',
  `target_titik` int DEFAULT '0' COMMENT 'Total target bulanan (SUM harian)',
  `bonus_insentif_titik` decimal(15,2) DEFAULT '0.00' COMMENT 'Bonus dari kelebihan titik bulanan',
  `bonus_insentif_full_masuk` decimal(15,2) DEFAULT '0.00' COMMENT 'Bonus dari full masuk',
  `denda_telat` decimal(15,2) DEFAULT '0.00' COMMENT 'Total denda telat bulanan',
  `akumulasi_telat` int DEFAULT '0' COMMENT 'Total dari akumulasi telat ',
  `hari_hadir` int NOT NULL DEFAULT '0',
  `hari_telat` int NOT NULL DEFAULT '0',
  `hari_cuti` int NOT NULL DEFAULT '0',
  `hari_sakit` int NOT NULL DEFAULT '0',
  `hari_alpha` int NOT NULL DEFAULT '0',
  `uang_makan` decimal(15,2) DEFAULT '0.00' COMMENT 'Total uang makan bulanan (positif=allowance, negatif=penalty)',
  `potongan_makan` decimal(15,2) DEFAULT '0.00',
  `uang_lembur` decimal(15,2) DEFAULT '0.00' COMMENT 'Total uang lembur bulanan',
  `jumlah_dibayarkan` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lembur_ambil_barang` decimal(15,2) DEFAULT NULL,
  `lembur_operasional` decimal(15,2) DEFAULT NULL,
  `lembur_lainnya` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_transaksi_bulanan` (`npp`,`periode`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
