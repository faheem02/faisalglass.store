-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 03, 2026 at 12:36 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `faysal_glass`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_transfers`
--

CREATE TABLE `account_transfers` (
  `id` int(11) NOT NULL,
  `transfer_date` date NOT NULL,
  `from_account_type` enum('cash','bank1','bank2','bank3') NOT NULL,
  `from_account_id` int(11) DEFAULT NULL,
  `to_account_type` enum('cash','bank1','bank2','bank3') NOT NULL,
  `to_account_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_transfers`
--

INSERT INTO `account_transfers` (`id`, `transfer_date`, `from_account_type`, `from_account_id`, `to_account_type`, `to_account_id`, `amount`, `reference_no`, `remarks`, `created_by`, `created_at`) VALUES
(1, '2026-07-26', 'cash', 0, '', 1, 704621.00, '79755768', 'Transfer: ', 1, '2026-07-26 18:50:23');

-- --------------------------------------------------------

--
-- Table structure for table `bank1_transactions`
--

CREATE TABLE `bank1_transactions` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `reference_type` enum('OPENING','SALE','PURCHASE','CUSTOMER_PAYMENT','SUPPLIER_RECEIPT','WITHDRAW','TRANSFER_IN','TRANSFER_OUT','ADJUSTMENT') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0.00 COMMENT 'Deposit',
  `credit` decimal(15,2) DEFAULT 0.00 COMMENT 'Withdrawal',
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank2_transactions`
--

CREATE TABLE `bank2_transactions` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `reference_type` enum('OPENING','SALE','PURCHASE','CUSTOMER_PAYMENT','SUPPLIER_RECEIPT','WITHDRAW','TRANSFER_IN','TRANSFER_OUT','ADJUSTMENT') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0.00 COMMENT 'Deposit',
  `credit` decimal(15,2) DEFAULT 0.00 COMMENT 'Withdrawal',
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank3_transactions`
--

CREATE TABLE `bank3_transactions` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `reference_type` enum('OPENING','SALE','PURCHASE','CUSTOMER_PAYMENT','SUPPLIER_RECEIPT','WITHDRAW','TRANSFER_IN','TRANSFER_OUT','ADJUSTMENT') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0.00 COMMENT 'Deposit',
  `credit` decimal(15,2) DEFAULT 0.00 COMMENT 'Withdrawal',
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int(11) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_title` varchar(200) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `branch_name` varchar(100) DEFAULT NULL,
  `opening_balance` decimal(15,2) DEFAULT 0.00,
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bank_accounts`
--

INSERT INTO `bank_accounts` (`id`, `bank_name`, `account_title`, `account_number`, `branch_name`, `opening_balance`, `current_balance`, `status`) VALUES
(1, 'Bank AL Habib', 'Faisal Glass & Aluminium Center', '01290081000893019', NULL, 500000.00, 1354121.00, 1),
(2, 'Faysal Bank', 'Faisal Glass & Aluminium Center', '3429301000002466', NULL, 500000.00, 500000.00, 1),
(3, 'UBL Bank', 'Faisal Glass & Aluminium Center', '2661362759574', NULL, 500000.00, 500000.00, 1),
(4, 'Raast / JazzCash / Easypaisa', 'Faisal Glass & Aluminium Center', '03228701098', NULL, 200000.00, 200000.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `bank_book`
--

CREATE TABLE `bank_book` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `bank_account_id` int(11) NOT NULL,
  `reference_type` varchar(50) NOT NULL,
  `reference_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bank_book`
--

INSERT INTO `bank_book` (`id`, `date`, `bank_account_id`, `reference_type`, `reference_id`, `description`, `debit`, `credit`, `balance`, `created_at`) VALUES
(1, '2026-06-17', 1, 'OPENING', 1, 'Opening Balance - Bank AL Habib', 500000.00, 0.00, 500000.00, '2026-06-17 11:38:08'),
(2, '2026-06-17', 2, 'OPENING', 2, 'Opening Balance - Faysal Bank', 500000.00, 0.00, 500000.00, '2026-06-17 11:38:08'),
(3, '2026-06-17', 3, 'OPENING', 3, 'Opening Balance - UBL Bank', 500000.00, 0.00, 500000.00, '2026-06-17 11:38:08'),
(4, '2026-06-17', 4, 'OPENING', 4, 'Opening Balance - Raast / JazzCash / Easypaisa', 200000.00, 0.00, 200000.00, '2026-06-17 11:38:08'),
(5, '2026-07-14', 1, 'customer_receipt', 2, 'Payment received from customer - ', 150000.00, 0.00, 650000.00, '2026-07-14 18:17:27'),
(6, '2026-07-26', 1, 'TRANSFER_IN', 31, 'Transfer from Cash - ', 704621.00, 0.00, 1354621.00, '2026-07-26 18:50:23'),
(7, '2026-09-01', 1, 'EMPLOYEE_PAYMENT', 3, 'Salary payment to: Faheem', 0.00, 500.00, 1354121.00, '2026-09-01 12:32:53'),
(8, '2026-09-02', 1, 'SUPPLIER_MANUAL', 31, 'Bank payment to Ghani glass (Manual Debit) - da', 0.00, 500.00, 1354121.00, '2026-09-02 09:42:01');

-- --------------------------------------------------------

--
-- Table structure for table `cash_book`
--

CREATE TABLE `cash_book` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `reference_type` varchar(50) NOT NULL,
  `reference_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
(8, '2026-06-15', 'SALE', 8, 'Sale Payment: SAL-00008', 26800.00, 0.00, 346196.25, '2026-06-15 07:43:44'),
(9, '2026-06-17', 'SALE', 12, 'Sale Payment: SAL-00011', 2250.00, 0.00, 348446.25, '2026-06-17 11:49:31'),
(10, '2026-06-17', 'SALE', 13, 'Sale Payment: SAL-00012', 3200.00, 0.00, 351646.25, '2026-06-17 11:50:51'),
(11, '2026-06-18', 'SALE', 14, 'Sale Payment: SAL-00013', 2250.00, 0.00, 353896.25, '2026-06-18 11:19:20'),
(13, '2026-06-24', 'SALE', 16, 'Sale Payment: SAL-00015', 25200.00, 0.00, 379096.25, '2026-06-24 14:47:38'),
(14, '2026-06-24', 'SALE', 17, 'Sale Payment: SAL-00016', 14400.00, 0.00, 393496.25, '2026-06-24 16:14:57'),
(15, '2026-06-24', 'SALE', 18, 'Sale Payment: SAL-00017', 52000.00, 0.00, 445496.25, '2026-06-24 16:17:31'),
(22, '2026-07-05', 'customer_receipt', 1, 'Payment received from customer - ', 50000.00, 0.00, 495499.03, '2026-07-05 14:36:49'),
(25, '2026-07-16', 'SALE', 29, 'Sale Payment: SAL-00025', 640.00, 0.00, 1381059.03, '2026-07-16 13:06:55'),
(26, '2026-07-16', 'SALE', 30, 'Sale Payment: SAL-00026', 640.00, 0.00, 1381699.03, '2026-07-16 13:09:11'),
(28, '2026-07-20', 'SALE', 32, 'Sale Payment: SAL-00028', 176400.00, 0.00, 1560499.03, '2026-07-20 18:10:41'),
(29, '2026-07-16', 'SALE', 31, 'Sale Payment: SAL-00027', 2400.00, 0.00, 1560499.03, '2026-07-20 18:12:19'),
(30, '2026-07-23', 'SALE', 33, 'Sale Payment: SAL-00029', 29045.00, 0.00, 1589544.03, '2026-07-23 06:36:06'),
(31, '2026-07-26', 'TRANSFER', 31, 'Transfer to Bank AL Habib - ', 0.00, 704621.00, 0.25, '2026-07-26 18:50:23'),
(32, '2026-07-26', 'SALE', 34, 'Sale Payment: SAL-00030', 19200.00, 0.00, 19200.25, '2026-07-26 18:52:44'),
(33, '2026-07-26', 'customer_receipt', 3, 'Payment received from customer - ', 5000.00, 0.00, 24200.25, '2026-07-26 18:57:59'),
(34, '2026-07-30', 'PURCHASE', 10, 'Purchase Payment: PUR-00010', 0.00, 20.00, 24180.25, '2026-07-30 11:05:58'),
(35, '2026-07-30', 'customer_receipt', 4, 'Payment received from customer - abc', 2000.00, 0.00, 26180.25, '2026-07-30 11:38:26'),
(36, '2026-07-30', 'customer_receipt', 5, 'Payment received from customer - ', 5000.00, 0.00, 31180.25, '2026-07-30 11:47:57'),
(37, '2026-07-30', 'SALE', 35, 'Sale Payment: SAL-00031', 19600.00, 0.00, 50780.25, '2026-07-30 11:51:12'),
(38, '2026-07-30', 'EMPLOYEE_PAYMENT', 1, 'Salary payment to: Faheem', 0.00, 452.00, 50328.25, '2026-07-30 12:49:08'),
(39, '2026-08-03', 'SUPPLIER_PAYMENT', 1, 'Payment to supplier: Vimbsol', 0.00, 500.00, 49828.25, '2026-08-03 10:41:59'),
(40, '2026-08-03', 'SUPPLIER_PAYMENT', 2, 'Payment to supplier: Vimbsol', 0.00, 750.00, 49078.25, '2026-08-03 10:49:27'),
(41, '2026-08-03', 'SUPPLIER_PAYMENT', 3, 'Payment to supplier: Vimbsol', 0.00, 50000.00, -921.75, '2026-08-03 10:51:07'),
(42, '2026-08-10', 'SUPPLIER_PAYMENT', 4, 'Payment to supplier: Vimbsol', 0.00, 5000.00, -5921.75, '2026-08-10 05:28:00'),
(44, '2026-08-25', 'customer_receipt', 6, 'Payment received from customer - ', 10.00, 0.00, -5811.75, '2026-08-25 06:12:15'),
(45, '2026-08-25', 'customer_receipt', 7, 'Payment received from customer - ', 500.00, 0.00, -5311.75, '2026-08-25 10:55:21'),
(46, '2026-08-25', 'SUPPLIER_PAYMENT', 5, 'Payment to supplier: Ghani glass', 0.00, 500.00, -5811.75, '2026-08-25 11:34:47'),
(47, '2026-08-12', 'SALE', 36, 'Sale Payment: SAL-00032', 100.00, 0.00, -5811.75, '2026-08-25 12:58:21'),
(48, '2026-08-28', 'SUPPLIER_MANUAL', 24, 'Payment made to Ghani glass (Manual Debit) - abc', 0.00, 500.00, -6311.75, '2026-08-28 11:50:35'),
(49, '2026-08-29', 'SALE', 37, 'Sale Payment: SAL-00033', 89100.00, 0.00, 82788.25, '2026-08-29 11:34:02'),
(50, '2026-09-01', 'EMPLOYEE_PAYMENT', 4, 'Salary payment to: Faheem', 0.00, 500.00, 82288.25, '2026-09-01 12:33:08'),
(51, '2026-09-02', 'SUPPLIER_MANUAL', 30, 'Refund/Cash received from Ghani glass (Manual Credit) - abc', 50.00, 0.00, 82338.25, '2026-09-02 09:41:36');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `id` int(11) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `id` int(11) NOT NULL,
  `customer_code` varchar(50) NOT NULL,
  `customer_name` varchar(200) NOT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `mobile` varchar(20) NOT NULL,
  `cnic` varchar(20) DEFAULT NULL,
  `ntn` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `opening_balance` decimal(15,2) DEFAULT 0.00,
  `balance_type` enum('receivable','payable') DEFAULT 'receivable' COMMENT 'receivable=Customer owes company, payable=Company owes customer',
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `status` tinyint(1) DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_code`, `customer_name`, `company_name`, `contact_person`, `mobile`, `cnic`, `ntn`, `email`, `address`, `opening_balance`, `balance_type`, `current_balance`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'CUS-0001', 'Test', 'Test', NULL, '03245675677', '', NULL, '', '', 0.00, 'receivable', 2104540.00, 1, '', '2026-06-09 05:49:58', '2026-09-02 08:37:37'),
(6, 'CUS-0004', 'Abbas', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 27700.00, 1, 'Customer added from sale form', '2026-06-10 13:31:37', '2026-08-25 10:55:21'),
(7, 'CUS-0005', 'Ali', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', -55000.00, 1, 'Customer added from sale form', '2026-06-12 05:43:50', '2026-07-30 11:47:57'),
(8, 'CUS-0006', 'Faheem', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 306.50, 1, 'Customer added from sale form', '2026-06-12 06:08:12', '2026-09-02 09:57:14'),
(9, 'CUS-0007', 'sajid', 'Sajid', NULL, '03217917178', '', NULL, 'talhaarshadatr@gmail.com', 'P/O raja wala chak No. 22/10r kacha khuh', 25000.00, 'receivable', 106952.78, 1, '', '2026-06-12 06:12:10', '2026-08-11 08:03:39'),
(10, 'CUS-0008', 'Zareef', NULL, NULL, '0321545655', NULL, NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Added from quotation form', '2026-06-12 07:33:43', '2026-06-12 07:33:43'),
(11, 'CUS-0009', 'tahir', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Added from quotation form', '2026-06-12 07:41:59', '2026-06-12 07:41:59'),
(12, 'CUS-0010', 'zaid', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-06-12 09:44:23', '2026-06-12 09:44:23'),
(13, 'CUS-0011', 'abid', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-06-15 10:05:17', '2026-08-12 06:39:04'),
(14, 'CUS-0012', 'teste', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 283.70, 1, 'Customer added from sale form', '2026-06-15 10:06:55', '2026-08-12 06:39:56'),
(15, 'CUS-0013', 'sabi', NULL, NULL, '03214565898', NULL, NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Added from quotation form', '2026-06-16 06:48:24', '2026-06-16 06:48:24'),
(16, 'CUS-0014', 'Mujahid Glass Ameer Chowk', '03007470698', NULL, '03067690429', '', NULL, '', 'Ameer Chowk College Road Lahore', 0.00, 'receivable', 1403167.50, 1, '', '2026-06-17 11:14:13', '2026-07-14 18:05:26'),
(17, 'CUS-0015', 'ahmad ali', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-07-14 17:50:19', '2026-07-14 17:50:19');

-- --------------------------------------------------------

--
-- Table structure for table `customer_ledger`
--

CREATE TABLE `customer_ledger` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `customer_id` int(11) NOT NULL,
  `reference_type` enum('OPENING','SALE','PAYMENT','ADJUSTMENT','QUOTATION') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0.00 COMMENT 'Customer owes company (Sale)',
  `credit` decimal(15,2) DEFAULT 0.00 COMMENT 'Customer pays company (Payment)',
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_ledger`
--

INSERT INTO `customer_ledger` (`id`, `date`, `customer_id`, `reference_type`, `reference_id`, `description`, `debit`, `credit`, `balance`, `created_at`) VALUES
(1, '2026-06-12', 9, 'OPENING', 9, 'Opening Balance - Receivable (Customer owes company)', 25000.00, 0.00, 25000.00, '2026-06-12 06:12:10'),
(2, '2026-06-12', 9, 'SALE', 6, 'Sale Invoice: SAL-00006 - \n                                    6mm Clear Glass                                 (15 x 19), \n                                    6mm Clear Glass                                 (11 x 85)', 25250.00, 0.00, 50250.00, '2026-06-12 06:13:22'),
(3, '2026-06-15', 9, 'SALE', 9, 'Sale Invoice: SAL-00009 - \n                                    12mm clear glass                                 (34 x 85), \n                                    12mm clear glass                                 (0 x 0), \n                                    12mm clear glass                                 (0 x 0) and 2 more items', 56702.78, 0.00, 106952.78, '2026-06-15 15:10:33'),
(4, '2026-06-17', 16, 'SALE', 10, 'Sale Invoice: SAL-00010 - \n                                    12mm clear glass                                 (35.7 x 88.2), \n                                    12mm clear glass                                 (40.7 x 88.2), \n                                    12mm clear glass                                 (31.5 x 87.7) and 5 more items', 90667.50, 0.00, 90667.50, '2026-06-17 11:20:48'),
(6, '2026-06-18', 16, 'SALE', 15, 'Sale Invoice: SAL-00014 - \n                                    12mm clear glass                                ', 2100.00, 0.00, 92767.50, '2026-06-18 11:51:03'),
(7, '2026-07-05', 7, 'PAYMENT', 1, 'Payment received - ', 0.00, 50000.00, -50000.00, '2026-07-05 14:36:49'),
(8, '2026-07-14', 16, 'SALE', 24, 'Sale Invoice: SAL-00020 - \n                                    12mm clear glass                                 (36 x 84)', 1310400.00, 0.00, 1403167.50, '2026-07-14 18:05:26'),
(9, '2026-07-14', 1, 'SALE', 25, 'Sale Invoice: SAL-00021 - \n                                    12mm clear glass                                 (45 x 78)', 1248000.00, 0.00, 1248000.00, '2026-07-14 18:07:22'),
(10, '2026-07-14', 1, 'SALE', 26, 'Sale Invoice: SAL-00022 - \n                                    12mm clear glass                                 (23 x 24), \n                                    12mm clear glass                                 (25 x 75), \n                                    12mm clear glass                                 (23 x 45) and 6 more items', 1013440.00, 0.00, 2261440.00, '2026-07-14 18:11:56'),
(11, '2026-07-14', 1, 'PAYMENT', 2, 'Payment received - ', 0.00, 150000.00, 2111440.00, '2026-07-14 18:17:27'),
(12, '2026-07-16', 6, 'SALE', 27, 'Sale Invoice: SAL-00023 - \n                                    6mm white                                 (034 x 045), \n                                    6mm brown                                 (35 x 45), \n                                    12mm clear glass                                 (45 x 66)', 11600.00, 0.00, 11600.00, '2026-07-16 12:51:39'),
(13, '2026-07-16', 6, 'SALE', 28, 'Sale Invoice: SAL-00024 - \n                                    6mm white                                 (034 x 045), \n                                    6mm brown                                 (35 x 45), \n                                    12mm clear glass                                 (45 x 66)', 16600.00, 0.00, 28200.00, '2026-07-16 12:54:22'),
(14, '2026-07-26', 1, 'PAYMENT', 3, 'Payment received - ', 0.00, 5000.00, 2106440.00, '2026-07-26 18:57:59'),
(15, '2026-07-30', 1, 'PAYMENT', 4, 'Payment received - abc', 0.00, 2000.00, 2104440.00, '2026-07-30 11:38:26'),
(16, '2026-07-30', 7, 'PAYMENT', 5, 'Payment received - ', 0.00, 5000.00, -55000.00, '2026-07-30 11:47:57'),
(20, '2026-08-11', 1, 'QUOTATION', 12, 'Quotation: QTN-00006 - 6mm Clear Glass (6.00 x 6.00)', 100.00, 0.00, 2104540.00, '2026-08-11 08:10:14'),
(23, '2026-08-12', 14, 'QUOTATION', 14, 'Quotation: QTN-00007 - 6mm brown (4.00 x 4.00), 6mm black (8.00 x 8.00), 12mm clear glass (6.00 x 6.00)', 283.70, 0.00, 283.70, '2026-08-12 06:39:56'),
(24, '2026-08-20', 8, 'QUOTATION', 15, 'Quotation: QTN-00008 - 12mm clear glass (4.00 x 4.00)', 44.00, 0.00, 44.00, '2026-08-20 10:09:26'),
(25, '2026-08-20', 8, 'QUOTATION', 16, 'Quotation: QTN-00009 - 12mm clear glass (6.00 x 6.00)', 100.00, 0.00, 144.00, '2026-08-20 10:32:41'),
(26, '2026-08-25', 8, 'PAYMENT', 6, 'Payment received - ', 0.00, 10.00, 134.00, '2026-08-25 06:12:15'),
(27, '2026-08-25', 6, 'PAYMENT', 7, 'Payment received - ', 0.00, 500.00, 27700.00, '2026-08-25 10:55:21'),
(28, '2026-08-12', 6, 'SALE', 36, 'Sale Invoice: SAL-00032 - 12mm clear glass (6.00 x 4.00)', 100.00, 100.00, 27700.00, '2026-08-25 12:58:21'),
(33, '2026-08-29', 6, 'SALE', 37, 'Sale Invoice: SAL-00033 - 12mm clear glass (84 x 96), 12mm clear glass (072 x 60), 6mm black (25 x 72)', 89100.00, 89100.00, 27700.00, '2026-08-29 11:34:02'),
(35, '2026-09-02', 8, 'QUOTATION', 17, 'Quotation: QTN-00010 -  (6 x 6),  (6 x 6)', 172.50, 0.00, 306.50, '2026-09-02 09:57:14');

-- --------------------------------------------------------

--
-- Table structure for table `customer_payments`
--

CREATE TABLE `customer_payments` (
  `id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `customer_id` int(11) NOT NULL,
  `payment_method` enum('cash','bank') NOT NULL,
  `bank_account_id` int(11) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `sale_invoice_no` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_receipts`
--

CREATE TABLE `customer_receipts` (
  `id` int(11) NOT NULL,
  `receipt_date` date NOT NULL,
  `customer_id` int(11) NOT NULL,
  `payment_method` enum('cash','bank') NOT NULL,
  `bank_account_id` int(11) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `sale_invoice_no` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_receipts`
--

INSERT INTO `customer_receipts` (`id`, `receipt_date`, `customer_id`, `payment_method`, `bank_account_id`, `reference_no`, `amount`, `sale_invoice_no`, `remarks`, `created_by`, `created_at`) VALUES
(1, '2026-07-05', 7, 'cash', NULL, '', 50000.00, NULL, '', 1, '2026-07-05 14:36:49'),
(2, '2026-07-14', 1, 'bank', 1, '', 150000.00, NULL, '', 1, '2026-07-14 18:17:27'),
(3, '2026-07-26', 1, 'cash', NULL, '', 5000.00, NULL, '', 1, '2026-07-26 18:57:59'),
(4, '2026-07-30', 1, 'cash', NULL, '', 2000.00, NULL, 'abc', 1, '2026-07-30 11:38:26'),
(5, '2026-07-30', 7, 'cash', NULL, '', 5000.00, NULL, '', 1, '2026-07-30 11:47:57'),
(6, '2026-08-25', 8, 'cash', NULL, '', 10.00, NULL, '', 1, '2026-08-25 06:12:15'),
(7, '2026-08-25', 6, 'cash', NULL, '', 500.00, NULL, '', 1, '2026-08-25 10:55:21');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_code` varchar(50) NOT NULL,
  `employee_name` varchar(200) NOT NULL,
  `father_name` varchar(200) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `employee_type` enum('permanent','contract','daily_wage','commission') DEFAULT 'permanent',
  `cnic` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `basic_salary` decimal(15,2) DEFAULT 0.00,
  `allowances` decimal(15,2) DEFAULT 0.00,
  `deductions` decimal(15,2) DEFAULT 0.00,
  `net_salary` decimal(15,2) DEFAULT 0.00,
  `opening_balance` decimal(15,2) DEFAULT 0.00,
  `balance_type` enum('payable','advance') DEFAULT 'payable',
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_code`, `employee_name`, `father_name`, `designation`, `department`, `employee_type`, `cnic`, `mobile`, `email`, `address`, `joining_date`, `basic_salary`, `allowances`, `deductions`, `net_salary`, `opening_balance`, `balance_type`, `current_balance`, `bank_name`, `bank_account_no`, `status`, `notes`, `created_at`) VALUES
(2, 'EMP-0002', 'Faheem', 'Ashraf', 'lahore', 'computer science', 'contract', '352145697455', '03214569745', 'faheem@gmail.com', 'abc1', '2026-07-30', 45000.00, 5000.00, 0.00, 50000.00, 10000.00, 'payable', 8548.00, 'faysal bank', '7896544133544', 1, '', '2026-07-30 12:41:04');

-- --------------------------------------------------------

--
-- Table structure for table `employee_ledger`
--

CREATE TABLE `employee_ledger` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `employee_id` int(11) NOT NULL,
  `reference_type` enum('OPENING','SALARY','PAYMENT','ADVANCE','ADJUSTMENT') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `month_year` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_ledger`
--

INSERT INTO `employee_ledger` (`id`, `date`, `employee_id`, `reference_type`, `reference_id`, `description`, `debit`, `credit`, `balance`, `month_year`, `created_at`) VALUES
(1, '2026-07-30', 2, 'OPENING', 2, 'Opening Balance - Payable (Company owes employee)', 0.00, 10000.00, 10000.00, NULL, '2026-07-30 12:41:04'),
(2, '2026-07-30', 2, 'PAYMENT', 1, 'Salary payment made - Cash for 2026-07', 452.00, 0.00, 9548.00, '2026-07', '2026-07-30 12:49:08'),
(4, '2026-09-01', 4, 'OPENING', 4, 'Opening Balance - Advance (Employee took advance)', 50000.00, 0.00, 50000.00, NULL, '2026-09-01 12:32:27'),
(5, '2026-09-01', 2, 'PAYMENT', 3, 'Salary payment made - Bank (Ref: 8794) for 2026-09', 500.00, 0.00, 9048.00, '2026-09', '2026-09-01 12:32:53'),
(6, '2026-09-01', 2, 'PAYMENT', 4, 'Salary payment made - Cash for 2026-09', 500.00, 0.00, 8548.00, '2026-09', '2026-09-01 12:33:08');

-- --------------------------------------------------------

--
-- Table structure for table `employee_payments`
--

CREATE TABLE `employee_payments` (
  `id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `employee_id` int(11) NOT NULL,
  `payment_method` enum('cash','bank') NOT NULL,
  `bank_account_id` int(11) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `salary_month` varchar(10) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_payments`
--

INSERT INTO `employee_payments` (`id`, `payment_date`, `employee_id`, `payment_method`, `bank_account_id`, `reference_no`, `amount`, `salary_month`, `remarks`, `created_by`, `created_at`) VALUES
(1, '2026-07-30', 2, 'cash', 0, '', 452.00, '2026-07', '', 1, '2026-07-30 12:49:08'),
(3, '2026-09-01', 2, 'bank', 1, '8794', 500.00, '2026-09', '', 1, '2026-09-01 12:32:53'),
(4, '2026-09-01', 2, 'cash', 0, '', 500.00, '2026-09', '', 1, '2026-09-01 12:33:08');

-- --------------------------------------------------------

--
-- Table structure for table `employee_salary`
--

CREATE TABLE `employee_salary` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `month_year` varchar(10) NOT NULL,
  `basic_salary` decimal(15,2) DEFAULT 0.00,
  `allowances` decimal(15,2) DEFAULT 0.00,
  `deductions` decimal(15,2) DEFAULT 0.00,
  `net_salary` decimal(15,2) DEFAULT 0.00,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `remaining_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','partial','paid') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `head_id` int(11) NOT NULL,
  `payment_method` enum('cash','bank') NOT NULL,
  `bank_account_id` int(11) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
  `id` int(11) NOT NULL,
  `head_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `expense_id` int(11) NOT NULL,
  `head_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_ledger`
--

INSERT INTO `expense_ledger` (`id`, `date`, `expense_id`, `head_id`, `description`, `debit`, `credit`, `balance`, `created_at`) VALUES
(1, '2026-06-09', 1, 1, 'Expense: Electricity Bill', 10000.00, 0.00, 10000.00, '2026-06-09 05:38:28');

-- --------------------------------------------------------

--
-- Table structure for table `hold_quotations_details`
--

CREATE TABLE `hold_quotations_details` (
  `id` int(11) NOT NULL,
  `hold_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `client_height` decimal(10,2) DEFAULT 0.00,
  `client_width` decimal(10,2) DEFAULT 0.00,
  `client_size` varchar(100) DEFAULT NULL,
  `multiple_of` int(11) DEFAULT 6,
  `std_height` varchar(50) DEFAULT NULL,
  `std_width` varchar(50) DEFAULT NULL,
  `uom` varchar(20) DEFAULT 'Inch',
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `area` decimal(15,2) DEFAULT 0.00,
  `rate` decimal(15,2) DEFAULT 0.00,
  `amount` decimal(15,2) DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `net_amount` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hold_quotations_details`
--

INSERT INTO `hold_quotations_details` (`id`, `hold_id`, `product_id`, `client_height`, `client_width`, `client_size`, `multiple_of`, `std_height`, `std_width`, `uom`, `quantity`, `unit_price`, `area`, `rate`, `amount`, `discount_percentage`, `discount_amount`, `net_amount`) VALUES
(1, 1, 3, 141.00, 168.00, NULL, 6, '141', '168', 'Inch', 5.00, 400.00, 164.50, 0.00, 329000.00, 0.00, 0.00, 329000.00);

-- --------------------------------------------------------

--
-- Table structure for table `hold_quotations_master`
--

CREATE TABLE `hold_quotations_master` (
  `id` int(11) NOT NULL,
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
  `received_amount` decimal(15,2) DEFAULT 0.00,
  `remaining_amount` decimal(15,2) DEFAULT 0.00,
  `payment_type` enum('cash','bank','credit','partial') DEFAULT 'credit',
  `bank_account_id` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('hold','converted','cancelled') DEFAULT 'hold',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hold_quotations_master`
--

INSERT INTO `hold_quotations_master` (`id`, `hold_no`, `hold_date`, `customer_id`, `valid_until`, `reference_no`, `subtotal`, `discount_percentage`, `discount_amount`, `other_charges`, `grand_total`, `received_amount`, `remaining_amount`, `payment_type`, `bank_account_id`, `remarks`, `status`, `created_by`, `created_at`) VALUES
(1, 'HOLDQ-00006', '2026-06-16', 15, '2026-07-16', 'das543', 329000.00, 0.00, 0.00, 0.00, 329000.00, 0.00, 0.00, 'credit', NULL, '', 'hold', 1, '2026-06-16 06:48:58');

-- --------------------------------------------------------

--
-- Table structure for table `hold_sales_details`
--

CREATE TABLE `hold_sales_details` (
  `id` int(11) NOT NULL,
  `hold_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `client_height` decimal(10,2) DEFAULT 0.00,
  `client_width` decimal(10,2) DEFAULT 0.00,
  `multiple_of` int(11) DEFAULT 6,
  `std_height` varchar(50) DEFAULT NULL,
  `std_width` varchar(50) DEFAULT NULL,
  `uom` varchar(20) DEFAULT 'Inch',
  `quantity` decimal(15,2) NOT NULL,
  `area` decimal(15,2) DEFAULT 0.00,
  `rate` decimal(15,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `amount` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hold_sales_details`
--

INSERT INTO `hold_sales_details` (`id`, `hold_id`, `product_id`, `client_height`, `client_width`, `multiple_of`, `std_height`, `std_width`, `uom`, `quantity`, `area`, `rate`, `discount_percentage`, `amount`) VALUES
(1, 1, 3, 13.00, 15.00, 3, '15', '15', NULL, 5.00, 1.56, 400.00, NULL, 3125.00),
(2, 2, 3, 13.00, 15.00, 3, '15', '15', NULL, 5.00, 0.00, 400.00, NULL, 0.00),
(3, 3, 3, 7.00, 11.00, 3, '9', '12', NULL, 5.00, 0.75, 400.00, NULL, 1500.00),
(33, 7, 5, 8.00, 8.00, 6, '12', '12', '', 1.00, 1.00, 320.00, 0.00, 320.00),
(34, 8, 5, 0.00, 0.00, 6, '1', '1', '', 1.00, 0.01, 320.00, 0.00, 2.22),
(36, 10, 16, 4.00, 4.00, 6, '6', '6', '', 1.00, 0.25, 330.00, 0.00, 82.50),
(37, 11, 16, 4.00, 4.00, 6, '6', '6', '', 1.00, 0.25, 330.00, 0.00, 82.50),
(38, 12, 16, 4.00, 4.00, 6, '6', '6', '', 1.00, 0.25, 330.00, 0.00, 82.50);

-- --------------------------------------------------------

--
-- Table structure for table `hold_sales_master`
--

CREATE TABLE `hold_sales_master` (
  `id` int(11) NOT NULL,
  `hold_no` varchar(50) NOT NULL,
  `hold_date` date NOT NULL,
  `customer_id` int(11) NOT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `other_charges` decimal(15,2) DEFAULT 0.00,
  `grand_total` decimal(15,2) DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `status` enum('hold','converted','cancelled') DEFAULT 'hold',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hold_sales_master`
--

INSERT INTO `hold_sales_master` (`id`, `hold_no`, `hold_date`, `customer_id`, `subtotal`, `discount_percentage`, `discount_amount`, `other_charges`, `grand_total`, `remarks`, `status`, `created_by`, `created_at`) VALUES
(4, 'HOLD-00002', '2026-06-17', 6, 11160.00, 0.00, 0.00, 0.00, 11160.00, '', 'hold', 1, '2026-06-17 12:18:23'),
(6, 'HOLD-00004', '2026-07-05', 14, 2.78, 0.00, 0.00, 0.00, 2.78, 'polish & tempered', 'hold', 1, '2026-07-05 14:34:31'),
(7, 'HOLD-00005', '2026-08-10', 6, 320.00, 0.00, 0.00, 0.00, 320.00, '', 'hold', 1, '2026-08-10 10:26:57'),
(8, 'HOLD-00006', '2026-08-10', 14, 2.22, 0.00, 0.00, 0.00, 2.22, '', 'hold', 1, '2026-08-10 10:27:52'),
(10, 'HOLD-00007', '2026-08-11', 6, 82.50, 0.00, 0.00, 0.00, 82.50, '', 'hold', 1, '2026-08-11 07:21:58'),
(11, 'HOLD-00008', '2026-08-11', 6, 82.50, 0.00, 0.00, 0.00, 82.50, '', 'hold', 1, '2026-08-11 07:22:07'),
(12, 'HOLD-00009', '2026-08-11', 6, 82.50, 0.00, 0.00, 0.00, 82.50, '', 'hold', 1, '2026-08-11 07:22:20');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_ledger`
--

CREATE TABLE `inventory_ledger` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `product_id` int(11) NOT NULL,
  `reference_type` enum('OPENING','PURCHASE','SALE','ADJUSTMENT','QUOTATION') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `qty_in` decimal(15,2) DEFAULT 0.00,
  `qty_out` decimal(15,2) DEFAULT 0.00,
  `balance_qty` decimal(15,2) DEFAULT 0.00,
  `unit_price` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_ledger`
--

INSERT INTO `inventory_ledger` (`id`, `date`, `product_id`, `reference_type`, `reference_id`, `qty_in`, `qty_out`, `balance_qty`, `unit_price`, `total_amount`, `remarks`, `created_at`) VALUES
(7, '2026-06-10', 2, 'OPENING', 2, 1500.00, 0.00, 1500.00, 2500.00, 3750000.00, 'Opening Stock Entry', '2026-06-10 13:31:26'),
(10, '2026-06-10', 2, 'SALE', 4, 0.00, 2.00, 1498.00, 3000.00, 3375.00, 'Sale Invoice: SAL-00004', '2026-06-10 13:32:46'),
(11, '2026-06-10', 2, 'SALE', 4, 0.00, 1.00, 1497.00, 3000.00, 5250.00, 'Sale Invoice: SAL-00004', '2026-06-10 13:32:46'),
(15, '2026-06-12', 2, 'SALE', 5, 0.00, 1.00, 1496.00, 3000.00, 2812.50, 'Sale Invoice: SAL-00005', '2026-06-12 06:09:12'),
(16, '2026-06-12', 2, 'SALE', 5, 0.00, 1.00, 1495.00, 3000.00, 7875.00, 'Sale Invoice: SAL-00005', '2026-06-12 06:09:12'),
(19, '2026-06-12', 3, 'OPENING', 3, 433890.00, 0.00, 433890.00, 350.00, 151861500.00, 'Opening Stock Entry - Opening Stock: 1000 pieces, each 433.89 sq ft, total 433890 sq ft', '2026-06-12 09:40:52'),
(20, '2026-06-12', 3, 'SALE', 7, 0.00, 343.75, 433546.25, 400.00, 137500.00, 'Sale Invoice: SAL-00007 - Total Area: 343.75 sq ft', '2026-06-12 09:44:50'),
(21, '2026-06-12', 3, 'PURCHASE', 2, 0.50, 0.00, 433546.75, 350.00, 175.00, 'Purchase Invoice: PUR-00002', '2026-06-12 09:50:55'),
(22, '2026-06-15', 3, 'SALE', 8, 0.00, 39.00, 433507.75, 400.00, 15600.00, 'Sale Invoice: SAL-00008 - Total Area: 39 sq ft', '2026-06-15 07:43:44'),
(23, '2026-06-15', 3, 'SALE', 8, 0.00, 28.00, 433479.75, 400.00, 11200.00, 'Sale Invoice: SAL-00008 - Total Area: 28 sq ft', '2026-06-15 07:43:44'),
(24, '2026-06-15', 3, 'SALE', 9, 0.00, 21.75, 433458.00, 400.00, 8700.00, 'Sale Invoice: SAL-00009 - Total Area: 21.75 sq ft', '2026-06-15 15:10:33'),
(25, '2026-06-15', 3, 'SALE', 9, 0.00, 0.01, 433457.99, 400.00, 2.78, 'Sale Invoice: SAL-00009 - Total Area: 0.0069444444444444 sq ft', '2026-06-15 15:10:33'),
(26, '2026-06-15', 3, 'SALE', 9, 0.00, 0.00, 433457.99, 400.00, 0.00, 'Sale Invoice: SAL-00009 - Total Area: 0 sq ft', '2026-06-15 15:10:33'),
(27, '2026-06-15', 3, 'SALE', 9, 0.00, 0.00, 433457.99, 400.00, 0.00, 'Sale Invoice: SAL-00009 - Total Area: 0 sq ft', '2026-06-15 15:10:33'),
(29, '2026-06-17', 3, 'SALE', 10, 0.00, 22.50, 433435.49, 660.00, 14850.00, 'Sale Invoice: SAL-00010 - Total Area: 22.5 sq ft', '2026-06-17 11:20:48'),
(30, '2026-06-17', 3, 'SALE', 10, 0.00, 26.25, 433409.24, 660.00, 17325.00, 'Sale Invoice: SAL-00010 - Total Area: 26.25 sq ft', '2026-06-17 11:20:48'),
(31, '2026-06-17', 3, 'SALE', 10, 0.00, 20.63, 433388.62, 660.00, 13612.50, 'Sale Invoice: SAL-00010 - Total Area: 20.625 sq ft', '2026-06-17 11:20:48'),
(32, '2026-06-17', 3, 'SALE', 10, 0.00, 15.00, 433373.62, 660.00, 9900.00, 'Sale Invoice: SAL-00010 - Total Area: 15 sq ft', '2026-06-17 11:20:48'),
(33, '2026-06-17', 3, 'SALE', 10, 0.00, 11.25, 433362.37, 660.00, 7425.00, 'Sale Invoice: SAL-00010 - Total Area: 11.25 sq ft', '2026-06-17 11:20:48'),
(34, '2026-06-17', 3, 'SALE', 10, 0.00, 19.50, 433342.87, 660.00, 12870.00, 'Sale Invoice: SAL-00010 - Total Area: 19.5 sq ft', '2026-06-17 11:20:48'),
(35, '2026-06-17', 3, 'SALE', 10, 0.00, 16.25, 433326.62, 660.00, 10725.00, 'Sale Invoice: SAL-00010 - Total Area: 16.25 sq ft', '2026-06-17 11:20:48'),
(36, '2026-06-17', 3, 'SALE', 10, 0.00, 6.00, 433320.62, 660.00, 3960.00, 'Sale Invoice: SAL-00010 - Total Area: 6 sq ft', '2026-06-17 11:20:48'),
(37, '2026-06-17', 3, 'SALE', 11, 0.00, 22.50, 433298.12, 660.00, 14850.00, 'Sale Invoice: SAL-00011 - Total Area: 22.5 sq ft', '2026-06-17 11:32:31'),
(38, '2026-06-17', 3, 'SALE', 11, 0.00, 26.25, 433271.87, 660.00, 17325.00, 'Sale Invoice: SAL-00011 - Total Area: 26.25 sq ft', '2026-06-17 11:32:31'),
(39, '2026-06-17', 3, 'SALE', 11, 0.00, 20.63, 433251.25, 660.00, 13612.50, 'Sale Invoice: SAL-00011 - Total Area: 20.625 sq ft', '2026-06-17 11:32:31'),
(40, '2026-06-17', 3, 'SALE', 11, 0.00, 15.00, 433236.25, 660.00, 9900.00, 'Sale Invoice: SAL-00011 - Total Area: 15 sq ft', '2026-06-17 11:32:31'),
(41, '2026-06-17', 3, 'SALE', 11, 0.00, 11.25, 433225.00, 660.00, 7425.00, 'Sale Invoice: SAL-00011 - Total Area: 11.25 sq ft', '2026-06-17 11:32:31'),
(42, '2026-06-17', 3, 'SALE', 11, 0.00, 19.50, 433205.50, 660.00, 12870.00, 'Sale Invoice: SAL-00011 - Total Area: 19.5 sq ft', '2026-06-17 11:32:31'),
(43, '2026-06-17', 3, 'SALE', 11, 0.00, 16.25, 433189.25, 660.00, 10725.00, 'Sale Invoice: SAL-00011 - Total Area: 16.25 sq ft', '2026-06-17 11:32:31'),
(44, '2026-06-17', 3, 'SALE', 11, 0.00, 6.00, 433183.25, 660.00, 3960.00, 'Sale Invoice: SAL-00011 - Total Area: 6 sq ft', '2026-06-17 11:32:31'),
(45, '2026-06-17', 3, 'ADJUSTMENT', 11, 1.00, 0.00, 433184.25, 660.00, 0.00, 'Sale Deleted: SAL-00011', '2026-06-17 11:36:17'),
(46, '2026-06-17', 3, 'ADJUSTMENT', 11, 1.00, 0.00, 433185.25, 660.00, 0.00, 'Sale Deleted: SAL-00011', '2026-06-17 11:36:17'),
(47, '2026-06-17', 3, 'ADJUSTMENT', 11, 1.00, 0.00, 433186.25, 660.00, 0.00, 'Sale Deleted: SAL-00011', '2026-06-17 11:36:17'),
(48, '2026-06-17', 3, 'ADJUSTMENT', 11, 1.00, 0.00, 433187.25, 660.00, 0.00, 'Sale Deleted: SAL-00011', '2026-06-17 11:36:17'),
(49, '2026-06-17', 3, 'ADJUSTMENT', 11, 2.00, 0.00, 433189.25, 660.00, 0.00, 'Sale Deleted: SAL-00011', '2026-06-17 11:36:17'),
(50, '2026-06-17', 3, 'ADJUSTMENT', 11, 1.00, 0.00, 433190.25, 660.00, 0.00, 'Sale Deleted: SAL-00011', '2026-06-17 11:36:17'),
(51, '2026-06-17', 3, 'ADJUSTMENT', 11, 1.00, 0.00, 433191.25, 660.00, 0.00, 'Sale Deleted: SAL-00011', '2026-06-17 11:36:17'),
(52, '2026-06-17', 3, 'ADJUSTMENT', 11, 1.00, 0.00, 433192.25, 660.00, 0.00, 'Sale Deleted: SAL-00011', '2026-06-17 11:36:17'),
(53, '2026-06-17', 3, 'SALE', 12, 0.00, 5.63, 433186.63, 400.00, 2250.00, 'Sale Invoice: SAL-00011 - Total Area: 5.625 sq ft', '2026-06-17 11:49:31'),
(54, '2026-06-17', 3, 'SALE', 13, 0.00, 8.00, 433178.63, 400.00, 3200.00, 'Sale Invoice: SAL-00012 - Total Area: 8 sq ft', '2026-06-17 11:50:51'),
(55, '2026-06-17', 3, 'PURCHASE', 3, 1440.00, 0.00, 434618.63, 350.00, 504000.00, 'Purchase Invoice: PUR-00003', '2026-06-17 12:58:30'),
(57, '2026-06-17', 2, 'PURCHASE', 3, 936.00, 0.00, 2431.00, 500.00, 468000.00, 'Purchase Invoice: PUR-00003', '2026-06-17 12:58:30'),
(58, '2026-06-17', 3, 'PURCHASE', 4, 1440.00, 0.00, 436058.63, 350.00, 504000.00, 'Purchase Invoice: PUR-00004', '2026-06-17 12:58:53'),
(60, '2026-06-17', 2, 'PURCHASE', 4, 936.00, 0.00, 3367.00, 500.00, 468000.00, 'Purchase Invoice: PUR-00004', '2026-06-17 12:58:53'),
(61, '2026-06-17', 4, 'OPENING', 4, 4200.00, 0.00, 4200.00, 340.00, 1428000.00, 'Opening Stock Entry - Opening Stock: 50 pieces, each 84 sq ft, total 4200 sq ft', '2026-06-17 13:45:52'),
(62, '2026-06-18', 3, 'SALE', 14, 0.00, 5.63, 436053.01, 400.00, 2250.00, 'Sale Invoice: SAL-00013 - Total Area: 5.625 sq ft', '2026-06-18 11:19:20'),
(63, '2026-06-18', 3, 'SALE', 15, 0.00, 5.63, 436047.39, 400.00, 2250.00, 'Sale Invoice: SAL-00014 - Total Area: 5.625 sq ft', '2026-06-18 11:33:45'),
(69, '2026-06-18', 3, 'ADJUSTMENT', 15, 2.00, 0.00, 436049.39, 0.00, 0.00, 'Restored from edit of invoice', '2026-06-18 11:51:03'),
(70, '2026-06-18', 3, 'SALE', 15, 0.00, 5.25, 436044.14, 400.00, 2100.00, 'Sale Invoice: SAL-00014 - Total Area: 5.25 sq ft', '2026-06-18 11:51:03'),
(71, '2026-06-24', 3, 'SALE', 16, 0.00, 63.00, 435981.14, 400.00, 25200.00, 'Sale Invoice: SAL-00015 - Total Area: 63 sq ft', '2026-06-24 14:47:38'),
(72, '2026-06-24', 3, 'SALE', 17, 0.00, 36.00, 435945.14, 400.00, 14400.00, 'Sale Invoice: SAL-00016 - Total Area: 36 sq ft', '2026-06-24 16:14:57'),
(73, '2026-06-24', 3, 'SALE', 18, 0.00, 130.00, 435815.14, 400.00, 52000.00, 'Sale Invoice: SAL-00017 - Total Area: 130 sq ft', '2026-06-24 16:17:31'),
(74, '2026-06-26', 5, 'OPENING', 5, 4200.00, 0.00, 4200.00, 315.00, 1323000.00, 'Opening Stock Entry - Opening Stock: 50 pieces, each 84 sq ft, total 4200 sq ft', '2026-06-26 16:59:16'),
(75, '2026-07-05', 3, 'SALE', 19, 0.00, 17.50, 435797.64, 770.00, 13475.00, 'Sale Invoice: SAL-00018 - Total Area: 17.5 sq ft', '2026-07-05 14:16:59'),
(76, '2026-07-05', 3, 'SALE', 19, 0.00, 18.75, 435778.89, 770.00, 14437.50, 'Sale Invoice: SAL-00018 - Total Area: 18.75 sq ft', '2026-07-05 14:16:59'),
(77, '2026-07-05', 3, 'SALE', 19, 0.00, 8.75, 435770.14, 770.00, 6737.50, 'Sale Invoice: SAL-00018 - Total Area: 8.75 sq ft', '2026-07-05 14:16:59'),
(78, '2026-07-05', 3, 'SALE', 19, 0.00, 10.00, 435760.14, 770.00, 7700.00, 'Sale Invoice: SAL-00018 - Total Area: 10 sq ft', '2026-07-05 14:16:59'),
(79, '2026-07-05', 3, 'SALE', 19, 0.00, 50.00, 435710.14, 770.00, 38500.00, 'Sale Invoice: SAL-00018 - Total Area: 50 sq ft', '2026-07-05 14:16:59'),
(80, '2026-07-05', 3, 'SALE', 19, 0.00, 18.75, 435691.39, 770.00, 14437.50, 'Sale Invoice: SAL-00018 - Total Area: 18.75 sq ft', '2026-07-05 14:16:59'),
(81, '2026-07-05', 3, 'SALE', 19, 0.00, 5.00, 435686.39, 770.00, 3850.00, 'Sale Invoice: SAL-00018 - Total Area: 5 sq ft', '2026-07-05 14:16:59'),
(82, '2026-07-05', 3, 'SALE', 19, 0.00, 7.50, 435678.89, 770.00, 5775.00, 'Sale Invoice: SAL-00018 - Total Area: 7.5 sq ft', '2026-07-05 14:16:59'),
(83, '2026-07-05', 3, 'SALE', 19, 0.00, 18.75, 435660.14, 770.00, 14437.50, 'Sale Invoice: SAL-00018 - Total Area: 18.75 sq ft', '2026-07-05 14:16:59'),
(84, '2026-07-05', 3, 'SALE', 19, 0.00, 17.50, 435642.64, 770.00, 13475.00, 'Sale Invoice: SAL-00018 - Total Area: 17.5 sq ft', '2026-07-05 14:16:59'),
(85, '2026-07-05', 3, 'SALE', 19, 0.00, 16.25, 435626.39, 770.00, 12512.50, 'Sale Invoice: SAL-00018 - Total Area: 16.25 sq ft', '2026-07-05 14:16:59'),
(86, '2026-07-05', 3, 'SALE', 19, 0.00, 8.75, 435617.64, 770.00, 6737.50, 'Sale Invoice: SAL-00018 - Total Area: 8.75 sq ft', '2026-07-05 14:16:59'),
(87, '2026-07-05', 3, 'SALE', 19, 0.00, 23.75, 435593.89, 770.00, 18287.50, 'Sale Invoice: SAL-00018 - Total Area: 23.75 sq ft', '2026-07-05 14:16:59'),
(88, '2026-07-05', 3, 'SALE', 19, 0.00, 1.25, 435592.64, 770.00, 962.50, 'Sale Invoice: SAL-00018 - Total Area: 1.25 sq ft', '2026-07-05 14:16:59'),
(89, '2026-07-05', 3, 'SALE', 19, 0.00, 1.88, 435590.77, 770.00, 1443.75, 'Sale Invoice: SAL-00018 - Total Area: 1.875 sq ft', '2026-07-05 14:16:59'),
(90, '2026-07-05', 3, 'SALE', 19, 0.00, 21.25, 435569.52, 770.00, 16362.50, 'Sale Invoice: SAL-00018 - Total Area: 21.25 sq ft', '2026-07-05 14:16:59'),
(91, '2026-07-05', 3, 'SALE', 19, 0.00, 22.50, 435547.02, 770.00, 17325.00, 'Sale Invoice: SAL-00018 - Total Area: 22.5 sq ft', '2026-07-05 14:16:59'),
(92, '2026-07-05', 3, 'SALE', 19, 0.00, 20.00, 435527.02, 770.00, 15400.00, 'Sale Invoice: SAL-00018 - Total Area: 20 sq ft', '2026-07-05 14:16:59'),
(93, '2026-07-05', 3, 'SALE', 19, 0.00, 22.50, 435504.52, 770.00, 17325.00, 'Sale Invoice: SAL-00018 - Total Area: 22.5 sq ft', '2026-07-05 14:16:59'),
(94, '2026-07-05', 3, 'SALE', 19, 0.00, 22.50, 435482.02, 770.00, 17325.00, 'Sale Invoice: SAL-00018 - Total Area: 22.5 sq ft', '2026-07-05 14:16:59'),
(95, '2026-07-05', 3, 'SALE', 19, 0.00, 47.50, 435434.52, 770.00, 36575.00, 'Sale Invoice: SAL-00018 - Total Area: 47.5 sq ft', '2026-07-05 14:16:59'),
(96, '2026-07-05', 3, 'SALE', 19, 0.00, 18.75, 435415.77, 770.00, 14437.50, 'Sale Invoice: SAL-00018 - Total Area: 18.75 sq ft', '2026-07-05 14:16:59'),
(97, '2026-07-05', 3, 'SALE', 19, 0.00, 32.50, 435383.27, 770.00, 25025.00, 'Sale Invoice: SAL-00018 - Total Area: 32.5 sq ft', '2026-07-05 14:16:59'),
(98, '2026-07-05', 3, 'SALE', 19, 0.00, 17.50, 435365.77, 770.00, 13475.00, 'Sale Invoice: SAL-00018 - Total Area: 17.5 sq ft', '2026-07-05 14:16:59'),
(99, '2026-07-05', 3, 'SALE', 19, 0.00, 22.50, 435343.27, 770.00, 17325.00, 'Sale Invoice: SAL-00018 - Total Area: 22.5 sq ft', '2026-07-05 14:16:59'),
(100, '2026-07-05', 3, 'SALE', 19, 0.00, 13.75, 435329.52, 770.00, 10587.50, 'Sale Invoice: SAL-00018 - Total Area: 13.75 sq ft', '2026-07-05 14:16:59'),
(101, '2026-07-05', 3, 'SALE', 19, 0.00, 13.75, 435315.77, 770.00, 10587.50, 'Sale Invoice: SAL-00018 - Total Area: 13.75 sq ft', '2026-07-05 14:16:59'),
(102, '2026-07-05', 3, 'SALE', 20, 0.00, 17.50, 435298.27, 770.00, 13475.00, 'Sale Invoice: SAL-00019 - Total Area: 17.5 sq ft', '2026-07-05 14:17:09'),
(103, '2026-07-05', 3, 'SALE', 20, 0.00, 18.75, 435279.52, 770.00, 14437.50, 'Sale Invoice: SAL-00019 - Total Area: 18.75 sq ft', '2026-07-05 14:17:09'),
(104, '2026-07-05', 3, 'SALE', 20, 0.00, 8.75, 435270.77, 770.00, 6737.50, 'Sale Invoice: SAL-00019 - Total Area: 8.75 sq ft', '2026-07-05 14:17:09'),
(105, '2026-07-05', 3, 'SALE', 20, 0.00, 10.00, 435260.77, 770.00, 7700.00, 'Sale Invoice: SAL-00019 - Total Area: 10 sq ft', '2026-07-05 14:17:09'),
(106, '2026-07-05', 3, 'SALE', 20, 0.00, 50.00, 435210.77, 770.00, 38500.00, 'Sale Invoice: SAL-00019 - Total Area: 50 sq ft', '2026-07-05 14:17:09'),
(107, '2026-07-05', 3, 'SALE', 20, 0.00, 18.75, 435192.02, 770.00, 14437.50, 'Sale Invoice: SAL-00019 - Total Area: 18.75 sq ft', '2026-07-05 14:17:09'),
(108, '2026-07-05', 3, 'SALE', 20, 0.00, 5.00, 435187.02, 770.00, 3850.00, 'Sale Invoice: SAL-00019 - Total Area: 5 sq ft', '2026-07-05 14:17:09'),
(109, '2026-07-05', 3, 'SALE', 20, 0.00, 7.50, 435179.52, 770.00, 5775.00, 'Sale Invoice: SAL-00019 - Total Area: 7.5 sq ft', '2026-07-05 14:17:09'),
(110, '2026-07-05', 3, 'SALE', 20, 0.00, 18.75, 435160.77, 770.00, 14437.50, 'Sale Invoice: SAL-00019 - Total Area: 18.75 sq ft', '2026-07-05 14:17:09'),
(111, '2026-07-05', 3, 'SALE', 20, 0.00, 17.50, 435143.27, 770.00, 13475.00, 'Sale Invoice: SAL-00019 - Total Area: 17.5 sq ft', '2026-07-05 14:17:09'),
(112, '2026-07-05', 3, 'SALE', 20, 0.00, 16.25, 435127.02, 770.00, 12512.50, 'Sale Invoice: SAL-00019 - Total Area: 16.25 sq ft', '2026-07-05 14:17:09'),
(113, '2026-07-05', 3, 'SALE', 20, 0.00, 8.75, 435118.27, 770.00, 6737.50, 'Sale Invoice: SAL-00019 - Total Area: 8.75 sq ft', '2026-07-05 14:17:09'),
(114, '2026-07-05', 3, 'SALE', 20, 0.00, 23.75, 435094.52, 770.00, 18287.50, 'Sale Invoice: SAL-00019 - Total Area: 23.75 sq ft', '2026-07-05 14:17:09'),
(115, '2026-07-05', 3, 'SALE', 20, 0.00, 1.25, 435093.27, 770.00, 962.50, 'Sale Invoice: SAL-00019 - Total Area: 1.25 sq ft', '2026-07-05 14:17:09'),
(116, '2026-07-05', 3, 'SALE', 20, 0.00, 1.88, 435091.40, 770.00, 1443.75, 'Sale Invoice: SAL-00019 - Total Area: 1.875 sq ft', '2026-07-05 14:17:09'),
(117, '2026-07-05', 3, 'SALE', 20, 0.00, 21.25, 435070.15, 770.00, 16362.50, 'Sale Invoice: SAL-00019 - Total Area: 21.25 sq ft', '2026-07-05 14:17:09'),
(118, '2026-07-05', 3, 'SALE', 20, 0.00, 22.50, 435047.65, 770.00, 17325.00, 'Sale Invoice: SAL-00019 - Total Area: 22.5 sq ft', '2026-07-05 14:17:09'),
(119, '2026-07-05', 3, 'SALE', 20, 0.00, 20.00, 435027.65, 770.00, 15400.00, 'Sale Invoice: SAL-00019 - Total Area: 20 sq ft', '2026-07-05 14:17:09'),
(120, '2026-07-05', 3, 'SALE', 20, 0.00, 22.50, 435005.15, 770.00, 17325.00, 'Sale Invoice: SAL-00019 - Total Area: 22.5 sq ft', '2026-07-05 14:17:09'),
(121, '2026-07-05', 3, 'SALE', 20, 0.00, 22.50, 434982.65, 770.00, 17325.00, 'Sale Invoice: SAL-00019 - Total Area: 22.5 sq ft', '2026-07-05 14:17:09'),
(122, '2026-07-05', 3, 'SALE', 20, 0.00, 47.50, 434935.15, 770.00, 36575.00, 'Sale Invoice: SAL-00019 - Total Area: 47.5 sq ft', '2026-07-05 14:17:09'),
(123, '2026-07-05', 3, 'SALE', 20, 0.00, 18.75, 434916.40, 770.00, 14437.50, 'Sale Invoice: SAL-00019 - Total Area: 18.75 sq ft', '2026-07-05 14:17:09'),
(124, '2026-07-05', 3, 'SALE', 20, 0.00, 32.50, 434883.90, 770.00, 25025.00, 'Sale Invoice: SAL-00019 - Total Area: 32.5 sq ft', '2026-07-05 14:17:09'),
(125, '2026-07-05', 3, 'SALE', 20, 0.00, 17.50, 434866.40, 770.00, 13475.00, 'Sale Invoice: SAL-00019 - Total Area: 17.5 sq ft', '2026-07-05 14:17:09'),
(126, '2026-07-05', 3, 'SALE', 20, 0.00, 22.50, 434843.90, 770.00, 17325.00, 'Sale Invoice: SAL-00019 - Total Area: 22.5 sq ft', '2026-07-05 14:17:09'),
(127, '2026-07-05', 3, 'SALE', 20, 0.00, 13.75, 434830.15, 770.00, 10587.50, 'Sale Invoice: SAL-00019 - Total Area: 13.75 sq ft', '2026-07-05 14:17:09'),
(128, '2026-07-05', 3, 'SALE', 20, 0.00, 13.75, 434816.40, 770.00, 10587.50, 'Sale Invoice: SAL-00019 - Total Area: 13.75 sq ft', '2026-07-05 14:17:09'),
(129, '2026-07-05', 3, 'SALE', 21, 0.00, 17.50, 434798.90, 770.00, 13475.00, 'Sale Invoice: SAL-00020 - Total Area: 17.5 sq ft', '2026-07-05 14:17:37'),
(130, '2026-07-05', 3, 'SALE', 21, 0.00, 18.75, 434780.15, 770.00, 14437.50, 'Sale Invoice: SAL-00020 - Total Area: 18.75 sq ft', '2026-07-05 14:17:37'),
(131, '2026-07-05', 3, 'SALE', 21, 0.00, 8.75, 434771.40, 770.00, 6737.50, 'Sale Invoice: SAL-00020 - Total Area: 8.75 sq ft', '2026-07-05 14:17:37'),
(132, '2026-07-05', 3, 'SALE', 21, 0.00, 10.00, 434761.40, 770.00, 7700.00, 'Sale Invoice: SAL-00020 - Total Area: 10 sq ft', '2026-07-05 14:17:37'),
(133, '2026-07-05', 3, 'SALE', 21, 0.00, 50.00, 434711.40, 770.00, 38500.00, 'Sale Invoice: SAL-00020 - Total Area: 50 sq ft', '2026-07-05 14:17:37'),
(134, '2026-07-05', 3, 'SALE', 21, 0.00, 18.75, 434692.65, 770.00, 14437.50, 'Sale Invoice: SAL-00020 - Total Area: 18.75 sq ft', '2026-07-05 14:17:37'),
(135, '2026-07-05', 3, 'SALE', 21, 0.00, 5.00, 434687.65, 770.00, 3850.00, 'Sale Invoice: SAL-00020 - Total Area: 5 sq ft', '2026-07-05 14:17:37'),
(136, '2026-07-05', 3, 'SALE', 21, 0.00, 7.50, 434680.15, 770.00, 5775.00, 'Sale Invoice: SAL-00020 - Total Area: 7.5 sq ft', '2026-07-05 14:17:37'),
(137, '2026-07-05', 3, 'SALE', 21, 0.00, 18.75, 434661.40, 770.00, 14437.50, 'Sale Invoice: SAL-00020 - Total Area: 18.75 sq ft', '2026-07-05 14:17:37'),
(138, '2026-07-05', 3, 'SALE', 21, 0.00, 17.50, 434643.90, 770.00, 13475.00, 'Sale Invoice: SAL-00020 - Total Area: 17.5 sq ft', '2026-07-05 14:17:37'),
(139, '2026-07-05', 3, 'SALE', 21, 0.00, 16.25, 434627.65, 770.00, 12512.50, 'Sale Invoice: SAL-00020 - Total Area: 16.25 sq ft', '2026-07-05 14:17:37'),
(140, '2026-07-05', 3, 'SALE', 21, 0.00, 8.75, 434618.90, 770.00, 6737.50, 'Sale Invoice: SAL-00020 - Total Area: 8.75 sq ft', '2026-07-05 14:17:37'),
(141, '2026-07-05', 3, 'SALE', 21, 0.00, 23.75, 434595.15, 770.00, 18287.50, 'Sale Invoice: SAL-00020 - Total Area: 23.75 sq ft', '2026-07-05 14:17:37'),
(142, '2026-07-05', 3, 'SALE', 21, 0.00, 1.25, 434593.90, 770.00, 962.50, 'Sale Invoice: SAL-00020 - Total Area: 1.25 sq ft', '2026-07-05 14:17:37'),
(143, '2026-07-05', 3, 'SALE', 21, 0.00, 1.88, 434592.03, 770.00, 1443.75, 'Sale Invoice: SAL-00020 - Total Area: 1.875 sq ft', '2026-07-05 14:17:37'),
(144, '2026-07-05', 3, 'SALE', 21, 0.00, 21.25, 434570.78, 770.00, 16362.50, 'Sale Invoice: SAL-00020 - Total Area: 21.25 sq ft', '2026-07-05 14:17:37'),
(145, '2026-07-05', 3, 'SALE', 21, 0.00, 22.50, 434548.28, 770.00, 17325.00, 'Sale Invoice: SAL-00020 - Total Area: 22.5 sq ft', '2026-07-05 14:17:37'),
(146, '2026-07-05', 3, 'SALE', 21, 0.00, 20.00, 434528.28, 770.00, 15400.00, 'Sale Invoice: SAL-00020 - Total Area: 20 sq ft', '2026-07-05 14:17:37'),
(147, '2026-07-05', 3, 'SALE', 21, 0.00, 22.50, 434505.78, 770.00, 17325.00, 'Sale Invoice: SAL-00020 - Total Area: 22.5 sq ft', '2026-07-05 14:17:37'),
(148, '2026-07-05', 3, 'SALE', 21, 0.00, 22.50, 434483.28, 770.00, 17325.00, 'Sale Invoice: SAL-00020 - Total Area: 22.5 sq ft', '2026-07-05 14:17:37'),
(149, '2026-07-05', 3, 'SALE', 21, 0.00, 47.50, 434435.78, 770.00, 36575.00, 'Sale Invoice: SAL-00020 - Total Area: 47.5 sq ft', '2026-07-05 14:17:37'),
(150, '2026-07-05', 3, 'SALE', 21, 0.00, 18.75, 434417.03, 770.00, 14437.50, 'Sale Invoice: SAL-00020 - Total Area: 18.75 sq ft', '2026-07-05 14:17:37'),
(151, '2026-07-05', 3, 'SALE', 21, 0.00, 32.50, 434384.53, 770.00, 25025.00, 'Sale Invoice: SAL-00020 - Total Area: 32.5 sq ft', '2026-07-05 14:17:37'),
(152, '2026-07-05', 3, 'SALE', 21, 0.00, 17.50, 434367.03, 770.00, 13475.00, 'Sale Invoice: SAL-00020 - Total Area: 17.5 sq ft', '2026-07-05 14:17:37'),
(153, '2026-07-05', 3, 'SALE', 21, 0.00, 22.50, 434344.53, 770.00, 17325.00, 'Sale Invoice: SAL-00020 - Total Area: 22.5 sq ft', '2026-07-05 14:17:37'),
(154, '2026-07-05', 3, 'SALE', 21, 0.00, 13.75, 434330.78, 770.00, 10587.50, 'Sale Invoice: SAL-00020 - Total Area: 13.75 sq ft', '2026-07-05 14:17:37'),
(155, '2026-07-05', 3, 'SALE', 21, 0.00, 13.75, 434317.03, 770.00, 10587.50, 'Sale Invoice: SAL-00020 - Total Area: 13.75 sq ft', '2026-07-05 14:17:37'),
(156, '2026-07-05', 3, 'SALE', 22, 0.00, 17.50, 434299.53, 770.00, 13475.00, 'Sale Invoice: SAL-00021 - Total Area: 17.5 sq ft', '2026-07-05 14:17:57'),
(157, '2026-07-05', 3, 'SALE', 22, 0.00, 18.75, 434280.78, 770.00, 14437.50, 'Sale Invoice: SAL-00021 - Total Area: 18.75 sq ft', '2026-07-05 14:17:57'),
(158, '2026-07-05', 3, 'SALE', 22, 0.00, 8.75, 434272.03, 770.00, 6737.50, 'Sale Invoice: SAL-00021 - Total Area: 8.75 sq ft', '2026-07-05 14:17:57'),
(159, '2026-07-05', 3, 'SALE', 22, 0.00, 10.00, 434262.03, 770.00, 7700.00, 'Sale Invoice: SAL-00021 - Total Area: 10 sq ft', '2026-07-05 14:17:57'),
(160, '2026-07-05', 3, 'SALE', 22, 0.00, 50.00, 434212.03, 770.00, 38500.00, 'Sale Invoice: SAL-00021 - Total Area: 50 sq ft', '2026-07-05 14:17:57'),
(161, '2026-07-05', 3, 'SALE', 22, 0.00, 18.75, 434193.28, 770.00, 14437.50, 'Sale Invoice: SAL-00021 - Total Area: 18.75 sq ft', '2026-07-05 14:17:57'),
(162, '2026-07-05', 3, 'SALE', 22, 0.00, 5.00, 434188.28, 770.00, 3850.00, 'Sale Invoice: SAL-00021 - Total Area: 5 sq ft', '2026-07-05 14:17:57'),
(163, '2026-07-05', 3, 'SALE', 22, 0.00, 7.50, 434180.78, 770.00, 5775.00, 'Sale Invoice: SAL-00021 - Total Area: 7.5 sq ft', '2026-07-05 14:17:57'),
(164, '2026-07-05', 3, 'SALE', 22, 0.00, 18.75, 434162.03, 770.00, 14437.50, 'Sale Invoice: SAL-00021 - Total Area: 18.75 sq ft', '2026-07-05 14:17:57'),
(165, '2026-07-05', 3, 'SALE', 22, 0.00, 17.50, 434144.53, 770.00, 13475.00, 'Sale Invoice: SAL-00021 - Total Area: 17.5 sq ft', '2026-07-05 14:17:57'),
(166, '2026-07-05', 3, 'SALE', 22, 0.00, 16.25, 434128.28, 770.00, 12512.50, 'Sale Invoice: SAL-00021 - Total Area: 16.25 sq ft', '2026-07-05 14:17:57'),
(167, '2026-07-05', 3, 'SALE', 22, 0.00, 8.75, 434119.53, 770.00, 6737.50, 'Sale Invoice: SAL-00021 - Total Area: 8.75 sq ft', '2026-07-05 14:17:57'),
(168, '2026-07-05', 3, 'SALE', 22, 0.00, 23.75, 434095.78, 770.00, 18287.50, 'Sale Invoice: SAL-00021 - Total Area: 23.75 sq ft', '2026-07-05 14:17:57'),
(169, '2026-07-05', 3, 'SALE', 22, 0.00, 1.25, 434094.53, 770.00, 962.50, 'Sale Invoice: SAL-00021 - Total Area: 1.25 sq ft', '2026-07-05 14:17:57'),
(170, '2026-07-05', 3, 'SALE', 22, 0.00, 1.88, 434092.66, 770.00, 1443.75, 'Sale Invoice: SAL-00021 - Total Area: 1.875 sq ft', '2026-07-05 14:17:57'),
(171, '2026-07-05', 3, 'SALE', 22, 0.00, 21.25, 434071.41, 770.00, 16362.50, 'Sale Invoice: SAL-00021 - Total Area: 21.25 sq ft', '2026-07-05 14:17:57'),
(172, '2026-07-05', 3, 'SALE', 22, 0.00, 22.50, 434048.91, 770.00, 17325.00, 'Sale Invoice: SAL-00021 - Total Area: 22.5 sq ft', '2026-07-05 14:17:57'),
(173, '2026-07-05', 3, 'SALE', 22, 0.00, 20.00, 434028.91, 770.00, 15400.00, 'Sale Invoice: SAL-00021 - Total Area: 20 sq ft', '2026-07-05 14:17:57'),
(174, '2026-07-05', 3, 'SALE', 22, 0.00, 22.50, 434006.41, 770.00, 17325.00, 'Sale Invoice: SAL-00021 - Total Area: 22.5 sq ft', '2026-07-05 14:17:57'),
(175, '2026-07-05', 3, 'SALE', 22, 0.00, 22.50, 433983.91, 770.00, 17325.00, 'Sale Invoice: SAL-00021 - Total Area: 22.5 sq ft', '2026-07-05 14:17:57'),
(176, '2026-07-05', 3, 'SALE', 22, 0.00, 47.50, 433936.41, 770.00, 36575.00, 'Sale Invoice: SAL-00021 - Total Area: 47.5 sq ft', '2026-07-05 14:17:57'),
(177, '2026-07-05', 3, 'SALE', 22, 0.00, 18.75, 433917.66, 770.00, 14437.50, 'Sale Invoice: SAL-00021 - Total Area: 18.75 sq ft', '2026-07-05 14:17:57'),
(178, '2026-07-05', 3, 'SALE', 22, 0.00, 32.50, 433885.16, 770.00, 25025.00, 'Sale Invoice: SAL-00021 - Total Area: 32.5 sq ft', '2026-07-05 14:17:57'),
(179, '2026-07-05', 3, 'SALE', 22, 0.00, 17.50, 433867.66, 770.00, 13475.00, 'Sale Invoice: SAL-00021 - Total Area: 17.5 sq ft', '2026-07-05 14:17:57'),
(180, '2026-07-05', 3, 'SALE', 22, 0.00, 22.50, 433845.16, 770.00, 17325.00, 'Sale Invoice: SAL-00021 - Total Area: 22.5 sq ft', '2026-07-05 14:17:57'),
(181, '2026-07-05', 3, 'SALE', 22, 0.00, 13.75, 433831.41, 770.00, 10587.50, 'Sale Invoice: SAL-00021 - Total Area: 13.75 sq ft', '2026-07-05 14:17:57'),
(182, '2026-07-05', 3, 'SALE', 22, 0.00, 13.75, 433817.66, 770.00, 10587.50, 'Sale Invoice: SAL-00021 - Total Area: 13.75 sq ft', '2026-07-05 14:17:57'),
(183, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433818.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(184, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433819.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(185, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433820.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(186, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433821.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(187, '2026-07-05', 3, 'ADJUSTMENT', 22, 2.00, 0.00, 433823.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(188, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433824.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(189, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433825.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(190, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433826.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(191, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433827.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(192, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433828.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(193, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433829.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(194, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433830.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(195, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433831.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(196, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433832.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(197, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433833.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(198, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433834.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(199, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433835.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(200, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433836.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(201, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433837.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(202, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433838.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(203, '2026-07-05', 3, 'ADJUSTMENT', 22, 2.00, 0.00, 433840.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(204, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433841.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(205, '2026-07-05', 3, 'ADJUSTMENT', 22, 2.00, 0.00, 433843.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(206, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433844.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(207, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433845.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(208, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433846.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(209, '2026-07-05', 3, 'ADJUSTMENT', 22, 1.00, 0.00, 433847.66, 770.00, 0.00, 'Sale Deleted: SAL-00021', '2026-07-05 14:26:32'),
(210, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433848.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(211, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433849.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(212, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433850.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(213, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433851.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(214, '2026-07-05', 3, 'ADJUSTMENT', 21, 2.00, 0.00, 433853.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(215, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433854.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(216, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433855.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(217, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433856.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(218, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433857.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(219, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433858.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(220, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433859.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(221, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433860.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(222, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433861.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(223, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433862.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(224, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433863.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(225, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433864.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(226, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433865.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(227, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433866.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(228, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433867.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(229, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433868.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(230, '2026-07-05', 3, 'ADJUSTMENT', 21, 2.00, 0.00, 433870.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(231, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433871.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(232, '2026-07-05', 3, 'ADJUSTMENT', 21, 2.00, 0.00, 433873.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(233, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433874.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(234, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433875.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(235, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433876.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(236, '2026-07-05', 3, 'ADJUSTMENT', 21, 1.00, 0.00, 433877.66, 770.00, 0.00, 'Sale Deleted: SAL-00020', '2026-07-05 14:26:38'),
(237, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433878.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(238, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433879.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(239, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433880.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(240, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433881.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(241, '2026-07-05', 3, 'ADJUSTMENT', 20, 2.00, 0.00, 433883.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(242, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433884.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(243, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433885.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(244, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433886.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(245, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433887.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(246, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433888.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(247, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433889.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(248, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433890.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(249, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433891.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(250, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433892.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(251, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433893.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(252, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433894.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(253, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433895.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(254, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433896.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(255, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433897.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(256, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433898.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(257, '2026-07-05', 3, 'ADJUSTMENT', 20, 2.00, 0.00, 433900.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(258, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433901.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(259, '2026-07-05', 3, 'ADJUSTMENT', 20, 2.00, 0.00, 433903.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(260, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433904.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(261, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433905.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(262, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433906.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(263, '2026-07-05', 3, 'ADJUSTMENT', 20, 1.00, 0.00, 433907.66, 770.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-05 14:26:43'),
(264, '2026-07-05', 3, 'ADJUSTMENT', 19, 17.50, 0.00, 433925.16, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(265, '2026-07-05', 3, 'ADJUSTMENT', 19, 18.75, 0.00, 433943.91, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(266, '2026-07-05', 3, 'ADJUSTMENT', 19, 8.75, 0.00, 433952.66, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(267, '2026-07-05', 3, 'ADJUSTMENT', 19, 10.00, 0.00, 433962.66, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(268, '2026-07-05', 3, 'ADJUSTMENT', 19, 50.00, 0.00, 434012.66, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(269, '2026-07-05', 3, 'ADJUSTMENT', 19, 18.75, 0.00, 434031.41, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(270, '2026-07-05', 3, 'ADJUSTMENT', 19, 5.00, 0.00, 434036.41, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(271, '2026-07-05', 3, 'ADJUSTMENT', 19, 7.50, 0.00, 434043.91, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(272, '2026-07-05', 3, 'ADJUSTMENT', 19, 18.75, 0.00, 434062.66, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(273, '2026-07-05', 3, 'ADJUSTMENT', 19, 17.50, 0.00, 434080.16, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(274, '2026-07-05', 3, 'ADJUSTMENT', 19, 16.25, 0.00, 434096.41, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(275, '2026-07-05', 3, 'ADJUSTMENT', 19, 8.75, 0.00, 434105.16, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(276, '2026-07-05', 3, 'ADJUSTMENT', 19, 23.75, 0.00, 434128.91, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(277, '2026-07-05', 3, 'ADJUSTMENT', 19, 1.25, 0.00, 434130.16, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(278, '2026-07-05', 3, 'ADJUSTMENT', 19, 1.88, 0.00, 434132.04, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(279, '2026-07-05', 3, 'ADJUSTMENT', 19, 21.25, 0.00, 434153.29, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(280, '2026-07-05', 3, 'ADJUSTMENT', 19, 22.50, 0.00, 434175.79, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(281, '2026-07-05', 3, 'ADJUSTMENT', 19, 20.00, 0.00, 434195.79, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(282, '2026-07-05', 3, 'ADJUSTMENT', 19, 22.50, 0.00, 434218.29, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(283, '2026-07-05', 3, 'ADJUSTMENT', 19, 22.50, 0.00, 434240.79, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(284, '2026-07-05', 3, 'ADJUSTMENT', 19, 47.50, 0.00, 434288.29, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(285, '2026-07-05', 3, 'ADJUSTMENT', 19, 18.75, 0.00, 434307.04, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(286, '2026-07-05', 3, 'ADJUSTMENT', 19, 32.50, 0.00, 434339.54, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(287, '2026-07-05', 3, 'ADJUSTMENT', 19, 17.50, 0.00, 434357.04, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(288, '2026-07-05', 3, 'ADJUSTMENT', 19, 22.50, 0.00, 434379.54, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(289, '2026-07-05', 3, 'ADJUSTMENT', 19, 13.75, 0.00, 434393.29, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(290, '2026-07-05', 3, 'ADJUSTMENT', 19, 13.75, 0.00, 434407.04, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:26:56'),
(291, '2026-07-05', 3, 'SALE', 19, 0.00, 17.50, 434389.54, 770.00, 13475.00, 'Sale Invoice: SAL-00018 - Total Area: 17.5 sq ft', '2026-07-05 14:26:56'),
(292, '2026-07-05', 3, 'SALE', 19, 0.00, 18.75, 434370.79, 770.00, 14437.50, 'Sale Invoice: SAL-00018 - Total Area: 18.75 sq ft', '2026-07-05 14:26:56'),
(293, '2026-07-05', 3, 'SALE', 19, 0.00, 8.75, 434362.04, 770.00, 6737.50, 'Sale Invoice: SAL-00018 - Total Area: 8.75 sq ft', '2026-07-05 14:26:56'),
(294, '2026-07-05', 3, 'SALE', 19, 0.00, 10.00, 434352.04, 770.00, 7700.00, 'Sale Invoice: SAL-00018 - Total Area: 10 sq ft', '2026-07-05 14:26:56'),
(295, '2026-07-05', 3, 'SALE', 19, 0.00, 50.00, 434302.04, 770.00, 38500.00, 'Sale Invoice: SAL-00018 - Total Area: 50 sq ft', '2026-07-05 14:26:56'),
(296, '2026-07-05', 3, 'SALE', 19, 0.00, 18.75, 434283.29, 770.00, 14437.50, 'Sale Invoice: SAL-00018 - Total Area: 18.75 sq ft', '2026-07-05 14:26:56'),
(297, '2026-07-05', 3, 'SALE', 19, 0.00, 5.00, 434278.29, 770.00, 3850.00, 'Sale Invoice: SAL-00018 - Total Area: 5 sq ft', '2026-07-05 14:26:56'),
(298, '2026-07-05', 3, 'SALE', 19, 0.00, 7.50, 434270.79, 770.00, 5775.00, 'Sale Invoice: SAL-00018 - Total Area: 7.5 sq ft', '2026-07-05 14:26:56'),
(299, '2026-07-05', 3, 'SALE', 19, 0.00, 18.75, 434252.04, 770.00, 14437.50, 'Sale Invoice: SAL-00018 - Total Area: 18.75 sq ft', '2026-07-05 14:26:56'),
(300, '2026-07-05', 3, 'SALE', 19, 0.00, 17.50, 434234.54, 770.00, 13475.00, 'Sale Invoice: SAL-00018 - Total Area: 17.5 sq ft', '2026-07-05 14:26:56'),
(301, '2026-07-05', 3, 'SALE', 19, 0.00, 16.25, 434218.29, 770.00, 12512.50, 'Sale Invoice: SAL-00018 - Total Area: 16.25 sq ft', '2026-07-05 14:26:56'),
(302, '2026-07-05', 3, 'SALE', 19, 0.00, 8.75, 434209.54, 770.00, 6737.50, 'Sale Invoice: SAL-00018 - Total Area: 8.75 sq ft', '2026-07-05 14:26:56'),
(303, '2026-07-05', 3, 'SALE', 19, 0.00, 23.75, 434185.79, 770.00, 18287.50, 'Sale Invoice: SAL-00018 - Total Area: 23.75 sq ft', '2026-07-05 14:26:56'),
(304, '2026-07-05', 3, 'SALE', 19, 0.00, 1.25, 434184.54, 770.00, 962.50, 'Sale Invoice: SAL-00018 - Total Area: 1.25 sq ft', '2026-07-05 14:26:56'),
(305, '2026-07-05', 3, 'SALE', 19, 0.00, 1.88, 434182.66, 770.00, 1447.60, 'Sale Invoice: SAL-00018 - Total Area: 1.88 sq ft', '2026-07-05 14:26:56'),
(306, '2026-07-05', 3, 'SALE', 19, 0.00, 21.25, 434161.41, 770.00, 16362.50, 'Sale Invoice: SAL-00018 - Total Area: 21.25 sq ft', '2026-07-05 14:26:56'),
(307, '2026-07-05', 3, 'SALE', 19, 0.00, 22.50, 434138.91, 770.00, 17325.00, 'Sale Invoice: SAL-00018 - Total Area: 22.5 sq ft', '2026-07-05 14:26:56'),
(308, '2026-07-05', 3, 'SALE', 19, 0.00, 20.00, 434118.91, 770.00, 15400.00, 'Sale Invoice: SAL-00018 - Total Area: 20 sq ft', '2026-07-05 14:26:56'),
(309, '2026-07-05', 3, 'SALE', 19, 0.00, 22.50, 434096.41, 770.00, 17325.00, 'Sale Invoice: SAL-00018 - Total Area: 22.5 sq ft', '2026-07-05 14:26:56'),
(310, '2026-07-05', 3, 'SALE', 19, 0.00, 22.50, 434073.91, 770.00, 17325.00, 'Sale Invoice: SAL-00018 - Total Area: 22.5 sq ft', '2026-07-05 14:26:56'),
(311, '2026-07-05', 3, 'SALE', 19, 0.00, 47.50, 434026.41, 770.00, 36575.00, 'Sale Invoice: SAL-00018 - Total Area: 47.5 sq ft', '2026-07-05 14:26:56'),
(312, '2026-07-05', 3, 'SALE', 19, 0.00, 18.75, 434007.66, 770.00, 14437.50, 'Sale Invoice: SAL-00018 - Total Area: 18.75 sq ft', '2026-07-05 14:26:56'),
(313, '2026-07-05', 3, 'SALE', 19, 0.00, 32.50, 433975.16, 770.00, 25025.00, 'Sale Invoice: SAL-00018 - Total Area: 32.5 sq ft', '2026-07-05 14:26:56'),
(314, '2026-07-05', 3, 'SALE', 19, 0.00, 17.50, 433957.66, 770.00, 13475.00, 'Sale Invoice: SAL-00018 - Total Area: 17.5 sq ft', '2026-07-05 14:26:56'),
(315, '2026-07-05', 3, 'SALE', 19, 0.00, 22.50, 433935.16, 770.00, 17325.00, 'Sale Invoice: SAL-00018 - Total Area: 22.5 sq ft', '2026-07-05 14:26:56'),
(316, '2026-07-05', 3, 'SALE', 19, 0.00, 13.75, 433921.41, 770.00, 10587.50, 'Sale Invoice: SAL-00018 - Total Area: 13.75 sq ft', '2026-07-05 14:26:56'),
(317, '2026-07-05', 3, 'SALE', 19, 0.00, 13.75, 433907.66, 770.00, 10587.50, 'Sale Invoice: SAL-00018 - Total Area: 13.75 sq ft', '2026-07-05 14:26:56'),
(318, '2026-07-05', 3, 'ADJUSTMENT', 19, 17.50, 0.00, 433925.16, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(319, '2026-07-05', 3, 'ADJUSTMENT', 19, 18.75, 0.00, 433943.91, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(320, '2026-07-05', 3, 'ADJUSTMENT', 19, 8.75, 0.00, 433952.66, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(321, '2026-07-05', 3, 'ADJUSTMENT', 19, 10.00, 0.00, 433962.66, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(322, '2026-07-05', 3, 'ADJUSTMENT', 19, 50.00, 0.00, 434012.66, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(323, '2026-07-05', 3, 'ADJUSTMENT', 19, 18.75, 0.00, 434031.41, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(324, '2026-07-05', 3, 'ADJUSTMENT', 19, 5.00, 0.00, 434036.41, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(325, '2026-07-05', 3, 'ADJUSTMENT', 19, 7.50, 0.00, 434043.91, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(326, '2026-07-05', 3, 'ADJUSTMENT', 19, 18.75, 0.00, 434062.66, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(327, '2026-07-05', 3, 'ADJUSTMENT', 19, 17.50, 0.00, 434080.16, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(328, '2026-07-05', 3, 'ADJUSTMENT', 19, 16.25, 0.00, 434096.41, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(329, '2026-07-05', 3, 'ADJUSTMENT', 19, 8.75, 0.00, 434105.16, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(330, '2026-07-05', 3, 'ADJUSTMENT', 19, 23.75, 0.00, 434128.91, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(331, '2026-07-05', 3, 'ADJUSTMENT', 19, 1.25, 0.00, 434130.16, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(332, '2026-07-05', 3, 'ADJUSTMENT', 19, 1.88, 0.00, 434132.04, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(333, '2026-07-05', 3, 'ADJUSTMENT', 19, 21.25, 0.00, 434153.29, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(334, '2026-07-05', 3, 'ADJUSTMENT', 19, 22.50, 0.00, 434175.79, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(335, '2026-07-05', 3, 'ADJUSTMENT', 19, 20.00, 0.00, 434195.79, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(336, '2026-07-05', 3, 'ADJUSTMENT', 19, 22.50, 0.00, 434218.29, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(337, '2026-07-05', 3, 'ADJUSTMENT', 19, 22.50, 0.00, 434240.79, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(338, '2026-07-05', 3, 'ADJUSTMENT', 19, 47.50, 0.00, 434288.29, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(339, '2026-07-05', 3, 'ADJUSTMENT', 19, 18.75, 0.00, 434307.04, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(340, '2026-07-05', 3, 'ADJUSTMENT', 19, 32.50, 0.00, 434339.54, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(341, '2026-07-05', 3, 'ADJUSTMENT', 19, 17.50, 0.00, 434357.04, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(342, '2026-07-05', 3, 'ADJUSTMENT', 19, 22.50, 0.00, 434379.54, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(343, '2026-07-05', 3, 'ADJUSTMENT', 19, 13.75, 0.00, 434393.29, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(344, '2026-07-05', 3, 'ADJUSTMENT', 19, 13.75, 0.00, 434407.04, 0.00, 0.00, 'Sale Edit Reversal: SAL-00018', '2026-07-05 14:28:40'),
(345, '2026-07-05', 3, 'SALE', 19, 0.00, 0.00, 434407.04, 0.00, 0.00, 'Sale Invoice: SAL-00018 - Total Area: 0 sq ft', '2026-07-05 14:28:40'),
(346, '2026-07-14', 3, 'SALE', 23, 0.00, 12.00, 434395.04, 400.00, 4800.00, 'Sale Invoice: SAL-00019 - Total Area: 12 sq ft', '2026-07-14 17:51:56'),
(347, '2026-07-14', 3, 'SALE', 23, 0.00, 10.50, 434384.54, 400.00, 4200.00, 'Sale Invoice: SAL-00019 - Total Area: 10.5 sq ft', '2026-07-14 17:51:56'),
(348, '2026-07-14', 3, 'SALE', 23, 0.00, 24.50, 434360.04, 400.00, 9800.00, 'Sale Invoice: SAL-00019 - Total Area: 24.5 sq ft', '2026-07-14 17:51:56'),
(349, '2026-07-14', 5, 'SALE', 23, 0.00, 11.25, 4188.75, 320.00, 3600.00, 'Sale Invoice: SAL-00019 - Total Area: 11.25 sq ft', '2026-07-14 17:51:56'),
(350, '2026-07-14', 5, 'SALE', 23, 0.00, 21.00, 4167.75, 320.00, 6720.00, 'Sale Invoice: SAL-00019 - Total Area: 21 sq ft', '2026-07-14 17:51:56'),
(351, '2026-07-14', 3, 'ADJUSTMENT', 23, 12.00, 0.00, 434372.04, 0.00, 0.00, 'Sale Edit Reversal: SAL-00019', '2026-07-14 17:54:23'),
(352, '2026-07-14', 3, 'ADJUSTMENT', 23, 10.50, 0.00, 434382.54, 0.00, 0.00, 'Sale Edit Reversal: SAL-00019', '2026-07-14 17:54:23'),
(353, '2026-07-14', 3, 'ADJUSTMENT', 23, 24.50, 0.00, 434407.04, 0.00, 0.00, 'Sale Edit Reversal: SAL-00019', '2026-07-14 17:54:23'),
(354, '2026-07-14', 5, 'ADJUSTMENT', 23, 11.25, 0.00, 4179.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00019', '2026-07-14 17:54:23'),
(355, '2026-07-14', 5, 'ADJUSTMENT', 23, 21.00, 0.00, 4200.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00019', '2026-07-14 17:54:23'),
(356, '2026-07-14', 3, 'SALE', 23, 0.00, 804.00, 433603.04, 400.00, 321600.00, 'Sale Invoice: SAL-00019 - Total Area: 804 sq ft', '2026-07-14 17:54:23'),
(357, '2026-07-14', 3, 'SALE', 23, 0.00, 10.50, 433592.54, 400.00, 4200.00, 'Sale Invoice: SAL-00019 - Total Area: 10.5 sq ft', '2026-07-14 17:54:23'),
(358, '2026-07-14', 3, 'SALE', 23, 0.00, 1372.00, 432220.54, 400.00, 548800.00, 'Sale Invoice: SAL-00019 - Total Area: 1372 sq ft', '2026-07-14 17:54:23'),
(359, '2026-07-14', 5, 'SALE', 23, 0.00, 11.25, 4188.75, 320.00, 3600.00, 'Sale Invoice: SAL-00019 - Total Area: 11.25 sq ft', '2026-07-14 17:54:23'),
(360, '2026-07-14', 5, 'SALE', 23, 0.00, 21.00, 4167.75, 320.00, 6720.00, 'Sale Invoice: SAL-00019 - Total Area: 21 sq ft', '2026-07-14 17:54:23'),
(361, '2026-07-14', 3, 'SALE', 24, 0.00, 3276.00, 428944.54, 400.00, 1310400.00, 'Sale Invoice: SAL-00020 - Total Area: 3276 sq ft', '2026-07-14 18:05:26'),
(362, '2026-07-14', 3, 'SALE', 25, 0.00, 3120.00, 425824.54, 400.00, 1248000.00, 'Sale Invoice: SAL-00021 - Total Area: 3120 sq ft', '2026-07-14 18:07:22'),
(363, '2026-07-14', 3, 'SALE', 26, 0.00, 4.00, 425820.54, 400.00, 1600.00, 'Sale Invoice: SAL-00022 - Total Area: 4 sq ft', '2026-07-14 18:11:56'),
(364, '2026-07-14', 3, 'SALE', 26, 0.00, 16.25, 425804.29, 400.00, 6500.00, 'Sale Invoice: SAL-00022 - Total Area: 16.25 sq ft', '2026-07-14 18:11:56'),
(365, '2026-07-14', 3, 'SALE', 26, 0.00, 8.00, 425796.29, 400.00, 3200.00, 'Sale Invoice: SAL-00022 - Total Area: 8 sq ft', '2026-07-14 18:11:56'),
(366, '2026-07-14', 3, 'SALE', 26, 0.00, 42.25, 425754.04, 400.00, 16900.00, 'Sale Invoice: SAL-00022 - Total Area: 42.25 sq ft', '2026-07-14 18:11:56'),
(367, '2026-07-14', 3, 'SALE', 26, 0.00, 45.50, 425708.54, 400.00, 18200.00, 'Sale Invoice: SAL-00022 - Total Area: 45.5 sq ft', '2026-07-14 18:11:56'),
(368, '2026-07-14', 3, 'SALE', 26, 0.00, 10.00, 425698.54, 400.00, 4000.00, 'Sale Invoice: SAL-00022 - Total Area: 10 sq ft', '2026-07-14 18:11:56'),
(369, '2026-07-14', 3, 'SALE', 26, 0.00, 42.00, 425656.54, 400.00, 16800.00, 'Sale Invoice: SAL-00022 - Total Area: 42 sq ft', '2026-07-14 18:11:56'),
(370, '2026-07-14', 5, 'SALE', 26, 0.00, 2912.00, 1255.75, 320.00, 931840.00, 'Sale Invoice: SAL-00022 - Total Area: 2912 sq ft', '2026-07-14 18:11:56'),
(371, '2026-07-14', 5, 'SALE', 26, 0.00, 45.00, 1210.75, 320.00, 14400.00, 'Sale Invoice: SAL-00022 - Total Area: 45 sq ft', '2026-07-14 18:11:56'),
(372, '2026-07-14', 3, 'PURCHASE', 5, 84.00, 0.00, 425740.54, 350.00, 29400.00, 'Purchase Invoice: PUR-00005', '2026-07-14 18:28:18'),
(373, '2026-07-14', 4, 'PURCHASE', 5, 663.00, 0.00, 4863.00, 340.00, 225420.00, 'Purchase Invoice: PUR-00005', '2026-07-14 18:28:18'),
(374, '2026-07-14', 5, 'PURCHASE', 5, 150.00, 0.00, 1360.75, 315.00, 47250.00, 'Purchase Invoice: PUR-00005', '2026-07-14 18:28:18'),
(375, '2026-07-14', 3, 'PURCHASE', 6, 4032.00, 0.00, 429772.54, 350.00, 1411200.00, 'Purchase Invoice: PUR-00006', '2026-07-14 18:50:36');
INSERT INTO `inventory_ledger` (`id`, `date`, `product_id`, `reference_type`, `reference_id`, `qty_in`, `qty_out`, `balance_qty`, `unit_price`, `total_amount`, `remarks`, `created_at`) VALUES
(376, '2026-07-16', 4, 'SALE', 27, 0.00, 12.00, 4851.00, 330.00, 3960.00, 'Sale Invoice: SAL-00023 - Total Area: 12 sq ft', '2026-07-16 12:51:39'),
(377, '2026-07-16', 5, 'SALE', 27, 0.00, 12.00, 1348.75, 320.00, 3840.00, 'Sale Invoice: SAL-00023 - Total Area: 12 sq ft', '2026-07-16 12:51:39'),
(378, '2026-07-16', 3, 'SALE', 27, 0.00, 22.00, 429750.54, 400.00, 8800.00, 'Sale Invoice: SAL-00023 - Total Area: 22 sq ft', '2026-07-16 12:51:39'),
(379, '2026-07-16', 4, 'SALE', 28, 0.00, 12.00, 4839.00, 330.00, 3960.00, 'Sale Invoice: SAL-00024 - Total Area: 12 sq ft', '2026-07-16 12:54:22'),
(380, '2026-07-16', 5, 'SALE', 28, 0.00, 12.00, 1336.75, 320.00, 3840.00, 'Sale Invoice: SAL-00024 - Total Area: 12 sq ft', '2026-07-16 12:54:22'),
(381, '2026-07-16', 3, 'SALE', 28, 0.00, 22.00, 429728.54, 400.00, 8800.00, 'Sale Invoice: SAL-00024 - Total Area: 22 sq ft', '2026-07-16 12:54:22'),
(382, '2026-07-16', 5, 'SALE', 29, 0.00, 1.00, 1335.75, 320.00, 320.00, 'Sale Invoice: SAL-00025 - Total Area: 1 sq ft', '2026-07-16 13:06:55'),
(383, '2026-07-16', 5, 'SALE', 29, 0.00, 1.00, 1334.75, 320.00, 320.00, 'Sale Invoice: SAL-00025 - Total Area: 1 sq ft', '2026-07-16 13:06:55'),
(384, '2026-07-16', 5, 'SALE', 30, 0.00, 1.00, 1333.75, 320.00, 320.00, 'Sale Invoice: SAL-00026 - Total Area: 1 sq ft', '2026-07-16 13:09:11'),
(385, '2026-07-16', 5, 'SALE', 30, 0.00, 1.00, 1332.75, 320.00, 320.00, 'Sale Invoice: SAL-00026 - Total Area: 1 sq ft', '2026-07-16 13:09:11'),
(386, '2026-07-16', 3, 'SALE', 31, 0.00, 1.00, 429727.54, 400.00, 400.00, 'Sale Invoice: SAL-00027 - Total Area: 1 sq ft', '2026-07-16 13:40:42'),
(388, '2026-07-20', 3, 'SALE', 32, 0.00, 441.00, 429286.54, 400.00, 176400.00, 'Sale Invoice: SAL-00028 - Total Area: 441 sq ft', '2026-07-20 18:10:41'),
(389, '2026-07-20', 3, 'ADJUSTMENT', 31, 1.00, 0.00, 429287.54, 0.00, 0.00, 'Sale Edit Reversal: SAL-00027', '2026-07-20 18:12:19'),
(392, '2026-07-16', 3, 'SALE', 31, 0.00, 1.00, 429286.54, 400.00, 400.00, 'Sale Invoice: SAL-00027 - Total Area: 1 sq ft', '2026-07-20 18:12:19'),
(393, '2026-07-20', 3, 'PURCHASE', 7, 3780.00, 0.00, 433066.54, 350.00, 1323000.00, 'Purchase Invoice: PUR-00007', '2026-07-20 18:13:37'),
(394, '2026-07-23', 5, 'SALE', 33, 0.00, 15.00, 1317.75, 330.00, 4950.00, 'Sale Invoice: SAL-00029 - Total Area: 15 sq ft', '2026-07-23 06:36:06'),
(395, '2026-07-23', 5, 'SALE', 33, 0.00, 44.00, 1273.75, 330.00, 14520.00, 'Sale Invoice: SAL-00029 - Total Area: 44 sq ft', '2026-07-23 06:36:06'),
(396, '2026-07-23', 5, 'SALE', 33, 0.00, 27.50, 1246.25, 330.00, 9075.00, 'Sale Invoice: SAL-00029 - Total Area: 27.5 sq ft', '2026-07-23 06:36:06'),
(397, '2026-07-23', 3, 'ADJUSTMENT', 19, 1.00, 0.00, 433067.54, 0.00, 0.00, 'Sale Deleted: SAL-00018', '2026-07-23 06:37:02'),
(398, '2026-07-23', 3, 'ADJUSTMENT', 23, 67.00, 0.00, 433134.54, 400.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-23 06:37:08'),
(399, '2026-07-23', 3, 'ADJUSTMENT', 23, 1.00, 0.00, 433135.54, 400.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-23 06:37:08'),
(400, '2026-07-23', 3, 'ADJUSTMENT', 23, 56.00, 0.00, 433191.54, 400.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-23 06:37:08'),
(401, '2026-07-23', 5, 'ADJUSTMENT', 23, 1.00, 0.00, 1247.25, 320.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-23 06:37:08'),
(402, '2026-07-23', 5, 'ADJUSTMENT', 23, 1.00, 0.00, 1248.25, 320.00, 0.00, 'Sale Deleted: SAL-00019', '2026-07-23 06:37:08'),
(403, '2026-07-23', 6, 'OPENING', 6, 8400.00, 0.00, 8400.00, 450.00, 3780000.00, 'Opening Stock Entry - Opening Stock: 100 pieces, each 84 sq ft, total 8400 sq ft', '2026-07-23 06:49:37'),
(404, '2026-07-26', 3, 'PURCHASE', 8, 4956.00, 0.00, 438147.54, 350.00, 1734600.00, 'Purchase Invoice: PUR-00008', '2026-07-26 18:27:56'),
(405, '2026-07-26', 3, 'SALE', 34, 0.00, 48.00, 438099.54, 400.00, 19200.00, 'Sale Invoice: SAL-00030 - Total Area: 48 sq ft', '2026-07-26 18:52:44'),
(406, '2026-07-30', 5, 'PURCHASE', 9, 6.75, 0.00, 1255.00, 315.00, 2126.25, 'Purchase Invoice: PUR-00009', '2026-07-30 11:05:29'),
(407, '2026-07-30', 5, 'PURCHASE', 10, 6.75, 0.00, 1261.75, 315.00, 2126.25, 'Purchase Invoice: PUR-00010', '2026-07-30 11:05:58'),
(408, '2026-07-30', 3, 'PURCHASE', 11, 1.50, 0.00, 438101.04, 350.00, 525.00, 'Purchase Invoice: PUR-00011', '2026-07-30 11:09:20'),
(409, '2026-07-30', 3, 'SALE', 35, 0.00, 49.00, 438052.04, 400.00, 19600.00, 'Sale Invoice: SAL-00031 - Total Area: 49 sq ft', '2026-07-30 11:51:12'),
(428, '2026-08-10', 16, 'OPENING', 0, 1600.00, 0.00, 1600.00, 340.00, 544000.00, 'Opening Stock', '2026-08-10 12:11:33'),
(429, '2026-08-10', 16, 'OPENING', 0, 2560.00, 0.00, 4160.00, 340.00, 870400.00, 'Opening Stock', '2026-08-10 12:11:33'),
(436, '2026-08-11', 16, 'ADJUSTMENT', 11, 0.11, 0.00, 4160.00, 0.00, 0.00, 'Quotation Deletion Restore', '2026-08-11 07:45:07'),
(437, '2026-08-11', 5, 'ADJUSTMENT', 10, 0.11, 0.00, 1261.75, 0.00, 0.00, 'Hold Quotation Migration - Stock Restore', '2026-08-11 08:03:39'),
(440, '2026-08-12', 3, 'SALE', 36, 0.00, 0.25, 438051.79, 400.00, 100.00, 'Sale Invoice: SAL-00032 - Total Area: 0.25 sq ft', '2026-08-12 05:36:51'),
(441, '2026-08-12', 16, 'PURCHASE', 12, 2.50, 0.00, 4162.50, 340.00, 849.15, 'Purchase Invoice: PUR-00012', '2026-08-12 05:38:24'),
(442, '2026-08-12', 2, 'ADJUSTMENT', 13, 1.10, 0.00, 3367.00, 0.00, 0.00, 'Quotation Deletion Restore', '2026-08-12 06:39:04'),
(443, '2026-08-12', 5, 'QUOTATION', 14, 0.00, 0.11, 1261.64, 320.00, 35.20, 'Quotation: QTN-00007 - Total Area: 0.11 sq ft', '2026-08-12 06:39:56'),
(444, '2026-08-12', 16, 'QUOTATION', 14, 0.00, 0.45, 4162.05, 330.00, 148.50, 'Quotation: QTN-00007 - Total Area: 0.45 sq ft', '2026-08-12 06:39:56'),
(445, '2026-08-12', 3, 'QUOTATION', 14, 0.00, 0.25, 438051.54, 400.00, 100.00, 'Quotation: QTN-00007 - Total Area: 0.25 sq ft', '2026-08-12 06:39:56'),
(446, '2026-08-20', 3, 'QUOTATION', 15, 0.00, 0.11, 438051.43, 400.00, 44.00, 'Quotation: QTN-00008 - Total Area: 0.11 sq ft', '2026-08-20 10:09:26'),
(447, '2026-08-20', 3, 'QUOTATION', 16, 0.00, 0.25, 438051.18, 400.00, 100.00, 'Quotation: QTN-00009 - Total Area: 0.25 sq ft', '2026-08-20 10:32:41'),
(448, '2026-08-25', 3, 'ADJUSTMENT', 36, 0.25, 0.00, 438051.43, 0.00, 0.00, 'Sale Edit Reversal: SAL-00032', '2026-08-25 12:58:21'),
(449, '2026-08-12', 3, 'SALE', 36, 0.00, 0.25, 438051.18, 400.00, 100.00, 'Sale Invoice: SAL-00032 - Total Area: 0.25 sq ft', '2026-08-25 12:58:21'),
(450, '2026-08-29', 3, 'SALE', 37, 0.00, 168.00, 437883.18, 400.00, 67200.00, 'Sale Invoice: SAL-00033 - Total Area: 168 sq ft', '2026-08-29 11:34:02'),
(451, '2026-08-29', 3, 'SALE', 37, 0.00, 30.00, 437853.18, 400.00, 12000.00, 'Sale Invoice: SAL-00033 - Total Area: 30 sq ft', '2026-08-29 11:34:02'),
(452, '2026-08-29', 16, 'SALE', 37, 0.00, 30.00, 4132.05, 330.00, 9900.00, 'Sale Invoice: SAL-00033 - Total Area: 30 sq ft', '2026-08-29 11:34:02'),
(453, '2026-09-02', 3, 'QUOTATION', 17, 0.00, 0.25, 437852.93, 350.00, 87.50, 'Quotation: QTN-00010 - Total Area: 0.25 sq ft', '2026-09-02 09:57:14'),
(454, '2026-09-02', 16, 'QUOTATION', 17, 0.00, 0.25, 4131.80, 340.00, 85.00, 'Quotation: QTN-00010 - Total Area: 0.25 sq ft', '2026-09-02 09:57:14');

-- --------------------------------------------------------

--
-- Table structure for table `opening_stock`
--

CREATE TABLE `opening_stock` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_size_id` int(11) DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `pieces` decimal(15,2) DEFAULT 0.00,
  `unit_price` decimal(15,2) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `opening_stock`
--

INSERT INTO `opening_stock` (`id`, `product_id`, `product_size_id`, `quantity`, `pieces`, `unit_price`, `total_amount`, `date`, `remarks`, `created_by`, `created_at`) VALUES
(2, 2, NULL, 1500.00, 0.00, 2500.00, 3750000.00, '2026-06-10', NULL, 1, '2026-06-10 13:31:26'),
(3, 3, NULL, 433890.00, 0.00, 350.00, 151861500.00, '2026-06-12', 'Opening Stock: 1000 pieces, each 433.89 sq ft, total 433890 sq ft', 1, '2026-06-12 09:40:52'),
(4, 4, NULL, 4200.00, 0.00, 340.00, 1428000.00, '2026-06-17', 'Opening Stock: 50 pieces, each 84 sq ft, total 4200 sq ft', 1, '2026-06-17 13:45:52'),
(5, 5, NULL, 4200.00, 0.00, 315.00, 1323000.00, '2026-06-26', 'Opening Stock: 50 pieces, each 84 sq ft, total 4200 sq ft', 1, '2026-06-26 16:59:16'),
(6, 6, NULL, 8400.00, 0.00, 450.00, 3780000.00, '2026-07-23', 'Opening Stock: 100 pieces, each 84 sq ft, total 8400 sq ft', 1, '2026-07-23 06:49:37'),
(25, 16, 18, 1600.00, 50.00, 340.00, 544000.00, '2026-08-10', 'Opening Stock: 50 pieces of 4 x 8 ft, total 1600 sq ft', NULL, '2026-08-10 12:11:33'),
(26, 16, 19, 2560.00, 40.00, 340.00, 870400.00, '2026-08-10', 'Opening Stock: 40 pieces of 8 x 8 ft, total 2560 sq ft', NULL, '2026-08-10 12:11:33');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `category_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT 0.00,
  `sale_price` decimal(15,2) DEFAULT 0.00,
  `min_stock_alert` int(11) DEFAULT 0,
  `location_rack` varchar(100) DEFAULT NULL,
  `length_inch` decimal(10,2) DEFAULT 0.00,
  `width_inch` decimal(10,2) DEFAULT 0.00,
  `length_feet` decimal(10,2) DEFAULT 0.00,
  `width_feet` decimal(10,2) DEFAULT 0.00,
  `area_sqft` decimal(15,2) DEFAULT 0.00,
  `status` tinyint(1) DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_code`, `product_name`, `category_id`, `company_id`, `unit_id`, `supplier_id`, `purchase_price`, `sale_price`, `min_stock_alert`, `location_rack`, `length_inch`, `width_inch`, `length_feet`, `width_feet`, `area_sqft`, `status`, `created_at`, `updated_at`) VALUES
(2, 'FG0002', '9mm glass', 1, 1, 1, NULL, 2500.00, 3000.00, 150, 'Rack2', 2499.96, 2499.96, 208.33, 208.33, 43401.39, 1, '2026-06-10 13:31:26', '2026-08-20 11:53:56'),
(3, 'FG0003', '12mm clear glass', 1, 1, 1, NULL, 350.00, 400.00, 100, 'Rack3', 249.96, 249.96, 20.83, 20.83, 433.89, 1, '2026-06-12 09:40:52', '2026-08-20 11:53:56'),
(4, 'FG0004', '6mm white', 1, 1, 2, NULL, 340.00, 330.00, 100, '', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-06-17 13:45:52', '2026-08-20 11:53:56'),
(5, 'FG0005', '6mm brown', 1, 1, 2, NULL, 315.00, 320.00, 0, '', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-06-26 16:59:16', '2026-08-20 11:53:56'),
(6, 'FG0006', '8mm white', 1, 1, 2, 2, 450.00, 410.00, 0, '', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-07-23 06:49:37', '2026-07-23 06:49:37'),
(16, 'FG0007', '6mm black', 1, 1, 2, NULL, 340.00, 330.00, 100, '', 0.00, 0.00, 4.00, 8.00, 32.00, 1, '2026-08-10 12:11:33', '2026-08-10 12:11:33');

-- --------------------------------------------------------

--
-- Table structure for table `product_sizes`
--

CREATE TABLE `product_sizes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size_label` varchar(100) DEFAULT NULL,
  `length_inch` decimal(10,2) DEFAULT 0.00,
  `width_inch` decimal(10,2) DEFAULT 0.00,
  `length_feet` decimal(10,2) DEFAULT 0.00,
  `width_feet` decimal(10,2) DEFAULT 0.00,
  `area_sqft` decimal(15,2) DEFAULT 0.00,
  `purchase_rate` decimal(15,2) DEFAULT NULL,
  `sale_rate` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_sizes`
--

INSERT INTO `product_sizes` (`id`, `product_id`, `size_label`, `length_inch`, `width_inch`, `length_feet`, `width_feet`, `area_sqft`, `purchase_rate`, `sale_rate`, `created_at`) VALUES
(18, 16, '4 x 8 ft', 0.00, 0.00, 4.00, 8.00, 32.00, NULL, NULL, '2026-08-10 12:11:33'),
(19, 16, '8 x 8 ft', 0.00, 0.00, 8.00, 8.00, 64.00, NULL, NULL, '2026-08-10 12:11:33');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_details`
--

CREATE TABLE `purchase_details` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `client_height` decimal(10,2) DEFAULT 0.00,
  `client_width` decimal(10,2) DEFAULT 0.00,
  `std_height` varchar(50) DEFAULT NULL,
  `std_width` varchar(50) DEFAULT NULL,
  `uom` varchar(20) DEFAULT 'Inch',
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `retail_price` decimal(15,2) DEFAULT 0.00,
  `area` decimal(15,2) DEFAULT 0.00,
  `amount` decimal(15,2) DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `net_amount` decimal(15,2) DEFAULT 0.00,
  `refunded_qty` decimal(15,2) DEFAULT 0.00,
  `refunded_amount` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_details`
--

INSERT INTO `purchase_details` (`id`, `purchase_id`, `product_id`, `client_height`, `client_width`, `std_height`, `std_width`, `uom`, `quantity`, `unit_price`, `retail_price`, `area`, `amount`, `discount_percentage`, `discount_amount`, `net_amount`, `refunded_qty`, `refunded_amount`) VALUES
(1, 1, 1, 25.00, 25.00, '30', '30', 'Inch', 1.00, 1500.00, 0.00, 6.25, 9375.00, 0.00, 0.00, 9375.00, 0.00, 0.00),
(2, 2, 3, 3.00, 7.00, '6', '12', '', 1.00, 350.00, 0.00, 0.50, 175.00, 0.00, 0.00, 175.00, 0.00, 0.00),
(3, 3, 3, 96.00, 144.00, '96', '144', '', 15.00, 350.00, 0.00, 96.00, 504000.00, 0.00, 0.00, 504000.00, 0.00, 0.00),
(4, 3, 1, 84.00, 144.00, '84', '144', '', 5.00, 250.00, 0.00, 84.00, 105000.00, 0.00, 0.00, 105000.00, 0.00, 0.00),
(5, 3, 2, 78.00, 144.00, '78', '144', '', 12.00, 500.00, 0.00, 78.00, 468000.00, 0.00, 0.00, 468000.00, 0.00, 0.00),
(6, 4, 3, 96.00, 144.00, '96', '144', '', 15.00, 350.00, 0.00, 96.00, 504000.00, 0.00, 0.00, 504000.00, 0.00, 0.00),
(7, 4, 1, 84.00, 144.00, '84', '144', '', 5.00, 250.00, 0.00, 84.00, 105000.00, 0.00, 0.00, 105000.00, 0.00, 0.00),
(8, 4, 2, 78.00, 144.00, '78', '144', '', 12.00, 500.00, 0.00, 78.00, 468000.00, 0.00, 0.00, 468000.00, 0.00, 0.00),
(9, 5, 3, 12.00, 84.00, '12', '84', '', 12.00, 350.00, 0.00, 7.00, 29400.00, 0.00, 0.00, 29400.00, 0.00, 0.00),
(10, 5, 4, 36.00, 78.00, '36', '78', '', 34.00, 340.00, 0.00, 19.50, 225420.00, 0.00, 0.00, 225420.00, 0.00, 0.00),
(11, 5, 5, 72.00, 29.00, '72', '30', '', 10.00, 315.00, 0.00, 15.00, 47250.00, 0.00, 0.00, 47250.00, 0.00, 0.00),
(12, 6, 3, 84.00, 144.00, '84', '144', '', 48.00, 350.00, 0.00, 84.00, 1411200.00, 0.00, 0.00, 1411200.00, 0.00, 0.00),
(13, 7, 3, 84.00, 144.00, '84', '144', '', 45.00, 350.00, 0.00, 84.00, 1323000.00, 0.00, 0.00, 1323000.00, 0.00, 0.00),
(14, 8, 3, 84.00, 144.00, '84', '144', '', 59.00, 350.00, 0.00, 84.00, 1734600.00, 0.00, 0.00, 1734600.00, 0.00, 0.00),
(15, 9, 5, 15.00, 50.00, '18', '54', '', 1.00, 315.00, 0.00, 6.75, 2126.25, 0.00, 0.00, 2126.25, 0.00, 0.00),
(16, 10, 5, 15.00, 50.00, '18', '54', '', 1.00, 315.00, 0.00, 6.75, 2126.25, 0.00, 0.00, 2126.25, 0.00, 0.00),
(17, 11, 3, 12.00, 14.00, '12', '18', 'Inch', 1.00, 350.00, 0.00, 1.50, 525.00, 0.00, 0.00, 525.00, 0.00, 0.00),
(18, 12, 16, 4.00, 4.00, '6', '6', 'Inch', 9.99, 340.00, 0.00, 0.25, 849.15, 0.00, 0.00, 849.15, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_master`
--

CREATE TABLE `purchase_master` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `purchase_date` date NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `other_charges` decimal(15,2) DEFAULT 0.00,
  `grand_total` decimal(15,2) DEFAULT 0.00,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `remaining_amount` decimal(15,2) DEFAULT 0.00,
  `payment_type` enum('cash','bank','credit') DEFAULT 'credit',
  `bank_account_id` int(11) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `refund_status` enum('none','partial','full') DEFAULT 'none',
  `refund_amount` decimal(15,2) DEFAULT 0.00,
  `refund_date` date DEFAULT NULL,
  `refund_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_master`
--

INSERT INTO `purchase_master` (`id`, `invoice_no`, `purchase_date`, `supplier_id`, `subtotal`, `discount_amount`, `discount_percentage`, `other_charges`, `grand_total`, `paid_amount`, `remaining_amount`, `payment_type`, `bank_account_id`, `reference_no`, `remarks`, `status`, `created_by`, `created_at`, `refund_status`, `refund_amount`, `refund_date`, `refund_reason`) VALUES
(1, 'PUR-00001', '2026-06-11', NULL, 9375.00, 0.00, 0.00, 0.00, 9375.00, 0.00, 9375.00, 'credit', 0, '', '', 1, 1, '2026-06-11 06:43:30', 'none', 0.00, NULL, NULL),
(2, 'PUR-00002', '2026-06-12', NULL, 175.00, 0.00, 0.00, 0.00, 175.00, 0.00, 175.00, 'credit', 0, '', '', 1, 1, '2026-06-12 09:50:55', 'none', 0.00, NULL, NULL),
(3, 'PUR-00003', '2026-06-17', NULL, 1077000.00, 129240.00, 12.00, 0.00, 947760.00, 0.00, 947760.00, 'credit', 0, '', '', 1, 1, '2026-06-17 12:58:30', 'none', 0.00, NULL, NULL),
(4, 'PUR-00004', '2026-06-17', NULL, 1077000.00, 129240.00, 12.00, 0.00, 947760.00, 0.00, 947760.00, 'credit', 0, '', '', 1, 1, '2026-06-17 12:58:53', 'none', 0.00, NULL, NULL),
(5, 'PUR-00005', '2026-07-14', NULL, 302070.00, 0.00, 0.00, 0.00, 302070.00, 0.00, 302070.00, 'credit', 0, '23345674', '', 1, 1, '2026-07-14 18:28:18', 'none', 0.00, NULL, NULL),
(6, 'PUR-00006', '2026-07-14', 2, 1411200.00, 141120.00, 10.00, 0.00, 1270080.00, 0.00, 1270080.00, 'credit', 0, '9087799', '', 1, 1, '2026-07-14 18:50:36', 'none', 0.00, NULL, NULL),
(7, 'PUR-00007', '2026-07-20', 2, 1323000.00, 171990.00, 13.00, 0.00, 1151010.00, 0.00, 1151010.00, 'credit', 0, '', '', 1, 1, '2026-07-20 18:13:37', 'none', 0.00, NULL, NULL),
(8, 'PUR-00008', '2026-07-26', 2, 1734600.00, 208152.00, 12.00, 0.00, 1526448.00, 0.00, 1526448.00, 'credit', 0, '', '', 1, 1, '2026-07-26 18:27:56', 'none', 0.00, NULL, NULL),
(9, 'PUR-00009', '2026-07-30', NULL, 2126.25, 0.00, 0.00, 0.00, 2126.25, 0.00, 2126.25, 'credit', 0, '', '', 1, 1, '2026-07-30 11:05:29', 'none', 0.00, NULL, NULL),
(10, 'PUR-00010', '2026-07-30', NULL, 2126.25, 0.00, 0.00, 0.00, 2126.25, 20.00, 2106.25, 'cash', 0, '4789641', 'abc', 1, 1, '2026-07-30 11:05:58', 'none', 0.00, NULL, NULL),
(11, 'PUR-00011', '2026-07-30', 2, 525.00, 0.00, 0.00, 0.00, 525.00, 0.00, 525.00, 'credit', 0, '4789641', 'abc', 1, 1, '2026-07-30 11:09:20', 'none', 0.00, NULL, NULL),
(12, 'PUR-00012', '2026-08-12', 2, 849.15, 0.00, 0.00, 0.00, 849.15, 0.00, 849.15, 'credit', 0, '', '', 1, 1, '2026-08-12 05:38:24', 'none', 0.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `quotation_details`
--

CREATE TABLE `quotation_details` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `client_height` decimal(10,2) DEFAULT 0.00,
  `client_width` decimal(10,2) DEFAULT 0.00,
  `client_size` varchar(100) DEFAULT NULL,
  `multiple_of` int(11) DEFAULT 6,
  `std_height` varchar(50) DEFAULT NULL,
  `std_width` varchar(50) DEFAULT NULL,
  `uom` varchar(20) DEFAULT 'Inch',
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `area` decimal(15,2) DEFAULT 0.00,
  `rate` decimal(15,2) DEFAULT 0.00,
  `amount` decimal(15,2) DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `net_amount` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotation_details`
--

INSERT INTO `quotation_details` (`id`, `quotation_id`, `product_id`, `client_height`, `client_width`, `client_size`, `multiple_of`, `std_height`, `std_width`, `uom`, `quantity`, `unit_price`, `area`, `rate`, `amount`, `discount_percentage`, `discount_amount`, `net_amount`) VALUES
(2, 5, 1, 7.00, 9.00, NULL, 6, '7', '9', 'Inch', 10.00, 2000.00, 0.44, 0.00, 8800.00, 0.00, 0.00, 8800.00),
(4, 7, 2, 84.00, 96.00, NULL, 6, '8', '9', 'Inch', 1.00, 3000.00, 0.50, 0.00, 1500.00, 0.00, 0.00, 1500.00),
(5, 8, 3, 60.00, 72.00, NULL, 6, '5', '6', 'Inch', 2.00, 400.00, 30.00, 0.00, 24000.00, 0.00, 0.00, 24000.00),
(9, 12, 1, 6.00, 6.00, NULL, 6, '0.5', '0.5', 'Inch', 2.00, 50.00, 0.25, 0.00, 25.00, 0.00, 0.00, 25.00),
(11, 14, 5, 4.00, 4.00, NULL, 6, '0.33', '0.33', 'Inch', 1.00, 320.00, 0.11, 0.00, 35.20, 0.00, 0.00, 35.20),
(12, 14, 16, 8.00, 8.00, NULL, 6, '0.67', '0.67', 'Inch', 1.00, 330.00, 0.45, 0.00, 148.50, 0.00, 0.00, 148.50),
(13, 14, 3, 6.00, 6.00, NULL, 6, '0.5', '0.5', 'Inch', 1.00, 400.00, 0.25, 0.00, 100.00, 0.00, 0.00, 100.00),
(14, 15, 3, 4.00, 4.00, NULL, 6, '0.33', '0.33', 'Inch', 1.00, 400.00, 0.11, 0.00, 44.00, 0.00, 0.00, 44.00),
(15, 16, 3, 6.00, 6.00, NULL, 6, '0.5', '0.5', 'Inch', 1.00, 400.00, 0.25, 0.00, 100.00, 0.00, 0.00, 100.00),
(16, 17, 3, 6.00, 6.00, '6 x 6', 6, '6', '6', 'Inch', 1.00, 350.00, 0.25, 350.00, 87.50, 0.00, 0.00, 87.50),
(17, 17, 16, 6.00, 6.00, '6 x 6', 6, '6', '6', 'Inch', 1.00, 340.00, 0.25, 340.00, 85.00, 0.00, 0.00, 85.00);

-- --------------------------------------------------------

--
-- Table structure for table `quotation_master`
--

CREATE TABLE `quotation_master` (
  `id` int(11) NOT NULL,
  `quotation_no` varchar(50) NOT NULL,
  `quotation_date` date NOT NULL,
  `customer_id` int(11) NOT NULL,
  `valid_until` date DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `other_charges` decimal(15,2) DEFAULT 0.00,
  `grand_total` decimal(15,2) DEFAULT 0.00,
  `received_amount` decimal(15,2) DEFAULT 0.00,
  `remaining_amount` decimal(15,2) DEFAULT 0.00,
  `payment_type` enum('cash','bank','credit','partial') DEFAULT 'credit',
  `bank_account_id` int(11) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('draft','hold','pending','approved','rejected','converted') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotation_master`
--

INSERT INTO `quotation_master` (`id`, `quotation_no`, `quotation_date`, `customer_id`, `valid_until`, `subtotal`, `discount_percentage`, `discount_amount`, `other_charges`, `grand_total`, `received_amount`, `remaining_amount`, `payment_type`, `bank_account_id`, `reference_no`, `remarks`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(5, 'QTN-00002', '2026-06-12', 11, '2026-07-12', 8800.00, 0.00, 0.00, 0.00, 8800.00, 0.00, 8800.00, 'credit', NULL, '', '', 'draft', 1, '2026-06-12 07:42:18', '2026-09-02 06:59:12'),
(7, 'QTN-00004', '2026-07-26', 1, '2026-08-25', 1500.00, 0.00, 0.00, 0.00, 1500.00, 0.00, 1500.00, 'credit', NULL, '', '', 'draft', 1, '2026-07-26 18:19:06', '2026-09-02 06:59:12'),
(8, 'QTN-00005', '2026-07-30', 17, '2026-08-29', 24000.00, 0.00, 0.00, 0.00, 24000.00, 0.00, 24000.00, 'credit', NULL, '', '', 'draft', 1, '2026-07-30 10:40:37', '2026-09-02 06:59:12'),
(12, 'QTN-00006', '2026-08-11', 1, '2026-09-10', 100.00, 0.00, 0.00, 0.00, 100.00, 0.00, 100.00, 'credit', NULL, 'TEST-HOLD', 'hold roundtrip test', 'draft', 1, '2026-08-11 08:10:14', '2026-09-02 06:59:12'),
(14, 'QTN-00007', '2026-08-12', 14, '2026-09-11', 283.70, 0.00, 0.00, 0.00, 283.70, 0.00, 283.70, 'credit', NULL, '4789641', '', 'draft', 1, '2026-08-12 06:39:56', '2026-09-02 06:59:12'),
(15, 'QTN-00008', '2026-08-20', 8, '2026-09-19', 44.00, 0.00, 0.00, 0.00, 44.00, 0.00, 44.00, 'credit', NULL, '7777', '', 'draft', 1, '2026-08-20 10:09:26', '2026-09-02 06:59:12'),
(16, 'QTN-00009', '2026-08-20', 8, '2026-09-19', 100.00, 0.00, 0.00, 0.00, 100.00, 0.00, 100.00, 'credit', NULL, '07777', '', 'draft', 1, '2026-08-20 10:32:41', '2026-09-02 06:59:12'),
(17, 'QTN-00010', '2026-09-02', 8, '2026-10-02', 172.50, 0.00, 0.00, 0.00, 172.50, 0.00, 172.50, 'credit', NULL, '', '', 'draft', 1, '2026-09-02 09:57:14', '2026-09-02 09:57:14');

-- --------------------------------------------------------

--
-- Table structure for table `sale_details`
--

CREATE TABLE `sale_details` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `client_height` decimal(10,2) DEFAULT 0.00,
  `client_width` decimal(10,2) DEFAULT 0.00,
  `client_size` varchar(100) DEFAULT NULL,
  `multiple_of` int(11) DEFAULT 6,
  `std_height` varchar(50) DEFAULT NULL,
  `std_width` varchar(50) DEFAULT NULL,
  `uom` varchar(20) DEFAULT 'Inch',
  `quantity` decimal(15,2) NOT NULL,
  `area` decimal(15,2) DEFAULT 0.00,
  `raw_area` decimal(15,2) DEFAULT 0.00,
  `total_area` decimal(15,2) DEFAULT 0.00,
  `rate` decimal(15,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `amount` decimal(15,2) DEFAULT 0.00,
  `refunded_qty` decimal(15,2) DEFAULT 0.00,
  `refunded_amount` decimal(15,2) DEFAULT 0.00
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
(23, 9, 1, 34.00, 78.00, '34 x 78', 6, '36', '78', '', 1.00, 19.50, 0.00, 0.00, 2000.00, 0.00, 39000.00, 0.00, 0.00),
(24, 10, 3, 35.70, 88.20, '35.7 x 88.2', 6, '36', '90', '', 1.00, 22.50, 0.00, 0.00, 660.00, 0.00, 14850.00, 0.00, 0.00),
(25, 10, 3, 40.70, 88.20, '40.7 x 88.2', 6, '42', '90', '', 1.00, 26.25, 0.00, 0.00, 660.00, 0.00, 17325.00, 0.00, 0.00),
(26, 10, 3, 31.50, 87.70, '31.5 x 87.7', 3, '33', '90', '', 1.00, 20.63, 0.00, 0.00, 660.00, 0.00, 13612.50, 0.00, 0.00),
(27, 10, 3, 22.50, 88.40, '22.5 x 88.4', 6, '24', '90', '', 1.00, 15.00, 0.00, 0.00, 660.00, 0.00, 9900.00, 0.00, 0.00),
(28, 10, 3, 8.50, 90.00, '8.5 x 90', 3, '9', '90', '', 2.00, 11.25, 0.00, 0.00, 660.00, 0.00, 7425.00, 0.00, 0.00),
(29, 10, 3, 35.40, 78.00, '35.4 x 78', 6, '36', '78', '', 1.00, 19.50, 0.00, 0.00, 660.00, 0.00, 12870.00, 0.00, 0.00),
(30, 10, 3, 29.40, 78.00, '29.4 x 78', 6, '30', '78', '', 1.00, 16.25, 0.00, 0.00, 660.00, 0.00, 10725.00, 0.00, 0.00),
(31, 10, 3, 12.00, 68.20, '12 x 68.2', 6, '12', '72', '', 1.00, 6.00, 0.00, 0.00, 660.00, 0.00, 3960.00, 0.00, 0.00),
(40, 12, 3, 9.00, 45.00, '9 x 45', 3, '9', '45', '', 2.00, 5.63, 0.00, 0.00, 400.00, 0.00, 2250.00, 0.00, 0.00),
(41, 13, 3, 9.00, 45.00, '9 x 45', 6, '12', '48', '', 2.00, 8.00, 0.00, 0.00, 400.00, 0.00, 3200.00, 0.00, 0.00),
(42, 14, 3, 7.00, 45.00, '7 x 45', 3, '9', '45', '', 2.00, 5.63, 0.00, 0.00, 400.00, 0.00, 2250.00, 0.00, 0.00),
(44, 15, 3, 7.00, 42.00, '7.00 x 42', 3, '9', '42', 'Inch', 2.00, 2.63, 0.00, 5.25, 400.00, 0.00, 2100.00, 0.00, 0.00),
(45, 16, 3, 36.00, 84.00, '36 x 84', 6, '36', '84', '', 3.00, 63.00, 0.00, 0.00, 400.00, 0.00, 25200.00, 0.00, 0.00),
(46, 17, 3, 34.00, 23.00, '34 x 23', 6, '36', '24', '', 6.00, 36.00, 0.00, 0.00, 400.00, 0.00, 14400.00, 0.00, 0.00),
(47, 18, 3, 25.00, 78.00, '25 x 78', 6, '30', '78', '', 8.00, 130.00, 0.00, 0.00, 400.00, 0.00, 52000.00, 0.00, 0.00),
(194, 24, 3, 36.00, 84.00, '36 x 84', 6, '36', '84', '', 156.00, 3276.00, 0.00, 0.00, 400.00, 0.00, 1310400.00, 0.00, 0.00),
(195, 25, 3, 45.00, 78.00, '45 x 78', 6, '48', '78', '', 120.00, 3120.00, 0.00, 0.00, 400.00, 0.00, 1248000.00, 0.00, 0.00),
(196, 26, 3, 23.00, 24.00, '23 x 24', 6, '24', '24', '', 1.00, 4.00, 0.00, 0.00, 400.00, 0.00, 1600.00, 0.00, 0.00),
(197, 26, 3, 25.00, 75.00, '25 x 75', 6, '30', '78', '', 1.00, 16.25, 0.00, 0.00, 400.00, 0.00, 6500.00, 0.00, 0.00),
(198, 26, 3, 23.00, 45.00, '23 x 45', 6, '24', '48', '', 1.00, 8.00, 0.00, 0.00, 400.00, 0.00, 3200.00, 0.00, 0.00),
(199, 26, 3, 75.00, 78.00, '75 x 78', 6, '78', '78', '', 1.00, 42.25, 0.00, 0.00, 400.00, 0.00, 16900.00, 0.00, 0.00),
(200, 26, 3, 84.00, 78.00, '84 x 78', 6, '84', '78', '', 1.00, 45.50, 0.00, 0.00, 400.00, 0.00, 18200.00, 0.00, 0.00),
(201, 26, 3, 12.00, 120.00, '12 x 120', 6, '12', '120', '', 1.00, 10.00, 0.00, 0.00, 400.00, 0.00, 4000.00, 0.00, 0.00),
(202, 26, 3, 72.00, 84.00, '72 x 84', 6, '72', '84', '', 1.00, 42.00, 0.00, 0.00, 400.00, 0.00, 16800.00, 0.00, 0.00),
(203, 26, 5, 78.00, 96.00, '78 x 96', 6, '78', '96', '', 56.00, 2912.00, 0.00, 0.00, 320.00, 0.00, 931840.00, 0.00, 0.00),
(204, 26, 5, 17.00, 35.00, '17 x 35', 6, '18', '36', '', 10.00, 45.00, 0.00, 0.00, 320.00, 0.00, 14400.00, 0.00, 0.00),
(205, 27, 4, 34.00, 45.00, '034 x 045', 6, '36', '48', '', 1.00, 12.00, 0.00, 0.00, 330.00, 0.00, 3960.00, 0.00, 0.00),
(206, 27, 5, 35.00, 45.00, '35 x 45', 6, '36', '48', '', 1.00, 12.00, 0.00, 0.00, 320.00, 0.00, 3840.00, 0.00, 0.00),
(207, 27, 3, 45.00, 66.00, '45 x 66', 6, '48', '66', '', 1.00, 22.00, 0.00, 0.00, 400.00, 0.00, 8800.00, 0.00, 0.00),
(208, 28, 4, 34.00, 45.00, '034 x 045', 6, '36', '48', '', 1.00, 12.00, 0.00, 0.00, 330.00, 0.00, 3960.00, 0.00, 0.00),
(209, 28, 5, 35.00, 45.00, '35 x 45', 6, '36', '48', '', 1.00, 12.00, 0.00, 0.00, 320.00, 0.00, 3840.00, 0.00, 0.00),
(210, 28, 3, 45.00, 66.00, '45 x 66', 6, '48', '66', '', 1.00, 22.00, 0.00, 0.00, 400.00, 0.00, 8800.00, 0.00, 0.00),
(211, 29, 5, 10.00, 10.00, '10 x 10', 6, '12', '12', '', 1.00, 1.00, 0.00, 0.00, 320.00, 0.00, 320.00, 0.00, 0.00),
(212, 29, 5, 10.00, 10.00, '10 x 10', 6, '12', '12', '', 1.00, 1.00, 0.00, 0.00, 320.00, 0.00, 320.00, 0.00, 0.00),
(213, 30, 5, 10.00, 10.00, '10 x 10', 6, '12', '12', '', 1.00, 1.00, 0.00, 0.00, 320.00, 0.00, 320.00, 0.00, 0.00),
(214, 30, 5, 10.00, 10.00, '10 x 10', 6, '12', '12', '', 1.00, 1.00, 0.00, 0.00, 320.00, 0.00, 320.00, 0.00, 0.00),
(217, 32, 3, 12.00, 121.00, '12 x 121', 6, '12', '126', '', 42.00, 441.00, 0.00, 0.00, 400.00, 0.00, 176400.00, 0.00, 0.00),
(218, 31, 1, 10.00, 10.00, '10.00 x 10.00', 6, '12', '12', '', 1.00, 1.00, 0.00, 0.00, 2000.00, 0.00, 2000.00, 0.00, 0.00),
(219, 31, 3, 10.00, 10.00, '10.00 x 10.00', 6, '12', '12', '', 1.00, 1.00, 0.00, 0.00, 400.00, 0.00, 400.00, 0.00, 0.00),
(220, 33, 5, 21.40, 42.40, '21.4 x 42.4', 3, '24', '45', '', 2.00, 15.00, 0.00, 0.00, 330.00, 0.00, 4950.00, 0.00, 0.00),
(221, 33, 5, 45.40, 65.10, '45.4 x 65.1', 6, '48', '66', '', 2.00, 44.00, 0.00, 0.00, 330.00, 0.00, 14520.00, 0.00, 0.00),
(222, 33, 5, 29.70, 65.10, '29.7 x 65.1', 6, '30', '66', '', 2.00, 27.50, 0.00, 0.00, 330.00, 0.00, 9075.00, 0.00, 0.00),
(223, 34, 3, 45.00, 72.00, '045 x 072', 6, '48', '72', '', 2.00, 48.00, 0.00, 0.00, 400.00, 0.00, 19200.00, 0.00, 0.00),
(224, 35, 3, 84.00, 84.00, '084 x 84', 6, '84', '84', '', 1.00, 49.00, 0.00, 0.00, 400.00, 0.00, 19600.00, 0.00, 0.00),
(226, 36, 3, 6.00, 4.00, '6.00 x 4.00', 6, '6', '6', '', 1.00, 0.25, 0.00, 0.00, 400.00, 0.00, 100.00, 0.00, 0.00),
(227, 37, 3, 84.00, 96.00, '84 x 96', 6, '84', '96', '', 3.00, 168.00, 0.00, 0.00, 400.00, 0.00, 67200.00, 0.00, 0.00),
(228, 37, 3, 72.00, 60.00, '072 x 60', 6, '72', '60', '', 1.00, 30.00, 0.00, 0.00, 400.00, 0.00, 12000.00, 0.00, 0.00),
(229, 37, 16, 25.00, 72.00, '25 x 72', 6, '30', '72', '', 2.00, 30.00, 0.00, 0.00, 330.00, 0.00, 9900.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `sale_master`
--

CREATE TABLE `sale_master` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `sale_date` date NOT NULL,
  `customer_id` int(11) NOT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `other_charges` decimal(15,2) DEFAULT 0.00,
  `grand_total` decimal(15,2) DEFAULT 0.00,
  `received_amount` decimal(15,2) DEFAULT 0.00,
  `remaining_amount` decimal(15,2) DEFAULT 0.00,
  `payment_type` enum('cash','bank','credit','partial') DEFAULT 'cash',
  `bank_account_id` int(11) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `refund_status` enum('none','partial','full') DEFAULT 'none',
  `refund_amount` decimal(15,2) DEFAULT 0.00,
  `refund_date` date DEFAULT NULL,
  `refund_reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
(9, 'SAL-00009', '2026-06-15', 9, 47702.78, 0.00, 0.00, 9000.00, 56702.78, 0.00, 56702.78, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-15 15:10:33'),
(10, 'SAL-00010', '2026-06-17', 16, 90667.50, 0.00, 0.00, 0.00, 90667.50, 0.00, 90667.50, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-17 11:20:48'),
(12, 'SAL-00011', '2026-06-17', 7, 2250.00, 0.00, 0.00, 0.00, 2250.00, 2250.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-17 11:49:31'),
(13, 'SAL-00012', '2026-06-17', 6, 3200.00, 0.00, 0.00, 0.00, 3200.00, 3200.00, 0.00, 'cash', 0, '', 'no polish', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-17 11:50:51'),
(14, 'SAL-00013', '2026-06-18', 16, 2250.00, 0.00, 0.00, 0.00, 2250.00, 2250.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-18 11:19:20'),
(15, 'SAL-00014', '2026-06-18', 16, 2100.00, 0.00, 0.00, 0.00, 2100.00, 0.00, 2100.00, 'credit', 0, '', 'polished', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-18 11:33:45'),
(16, 'SAL-00015', '2026-06-24', 16, 25200.00, 0.00, 0.00, 0.00, 25200.00, 25200.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-24 14:47:38'),
(17, 'SAL-00016', '2026-06-24', 7, 14400.00, 0.00, 0.00, 0.00, 14400.00, 14400.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-24 16:14:57'),
(18, 'SAL-00017', '2026-06-24', 7, 52000.00, 0.00, 0.00, 0.00, 52000.00, 52000.00, 0.00, 'cash', 0, '', 'no polish', 1, 'none', 0.00, NULL, NULL, 1, '2026-06-24 16:17:31'),
(24, 'SAL-00020', '2026-07-14', 16, 1310400.00, 0.00, 0.00, 0.00, 1310400.00, 0.00, 1310400.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-07-14 18:05:26'),
(25, 'SAL-00021', '2026-07-14', 1, 1248000.00, 0.00, 0.00, 0.00, 1248000.00, 0.00, 1248000.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-07-14 18:07:22'),
(26, 'SAL-00022', '2026-07-14', 1, 1013440.00, 0.00, 0.00, 0.00, 1013440.00, 0.00, 1013440.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-07-14 18:11:56'),
(27, 'SAL-00023', '2026-07-16', 6, 16600.00, 0.00, 0.00, 0.00, 16600.00, 5000.00, 11600.00, 'partial', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-07-16 12:51:39'),
(28, 'SAL-00024', '2026-07-16', 6, 16600.00, 0.00, 0.00, 0.00, 16600.00, 0.00, 16600.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-07-16 12:54:22'),
(29, 'SAL-00025', '2026-07-16', 6, 640.00, 0.00, 0.00, 0.00, 640.00, 640.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-07-16 13:06:55'),
(30, 'SAL-00026', '2026-07-16', 6, 640.00, 0.00, 0.00, 0.00, 640.00, 640.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-07-16 13:09:11'),
(31, 'SAL-00027', '2026-07-16', 6, 2400.00, 0.00, 0.00, 0.00, 2400.00, 2400.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-07-16 13:40:42'),
(32, 'SAL-00028', '2026-07-20', 7, 176400.00, 0.00, 0.00, 0.00, 176400.00, 176400.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-07-20 18:10:41'),
(33, 'SAL-00029', '2026-07-23', 6, 28545.00, 0.00, 0.00, 500.00, 29045.00, 29045.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-07-23 06:36:06'),
(34, 'SAL-00030', '2026-07-26', 7, 19200.00, 0.00, 0.00, 0.00, 19200.00, 19200.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-07-26 18:52:44'),
(35, 'SAL-00031', '2026-07-30', 7, 19600.00, 0.00, 0.00, 0.00, 19600.00, 19600.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-07-30 11:51:12'),
(36, 'SAL-00032', '2026-08-12', 6, 100.00, 0.00, 0.00, 0.00, 100.00, 100.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-12 05:36:51'),
(37, 'SAL-00033', '2026-08-29', 6, 89100.00, 0.00, 0.00, 0.00, 89100.00, 89100.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-29 11:34:02');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `supplier_code` varchar(50) NOT NULL,
  `supplier_name` varchar(200) NOT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `mobile` varchar(20) NOT NULL,
  `cnic` varchar(20) DEFAULT NULL,
  `ntn` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `opening_balance` decimal(15,2) DEFAULT 0.00,
  `balance_type` enum('payable','receivable') DEFAULT 'payable' COMMENT 'payable=Company owes supplier, receivable=Supplier owes company',
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `status` tinyint(1) DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `supplier_code`, `supplier_name`, `company_name`, `contact_person`, `mobile`, `cnic`, `ntn`, `email`, `address`, `opening_balance`, `balance_type`, `current_balance`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(2, 'SUP-0002', 'Ghani glass', 'ghani glass ltd', 'Talha Arshad', '03217917178', '', '', 'talhaarshadatr@gmail.com', 'P/O raja wala chak No. 22/10r kacha khuh', 2000000.00, 'payable', 5948012.15, 1, '', '2026-07-14 18:47:21', '2026-09-02 09:42:25');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_ledger`
--

CREATE TABLE `supplier_ledger` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `reference_type` enum('OPENING','PURCHASE','PAYMENT','ADJUSTMENT') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0.00 COMMENT 'Supplier owes company',
  `credit` decimal(15,2) DEFAULT 0.00 COMMENT 'Company owes supplier',
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_ledger`
--

INSERT INTO `supplier_ledger` (`id`, `date`, `supplier_id`, `reference_type`, `reference_id`, `description`, `debit`, `credit`, `balance`, `created_at`) VALUES
(7, '2026-07-14', 2, 'PURCHASE', 6, 'Purchase Invoice: PUR-00006', 0.00, 1270080.00, 4270080.00, '2026-07-14 18:50:36'),
(8, '2026-07-20', 2, 'PURCHASE', 7, 'Purchase Invoice: PUR-00007', 0.00, 1151010.00, 5421090.00, '2026-07-20 18:13:37'),
(9, '2026-07-26', 2, 'PURCHASE', 8, 'Purchase Invoice: PUR-00008', 0.00, 1526448.00, 6947538.00, '2026-07-26 18:27:56'),
(12, '2026-07-30', 2, 'PURCHASE', 11, 'Purchase Invoice: PUR-00011', 0.00, 525.00, 6948063.00, '2026-07-30 11:09:20'),
(17, '2026-08-12', 2, 'PURCHASE', 12, 'Purchase Invoice: PUR-00012', 0.00, 849.15, 6948912.15, '2026-08-12 05:38:24'),
(18, '2026-08-20', 2, 'OPENING', 2, 'Opening Balance - Payable (Company owes supplier)', 0.00, 2000000.00, 2000000.00, '2026-08-20 11:55:58'),
(19, '2026-08-25', 2, 'PAYMENT', 5, 'Payment made to supplier - Cash', 500.00, 0.00, 5948412.15, '2026-08-25 11:34:47'),
(24, '2026-08-28', 2, 'ADJUSTMENT', 0, '[Manual Debit] abc', 500.00, 0.00, 5948412.15, '2026-08-28 11:50:35'),
(30, '2026-09-02', 2, 'ADJUSTMENT', 0, '[Manual Credit] abc', 0.00, 50.00, 5948462.15, '2026-09-02 09:41:36'),
(31, '2026-09-02', 2, 'ADJUSTMENT', 0, '[Manual Debit] da', 500.00, 0.00, 5947962.15, '2026-09-02 09:42:01'),
(32, '2026-09-02', 2, 'ADJUSTMENT', 0, '[Manual Credit] a', 0.00, 50.00, 5948012.15, '2026-09-02 09:42:25');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payments`
--

CREATE TABLE `supplier_payments` (
  `id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `payment_method` enum('cash','bank') NOT NULL,
  `bank_account_id` int(11) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `purchase_invoice_no` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_payments`
--

INSERT INTO `supplier_payments` (`id`, `payment_date`, `supplier_id`, `payment_method`, `bank_account_id`, `reference_no`, `purchase_invoice_no`, `amount`, `remarks`, `created_by`, `created_at`) VALUES
(3, '2026-08-03', NULL, 'cash', 0, '', NULL, 50000.00, 'abc', 1, '2026-08-03 10:51:07'),
(4, '2026-08-10', NULL, 'cash', 0, '', NULL, 5000.00, '', 1, '2026-08-10 05:28:00'),
(5, '2026-08-25', 2, 'cash', 0, '', '', 500.00, '', 1, '2026-08-25 11:34:47');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `unit_name` varchar(50) NOT NULL,
  `short_name` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Plain text password as per requirements',
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','manager','cashier') DEFAULT 'cashier',
  `status` tinyint(1) DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`, `last_login`, `created_at`) VALUES
(1, 'admin', 'admin123', 'Administrator', 'admin@faysalglass.com', 'admin', 1, '2026-09-02 17:31:17', '2026-06-05 11:34:23'),
(2, 'manager', 'manager123', 'Store Manager', 'manager@faysalglass.com', 'manager', 1, NULL, '2026-06-05 11:34:23'),
(3, 'cashier', 'cashier123', 'Cashier User', 'cashier@faysalglass.com', 'cashier', 1, '2026-09-02 13:36:28', '2026-06-05 11:34:23');

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
-- Indexes for table `hold_quotations_details`
--
ALTER TABLE `hold_quotations_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hold_id` (`hold_id`);

--
-- Indexes for table `hold_quotations_master`
--
ALTER TABLE `hold_quotations_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hold_no_unique` (`hold_no`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_status` (`status`);

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
  ADD KEY `product_id` (`product_id`),
  ADD KEY `product_size_id` (`product_size_id`);

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
-- Indexes for table `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bank1_transactions`
--
ALTER TABLE `bank1_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank2_transactions`
--
ALTER TABLE `bank2_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank3_transactions`
--
ALTER TABLE `bank3_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bank_book`
--
ALTER TABLE `bank_book`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cash_book`
--
ALTER TABLE `cash_book`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `customer_ledger`
--
ALTER TABLE `customer_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `customer_payments`
--
ALTER TABLE `customer_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_receipts`
--
ALTER TABLE `customer_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employee_ledger`
--
ALTER TABLE `employee_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `employee_payments`
--
ALTER TABLE `employee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employee_salary`
--
ALTER TABLE `employee_salary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expense_heads`
--
ALTER TABLE `expense_heads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expense_ledger`
--
ALTER TABLE `expense_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hold_quotations_details`
--
ALTER TABLE `hold_quotations_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hold_quotations_master`
--
ALTER TABLE `hold_quotations_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hold_sales_details`
--
ALTER TABLE `hold_sales_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `hold_sales_master`
--
ALTER TABLE `hold_sales_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `inventory_ledger`
--
ALTER TABLE `inventory_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=455;

--
-- AUTO_INCREMENT for table `opening_stock`
--
ALTER TABLE `opening_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `product_sizes`
--
ALTER TABLE `product_sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `purchase_details`
--
ALTER TABLE `purchase_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `purchase_master`
--
ALTER TABLE `purchase_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `quotation_details`
--
ALTER TABLE `quotation_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `quotation_master`
--
ALTER TABLE `quotation_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `sale_details`
--
ALTER TABLE `sale_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=230;

--
-- AUTO_INCREMENT for table `sale_master`
--
ALTER TABLE `sale_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `supplier_ledger`
--
ALTER TABLE `supplier_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`head_id`) REFERENCES `expense_heads` (`id`);

--
-- Constraints for table `expense_ledger`
--
ALTER TABLE `expense_ledger`
  ADD CONSTRAINT `expense_ledger_ibfk_1` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`),
  ADD CONSTRAINT `expense_ledger_ibfk_2` FOREIGN KEY (`head_id`) REFERENCES `expense_heads` (`id`);

--
-- Constraints for table `opening_stock`
--
ALTER TABLE `opening_stock`
  ADD CONSTRAINT `opening_stock_ibfk_2` FOREIGN KEY (`product_size_id`) REFERENCES `product_sizes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`);

--
-- Constraints for table `quotation_details`
--
ALTER TABLE `quotation_details`
  ADD CONSTRAINT `quotation_details_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotation_master` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
