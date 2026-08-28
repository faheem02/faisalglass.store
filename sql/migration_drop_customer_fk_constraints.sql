-- Migration: Drop FOREIGN KEY constraints on customer_id columns
-- This allows deleting customers without cascade-restricting
-- Transaction data (sales, payments, ledger, quotations) remains intact
-- Run this ONCE on your database

ALTER TABLE `customer_ledger` DROP FOREIGN KEY `customer_ledger_ibfk_1`;
ALTER TABLE `customer_payments` DROP FOREIGN KEY `customer_payments_ibfk_1`;
ALTER TABLE `customer_receipts` DROP FOREIGN KEY `customer_receipts_ibfk_1`;
ALTER TABLE `quotation_master` DROP FOREIGN KEY `quotation_master_ibfk_1`;
ALTER TABLE `sale_master` DROP FOREIGN KEY `sale_master_ibfk_1`;
