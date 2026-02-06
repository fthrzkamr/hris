-- Database tables for Incentive System
-- Created: 2026-02-06

-- Table for courier incentives
CREATE TABLE IF NOT EXISTS `insentif_kurir` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `npp` varchar(20) NOT NULL,
  `periode` date NOT NULL,
  `total_titik` int(11) NOT NULL DEFAULT 0,
  `target_titik` int(11) NOT NULL DEFAULT 0,
  `persentase_pencapaian` decimal(5,2) DEFAULT 0.00,
  `status_target` enum('Tercapai','Tidak Tercapai') DEFAULT 'Tidak Tercapai',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_npp_periode` (`npp`, `periode`),
  KEY `idx_npp` (`npp`),
  KEY `idx_periode` (`periode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for employee attendance (absensi karyawan)
CREATE TABLE IF NOT EXISTS `absensi_karyawan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `npp` varchar(20) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `durasi_kerja` time DEFAULT NULL,
  `status_absensi` enum('Hadir','Terlambat','Tidak Hadir','Pulang Awal') DEFAULT 'Hadir',
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_npp_tanggal` (`npp`, `tanggal`),
  KEY `idx_npp` (`npp`),
  KEY `idx_tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
