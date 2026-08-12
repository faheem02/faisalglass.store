-- Faysal Glass - Quotation Hold Bills (sales-style hold)
-- Run against faysal_glass database before deploying quotation hold changes.

CREATE TABLE IF NOT EXISTS `hold_quotations_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hold_no` varchar(50) NOT NULL,
  `hold_date` date NOT NULL,
  `customer_id` int(11) NOT NULL,
  `valid_until` date DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `other_charges` decimal(15,2) DEFAULT 0.00,
  `grand_total` decimal(15,2) DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `status` enum('hold','converted','cancelled') DEFAULT 'hold',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `hold_no_unique` (`hold_no`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hold_quotations_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hold_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `client_height` decimal(10,2) DEFAULT 0.00,
  `client_width` decimal(10,2) DEFAULT 0.00,
  `std_height` varchar(50) DEFAULT NULL,
  `std_width` varchar(50) DEFAULT NULL,
  `uom` varchar(20) DEFAULT 'Inch',
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `area` decimal(15,2) DEFAULT 0.00,
  `amount` decimal(15,2) DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `net_amount` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_hold_id` (`hold_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
