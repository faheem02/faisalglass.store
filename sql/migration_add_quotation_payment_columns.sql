-- Migration: Add payment columns to quotation_master
-- Safe to run multiple times (uses IF NOT EXISTS)

ALTER TABLE `quotation_master` ADD COLUMN IF NOT EXISTS `payment_type` enum('cash','bank','credit','partial') DEFAULT 'credit' AFTER `grand_total`;
ALTER TABLE `quotation_master` ADD COLUMN IF NOT EXISTS `bank_account_id` int(11) DEFAULT NULL AFTER `payment_type`;
ALTER TABLE `quotation_master` ADD COLUMN IF NOT EXISTS `reference_no` varchar(100) DEFAULT NULL AFTER `bank_account_id`;
ALTER TABLE `quotation_master` ADD COLUMN IF NOT EXISTS `received_amount` decimal(15,2) DEFAULT 0.00 AFTER `reference_no`;
ALTER TABLE `quotation_master` ADD COLUMN IF NOT EXISTS `remaining_amount` decimal(15,2) DEFAULT 0.00 AFTER `received_amount`;
