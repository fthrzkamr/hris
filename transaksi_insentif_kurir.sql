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

-- Dumping structure for table u9621710_hris.transaksi_insentif_kurir
CREATE TABLE IF NOT EXISTS `transaksi_insentif_kurir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `npp` varchar(20) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nomor Pokok Pegawai',
  `periode` char(7) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Format YYYY-MM untuk periode bulanan',
  `total_titik` int DEFAULT '0' COMMENT 'Total pencapaian titik bulanan (SUM harian)',
  `target_titik` int DEFAULT '0' COMMENT 'Total target bulanan (SUM harian)',
  `bonus_insentif` decimal(15,2) DEFAULT '0.00' COMMENT 'Bonus dari kelebihan titik bulanan',
  `denda_telat` decimal(15,2) DEFAULT '0.00' COMMENT 'Total denda telat bulanan',
  `potongan_makan` decimal(15,2) DEFAULT '0.00' COMMENT 'Total uang makan bulanan (positif=allowance, negatif=penalty)',
  `uang_lembur` decimal(15,2) DEFAULT '0.00' COMMENT 'Total uang lembur bulanan',
  `jumlah_dibayarkan` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_transaksi_bulanan` (`npp`,`periode`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table u9621710_hris.transaksi_insentif_kurir: ~1 rows (approximately)
REPLACE INTO `transaksi_insentif_kurir` (`id`, `npp`, `periode`, `total_titik`, `target_titik`, `bonus_insentif`, `denda_telat`, `potongan_makan`, `uang_lembur`, `jumlah_dibayarkan`, `created_at`, `updated_at`) VALUES
	(1, '22910033', '2026-02', 835, 750, 1700000.00, 5000.00, 75000.00, 0.00, 1770000, '2026-02-10 08:19:58', '2026-02-10 08:19:58');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
