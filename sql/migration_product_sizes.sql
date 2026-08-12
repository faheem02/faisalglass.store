-- ============================================================
-- Migration: Multi-size products + per-size opening stock
-- Faysal Glass And Aluminium Centre
--
-- Run once on the live DB (faysal_glass).
-- Existing data is untouched (new columns are nullable).
-- ============================================================

-- 1. New table: product_sizes (one product -> many sizes)
CREATE TABLE IF NOT EXISTS `product_sizes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `size_label` varchar(100) DEFAULT NULL,
  `length_inch` decimal(10,2) DEFAULT '0.00',
  `width_inch` decimal(10,2) DEFAULT '0.00',
  `length_feet` decimal(10,2) DEFAULT '0.00',
  `width_feet` decimal(10,2) DEFAULT '0.00',
  `area_sqft` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_sizes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. opening_stock: link each entry to a size + keep pieces count
ALTER TABLE `opening_stock`
  ADD COLUMN `product_size_id` int DEFAULT NULL AFTER `product_id`,
  ADD COLUMN `pieces` decimal(15,2) DEFAULT '0.00' AFTER `quantity`;

ALTER TABLE `opening_stock`
  ADD KEY `product_size_id` (`product_size_id`),
  ADD CONSTRAINT `opening_stock_ibfk_2` FOREIGN KEY (`product_size_id`) REFERENCES `product_sizes` (`id`) ON DELETE SET NULL;

-- 3. Per-size rates: purchase_rate / sale_rate per size (rate per sq ft)
ALTER TABLE `product_sizes`
  ADD COLUMN `purchase_rate` decimal(15,2) DEFAULT NULL AFTER `area_sqft`,
  ADD COLUMN `sale_rate` decimal(15,2) DEFAULT NULL AFTER `purchase_rate`;
