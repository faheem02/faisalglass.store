-- Migration: Drop FOREIGN KEY constraints on product_id columns
-- This allows deleting products without cascade-restricting
-- Transaction data (sales, purchases, quotations) remains intact
-- Run this ONCE on your database

ALTER TABLE `inventory_ledger` DROP FOREIGN KEY `inventory_ledger_ibfk_1`;
ALTER TABLE `opening_stock` DROP FOREIGN KEY `opening_stock_ibfk_1`;
ALTER TABLE `product_sizes` DROP FOREIGN KEY `product_sizes_ibfk_1`;
ALTER TABLE `quotation_details` DROP FOREIGN KEY `quotation_details_ibfk_2`;
