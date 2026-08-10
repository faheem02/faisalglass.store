-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 16, 2026 at 12:20 PM
-- Server version: 8.0.45-cll-lve
-- PHP Version: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `atrmarke_faysal_glass`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_transfers`
--

CREATE TABLE `account_transfers` (
  `id` int NOT NULL,
  `transfer_date` date NOT NULL,
  `from_account_type` enum('cash','bank1','bank2','bank3') NOT NULL,
  `from_account_id` int DEFAULT NULL,
  `to_account_type` enum('cash','bank1','bank2','bank3') NOT NULL,
  `to_account_id` int DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank1_transactions`
--

CREATE TABLE `bank1_transactions` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `reference_type` enum('OPENING','SALE','PURCHASE','CUSTOMER_PAYMENT','SUPPLIER_RECEIPT','WITHDRAW','TRANSFER_IN','TRANSFER_OUT','ADJUSTMENT') NOT NULL,
  `reference_id` int NOT NULL,
  `description` text,
  `debit` decimal(15,2) DEFAULT '0.00' COMMENT 'Deposit',
  `credit` decimal(15,2) DEFAULT '0.00' COMMENT 'Withdrawal',
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank2_transactions`
--

CREATE TABLE `bank2_transactions` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `reference_type` enum('OPENING','SALE','PURCHASE','CUSTOMER_PAYMENT','SUPPLIER_RECEIPT','WITHDRAW','TRANSFER_IN','TRANSFER_OUT','ADJUSTMENT') NOT NULL,
  `reference_id` int NOT NULL,
  `description` text,
  `debit` decimal(15,2) DEFAULT '0.00' COMMENT 'Deposit',
  `credit` decimal(15,2) DEFAULT '0.00' COMMENT 'Withdrawal',
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank3_transactions`
--

CREATE TABLE `bank3_transactions` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `reference_type` enum('OPENING','SALE','PURCHASE','CUSTOMER_PAYMENT','SUPPLIER_RECEIPT','WITHDRAW','TRANSFER_IN','TRANSFER_OUT','ADJUSTMENT') NOT NULL,
  `reference_id` int NOT NULL,
  `description` text,
  `debit` decimal(15,2) DEFAULT '0.00' COMMENT 'Deposit',
  `credit` decimal(15,2) DEFAULT '0.00' COMMENT 'Withdrawal',
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_title` varchar(200) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `branch_name` varchar(100) DEFAULT NULL,
  `opening_balance` decimal(15,2) DEFAULT '0.00',
  `current_balance` decimal(15,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_book`
--

CREATE TABLE `bank_book` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `bank_account_id` int NOT NULL,
  `reference_type` varchar(50) NOT NULL,
  `reference_id` int NOT NULL,
  `description` text,
  `debit` decimal(15,2) DEFAULT '0.00',
  `credit` decimal(15,2) DEFAULT '0.00',
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_book`
--

CREATE TABLE `cash_book` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `reference_type` varchar(50) NOT NULL,
  `reference_id` int NOT NULL,
  `description` text,
  `debit` decimal(15,2) DEFAULT '0.00',
  `credit` decimal(15,2) DEFAULT '0.00',
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cash_book`
--

INSERT INTO `cash_book` (`id`, `date`, `reference_type`, `reference_id`, `description`, `debit`, `credit`, `balance`, `created_at`) VALUES
(1, '2026-06-09', 'EXPENSE', 1, 'Expense: Electricity Bill', 0.00, 10000.00, -10000.00, '2026-06-09 05:38:28'),
(2, '2026-06-09', 'SALE', 1, 'Sale Payment: SAL-00001', 80000.00, 0.00, 70000.00, '2026-06-09 05:50:33'),
(3, '2026-06-10', 'SALE', 2, 'Sale Payment: SAL-00002', 20102.50, 0.00, 90102.50, '2026-06-10 12:33:21'),
(4, '2026-06-10', 'SALE', 3, 'Sale Payment: SAL-00003', 13250.00, 0.00, 103352.50, '2026-06-10 12:50:36'),
(5, '2026-06-10', 'SALE', 4, 'Sale Payment: SAL-00004', 60250.00, 0.00, 163602.50, '2026-06-10 13:32:46'),
(6, '2026-06-12', 'SALE', 5, 'Sale Payment: SAL-00005', 18293.75, 0.00, 181896.25, '2026-06-12 06:09:12'),
(7, '2026-06-12', 'SALE', 7, 'Sale Payment: SAL-00007', 137500.00, 0.00, 319396.25, '2026-06-12 09:44:50'),
(8, '2026-06-15', 'SALE', 8, 'Sale Payment: SAL-00008', 26800.00, 0.00, 346196.25, '2026-06-15 07:43:44');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text,
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Glass', '', 1, '2026-06-08 11:21:19', '2026-06-08 11:21:19');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `company_name`, `contact_person`, `phone`, `address`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Ghani', 'ghani', '03217917178', 'Khanewal', 1, '2026-06-08 11:21:11', '2026-06-08 11:21:11');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int NOT NULL,
  `customer_code` varchar(50) NOT NULL,
  `customer_name` varchar(200) NOT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  `mobile` varchar(20) NOT NULL,
  `cnic` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `opening_balance` decimal(15,2) DEFAULT '0.00',
  `balance_type` enum('receivable','payable') DEFAULT 'receivable' COMMENT 'receivable=Customer owes company, payable=Company owes customer',
  `current_balance` decimal(15,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_code`, `customer_name`, `company_name`, `mobile`, `cnic`, `email`, `address`, `opening_balance`, `balance_type`, `current_balance`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'CUS-0001', 'Test', 'Test', '03245675677', '', '', '', 0.00, 'receivable', 0.00, 1, '', '2026-06-09 05:49:58', '2026-06-09 05:49:58'),
(2, 'CUS-WALKIN', 'Walk-In Customer', '', '0000000000', '', '', '', 0.00, 'receivable', 0.00, 0, '', '2026-06-09 07:03:03', '2026-06-10 12:26:27'),
(4, 'CUS-0002', 'Anas', NULL, '0000000000', NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-06-10 12:05:31', '2026-06-10 12:05:31'),
(5, 'CUS-0003', 'Talha', NULL, '0000000000', NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-06-10 12:48:17', '2026-06-10 12:48:17'),
(6, 'CUS-0004', 'Abbas', NULL, '0000000000', NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-06-10 13:31:37', '2026-06-10 13:31:37'),
(7, 'CUS-0005', 'Ali', NULL, '0000000000', NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-06-12 05:43:50', '2026-06-12 05:43:50'),
(8, 'CUS-0006', 'Faheem', NULL, '0000000000', NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-06-12 06:08:12', '2026-06-12 06:08:12'),
(9, 'CUS-0007', 'sajid', 'Sajid', '03217917178', '', 'talhaarshadatr@gmail.com', 'P/O raja wala chak No. 22/10r kacha khuh', 25000.00, 'receivable', 106952.78, 1, '', '2026-06-12 06:12:10', '2026-06-15 15:10:33'),
(10, 'CUS-0008', 'Zareef', NULL, '0321545655', NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Added from quotation form', '2026-06-12 07:33:43', '2026-06-12 07:33:43'),
(11, 'CUS-0009', 'tahir', NULL, '0000000000', NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Added from quotation form', '2026-06-12 07:41:59', '2026-06-12 07:41:59'),
(12, 'CUS-0010', 'zaid', NULL, '0000000000', NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-06-12 09:44:23', '2026-06-12 09:44:23'),
(13, 'CUS-0011', 'abid', NULL, '0000000000', NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-06-15 10:05:17', '2026-06-15 10:05:17'),
(14, 'CUS-0012', 'teste', NULL, '0000000000', NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-06-15 10:06:55', '2026-06-15 10:06:55'),
(15, 'CUS-0013', 'sabi', NULL, '03214565898', NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Added from quotation form', '2026-06-16 06:48:24', '2026-06-16 06:48:24');

-- --------------------------------------------------------

--
-- Table structure for table `customer_ledger`
--

CREATE TABLE `customer_ledger` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `customer_id` int NOT NULL,
  `reference_type` enum('OPENING','SALE','PAYMENT','ADJUSTMENT') NOT NULL,
  `reference_id` int NOT NULL,
  `description` text,
  `debit` decimal(15,2) DEFAULT '0.00' COMMENT 'Customer owes company (Sale)',
  `credit` decimal(15,2) DEFAULT '0.00' COMMENT 'Customer pays company (Payment)',
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_ledger`
--

INSERT INTO `customer_ledger` (`id`, `date`, `customer_id`, `reference_type`, `reference_id`, `description`, `debit`, `credit`, `balance`, `created_at`) VALUES
(1, '2026-06-12', 9, 'OPENING', 9, 'Opening Balance - Receivable (Customer owes company)', 25000.00, 0.00, 25000.00, '2026-06-12 06:12:10'),
(2, '2026-06-12', 9, 'SALE', 6, 'Sale Invoice: SAL-00006 - \n                                    6mm Clear Glass                                 (15 x 19), \n                                    6mm Clear Glass                                 (11 x 85)', 25250.00, 0.00, 50250.00, '2026-06-12 06:13:22'),
(3, '2026-06-15', 9, 'SALE', 9, 'Sale Invoice: SAL-00009 - \n                                    12mm clear glass                                 (34 x 85), \n                                    12mm clear glass                                 (0 x 0), \n                                    12mm clear glass                                 (0 x 0) and 2 more items', 56702.78, 0.00, 106952.78, '2026-06-15 15:10:33');

-- --------------------------------------------------------

--
-- Table structure for table `customer_payments`
--

CREATE TABLE `customer_payments` (
  `id` int NOT NULL,
  `payment_date` date NOT NULL,
  `customer_id` int NOT NULL,
  `payment_method` enum('cash','bank') NOT NULL,
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `sale_invoice_no` varchar(50) DEFAULT NULL,
  `remarks` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_receipts`
--

CREATE TABLE `customer_receipts` (
  `id` int NOT NULL,
  `receipt_date` date NOT NULL,
  `customer_id` int NOT NULL,
  `payment_method` enum('cash','bank') NOT NULL,
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `sale_invoice_no` varchar(50) DEFAULT NULL,
  `remarks` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int NOT NULL,
  `employee_code` varchar(50) NOT NULL,
  `employee_name` varchar(200) NOT NULL,
  `father_name` varchar(200) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `employee_type` enum('permanent','contract','daily_wage','commission') DEFAULT 'permanent',
  `cnic` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `joining_date` date DEFAULT NULL,
  `basic_salary` decimal(15,2) DEFAULT '0.00',
  `allowances` decimal(15,2) DEFAULT '0.00',
  `deductions` decimal(15,2) DEFAULT '0.00',
  `net_salary` decimal(15,2) DEFAULT '0.00',
  `opening_balance` decimal(15,2) DEFAULT '0.00',
  `balance_type` enum('payable','advance') DEFAULT 'payable',
  `current_balance` decimal(15,2) DEFAULT '0.00',
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_ledger`
--

CREATE TABLE `employee_ledger` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `employee_id` int NOT NULL,
  `reference_type` enum('OPENING','SALARY','PAYMENT','ADVANCE','ADJUSTMENT') NOT NULL,
  `reference_id` int NOT NULL,
  `description` text,
  `debit` decimal(15,2) DEFAULT '0.00',
  `credit` decimal(15,2) DEFAULT '0.00',
  `balance` decimal(15,2) DEFAULT '0.00',
  `month_year` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_payments`
--

CREATE TABLE `employee_payments` (
  `id` int NOT NULL,
  `payment_date` date NOT NULL,
  `employee_id` int NOT NULL,
  `payment_method` enum('cash','bank') NOT NULL,
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `salary_month` varchar(10) DEFAULT NULL,
  `remarks` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_salary`
--

CREATE TABLE `employee_salary` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `month_year` varchar(10) NOT NULL,
  `basic_salary` decimal(15,2) DEFAULT '0.00',
  `allowances` decimal(15,2) DEFAULT '0.00',
  `deductions` decimal(15,2) DEFAULT '0.00',
  `net_salary` decimal(15,2) DEFAULT '0.00',
  `paid_amount` decimal(15,2) DEFAULT '0.00',
  `remaining_amount` decimal(15,2) DEFAULT '0.00',
  `status` enum('pending','partial','paid') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int NOT NULL,
  `expense_date` date NOT NULL,
  `head_id` int NOT NULL,
  `payment_method` enum('cash','bank') NOT NULL,
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `remarks` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_date`, `head_id`, `payment_method`, `bank_account_id`, `reference_no`, `amount`, `remarks`, `created_by`, `created_at`) VALUES
(1, '2026-06-09', 1, 'cash', 0, '', 10000.00, '', 1, '2026-06-09 05:38:28');

-- --------------------------------------------------------

--
-- Table structure for table `expense_heads`
--

CREATE TABLE `expense_heads` (
  `id` int NOT NULL,
  `head_name` varchar(100) NOT NULL,
  `description` text,
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_heads`
--

INSERT INTO `expense_heads` (`id`, `head_name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Electricity Bill', '', 1, '2026-06-09 05:37:53', '2026-06-09 05:37:53');

-- --------------------------------------------------------

--
-- Table structure for table `expense_ledger`
--

CREATE TABLE `expense_ledger` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `expense_id` int NOT NULL,
  `head_id` int NOT NULL,
  `description` text,
  `debit` decimal(15,2) DEFAULT '0.00',
  `credit` decimal(15,2) DEFAULT '0.00',
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_ledger`
--

INSERT INTO `expense_ledger` (`id`, `date`, `expense_id`, `head_id`, `description`, `debit`, `credit`, `balance`, `created_at`) VALUES
(1, '2026-06-09', 1, 1, 'Expense: Electricity Bill', 10000.00, 0.00, 10000.00, '2026-06-09 05:38:28');

-- --------------------------------------------------------

--
-- Table structure for table `hold_sales_details`
--

CREATE TABLE `hold_sales_details` (
  `id` int NOT NULL,
  `hold_id` int NOT NULL,
  `product_id` int NOT NULL,
  `client_height` decimal(10,2) DEFAULT '0.00',
  `client_width` decimal(10,2) DEFAULT '0.00',
  `multiple_of` int DEFAULT '6',
  `std_height` varchar(50) DEFAULT NULL,
  `std_width` varchar(50) DEFAULT NULL,
  `uom` varchar(20) DEFAULT 'Inch',
  `quantity` decimal(15,2) NOT NULL,
  `area` decimal(15,2) DEFAULT '0.00',
  `rate` decimal(15,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hold_sales_details`
--

INSERT INTO `hold_sales_details` (`id`, `hold_id`, `product_id`, `client_height`, `client_width`, `multiple_of`, `std_height`, `std_width`, `uom`, `quantity`, `area`, `rate`, `discount_percentage`, `amount`) VALUES
(1, 1, 3, 13.00, 15.00, 3, '15', '15', NULL, 5.00, 1.56, 400.00, NULL, 3125.00),
(2, 2, 3, 13.00, 15.00, 3, '15', '15', NULL, 5.00, 0.00, 400.00, NULL, 0.00),
(3, 3, 3, 7.00, 11.00, 3, '9', '12', NULL, 5.00, 0.75, 400.00, NULL, 1500.00);

-- --------------------------------------------------------

--
-- Table structure for table `hold_sales_master`
--

CREATE TABLE `hold_sales_master` (
  `id` int NOT NULL,
  `hold_no` varchar(50) NOT NULL,
  `hold_date` date NOT NULL,
  `customer_id` int NOT NULL,
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `other_charges` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `remarks` text,
  `status` enum('hold','converted','cancelled') DEFAULT 'hold',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hold_sales_master`
--

INSERT INTO `hold_sales_master` (`id`, `hold_no`, `hold_date`, `customer_id`, `subtotal`, `discount_percentage`, `discount_amount`, `other_charges`, `grand_total`, `remarks`, `status`, `created_by`, `created_at`) VALUES
(3, 'HOLD-00001', '2026-06-15', 14, 1500.00, 0.00, 0.00, 0.00, 1500.00, '', 'hold', 1, '2026-06-15 10:07:10');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_ledger`
--

CREATE TABLE `inventory_ledger` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `product_id` int NOT NULL,
  `reference_type` enum('OPENING','PURCHASE','SALE','ADJUSTMENT') NOT NULL,
  `reference_id` int NOT NULL,
  `qty_in` decimal(15,2) DEFAULT '0.00',
  `qty_out` decimal(15,2) DEFAULT '0.00',
  `balance_qty` decimal(15,2) DEFAULT '0.00',
  `unit_price` decimal(15,2) DEFAULT '0.00',
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_ledger`
--

INSERT INTO `inventory_ledger` (`id`, `date`, `product_id`, `reference_type`, `reference_id`, `qty_in`, `qty_out`, `balance_qty`, `unit_price`, `total_amount`, `remarks`, `created_at`) VALUES
(1, '2026-06-08', 1, 'OPENING', 1, 1255.00, 0.00, 1255.00, 1500.00, 1882500.00, 'Opening Stock Entry', '2026-06-08 11:21:52'),
(2, '2026-06-09', 1, 'SALE', 1, 0.00, 5.00, 1250.00, 2000.00, 80000.00, 'Sale Invoice: SAL-00001', '2026-06-09 05:50:33'),
(3, '2026-06-10', 1, 'SALE', 2, 0.00, 3.00, 1247.00, 2100.00, 4252.50, 'Sale Invoice: SAL-00002', '2026-06-10 12:33:21'),
(4, '2026-06-10', 1, 'SALE', 2, 0.00, 4.00, 1243.00, 2100.00, 15750.00, 'Sale Invoice: SAL-00002', '2026-06-10 12:33:21'),
(5, '2026-06-10', 1, 'SALE', 3, 0.00, 5.00, 1238.00, 2000.00, 6750.00, 'Sale Invoice: SAL-00003', '2026-06-10 12:50:36'),
(6, '2026-06-10', 1, 'SALE', 3, 0.00, 2.00, 1236.00, 2000.00, 6300.00, 'Sale Invoice: SAL-00003', '2026-06-10 12:50:36'),
(7, '2026-06-10', 2, 'OPENING', 2, 1500.00, 0.00, 1500.00, 2500.00, 3750000.00, 'Opening Stock Entry', '2026-06-10 13:31:26'),
(8, '2026-06-10', 1, 'SALE', 4, 0.00, 2.00, 1234.00, 2000.00, 15750.00, 'Sale Invoice: SAL-00004', '2026-06-10 13:32:46'),
(9, '2026-06-10', 1, 'SALE', 4, 0.00, 1.00, 1233.00, 2000.00, 35875.00, 'Sale Invoice: SAL-00004', '2026-06-10 13:32:46'),
(10, '2026-06-10', 2, 'SALE', 4, 0.00, 2.00, 1498.00, 3000.00, 3375.00, 'Sale Invoice: SAL-00004', '2026-06-10 13:32:46'),
(11, '2026-06-10', 2, 'SALE', 4, 0.00, 1.00, 1497.00, 3000.00, 5250.00, 'Sale Invoice: SAL-00004', '2026-06-10 13:32:46'),
(12, '2026-06-11', 1, 'PURCHASE', 1, 1.00, 0.00, 1234.00, 1500.00, 9375.00, 'Purchase Invoice: PUR-00001', '2026-06-11 06:43:30'),
(13, '2026-06-12', 1, 'SALE', 5, 0.00, 3.00, 1231.00, 1950.00, 4387.50, 'Sale Invoice: SAL-00005', '2026-06-12 06:09:12'),
(14, '2026-06-12', 1, 'SALE', 5, 0.00, 1.00, 1230.00, 1950.00, 1218.75, 'Sale Invoice: SAL-00005', '2026-06-12 06:09:12'),
(15, '2026-06-12', 2, 'SALE', 5, 0.00, 1.00, 1496.00, 3000.00, 2812.50, 'Sale Invoice: SAL-00005', '2026-06-12 06:09:12'),
(16, '2026-06-12', 2, 'SALE', 5, 0.00, 1.00, 1495.00, 3000.00, 7875.00, 'Sale Invoice: SAL-00005', '2026-06-12 06:09:12'),
(17, '2026-06-12', 1, 'SALE', 6, 0.00, 2.00, 1228.00, 2000.00, 8750.00, 'Sale Invoice: SAL-00006', '2026-06-12 06:13:22'),
(18, '2026-06-12', 1, 'SALE', 6, 0.00, 1.00, 1227.00, 2000.00, 14500.00, 'Sale Invoice: SAL-00006', '2026-06-12 06:13:22'),
(19, '2026-06-12', 3, 'OPENING', 3, 433890.00, 0.00, 433890.00, 350.00, 151861500.00, 'Opening Stock Entry - Opening Stock: 1000 pieces, each 433.89 sq ft, total 433890 sq ft', '2026-06-12 09:40:52'),
(20, '2026-06-12', 3, 'SALE', 7, 0.00, 343.75, 433546.25, 400.00, 137500.00, 'Sale Invoice: SAL-00007 - Total Area: 343.75 sq ft', '2026-06-12 09:44:50'),
(21, '2026-06-12', 3, 'PURCHASE', 2, 0.50, 0.00, 433546.75, 350.00, 175.00, 'Purchase Invoice: PUR-00002', '2026-06-12 09:50:55'),
(22, '2026-06-15', 3, 'SALE', 8, 0.00, 39.00, 433507.75, 400.00, 15600.00, 'Sale Invoice: SAL-00008 - Total Area: 39 sq ft', '2026-06-15 07:43:44'),
(23, '2026-06-15', 3, 'SALE', 8, 0.00, 28.00, 433479.75, 400.00, 11200.00, 'Sale Invoice: SAL-00008 - Total Area: 28 sq ft', '2026-06-15 07:43:44'),
(24, '2026-06-15', 3, 'SALE', 9, 0.00, 21.75, 433458.00, 400.00, 8700.00, 'Sale Invoice: SAL-00009 - Total Area: 21.75 sq ft', '2026-06-15 15:10:33'),
(25, '2026-06-15', 3, 'SALE', 9, 0.00, 0.01, 433457.99, 400.00, 2.78, 'Sale Invoice: SAL-00009 - Total Area: 0.0069444444444444 sq ft', '2026-06-15 15:10:33'),
(26, '2026-06-15', 3, 'SALE', 9, 0.00, 0.00, 433457.99, 400.00, 0.00, 'Sale Invoice: SAL-00009 - Total Area: 0 sq ft', '2026-06-15 15:10:33'),
(27, '2026-06-15', 3, 'SALE', 9, 0.00, 0.00, 433457.99, 400.00, 0.00, 'Sale Invoice: SAL-00009 - Total Area: 0 sq ft', '2026-06-15 15:10:33'),
(28, '2026-06-15', 1, 'SALE', 9, 0.00, 19.50, 1207.50, 2000.00, 39000.00, 'Sale Invoice: SAL-00009 - Total Area: 19.5 sq ft', '2026-06-15 15:10:33');

-- --------------------------------------------------------

--
-- Table structure for table `opening_stock`
--

CREATE TABLE `opening_stock` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `date` date NOT NULL,
  `remarks` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `opening_stock`
--

INSERT INTO `opening_stock` (`id`, `product_id`, `quantity`, `unit_price`, `total_amount`, `date`, `remarks`, `created_by`, `created_at`) VALUES
(1, 1, 1255.00, 1500.00, 1882500.00, '2026-06-08', NULL, 1, '2026-06-08 11:21:52'),
(2, 2, 1500.00, 2500.00, 3750000.00, '2026-06-10', NULL, 1, '2026-06-10 13:31:26'),
(3, 3, 433890.00, 350.00, 151861500.00, '2026-06-12', 'Opening Stock: 1000 pieces, each 433.89 sq ft, total 433890 sq ft', 1, '2026-06-12 09:40:52');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `category_id` int NOT NULL,
  `company_id` int NOT NULL,
  `unit_id` int NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT '0.00',
  `sale_price` decimal(15,2) DEFAULT '0.00',
  `min_stock_alert` int DEFAULT '0',
  `location_rack` varchar(100) DEFAULT NULL,
  `length_inch` decimal(10,2) DEFAULT '0.00',
  `width_inch` decimal(10,2) DEFAULT '0.00',
  `length_feet` decimal(10,2) DEFAULT '0.00',
  `width_feet` decimal(10,2) DEFAULT '0.00',
  `area_sqft` decimal(15,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_code`, `product_name`, `category_id`, `company_id`, `unit_id`, `supplier_id`, `purchase_price`, `sale_price`, `min_stock_alert`, `location_rack`, `length_inch`, `width_inch`, `length_feet`, `width_feet`, `area_sqft`, `status`, `created_at`, `updated_at`) VALUES
(1, 'FG0001', '6mm Clear Glass', 1, 1, 1, NULL, 1500.00, 2000.00, 150, 'Rack1', 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-06-08 11:21:52', '2026-06-08 11:21:52'),
(2, 'FG0002', '9mm glass', 1, 1, 1, 1, 2500.00, 3000.00, 150, 'Rack2', 2499.96, 2499.96, 208.33, 208.33, 43401.39, 1, '2026-06-10 13:31:26', '2026-06-10 13:31:26'),
(3, 'FG0003', '12mm clear glass', 1, 1, 1, 1, 350.00, 400.00, 100, 'Rack3', 249.96, 249.96, 20.83, 20.83, 433.89, 1, '2026-06-12 09:40:52', '2026-06-12 09:40:52');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_details`
--

CREATE TABLE `purchase_details` (
  `id` int NOT NULL,
  `purchase_id` int NOT NULL,
  `product_id` int NOT NULL,
  `client_height` decimal(10,2) DEFAULT '0.00',
  `client_width` decimal(10,2) DEFAULT '0.00',
  `std_height` varchar(50) DEFAULT NULL,
  `std_width` varchar(50) DEFAULT NULL,
  `uom` varchar(20) DEFAULT 'Inch',
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `retail_price` decimal(15,2) DEFAULT '0.00',
  `area` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `refunded_qty` decimal(15,2) DEFAULT '0.00',
  `refunded_amount` decimal(15,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_details`
--

INSERT INTO `purchase_details` (`id`, `purchase_id`, `product_id`, `client_height`, `client_width`, `std_height`, `std_width`, `uom`, `quantity`, `unit_price`, `retail_price`, `area`, `amount`, `discount_percentage`, `discount_amount`, `net_amount`, `refunded_qty`, `refunded_amount`) VALUES
(1, 1, 1, 25.00, 25.00, '30', '30', 'Inch', 1.00, 1500.00, 0.00, 6.25, 9375.00, 0.00, 0.00, 9375.00, 0.00, 0.00),
(2, 2, 3, 3.00, 7.00, '6', '12', '', 1.00, 350.00, 0.00, 0.50, 175.00, 0.00, 0.00, 175.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_master`
--

CREATE TABLE `purchase_master` (
  `id` int NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `purchase_date` date NOT NULL,
  `supplier_id` int NOT NULL,
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `other_charges` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `paid_amount` decimal(15,2) DEFAULT '0.00',
  `remaining_amount` decimal(15,2) DEFAULT '0.00',
  `payment_type` enum('cash','bank','credit') DEFAULT 'credit',
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` text,
  `status` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `refund_status` enum('none','partial','full') DEFAULT 'none',
  `refund_amount` decimal(15,2) DEFAULT '0.00',
  `refund_date` date DEFAULT NULL,
  `refund_reason` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_master`
--

INSERT INTO `purchase_master` (`id`, `invoice_no`, `purchase_date`, `supplier_id`, `subtotal`, `discount_amount`, `discount_percentage`, `other_charges`, `grand_total`, `paid_amount`, `remaining_amount`, `payment_type`, `bank_account_id`, `reference_no`, `remarks`, `status`, `created_by`, `created_at`, `refund_status`, `refund_amount`, `refund_date`, `refund_reason`) VALUES
(1, 'PUR-00001', '2026-06-11', 1, 9375.00, 0.00, 0.00, 0.00, 9375.00, 0.00, 9375.00, 'credit', 0, '', '', 1, 1, '2026-06-11 06:43:30', 'none', 0.00, NULL, NULL),
(2, 'PUR-00002', '2026-06-12', 1, 175.00, 0.00, 0.00, 0.00, 175.00, 0.00, 175.00, 'credit', 0, '', '', 1, 1, '2026-06-12 09:50:55', 'none', 0.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `quotation_details`
--

CREATE TABLE `quotation_details` (
  `id` int NOT NULL,
  `quotation_id` int NOT NULL,
  `product_id` int NOT NULL,
  `client_height` decimal(10,2) DEFAULT '0.00',
  `client_width` decimal(10,2) DEFAULT '0.00',
  `std_height` varchar(50) DEFAULT NULL,
  `std_width` varchar(50) DEFAULT NULL,
  `uom` varchar(20) DEFAULT 'Inch',
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `area` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotation_details`
--

INSERT INTO `quotation_details` (`id`, `quotation_id`, `product_id`, `client_height`, `client_width`, `std_height`, `std_width`, `uom`, `quantity`, `unit_price`, `area`, `amount`, `discount_percentage`, `discount_amount`, `net_amount`) VALUES
(1, 1, 1, 25.00, 25.00, '27', '27', '', 5.00, 2000.00, 5.06, 50600.00, 0.00, 0.00, 50600.00),
(2, 5, 1, 7.00, 9.00, '7', '9', 'Inch', 10.00, 2000.00, 0.44, 8800.00, 0.00, 0.00, 8800.00),
(3, 6, 3, 141.00, 168.00, '141', '168', 'Inch', 5.00, 400.00, 164.50, 329000.00, 0.00, 0.00, 329000.00);

-- --------------------------------------------------------

--
-- Table structure for table `quotation_master`
--

CREATE TABLE `quotation_master` (
  `id` int NOT NULL,
  `quotation_no` varchar(50) NOT NULL,
  `quotation_date` date NOT NULL,
  `customer_id` int NOT NULL,
  `valid_until` date DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `other_charges` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` text,
  `status` enum('draft','hold','pending','approved','rejected','converted') DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotation_master`
--

INSERT INTO `quotation_master` (`id`, `quotation_no`, `quotation_date`, `customer_id`, `valid_until`, `subtotal`, `discount_percentage`, `discount_amount`, `other_charges`, `grand_total`, `reference_no`, `remarks`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'QTN-00001', '2026-06-12', 10, '2026-07-12', 50600.00, 0.00, 0.00, 0.00, 50600.00, '', '', 'draft', 1, '2026-06-12 07:35:24', '2026-06-12 07:35:24'),
(5, 'QTN-00002', '2026-06-12', 11, '2026-07-12', 8800.00, 0.00, 0.00, 0.00, 8800.00, '', '', 'draft', 1, '2026-06-12 07:42:18', '2026-06-12 07:42:18'),
(6, 'QTN-00003', '2026-06-16', 15, '2026-07-16', 329000.00, 0.00, 0.00, 0.00, 329000.00, 'das543', '', 'hold', 1, '2026-06-16 06:48:58', '2026-06-16 06:48:58');

-- --------------------------------------------------------

--
-- Table structure for table `sale_details`
--

CREATE TABLE `sale_details` (
  `id` int NOT NULL,
  `sale_id` int NOT NULL,
  `product_id` int NOT NULL,
  `client_height` decimal(10,2) DEFAULT '0.00',
  `client_width` decimal(10,2) DEFAULT '0.00',
  `client_size` varchar(100) DEFAULT NULL,
  `multiple_of` int DEFAULT '6',
  `std_height` varchar(50) DEFAULT NULL,
  `std_width` varchar(50) DEFAULT NULL,
  `uom` varchar(20) DEFAULT 'Inch',
  `quantity` decimal(15,2) NOT NULL,
  `area` decimal(15,2) DEFAULT '0.00',
  `raw_area` decimal(15,2) DEFAULT '0.00',
  `total_area` decimal(15,2) DEFAULT '0.00',
  `rate` decimal(15,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `refunded_qty` decimal(15,2) DEFAULT '0.00',
  `refunded_amount` decimal(15,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_details`
--

INSERT INTO `sale_details` (`id`, `sale_id`, `product_id`, `client_height`, `client_width`, `client_size`, `multiple_of`, `std_height`, `std_width`, `uom`, `quantity`, `area`, `raw_area`, `total_area`, `rate`, `discount_percentage`, `amount`, `refunded_qty`, `refunded_amount`) VALUES
(1, 1, 1, 24.00, 48.00, '24 x 48', 6, '24', '48', 'Inch', 5.00, 8.00, 0.00, 0.00, 2000.00, 0.00, 80000.00, 0.00, 0.00),
(2, 2, 1, 7.00, 11.00, '7 x 11', 3, '9', '12', 'Inch', 3.00, 2.25, 0.00, 0.00, 2100.00, 10.00, 4252.50, 0.00, 0.00),
(3, 2, 1, 13.00, 17.00, '13 x 17', 3, '15', '18', 'Inch', 4.00, 7.50, 0.00, 0.00, 2100.00, 0.00, 15750.00, 0.00, 0.00),
(4, 3, 1, 7.00, 11.00, '7 x 11', 3, '9', '12', '', 5.00, 3.75, 0.00, 0.00, 2000.00, 10.00, 6750.00, 0.00, 0.00),
(5, 3, 1, 11.00, 19.00, '11 x 19', 3, '12', '21', '', 2.00, 3.50, 0.00, 0.00, 2000.00, 10.00, 6300.00, 0.00, 0.00),
(6, 4, 1, 25.00, 19.00, '25 x 19', 3, '27', '21', '', 2.00, 7.88, 0.00, 0.00, 2000.00, 0.00, 15750.00, 0.00, 0.00),
(7, 4, 1, 19.00, 122.00, '19 x 122', 3, '21', '123', '', 1.00, 17.94, 0.00, 0.00, 2000.00, 0.00, 35875.00, 0.00, 0.00),
(8, 4, 2, 7.00, 9.00, '7 x 9', 3, '9', '9', '', 2.00, 1.13, 0.00, 0.00, 3000.00, 0.00, 3375.00, 0.00, 0.00),
(9, 4, 2, 11.00, 19.00, '11 x 19', 3, '12', '21', '', 1.00, 1.75, 0.00, 0.00, 3000.00, 0.00, 5250.00, 0.00, 0.00),
(10, 5, 1, 9.00, 11.00, '9 x 11', 3, '9', '12', '', 3.00, 2.25, 0.00, 0.00, 1950.00, 0.00, 4387.50, 0.00, 0.00),
(11, 5, 1, 6.00, 13.00, '6 x 13', 3, '6', '15', '', 1.00, 0.63, 0.00, 0.00, 1950.00, 0.00, 1218.75, 0.00, 0.00),
(12, 5, 2, 9.00, 14.00, '9 x 14', 3, '9', '15', '', 1.00, 0.94, 0.00, 0.00, 3000.00, 0.00, 2812.50, 0.00, 0.00),
(13, 5, 2, 18.00, 21.00, '18 x 21', 3, '18', '21', '', 1.00, 2.63, 0.00, 0.00, 3000.00, 0.00, 7875.00, 0.00, 0.00),
(14, 6, 1, 15.00, 19.00, '15 x 19', 3, '15', '21', '', 2.00, 4.38, 0.00, 0.00, 2000.00, 0.00, 8750.00, 0.00, 0.00),
(15, 6, 1, 11.00, 85.00, '11 x 85', 3, '12', '87', '', 1.00, 7.25, 0.00, 0.00, 2000.00, 0.00, 14500.00, 0.00, 0.00),
(16, 7, 3, 150.00, 165.00, '150 x 165', 3, '150', '165', '', 2.00, 343.75, 0.00, 0.00, 400.00, 0.00, 137500.00, 0.00, 0.00),
(17, 8, 3, 34.00, 78.00, '034 x 078', 6, '36', '78', '', 2.00, 39.00, 0.00, 0.00, 400.00, 0.00, 15600.00, 0.00, 0.00),
(18, 8, 3, 37.00, 47.00, '037 x 47', 6, '42', '48', '', 2.00, 28.00, 0.00, 0.00, 400.00, 0.00, 11200.00, 0.00, 0.00),
(19, 9, 3, 34.00, 85.00, '34 x 85', 3, '36', '87', '', 1.00, 21.75, 0.00, 0.00, 400.00, 0.00, 8700.00, 0.00, 0.00),
(20, 9, 3, 0.00, 0.00, '0 x 0', 6, '1', '1', '', 1.00, 0.01, 0.00, 0.00, 400.00, 0.00, 2.78, 0.00, 0.00),
(21, 9, 3, 0.00, 0.00, '0 x 0', 6, '0', '0', '', 1.00, 0.00, 0.00, 0.00, 400.00, 0.00, 0.00, 0.00, 0.00),
(22, 9, 3, 0.00, 0.00, '0 x 0', 6, '0', '0', '', 1.00, 0.00, 0.00, 0.00, 400.00, 0.00, 0.00, 0.00, 0.00),
(23, 9, 1, 34.00, 78.00, '34 x 78', 6, '36', '78', '', 1.00, 19.50, 0.00, 0.00, 2000.00, 0.00, 39000.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `sale_master`
--

CREATE TABLE `sale_master` (
  `id` int NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `sale_date` date NOT NULL,
  `customer_id` int NOT NULL,
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `other_charges` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `received_amount` decimal(15,2) DEFAULT '0.00',
  `remaining_amount` decimal(15,2) DEFAULT '0.00',
  `payment_type` enum('cash','bank','credit','partial') DEFAULT 'cash',
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` text,
  `status` tinyint(1) DEFAULT '1',
  `refund_status` enum('none','partial','full') DEFAULT 'none',
  `refund_amount` decimal(15,2) DEFAULT '0.00',
  `refund_date` date DEFAULT NULL,
  `refund_reason` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_master`
--

INSERT INTO `sale_master` (`id`, `invoice_no`, `sale_date`, `customer_id`, `subtotal`, `discount_percentage`, `discount_amount`, `other_charges`, `grand_total`, `received_amount`, `remaining_amount`, `payment_type`, `bank_account_id`, `reference_no`, `remarks`, `status`, `refund_status`, `refund_amount`, `refund_date`, `refund_reason`, `created_by`, `created_at`) VALUES
(1, 'SAL-00001', '2026-06-09', 1, 80000.00, 0.00, 0.00, 0.00, 80000.00, 80000.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-09 05:50:33'),
(2, 'SAL-00002', '2026-06-10', 1, 20002.50, 0.00, 0.00, 100.00, 20102.50, 20102.50, 0.00, 'cash', 0, '0001', 'ok', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-10 12:33:21'),
(3, 'SAL-00003', '2026-06-10', 5, 13050.00, 0.00, 0.00, 200.00, 13250.00, 13250.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-10 12:50:36'),
(4, 'SAL-00004', '2026-06-10', 6, 60250.00, 0.00, 0.00, 0.00, 60250.00, 60250.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-10 13:32:46'),
(5, 'SAL-00005', '2026-06-12', 8, 16293.75, 0.00, 0.00, 2000.00, 18293.75, 18293.75, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-12 06:09:12'),
(6, 'SAL-00006', '2026-06-12', 9, 23250.00, 0.00, 0.00, 2000.00, 25250.00, 0.00, 25250.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-12 06:13:22'),
(7, 'SAL-00007', '2026-06-12', 12, 137500.00, 0.00, 0.00, 0.00, 137500.00, 137500.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-12 09:44:50'),
(8, 'SAL-00008', '2026-06-15', 7, 26800.00, 0.00, 0.00, 0.00, 26800.00, 26800.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-15 07:43:44'),
(9, 'SAL-00009', '2026-06-15', 9, 47702.78, 0.00, 0.00, 9000.00, 56702.78, 0.00, 56702.78, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-15 15:10:33');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int NOT NULL,
  `supplier_code` varchar(50) NOT NULL,
  `supplier_name` varchar(200) NOT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `mobile` varchar(20) NOT NULL,
  `cnic` varchar(20) DEFAULT NULL,
  `ntn` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `opening_balance` decimal(15,2) DEFAULT '0.00',
  `balance_type` enum('payable','receivable') DEFAULT 'payable' COMMENT 'payable=Company owes supplier, receivable=Supplier owes company',
  `current_balance` decimal(15,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `supplier_code`, `supplier_name`, `company_name`, `contact_person`, `mobile`, `cnic`, `ntn`, `email`, `address`, `opening_balance`, `balance_type`, `current_balance`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'SUP-0001', 'Vimbsol', 'Vimbsol', 'Talha Arshad', '03217917178', '', '', 'talhaarshadatr@gmail.com', 'P/O raja wala chak No. 22/10r kacha khuh', 0.00, 'payable', 9550.00, 1, '', '2026-06-10 13:30:33', '2026-06-12 09:50:55');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_ledger`
--

CREATE TABLE `supplier_ledger` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `supplier_id` int NOT NULL,
  `reference_type` enum('OPENING','PURCHASE','PAYMENT','ADJUSTMENT') NOT NULL,
  `reference_id` int NOT NULL,
  `description` text,
  `debit` decimal(15,2) DEFAULT '0.00' COMMENT 'Supplier owes company',
  `credit` decimal(15,2) DEFAULT '0.00' COMMENT 'Company owes supplier',
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_ledger`
--

INSERT INTO `supplier_ledger` (`id`, `date`, `supplier_id`, `reference_type`, `reference_id`, `description`, `debit`, `credit`, `balance`, `created_at`) VALUES
(1, '2026-06-11', 1, 'PURCHASE', 1, 'Purchase Invoice: PUR-00001', 0.00, 9375.00, 9375.00, '2026-06-11 06:43:30'),
(2, '2026-06-12', 1, 'PURCHASE', 2, 'Purchase Invoice: PUR-00002', 0.00, 175.00, 9550.00, '2026-06-12 09:50:55');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payments`
--

CREATE TABLE `supplier_payments` (
  `id` int NOT NULL,
  `payment_date` date NOT NULL,
  `supplier_id` int NOT NULL,
  `payment_method` enum('cash','bank') NOT NULL,
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `remarks` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int NOT NULL,
  `unit_name` varchar(50) NOT NULL,
  `short_name` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `unit_name`, `short_name`, `created_at`) VALUES
(1, 'Meter', 'MD', '2026-06-08 11:20:40'),
(2, 'Square Feet', 'SQ FT', '2026-06-09 05:36:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Plain text password as per requirements',
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','manager','cashier') DEFAULT 'cashier',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`, `last_login`, `created_at`) VALUES
(1, 'admin', 'admin123', 'Administrator', 'admin@faysalglass.com', 'admin', 1, '2026-06-16 08:47:08', '2026-06-05 11:34:23'),
(2, 'manager', 'manager123', 'Store Manager', 'manager@faysalglass.com', 'manager', 1, NULL, '2026-06-05 11:34:23'),
(3, 'cashier', 'cashier123', 'Cashier User', 'cashier@faysalglass.com', 'cashier', 1, NULL, '2026-06-05 11:34:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_transfers`
--
ALTER TABLE `account_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`transfer_date`),
  ADD KEY `idx_from_account` (`from_account_type`,`from_account_id`),
  ADD KEY `idx_to_account` (`to_account_type`,`to_account_id`);

--
-- Indexes for table `bank1_transactions`
--
ALTER TABLE `bank1_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `bank2_transactions`
--
ALTER TABLE `bank2_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `bank3_transactions`
--
ALTER TABLE `bank3_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bank_book`
--
ALTER TABLE `bank_book`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cash_book`
--
ALTER TABLE `cash_book`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name_unique` (`category_name`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_name_unique` (`company_name`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_code_unique` (`customer_code`),
  ADD UNIQUE KEY `customer_name_unique` (`customer_name`),
  ADD KEY `idx_mobile` (`mobile`);

--
-- Indexes for table `customer_ledger`
--
ALTER TABLE `customer_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `customer_payments`
--
ALTER TABLE `customer_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_payment_date` (`payment_date`);

--
-- Indexes for table `customer_receipts`
--
ALTER TABLE `customer_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_receipt_date` (`receipt_date`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_code_unique` (`employee_code`),
  ADD UNIQUE KEY `employee_name_unique` (`employee_name`);

--
-- Indexes for table `employee_ledger`
--
ALTER TABLE `employee_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_payments`
--
ALTER TABLE `employee_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_salary`
--
ALTER TABLE `employee_salary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expense_date` (`expense_date`),
  ADD KEY `idx_head_id` (`head_id`),
  ADD KEY `idx_payment_method` (`payment_method`);

--
-- Indexes for table `expense_heads`
--
ALTER TABLE `expense_heads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `head_name_unique` (`head_name`);

--
-- Indexes for table `expense_ledger`
--
ALTER TABLE `expense_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expense_id` (`expense_id`),
  ADD KEY `idx_head_id` (`head_id`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `hold_sales_details`
--
ALTER TABLE `hold_sales_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hold_id` (`hold_id`);

--
-- Indexes for table `hold_sales_master`
--
ALTER TABLE `hold_sales_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hold_no_unique` (`hold_no`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `inventory_ledger`
--
ALTER TABLE `inventory_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `opening_stock`
--
ALTER TABLE `opening_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code_unique` (`product_code`),
  ADD UNIQUE KEY `product_name_unique` (`product_name`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `products_ibfk_4` (`supplier_id`);

--
-- Indexes for table `purchase_details`
--
ALTER TABLE `purchase_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_purchase_id` (`purchase_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `purchase_master`
--
ALTER TABLE `purchase_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no_unique` (`invoice_no`),
  ADD KEY `idx_supplier_id` (`supplier_id`),
  ADD KEY `idx_purchase_date` (`purchase_date`);

--
-- Indexes for table `quotation_details`
--
ALTER TABLE `quotation_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quotation_id` (`quotation_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `quotation_master`
--
ALTER TABLE `quotation_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quotation_no_unique` (`quotation_no`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_quotation_date` (`quotation_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `sale_details`
--
ALTER TABLE `sale_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sale_id` (`sale_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `sale_master`
--
ALTER TABLE `sale_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no_unique` (`invoice_no`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_sale_date` (`sale_date`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `supplier_code_unique` (`supplier_code`),
  ADD UNIQUE KEY `supplier_name_unique` (`supplier_name`),
  ADD KEY `idx_mobile` (`mobile`);

--
-- Indexes for table `supplier_ledger`
--
ALTER TABLE `supplier_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_supplier_id` (`supplier_id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_supplier_id` (`supplier_id`),
  ADD KEY `idx_payment_date` (`payment_date`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unit_name_unique` (`unit_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username_unique` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_transfers`
--
ALTER TABLE `account_transfers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank1_transactions`
--
ALTER TABLE `bank1_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank2_transactions`
--
ALTER TABLE `bank2_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank3_transactions`
--
ALTER TABLE `bank3_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_book`
--
ALTER TABLE `bank_book`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_book`
--
ALTER TABLE `cash_book`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `customer_ledger`
--
ALTER TABLE `customer_ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customer_payments`
--
ALTER TABLE `customer_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_receipts`
--
ALTER TABLE `customer_receipts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_ledger`
--
ALTER TABLE `employee_ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_payments`
--
ALTER TABLE `employee_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_salary`
--
ALTER TABLE `employee_salary`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expense_heads`
--
ALTER TABLE `expense_heads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expense_ledger`
--
ALTER TABLE `expense_ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hold_sales_details`
--
ALTER TABLE `hold_sales_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hold_sales_master`
--
ALTER TABLE `hold_sales_master`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inventory_ledger`
--
ALTER TABLE `inventory_ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `opening_stock`
--
ALTER TABLE `opening_stock`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `purchase_details`
--
ALTER TABLE `purchase_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchase_master`
--
ALTER TABLE `purchase_master`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quotation_details`
--
ALTER TABLE `quotation_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `quotation_master`
--
ALTER TABLE `quotation_master`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sale_details`
--
ALTER TABLE `sale_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `sale_master`
--
ALTER TABLE `sale_master`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supplier_ledger`
--
ALTER TABLE `supplier_ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customer_ledger`
--
ALTER TABLE `customer_ledger`
  ADD CONSTRAINT `customer_ledger_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `customer_payments`
--
ALTER TABLE `customer_payments`
  ADD CONSTRAINT `customer_payments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `customer_receipts`
--
ALTER TABLE `customer_receipts`
  ADD CONSTRAINT `customer_receipts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `employee_ledger`
--
ALTER TABLE `employee_ledger`
  ADD CONSTRAINT `employee_ledger_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `employee_payments`
--
ALTER TABLE `employee_payments`
  ADD CONSTRAINT `employee_payments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `employee_salary`
--
ALTER TABLE `employee_salary`
  ADD CONSTRAINT `employee_salary_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`head_id`) REFERENCES `expense_heads` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `expense_ledger`
--
ALTER TABLE `expense_ledger`
  ADD CONSTRAINT `expense_ledger_ibfk_1` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `expense_ledger_ibfk_2` FOREIGN KEY (`head_id`) REFERENCES `expense_heads` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `inventory_ledger`
--
ALTER TABLE `inventory_ledger`
  ADD CONSTRAINT `inventory_ledger_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `opening_stock`
--
ALTER TABLE `opening_stock`
  ADD CONSTRAINT `opening_stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `products_ibfk_4` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `purchase_master`
--
ALTER TABLE `purchase_master`
  ADD CONSTRAINT `purchase_master_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `quotation_details`
--
ALTER TABLE `quotation_details`
  ADD CONSTRAINT `quotation_details_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotation_master` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quotation_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `quotation_master`
--
ALTER TABLE `quotation_master`
  ADD CONSTRAINT `quotation_master_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `sale_master`
--
ALTER TABLE `sale_master`
  ADD CONSTRAINT `sale_master_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `supplier_ledger`
--
ALTER TABLE `supplier_ledger`
  ADD CONSTRAINT `supplier_ledger_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD CONSTRAINT `supplier_payments_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
