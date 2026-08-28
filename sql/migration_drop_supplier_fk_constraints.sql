-- Migration: Drop FOREIGN KEY constraints on supplier_id columns
-- This allows deleting suppliers without cascade-restricting
-- Transaction data (purchases, payments, products, ledger) remains intact
-- Run this ONCE on your database

ALTER TABLE `products` DROP FOREIGN KEY `products_ibfk_4`;
ALTER TABLE `purchase_master` DROP FOREIGN KEY `purchase_master_ibfk_1`;
ALTER TABLE `supplier_ledger` DROP FOREIGN KEY `supplier_ledger_ibfk_1`;
ALTER TABLE `supplier_payments` DROP FOREIGN KEY `supplier_payments_ibfk_1`;
