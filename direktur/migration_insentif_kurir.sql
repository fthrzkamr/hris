-- Migration: Add potongan_makan and akumulasi_telat columns to transaksi_insentif_kurir table
-- Date: 2026-02-13
-- Description: Adds columns to store meal deductions and attendance lateness tracking

-- Add potongan_makan column (if not exists)
ALTER TABLE transaksi_insentif_kurir
ADD COLUMN IF NOT EXISTS potongan_makan DECIMAL(15,2) NOT NULL DEFAULT 0.00 
COMMENT 'Potongan absolut uang makan dari ketidakhadiran (alpha)' 
AFTER denda_telat;

-- Add akumulasi_telat column (if not exists)  
ALTER TABLE transaksi_insentif_kurir
ADD COLUMN IF NOT EXISTS akumulasi_telat INT NOT NULL DEFAULT 0 
COMMENT 'Total menit keterlambatan dalam periode bulanan (SUM dari menit_terlambat harian)' 
AFTER denda_telat;

-- Optional: Update existing records to calculate potongan_makan from uang_makan
-- This assumes UANG_MAKAN_BULANAN = 300000
-- potongan_makan = 300000 - uang_makan
UPDATE transaksi_insentif_kurir 
SET potongan_makan = (300000 - uang_makan) 
WHERE potongan_makan = 0 AND uang_makan < 300000;
