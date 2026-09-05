-- Migration: Add walk-in customer name/phone columns
-- Compatible with MySQL 5.7 (does NOT use ADD COLUMN IF NOT EXISTS, which requires MySQL 8.0)
-- Safe to run multiple times: checks INFORMATION_SCHEMA before adding each column.
-- These store the walk-in customer's name & phone for a single transaction
-- so the sale/quotation shows who the walk-in customer was.

-- Helper: add walk_in_customer_name column if missing
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sale_master' AND COLUMN_NAME = 'walk_in_customer_name') = 0,
  'ALTER TABLE `sale_master` ADD COLUMN `walk_in_customer_name` varchar(200) DEFAULT NULL AFTER `customer_id`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sale_master' AND COLUMN_NAME = 'walk_in_customer_phone') = 0,
  'ALTER TABLE `sale_master` ADD COLUMN `walk_in_customer_phone` varchar(20) DEFAULT NULL AFTER `walk_in_customer_name`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quotation_master' AND COLUMN_NAME = 'walk_in_customer_name') = 0,
  'ALTER TABLE `quotation_master` ADD COLUMN `walk_in_customer_name` varchar(200) DEFAULT NULL AFTER `customer_id`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quotation_master' AND COLUMN_NAME = 'walk_in_customer_phone') = 0,
  'ALTER TABLE `quotation_master` ADD COLUMN `walk_in_customer_phone` varchar(20) DEFAULT NULL AFTER `walk_in_customer_name`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hold_sales_master' AND COLUMN_NAME = 'walk_in_customer_name') = 0,
  'ALTER TABLE `hold_sales_master` ADD COLUMN `walk_in_customer_name` varchar(200) DEFAULT NULL AFTER `customer_id`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hold_sales_master' AND COLUMN_NAME = 'walk_in_customer_phone') = 0,
  'ALTER TABLE `hold_sales_master` ADD COLUMN `walk_in_customer_phone` varchar(20) DEFAULT NULL AFTER `walk_in_customer_name`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hold_quotations_master' AND COLUMN_NAME = 'walk_in_customer_name') = 0,
  'ALTER TABLE `hold_quotations_master` ADD COLUMN `walk_in_customer_name` varchar(200) DEFAULT NULL AFTER `customer_id`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hold_quotations_master' AND COLUMN_NAME = 'walk_in_customer_phone') = 0,
  'ALTER TABLE `hold_quotations_master` ADD COLUMN `walk_in_customer_phone` varchar(20) DEFAULT NULL AFTER `walk_in_customer_name`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Seed the generic walk-in customer record (only if it doesn't already exist)
-- This is required for the Walk-In quick button and per-sale name/phone capture.
INSERT INTO `customers` (customer_code, customer_name, mobile, opening_balance, current_balance, balance_type, status, notes)
SELECT 'WALK-IN', 'Walk-In Customer', '0000000000', 0, 0, 'receivable', 1, 'Generic walk-in customer account - per-sale name/phone stored on invoice'
WHERE NOT EXISTS (SELECT 1 FROM `customers` WHERE `customer_code` = 'WALK-IN' OR `customer_name` LIKE 'Walk-In%');