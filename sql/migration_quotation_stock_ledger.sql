-- ============================================================
-- Migration: Quotation affects stock + customer ledger (like sale)
-- Faysal Glass And Aluminium Centre
--
-- Run once on the live DB (faysal_glass).
-- Adds 'QUOTATION' to the reference_type enums so quotation
-- entries can be posted to inventory_ledger and customer_ledger.
-- Existing rows are untouched (enum values appended at the end).
-- ============================================================

ALTER TABLE `inventory_ledger`
  MODIFY COLUMN `reference_type` enum('OPENING','PURCHASE','SALE','ADJUSTMENT','QUOTATION') NOT NULL;

ALTER TABLE `customer_ledger`
  MODIFY COLUMN `reference_type` enum('OPENING','SALE','PAYMENT','ADJUSTMENT','QUOTATION') NOT NULL;
