-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 02, 2026 at 01:49 PM
-- Server version: 8.0.46-cll-lve
-- PHP Version: 8.4.24

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
  `from_account_type` enum('cash','bank1','bank2','bank3') COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_account_id` int DEFAULT NULL,
  `to_account_type` enum('cash','bank1','bank2','bank3') COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_account_id` int DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
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
  `id` int NOT NULL,
  `date` date NOT NULL,
  `reference_type` enum('OPENING','SALE','PURCHASE','CUSTOMER_PAYMENT','SUPPLIER_RECEIPT','WITHDRAW','TRANSFER_IN','TRANSFER_OUT','ADJUSTMENT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
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
  `reference_type` enum('OPENING','SALE','PURCHASE','CUSTOMER_PAYMENT','SUPPLIER_RECEIPT','WITHDRAW','TRANSFER_IN','TRANSFER_OUT','ADJUSTMENT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
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
  `reference_type` enum('OPENING','SALE','PURCHASE','CUSTOMER_PAYMENT','SUPPLIER_RECEIPT','WITHDRAW','TRANSFER_IN','TRANSFER_OUT','ADJUSTMENT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
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
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opening_balance` decimal(15,2) DEFAULT '0.00',
  `current_balance` decimal(15,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bank_accounts`
--

INSERT INTO `bank_accounts` (`id`, `bank_name`, `account_title`, `account_number`, `branch_name`, `opening_balance`, `current_balance`, `status`) VALUES
(1, 'Bank AL Habib', 'Faisal Glass & Aluminium Center', '01290081000893019', NULL, 500000.00, 1556121.00, 1),
(2, 'Faysal Bank', 'Faisal Glass & Aluminium Center', '3429301000002466', NULL, 500000.00, 500000.00, 1),
(3, 'UBL Bank', 'Faisal Glass & Aluminium Center', '2661362759574', NULL, 500000.00, 500000.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `bank_book`
--

CREATE TABLE `bank_book` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `bank_account_id` int NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `debit` decimal(15,2) DEFAULT '0.00',
  `credit` decimal(15,2) DEFAULT '0.00',
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bank_book`
--

INSERT INTO `bank_book` (`id`, `date`, `bank_account_id`, `reference_type`, `reference_id`, `description`, `debit`, `credit`, `balance`, `created_at`) VALUES
(1, '2026-06-17', 1, 'OPENING', 1, 'Opening Balance - Bank AL Habib', 500000.00, 0.00, 500000.00, '2026-06-17 11:38:08'),
(2, '2026-06-17', 2, 'OPENING', 2, 'Opening Balance - Faysal Bank', 500000.00, 0.00, 500000.00, '2026-06-17 11:38:08'),
(3, '2026-06-17', 3, 'OPENING', 3, 'Opening Balance - UBL Bank', 500000.00, 0.00, 500000.00, '2026-06-17 11:38:08'),
(5, '2026-07-14', 1, 'customer_receipt', 2, 'Payment received from customer - ', 150000.00, 0.00, 650000.00, '2026-07-14 18:17:27'),
(6, '2026-07-26', 1, 'TRANSFER_IN', 31, 'Transfer from Cash - ', 704621.00, 0.00, 1354621.00, '2026-07-26 18:50:23'),
(7, '2026-08-07', 1, 'customer_receipt', 8, 'Payment received from customer - ', 200000.00, 0.00, 1554621.00, '2026-08-28 13:47:01'),
(8, '2026-08-31', 1, 'SALE', 45, 'Sale Payment: SAL-00041', 117390.00, 0.00, 1672011.00, '2026-08-31 11:07:51'),
(9, '2026-08-31', 1, 'SALE', 46, 'Sale Payment: SAL-00042', 6460.00, 0.00, 1678471.00, '2026-08-31 11:09:40'),
(11, '2026-08-31', 1, 'SALE', 64, 'Sale Payment: SAL-00060', 11375.00, 0.00, 1689846.00, '2026-09-01 06:36:46'),
(12, '2026-09-01', 1, 'customer_receipt', 11, 'Payment received from customer - ', 1500.00, 0.00, 1691346.00, '2026-09-01 06:44:17'),
(13, '2026-09-01', 1, 'SALE', 101, 'Sale Payment: SAL-00095', 59500.00, 0.00, 1750846.00, '2026-09-01 15:02:34'),
(14, '2026-08-31', 1, 'SALE', 44, 'Sale Payment: SAL-00040', 36031.20, 0.00, 1786877.20, '2026-09-01 15:55:51');

-- --------------------------------------------------------

--
-- Table structure for table `cash_book`
--

CREATE TABLE `cash_book` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
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
(47, '2026-08-12', 'SALE', 36, 'Sale Payment: SAL-00032', 100.00, 0.00, -5811.75, '2026-08-25 12:58:21'),
(48, '2026-08-28', 'SUPPLIER_MANUAL', 20, 'Payment made to Ghani glass (Manual Debit) - abc', 0.00, 500.00, -6311.75, '2026-08-28 13:20:43'),
(49, '2026-08-29', 'CUSTOMER_MANUAL', 71, 'Cash paid/refunded to Amir Butt Glass (Manual Debit) - pl', 0.00, 1.00, -6312.75, '2026-08-29 10:41:04'),
(50, '2026-08-29', 'CUSTOMER_MANUAL', 72, 'Payment received from Amir Butt Glass (Manual Credit) - ok', 1.00, 0.00, -6311.75, '2026-08-29 10:41:28'),
(51, '2026-08-29', 'customer_receipt', 9, 'Payment received from customer - Nawaz glass ', 200000.00, 0.00, 193688.25, '2026-08-29 16:57:40'),
(53, '2026-08-31', 'SALE', 42, 'Sale Payment: SAL-00038', 2850.00, 0.00, 197038.25, '2026-08-31 08:58:21'),
(55, '2026-08-31', 'SALE', 48, 'Sale Payment: SAL-00044', 6555.00, 0.00, 203593.25, '2026-08-31 11:46:37'),
(56, '2026-08-31', 'customer_receipt', 10, 'Payment received from customer - ', 175000.00, 0.00, 378593.25, '2026-08-31 13:04:37'),
(57, '2026-08-31', 'EMPLOYEE_PAYMENT', 2, 'Salary payment to: Faheem', 0.00, 500.00, 378093.25, '2026-08-31 13:18:41'),
(59, '2026-08-31', 'SALE', 61, 'Sale Payment: SAL-00057', 2760.00, 0.00, 380853.25, '2026-08-31 16:02:17'),
(60, '2026-09-01', 'SALE', 77, 'Sale Payment: SAL-00073', 12990.00, 0.00, 393843.25, '2026-09-01 08:55:41'),
(61, '2026-09-01', 'SALE', 78, 'Sale Payment: SAL-00074', 5040.00, 0.00, 398883.25, '2026-09-01 09:01:08'),
(63, '2026-09-01', 'SALE', 83, 'Sale Payment: SAL-00079', 4140.00, 0.00, 403023.25, '2026-09-01 11:37:40'),
(64, '2026-09-01', 'SALE', 91, 'Sale Payment: SAL-00087', 22620.00, 0.00, 425643.25, '2026-09-01 12:48:10'),
(65, '2026-09-01', 'customer_receipt', 12, 'Payment received from customer - ', 6325.00, 0.00, 431968.25, '2026-09-01 12:49:41'),
(66, '2026-09-01', 'SALE', 93, 'Sale Payment: SAL-00089', 26100.00, 0.00, 458068.25, '2026-09-01 12:56:40'),
(70, '2026-09-01', 'SALE', 96, 'Sale Payment: SAL-00090', 2801.25, 0.00, 460869.50, '2026-09-01 13:27:26'),
(71, '2026-09-01', 'SALE', 99, 'Sale Payment: SAL-00093', 10500.00, 0.00, 471369.50, '2026-09-01 15:00:27'),
(72, '2026-09-01', 'SALE', 100, 'Sale Payment: SAL-00094', 17500.00, 0.00, 488869.50, '2026-09-01 15:01:36'),
(73, '2026-09-01', 'SALE', 104, 'Sale Payment: SAL-00098', 27255.00, 0.00, 516124.50, '2026-09-01 15:10:57'),
(74, '2026-08-31', 'SALE', 68, 'Sale Payment: SAL-00064', 3675.00, 0.00, 519799.50, '2026-09-01 15:50:46'),
(75, '2026-08-31', 'SALE', 62, 'Sale Payment: SAL-00058', 10150.00, 0.00, 529949.50, '2026-09-01 15:51:54'),
(76, '2026-08-31', 'SALE', 63, 'Sale Payment: SAL-00059', 19670.00, 0.00, 549619.50, '2026-09-01 15:53:30'),
(77, '2026-09-01', 'SALE', 89, 'Sale Payment: SAL-00085', 251096.80, 0.00, 800716.30, '2026-09-01 15:57:14');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `category_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
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
  `company_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `company_name`, `contact_person`, `phone`, `address`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Ghani glass limited', 'Ghani', '03018481249', '40-L Model Town Extension Block L Lahore', 1, '2026-06-08 11:21:11', '2026-08-29 14:22:35'),
(2, 'Ghani value glass limited', 'GVG', '03046666649', '39-L Modal Town Extension Block L Lahore', 1, '2026-08-29 14:26:27', '2026-08-29 14:26:27');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int NOT NULL,
  `customer_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cnic` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ntn` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `opening_balance` decimal(15,2) DEFAULT '0.00',
  `balance_type` enum('receivable','payable') COLLATE utf8mb4_unicode_ci DEFAULT 'receivable' COMMENT 'receivable=Customer owes company, payable=Company owes customer',
  `current_balance` decimal(15,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_code`, `customer_name`, `company_name`, `contact_person`, `mobile`, `cnic`, `ntn`, `email`, `address`, `opening_balance`, `balance_type`, `current_balance`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(18, 'CUS-0001', 'Amir Butt Glass', 'Manava', '', '03235241919', '', '', '', '', 5108218.00, 'receivable', 5108218.00, 1, 'register wala', '2026-08-27 15:07:50', '2026-08-29 10:42:02'),
(19, 'CUS-0002', 'Ali GLass sufi sab', '', '', '03224075313', '', '', '', '', 28661487.00, 'receivable', 28661487.00, 1, 'register wala', '2026-08-27 15:10:19', '2026-08-27 15:10:19'),
(20, 'CUS-0003', 'Mujahid Glass', 'Saim Ali', '', '03007470698', '', '', '', 'Ameer chowk', 3989156.00, 'receivable', 3989156.00, 1, 'register wala', '2026-08-27 15:17:31', '2026-08-27 15:17:31'),
(21, 'CUS-0004', 'Mirza Asif GT Road', '', '', '03219413277', '', '', '', '', 510296.00, 'receivable', 510296.00, 1, '', '2026-08-27 15:20:10', '2026-08-27 15:20:10'),
(22, 'CUS-0005', 'Younas Haideri Glass', 'Ghazi Road', '', '03214985843', '', '', '', '', 30775743.00, 'receivable', 30775743.00, 1, '', '2026-08-27 15:22:14', '2026-08-27 15:22:14'),
(23, 'CUS-0006', 'Imran Mughal Glass', 'Mughalpura', '', '03214528872', '', '', '', '', 687860.00, 'receivable', 687860.00, 1, '', '2026-08-27 15:26:21', '2026-08-27 15:26:21'),
(24, 'CUS-0007', 'AL Hammad Glass', 'Ghazi Road', '', '03218432022', '', '', '', '', 3199653.00, 'receivable', 3199653.00, 1, 'register wala', '2026-08-27 15:31:10', '2026-08-27 15:31:10'),
(25, 'CUS-0008', 'Mahar Glass GT Road Fahad', '', '', '03045762580', '', '', '', '', 827320.00, 'receivable', 827320.00, 1, 'register wala', '2026-08-27 15:33:13', '2026-08-27 15:33:13'),
(26, 'CUS-0009', 'Nadeem Joray pull', '', '', 'no', '', '', '', '', 205430.00, 'receivable', 205430.00, 1, 'register wala', '2026-08-27 15:36:06', '2026-08-27 15:36:06'),
(27, 'CUS-0010', 'Awais Glass Raiwind', '', '', '03024000045', '', '', '', '', 1918575.00, 'receivable', 1918575.00, 1, '', '2026-08-27 15:39:43', '2026-08-27 15:39:43'),
(28, 'CUS-0011', 'Awais Glass kasur', 'kasur', '', '03234175510', '', '', '', '', 3914513.00, 'receivable', 3914513.00, 1, 'register wala', '2026-08-27 15:43:39', '2026-08-27 15:43:39'),
(29, 'CUS-0012', 'Awais Glass kasur cutting', '', '', '03234175510', '', '', '', '', 0.00, 'receivable', 58608.75, 1, '', '2026-08-27 15:44:40', '2026-09-01 15:48:35'),
(30, 'CUS-0013', 'Zaman Glass', 'Fatah Garh', '', 'no', '', '', '', '', 184560.00, 'receivable', 184560.00, 1, '', '2026-08-27 15:49:32', '2026-08-27 15:49:32'),
(31, 'CUS-0014', 'Mujahid Glass cutting', 'Saim Ali', '', '03067690429', '', '', '', '', 0.00, 'receivable', 0.00, 1, '', '2026-08-27 15:50:32', '2026-08-27 15:50:32'),
(32, 'CUS-0015', 'Umar Glass Valencia Town', '', '', '03227955084', '', '', '', '', 3991678.00, 'receivable', 5891113.00, 1, '', '2026-08-27 15:54:37', '2026-09-01 14:58:39'),
(33, 'CUS-0016', 'shabbir Glass Raiwind', '', '', '03054058078', '', '', '', '', 1695852.00, 'receivable', 1695852.00, 1, 'register wala', '2026-08-28 11:07:40', '2026-08-28 11:07:40'),
(34, 'CUS-0017', 'Mian shahid', 'Bo Ali Glass', '', '03234549946', '', '', '', '', 586785.00, 'receivable', 592190.00, 1, 'register wala', '2026-08-28 11:15:28', '2026-09-01 12:54:22'),
(35, 'CUS-0018', 'Tacno Aluminium', '', '', '03004182303', '', '', '', '', 14706.00, 'receivable', 181181.00, 1, '', '2026-08-28 11:17:47', '2026-08-31 13:38:02'),
(36, 'CUS-0019', 'Mian Brother Ahmad', '', '', '03334120049', '', '', '', '', 2753331.00, 'receivable', 2753331.00, 1, 'register wala', '2026-08-28 11:26:53', '2026-08-28 11:26:53'),
(37, 'CUS-0020', 'Asif Baig Aluminium', '', '', '03004638283', '', '', '', '', 742548.00, 'receivable', 742548.00, 1, '', '2026-08-28 11:33:09', '2026-08-28 11:33:09'),
(38, 'CUS-0021', 'Mudassir Zahoor sons', '', '', '03064422320', '', '', '', '', 114017.00, 'receivable', 114017.00, 1, '', '2026-08-28 11:36:27', '2026-08-28 15:13:09'),
(39, 'CUS-0022', 'Ali Hassan Aluminium', 'Nasrullah khan', '', '03218520001', '', '', '', '', 407667.00, 'receivable', 407667.00, 1, '', '2026-08-28 11:39:16', '2026-08-28 11:39:16'),
(40, 'CUS-0023', 'Zaim Muzammal Aluminium', '', '', '03014936601', '', '', '', '', 450552.00, 'receivable', 450552.00, 1, 'register wala', '2026-08-28 11:41:54', '2026-08-28 11:41:54'),
(41, 'CUS-0024', 'Nawaz Glass college Road', '', '', '03004353524', '', '', '', '', 2115680.00, 'receivable', 1939030.00, 1, '', '2026-08-28 11:47:06', '2026-08-31 09:27:22'),
(42, 'CUS-0025', 'shahid Ameer Chowk', '', '', '03214363543', '', '', '', '', 116482.00, 'receivable', 116482.00, 1, 'register wala & Ali wala', '2026-08-28 11:53:08', '2026-08-28 11:53:08'),
(43, 'CUS-0026', 'Azam Glass Samsani', '', '', '03214219310', '', '', '', '', 117957.00, 'receivable', 117957.00, 1, 'register wala', '2026-08-28 11:55:29', '2026-08-28 11:55:29'),
(44, 'CUS-0027', 'Younas Haideri Glass cutting', '', '', 'no', '', '', '', '', 140483.00, 'receivable', 140483.00, 1, '6mm brown&6mm white parche in file', '2026-08-28 12:02:28', '2026-08-28 12:02:28'),
(45, 'CUS-0028', 'AL Majeed Aluminium', '', '', '03014509242', '', '', '', '', 3795880.00, 'receivable', 4035325.00, 1, 'register wala', '2026-08-28 12:14:35', '2026-08-31 14:29:13'),
(46, 'CUS-0029', 'Imran Anwar Trader', '', '', '03214361530', '', '', '', '', 486667.00, 'receivable', 486667.00, 1, 'register wala', '2026-08-28 13:04:01', '2026-08-28 13:04:01'),
(47, 'CUS-0030', 'Eman Glass Nazam', '', '', '03003689087', '', '', '', '', 146833.00, 'receivable', 146833.00, 1, 'register wala', '2026-08-28 13:18:32', '2026-08-29 09:13:06'),
(48, 'CUS-0031', 'AL Naqeeb Aluminium', 'Azhar sab', '', '03000751934', '', '', '', '', 295896.00, 'receivable', 404616.00, 1, 'register wala', '2026-08-28 13:27:00', '2026-08-29 09:22:22'),
(49, 'CUS-0032', 'Jahangir Glass nazam', '', '', '03014258896', '', '', '', '', 434864.00, 'receivable', 434864.00, 1, 'register wala', '2026-08-28 13:29:34', '2026-08-29 09:14:04'),
(50, 'CUS-0033', 'shahbaz Karmanwalay Aluminium', '', '', '03244010500', '', '', '', '', 1274425.00, 'receivable', 1074425.00, 1, 'register wala', '2026-08-28 13:35:46', '2026-08-28 13:47:01'),
(51, 'CUS-0034', 'Rofayel Aluminium', 'Main Rasheed', '', '03228661260', '', '', '', '', 691707.00, 'receivable', 691707.00, 1, 'register wala', '2026-08-28 14:07:36', '2026-08-28 14:07:36'),
(52, 'CUS-0035', 'Faham Glass', 'vicky bhai', '', '03048880418', '', '', '', '', 1247269.00, 'receivable', 1247269.00, 1, '', '2026-08-28 14:11:30', '2026-08-28 14:11:30'),
(53, 'CUS-0036', 'Ideal Aluminium nazam', '', '', '03074262167', '', '', '', '', 331729.00, 'receivable', 331729.00, 1, 'register wala', '2026-08-28 14:14:44', '2026-08-28 14:14:44'),
(54, 'CUS-0037', 'Lahore Glass', 'Lala & Jalil', '', '03004156693', '', '', '', '', 482933.00, 'receivable', 482933.00, 1, '', '2026-08-28 14:17:45', '2026-08-28 14:17:45'),
(55, 'CUS-0038', 'Ahmad Glass Wapda Town', '', '', '03008334988', '', '', '', '', 3918699.00, 'receivable', 3965484.00, 1, 'register wala', '2026-08-28 14:25:06', '2026-09-02 06:06:17'),
(56, 'CUS-0039', 'A TO Z Glass', 'Aman Ullah Sab', '', '03004881112', '', '', '', '', 857782.00, 'receivable', 857782.00, 1, 'register wala', '2026-08-28 14:37:24', '2026-08-28 14:37:24'),
(57, 'CUS-0040', 'AL Usman Glass', '', '', '03224139509', '', '', '', '', 618648.00, 'receivable', 628308.00, 1, '', '2026-08-28 15:10:01', '2026-09-01 12:35:04'),
(58, 'CUS-0041', 'ali', '', '', '0000000000', '', '', '', '', 0.00, 'receivable', 0.00, 1, '', '2026-08-29 08:53:35', '2026-08-29 08:53:35'),
(60, 'CUS-0042', 'customer', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 3000.00, 1, 'Customer added from sale form', '2026-08-31 08:56:25', '2026-08-31 12:12:16'),
(61, 'CUS-0043', 'Fazal Mahmood UH Glass', '', '', '03214841963', '', '', '', 'Bhatta chowk Lahore', 825248.00, 'receivable', 825248.00, 1, 'Bill no 863', '2026-08-31 09:39:57', '2026-08-31 09:39:57'),
(62, 'CUS-0044', 'Mughal Glass', '', '', '03054575330', '', '', '', '', 0.00, 'receivable', 0.00, 1, '', '2026-08-31 09:42:44', '2026-09-01 15:55:51'),
(63, 'CUS-0045', 'Mahar Brother\'s Glass Samsani', '', '', '03008100345', '', '', '', '', 0.00, 'receivable', 131475.00, 1, '', '2026-08-31 11:05:05', '2026-09-01 08:32:46'),
(64, 'CUS-0046', 'Ali Kamboh', '', '', '03053987709', '', '', '', '', 300000.00, 'payable', -112227.50, 1, '', '2026-08-31 12:16:32', '2026-08-31 13:54:35'),
(65, 'CUS-0047', 'Kanch Ghar', '', '', '03096332340', '', '', '', '', 0.00, 'receivable', 1250.00, 1, '', '2026-08-31 12:22:38', '2026-09-01 06:44:17'),
(66, 'CUS-0048', 'khan Glass Riaz', '', '', '03004305529', '', '', '', '', 0.00, 'receivable', 210045.00, 1, '', '2026-08-31 12:27:39', '2026-09-01 12:07:32'),
(67, 'CUS-0049', 'kasur customer', '', '', 'no', '', '', '', '', 0.00, 'receivable', 20000.00, 1, '', '2026-08-31 12:28:54', '2026-08-31 14:39:32'),
(68, 'CUS-0050', 'Shoaib General Trader', '', '', '03214140196', '', '', '', '', 0.00, 'receivable', 28250.00, 1, '', '2026-08-31 14:01:31', '2026-08-31 14:06:04'),
(69, 'CUS-0051', 'Ashfaq Mansoor Glass kasur', '', '', '03217285765', '', '', '', '', 0.00, 'receivable', 0.00, 1, '', '2026-08-31 14:18:25', '2026-08-31 14:18:25'),
(70, 'CUS-0052', 'Ramzan Desiger Glass', '', '', '03245606091', '', '', '', '', 600000.00, 'payable', -479916.50, 1, '5mm white 84x144=40 lani hai', '2026-08-31 15:41:03', '2026-09-01 06:41:16'),
(71, 'CUS-0053', 'khan bhai', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-08-31 16:01:19', '2026-08-31 16:01:19'),
(72, 'CUS-0054', 'Saleem AL Zaban', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-08-31 16:04:11', '2026-08-31 16:04:11'),
(73, 'CUS-0055', 'Al Riaz Glass  Rizwan', '', '', '0300478655', '', '', '', '', 0.00, 'receivable', 43297.50, 1, '', '2026-09-01 06:00:57', '2026-09-01 06:35:44'),
(74, 'CUS-0056', 'Irfan PIA', '', '', '03014114011', '', '', '', '', 0.00, 'receivable', 0.00, 1, '', '2026-09-01 06:03:08', '2026-09-01 15:50:46'),
(75, 'CUS-0057', 'Muzamil', '', '', '03260241192', '', '', '', '', 0.00, 'receivable', 0.00, 1, '', '2026-09-01 06:06:44', '2026-09-01 15:53:30'),
(76, 'CUS-0058', 'Younas Glass Muhammad Ali chowk', '', '', '03225976437', '', '', '', '', 0.00, 'receivable', 0.00, 1, '', '2026-09-01 06:09:14', '2026-09-01 15:51:54'),
(77, 'CUS-0059', 'Ali Raza', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-09-01 06:17:54', '2026-09-01 06:17:54'),
(78, 'CUS-0060', 'Ashfaq sona chandi', '', '', '03215558249', '', '', '', '', 0.00, 'receivable', 76807.50, 1, '', '2026-09-01 06:23:03', '2026-09-01 06:26:08'),
(79, 'CUS-0061', 'AL Hammad Akram sab', '', '', '03214707933', '', '', '', '', 0.00, 'receivable', 186965.00, 1, '', '2026-09-01 08:12:47', '2026-09-01 15:57:14'),
(80, 'CUS-0062', 'Amir Green Town', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 6727.50, 1, 'Customer added from sale form', '2026-09-01 08:18:23', '2026-09-01 08:20:52'),
(81, 'CUS-0063', 'Anwar Glass Moeen', '', '', '03154260054', '', '', '', '', 0.00, 'receivable', 0.00, 1, '', '2026-09-01 08:35:32', '2026-09-01 12:49:41'),
(82, 'CUS-0064', 'Qasir sab', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 6515.00, 1, 'Customer added from sale form', '2026-09-01 08:38:23', '2026-09-01 08:50:38'),
(83, 'CUS-0065', 'Waqas waki', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-09-01 08:52:17', '2026-09-01 08:52:17'),
(84, 'CUS-0066', 'Ashfaq', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-09-01 08:58:48', '2026-09-01 08:58:48'),
(85, 'CUS-0067', 'Mian Kashif', '', '', '03000041695', '', '', '', '', 24640.00, 'receivable', 33320.00, 1, 'register wala', '2026-09-01 09:08:49', '2026-09-01 09:20:28'),
(86, 'CUS-0068', 'Ahmad customer', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 6100.00, 1, 'Customer added from sale form', '2026-09-01 09:24:01', '2026-09-01 09:25:03'),
(87, 'CUS-0069', 'Saleem Al Musavir Aluminium', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 15697.50, 1, 'Customer added from sale form', '2026-09-01 09:28:54', '2026-09-01 09:29:29'),
(88, 'CUS-0070', 'Malik Hasnain Aluminium', '', '', '03221149372', '', '', '', '', 0.00, 'receivable', 2200.00, 1, '', '2026-09-01 11:39:17', '2026-09-01 11:40:05'),
(89, 'CUS-0071', 'Mazhar Abbas Bhutta', '', '', '03026612376', '', '', '', '', 0.00, 'receivable', 17520.00, 1, '', '2026-09-01 11:43:39', '2026-09-01 11:46:59'),
(90, 'CUS-0072', 'Qarban Ali Baba', '', '', 'no', '', '', '', '', 0.00, 'receivable', 5130.00, 1, '', '2026-09-01 11:53:09', '2026-09-01 11:55:13'),
(91, 'CUS-0073', 'Adnan AA Glass', '', '', '03231481848', '', '', '', '', 165442.00, 'receivable', 449118.25, 1, '', '2026-09-01 12:15:03', '2026-09-01 12:26:34'),
(92, 'CUS-0074', 'Ali hassan', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-09-01 12:55:11', '2026-09-01 12:55:11'),
(93, 'CUS-0075', 'Lala Ahsan kasur', '', '', '03217071830', '', '', '', '', 0.00, 'receivable', 101190.00, 1, '', '2026-09-01 12:58:57', '2026-09-01 15:34:17'),
(94, 'CUS-0076', 'Hajvery Glass', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-09-01 13:09:11', '2026-09-01 13:09:11'),
(95, 'CUS-0077', 'shokat raksha wala', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-09-01 14:59:39', '2026-09-01 14:59:39'),
(96, 'CUS-0078', 'Saleem Glass Muhammad Ali Chowk', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 6590.00, 1, 'Customer added from sale form', '2026-09-01 15:03:25', '2026-09-01 15:08:53'),
(97, 'CUS-0079', 'Yasir', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 0.00, 1, 'Customer added from sale form', '2026-09-01 15:09:18', '2026-09-01 15:09:18'),
(98, 'CUS-0080', 'Shan Lala Kasur', '', '', '03217071830', '', '', '', '', 0.00, 'receivable', 332140.00, 1, '', '2026-09-01 15:13:51', '2026-09-01 15:30:49'),
(99, 'CUS-0081', 'Hafiz Shaikham', '', '', '03074342763', '', '', '', '', 0.00, 'receivable', 43985.00, 1, '', '2026-09-01 15:20:11', '2026-09-01 15:44:21'),
(100, 'CUS-0082', 'Asif Glass kasur', '', '', '03216574774', '', '', '', '', 0.00, 'receivable', 22895.00, 1, '', '2026-09-01 15:21:32', '2026-09-01 15:42:16'),
(101, 'CUS-0083', 'Wood Green Hamza', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 8580.00, 1, 'Customer added from sale form', '2026-09-02 06:18:03', '2026-09-02 06:19:19'),
(102, 'CUS-0084', 'Abdul Gafar Aluminium', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 2430.00, 1, 'Customer added from sale form', '2026-09-02 06:22:33', '2026-09-02 06:25:36'),
(103, 'CUS-0085', 'Abdul Rahman', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 5000.00, 1, 'Customer added from sale form', '2026-09-02 06:29:27', '2026-09-02 06:30:47'),
(104, 'CUS-0086', 'Zahid Akbar chowk', NULL, NULL, '0000000000', NULL, NULL, NULL, NULL, 0.00, 'receivable', 3237.50, 1, 'Customer added from sale form', '2026-09-02 06:32:18', '2026-09-02 06:34:32');

-- --------------------------------------------------------

--
-- Table structure for table `customer_ledger`
--

CREATE TABLE `customer_ledger` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `customer_id` int NOT NULL,
  `reference_type` enum('OPENING','SALE','PAYMENT','ADJUSTMENT','QUOTATION') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
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
(29, '2026-08-27', 8, 'OPENING', 8, 'Opening Balance - Receivable', 500.00, 0.00, 500.00, '2026-08-27 15:01:23'),
(30, '2026-08-27', 18, 'OPENING', 18, 'Opening Balance - Receivable (Customer owes company)', 5108218.00, 0.00, 5108218.00, '2026-08-27 15:07:50'),
(31, '2026-08-27', 19, 'OPENING', 19, 'Opening Balance - Receivable (Customer owes company)', 28661487.00, 0.00, 28661487.00, '2026-08-27 15:10:19'),
(32, '2026-08-27', 20, 'OPENING', 20, 'Opening Balance - Receivable (Customer owes company)', 3989156.00, 0.00, 3989156.00, '2026-08-27 15:17:31'),
(33, '2026-08-27', 21, 'OPENING', 21, 'Opening Balance - Receivable (Customer owes company)', 510296.00, 0.00, 510296.00, '2026-08-27 15:20:10'),
(34, '2026-08-27', 22, 'OPENING', 22, 'Opening Balance - Receivable (Customer owes company)', 30775743.00, 0.00, 30775743.00, '2026-08-27 15:22:14'),
(35, '2026-08-27', 23, 'OPENING', 23, 'Opening Balance - Receivable (Customer owes company)', 687860.00, 0.00, 687860.00, '2026-08-27 15:26:21'),
(36, '2026-08-27', 24, 'OPENING', 24, 'Opening Balance - Receivable (Customer owes company)', 3199653.00, 0.00, 3199653.00, '2026-08-27 15:31:10'),
(37, '2026-08-27', 25, 'OPENING', 25, 'Opening Balance - Receivable (Customer owes company)', 827320.00, 0.00, 827320.00, '2026-08-27 15:33:13'),
(38, '2026-08-27', 26, 'OPENING', 26, 'Opening Balance - Receivable (Customer owes company)', 205430.00, 0.00, 205430.00, '2026-08-27 15:36:06'),
(39, '2026-08-27', 27, 'OPENING', 27, 'Opening Balance - Receivable (Customer owes company)', 1918575.00, 0.00, 1918575.00, '2026-08-27 15:39:43'),
(40, '2026-08-27', 28, 'OPENING', 28, 'Opening Balance - Receivable (Customer owes company)', 3914513.00, 0.00, 3914513.00, '2026-08-27 15:43:39'),
(41, '2026-08-27', 30, 'OPENING', 30, 'Opening Balance - Receivable (Customer owes company)', 184560.00, 0.00, 184560.00, '2026-08-27 15:49:32'),
(42, '2026-08-27', 32, 'OPENING', 32, 'Opening Balance - Receivable (Customer owes company)', 3991678.00, 0.00, 3991678.00, '2026-08-27 15:54:37'),
(43, '2026-08-28', 33, 'OPENING', 33, 'Opening Balance - Receivable (Customer owes company)', 1695852.00, 0.00, 1695852.00, '2026-08-28 11:07:40'),
(44, '2026-08-28', 34, 'OPENING', 34, 'Opening Balance - Receivable (Customer owes company)', 586785.00, 0.00, 586785.00, '2026-08-28 11:15:28'),
(45, '2026-08-28', 35, 'OPENING', 35, 'Opening Balance - Receivable (Customer owes company)', 14706.00, 0.00, 14706.00, '2026-08-28 11:17:48'),
(46, '2026-08-28', 36, 'OPENING', 36, 'Opening Balance - Receivable (Customer owes company)', 2753331.00, 0.00, 2753331.00, '2026-08-28 11:26:53'),
(47, '2026-08-28', 37, 'OPENING', 37, 'Opening Balance - Receivable (Customer owes company)', 742548.00, 0.00, 742548.00, '2026-08-28 11:33:09'),
(48, '2026-08-28', 39, 'OPENING', 39, 'Opening Balance - Receivable (Customer owes company)', 407667.00, 0.00, 407667.00, '2026-08-28 11:39:16'),
(49, '2026-08-28', 40, 'OPENING', 40, 'Opening Balance - Receivable (Customer owes company)', 450552.00, 0.00, 450552.00, '2026-08-28 11:41:54'),
(50, '2026-08-28', 41, 'OPENING', 41, 'Opening Balance - Receivable (Customer owes company)', 2115680.00, 0.00, 2115680.00, '2026-08-28 11:47:06'),
(51, '2026-08-28', 42, 'OPENING', 42, 'Opening Balance - Receivable (Customer owes company)', 116482.00, 0.00, 116482.00, '2026-08-28 11:53:08'),
(52, '2026-08-28', 43, 'OPENING', 43, 'Opening Balance - Receivable (Customer owes company)', 117957.00, 0.00, 117957.00, '2026-08-28 11:55:29'),
(53, '2026-08-28', 44, 'OPENING', 44, 'Opening Balance - Receivable (Customer owes company)', 140483.00, 0.00, 140483.00, '2026-08-28 12:02:28'),
(54, '2026-08-28', 45, 'OPENING', 45, 'Opening Balance - Receivable (Customer owes company)', 3795880.00, 0.00, 3795880.00, '2026-08-28 12:14:35'),
(55, '2026-08-28', 46, 'OPENING', 46, 'Opening Balance - Receivable (Customer owes company)', 486667.00, 0.00, 486667.00, '2026-08-28 13:04:01'),
(56, '2026-08-28', 47, 'OPENING', 47, 'Opening Balance - Receivable', 146833.00, 0.00, 146833.00, '2026-08-28 13:18:32'),
(57, '2026-08-28', 48, 'OPENING', 48, 'Opening Balance - Receivable (Customer owes company)', 295896.00, 0.00, 295896.00, '2026-08-28 13:27:00'),
(58, '2026-08-28', 49, 'OPENING', 49, 'Opening Balance - Receivable', 434864.00, 0.00, 434864.00, '2026-08-28 13:29:34'),
(59, '2026-08-28', 50, 'OPENING', 50, 'Opening Balance - Receivable (Customer owes company)', 1274425.00, 0.00, 1274425.00, '2026-08-28 13:35:46'),
(60, '2026-08-07', 50, 'PAYMENT', 8, 'Payment received - ', 0.00, 200000.00, 1074425.00, '2026-08-28 13:47:01'),
(61, '2026-08-28', 51, 'OPENING', 51, 'Opening Balance - Receivable (Customer owes company)', 691707.00, 0.00, 691707.00, '2026-08-28 14:07:36'),
(62, '2026-08-28', 52, 'OPENING', 52, 'Opening Balance - Receivable (Customer owes company)', 1247269.00, 0.00, 1247269.00, '2026-08-28 14:11:30'),
(63, '2026-08-28', 53, 'OPENING', 53, 'Opening Balance - Receivable (Customer owes company)', 331729.00, 0.00, 331729.00, '2026-08-28 14:14:44'),
(64, '2026-08-28', 54, 'OPENING', 54, 'Opening Balance - Receivable (Customer owes company)', 482933.00, 0.00, 482933.00, '2026-08-28 14:17:45'),
(65, '2026-08-28', 55, 'OPENING', 55, 'Opening Balance - Receivable (Customer owes company)', 3918699.00, 0.00, 3918699.00, '2026-08-28 14:25:06'),
(66, '2026-08-28', 56, 'OPENING', 56, 'Opening Balance - Receivable (Customer owes company)', 857782.00, 0.00, 857782.00, '2026-08-28 14:37:24'),
(67, '2026-08-28', 57, 'OPENING', 57, 'Opening Balance - Receivable (Customer owes company)', 618648.00, 0.00, 618648.00, '2026-08-28 15:10:01'),
(68, '2026-08-28', 38, 'OPENING', 38, 'Opening Balance - Receivable', 114017.00, 0.00, 114017.00, '2026-08-28 15:13:09'),
(69, '2026-08-28', 41, 'SALE', 37, 'Sale Invoice: SAL-00033 - 6MM Grey looking (6.3 x 84), 6MM Grey looking (6.1 x 84), 6MM Grey looking (6 x 78.1) and 1 more items', 20500.00, 0.00, 2136180.00, '2026-08-28 15:23:44'),
(70, '2026-08-29', 48, 'SALE', 38, 'Sale Invoice: SAL-00034 - 6mm white (84 x 144), 6mm white (84 x 114), 6mm white (66 x 84) and 7 more items', 108720.00, 0.00, 404616.00, '2026-08-29 09:22:22'),
(71, '2026-08-29', 18, 'ADJUSTMENT', 0, '[Manual Debit] pl (Ref: 4789641)', 1.00, 0.00, 5108219.00, '2026-08-29 10:41:04'),
(72, '2026-08-29', 18, 'ADJUSTMENT', 0, '[Manual Credit] ok (Ref: 4789641)', 0.00, 1.00, 5108218.00, '2026-08-29 10:41:28'),
(73, '2026-08-29', 18, 'ADJUSTMENT', 0, '[Manual Debit] ok (Ref: 7777)', 1.00, 0.00, 5108219.00, '2026-08-29 10:41:44'),
(74, '2026-08-29', 18, 'ADJUSTMENT', 0, '[Manual Credit] ji (Ref: 07777)', 0.00, 1.00, 5108218.00, '2026-08-29 10:42:02'),
(75, '2026-08-29', 59, 'OPENING', 59, 'Opening Balance - Receivable (Customer owes company)', 100000000000.00, 0.00, 100000000000.00, '2026-08-29 16:54:51'),
(76, '2026-08-29', 41, 'PAYMENT', 9, 'Payment received - Nawaz glass ', 0.00, 200000.00, 1936180.00, '2026-08-29 16:57:40'),
(77, '2026-08-31', 45, 'SALE', 39, 'Sale Invoice: SAL-00035 - 6mm Green Mercury (66 x 26.7), 6mm Green Mercury (66 x 15), 6mm Green Mercury (42 x 11.7) and 9 more items', 83205.00, 0.00, 3879085.00, '2026-08-31 05:43:22'),
(78, '2026-08-31', 45, 'SALE', 40, 'Sale Invoice: SAL-00036 - 6mm Brown (5.7 x 10.4), 6mm Brown (21 x 51.2), 6mm Brown (28.6 x 60.5) and 24 more items', 147840.00, 0.00, 4026925.00, '2026-08-31 05:56:59'),
(81, '2026-08-31', 60, 'SALE', 42, 'Sale Invoice: SAL-00038 - 8mm Brown (18.00 x 24.00)', 2850.00, 2850.00, 0.00, '2026-08-31 08:58:21'),
(82, '2026-08-31', 41, 'SALE', 43, 'Sale Invoice: SAL-00039 - 8mm Grey (12 x 36)', 2850.00, 0.00, 1939030.00, '2026-08-31 09:27:22'),
(83, '2026-08-31', 61, 'OPENING', 61, 'Opening Balance - Receivable (Customer owes company)', 825248.00, 0.00, 825248.00, '2026-08-31 09:39:57'),
(85, '2026-08-31', 63, 'SALE', 45, 'Sale Invoice: SAL-00041 - 6mm Blue Mercury (84 x 144), 6mm Blue Mercury (84 x 120), 6mm Blue Mercury (42 x 84) and 4 more items', 117390.00, 117390.00, 0.00, '2026-08-31 11:07:51'),
(86, '2026-08-31', 60, 'SALE', 46, 'Sale Invoice: SAL-00042 - 5mm Brown Mercury (21.5 x 41), 5mm Brown Mercury (18 x 19)', 6460.00, 6460.00, 0.00, '2026-08-31 11:09:40'),
(89, '2026-08-31', 45, 'SALE', 48, 'Sale Invoice: SAL-00044 - 12mm White (12 x 114)', 6555.00, 6555.00, 4026925.00, '2026-08-31 11:46:37'),
(90, '2026-08-31', 60, 'SALE', 49, 'Sale Invoice: SAL-00045 - 8mm White (13.4 x 39), 8mm White (8.4 x 11.4)', 5500.00, 2500.00, 3000.00, '2026-08-31 12:12:16'),
(91, '2026-08-31', 64, 'OPENING', 64, 'Opening Balance - Payable (Company owes customer)', 0.00, 300000.00, -300000.00, '2026-08-31 12:16:32'),
(92, '2026-08-31', 64, 'SALE', 50, 'Sale Invoice: SAL-00046 - 12mm White (48 x 62.2), 12mm White (48 x 66)', 45540.00, 0.00, -254460.00, '2026-08-31 12:18:44'),
(93, '2026-08-31', 65, 'SALE', 51, 'Sale Invoice: SAL-00047 - 6mm Looking (29 x 25)', 2750.00, 0.00, 2750.00, '2026-08-31 12:23:55'),
(94, '2026-08-31', 32, 'SALE', 52, 'Sale Invoice: SAL-00048 - 8mm White (27 x 45), 12mm White (24 x 45)', 9300.00, 0.00, 4000978.00, '2026-08-31 13:02:15'),
(95, '2026-08-31', 32, 'PAYMENT', 10, 'Payment received - ', 0.00, 175000.00, 3825978.00, '2026-08-31 13:04:37'),
(96, '2026-08-31', 35, 'SALE', 41, 'Sale Invoice: SAL-00037 - 6mm Looking (19.60 x 120.00), 6mm Looking (31.20 x 120.00), 6mm Looking (31.60 x 48.00) and 6 more items', 166475.00, 0.00, 181181.00, '2026-08-31 13:38:02'),
(97, '2026-08-31', 64, 'SALE', 53, 'Sale Invoice: SAL-00049 - 6mm White (69 x 96), 6mm White (96 x 144), 6mm White (18 x 84) and 4 more items', 142232.50, 0.00, -112227.50, '2026-08-31 13:54:35'),
(98, '2026-08-31', 68, 'SALE', 54, 'Sale Invoice: SAL-00050 - 8mm White (35.3 x 47.3), 8mm White (23 x 26), 8mm White (30 x 50) and 5 more items', 28250.00, 0.00, 28250.00, '2026-08-31 14:06:04'),
(99, '2026-08-31', 32, 'SALE', 55, 'Sale Invoice: SAL-00051 - 6mm Grey Mercury (17.3 x 59.2), 6mm Grey Mercury (17.6 x 19.4)', 5737.50, 0.00, 3831715.50, '2026-08-31 14:12:44'),
(100, '2026-08-01', 32, 'ADJUSTMENT', 0, '[Manual Debit] 6mm white \r\n96x144=90     8640x235=2030400', 2030400.00, 0.00, 5862115.50, '2026-08-31 14:25:02'),
(101, '2026-08-31', 45, 'SALE', 56, 'Sale Invoice: SAL-00052 - 6mm White (60 x 78)', 8400.00, 0.00, 4035325.00, '2026-08-31 14:29:13'),
(104, '2026-08-31', 67, 'SALE', 59, 'Sale Invoice: SAL-00055 - 12mm Grey (30 x 132), 12mm Grey (5.2 x 30), 12mm Grey (19.4 x 30) and 2 more items', 60000.00, 40000.00, 20000.00, '2026-08-31 14:39:32'),
(105, '2026-08-31', 70, 'OPENING', 70, 'Opening Balance - Payable (Company owes customer)', 0.00, 600000.00, -600000.00, '2026-08-31 15:41:03'),
(106, '2026-08-31', 70, 'ADJUSTMENT', 0, '[Manual Debit] cutting purche ka bill hai ya', 86806.00, 0.00, -513194.00, '2026-08-31 15:42:31'),
(107, '2026-08-31', 70, 'SALE', 60, 'Sale Invoice: SAL-00056 - 6mm Brown Mercury (66 x 72), 6mm Brown Mercury (21.5 x 72), 6mm Brown Mercury (18 x 90) and 1 more items', 28177.50, 0.00, -485016.50, '2026-08-31 15:44:42'),
(108, '2026-08-31', 71, 'SALE', 61, 'Sale Invoice: SAL-00057 - 5mm white (36 x 48)', 2760.00, 2760.00, 0.00, '2026-08-31 16:02:17'),
(112, '2026-09-01', 78, 'SALE', 65, 'Sale Invoice: SAL-00061 - 6mm Brown (96 x 144), 6mm Brown (26.5 x 62.5), 6mm Brown (26.1 x 69.7)', 76807.50, 0.00, 76807.50, '2026-09-01 06:26:08'),
(115, '2026-08-31', 77, 'SALE', 64, 'Sale Invoice: SAL-00060 - 6mm Looking (37.60 x 56.00)', 11375.00, 11375.00, 0.00, '2026-09-01 06:36:46'),
(116, '2026-08-31', 73, 'SALE', 67, 'Sale Invoice: SAL-00063 - 12mm White (72.00 x 80.20), 12mm White (11.60 x 79.00), 12mm White (31.50 x 28.10)', 43297.50, 0.00, 43297.50, '2026-09-01 06:37:09'),
(118, '2026-08-31', 70, 'SALE', 69, 'Sale Invoice: SAL-00065 - 6mm White (45 x 48), 6mm White (16 x 42)', 5100.00, 0.00, -479916.50, '2026-09-01 06:41:16'),
(119, '2026-09-01', 65, 'PAYMENT', 11, 'Payment received - ', 0.00, 1500.00, 1250.00, '2026-09-01 06:44:17'),
(120, '2026-08-31', 66, 'SALE', 58, 'Sale Invoice: SAL-00054 - 12mm White (22.00 x 60.00), 12mm White (22.00 x 48.00), 12mm White (26.00 x 61.00) and 1 more items', 64170.00, 0.00, 64170.00, '2026-09-01 06:47:30'),
(121, '2026-09-01', 79, 'SALE', 70, 'Sale Invoice: SAL-00066 - 12mm White (51 x 102), 12mm White (54 x 102), 12mm White (45 x 102)', 246330.00, 100000.00, 146330.00, '2026-09-01 08:15:32'),
(122, '2026-09-01', 79, 'SALE', 71, 'Sale Invoice: SAL-00067 - 6mm Looking (54 x 84)', 40635.00, 0.00, 186965.00, '2026-09-01 08:17:38'),
(123, '2026-09-01', 80, 'SALE', 72, 'Sale Invoice: SAL-00068 - 12mm White (15.3 x 73.6)', 6727.50, 0.00, 6727.50, '2026-09-01 08:20:52'),
(124, '2026-09-01', 63, 'SALE', 73, 'Sale Invoice: SAL-00069 - 8mm White (54 x 85.6), 6mm White (32.4 x 55.4), 6mm White (21 x 51.7) and 2 more items', 53475.00, 0.00, 53475.00, '2026-09-01 08:30:17'),
(125, '2026-09-01', 63, 'SALE', 74, 'Sale Invoice: SAL-00070 - 6MM Grey looking (84 x 144), 6MM Grey looking (72 x 96), 6MM Grey looking (45 x 68)', 78000.00, 0.00, 131475.00, '2026-09-01 08:32:46'),
(126, '2026-09-01', 81, 'SALE', 75, 'Sale Invoice: SAL-00071 - 6mm Looking (27.6 x 31.6), 6mm Looking (27.6 x 28.6)', 6325.00, 0.00, 6325.00, '2026-09-01 08:37:23'),
(127, '2026-09-01', 82, 'SALE', 76, 'Sale Invoice: SAL-00072 - 8mm Brown (41 x 41)', 11515.00, 5000.00, 6515.00, '2026-09-01 08:50:38'),
(128, '2026-09-01', 83, 'SALE', 77, 'Sale Invoice: SAL-00073 - 8mm White (21 x 42), 8mm White (21 x 15.2), 8mm White (13.4 x 28.6) and 2 more items', 12990.00, 12990.00, 0.00, '2026-09-01 08:55:41'),
(129, '2026-09-01', 84, 'SALE', 78, 'Sale Invoice: SAL-00074 - 8mm White (4.6 x 18), 8mm White (9.2 x 90)', 5040.00, 5040.00, 0.00, '2026-09-01 09:01:08'),
(130, '2026-09-01', 85, 'OPENING', 85, 'Opening Balance - Receivable (Customer owes company)', 24640.00, 0.00, 24640.00, '2026-09-01 09:08:49'),
(131, '2026-09-01', 66, 'SALE', 79, 'Sale Invoice: SAL-00075 - 12mm White (12 x 90), 6mm Looking (24 x 36), 6mm Looking (30 x 36)', 12240.00, 0.00, 76410.00, '2026-09-01 09:14:24'),
(132, '2026-09-01', 66, 'ADJUSTMENT', 0, '[Manual Debit] cutting Purchi ka bill hai', 119835.00, 0.00, 196245.00, '2026-09-01 09:16:49'),
(134, '2026-09-01', 85, 'SALE', 80, 'Sale Invoice: SAL-00076 - 6mm White (23.00 x 81.10), 6mm White (23.00 x 81.60)', 8680.00, 0.00, 33320.00, '2026-09-01 09:20:28'),
(135, '2026-09-01', 86, 'SALE', 81, 'Sale Invoice: SAL-00077 - 6mm Looking (24 x 30), 6mm Looking (24 x 24)', 6100.00, 0.00, 6100.00, '2026-09-01 09:25:03'),
(136, '2026-09-01', 87, 'SALE', 82, 'Sale Invoice: SAL-00078 - 12mm White (37.6 x 84)', 15697.50, 0.00, 15697.50, '2026-09-01 09:29:29'),
(137, '2026-09-01', 34, 'SALE', 83, 'Sale Invoice: SAL-00079 - 5mm white (30.3 x 68)', 4140.00, 4140.00, 586785.00, '2026-09-01 11:37:40'),
(138, '2026-09-01', 88, 'SALE', 84, 'Sale Invoice: SAL-00080 - 8mm White (23.4 x 27.1)', 2200.00, 0.00, 2200.00, '2026-09-01 11:40:05'),
(139, '2026-09-01', 89, 'SALE', 85, 'Sale Invoice: SAL-00081 - 5.5 Green (15 x 16.7), 5.5 Green (30 x 72), 5.5 Green (19.2 x 71.6)', 17520.00, 0.00, 17520.00, '2026-09-01 11:46:59'),
(140, '2026-09-01', 90, 'SALE', 86, 'Sale Invoice: SAL-00082 - 6mm White (36 x 36), 6mm White (35.6 x 35.6)', 5130.00, 0.00, 5130.00, '2026-09-01 11:55:13'),
(141, '2026-09-01', 66, 'SALE', 87, 'Sale Invoice: SAL-00083 - 12mm White (22.2 x 66), 12mm White (22.2 x 54)', 13800.00, 0.00, 210045.00, '2026-09-01 12:07:32'),
(142, '2026-09-01', 91, 'OPENING', 91, 'Opening Balance - Receivable (Customer owes company)', 165442.00, 0.00, 165442.00, '2026-09-01 12:15:03'),
(143, '2026-09-01', 91, 'SALE', 88, 'Sale Invoice: SAL-00084 - 12mm White (45 x 84), 12mm White (24 x 77.4), 12mm White (19.4 x 30)', 283676.25, 0.00, 449118.25, '2026-09-01 12:26:34'),
(145, '2026-09-01', 57, 'SALE', 90, 'Sale Invoice: SAL-00086 - 12mm White (21.6 x 84)', 9660.00, 0.00, 628308.00, '2026-09-01 12:35:04'),
(146, '2026-09-01', 81, 'SALE', 91, 'Sale Invoice: SAL-00087 - 6mm White (23.4 x 36), 12mm White (24 x 82.1)', 22620.00, 22620.00, 6325.00, '2026-09-01 12:48:10'),
(147, '2026-09-01', 81, 'PAYMENT', 12, 'Payment received - ', 0.00, 6325.00, 0.00, '2026-09-01 12:49:41'),
(148, '2026-09-01', 34, 'SALE', 92, 'Sale Invoice: SAL-00088 - 5mm white (9.5 x 84), 5mm white (30.3 x 72)', 5405.00, 0.00, 592190.00, '2026-09-01 12:54:22'),
(149, '2026-09-01', 92, 'SALE', 93, 'Sale Invoice: SAL-00089 - 6mm White (61.4 x 45), 6mm White (61.4 x 45), 6mm White (46 x 45)', 26100.00, 26100.00, 0.00, '2026-09-01 12:56:40'),
(153, '2026-09-01', 58, 'SALE', 96, 'Sale Invoice: SAL-00090 - 6mm Brown (16.2 x 48.2)', 2801.25, 2801.25, 0.00, '2026-09-01 13:27:26'),
(154, '2026-09-01', 55, 'SALE', 97, 'Sale Invoice: SAL-00091 - 6mm Brown (17.2 x 23.1), 6mm Brown (16.5 x 23), 6mm Brown (35.6 x 18) and 2 more items', 7425.00, 0.00, 3926124.00, '2026-09-01 14:55:21'),
(155, '2026-09-01', 32, 'SALE', 66, 'Sale Invoice: SAL-00062 - 8mm White (23.70 x 26.60), 8mm White (23.70 x 38.10), 12mm White (42.00 x 109.50)', 27782.50, 0.00, 5889898.00, '2026-09-01 14:57:03'),
(156, '2026-09-01', 32, 'SALE', 98, 'Sale Invoice: SAL-00092 - 6mm White (16 x 34)', 1215.00, 0.00, 5891113.00, '2026-09-01 14:58:39'),
(157, '2026-09-01', 95, 'SALE', 99, 'Sale Invoice: SAL-00093 - 12mm Brown (15 x 60)', 10500.00, 10500.00, 0.00, '2026-09-01 15:00:27'),
(158, '2026-09-01', 58, 'SALE', 100, 'Sale Invoice: SAL-00094 - 12mm Grey (28 x 58.4)', 17500.00, 17500.00, 0.00, '2026-09-01 15:01:36'),
(159, '2026-09-01', 58, 'SALE', 101, 'Sale Invoice: SAL-00095 - 12mm Grey (60 x 102)', 59500.00, 59500.00, 0.00, '2026-09-01 15:02:34'),
(160, '2026-09-01', 96, 'SALE', 102, 'Sale Invoice: SAL-00096 - 12mm White (28 x 52), 12mm White (28 x 51.5), 12mm White (21 x 120) and 1 more items', 36915.00, 35000.00, 1915.00, '2026-09-01 15:05:26'),
(161, '2026-09-01', 96, 'SALE', 103, 'Sale Invoice: SAL-00097 - 12mm White (28 x 52), 12mm White (28 x 51.5), 12mm White (21 x 120) and 2 more items', 39675.00, 35000.00, 6590.00, '2026-09-01 15:08:53'),
(162, '2026-09-01', 97, 'SALE', 104, 'Sale Invoice: SAL-00098 - 12mm White (8 x 60), 12mm White (8 x 54)', 27255.00, 27255.00, 0.00, '2026-09-01 15:10:57'),
(165, '2026-09-01', 93, 'SALE', 106, 'Sale Invoice: SAL-00100 - 8mm White (32.50 x 98.30), 8mm White (35.40 x 91.20), 8mm White (46.00 x 68.00) and 5 more items', 101190.00, 0.00, 101190.00, '2026-09-01 15:34:49'),
(166, '2026-09-01', 98, 'SALE', 105, 'Sale Invoice: SAL-00099 - 6mm White (72.00 x 84.00), 6mm Brown (72.00 x 84.00), 4mm looking (72.00 x 84.00) and 1 more items', 332140.00, 0.00, 332140.00, '2026-09-01 15:35:24'),
(167, '2026-09-01', 100, 'SALE', 107, 'Sale Invoice: SAL-00101 - 12mm White (12 x 45.6), 12mm White (8.4 x 90), 6mm Looking (18 x 24) and 1 more items', 22895.00, 0.00, 22895.00, '2026-09-01 15:42:16'),
(168, '2026-09-01', 99, 'SALE', 108, 'Sale Invoice: SAL-00102 - 12mm White (29.6 x 84), 12mm White (18 x 30), 12mm White (30 x 102) and 1 more items', 43985.00, 0.00, 43985.00, '2026-09-01 15:44:21'),
(169, '2026-08-31', 29, 'SALE', 47, 'Sale Invoice: SAL-00043 - 12mm White (30.00 x 84.00), 12mm White (42.00 x 108.00), 12mm White (21.00 x 60.00) and 2 more items', 58608.75, 0.00, 58608.75, '2026-09-01 15:48:35'),
(170, '2026-08-31', 74, 'SALE', 68, 'Sale Invoice: SAL-00064 - 6mm White (42.00 x 42.00)', 3675.00, 3675.00, 0.00, '2026-09-01 15:50:46'),
(171, '2026-08-31', 76, 'SALE', 62, 'Sale Invoice: SAL-00058 - 3mm looking (24.00 x 72.00), 3mm looking (18.00 x 72.00), 4mm looking (30.00 x 30.00)', 10150.00, 10150.00, 0.00, '2026-09-01 15:51:54'),
(172, '2026-08-31', 75, 'SALE', 63, 'Sale Invoice: SAL-00059 - 8mm White (30.00 x 84.00), 12mm White (12.00 x 36.00), 6mm Grey Mercury (6.00 x 80.20) and 2 more items', 19670.00, 19670.00, 0.00, '2026-09-01 15:53:30'),
(173, '2026-08-31', 62, 'SALE', 44, 'Sale Invoice: SAL-00040 - 6mm White (11.00 x 24.00), 6mm White (17.00 x 71.00), 6mm White (10.00 x 48.20) and 5 more items', 36031.20, 36031.20, 0.00, '2026-09-01 15:55:51'),
(174, '2026-09-01', 79, 'SALE', 89, 'Sale Invoice: SAL-00085 - 12mm White (35.50 x 84.00), 12mm White (48.00 x 102.00), 12mm White (66.00 x 102.00) and 6 more items', 251096.80, 251096.80, 186965.00, '2026-09-01 15:57:14'),
(175, '2026-09-02', 55, 'SALE', 109, 'Sale Invoice: SAL-00103 - 8mm White (48 x 72)', 39360.00, 0.00, 3965484.00, '2026-09-02 06:06:17'),
(176, '2026-09-02', 101, 'SALE', 110, 'Sale Invoice: SAL-00104 - 8mm White (12.3 x 96), 8mm White (12.3 x 60)', 8580.00, 0.00, 8580.00, '2026-09-02 06:19:19'),
(177, '2026-09-02', 102, 'SALE', 111, 'Sale Invoice: SAL-00105 - 6mm White (36 x 36)', 2430.00, 0.00, 2430.00, '2026-09-02 06:25:36'),
(178, '2026-09-02', 103, 'SALE', 112, 'Sale Invoice: SAL-00106 - 12mm White (48 x 48)', 12000.00, 7000.00, 5000.00, '2026-09-02 06:30:47'),
(179, '2026-09-02', 104, 'SALE', 113, 'Sale Invoice: SAL-00107 - 6mm Looking (20 x 30), 8mm White (18 x 33)', 7217.50, 3980.00, 3237.50, '2026-09-02 06:34:32');

-- --------------------------------------------------------

--
-- Table structure for table `customer_payments`
--

CREATE TABLE `customer_payments` (
  `id` int NOT NULL,
  `payment_date` date NOT NULL,
  `customer_id` int NOT NULL,
  `payment_method` enum('cash','bank') COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `sale_invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
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
  `payment_method` enum('cash','bank') COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `sale_invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
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
(7, '2026-08-25', 6, 'cash', NULL, '', 500.00, NULL, '', 1, '2026-08-25 10:55:21'),
(8, '2026-08-07', 50, 'bank', 1, '030020', 200000.00, NULL, '', 1, '2026-08-28 13:47:01'),
(9, '2026-08-29', 41, 'cash', NULL, 'Monshe shabbir ', 200000.00, NULL, 'Nawaz glass ', 1, '2026-08-29 16:57:40'),
(10, '2026-08-31', 32, 'cash', NULL, 'shafiq sab', 175000.00, NULL, '', 1, '2026-08-31 13:04:37'),
(11, '2026-09-01', 65, 'bank', 1, '', 1500.00, NULL, '', 1, '2026-09-01 06:44:17'),
(12, '2026-09-01', 81, 'cash', NULL, '', 6325.00, NULL, '', 1, '2026-09-01 12:49:41');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int NOT NULL,
  `employee_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `father_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_type` enum('permanent','contract','daily_wage','commission') COLLATE utf8mb4_unicode_ci DEFAULT 'permanent',
  `cnic` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `joining_date` date DEFAULT NULL,
  `basic_salary` decimal(15,2) DEFAULT '0.00',
  `allowances` decimal(15,2) DEFAULT '0.00',
  `deductions` decimal(15,2) DEFAULT '0.00',
  `net_salary` decimal(15,2) DEFAULT '0.00',
  `opening_balance` decimal(15,2) DEFAULT '0.00',
  `balance_type` enum('payable','advance') COLLATE utf8mb4_unicode_ci DEFAULT 'payable',
  `current_balance` decimal(15,2) DEFAULT '0.00',
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_code`, `employee_name`, `father_name`, `designation`, `department`, `employee_type`, `cnic`, `mobile`, `email`, `address`, `joining_date`, `basic_salary`, `allowances`, `deductions`, `net_salary`, `opening_balance`, `balance_type`, `current_balance`, `bank_name`, `bank_account_no`, `status`, `notes`, `created_at`) VALUES
(2, 'EMP-0002', 'Faheem', 'Ashraf', 'lahore', 'computer science', 'contract', '352145697455', '03214569745', 'faheem@gmail.com', 'abc1', '2026-07-30', 45000.00, 5000.00, 0.00, 50000.00, 10000.00, 'payable', 9048.00, 'faysal bank', '7896544133544', 1, '', '2026-07-30 12:41:04');

-- --------------------------------------------------------

--
-- Table structure for table `employee_ledger`
--

CREATE TABLE `employee_ledger` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `employee_id` int NOT NULL,
  `reference_type` enum('OPENING','SALARY','PAYMENT','ADVANCE','ADJUSTMENT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `debit` decimal(15,2) DEFAULT '0.00',
  `credit` decimal(15,2) DEFAULT '0.00',
  `balance` decimal(15,2) DEFAULT '0.00',
  `month_year` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_ledger`
--

INSERT INTO `employee_ledger` (`id`, `date`, `employee_id`, `reference_type`, `reference_id`, `description`, `debit`, `credit`, `balance`, `month_year`, `created_at`) VALUES
(1, '2026-07-30', 2, 'OPENING', 2, 'Opening Balance - Payable (Company owes employee)', 0.00, 10000.00, 10000.00, NULL, '2026-07-30 12:41:04'),
(2, '2026-07-30', 2, 'PAYMENT', 1, 'Salary payment made - Cash for 2026-07', 452.00, 0.00, 9548.00, '2026-07', '2026-07-30 12:49:08'),
(3, '2026-08-31', 2, 'PAYMENT', 2, 'Salary payment made - Cash for 2026-08', 500.00, 0.00, 9048.00, '2026-08', '2026-08-31 13:18:41');

-- --------------------------------------------------------

--
-- Table structure for table `employee_payments`
--

CREATE TABLE `employee_payments` (
  `id` int NOT NULL,
  `payment_date` date NOT NULL,
  `employee_id` int NOT NULL,
  `payment_method` enum('cash','bank') COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `salary_month` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_payments`
--

INSERT INTO `employee_payments` (`id`, `payment_date`, `employee_id`, `payment_method`, `bank_account_id`, `reference_no`, `amount`, `salary_month`, `remarks`, `created_by`, `created_at`) VALUES
(1, '2026-07-30', 2, 'cash', 0, '', 452.00, '2026-07', '', 1, '2026-07-30 12:49:08'),
(2, '2026-08-31', 2, 'cash', 0, '', 500.00, '2026-08', '', 1, '2026-08-31 13:18:41');

-- --------------------------------------------------------

--
-- Table structure for table `employee_salary`
--

CREATE TABLE `employee_salary` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `month_year` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `basic_salary` decimal(15,2) DEFAULT '0.00',
  `allowances` decimal(15,2) DEFAULT '0.00',
  `deductions` decimal(15,2) DEFAULT '0.00',
  `net_salary` decimal(15,2) DEFAULT '0.00',
  `paid_amount` decimal(15,2) DEFAULT '0.00',
  `remaining_amount` decimal(15,2) DEFAULT '0.00',
  `status` enum('pending','partial','paid') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
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
  `payment_method` enum('cash','bank') COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
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
  `head_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
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
  `description` text COLLATE utf8mb4_unicode_ci,
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
-- Table structure for table `hold_quotations_details`
--

CREATE TABLE `hold_quotations_details` (
  `id` int NOT NULL,
  `hold_id` int NOT NULL,
  `product_id` int NOT NULL,
  `client_height` decimal(10,2) DEFAULT '0.00',
  `client_width` decimal(10,2) DEFAULT '0.00',
  `client_size` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `multiple_of` int DEFAULT '6',
  `std_height` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `std_width` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uom` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Inch',
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `area` decimal(15,2) DEFAULT '0.00',
  `rate` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hold_quotations_master`
--

CREATE TABLE `hold_quotations_master` (
  `id` int NOT NULL,
  `hold_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hold_date` date NOT NULL,
  `customer_id` int NOT NULL,
  `valid_until` date DEFAULT NULL,
  `reference_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `other_charges` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `received_amount` decimal(15,2) DEFAULT '0.00',
  `remaining_amount` decimal(15,2) DEFAULT '0.00',
  `payment_type` enum('cash','bank','credit','partial') COLLATE utf8mb4_unicode_ci DEFAULT 'credit',
  `bank_account_id` int DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `status` enum('hold','converted','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'hold',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
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
  `id` int NOT NULL,
  `hold_id` int NOT NULL,
  `product_id` int NOT NULL,
  `client_height` decimal(10,2) DEFAULT '0.00',
  `client_width` decimal(10,2) DEFAULT '0.00',
  `multiple_of` int DEFAULT '6',
  `std_height` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `std_width` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uom` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Inch',
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
(54, 14, 35, 16.20, 48.20, 6, '18', '54', '', 1.00, 6.75, 330.00, 0.00, 2227.50);

-- --------------------------------------------------------

--
-- Table structure for table `hold_sales_master`
--

CREATE TABLE `hold_sales_master` (
  `id` int NOT NULL,
  `hold_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hold_date` date NOT NULL,
  `customer_id` int NOT NULL,
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `other_charges` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `status` enum('hold','converted','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'hold',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
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
(12, 'HOLD-00009', '2026-08-11', 6, 82.50, 0.00, 0.00, 0.00, 82.50, '', 'hold', 1, '2026-08-11 07:22:20'),
(14, 'HOLD-00010', '2026-09-01', 58, 2227.50, 0.00, 0.00, 0.00, 2227.50, '', 'hold', 1, '2026-09-01 13:23:35');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_ledger`
--

CREATE TABLE `inventory_ledger` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `product_id` int NOT NULL,
  `reference_type` enum('OPENING','PURCHASE','SALE','ADJUSTMENT','QUOTATION') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` int NOT NULL,
  `qty_in` decimal(15,2) DEFAULT '0.00',
  `qty_out` decimal(15,2) DEFAULT '0.00',
  `balance_qty` decimal(15,2) DEFAULT '0.00',
  `unit_price` decimal(15,2) DEFAULT '0.00',
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_ledger`
--

INSERT INTO `inventory_ledger` (`id`, `date`, `product_id`, `reference_type`, `reference_id`, `qty_in`, `qty_out`, `balance_qty`, `unit_price`, `total_amount`, `remarks`, `created_at`) VALUES
(450, '2026-08-28', 18, 'OPENING', 18, 1512.00, 0.00, 1512.00, 447.12, 676045.44, 'Opening Stock Entry - Opening Stock: 18 pieces of 7 x 12 ft, total 1512 sq ft', '2026-08-28 15:17:32'),
(451, '2026-08-28', 19, 'OPENING', 19, 168.00, 0.00, 168.00, 447.12, 75116.16, 'Opening Stock Entry - Opening Stock: 2 pieces of 7 x 12 ft, total 168 sq ft', '2026-08-28 15:19:08'),
(452, '2026-08-28', 18, 'SALE', 37, 0.00, 5.25, 1506.75, 590.00, 3097.50, 'Sale Invoice: SAL-00033 - Total Area: 5.25 sq ft', '2026-08-28 15:23:44'),
(453, '2026-08-28', 18, 'SALE', 37, 0.00, 5.25, 1501.50, 590.00, 3097.50, 'Sale Invoice: SAL-00033 - Total Area: 5.25 sq ft', '2026-08-28 15:23:44'),
(454, '2026-08-28', 18, 'SALE', 37, 0.00, 3.50, 1498.00, 590.00, 2065.00, 'Sale Invoice: SAL-00033 - Total Area: 3.5 sq ft', '2026-08-28 15:23:44'),
(455, '2026-08-28', 19, 'SALE', 37, 0.00, 24.00, 144.00, 510.00, 12240.00, 'Sale Invoice: SAL-00033 - Total Area: 24 sq ft', '2026-08-28 15:23:44'),
(467, '2026-08-29', 20, 'OPENING', 20, 9366.00, 0.00, 9366.00, 190.38, 1783099.08, 'Opening Stock Entry - Opening Stock: 223 pieces of 6 x 7 ft, total 9366 sq ft', '2026-08-29 14:39:59'),
(468, '2026-08-29', 21, 'OPENING', 21, 1680.00, 0.00, 1680.00, 231.89, 389575.20, 'Opening Stock Entry - Opening Stock: 20 pieces of 7 x 12 ft, total 1680 sq ft', '2026-08-29 14:42:18'),
(469, '2026-08-29', 22, 'OPENING', 22, 16464.00, 0.00, 16464.00, 247.63, 4076980.32, 'Opening Stock Entry - Opening Stock: 196 pieces of 7 x 12 ft, total 16464 sq ft', '2026-08-29 14:51:26'),
(470, '2026-08-29', 22, 'OPENING', 22, 3648.00, 0.00, 20112.00, 247.63, 903354.24, 'Opening Stock Entry - Opening Stock: 38 pieces of 8 x 12 ft, total 3648 sq ft', '2026-08-29 14:51:26'),
(471, '2026-08-29', 23, 'OPENING', 23, 4320.00, 0.00, 4320.00, 265.52, 1147046.40, 'Opening Stock Entry - Opening Stock: 48 pieces of 7.5 x 12 ft, total 4320 sq ft', '2026-08-29 14:55:59'),
(472, '2026-08-29', 23, 'OPENING', 23, 13920.00, 0.00, 18240.00, 265.52, 3696038.40, 'Opening Stock Entry - Opening Stock: 145 pieces of 8 x 12 ft, total 13920 sq ft', '2026-08-29 14:56:00'),
(473, '2026-08-29', 23, 'OPENING', 23, 35028.00, 0.00, 53268.00, 265.52, 9300634.56, 'Opening Stock Entry - Opening Stock: 417 pieces of 7 x 12 ft, total 35028 sq ft', '2026-08-29 14:56:00'),
(474, '2026-08-29', 23, 'OPENING', 23, 1820.00, 0.00, 55088.00, 265.52, 483246.40, 'Opening Stock Entry - Opening Stock: 26 pieces of 7 x 10 ft, total 1820 sq ft', '2026-08-29 14:56:00'),
(475, '2026-08-29', 23, 'OPENING', 23, 336.00, 0.00, 55424.00, 265.52, 89214.72, 'Opening Stock Entry - Opening Stock: 8 pieces of 6 x 7 ft, total 336 sq ft', '2026-08-29 14:56:00'),
(476, '2026-08-29', 23, 'OPENING', 23, 96.00, 0.00, 55520.00, 265.52, 25489.92, 'Opening Stock Entry - Opening Stock: 2 pieces of 6 x 8 ft, total 96 sq ft', '2026-08-29 14:56:00'),
(477, '2026-08-29', 24, 'OPENING', 24, 7560.00, 0.00, 7560.00, 463.06, 3500733.60, 'Opening Stock Entry - Opening Stock: 90 pieces of 7 x 12 ft, total 7560 sq ft', '2026-08-29 15:01:46'),
(478, '2026-08-29', 24, 'OPENING', 24, 6528.00, 0.00, 14088.00, 463.06, 3022855.68, 'Opening Stock Entry - Opening Stock: 68 pieces of 8 x 12 ft, total 6528 sq ft', '2026-08-29 15:01:46'),
(479, '2026-08-29', 24, 'OPENING', 24, 4770.00, 0.00, 18858.00, 463.06, 2208796.20, 'Opening Stock Entry - Opening Stock: 53 pieces of 7.5 x 12 ft, total 4770 sq ft', '2026-08-29 15:01:46'),
(480, '2026-08-29', 24, 'OPENING', 24, 504.00, 0.00, 19362.00, 463.06, 233382.24, 'Opening Stock Entry - Opening Stock: 12 pieces of 6 x 7 ft, total 504 sq ft', '2026-08-29 15:01:46'),
(481, '2026-08-29', 24, 'OPENING', 24, 1470.00, 0.00, 20832.00, 463.06, 680698.20, 'Opening Stock Entry - Opening Stock: 42 pieces of 5 x 7 ft, total 1470 sq ft', '2026-08-29 15:01:46'),
(482, '2026-08-29', 25, 'OPENING', 25, 6708.00, 0.00, 6708.00, 745.04, 4997728.32, 'Opening Stock Entry - Opening Stock: 86 pieces of 6.5 x 12 ft, total 6708 sq ft', '2026-08-29 15:06:29'),
(483, '2026-08-29', 25, 'OPENING', 25, 12684.00, 0.00, 19392.00, 745.04, 9450087.36, 'Opening Stock Entry - Opening Stock: 151 pieces of 7 x 12 ft, total 12684 sq ft', '2026-08-29 15:06:29'),
(484, '2026-08-29', 25, 'OPENING', 25, 6120.00, 0.00, 25512.00, 745.04, 4559644.80, 'Opening Stock Entry - Opening Stock: 68 pieces of 7.5 x 12 ft, total 6120 sq ft', '2026-08-29 15:06:29'),
(485, '2026-08-29', 25, 'OPENING', 25, 12096.00, 0.00, 37608.00, 745.04, 9012003.84, 'Opening Stock Entry - Opening Stock: 126 pieces of 8 x 12 ft, total 12096 sq ft', '2026-08-29 15:06:29'),
(486, '2026-08-29', 25, 'OPENING', 25, 7140.00, 0.00, 44748.00, 745.04, 5319585.60, 'Opening Stock Entry - Opening Stock: 70 pieces of 8.5 x 12 ft, total 7140 sq ft', '2026-08-29 15:06:29'),
(487, '2026-08-29', 25, 'OPENING', 25, 225.00, 0.00, 44973.00, 745.04, 167634.00, 'Opening Stock Entry - Opening Stock: 5 pieces of 6 x 7.5 ft, total 225 sq ft', '2026-08-29 15:06:29'),
(488, '2026-08-29', 26, 'OPENING', 26, 5040.00, 0.00, 5040.00, 328.37, 1654984.80, 'Opening Stock Entry - Opening Stock: 60 pieces of 7 x 12 ft, total 5040 sq ft', '2026-08-29 15:11:28'),
(489, '2026-08-29', 27, 'OPENING', 27, 288.00, 0.00, 288.00, 948.13, 273061.44, 'Opening Stock Entry - Opening Stock: 3 pieces of 8 x 12 ft, total 288 sq ft', '2026-08-29 15:13:51'),
(490, '2026-08-29', 28, 'OPENING', 28, 768.00, 0.00, 768.00, 1055.78, 810839.04, 'Opening Stock Entry - Opening Stock: 8 pieces of 8 x 12 ft, total 768 sq ft', '2026-08-29 15:15:47'),
(491, '2026-08-29', 28, 'OPENING', 28, 336.00, 0.00, 1104.00, 1055.78, 354742.08, 'Opening Stock Entry - Opening Stock: 8 pieces of 6 x 7 ft, total 336 sq ft', '2026-08-29 15:15:47'),
(492, '2026-08-29', 29, 'OPENING', 29, 1680.00, 0.00, 1680.00, 1643.78, 2761550.40, 'Opening Stock Entry - Opening Stock: 20 pieces of 7 x 12 ft, total 1680 sq ft', '2026-08-29 15:17:20'),
(493, '2026-08-29', 29, 'OPENING', 29, 1152.00, 0.00, 2832.00, 1643.78, 1893634.56, 'Opening Stock Entry - Opening Stock: 12 pieces of 8 x 12 ft, total 1152 sq ft', '2026-08-29 15:17:20'),
(495, '2026-08-29', 31, 'OPENING', 31, 1676.16, 0.00, 1676.16, 345.70, 579448.20, 'Opening Stock Entry - Opening Stock: 21 pieces of 7.37 x 10.83 ft, total 1676.1591 sq ft', '2026-08-29 15:22:35'),
(496, '2026-08-29', 32, 'OPENING', 32, 2436.00, 0.00, 2436.00, 359.56, 875888.16, 'Opening Stock Entry - Opening Stock: 29 pieces of 7 x 12 ft, total 2436 sq ft', '2026-08-29 15:24:13'),
(498, '2026-08-29', 34, 'OPENING', 34, 14616.00, 0.00, 14616.00, 345.70, 5052751.20, 'Opening Stock Entry - Opening Stock: 174 pieces of 7 x 12 ft, total 14616 sq ft', '2026-08-29 15:28:33'),
(499, '2026-08-29', 34, 'OPENING', 34, 3936.00, 0.00, 18552.00, 345.70, 1360675.20, 'Opening Stock Entry - Opening Stock: 41 pieces of 8 x 12 ft, total 3936 sq ft', '2026-08-29 15:28:33'),
(546, '2026-08-29', 35, 'OPENING', 35, 12348.00, 0.00, 12348.00, 359.56, 4439846.88, 'Opening Stock Entry - Opening Stock: 147 pieces of 7 x 12 ft, total 12348 sq ft', '2026-08-29 15:43:38'),
(547, '2026-08-29', 35, 'OPENING', 35, 5376.00, 0.00, 17724.00, 359.56, 1932994.56, 'Opening Stock Entry - Opening Stock: 56 pieces of 8 x 12 ft, total 5376 sq ft', '2026-08-29 15:43:38'),
(548, '2026-08-29', 36, 'OPENING', 36, 84.00, 0.00, 84.00, 429.57, 36083.88, 'Opening Stock Entry - Opening Stock: 1 pieces of 7 x 12 ft, total 84 sq ft', '2026-08-29 15:49:27'),
(549, '2026-08-29', 36, 'OPENING', 36, 2880.00, 0.00, 2964.00, 429.57, 1237161.60, 'Opening Stock Entry - Opening Stock: 32 pieces of 7.5 x 12 ft, total 2880 sq ft', '2026-08-29 15:49:27'),
(550, '2026-08-29', 36, 'OPENING', 36, 8832.00, 0.00, 11796.00, 429.57, 3793962.24, 'Opening Stock Entry - Opening Stock: 92 pieces of 8 x 12 ft, total 8832 sq ft', '2026-08-29 15:49:27'),
(551, '2026-08-29', 37, 'OPENING', 37, 11520.00, 0.00, 11520.00, 429.57, 4948646.40, 'Opening Stock Entry - Opening Stock: 120 pieces of 8 x 12 ft, total 11520 sq ft', '2026-08-29 15:52:36'),
(552, '2026-08-29', 37, 'OPENING', 37, 4032.00, 0.00, 15552.00, 429.57, 1732026.24, 'Opening Stock Entry - Opening Stock: 48 pieces of 7 x 12 ft, total 4032 sq ft', '2026-08-29 15:52:36'),
(553, '2026-08-29', 38, 'OPENING', 38, 7104.00, 0.00, 7104.00, 413.53, 2937717.12, 'Opening Stock Entry - Opening Stock: 74 pieces of 8 x 12 ft, total 7104 sq ft', '2026-08-29 15:55:01'),
(554, '2026-08-29', 38, 'OPENING', 38, 1764.00, 0.00, 8868.00, 413.53, 729466.92, 'Opening Stock Entry - Opening Stock: 21 pieces of 7 x 12 ft, total 1764 sq ft', '2026-08-29 15:55:01'),
(555, '2026-08-29', 39, 'OPENING', 39, 5760.00, 0.00, 5760.00, 482.32, 2778163.20, 'Opening Stock Entry - Opening Stock: 60 pieces of 8 x 12 ft, total 5760 sq ft', '2026-08-29 15:57:07'),
(556, '2026-08-29', 39, 'OPENING', 39, 84.00, 0.00, 5844.00, 482.32, 40514.88, 'Opening Stock Entry - Opening Stock: 1 pieces of 7 x 12 ft, total 84 sq ft', '2026-08-29 15:57:07'),
(557, '2026-08-29', 40, 'OPENING', 40, 2604.00, 0.00, 2604.00, 429.57, 1118600.28, 'Opening Stock Entry - Opening Stock: 31 pieces of 7 x 12 ft, total 2604 sq ft', '2026-08-29 15:59:43'),
(558, '2026-08-29', 41, 'OPENING', 41, 2856.00, 0.00, 2856.00, 429.57, 1226851.92, 'Opening Stock Entry - Opening Stock: 34 pieces of 7 x 12 ft, total 2856 sq ft', '2026-08-29 16:02:53'),
(559, '2026-08-29', 42, 'OPENING', 42, 648.00, 0.00, 648.00, 231.79, 150199.92, 'Opening Stock Entry - Opening Stock: 18 pieces of 6 x 6 ft, total 648 sq ft', '2026-08-29 16:06:14'),
(560, '2026-08-29', 42, 'OPENING', 42, 6552.00, 0.00, 7200.00, 231.79, 1518688.08, 'Opening Stock Entry - Opening Stock: 156 pieces of 6 x 7 ft, total 6552 sq ft', '2026-08-29 16:06:14'),
(561, '2026-08-29', 43, 'OPENING', 43, 468.00, 0.00, 468.00, 280.86, 131442.48, 'Opening Stock Entry - Opening Stock: 13 pieces of 6 x 6 ft, total 468 sq ft', '2026-08-29 16:08:06'),
(562, '2026-08-29', 43, 'OPENING', 43, 840.00, 0.00, 1308.00, 280.86, 235922.40, 'Opening Stock Entry - Opening Stock: 20 pieces of 6 x 7 ft, total 840 sq ft', '2026-08-29 16:08:06'),
(563, '2026-08-29', 43, 'OPENING', 43, 2772.00, 0.00, 4080.00, 280.86, 778543.92, 'Opening Stock Entry - Opening Stock: 33 pieces of 7 x 12 ft, total 2772 sq ft', '2026-08-29 16:08:06'),
(564, '2026-08-29', 44, 'OPENING', 44, 2268.00, 0.00, 2268.00, 324.17, 735217.56, 'Opening Stock Entry - Opening Stock: 54 pieces of 6 x 7 ft, total 2268 sq ft', '2026-08-29 16:10:25'),
(565, '2026-08-29', 45, 'OPENING', 45, 14784.00, 0.00, 14784.00, 411.86, 6088938.24, 'Opening Stock Entry - Opening Stock: 176 pieces of 7 x 12 ft, total 14784 sq ft', '2026-08-29 16:12:58'),
(566, '2026-08-29', 45, 'OPENING', 45, 4992.00, 0.00, 19776.00, 411.86, 2056005.12, 'Opening Stock Entry - Opening Stock: 52 pieces of 8 x 12 ft, total 4992 sq ft', '2026-08-29 16:12:58'),
(567, '2026-08-29', 46, 'OPENING', 46, 6636.00, 0.00, 6636.00, 396.11, 2628585.96, 'Opening Stock Entry - Opening Stock: 79 pieces of 7 x 12 ft, total 6636 sq ft', '2026-08-29 16:19:09'),
(568, '2026-08-29', 46, 'OPENING', 46, 600.00, 0.00, 7236.00, 396.11, 237666.00, 'Opening Stock Entry - Opening Stock: 100 pieces of 2 x 3 ft, total 600 sq ft', '2026-08-29 16:19:09'),
(569, '2026-08-31', 36, 'SALE', 39, 0.00, 24.75, 11771.25, 390.00, 9652.50, 'Sale Invoice: SAL-00035 - Total Area: 24.75 sq ft', '2026-08-31 05:43:22'),
(570, '2026-08-31', 36, 'SALE', 39, 0.00, 13.75, 11757.50, 390.00, 5362.50, 'Sale Invoice: SAL-00035 - Total Area: 13.75 sq ft', '2026-08-31 05:43:22'),
(571, '2026-08-31', 36, 'SALE', 39, 0.00, 7.00, 11750.50, 390.00, 2730.00, 'Sale Invoice: SAL-00035 - Total Area: 7 sq ft', '2026-08-31 05:43:22'),
(572, '2026-08-31', 36, 'SALE', 39, 0.00, 15.00, 11735.50, 390.00, 5850.00, 'Sale Invoice: SAL-00035 - Total Area: 15 sq ft', '2026-08-31 05:43:22'),
(573, '2026-08-31', 36, 'SALE', 39, 0.00, 25.00, 11710.50, 390.00, 9750.00, 'Sale Invoice: SAL-00035 - Total Area: 25 sq ft', '2026-08-31 05:43:22'),
(574, '2026-08-31', 36, 'SALE', 39, 0.00, 3.00, 11707.50, 390.00, 1170.00, 'Sale Invoice: SAL-00035 - Total Area: 3 sq ft', '2026-08-31 05:43:22'),
(575, '2026-08-31', 36, 'SALE', 39, 0.00, 7.00, 11700.50, 390.00, 2730.00, 'Sale Invoice: SAL-00035 - Total Area: 7 sq ft', '2026-08-31 05:43:22'),
(576, '2026-08-31', 23, 'SALE', 39, 0.00, 42.00, 55478.00, 240.00, 10080.00, 'Sale Invoice: SAL-00035 - Total Area: 42 sq ft', '2026-08-31 05:43:22'),
(577, '2026-08-31', 23, 'SALE', 39, 0.00, 16.25, 55461.75, 240.00, 3900.00, 'Sale Invoice: SAL-00035 - Total Area: 16.25 sq ft', '2026-08-31 05:43:22'),
(578, '2026-08-31', 23, 'SALE', 39, 0.00, 9.75, 55452.00, 240.00, 2340.00, 'Sale Invoice: SAL-00035 - Total Area: 9.75 sq ft', '2026-08-31 05:43:22'),
(579, '2026-08-31', 23, 'SALE', 39, 0.00, 97.50, 55354.50, 240.00, 23400.00, 'Sale Invoice: SAL-00035 - Total Area: 97.5 sq ft', '2026-08-31 05:43:22'),
(580, '2026-08-31', 23, 'SALE', 39, 0.00, 26.00, 55328.50, 240.00, 6240.00, 'Sale Invoice: SAL-00035 - Total Area: 26 sq ft', '2026-08-31 05:43:22'),
(581, '2026-08-31', 35, 'SALE', 40, 0.00, 1.00, 17723.00, 330.00, 330.00, 'Sale Invoice: SAL-00036 - Total Area: 1 sq ft', '2026-08-31 05:56:59'),
(582, '2026-08-31', 35, 'SALE', 40, 0.00, 18.00, 17705.00, 330.00, 5940.00, 'Sale Invoice: SAL-00036 - Total Area: 18 sq ft', '2026-08-31 05:56:59'),
(583, '2026-08-31', 35, 'SALE', 40, 0.00, 27.50, 17677.50, 330.00, 9075.00, 'Sale Invoice: SAL-00036 - Total Area: 27.5 sq ft', '2026-08-31 05:56:59'),
(584, '2026-08-31', 35, 'SALE', 40, 0.00, 16.00, 17661.50, 330.00, 5280.00, 'Sale Invoice: SAL-00036 - Total Area: 16 sq ft', '2026-08-31 05:56:59'),
(585, '2026-08-31', 35, 'SALE', 40, 0.00, 3.00, 17658.50, 330.00, 990.00, 'Sale Invoice: SAL-00036 - Total Area: 3 sq ft', '2026-08-31 05:56:59'),
(586, '2026-08-31', 35, 'SALE', 40, 0.00, 4.50, 17654.00, 330.00, 1485.00, 'Sale Invoice: SAL-00036 - Total Area: 4.5 sq ft', '2026-08-31 05:56:59'),
(587, '2026-08-31', 35, 'SALE', 40, 0.00, 4.50, 17649.50, 330.00, 1485.00, 'Sale Invoice: SAL-00036 - Total Area: 4.5 sq ft', '2026-08-31 05:56:59'),
(588, '2026-08-31', 35, 'SALE', 40, 0.00, 16.00, 17633.50, 330.00, 5280.00, 'Sale Invoice: SAL-00036 - Total Area: 16 sq ft', '2026-08-31 05:56:59'),
(589, '2026-08-31', 35, 'SALE', 40, 0.00, 14.00, 17619.50, 330.00, 4620.00, 'Sale Invoice: SAL-00036 - Total Area: 14 sq ft', '2026-08-31 05:56:59'),
(590, '2026-08-31', 35, 'SALE', 40, 0.00, 30.00, 17589.50, 330.00, 9900.00, 'Sale Invoice: SAL-00036 - Total Area: 30 sq ft', '2026-08-31 05:56:59'),
(591, '2026-08-31', 35, 'SALE', 40, 0.00, 1.00, 17588.50, 330.00, 330.00, 'Sale Invoice: SAL-00036 - Total Area: 1 sq ft', '2026-08-31 05:56:59'),
(592, '2026-08-31', 35, 'SALE', 40, 0.00, 26.00, 17562.50, 330.00, 8580.00, 'Sale Invoice: SAL-00036 - Total Area: 26 sq ft', '2026-08-31 05:56:59'),
(593, '2026-08-31', 35, 'SALE', 40, 0.00, 19.50, 17543.00, 330.00, 6435.00, 'Sale Invoice: SAL-00036 - Total Area: 19.5 sq ft', '2026-08-31 05:56:59'),
(594, '2026-08-31', 35, 'SALE', 40, 0.00, 19.50, 17523.50, 330.00, 6435.00, 'Sale Invoice: SAL-00036 - Total Area: 19.5 sq ft', '2026-08-31 05:56:59'),
(595, '2026-08-31', 35, 'SALE', 40, 0.00, 2.00, 17521.50, 330.00, 660.00, 'Sale Invoice: SAL-00036 - Total Area: 2 sq ft', '2026-08-31 05:56:59'),
(596, '2026-08-31', 35, 'SALE', 40, 0.00, 16.50, 17505.00, 330.00, 5445.00, 'Sale Invoice: SAL-00036 - Total Area: 16.5 sq ft', '2026-08-31 05:56:59'),
(597, '2026-08-31', 35, 'SALE', 40, 0.00, 3.00, 17502.00, 330.00, 990.00, 'Sale Invoice: SAL-00036 - Total Area: 3 sq ft', '2026-08-31 05:56:59'),
(598, '2026-08-31', 35, 'SALE', 40, 0.00, 20.00, 17482.00, 330.00, 6600.00, 'Sale Invoice: SAL-00036 - Total Area: 20 sq ft', '2026-08-31 05:56:59'),
(599, '2026-08-31', 35, 'SALE', 40, 0.00, 4.50, 17477.50, 330.00, 1485.00, 'Sale Invoice: SAL-00036 - Total Area: 4.5 sq ft', '2026-08-31 05:56:59'),
(600, '2026-08-31', 35, 'SALE', 40, 0.00, 32.50, 17445.00, 330.00, 10725.00, 'Sale Invoice: SAL-00036 - Total Area: 32.5 sq ft', '2026-08-31 05:56:59'),
(601, '2026-08-31', 35, 'SALE', 40, 0.00, 25.00, 17420.00, 330.00, 8250.00, 'Sale Invoice: SAL-00036 - Total Area: 25 sq ft', '2026-08-31 05:56:59'),
(602, '2026-08-31', 35, 'SALE', 40, 0.00, 14.00, 17406.00, 330.00, 4620.00, 'Sale Invoice: SAL-00036 - Total Area: 14 sq ft', '2026-08-31 05:56:59'),
(603, '2026-08-31', 35, 'SALE', 40, 0.00, 19.50, 17386.50, 330.00, 6435.00, 'Sale Invoice: SAL-00036 - Total Area: 19.5 sq ft', '2026-08-31 05:56:59'),
(604, '2026-08-31', 35, 'SALE', 40, 0.00, 32.50, 17354.00, 330.00, 10725.00, 'Sale Invoice: SAL-00036 - Total Area: 32.5 sq ft', '2026-08-31 05:56:59'),
(605, '2026-08-31', 35, 'SALE', 40, 0.00, 32.50, 17321.50, 330.00, 10725.00, 'Sale Invoice: SAL-00036 - Total Area: 32.5 sq ft', '2026-08-31 05:56:59'),
(606, '2026-08-31', 35, 'SALE', 40, 0.00, 26.00, 17295.50, 330.00, 8580.00, 'Sale Invoice: SAL-00036 - Total Area: 26 sq ft', '2026-08-31 05:56:59'),
(607, '2026-08-31', 35, 'SALE', 40, 0.00, 19.50, 17276.00, 330.00, 6435.00, 'Sale Invoice: SAL-00036 - Total Area: 19.5 sq ft', '2026-08-31 05:56:59'),
(608, '2026-08-31', 45, 'SALE', 41, 0.00, 17.50, 19758.50, 370.00, 6475.00, 'Sale Invoice: SAL-00037 - Total Area: 17.5 sq ft', '2026-08-31 07:59:19'),
(609, '2026-08-31', 45, 'SALE', 41, 0.00, 27.50, 19731.00, 370.00, 10175.00, 'Sale Invoice: SAL-00037 - Total Area: 27.5 sq ft', '2026-08-31 07:59:19'),
(610, '2026-08-31', 45, 'SALE', 41, 0.00, 12.00, 19719.00, 370.00, 4440.00, 'Sale Invoice: SAL-00037 - Total Area: 12 sq ft', '2026-08-31 07:59:19'),
(611, '2026-08-31', 45, 'SALE', 41, 0.00, 30.00, 19689.00, 370.00, 11100.00, 'Sale Invoice: SAL-00037 - Total Area: 30 sq ft', '2026-08-31 07:59:19'),
(612, '2026-08-31', 45, 'SALE', 41, 0.00, 66.50, 19622.50, 370.00, 24605.00, 'Sale Invoice: SAL-00037 - Total Area: 66.5 sq ft', '2026-08-31 07:59:19'),
(613, '2026-08-31', 45, 'SALE', 41, 0.00, 66.50, 19556.00, 370.00, 24605.00, 'Sale Invoice: SAL-00037 - Total Area: 66.5 sq ft', '2026-08-31 07:59:19'),
(614, '2026-08-31', 45, 'SALE', 41, 0.00, 66.50, 19489.50, 370.00, 24605.00, 'Sale Invoice: SAL-00037 - Total Area: 66.5 sq ft', '2026-08-31 07:59:19'),
(615, '2026-08-31', 45, 'SALE', 41, 0.00, 76.00, 19413.50, 370.00, 28120.00, 'Sale Invoice: SAL-00037 - Total Area: 76 sq ft', '2026-08-31 07:59:19'),
(616, '2026-08-31', 45, 'SALE', 41, 0.00, 55.00, 19358.50, 370.00, 20350.00, 'Sale Invoice: SAL-00037 - Total Area: 55 sq ft', '2026-08-31 07:59:19'),
(617, '2026-08-31', 27, 'SALE', 42, 0.00, 2.00, 286.00, 950.00, 1900.00, 'Sale Invoice: SAL-00038 - Total Area: 2 sq ft', '2026-08-31 08:57:35'),
(618, '2026-08-31', 27, 'ADJUSTMENT', 42, 2.00, 0.00, 288.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00038', '2026-08-31 08:58:21'),
(619, '2026-08-31', 27, 'SALE', 42, 0.00, 3.00, 285.00, 950.00, 2850.00, 'Sale Invoice: SAL-00038 - Total Area: 3 sq ft', '2026-08-31 08:58:21'),
(620, '2026-08-31', 28, 'SALE', 43, 0.00, 3.00, 1101.00, 950.00, 2850.00, 'Sale Invoice: SAL-00039 - Total Area: 3 sq ft', '2026-08-31 09:27:22'),
(621, '2026-08-31', 23, 'SALE', 44, 0.00, 2.00, 55326.50, 240.00, 480.00, 'Sale Invoice: SAL-00040 - Total Area: 2 sq ft', '2026-08-31 09:47:47'),
(622, '2026-08-31', 23, 'SALE', 44, 0.00, 9.00, 55317.50, 240.00, 2160.00, 'Sale Invoice: SAL-00040 - Total Area: 9 sq ft', '2026-08-31 09:47:47'),
(623, '2026-08-31', 23, 'SALE', 44, 0.00, 4.50, 55313.00, 240.00, 1080.00, 'Sale Invoice: SAL-00040 - Total Area: 4.5 sq ft', '2026-08-31 09:47:47'),
(624, '2026-08-31', 23, 'SALE', 44, 0.00, 11.38, 55301.63, 240.00, 2730.00, 'Sale Invoice: SAL-00040 - Total Area: 11.375 sq ft', '2026-08-31 09:47:47'),
(625, '2026-08-31', 23, 'SALE', 44, 0.00, 22.00, 55279.63, 240.00, 5280.00, 'Sale Invoice: SAL-00040 - Total Area: 22 sq ft', '2026-08-31 09:47:47'),
(626, '2026-08-31', 44, 'SALE', 44, 0.00, 48.00, 2220.00, 300.00, 14400.00, 'Sale Invoice: SAL-00040 - Total Area: 48 sq ft', '2026-08-31 09:47:47'),
(627, '2026-08-31', 44, 'SALE', 44, 0.00, 21.00, 2199.00, 300.00, 6300.00, 'Sale Invoice: SAL-00040 - Total Area: 21 sq ft', '2026-08-31 09:47:47'),
(628, '2026-08-31', 44, 'SALE', 44, 0.00, 12.00, 2187.00, 300.00, 3600.00, 'Sale Invoice: SAL-00040 - Total Area: 12 sq ft', '2026-08-31 09:47:47'),
(629, '2026-08-31', 40, 'SALE', 45, 0.00, 168.00, 2436.00, 390.00, 65520.00, 'Sale Invoice: SAL-00041 - Total Area: 168 sq ft', '2026-08-31 11:07:51'),
(630, '2026-08-31', 40, 'SALE', 45, 0.00, 70.00, 2366.00, 390.00, 27300.00, 'Sale Invoice: SAL-00041 - Total Area: 70 sq ft', '2026-08-31 11:07:51'),
(631, '2026-08-31', 40, 'SALE', 45, 0.00, 24.50, 2341.50, 390.00, 9555.00, 'Sale Invoice: SAL-00041 - Total Area: 24.5 sq ft', '2026-08-31 11:07:51'),
(632, '2026-08-31', 40, 'SALE', 45, 0.00, 12.25, 2329.25, 390.00, 4777.50, 'Sale Invoice: SAL-00041 - Total Area: 12.25 sq ft', '2026-08-31 11:07:51'),
(633, '2026-08-31', 40, 'SALE', 45, 0.00, 11.25, 2318.00, 390.00, 4387.50, 'Sale Invoice: SAL-00041 - Total Area: 11.25 sq ft', '2026-08-31 11:07:51'),
(634, '2026-08-31', 40, 'SALE', 45, 0.00, 8.00, 2310.00, 390.00, 3120.00, 'Sale Invoice: SAL-00041 - Total Area: 8 sq ft', '2026-08-31 11:07:51'),
(635, '2026-08-31', 40, 'SALE', 45, 0.00, 7.00, 2303.00, 390.00, 2730.00, 'Sale Invoice: SAL-00041 - Total Area: 7 sq ft', '2026-08-31 11:07:51'),
(636, '2026-08-31', 38, 'SALE', 46, 0.00, 14.00, 8854.00, 380.00, 5320.00, 'Sale Invoice: SAL-00042 - Total Area: 14 sq ft', '2026-08-31 11:09:40'),
(637, '2026-08-31', 38, 'SALE', 46, 0.00, 3.00, 8851.00, 380.00, 1140.00, 'Sale Invoice: SAL-00042 - Total Area: 3 sq ft', '2026-08-31 11:09:40'),
(638, '2026-08-31', 23, 'ADJUSTMENT', 44, 2.00, 0.00, 55281.63, 0.00, 0.00, 'Sale Edit Reversal: SAL-00040', '2026-08-31 11:30:42'),
(639, '2026-08-31', 23, 'ADJUSTMENT', 44, 9.00, 0.00, 55290.63, 0.00, 0.00, 'Sale Edit Reversal: SAL-00040', '2026-08-31 11:30:42'),
(640, '2026-08-31', 23, 'ADJUSTMENT', 44, 4.50, 0.00, 55295.13, 0.00, 0.00, 'Sale Edit Reversal: SAL-00040', '2026-08-31 11:30:42'),
(641, '2026-08-31', 23, 'ADJUSTMENT', 44, 11.38, 0.00, 55306.51, 0.00, 0.00, 'Sale Edit Reversal: SAL-00040', '2026-08-31 11:30:42'),
(642, '2026-08-31', 23, 'ADJUSTMENT', 44, 22.00, 0.00, 55328.51, 0.00, 0.00, 'Sale Edit Reversal: SAL-00040', '2026-08-31 11:30:42'),
(643, '2026-08-31', 44, 'ADJUSTMENT', 44, 48.00, 0.00, 2235.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00040', '2026-08-31 11:30:42'),
(644, '2026-08-31', 44, 'ADJUSTMENT', 44, 21.00, 0.00, 2256.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00040', '2026-08-31 11:30:42'),
(645, '2026-08-31', 44, 'ADJUSTMENT', 44, 12.00, 0.00, 2268.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00040', '2026-08-31 11:30:42'),
(646, '2026-08-31', 23, 'SALE', 44, 0.00, 2.00, 55326.51, 240.00, 480.00, 'Sale Invoice: SAL-00040 - Total Area: 2 sq ft', '2026-08-31 11:30:42'),
(647, '2026-08-31', 23, 'SALE', 44, 0.00, 9.00, 55317.51, 240.00, 2160.00, 'Sale Invoice: SAL-00040 - Total Area: 9 sq ft', '2026-08-31 11:30:42'),
(648, '2026-08-31', 23, 'SALE', 44, 0.00, 4.50, 55313.01, 240.00, 1080.00, 'Sale Invoice: SAL-00040 - Total Area: 4.5 sq ft', '2026-08-31 11:30:42'),
(649, '2026-08-31', 23, 'SALE', 44, 0.00, 11.38, 55301.63, 240.00, 2731.20, 'Sale Invoice: SAL-00040 - Total Area: 11.38 sq ft', '2026-08-31 11:30:42'),
(650, '2026-08-31', 23, 'SALE', 44, 0.00, 22.00, 55279.63, 240.00, 5280.00, 'Sale Invoice: SAL-00040 - Total Area: 22 sq ft', '2026-08-31 11:30:42'),
(651, '2026-08-31', 44, 'SALE', 44, 0.00, 48.00, 2220.00, 300.00, 14400.00, 'Sale Invoice: SAL-00040 - Total Area: 48 sq ft', '2026-08-31 11:30:42'),
(652, '2026-08-31', 44, 'SALE', 44, 0.00, 21.00, 2199.00, 300.00, 6300.00, 'Sale Invoice: SAL-00040 - Total Area: 21 sq ft', '2026-08-31 11:30:42'),
(653, '2026-08-31', 44, 'SALE', 44, 0.00, 12.00, 2187.00, 300.00, 3600.00, 'Sale Invoice: SAL-00040 - Total Area: 12 sq ft', '2026-08-31 11:30:42'),
(654, '2026-08-31', 25, 'SALE', 47, 0.00, 35.00, 44938.00, 680.00, 23800.00, 'Sale Invoice: SAL-00043 - Total Area: 35 sq ft', '2026-08-31 11:39:08'),
(655, '2026-08-31', 25, 'SALE', 47, 0.00, 31.50, 44906.50, 680.00, 21420.00, 'Sale Invoice: SAL-00043 - Total Area: 31.5 sq ft', '2026-08-31 11:39:08'),
(656, '2026-08-31', 25, 'SALE', 47, 0.00, 10.00, 44896.50, 680.00, 6800.00, 'Sale Invoice: SAL-00043 - Total Area: 10 sq ft', '2026-08-31 11:39:08'),
(657, '2026-08-31', 25, 'SALE', 48, 0.00, 9.50, 44887.00, 690.00, 6555.00, 'Sale Invoice: SAL-00044 - Total Area: 9.5 sq ft', '2026-08-31 11:46:37'),
(658, '2026-08-31', 24, 'SALE', 49, 0.00, 5.25, 20826.75, 880.00, 4620.00, 'Sale Invoice: SAL-00045 - Total Area: 5.25 sq ft', '2026-08-31 12:12:16'),
(659, '2026-08-31', 24, 'SALE', 49, 0.00, 1.00, 20825.75, 880.00, 880.00, 'Sale Invoice: SAL-00045 - Total Area: 1 sq ft', '2026-08-31 12:12:16'),
(660, '2026-08-31', 25, 'SALE', 50, 0.00, 22.00, 44865.00, 690.00, 15180.00, 'Sale Invoice: SAL-00046 - Total Area: 22 sq ft', '2026-08-31 12:18:44'),
(661, '2026-08-31', 25, 'SALE', 50, 0.00, 44.00, 44821.00, 690.00, 30360.00, 'Sale Invoice: SAL-00046 - Total Area: 44 sq ft', '2026-08-31 12:18:44'),
(662, '2026-08-31', 45, 'SALE', 51, 0.00, 6.25, 19352.25, 440.00, 2750.00, 'Sale Invoice: SAL-00047 - Total Area: 6.25 sq ft', '2026-08-31 12:23:55'),
(696, '2026-08-31', 24, 'SALE', 52, 0.00, 8.44, 20817.31, 480.00, 4050.00, 'Sale Invoice: SAL-00048 - Total Area: 8.4375 sq ft', '2026-08-31 13:02:15'),
(697, '2026-08-31', 25, 'SALE', 52, 0.00, 7.50, 44813.50, 700.00, 5250.00, 'Sale Invoice: SAL-00048 - Total Area: 7.5 sq ft', '2026-08-31 13:02:15'),
(698, '2026-08-31', 45, 'ADJUSTMENT', 41, 17.50, 0.00, 19369.75, 0.00, 0.00, 'Sale Edit Reversal: SAL-00037', '2026-08-31 13:38:02'),
(699, '2026-08-31', 45, 'ADJUSTMENT', 41, 27.50, 0.00, 19397.25, 0.00, 0.00, 'Sale Edit Reversal: SAL-00037', '2026-08-31 13:38:02'),
(700, '2026-08-31', 45, 'ADJUSTMENT', 41, 12.00, 0.00, 19409.25, 0.00, 0.00, 'Sale Edit Reversal: SAL-00037', '2026-08-31 13:38:02'),
(701, '2026-08-31', 45, 'ADJUSTMENT', 41, 30.00, 0.00, 19439.25, 0.00, 0.00, 'Sale Edit Reversal: SAL-00037', '2026-08-31 13:38:02'),
(702, '2026-08-31', 45, 'ADJUSTMENT', 41, 66.50, 0.00, 19505.75, 0.00, 0.00, 'Sale Edit Reversal: SAL-00037', '2026-08-31 13:38:02'),
(703, '2026-08-31', 45, 'ADJUSTMENT', 41, 66.50, 0.00, 19572.25, 0.00, 0.00, 'Sale Edit Reversal: SAL-00037', '2026-08-31 13:38:02'),
(704, '2026-08-31', 45, 'ADJUSTMENT', 41, 66.50, 0.00, 19638.75, 0.00, 0.00, 'Sale Edit Reversal: SAL-00037', '2026-08-31 13:38:02'),
(705, '2026-08-31', 45, 'ADJUSTMENT', 41, 76.00, 0.00, 19714.75, 0.00, 0.00, 'Sale Edit Reversal: SAL-00037', '2026-08-31 13:38:02'),
(706, '2026-08-31', 45, 'ADJUSTMENT', 41, 55.00, 0.00, 19769.75, 0.00, 0.00, 'Sale Edit Reversal: SAL-00037', '2026-08-31 13:38:02'),
(707, '2026-08-31', 45, 'SALE', 41, 0.00, 17.50, 19752.25, 370.00, 6475.00, 'Sale Invoice: SAL-00037 - Total Area: 17.5 sq ft', '2026-08-31 13:38:02'),
(708, '2026-08-31', 45, 'SALE', 41, 0.00, 27.50, 19724.75, 370.00, 10175.00, 'Sale Invoice: SAL-00037 - Total Area: 27.5 sq ft', '2026-08-31 13:38:02'),
(709, '2026-08-31', 45, 'SALE', 41, 0.00, 12.00, 19712.75, 370.00, 4440.00, 'Sale Invoice: SAL-00037 - Total Area: 12 sq ft', '2026-08-31 13:38:02'),
(710, '2026-08-31', 45, 'SALE', 41, 0.00, 30.00, 19682.75, 370.00, 11100.00, 'Sale Invoice: SAL-00037 - Total Area: 30 sq ft', '2026-08-31 13:38:02'),
(711, '2026-08-31', 45, 'SALE', 41, 0.00, 66.50, 19616.25, 370.00, 24605.00, 'Sale Invoice: SAL-00037 - Total Area: 66.5 sq ft', '2026-08-31 13:38:02'),
(712, '2026-08-31', 45, 'SALE', 41, 0.00, 66.50, 19549.75, 370.00, 24605.00, 'Sale Invoice: SAL-00037 - Total Area: 66.5 sq ft', '2026-08-31 13:38:02'),
(713, '2026-08-31', 45, 'SALE', 41, 0.00, 66.50, 19483.25, 370.00, 24605.00, 'Sale Invoice: SAL-00037 - Total Area: 66.5 sq ft', '2026-08-31 13:38:02'),
(714, '2026-08-31', 45, 'SALE', 41, 0.00, 76.00, 19407.25, 370.00, 28120.00, 'Sale Invoice: SAL-00037 - Total Area: 76 sq ft', '2026-08-31 13:38:02'),
(715, '2026-08-31', 45, 'SALE', 41, 0.00, 55.00, 19352.25, 370.00, 20350.00, 'Sale Invoice: SAL-00037 - Total Area: 55 sq ft', '2026-08-31 13:38:02'),
(716, '2026-08-31', 23, 'SALE', 53, 0.00, 46.00, 55233.63, 235.00, 10810.00, 'Sale Invoice: SAL-00049 - Total Area: 46 sq ft', '2026-08-31 13:54:35'),
(717, '2026-08-31', 23, 'SALE', 53, 0.00, 96.00, 55137.63, 235.00, 22560.00, 'Sale Invoice: SAL-00049 - Total Area: 96 sq ft', '2026-08-31 13:54:35'),
(718, '2026-08-31', 23, 'SALE', 53, 0.00, 189.00, 54948.63, 235.00, 44415.00, 'Sale Invoice: SAL-00049 - Total Area: 189 sq ft', '2026-08-31 13:54:35'),
(719, '2026-08-31', 37, 'SALE', 53, 0.00, 64.00, 15488.00, 390.00, 24960.00, 'Sale Invoice: SAL-00049 - Total Area: 64 sq ft', '2026-08-31 13:54:35'),
(720, '2026-08-31', 37, 'SALE', 53, 0.00, 96.00, 15392.00, 390.00, 37440.00, 'Sale Invoice: SAL-00049 - Total Area: 96 sq ft', '2026-08-31 13:54:35'),
(721, '2026-08-31', 37, 'SALE', 53, 0.00, 2.25, 15389.75, 390.00, 877.50, 'Sale Invoice: SAL-00049 - Total Area: 2.25 sq ft', '2026-08-31 13:54:35'),
(722, '2026-08-31', 37, 'SALE', 53, 0.00, 3.00, 15386.75, 390.00, 1170.00, 'Sale Invoice: SAL-00049 - Total Area: 3 sq ft', '2026-08-31 13:54:35'),
(723, '2026-08-31', 24, 'SALE', 54, 0.00, 12.00, 20805.31, 440.00, 5280.00, 'Sale Invoice: SAL-00050 - Total Area: 12 sq ft', '2026-08-31 14:06:04'),
(724, '2026-08-31', 24, 'SALE', 54, 0.00, 9.00, 20796.31, 440.00, 3960.00, 'Sale Invoice: SAL-00050 - Total Area: 9 sq ft', '2026-08-31 14:06:04'),
(725, '2026-08-31', 24, 'SALE', 54, 0.00, 11.25, 20785.06, 440.00, 4950.00, 'Sale Invoice: SAL-00050 - Total Area: 11.25 sq ft', '2026-08-31 14:06:04'),
(726, '2026-08-31', 24, 'SALE', 54, 0.00, 3.00, 20782.06, 440.00, 1320.00, 'Sale Invoice: SAL-00050 - Total Area: 3 sq ft', '2026-08-31 14:06:04'),
(727, '2026-08-31', 24, 'SALE', 54, 0.00, 6.25, 20775.81, 440.00, 2750.00, 'Sale Invoice: SAL-00050 - Total Area: 6.25 sq ft', '2026-08-31 14:06:04'),
(728, '2026-08-31', 24, 'SALE', 54, 0.00, 6.00, 20769.81, 440.00, 2640.00, 'Sale Invoice: SAL-00050 - Total Area: 6 sq ft', '2026-08-31 14:06:04'),
(729, '2026-08-31', 24, 'SALE', 54, 0.00, 7.50, 20762.31, 420.00, 3150.00, 'Sale Invoice: SAL-00050 - Total Area: 7.5 sq ft', '2026-08-31 14:06:04'),
(730, '2026-08-31', 24, 'SALE', 54, 0.00, 10.00, 20752.31, 420.00, 4200.00, 'Sale Invoice: SAL-00050 - Total Area: 10 sq ft', '2026-08-31 14:06:04'),
(731, '2026-08-31', 39, 'SALE', 55, 0.00, 7.50, 5836.50, 450.00, 3375.00, 'Sale Invoice: SAL-00051 - Total Area: 7.5 sq ft', '2026-08-31 14:12:44'),
(732, '2026-08-31', 39, 'SALE', 55, 0.00, 5.25, 5831.25, 450.00, 2362.50, 'Sale Invoice: SAL-00051 - Total Area: 5.25 sq ft', '2026-08-31 14:12:44'),
(733, '2026-08-31', 23, 'SALE', 56, 0.00, 35.00, 54913.63, 240.00, 8400.00, 'Sale Invoice: SAL-00052 - Total Area: 35 sq ft', '2026-08-31 14:29:13'),
(734, '2026-08-31', 25, 'SALE', 57, 0.00, 20.00, 44793.50, 690.00, 13800.00, 'Sale Invoice: SAL-00053 - Total Area: 20 sq ft', '2026-08-31 14:33:45'),
(735, '2026-08-31', 25, 'SALE', 57, 0.00, 48.00, 44745.50, 690.00, 33120.00, 'Sale Invoice: SAL-00053 - Total Area: 48 sq ft', '2026-08-31 14:33:45'),
(736, '2026-08-31', 25, 'SALE', 57, 0.00, 12.50, 44733.00, 690.00, 8625.00, 'Sale Invoice: SAL-00053 - Total Area: 12.5 sq ft', '2026-08-31 14:33:45'),
(737, '2026-08-31', 25, 'SALE', 57, 0.00, 10.00, 44723.00, 690.00, 6900.00, 'Sale Invoice: SAL-00053 - Total Area: 10 sq ft', '2026-08-31 14:33:45'),
(738, '2026-08-31', 25, 'SALE', 58, 0.00, 20.00, 44703.00, 690.00, 13800.00, 'Sale Invoice: SAL-00054 - Total Area: 20 sq ft', '2026-08-31 14:33:54'),
(739, '2026-08-31', 25, 'SALE', 58, 0.00, 48.00, 44655.00, 690.00, 33120.00, 'Sale Invoice: SAL-00054 - Total Area: 48 sq ft', '2026-08-31 14:33:54'),
(740, '2026-08-31', 25, 'SALE', 58, 0.00, 12.50, 44642.50, 690.00, 8625.00, 'Sale Invoice: SAL-00054 - Total Area: 12.5 sq ft', '2026-08-31 14:33:54'),
(741, '2026-08-31', 25, 'SALE', 58, 0.00, 10.00, 44632.50, 690.00, 6900.00, 'Sale Invoice: SAL-00054 - Total Area: 10 sq ft', '2026-08-31 14:33:54'),
(742, '2026-08-31', 25, 'ADJUSTMENT', 57, 2.00, 0.00, 44634.50, 690.00, 0.00, 'Sale Deleted: SAL-00053', '2026-08-31 14:34:09'),
(743, '2026-08-31', 25, 'ADJUSTMENT', 57, 6.00, 0.00, 44640.50, 690.00, 0.00, 'Sale Deleted: SAL-00053', '2026-08-31 14:34:09'),
(744, '2026-08-31', 25, 'ADJUSTMENT', 57, 1.00, 0.00, 44641.50, 690.00, 0.00, 'Sale Deleted: SAL-00053', '2026-08-31 14:34:09'),
(745, '2026-08-31', 25, 'ADJUSTMENT', 57, 1.00, 0.00, 44642.50, 690.00, 0.00, 'Sale Deleted: SAL-00053', '2026-08-31 14:34:09'),
(746, '2026-08-31', 29, 'SALE', 59, 0.00, 27.50, 2804.50, 1500.00, 41250.00, 'Sale Invoice: SAL-00055 - Total Area: 27.5 sq ft', '2026-08-31 14:39:32'),
(747, '2026-08-31', 29, 'SALE', 59, 0.00, 1.25, 2803.25, 1500.00, 1875.00, 'Sale Invoice: SAL-00055 - Total Area: 1.25 sq ft', '2026-08-31 14:39:32'),
(748, '2026-08-31', 29, 'SALE', 59, 0.00, 4.38, 2798.88, 1500.00, 6562.50, 'Sale Invoice: SAL-00055 - Total Area: 4.375 sq ft', '2026-08-31 14:39:32'),
(749, '2026-08-31', 29, 'SALE', 59, 0.00, 5.63, 2793.26, 1500.00, 8437.50, 'Sale Invoice: SAL-00055 - Total Area: 5.625 sq ft', '2026-08-31 14:39:32'),
(750, '2026-08-31', 29, 'SALE', 59, 0.00, 1.25, 2792.01, 1500.00, 1875.00, 'Sale Invoice: SAL-00055 - Total Area: 1.25 sq ft', '2026-08-31 14:39:32'),
(751, '2026-08-31', 48, 'OPENING', 48, 288.00, 0.00, 288.00, 1458.66, 420094.08, 'Opening Stock Entry - Opening Stock: 3 pieces of 8 x 12 ft, total 288 sq ft', '2026-08-31 14:44:30'),
(752, '2026-08-31', 49, 'OPENING', 49, 1680.00, 0.00, 1680.00, 399.91, 671848.80, 'Opening Stock Entry - Opening Stock: 20 pieces of 7 x 12 ft, total 1680 sq ft', '2026-08-31 14:46:36'),
(753, '2026-08-31', 37, 'SALE', 60, 0.00, 33.00, 15353.75, 390.00, 12870.00, 'Sale Invoice: SAL-00056 - Total Area: 33 sq ft', '2026-08-31 15:44:42'),
(754, '2026-08-31', 37, 'SALE', 60, 0.00, 12.00, 15341.75, 390.00, 4680.00, 'Sale Invoice: SAL-00056 - Total Area: 12 sq ft', '2026-08-31 15:44:42'),
(755, '2026-08-31', 37, 'SALE', 60, 0.00, 11.25, 15330.50, 390.00, 4387.50, 'Sale Invoice: SAL-00056 - Total Area: 11.25 sq ft', '2026-08-31 15:44:42'),
(756, '2026-08-31', 37, 'SALE', 60, 0.00, 16.00, 15314.50, 390.00, 6240.00, 'Sale Invoice: SAL-00056 - Total Area: 16 sq ft', '2026-08-31 15:44:42'),
(757, '2026-08-31', 22, 'SALE', 61, 0.00, 12.00, 20100.00, 230.00, 2760.00, 'Sale Invoice: SAL-00057 - Total Area: 12 sq ft', '2026-08-31 16:02:17'),
(758, '2026-08-31', 42, 'SALE', 62, 0.00, 24.00, 7176.00, 200.00, 4800.00, 'Sale Invoice: SAL-00058 - Total Area: 24 sq ft', '2026-09-01 06:13:43'),
(759, '2026-08-31', 42, 'SALE', 62, 0.00, 18.00, 7158.00, 200.00, 3600.00, 'Sale Invoice: SAL-00058 - Total Area: 18 sq ft', '2026-09-01 06:13:43'),
(760, '2026-08-31', 43, 'SALE', 62, 0.00, 6.25, 4073.75, 280.00, 1750.00, 'Sale Invoice: SAL-00058 - Total Area: 6.25 sq ft', '2026-09-01 06:13:43'),
(761, '2026-08-31', 24, 'SALE', 63, 0.00, 17.50, 20734.81, 440.00, 7700.00, 'Sale Invoice: SAL-00059 - Total Area: 17.5 sq ft', '2026-09-01 06:17:18'),
(762, '2026-08-31', 25, 'SALE', 63, 0.00, 3.00, 44639.50, 690.00, 2070.00, 'Sale Invoice: SAL-00059 - Total Area: 3 sq ft', '2026-09-01 06:17:18'),
(763, '2026-08-31', 39, 'SALE', 63, 0.00, 14.00, 5817.25, 450.00, 6300.00, 'Sale Invoice: SAL-00059 - Total Area: 14 sq ft', '2026-09-01 06:17:18'),
(764, '2026-08-31', 39, 'SALE', 63, 0.00, 4.00, 5813.25, 450.00, 1800.00, 'Sale Invoice: SAL-00059 - Total Area: 4 sq ft', '2026-09-01 06:17:18'),
(765, '2026-08-31', 39, 'SALE', 63, 0.00, 4.00, 5809.25, 450.00, 1800.00, 'Sale Invoice: SAL-00059 - Total Area: 4 sq ft', '2026-09-01 06:17:18'),
(766, '2026-09-01', 45, 'SALE', 64, 0.00, 17.50, 19334.75, 650.00, 11375.00, 'Sale Invoice: SAL-00060 - Total Area: 17.5 sq ft', '2026-09-01 06:19:00'),
(767, '2026-09-01', 35, 'SALE', 65, 0.00, 192.00, 17084.00, 330.00, 63360.00, 'Sale Invoice: SAL-00061 - Total Area: 192 sq ft', '2026-09-01 06:26:08'),
(768, '2026-09-01', 35, 'SALE', 65, 0.00, 13.75, 17070.25, 330.00, 4537.50, 'Sale Invoice: SAL-00061 - Total Area: 13.75 sq ft', '2026-09-01 06:26:08'),
(769, '2026-09-01', 35, 'SALE', 65, 0.00, 27.00, 17043.25, 330.00, 8910.00, 'Sale Invoice: SAL-00061 - Total Area: 27 sq ft', '2026-09-01 06:26:08'),
(770, '2026-09-01', 25, 'SALE', 66, 0.00, 33.25, 44606.25, 690.00, 22942.50, 'Sale Invoice: SAL-00062 - Total Area: 33.25 sq ft', '2026-09-01 06:30:07'),
(771, '2026-09-01', 24, 'SALE', 66, 0.00, 4.50, 20730.31, 420.00, 1890.00, 'Sale Invoice: SAL-00062 - Total Area: 4.5 sq ft', '2026-09-01 06:30:07'),
(772, '2026-09-01', 24, 'SALE', 66, 0.00, 6.50, 20723.81, 420.00, 2730.00, 'Sale Invoice: SAL-00062 - Total Area: 6.5 sq ft', '2026-09-01 06:30:07'),
(773, '2026-09-01', 25, 'SALE', 67, 0.00, 42.00, 44564.25, 690.00, 28980.00, 'Sale Invoice: SAL-00063 - Total Area: 42 sq ft', '2026-09-01 06:35:44'),
(774, '2026-09-01', 25, 'SALE', 67, 0.00, 7.00, 44557.25, 690.00, 4830.00, 'Sale Invoice: SAL-00063 - Total Area: 7 sq ft', '2026-09-01 06:35:44'),
(775, '2026-09-01', 25, 'SALE', 67, 0.00, 13.75, 44543.50, 690.00, 9487.50, 'Sale Invoice: SAL-00063 - Total Area: 13.75 sq ft', '2026-09-01 06:35:44'),
(776, '2026-09-01', 45, 'ADJUSTMENT', 64, 17.50, 0.00, 19352.25, 0.00, 0.00, 'Sale Edit Reversal: SAL-00060', '2026-09-01 06:36:46'),
(777, '2026-08-31', 45, 'SALE', 64, 0.00, 17.50, 19334.75, 650.00, 11375.00, 'Sale Invoice: SAL-00060 - Total Area: 17.5 sq ft', '2026-09-01 06:36:46'),
(778, '2026-09-01', 25, 'ADJUSTMENT', 67, 42.00, 0.00, 44585.50, 0.00, 0.00, 'Sale Edit Reversal: SAL-00063', '2026-09-01 06:37:09'),
(779, '2026-09-01', 25, 'ADJUSTMENT', 67, 7.00, 0.00, 44592.50, 0.00, 0.00, 'Sale Edit Reversal: SAL-00063', '2026-09-01 06:37:09'),
(780, '2026-09-01', 25, 'ADJUSTMENT', 67, 13.75, 0.00, 44606.25, 0.00, 0.00, 'Sale Edit Reversal: SAL-00063', '2026-09-01 06:37:09'),
(781, '2026-08-31', 25, 'SALE', 67, 0.00, 42.00, 44564.25, 690.00, 28980.00, 'Sale Invoice: SAL-00063 - Total Area: 42 sq ft', '2026-09-01 06:37:09'),
(782, '2026-08-31', 25, 'SALE', 67, 0.00, 7.00, 44557.25, 690.00, 4830.00, 'Sale Invoice: SAL-00063 - Total Area: 7 sq ft', '2026-09-01 06:37:09'),
(783, '2026-08-31', 25, 'SALE', 67, 0.00, 13.75, 44543.50, 690.00, 9487.50, 'Sale Invoice: SAL-00063 - Total Area: 13.75 sq ft', '2026-09-01 06:37:09'),
(784, '2026-08-31', 23, 'SALE', 68, 0.00, 12.25, 54901.38, 300.00, 3675.00, 'Sale Invoice: SAL-00064 - Total Area: 12.25 sq ft', '2026-09-01 06:38:46'),
(785, '2026-08-31', 23, 'SALE', 69, 0.00, 16.00, 54885.38, 240.00, 3840.00, 'Sale Invoice: SAL-00065 - Total Area: 16 sq ft', '2026-09-01 06:41:16'),
(786, '2026-08-31', 23, 'SALE', 69, 0.00, 5.25, 54880.13, 240.00, 1260.00, 'Sale Invoice: SAL-00065 - Total Area: 5.25 sq ft', '2026-09-01 06:41:16'),
(787, '2026-09-01', 25, 'ADJUSTMENT', 58, 20.00, 0.00, 44563.50, 0.00, 0.00, 'Sale Edit Reversal: SAL-00054', '2026-09-01 06:47:30'),
(788, '2026-09-01', 25, 'ADJUSTMENT', 58, 48.00, 0.00, 44611.50, 0.00, 0.00, 'Sale Edit Reversal: SAL-00054', '2026-09-01 06:47:30'),
(789, '2026-09-01', 25, 'ADJUSTMENT', 58, 12.50, 0.00, 44624.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00054', '2026-09-01 06:47:30'),
(790, '2026-09-01', 25, 'ADJUSTMENT', 58, 10.00, 0.00, 44634.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00054', '2026-09-01 06:47:30'),
(791, '2026-08-31', 25, 'SALE', 58, 0.00, 20.00, 44614.00, 690.00, 13800.00, 'Sale Invoice: SAL-00054 - Total Area: 20 sq ft', '2026-09-01 06:47:30'),
(792, '2026-08-31', 25, 'SALE', 58, 0.00, 48.00, 44566.00, 690.00, 33120.00, 'Sale Invoice: SAL-00054 - Total Area: 48 sq ft', '2026-09-01 06:47:30'),
(793, '2026-08-31', 25, 'SALE', 58, 0.00, 13.75, 44552.25, 690.00, 9487.50, 'Sale Invoice: SAL-00054 - Total Area: 13.75 sq ft', '2026-09-01 06:47:30'),
(794, '2026-08-31', 25, 'SALE', 58, 0.00, 11.25, 44541.00, 690.00, 7762.50, 'Sale Invoice: SAL-00054 - Total Area: 11.25 sq ft', '2026-09-01 06:47:30'),
(795, '2026-09-01', 25, 'SALE', 70, 0.00, 216.75, 44324.25, 690.00, 149557.50, 'Sale Invoice: SAL-00066 - Total Area: 216.75 sq ft', '2026-09-01 08:15:32'),
(796, '2026-09-01', 25, 'SALE', 70, 0.00, 76.50, 44247.75, 690.00, 52785.00, 'Sale Invoice: SAL-00066 - Total Area: 76.5 sq ft', '2026-09-01 08:15:32'),
(797, '2026-09-01', 25, 'SALE', 70, 0.00, 63.75, 44184.00, 690.00, 43987.50, 'Sale Invoice: SAL-00066 - Total Area: 63.75 sq ft', '2026-09-01 08:15:32'),
(798, '2026-09-01', 45, 'SALE', 71, 0.00, 94.50, 19240.25, 430.00, 40635.00, 'Sale Invoice: SAL-00067 - Total Area: 94.5 sq ft', '2026-09-01 08:17:38'),
(799, '2026-09-01', 25, 'SALE', 72, 0.00, 9.75, 44174.25, 690.00, 6727.50, 'Sale Invoice: SAL-00068 - Total Area: 9.75 sq ft', '2026-09-01 08:20:52'),
(800, '2026-09-01', 24, 'SALE', 73, 0.00, 33.75, 20690.06, 420.00, 14175.00, 'Sale Invoice: SAL-00069 - Total Area: 33.75 sq ft', '2026-09-01 08:30:17'),
(801, '2026-09-01', 23, 'SALE', 73, 0.00, 15.00, 54865.13, 240.00, 3600.00, 'Sale Invoice: SAL-00069 - Total Area: 15 sq ft', '2026-09-01 08:30:17'),
(802, '2026-09-01', 23, 'SALE', 73, 0.00, 9.00, 54856.13, 240.00, 2160.00, 'Sale Invoice: SAL-00069 - Total Area: 9 sq ft', '2026-09-01 08:30:17'),
(803, '2026-09-01', 23, 'SALE', 73, 0.00, 6.75, 54849.38, 240.00, 1620.00, 'Sale Invoice: SAL-00069 - Total Area: 6.75 sq ft', '2026-09-01 08:30:17'),
(804, '2026-09-01', 45, 'SALE', 73, 0.00, 84.00, 19156.25, 380.00, 31920.00, 'Sale Invoice: SAL-00069 - Total Area: 84 sq ft', '2026-09-01 08:30:17'),
(805, '2026-09-01', 18, 'SALE', 74, 0.00, 84.00, 1414.00, 500.00, 42000.00, 'Sale Invoice: SAL-00070 - Total Area: 84 sq ft', '2026-09-01 08:32:46'),
(806, '2026-09-01', 18, 'SALE', 74, 0.00, 48.00, 1366.00, 500.00, 24000.00, 'Sale Invoice: SAL-00070 - Total Area: 48 sq ft', '2026-09-01 08:32:46'),
(807, '2026-09-01', 18, 'SALE', 74, 0.00, 24.00, 1342.00, 500.00, 12000.00, 'Sale Invoice: SAL-00070 - Total Area: 24 sq ft', '2026-09-01 08:32:46'),
(808, '2026-09-01', 45, 'SALE', 75, 0.00, 7.50, 19148.75, 460.00, 3450.00, 'Sale Invoice: SAL-00071 - Total Area: 7.5 sq ft', '2026-09-01 08:37:23'),
(809, '2026-09-01', 45, 'SALE', 75, 0.00, 6.25, 19142.50, 460.00, 2875.00, 'Sale Invoice: SAL-00071 - Total Area: 6.25 sq ft', '2026-09-01 08:37:23'),
(810, '2026-09-01', 27, 'SALE', 76, 0.00, 12.25, 272.75, 940.00, 11515.00, 'Sale Invoice: SAL-00072 - Total Area: 12.25 sq ft', '2026-09-01 08:50:38'),
(811, '2026-09-01', 24, 'SALE', 77, 0.00, 7.00, 20683.06, 440.00, 3080.00, 'Sale Invoice: SAL-00073 - Total Area: 7 sq ft', '2026-09-01 08:55:41'),
(812, '2026-09-01', 24, 'SALE', 77, 0.00, 5.25, 20677.81, 440.00, 2310.00, 'Sale Invoice: SAL-00073 - Total Area: 5.25 sq ft', '2026-09-01 08:55:41'),
(813, '2026-09-01', 24, 'SALE', 77, 0.00, 3.75, 20674.06, 440.00, 1650.00, 'Sale Invoice: SAL-00073 - Total Area: 3.75 sq ft', '2026-09-01 08:55:41'),
(814, '2026-09-01', 25, 'SALE', 77, 0.00, 5.25, 44169.00, 690.00, 3622.50, 'Sale Invoice: SAL-00073 - Total Area: 5.25 sq ft', '2026-09-01 08:55:41'),
(815, '2026-09-01', 45, 'SALE', 77, 0.00, 6.13, 19136.38, 380.00, 2327.50, 'Sale Invoice: SAL-00073 - Total Area: 6.125 sq ft', '2026-09-01 08:55:41'),
(816, '2026-09-01', 24, 'SALE', 78, 0.00, 4.50, 20669.56, 420.00, 1890.00, 'Sale Invoice: SAL-00074 - Total Area: 4.5 sq ft', '2026-09-01 09:01:08'),
(817, '2026-09-01', 24, 'SALE', 78, 0.00, 7.50, 20662.06, 420.00, 3150.00, 'Sale Invoice: SAL-00074 - Total Area: 7.5 sq ft', '2026-09-01 09:01:08'),
(818, '2026-09-01', 25, 'SALE', 79, 0.00, 7.50, 44161.50, 670.00, 5025.00, 'Sale Invoice: SAL-00075 - Total Area: 7.5 sq ft', '2026-09-01 09:14:24'),
(819, '2026-09-01', 45, 'SALE', 79, 0.00, 12.00, 19124.38, 370.00, 4440.00, 'Sale Invoice: SAL-00075 - Total Area: 12 sq ft', '2026-09-01 09:14:24'),
(820, '2026-09-01', 45, 'SALE', 79, 0.00, 7.50, 19116.88, 370.00, 2775.00, 'Sale Invoice: SAL-00075 - Total Area: 7.5 sq ft', '2026-09-01 09:14:24'),
(821, '2026-09-01', 23, 'SALE', 80, 0.00, 14.00, 54835.38, 310.00, 4340.00, 'Sale Invoice: SAL-00076 - Total Area: 14 sq ft', '2026-09-01 09:19:56'),
(822, '2026-09-01', 23, 'SALE', 80, 0.00, 14.00, 54821.38, 310.00, 4340.00, 'Sale Invoice: SAL-00076 - Total Area: 14 sq ft', '2026-09-01 09:19:56'),
(823, '2026-09-01', 23, 'ADJUSTMENT', 80, 14.00, 0.00, 54835.38, 0.00, 0.00, 'Sale Edit Reversal: SAL-00076', '2026-09-01 09:20:28'),
(824, '2026-09-01', 23, 'ADJUSTMENT', 80, 14.00, 0.00, 54849.38, 0.00, 0.00, 'Sale Edit Reversal: SAL-00076', '2026-09-01 09:20:28'),
(825, '2026-09-01', 23, 'SALE', 80, 0.00, 14.00, 54835.38, 310.00, 4340.00, 'Sale Invoice: SAL-00076 - Total Area: 14 sq ft', '2026-09-01 09:20:28'),
(826, '2026-09-01', 23, 'SALE', 80, 0.00, 14.00, 54821.38, 310.00, 4340.00, 'Sale Invoice: SAL-00076 - Total Area: 14 sq ft', '2026-09-01 09:20:28'),
(827, '2026-09-01', 45, 'SALE', 81, 0.00, 10.00, 19106.88, 430.00, 4300.00, 'Sale Invoice: SAL-00077 - Total Area: 10 sq ft', '2026-09-01 09:25:03'),
(828, '2026-09-01', 45, 'SALE', 81, 0.00, 4.00, 19102.88, 450.00, 1800.00, 'Sale Invoice: SAL-00077 - Total Area: 4 sq ft', '2026-09-01 09:25:03'),
(829, '2026-09-01', 25, 'SALE', 82, 0.00, 22.75, 44138.75, 690.00, 15697.50, 'Sale Invoice: SAL-00078 - Total Area: 22.75 sq ft', '2026-09-01 09:29:29'),
(830, '2026-09-01', 22, 'SALE', 83, 0.00, 18.00, 20082.00, 230.00, 4140.00, 'Sale Invoice: SAL-00079 - Total Area: 18 sq ft', '2026-09-01 11:37:40'),
(831, '2026-09-01', 24, 'SALE', 84, 0.00, 5.00, 20657.06, 440.00, 2200.00, 'Sale Invoice: SAL-00080 - Total Area: 5 sq ft', '2026-09-01 11:40:05'),
(832, '2026-09-01', 31, 'SALE', 85, 0.00, 3.75, 1672.41, 320.00, 1200.00, 'Sale Invoice: SAL-00081 - Total Area: 3.75 sq ft', '2026-09-01 11:46:59'),
(833, '2026-09-01', 31, 'SALE', 85, 0.00, 30.00, 1642.41, 320.00, 9600.00, 'Sale Invoice: SAL-00081 - Total Area: 30 sq ft', '2026-09-01 11:46:59'),
(834, '2026-09-01', 31, 'SALE', 85, 0.00, 21.00, 1621.41, 320.00, 6720.00, 'Sale Invoice: SAL-00081 - Total Area: 21 sq ft', '2026-09-01 11:46:59'),
(835, '2026-09-01', 23, 'SALE', 86, 0.00, 9.00, 54812.38, 300.00, 2700.00, 'Sale Invoice: SAL-00082 - Total Area: 9 sq ft', '2026-09-01 11:55:13'),
(836, '2026-09-01', 23, 'SALE', 86, 0.00, 9.00, 54803.38, 270.00, 2430.00, 'Sale Invoice: SAL-00082 - Total Area: 9 sq ft', '2026-09-01 11:55:13'),
(837, '2026-09-01', 25, 'SALE', 87, 0.00, 11.00, 44127.75, 690.00, 7590.00, 'Sale Invoice: SAL-00083 - Total Area: 11 sq ft', '2026-09-01 12:07:32'),
(838, '2026-09-01', 25, 'SALE', 87, 0.00, 9.00, 44118.75, 690.00, 6210.00, 'Sale Invoice: SAL-00083 - Total Area: 9 sq ft', '2026-09-01 12:07:32'),
(839, '2026-09-01', 25, 'SALE', 88, 0.00, 393.75, 43725.00, 690.00, 271687.50, 'Sale Invoice: SAL-00084 - Total Area: 393.75 sq ft', '2026-09-01 12:26:34'),
(840, '2026-09-01', 25, 'SALE', 88, 0.00, 13.00, 43712.00, 690.00, 8970.00, 'Sale Invoice: SAL-00084 - Total Area: 13 sq ft', '2026-09-01 12:26:34'),
(841, '2026-09-01', 25, 'SALE', 88, 0.00, 4.38, 43707.63, 690.00, 3018.75, 'Sale Invoice: SAL-00084 - Total Area: 4.375 sq ft', '2026-09-01 12:26:34'),
(842, '2026-09-01', 25, 'SALE', 89, 0.00, 42.00, 43665.63, 680.00, 28560.00, 'Sale Invoice: SAL-00085 - Total Area: 42 sq ft', '2026-09-01 12:33:29'),
(843, '2026-09-01', 25, 'SALE', 89, 0.00, 68.00, 43597.63, 680.00, 46240.00, 'Sale Invoice: SAL-00085 - Total Area: 68 sq ft', '2026-09-01 12:33:29'),
(844, '2026-09-01', 25, 'SALE', 89, 0.00, 93.50, 43504.13, 680.00, 63580.00, 'Sale Invoice: SAL-00085 - Total Area: 93.5 sq ft', '2026-09-01 12:33:29'),
(845, '2026-09-01', 25, 'SALE', 89, 0.00, 63.75, 43440.38, 680.00, 43350.00, 'Sale Invoice: SAL-00085 - Total Area: 63.75 sq ft', '2026-09-01 12:33:29'),
(846, '2026-09-01', 25, 'SALE', 89, 0.00, 21.25, 43419.13, 680.00, 14450.00, 'Sale Invoice: SAL-00085 - Total Area: 21.25 sq ft', '2026-09-01 12:33:29'),
(847, '2026-09-01', 25, 'SALE', 89, 0.00, 8.50, 43410.63, 680.00, 5780.00, 'Sale Invoice: SAL-00085 - Total Area: 8.5 sq ft', '2026-09-01 12:33:29'),
(848, '2026-09-01', 25, 'SALE', 89, 0.00, 8.50, 43402.13, 680.00, 5780.00, 'Sale Invoice: SAL-00085 - Total Area: 8.5 sq ft', '2026-09-01 12:33:29'),
(849, '2026-09-01', 25, 'SALE', 89, 0.00, 27.63, 43374.51, 680.00, 18785.00, 'Sale Invoice: SAL-00085 - Total Area: 27.625 sq ft', '2026-09-01 12:33:29'),
(850, '2026-09-01', 25, 'SALE', 89, 0.00, 36.13, 43338.39, 680.00, 24565.00, 'Sale Invoice: SAL-00085 - Total Area: 36.125 sq ft', '2026-09-01 12:33:29'),
(851, '2026-09-01', 25, 'SALE', 90, 0.00, 14.00, 43324.39, 690.00, 9660.00, 'Sale Invoice: SAL-00086 - Total Area: 14 sq ft', '2026-09-01 12:35:04'),
(852, '2026-09-01', 23, 'SALE', 91, 0.00, 48.00, 54755.38, 270.00, 12960.00, 'Sale Invoice: SAL-00087 - Total Area: 48 sq ft', '2026-09-01 12:48:10'),
(853, '2026-09-01', 25, 'SALE', 91, 0.00, 14.00, 43310.39, 690.00, 9660.00, 'Sale Invoice: SAL-00087 - Total Area: 14 sq ft', '2026-09-01 12:48:10'),
(854, '2026-09-01', 22, 'SALE', 92, 0.00, 7.00, 20075.00, 230.00, 1610.00, 'Sale Invoice: SAL-00088 - Total Area: 7 sq ft', '2026-09-01 12:54:22'),
(855, '2026-09-01', 22, 'SALE', 92, 0.00, 16.50, 20058.50, 230.00, 3795.00, 'Sale Invoice: SAL-00088 - Total Area: 16.5 sq ft', '2026-09-01 12:54:22'),
(856, '2026-09-01', 23, 'SALE', 93, 0.00, 39.38, 54716.01, 240.00, 9450.00, 'Sale Invoice: SAL-00089 - Total Area: 39.375 sq ft', '2026-09-01 12:56:40'),
(857, '2026-09-01', 23, 'SALE', 93, 0.00, 39.38, 54676.64, 240.00, 9450.00, 'Sale Invoice: SAL-00089 - Total Area: 39.375 sq ft', '2026-09-01 12:56:40'),
(858, '2026-09-01', 23, 'SALE', 93, 0.00, 30.00, 54646.64, 240.00, 7200.00, 'Sale Invoice: SAL-00089 - Total Area: 30 sq ft', '2026-09-01 12:56:40'),
(859, '2026-09-01', 18, 'SALE', 94, 0.00, 73.50, 1268.50, 450.00, 33075.00, 'Sale Invoice: SAL-00090 - Total Area: 73.5 sq ft', '2026-09-01 13:09:57'),
(860, '2026-09-01', 18, 'SALE', 94, 0.00, 24.50, 1244.00, 450.00, 11025.00, 'Sale Invoice: SAL-00090 - Total Area: 24.5 sq ft', '2026-09-01 13:09:57'),
(861, '2026-09-01', 18, 'SALE', 94, 0.00, 15.75, 1228.25, 450.00, 7087.50, 'Sale Invoice: SAL-00090 - Total Area: 15.75 sq ft', '2026-09-01 13:09:57'),
(862, '2026-09-01', 18, 'SALE', 94, 0.00, 49.50, 1178.75, 450.00, 22275.00, 'Sale Invoice: SAL-00090 - Total Area: 49.5 sq ft', '2026-09-01 13:09:57'),
(863, '2026-09-01', 18, 'SALE', 94, 0.00, 22.00, 1156.75, 450.00, 9900.00, 'Sale Invoice: SAL-00090 - Total Area: 22 sq ft', '2026-09-01 13:09:57'),
(864, '2026-09-01', 18, 'SALE', 94, 0.00, 31.50, 1125.25, 450.00, 14175.00, 'Sale Invoice: SAL-00090 - Total Area: 31.5 sq ft', '2026-09-01 13:09:57'),
(865, '2026-09-01', 18, 'SALE', 94, 0.00, 36.00, 1089.25, 450.00, 16200.00, 'Sale Invoice: SAL-00090 - Total Area: 36 sq ft', '2026-09-01 13:09:57');
INSERT INTO `inventory_ledger` (`id`, `date`, `product_id`, `reference_type`, `reference_id`, `qty_in`, `qty_out`, `balance_qty`, `unit_price`, `total_amount`, `remarks`, `created_at`) VALUES
(866, '2026-09-01', 18, 'SALE', 94, 0.00, 24.00, 1065.25, 450.00, 10800.00, 'Sale Invoice: SAL-00090 - Total Area: 24 sq ft', '2026-09-01 13:09:57'),
(867, '2026-09-01', 18, 'SALE', 94, 0.00, 42.00, 1023.25, 450.00, 18900.00, 'Sale Invoice: SAL-00090 - Total Area: 42 sq ft', '2026-09-01 13:09:57'),
(868, '2026-09-01', 18, 'SALE', 94, 0.00, 40.00, 983.25, 480.00, 19200.00, 'Sale Invoice: SAL-00090 - Total Area: 40 sq ft', '2026-09-01 13:09:57'),
(869, '2026-09-01', 18, 'SALE', 94, 0.00, 22.00, 961.25, 480.00, 10560.00, 'Sale Invoice: SAL-00090 - Total Area: 22 sq ft', '2026-09-01 13:09:57'),
(870, '2026-09-01', 18, 'SALE', 94, 0.00, 18.00, 943.25, 450.00, 8100.00, 'Sale Invoice: SAL-00090 - Total Area: 18 sq ft', '2026-09-01 13:09:57'),
(871, '2026-09-01', 18, 'SALE', 94, 0.00, 27.00, 916.25, 480.00, 12960.00, 'Sale Invoice: SAL-00090 - Total Area: 27 sq ft', '2026-09-01 13:09:57'),
(872, '2026-09-01', 18, 'SALE', 94, 0.00, 40.00, 876.25, 480.00, 19200.00, 'Sale Invoice: SAL-00090 - Total Area: 40 sq ft', '2026-09-01 13:09:57'),
(873, '2026-09-01', 18, 'SALE', 94, 0.00, 75.00, 801.25, 480.00, 36000.00, 'Sale Invoice: SAL-00090 - Total Area: 75 sq ft', '2026-09-01 13:09:57'),
(874, '2026-09-01', 18, 'ADJUSTMENT', 94, 73.50, 0.00, 874.75, 0.00, 0.00, 'Sale Edit Reversal: SAL-00090', '2026-09-01 13:18:23'),
(875, '2026-09-01', 18, 'ADJUSTMENT', 94, 24.50, 0.00, 899.25, 0.00, 0.00, 'Sale Edit Reversal: SAL-00090', '2026-09-01 13:18:23'),
(876, '2026-09-01', 18, 'ADJUSTMENT', 94, 15.75, 0.00, 915.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00090', '2026-09-01 13:18:23'),
(877, '2026-09-01', 18, 'ADJUSTMENT', 94, 49.50, 0.00, 964.50, 0.00, 0.00, 'Sale Edit Reversal: SAL-00090', '2026-09-01 13:18:23'),
(878, '2026-09-01', 18, 'ADJUSTMENT', 94, 22.00, 0.00, 986.50, 0.00, 0.00, 'Sale Edit Reversal: SAL-00090', '2026-09-01 13:18:23'),
(879, '2026-09-01', 18, 'ADJUSTMENT', 94, 31.50, 0.00, 1018.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00090', '2026-09-01 13:18:23'),
(880, '2026-09-01', 18, 'ADJUSTMENT', 94, 36.00, 0.00, 1054.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00090', '2026-09-01 13:18:23'),
(881, '2026-09-01', 18, 'ADJUSTMENT', 94, 24.00, 0.00, 1078.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00090', '2026-09-01 13:18:23'),
(882, '2026-09-01', 18, 'ADJUSTMENT', 94, 42.00, 0.00, 1120.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00090', '2026-09-01 13:18:23'),
(883, '2026-09-01', 18, 'ADJUSTMENT', 94, 40.00, 0.00, 1160.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00090', '2026-09-01 13:18:23'),
(884, '2026-09-01', 18, 'ADJUSTMENT', 94, 22.00, 0.00, 1182.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00090', '2026-09-01 13:18:23'),
(885, '2026-09-01', 18, 'ADJUSTMENT', 94, 18.00, 0.00, 1200.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00090', '2026-09-01 13:18:23'),
(886, '2026-09-01', 18, 'ADJUSTMENT', 94, 27.00, 0.00, 1227.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00090', '2026-09-01 13:18:23'),
(887, '2026-09-01', 18, 'ADJUSTMENT', 94, 40.00, 0.00, 1267.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00090', '2026-09-01 13:18:23'),
(888, '2026-09-01', 18, 'ADJUSTMENT', 94, 75.00, 0.00, 1342.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00090', '2026-09-01 13:18:23'),
(889, '2026-09-01', 18, 'SALE', 94, 0.00, 73.50, 1268.50, 480.00, 35280.00, 'Sale Invoice: SAL-00090 - Total Area: 73.5 sq ft', '2026-09-01 13:18:23'),
(890, '2026-09-01', 18, 'SALE', 94, 0.00, 24.50, 1244.00, 480.00, 11760.00, 'Sale Invoice: SAL-00090 - Total Area: 24.5 sq ft', '2026-09-01 13:18:23'),
(891, '2026-09-01', 18, 'SALE', 94, 0.00, 15.75, 1228.25, 480.00, 7560.00, 'Sale Invoice: SAL-00090 - Total Area: 15.75 sq ft', '2026-09-01 13:18:23'),
(892, '2026-09-01', 18, 'SALE', 94, 0.00, 49.50, 1178.75, 480.00, 23760.00, 'Sale Invoice: SAL-00090 - Total Area: 49.5 sq ft', '2026-09-01 13:18:23'),
(893, '2026-09-01', 18, 'SALE', 94, 0.00, 22.00, 1156.75, 480.00, 10560.00, 'Sale Invoice: SAL-00090 - Total Area: 22 sq ft', '2026-09-01 13:18:23'),
(894, '2026-09-01', 18, 'SALE', 94, 0.00, 31.50, 1125.25, 480.00, 15120.00, 'Sale Invoice: SAL-00090 - Total Area: 31.5 sq ft', '2026-09-01 13:18:23'),
(895, '2026-09-01', 18, 'SALE', 94, 0.00, 36.00, 1089.25, 480.00, 17280.00, 'Sale Invoice: SAL-00090 - Total Area: 36 sq ft', '2026-09-01 13:18:23'),
(896, '2026-09-01', 18, 'SALE', 94, 0.00, 24.00, 1065.25, 480.00, 11520.00, 'Sale Invoice: SAL-00090 - Total Area: 24 sq ft', '2026-09-01 13:18:23'),
(897, '2026-09-01', 18, 'SALE', 94, 0.00, 42.00, 1023.25, 480.00, 20160.00, 'Sale Invoice: SAL-00090 - Total Area: 42 sq ft', '2026-09-01 13:18:23'),
(898, '2026-09-01', 18, 'SALE', 94, 0.00, 40.00, 983.25, 510.00, 20400.00, 'Sale Invoice: SAL-00090 - Total Area: 40 sq ft', '2026-09-01 13:18:23'),
(899, '2026-09-01', 18, 'SALE', 94, 0.00, 22.00, 961.25, 510.00, 11220.00, 'Sale Invoice: SAL-00090 - Total Area: 22 sq ft', '2026-09-01 13:18:23'),
(900, '2026-09-01', 18, 'SALE', 94, 0.00, 18.00, 943.25, 480.00, 8640.00, 'Sale Invoice: SAL-00090 - Total Area: 18 sq ft', '2026-09-01 13:18:23'),
(901, '2026-09-01', 18, 'SALE', 94, 0.00, 27.00, 916.25, 510.00, 13770.00, 'Sale Invoice: SAL-00090 - Total Area: 27 sq ft', '2026-09-01 13:18:23'),
(902, '2026-09-01', 18, 'SALE', 94, 0.00, 40.00, 876.25, 510.00, 20400.00, 'Sale Invoice: SAL-00090 - Total Area: 40 sq ft', '2026-09-01 13:18:23'),
(903, '2026-09-01', 18, 'SALE', 94, 0.00, 75.00, 801.25, 510.00, 38250.00, 'Sale Invoice: SAL-00090 - Total Area: 75 sq ft', '2026-09-01 13:18:23'),
(904, '2026-09-01', 18, 'ADJUSTMENT', 94, 2.00, 0.00, 803.25, 480.00, 0.00, 'Sale Deleted: SAL-00090', '2026-09-01 13:20:33'),
(905, '2026-09-01', 18, 'ADJUSTMENT', 94, 1.00, 0.00, 804.25, 480.00, 0.00, 'Sale Deleted: SAL-00090', '2026-09-01 13:20:33'),
(906, '2026-09-01', 18, 'ADJUSTMENT', 94, 1.00, 0.00, 805.25, 480.00, 0.00, 'Sale Deleted: SAL-00090', '2026-09-01 13:20:33'),
(907, '2026-09-01', 18, 'ADJUSTMENT', 94, 1.00, 0.00, 806.25, 480.00, 0.00, 'Sale Deleted: SAL-00090', '2026-09-01 13:20:33'),
(908, '2026-09-01', 18, 'ADJUSTMENT', 94, 1.00, 0.00, 807.25, 480.00, 0.00, 'Sale Deleted: SAL-00090', '2026-09-01 13:20:33'),
(909, '2026-09-01', 18, 'ADJUSTMENT', 94, 1.00, 0.00, 808.25, 480.00, 0.00, 'Sale Deleted: SAL-00090', '2026-09-01 13:20:33'),
(910, '2026-09-01', 18, 'ADJUSTMENT', 94, 1.00, 0.00, 809.25, 480.00, 0.00, 'Sale Deleted: SAL-00090', '2026-09-01 13:20:33'),
(911, '2026-09-01', 18, 'ADJUSTMENT', 94, 1.00, 0.00, 810.25, 480.00, 0.00, 'Sale Deleted: SAL-00090', '2026-09-01 13:20:33'),
(912, '2026-09-01', 18, 'ADJUSTMENT', 94, 1.00, 0.00, 811.25, 480.00, 0.00, 'Sale Deleted: SAL-00090', '2026-09-01 13:20:33'),
(913, '2026-09-01', 18, 'ADJUSTMENT', 94, 2.00, 0.00, 813.25, 510.00, 0.00, 'Sale Deleted: SAL-00090', '2026-09-01 13:20:33'),
(914, '2026-09-01', 18, 'ADJUSTMENT', 94, 1.00, 0.00, 814.25, 510.00, 0.00, 'Sale Deleted: SAL-00090', '2026-09-01 13:20:33'),
(915, '2026-09-01', 18, 'ADJUSTMENT', 94, 1.00, 0.00, 815.25, 480.00, 0.00, 'Sale Deleted: SAL-00090', '2026-09-01 13:20:33'),
(916, '2026-09-01', 18, 'ADJUSTMENT', 94, 2.00, 0.00, 817.25, 510.00, 0.00, 'Sale Deleted: SAL-00090', '2026-09-01 13:20:33'),
(917, '2026-09-01', 18, 'ADJUSTMENT', 94, 1.00, 0.00, 818.25, 510.00, 0.00, 'Sale Deleted: SAL-00090', '2026-09-01 13:20:33'),
(918, '2026-09-01', 18, 'ADJUSTMENT', 94, 3.00, 0.00, 821.25, 510.00, 0.00, 'Sale Deleted: SAL-00090', '2026-09-01 13:20:33'),
(919, '2026-09-01', 35, 'SALE', 95, 0.00, 6.75, 17036.50, 330.00, 2227.50, 'Sale Invoice: SAL-00090 - Total Area: 6.75 sq ft', '2026-09-01 13:23:38'),
(920, '2026-09-01', 35, 'ADJUSTMENT', 95, 1.00, 0.00, 17037.50, 330.00, 0.00, 'Sale Deleted: SAL-00090', '2026-09-01 13:23:53'),
(921, '2026-09-01', 35, 'SALE', 96, 0.00, 6.75, 17030.75, 415.00, 2801.25, 'Sale Invoice: SAL-00090 - Total Area: 6.75 sq ft', '2026-09-01 13:27:26'),
(922, '2026-09-01', 35, 'SALE', 97, 0.00, 3.00, 17027.75, 330.00, 990.00, 'Sale Invoice: SAL-00091 - Total Area: 3 sq ft', '2026-09-01 14:55:21'),
(923, '2026-09-01', 35, 'SALE', 97, 0.00, 3.00, 17024.75, 330.00, 990.00, 'Sale Invoice: SAL-00091 - Total Area: 3 sq ft', '2026-09-01 14:55:21'),
(924, '2026-09-01', 35, 'SALE', 97, 0.00, 4.50, 17020.25, 330.00, 1485.00, 'Sale Invoice: SAL-00091 - Total Area: 4.5 sq ft', '2026-09-01 14:55:21'),
(925, '2026-09-01', 35, 'SALE', 97, 0.00, 6.00, 17014.25, 330.00, 1980.00, 'Sale Invoice: SAL-00091 - Total Area: 6 sq ft', '2026-09-01 14:55:21'),
(926, '2026-09-01', 35, 'SALE', 97, 0.00, 6.00, 17008.25, 330.00, 1980.00, 'Sale Invoice: SAL-00091 - Total Area: 6 sq ft', '2026-09-01 14:55:21'),
(927, '2026-09-01', 25, 'ADJUSTMENT', 66, 33.25, 0.00, 43343.64, 0.00, 0.00, 'Sale Edit Reversal: SAL-00062', '2026-09-01 14:57:03'),
(928, '2026-09-01', 24, 'ADJUSTMENT', 66, 4.50, 0.00, 20661.56, 0.00, 0.00, 'Sale Edit Reversal: SAL-00062', '2026-09-01 14:57:03'),
(929, '2026-09-01', 24, 'ADJUSTMENT', 66, 6.50, 0.00, 20668.06, 0.00, 0.00, 'Sale Edit Reversal: SAL-00062', '2026-09-01 14:57:03'),
(930, '2026-09-01', 24, 'SALE', 66, 0.00, 4.50, 20663.56, 440.00, 1980.00, 'Sale Invoice: SAL-00062 - Total Area: 4.5 sq ft', '2026-09-01 14:57:03'),
(931, '2026-09-01', 24, 'SALE', 66, 0.00, 6.50, 20657.06, 440.00, 2860.00, 'Sale Invoice: SAL-00062 - Total Area: 6.5 sq ft', '2026-09-01 14:57:03'),
(932, '2026-09-01', 25, 'SALE', 66, 0.00, 33.25, 43310.39, 690.00, 22942.50, 'Sale Invoice: SAL-00062 - Total Area: 33.25 sq ft', '2026-09-01 14:57:03'),
(933, '2026-09-01', 23, 'SALE', 98, 0.00, 4.50, 54642.14, 270.00, 1215.00, 'Sale Invoice: SAL-00092 - Total Area: 4.5 sq ft', '2026-09-01 14:58:39'),
(934, '2026-09-01', 48, 'SALE', 99, 0.00, 7.50, 280.50, 1400.00, 10500.00, 'Sale Invoice: SAL-00093 - Total Area: 7.5 sq ft', '2026-09-01 15:00:27'),
(935, '2026-09-01', 29, 'SALE', 100, 0.00, 12.50, 2779.51, 1400.00, 17500.00, 'Sale Invoice: SAL-00094 - Total Area: 12.5 sq ft', '2026-09-01 15:01:36'),
(936, '2026-09-01', 29, 'SALE', 101, 0.00, 42.50, 2737.01, 1400.00, 59500.00, 'Sale Invoice: SAL-00095 - Total Area: 42.5 sq ft', '2026-09-01 15:02:34'),
(937, '2026-09-01', 25, 'SALE', 102, 0.00, 11.25, 43299.14, 690.00, 7762.50, 'Sale Invoice: SAL-00096 - Total Area: 11.25 sq ft', '2026-09-01 15:05:26'),
(938, '2026-09-01', 25, 'SALE', 102, 0.00, 11.25, 43287.89, 690.00, 7762.50, 'Sale Invoice: SAL-00096 - Total Area: 11.25 sq ft', '2026-09-01 15:05:26'),
(939, '2026-09-01', 25, 'SALE', 102, 0.00, 20.00, 43267.89, 690.00, 13800.00, 'Sale Invoice: SAL-00096 - Total Area: 20 sq ft', '2026-09-01 15:05:26'),
(940, '2026-09-01', 25, 'SALE', 102, 0.00, 11.00, 43256.89, 690.00, 7590.00, 'Sale Invoice: SAL-00096 - Total Area: 11 sq ft', '2026-09-01 15:05:26'),
(941, '2026-09-01', 25, 'SALE', 103, 0.00, 11.25, 43245.64, 690.00, 7762.50, 'Sale Invoice: SAL-00097 - Total Area: 11.25 sq ft', '2026-09-01 15:08:53'),
(942, '2026-09-01', 25, 'SALE', 103, 0.00, 11.25, 43234.39, 690.00, 7762.50, 'Sale Invoice: SAL-00097 - Total Area: 11.25 sq ft', '2026-09-01 15:08:53'),
(943, '2026-09-01', 25, 'SALE', 103, 0.00, 17.50, 43216.89, 690.00, 12075.00, 'Sale Invoice: SAL-00097 - Total Area: 17.5 sq ft', '2026-09-01 15:08:53'),
(944, '2026-09-01', 25, 'SALE', 103, 0.00, 9.63, 43207.27, 690.00, 6641.25, 'Sale Invoice: SAL-00097 - Total Area: 9.625 sq ft', '2026-09-01 15:08:53'),
(945, '2026-09-01', 25, 'SALE', 103, 0.00, 7.88, 43199.40, 690.00, 5433.75, 'Sale Invoice: SAL-00097 - Total Area: 7.875 sq ft', '2026-09-01 15:08:53'),
(946, '2026-09-01', 25, 'SALE', 104, 0.00, 35.00, 43164.40, 690.00, 24150.00, 'Sale Invoice: SAL-00098 - Total Area: 35 sq ft', '2026-09-01 15:10:57'),
(947, '2026-09-01', 25, 'SALE', 104, 0.00, 4.50, 43159.90, 690.00, 3105.00, 'Sale Invoice: SAL-00098 - Total Area: 4.5 sq ft', '2026-09-01 15:10:57'),
(948, '2026-09-01', 23, 'SALE', 105, 0.00, 588.00, 54054.14, 235.00, 138180.00, 'Sale Invoice: SAL-00099 - Total Area: 588 sq ft', '2026-09-01 15:30:49'),
(949, '2026-09-01', 35, 'SALE', 105, 0.00, 252.00, 16756.25, 320.00, 80640.00, 'Sale Invoice: SAL-00099 - Total Area: 252 sq ft', '2026-09-01 15:30:49'),
(950, '2026-09-01', 45, 'SALE', 105, 0.00, 168.00, 18934.88, 365.00, 61320.00, 'Sale Invoice: SAL-00099 - Total Area: 168 sq ft', '2026-09-01 15:30:49'),
(951, '2026-09-01', 43, 'SALE', 105, 0.00, 168.00, 3905.75, 250.00, 42000.00, 'Sale Invoice: SAL-00099 - Total Area: 168 sq ft', '2026-09-01 15:30:49'),
(952, '2026-09-01', 39, 'SALE', 106, 0.00, 45.50, 5763.75, 450.00, 20475.00, 'Sale Invoice: SAL-00100 - Total Area: 45.5 sq ft', '2026-09-01 15:34:17'),
(953, '2026-09-01', 39, 'SALE', 106, 0.00, 49.00, 5714.75, 450.00, 22050.00, 'Sale Invoice: SAL-00100 - Total Area: 49 sq ft', '2026-09-01 15:34:17'),
(954, '2026-09-01', 39, 'SALE', 106, 0.00, 10.50, 5704.25, 450.00, 4725.00, 'Sale Invoice: SAL-00100 - Total Area: 10.5 sq ft', '2026-09-01 15:34:17'),
(955, '2026-09-01', 39, 'SALE', 106, 0.00, 7.50, 5696.75, 450.00, 3375.00, 'Sale Invoice: SAL-00100 - Total Area: 7.5 sq ft', '2026-09-01 15:34:17'),
(956, '2026-09-01', 39, 'SALE', 106, 0.00, 4.50, 5692.25, 450.00, 2025.00, 'Sale Invoice: SAL-00100 - Total Area: 4.5 sq ft', '2026-09-01 15:34:17'),
(957, '2026-09-01', 24, 'SALE', 106, 0.00, 25.50, 20631.56, 440.00, 11220.00, 'Sale Invoice: SAL-00100 - Total Area: 25.5 sq ft', '2026-09-01 15:34:17'),
(958, '2026-09-01', 24, 'SALE', 106, 0.00, 48.00, 20583.56, 440.00, 21120.00, 'Sale Invoice: SAL-00100 - Total Area: 48 sq ft', '2026-09-01 15:34:17'),
(959, '2026-09-01', 24, 'SALE', 106, 0.00, 24.00, 20559.56, 550.00, 13200.00, 'Sale Invoice: SAL-00100 - Total Area: 24 sq ft', '2026-09-01 15:34:17'),
(960, '2026-09-01', 39, 'ADJUSTMENT', 106, 45.50, 0.00, 5737.75, 0.00, 0.00, 'Sale Edit Reversal: SAL-00100', '2026-09-01 15:34:49'),
(961, '2026-09-01', 39, 'ADJUSTMENT', 106, 49.00, 0.00, 5786.75, 0.00, 0.00, 'Sale Edit Reversal: SAL-00100', '2026-09-01 15:34:49'),
(962, '2026-09-01', 39, 'ADJUSTMENT', 106, 10.50, 0.00, 5797.25, 0.00, 0.00, 'Sale Edit Reversal: SAL-00100', '2026-09-01 15:34:49'),
(963, '2026-09-01', 39, 'ADJUSTMENT', 106, 7.50, 0.00, 5804.75, 0.00, 0.00, 'Sale Edit Reversal: SAL-00100', '2026-09-01 15:34:49'),
(964, '2026-09-01', 39, 'ADJUSTMENT', 106, 4.50, 0.00, 5809.25, 0.00, 0.00, 'Sale Edit Reversal: SAL-00100', '2026-09-01 15:34:49'),
(965, '2026-09-01', 24, 'ADJUSTMENT', 106, 25.50, 0.00, 20585.06, 0.00, 0.00, 'Sale Edit Reversal: SAL-00100', '2026-09-01 15:34:49'),
(966, '2026-09-01', 24, 'ADJUSTMENT', 106, 48.00, 0.00, 20633.06, 0.00, 0.00, 'Sale Edit Reversal: SAL-00100', '2026-09-01 15:34:49'),
(967, '2026-09-01', 24, 'ADJUSTMENT', 106, 24.00, 0.00, 20657.06, 0.00, 0.00, 'Sale Edit Reversal: SAL-00100', '2026-09-01 15:34:49'),
(968, '2026-09-01', 24, 'SALE', 106, 0.00, 25.50, 20631.56, 440.00, 11220.00, 'Sale Invoice: SAL-00100 - Total Area: 25.5 sq ft', '2026-09-01 15:34:49'),
(969, '2026-09-01', 24, 'SALE', 106, 0.00, 48.00, 20583.56, 440.00, 21120.00, 'Sale Invoice: SAL-00100 - Total Area: 48 sq ft', '2026-09-01 15:34:49'),
(970, '2026-09-01', 24, 'SALE', 106, 0.00, 24.00, 20559.56, 550.00, 13200.00, 'Sale Invoice: SAL-00100 - Total Area: 24 sq ft', '2026-09-01 15:34:49'),
(971, '2026-09-01', 39, 'SALE', 106, 0.00, 45.50, 5763.75, 450.00, 20475.00, 'Sale Invoice: SAL-00100 - Total Area: 45.5 sq ft', '2026-09-01 15:34:49'),
(972, '2026-09-01', 39, 'SALE', 106, 0.00, 49.00, 5714.75, 450.00, 22050.00, 'Sale Invoice: SAL-00100 - Total Area: 49 sq ft', '2026-09-01 15:34:49'),
(973, '2026-09-01', 39, 'SALE', 106, 0.00, 10.50, 5704.25, 450.00, 4725.00, 'Sale Invoice: SAL-00100 - Total Area: 10.5 sq ft', '2026-09-01 15:34:49'),
(974, '2026-09-01', 39, 'SALE', 106, 0.00, 7.50, 5696.75, 450.00, 3375.00, 'Sale Invoice: SAL-00100 - Total Area: 7.5 sq ft', '2026-09-01 15:34:49'),
(975, '2026-09-01', 39, 'SALE', 106, 0.00, 4.50, 5692.25, 450.00, 2025.00, 'Sale Invoice: SAL-00100 - Total Area: 4.5 sq ft', '2026-09-01 15:34:49'),
(976, '2026-09-01', 23, 'ADJUSTMENT', 105, 588.00, 0.00, 54642.14, 0.00, 0.00, 'Sale Edit Reversal: SAL-00099', '2026-09-01 15:35:24'),
(977, '2026-09-01', 35, 'ADJUSTMENT', 105, 252.00, 0.00, 17008.25, 0.00, 0.00, 'Sale Edit Reversal: SAL-00099', '2026-09-01 15:35:24'),
(978, '2026-09-01', 45, 'ADJUSTMENT', 105, 168.00, 0.00, 19102.88, 0.00, 0.00, 'Sale Edit Reversal: SAL-00099', '2026-09-01 15:35:24'),
(979, '2026-09-01', 43, 'ADJUSTMENT', 105, 168.00, 0.00, 4073.75, 0.00, 0.00, 'Sale Edit Reversal: SAL-00099', '2026-09-01 15:35:24'),
(980, '2026-09-01', 23, 'SALE', 105, 0.00, 588.00, 54054.14, 235.00, 138180.00, 'Sale Invoice: SAL-00099 - Total Area: 588 sq ft', '2026-09-01 15:35:24'),
(981, '2026-09-01', 35, 'SALE', 105, 0.00, 252.00, 16756.25, 320.00, 80640.00, 'Sale Invoice: SAL-00099 - Total Area: 252 sq ft', '2026-09-01 15:35:24'),
(982, '2026-09-01', 43, 'SALE', 105, 0.00, 168.00, 3905.75, 250.00, 42000.00, 'Sale Invoice: SAL-00099 - Total Area: 168 sq ft', '2026-09-01 15:35:24'),
(983, '2026-09-01', 45, 'SALE', 105, 0.00, 168.00, 18934.88, 365.00, 61320.00, 'Sale Invoice: SAL-00099 - Total Area: 168 sq ft', '2026-09-01 15:35:24'),
(984, '2026-09-01', 25, 'SALE', 107, 0.00, 4.00, 43155.90, 690.00, 2760.00, 'Sale Invoice: SAL-00101 - Total Area: 4 sq ft', '2026-09-01 15:42:16'),
(985, '2026-09-01', 25, 'SALE', 107, 0.00, 7.50, 43148.40, 690.00, 5175.00, 'Sale Invoice: SAL-00101 - Total Area: 7.5 sq ft', '2026-09-01 15:42:16'),
(986, '2026-09-01', 45, 'SALE', 107, 0.00, 12.00, 18922.88, 440.00, 5280.00, 'Sale Invoice: SAL-00101 - Total Area: 12 sq ft', '2026-09-01 15:42:16'),
(987, '2026-09-01', 24, 'SALE', 107, 0.00, 16.00, 20543.56, 480.00, 7680.00, 'Sale Invoice: SAL-00101 - Total Area: 16 sq ft', '2026-09-01 15:42:16'),
(988, '2026-09-01', 25, 'SALE', 108, 0.00, 17.50, 43130.90, 690.00, 12075.00, 'Sale Invoice: SAL-00102 - Total Area: 17.5 sq ft', '2026-09-01 15:44:21'),
(989, '2026-09-01', 25, 'SALE', 108, 0.00, 3.75, 43127.15, 690.00, 2587.50, 'Sale Invoice: SAL-00102 - Total Area: 3.75 sq ft', '2026-09-01 15:44:21'),
(990, '2026-09-01', 25, 'SALE', 108, 0.00, 21.25, 43105.90, 690.00, 14662.50, 'Sale Invoice: SAL-00102 - Total Area: 21.25 sq ft', '2026-09-01 15:44:21'),
(991, '2026-09-01', 25, 'SALE', 108, 0.00, 14.00, 43091.90, 690.00, 9660.00, 'Sale Invoice: SAL-00102 - Total Area: 14 sq ft', '2026-09-01 15:44:21'),
(992, '2026-09-01', 25, 'ADJUSTMENT', 47, 35.00, 0.00, 43126.90, 0.00, 0.00, 'Sale Edit Reversal: SAL-00043', '2026-09-01 15:48:35'),
(993, '2026-09-01', 25, 'ADJUSTMENT', 47, 31.50, 0.00, 43158.40, 0.00, 0.00, 'Sale Edit Reversal: SAL-00043', '2026-09-01 15:48:35'),
(994, '2026-09-01', 25, 'ADJUSTMENT', 47, 10.00, 0.00, 43168.40, 0.00, 0.00, 'Sale Edit Reversal: SAL-00043', '2026-09-01 15:48:35'),
(995, '2026-08-31', 25, 'SALE', 47, 0.00, 35.00, 43133.40, 680.00, 23800.00, 'Sale Invoice: SAL-00043 - Total Area: 35 sq ft', '2026-09-01 15:48:35'),
(996, '2026-08-31', 25, 'SALE', 47, 0.00, 31.50, 43101.90, 680.00, 21420.00, 'Sale Invoice: SAL-00043 - Total Area: 31.5 sq ft', '2026-09-01 15:48:35'),
(997, '2026-08-31', 25, 'SALE', 47, 0.00, 10.00, 43091.90, 680.00, 6800.00, 'Sale Invoice: SAL-00043 - Total Area: 10 sq ft', '2026-09-01 15:48:35'),
(998, '2026-08-31', 24, 'SALE', 47, 0.00, 3.75, 20539.81, 445.00, 1668.75, 'Sale Invoice: SAL-00043 - Total Area: 3.75 sq ft', '2026-09-01 15:48:35'),
(999, '2026-08-31', 23, 'SALE', 47, 0.00, 8.00, 54046.14, 240.00, 1920.00, 'Sale Invoice: SAL-00043 - Total Area: 8 sq ft', '2026-09-01 15:48:35'),
(1000, '2026-09-01', 23, 'ADJUSTMENT', 68, 12.25, 0.00, 54058.39, 0.00, 0.00, 'Sale Edit Reversal: SAL-00064', '2026-09-01 15:50:46'),
(1001, '2026-08-31', 23, 'SALE', 68, 0.00, 12.25, 54046.14, 300.00, 3675.00, 'Sale Invoice: SAL-00064 - Total Area: 12.25 sq ft', '2026-09-01 15:50:46'),
(1002, '2026-09-01', 42, 'ADJUSTMENT', 62, 24.00, 0.00, 7182.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00058', '2026-09-01 15:51:54'),
(1003, '2026-09-01', 42, 'ADJUSTMENT', 62, 18.00, 0.00, 7200.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00058', '2026-09-01 15:51:54'),
(1004, '2026-09-01', 43, 'ADJUSTMENT', 62, 6.25, 0.00, 3912.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00058', '2026-09-01 15:51:54'),
(1005, '2026-08-31', 42, 'SALE', 62, 0.00, 24.00, 7176.00, 200.00, 4800.00, 'Sale Invoice: SAL-00058 - Total Area: 24 sq ft', '2026-09-01 15:51:54'),
(1006, '2026-08-31', 42, 'SALE', 62, 0.00, 18.00, 7158.00, 200.00, 3600.00, 'Sale Invoice: SAL-00058 - Total Area: 18 sq ft', '2026-09-01 15:51:54'),
(1007, '2026-08-31', 43, 'SALE', 62, 0.00, 6.25, 3905.75, 280.00, 1750.00, 'Sale Invoice: SAL-00058 - Total Area: 6.25 sq ft', '2026-09-01 15:51:54'),
(1008, '2026-09-01', 24, 'ADJUSTMENT', 63, 17.50, 0.00, 20557.31, 0.00, 0.00, 'Sale Edit Reversal: SAL-00059', '2026-09-01 15:53:30'),
(1009, '2026-09-01', 25, 'ADJUSTMENT', 63, 3.00, 0.00, 43094.90, 0.00, 0.00, 'Sale Edit Reversal: SAL-00059', '2026-09-01 15:53:30'),
(1010, '2026-09-01', 39, 'ADJUSTMENT', 63, 14.00, 0.00, 5706.25, 0.00, 0.00, 'Sale Edit Reversal: SAL-00059', '2026-09-01 15:53:30'),
(1011, '2026-09-01', 39, 'ADJUSTMENT', 63, 4.00, 0.00, 5710.25, 0.00, 0.00, 'Sale Edit Reversal: SAL-00059', '2026-09-01 15:53:30'),
(1012, '2026-09-01', 39, 'ADJUSTMENT', 63, 4.00, 0.00, 5714.25, 0.00, 0.00, 'Sale Edit Reversal: SAL-00059', '2026-09-01 15:53:30'),
(1013, '2026-08-31', 24, 'SALE', 63, 0.00, 17.50, 20539.81, 440.00, 7700.00, 'Sale Invoice: SAL-00059 - Total Area: 17.5 sq ft', '2026-09-01 15:53:30'),
(1014, '2026-08-31', 25, 'SALE', 63, 0.00, 3.00, 43091.90, 690.00, 2070.00, 'Sale Invoice: SAL-00059 - Total Area: 3 sq ft', '2026-09-01 15:53:30'),
(1015, '2026-08-31', 39, 'SALE', 63, 0.00, 14.00, 5700.25, 450.00, 6300.00, 'Sale Invoice: SAL-00059 - Total Area: 14 sq ft', '2026-09-01 15:53:30'),
(1016, '2026-08-31', 39, 'SALE', 63, 0.00, 4.00, 5696.25, 450.00, 1800.00, 'Sale Invoice: SAL-00059 - Total Area: 4 sq ft', '2026-09-01 15:53:30'),
(1017, '2026-08-31', 39, 'SALE', 63, 0.00, 4.00, 5692.25, 450.00, 1800.00, 'Sale Invoice: SAL-00059 - Total Area: 4 sq ft', '2026-09-01 15:53:30'),
(1018, '2026-09-01', 23, 'ADJUSTMENT', 44, 2.00, 0.00, 54048.14, 0.00, 0.00, 'Sale Edit Reversal: SAL-00040', '2026-09-01 15:55:51'),
(1019, '2026-09-01', 23, 'ADJUSTMENT', 44, 9.00, 0.00, 54057.14, 0.00, 0.00, 'Sale Edit Reversal: SAL-00040', '2026-09-01 15:55:51'),
(1020, '2026-09-01', 23, 'ADJUSTMENT', 44, 4.50, 0.00, 54061.64, 0.00, 0.00, 'Sale Edit Reversal: SAL-00040', '2026-09-01 15:55:51'),
(1021, '2026-09-01', 23, 'ADJUSTMENT', 44, 11.38, 0.00, 54073.02, 0.00, 0.00, 'Sale Edit Reversal: SAL-00040', '2026-09-01 15:55:51'),
(1022, '2026-09-01', 23, 'ADJUSTMENT', 44, 22.00, 0.00, 54095.02, 0.00, 0.00, 'Sale Edit Reversal: SAL-00040', '2026-09-01 15:55:51'),
(1023, '2026-09-01', 44, 'ADJUSTMENT', 44, 48.00, 0.00, 2235.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00040', '2026-09-01 15:55:51'),
(1024, '2026-09-01', 44, 'ADJUSTMENT', 44, 21.00, 0.00, 2256.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00040', '2026-09-01 15:55:51'),
(1025, '2026-09-01', 44, 'ADJUSTMENT', 44, 12.00, 0.00, 2268.00, 0.00, 0.00, 'Sale Edit Reversal: SAL-00040', '2026-09-01 15:55:51'),
(1026, '2026-08-31', 23, 'SALE', 44, 0.00, 2.00, 54093.02, 240.00, 480.00, 'Sale Invoice: SAL-00040 - Total Area: 2 sq ft', '2026-09-01 15:55:51'),
(1027, '2026-08-31', 23, 'SALE', 44, 0.00, 9.00, 54084.02, 240.00, 2160.00, 'Sale Invoice: SAL-00040 - Total Area: 9 sq ft', '2026-09-01 15:55:51'),
(1028, '2026-08-31', 23, 'SALE', 44, 0.00, 4.50, 54079.52, 240.00, 1080.00, 'Sale Invoice: SAL-00040 - Total Area: 4.5 sq ft', '2026-09-01 15:55:51'),
(1029, '2026-08-31', 23, 'SALE', 44, 0.00, 11.38, 54068.14, 240.00, 2731.20, 'Sale Invoice: SAL-00040 - Total Area: 11.38 sq ft', '2026-09-01 15:55:51'),
(1030, '2026-08-31', 23, 'SALE', 44, 0.00, 22.00, 54046.14, 240.00, 5280.00, 'Sale Invoice: SAL-00040 - Total Area: 22 sq ft', '2026-09-01 15:55:51'),
(1031, '2026-08-31', 44, 'SALE', 44, 0.00, 48.00, 2220.00, 300.00, 14400.00, 'Sale Invoice: SAL-00040 - Total Area: 48 sq ft', '2026-09-01 15:55:51'),
(1032, '2026-08-31', 44, 'SALE', 44, 0.00, 21.00, 2199.00, 300.00, 6300.00, 'Sale Invoice: SAL-00040 - Total Area: 21 sq ft', '2026-09-01 15:55:51'),
(1033, '2026-08-31', 44, 'SALE', 44, 0.00, 12.00, 2187.00, 300.00, 3600.00, 'Sale Invoice: SAL-00040 - Total Area: 12 sq ft', '2026-09-01 15:55:51'),
(1034, '2026-09-01', 25, 'ADJUSTMENT', 89, 42.00, 0.00, 43133.90, 0.00, 0.00, 'Sale Edit Reversal: SAL-00085', '2026-09-01 15:57:14'),
(1035, '2026-09-01', 25, 'ADJUSTMENT', 89, 68.00, 0.00, 43201.90, 0.00, 0.00, 'Sale Edit Reversal: SAL-00085', '2026-09-01 15:57:14'),
(1036, '2026-09-01', 25, 'ADJUSTMENT', 89, 93.50, 0.00, 43295.40, 0.00, 0.00, 'Sale Edit Reversal: SAL-00085', '2026-09-01 15:57:14'),
(1037, '2026-09-01', 25, 'ADJUSTMENT', 89, 63.75, 0.00, 43359.15, 0.00, 0.00, 'Sale Edit Reversal: SAL-00085', '2026-09-01 15:57:14'),
(1038, '2026-09-01', 25, 'ADJUSTMENT', 89, 21.25, 0.00, 43380.40, 0.00, 0.00, 'Sale Edit Reversal: SAL-00085', '2026-09-01 15:57:14'),
(1039, '2026-09-01', 25, 'ADJUSTMENT', 89, 8.50, 0.00, 43388.90, 0.00, 0.00, 'Sale Edit Reversal: SAL-00085', '2026-09-01 15:57:14'),
(1040, '2026-09-01', 25, 'ADJUSTMENT', 89, 8.50, 0.00, 43397.40, 0.00, 0.00, 'Sale Edit Reversal: SAL-00085', '2026-09-01 15:57:14'),
(1041, '2026-09-01', 25, 'ADJUSTMENT', 89, 27.63, 0.00, 43425.03, 0.00, 0.00, 'Sale Edit Reversal: SAL-00085', '2026-09-01 15:57:14'),
(1042, '2026-09-01', 25, 'ADJUSTMENT', 89, 36.13, 0.00, 43461.16, 0.00, 0.00, 'Sale Edit Reversal: SAL-00085', '2026-09-01 15:57:14'),
(1043, '2026-09-01', 25, 'SALE', 89, 0.00, 42.00, 43419.16, 680.00, 28560.00, 'Sale Invoice: SAL-00085 - Total Area: 42 sq ft', '2026-09-01 15:57:14'),
(1044, '2026-09-01', 25, 'SALE', 89, 0.00, 68.00, 43351.16, 680.00, 46240.00, 'Sale Invoice: SAL-00085 - Total Area: 68 sq ft', '2026-09-01 15:57:14'),
(1045, '2026-09-01', 25, 'SALE', 89, 0.00, 93.50, 43257.66, 680.00, 63580.00, 'Sale Invoice: SAL-00085 - Total Area: 93.5 sq ft', '2026-09-01 15:57:14'),
(1046, '2026-09-01', 25, 'SALE', 89, 0.00, 63.75, 43193.91, 680.00, 43350.00, 'Sale Invoice: SAL-00085 - Total Area: 63.75 sq ft', '2026-09-01 15:57:14'),
(1047, '2026-09-01', 25, 'SALE', 89, 0.00, 21.25, 43172.66, 680.00, 14450.00, 'Sale Invoice: SAL-00085 - Total Area: 21.25 sq ft', '2026-09-01 15:57:14'),
(1048, '2026-09-01', 25, 'SALE', 89, 0.00, 8.50, 43164.16, 680.00, 5780.00, 'Sale Invoice: SAL-00085 - Total Area: 8.5 sq ft', '2026-09-01 15:57:14'),
(1049, '2026-09-01', 25, 'SALE', 89, 0.00, 8.50, 43155.66, 680.00, 5780.00, 'Sale Invoice: SAL-00085 - Total Area: 8.5 sq ft', '2026-09-01 15:57:14'),
(1050, '2026-09-01', 25, 'SALE', 89, 0.00, 27.63, 43128.03, 680.00, 18788.40, 'Sale Invoice: SAL-00085 - Total Area: 27.63 sq ft', '2026-09-01 15:57:14'),
(1051, '2026-09-01', 25, 'SALE', 89, 0.00, 36.13, 43091.90, 680.00, 24568.40, 'Sale Invoice: SAL-00085 - Total Area: 36.13 sq ft', '2026-09-01 15:57:14'),
(1052, '2026-09-02', 24, 'SALE', 109, 0.00, 96.00, 20443.81, 410.00, 39360.00, 'Sale Invoice: SAL-00103 - Total Area: 96 sq ft', '2026-09-02 06:06:17'),
(1053, '2026-09-02', 24, 'SALE', 110, 0.00, 12.00, 20431.81, 440.00, 5280.00, 'Sale Invoice: SAL-00104 - Total Area: 12 sq ft', '2026-09-02 06:19:19'),
(1054, '2026-09-02', 24, 'SALE', 110, 0.00, 7.50, 20424.31, 440.00, 3300.00, 'Sale Invoice: SAL-00104 - Total Area: 7.5 sq ft', '2026-09-02 06:19:19'),
(1055, '2026-09-02', 23, 'SALE', 111, 0.00, 9.00, 54037.14, 270.00, 2430.00, 'Sale Invoice: SAL-00105 - Total Area: 9 sq ft', '2026-09-02 06:25:36'),
(1056, '2026-09-02', 25, 'SALE', 112, 0.00, 16.00, 43075.90, 750.00, 12000.00, 'Sale Invoice: SAL-00106 - Total Area: 16 sq ft', '2026-09-02 06:30:47'),
(1057, '2026-09-02', 45, 'SALE', 113, 0.00, 8.75, 18914.13, 410.00, 3587.50, 'Sale Invoice: SAL-00107 - Total Area: 8.75 sq ft', '2026-09-02 06:34:32'),
(1058, '2026-09-02', 24, 'SALE', 113, 0.00, 8.25, 20416.06, 440.00, 3630.00, 'Sale Invoice: SAL-00107 - Total Area: 8.25 sq ft', '2026-09-02 06:34:32');

-- --------------------------------------------------------

--
-- Table structure for table `opening_stock`
--

CREATE TABLE `opening_stock` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_size_id` int DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `pieces` decimal(15,2) DEFAULT '0.00',
  `unit_price` decimal(15,2) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `date` date NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `opening_stock`
--

INSERT INTO `opening_stock` (`id`, `product_id`, `product_size_id`, `quantity`, `pieces`, `unit_price`, `total_amount`, `date`, `remarks`, `created_by`, `created_at`) VALUES
(29, 18, 22, 1512.00, 18.00, 447.12, 676045.44, '2026-08-28', 'Opening Stock: 18 pieces of 7 x 12 ft, total 1512 sq ft', 1, '2026-08-28 15:17:32'),
(30, 19, 23, 168.00, 2.00, 447.12, 75116.16, '2026-08-28', 'Opening Stock: 2 pieces of 7 x 12 ft, total 168 sq ft', 1, '2026-08-28 15:19:08'),
(31, 20, 24, 9366.00, 223.00, 190.38, 1783099.08, '2026-08-29', 'Opening Stock: 223 pieces of 6 x 7 ft, total 9366 sq ft', 1, '2026-08-29 14:39:59'),
(32, 21, 25, 1680.00, 20.00, 231.89, 389575.20, '2026-08-29', 'Opening Stock: 20 pieces of 7 x 12 ft, total 1680 sq ft', 1, '2026-08-29 14:42:18'),
(33, 22, 26, 16464.00, 196.00, 247.63, 4076980.32, '2026-08-29', 'Opening Stock: 196 pieces of 7 x 12 ft, total 16464 sq ft', 1, '2026-08-29 14:51:26'),
(34, 22, 27, 3648.00, 38.00, 247.63, 903354.24, '2026-08-29', 'Opening Stock: 38 pieces of 8 x 12 ft, total 3648 sq ft', 1, '2026-08-29 14:51:26'),
(35, 23, 28, 4320.00, 48.00, 265.52, 1147046.40, '2026-08-29', 'Opening Stock: 48 pieces of 7.5 x 12 ft, total 4320 sq ft', 1, '2026-08-29 14:55:59'),
(36, 23, 29, 13920.00, 145.00, 265.52, 3696038.40, '2026-08-29', 'Opening Stock: 145 pieces of 8 x 12 ft, total 13920 sq ft', 1, '2026-08-29 14:56:00'),
(37, 23, 30, 35028.00, 417.00, 265.52, 9300634.56, '2026-08-29', 'Opening Stock: 417 pieces of 7 x 12 ft, total 35028 sq ft', 1, '2026-08-29 14:56:00'),
(38, 23, 31, 1820.00, 26.00, 265.52, 483246.40, '2026-08-29', 'Opening Stock: 26 pieces of 7 x 10 ft, total 1820 sq ft', 1, '2026-08-29 14:56:00'),
(39, 23, 32, 336.00, 8.00, 265.52, 89214.72, '2026-08-29', 'Opening Stock: 8 pieces of 6 x 7 ft, total 336 sq ft', 1, '2026-08-29 14:56:00'),
(40, 23, 33, 96.00, 2.00, 265.52, 25489.92, '2026-08-29', 'Opening Stock: 2 pieces of 6 x 8 ft, total 96 sq ft', 1, '2026-08-29 14:56:00'),
(41, 24, 34, 7560.00, 90.00, 463.06, 3500733.60, '2026-08-29', 'Opening Stock: 90 pieces of 7 x 12 ft, total 7560 sq ft', 1, '2026-08-29 15:01:46'),
(42, 24, 35, 6528.00, 68.00, 463.06, 3022855.68, '2026-08-29', 'Opening Stock: 68 pieces of 8 x 12 ft, total 6528 sq ft', 1, '2026-08-29 15:01:46'),
(43, 24, 36, 4770.00, 53.00, 463.06, 2208796.20, '2026-08-29', 'Opening Stock: 53 pieces of 7.5 x 12 ft, total 4770 sq ft', 1, '2026-08-29 15:01:46'),
(44, 24, 37, 504.00, 12.00, 463.06, 233382.24, '2026-08-29', 'Opening Stock: 12 pieces of 6 x 7 ft, total 504 sq ft', 1, '2026-08-29 15:01:46'),
(45, 24, 38, 1470.00, 42.00, 463.06, 680698.20, '2026-08-29', 'Opening Stock: 42 pieces of 5 x 7 ft, total 1470 sq ft', 1, '2026-08-29 15:01:46'),
(46, 25, 39, 6708.00, 86.00, 745.04, 4997728.32, '2026-08-29', 'Opening Stock: 86 pieces of 6.5 x 12 ft, total 6708 sq ft', 1, '2026-08-29 15:06:29'),
(47, 25, 40, 12684.00, 151.00, 745.04, 9450087.36, '2026-08-29', 'Opening Stock: 151 pieces of 7 x 12 ft, total 12684 sq ft', 1, '2026-08-29 15:06:29'),
(48, 25, 41, 6120.00, 68.00, 745.04, 4559644.80, '2026-08-29', 'Opening Stock: 68 pieces of 7.5 x 12 ft, total 6120 sq ft', 1, '2026-08-29 15:06:29'),
(49, 25, 42, 12096.00, 126.00, 745.04, 9012003.84, '2026-08-29', 'Opening Stock: 126 pieces of 8 x 12 ft, total 12096 sq ft', 1, '2026-08-29 15:06:29'),
(50, 25, 43, 7140.00, 70.00, 745.04, 5319585.60, '2026-08-29', 'Opening Stock: 70 pieces of 8.5 x 12 ft, total 7140 sq ft', 1, '2026-08-29 15:06:29'),
(51, 25, 44, 225.00, 5.00, 745.04, 167634.00, '2026-08-29', 'Opening Stock: 5 pieces of 6 x 7.5 ft, total 225 sq ft', 1, '2026-08-29 15:06:29'),
(52, 26, 45, 5040.00, 60.00, 328.37, 1654984.80, '2026-08-29', 'Opening Stock: 60 pieces of 7 x 12 ft, total 5040 sq ft', 1, '2026-08-29 15:11:28'),
(53, 27, 46, 288.00, 3.00, 948.13, 273061.44, '2026-08-29', 'Opening Stock: 3 pieces of 8 x 12 ft, total 288 sq ft', 1, '2026-08-29 15:13:51'),
(54, 28, 47, 768.00, 8.00, 1055.78, 810839.04, '2026-08-29', 'Opening Stock: 8 pieces of 8 x 12 ft, total 768 sq ft', 1, '2026-08-29 15:15:47'),
(55, 28, 48, 336.00, 8.00, 1055.78, 354742.08, '2026-08-29', 'Opening Stock: 8 pieces of 6 x 7 ft, total 336 sq ft', 1, '2026-08-29 15:15:47'),
(56, 29, 49, 1680.00, 20.00, 1643.78, 2761550.40, '2026-08-29', 'Opening Stock: 20 pieces of 7 x 12 ft, total 1680 sq ft', 1, '2026-08-29 15:17:20'),
(57, 29, 50, 1152.00, 12.00, 1643.78, 1893634.56, '2026-08-29', 'Opening Stock: 12 pieces of 8 x 12 ft, total 1152 sq ft', 1, '2026-08-29 15:17:20'),
(59, 31, 52, 1676.16, 21.00, 345.70, 579448.20, '2026-08-29', 'Opening Stock: 21 pieces of 7.37 x 10.83 ft, total 1676.1591 sq ft', 1, '2026-08-29 15:22:35'),
(60, 32, 53, 2436.00, 29.00, 359.56, 875888.16, '2026-08-29', 'Opening Stock: 29 pieces of 7 x 12 ft, total 2436 sq ft', 1, '2026-08-29 15:24:13'),
(62, 34, 55, 14616.00, 174.00, 345.70, 5052751.20, '2026-08-29', 'Opening Stock: 174 pieces of 7 x 12 ft, total 14616 sq ft', 1, '2026-08-29 15:28:33'),
(63, 34, 56, 3936.00, 41.00, 345.70, 1360675.20, '2026-08-29', 'Opening Stock: 41 pieces of 8 x 12 ft, total 3936 sq ft', 1, '2026-08-29 15:28:33'),
(110, 35, 103, 12348.00, 147.00, 359.56, 4439846.88, '2026-08-29', 'Opening Stock: 147 pieces of 7 x 12 ft, total 12348 sq ft', 1, '2026-08-29 15:43:38'),
(111, 35, 104, 5376.00, 56.00, 359.56, 1932994.56, '2026-08-29', 'Opening Stock: 56 pieces of 8 x 12 ft, total 5376 sq ft', 1, '2026-08-29 15:43:38'),
(112, 36, 105, 84.00, 1.00, 429.57, 36083.88, '2026-08-29', 'Opening Stock: 1 pieces of 7 x 12 ft, total 84 sq ft', 1, '2026-08-29 15:49:27'),
(113, 36, 106, 2880.00, 32.00, 429.57, 1237161.60, '2026-08-29', 'Opening Stock: 32 pieces of 7.5 x 12 ft, total 2880 sq ft', 1, '2026-08-29 15:49:27'),
(114, 36, 107, 8832.00, 92.00, 429.57, 3793962.24, '2026-08-29', 'Opening Stock: 92 pieces of 8 x 12 ft, total 8832 sq ft', 1, '2026-08-29 15:49:27'),
(115, 37, 108, 11520.00, 120.00, 429.57, 4948646.40, '2026-08-29', 'Opening Stock: 120 pieces of 8 x 12 ft, total 11520 sq ft', 1, '2026-08-29 15:52:36'),
(116, 37, 109, 4032.00, 48.00, 429.57, 1732026.24, '2026-08-29', 'Opening Stock: 48 pieces of 7 x 12 ft, total 4032 sq ft', 1, '2026-08-29 15:52:36'),
(117, 38, 110, 7104.00, 74.00, 413.53, 2937717.12, '2026-08-29', 'Opening Stock: 74 pieces of 8 x 12 ft, total 7104 sq ft', 1, '2026-08-29 15:55:01'),
(118, 38, 111, 1764.00, 21.00, 413.53, 729466.92, '2026-08-29', 'Opening Stock: 21 pieces of 7 x 12 ft, total 1764 sq ft', 1, '2026-08-29 15:55:01'),
(119, 39, 112, 5760.00, 60.00, 482.32, 2778163.20, '2026-08-29', 'Opening Stock: 60 pieces of 8 x 12 ft, total 5760 sq ft', 1, '2026-08-29 15:57:07'),
(120, 39, 113, 84.00, 1.00, 482.32, 40514.88, '2026-08-29', 'Opening Stock: 1 pieces of 7 x 12 ft, total 84 sq ft', 1, '2026-08-29 15:57:07'),
(121, 40, 114, 2604.00, 31.00, 429.57, 1118600.28, '2026-08-29', 'Opening Stock: 31 pieces of 7 x 12 ft, total 2604 sq ft', 1, '2026-08-29 15:59:43'),
(122, 41, 115, 2856.00, 34.00, 429.57, 1226851.92, '2026-08-29', 'Opening Stock: 34 pieces of 7 x 12 ft, total 2856 sq ft', 1, '2026-08-29 16:02:53'),
(123, 42, 116, 648.00, 18.00, 231.79, 150199.92, '2026-08-29', 'Opening Stock: 18 pieces of 6 x 6 ft, total 648 sq ft', 1, '2026-08-29 16:06:14'),
(124, 42, 117, 6552.00, 156.00, 231.79, 1518688.08, '2026-08-29', 'Opening Stock: 156 pieces of 6 x 7 ft, total 6552 sq ft', 1, '2026-08-29 16:06:14'),
(125, 43, 118, 468.00, 13.00, 280.86, 131442.48, '2026-08-29', 'Opening Stock: 13 pieces of 6 x 6 ft, total 468 sq ft', 1, '2026-08-29 16:08:06'),
(126, 43, 119, 840.00, 20.00, 280.86, 235922.40, '2026-08-29', 'Opening Stock: 20 pieces of 6 x 7 ft, total 840 sq ft', 1, '2026-08-29 16:08:06'),
(127, 43, 120, 2772.00, 33.00, 280.86, 778543.92, '2026-08-29', 'Opening Stock: 33 pieces of 7 x 12 ft, total 2772 sq ft', 1, '2026-08-29 16:08:06'),
(128, 44, 121, 2268.00, 54.00, 324.17, 735217.56, '2026-08-29', 'Opening Stock: 54 pieces of 6 x 7 ft, total 2268 sq ft', 1, '2026-08-29 16:10:25'),
(129, 45, 122, 14784.00, 176.00, 411.86, 6088938.24, '2026-08-29', 'Opening Stock: 176 pieces of 7 x 12 ft, total 14784 sq ft', 1, '2026-08-29 16:12:58'),
(130, 45, 123, 4992.00, 52.00, 411.86, 2056005.12, '2026-08-29', 'Opening Stock: 52 pieces of 8 x 12 ft, total 4992 sq ft', 1, '2026-08-29 16:12:58'),
(131, 46, 124, 6636.00, 79.00, 396.11, 2628585.96, '2026-08-29', 'Opening Stock: 79 pieces of 7 x 12 ft, total 6636 sq ft', 1, '2026-08-29 16:19:09'),
(132, 46, 125, 600.00, 100.00, 396.11, 237666.00, '2026-08-29', 'Opening Stock: 100 pieces of 2 x 3 ft, total 600 sq ft', 1, '2026-08-29 16:19:09'),
(166, 48, 159, 288.00, 3.00, 1458.66, 420094.08, '2026-08-31', 'Opening Stock: 3 pieces of 8 x 12 ft, total 288 sq ft', 1, '2026-08-31 14:44:30'),
(167, 49, 160, 1680.00, 20.00, 399.91, 671848.80, '2026-08-31', 'Opening Stock: 20 pieces of 7 x 12 ft, total 1680 sq ft', 1, '2026-08-31 14:46:36');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `product_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int NOT NULL,
  `company_id` int NOT NULL,
  `unit_id` int NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT '0.00',
  `sale_price` decimal(15,2) DEFAULT '0.00',
  `min_stock_alert` int DEFAULT '0',
  `location_rack` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
(18, 'FG0008', '6MM Grey looking', 1, 1, 2, 2, 447.12, 450.00, 0, '', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-08-28 15:17:32', '2026-08-28 15:17:32'),
(19, 'FG0009', '6mm brown looking', 1, 1, 2, 2, 447.12, 480.00, 0, '', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-08-28 15:19:08', '2026-08-28 15:19:08'),
(20, 'FG0010', '3mm White', 1, 1, 2, 3, 190.38, 170.00, 210, 'self', 72.00, 84.00, 6.00, 7.00, 42.00, 1, '2026-08-29 14:39:59', '2026-08-29 14:39:59'),
(21, 'FG0011', '4mm White', 1, 1, 2, 3, 231.89, 215.00, 84, 'self', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-08-29 14:42:18', '2026-08-29 14:42:18'),
(22, 'FG0012', '5mm white', 1, 1, 2, 3, 247.63, 225.00, 420, 'self', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-08-29 14:51:26', '2026-08-29 14:51:26'),
(23, 'FG0013', '6mm White', 1, 1, 2, 3, 265.52, 240.00, 1680, 'shop', 90.00, 144.00, 7.50, 12.00, 90.00, 1, '2026-08-29 14:55:59', '2026-08-29 14:55:59'),
(24, 'FG0014', '8mm White', 1, 1, 2, 3, 463.06, 440.00, 840, '', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-08-29 15:01:46', '2026-09-02 06:07:50'),
(25, 'FG0015', '12mm White', 1, 1, 2, 3, 745.04, 690.00, 840, 'self', 78.00, 144.00, 6.50, 12.00, 78.00, 1, '2026-08-29 15:06:29', '2026-09-02 06:07:20'),
(26, 'FG0016', '5mm light Green', 1, 1, 2, 3, 328.37, 300.00, 100, 'shop', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-08-29 15:11:28', '2026-08-29 15:11:28'),
(27, 'FG0017', '8mm Brown', 1, 1, 2, 3, 948.13, 900.00, 50, 'Lahore glass', 96.00, 144.00, 8.00, 12.00, 96.00, 1, '2026-08-29 15:13:51', '2026-08-29 15:13:51'),
(28, 'FG0018', '8mm Grey', 1, 1, 2, 3, 1055.78, 950.00, 100, 'shop', 96.00, 144.00, 8.00, 12.00, 96.00, 1, '2026-08-29 15:15:47', '2026-08-29 15:15:47'),
(29, 'FG0019', '12mm Grey', 1, 1, 2, 3, 1643.78, 1450.00, 100, '', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-08-29 15:17:20', '2026-08-29 15:17:20'),
(31, 'FG0021', '5.5 Green', 1, 1, 2, 3, 345.70, 330.00, 150, '', 88.44, 129.96, 7.37, 10.83, 79.82, 1, '2026-08-29 15:22:35', '2026-08-29 15:22:35'),
(32, 'FG0022', '6mm Blue', 1, 1, 2, 3, 359.56, 330.00, 100, 'shop', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-08-29 15:24:13', '2026-08-29 15:24:13'),
(34, 'FG0024', '5mm Brown', 1, 1, 2, 3, 345.70, 330.00, 100, 'self', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-08-29 15:28:33', '2026-08-29 15:28:33'),
(35, 'FG0025', '6mm Brown', 1, 1, 2, 3, 359.56, 330.00, 100, '', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-08-29 15:43:38', '2026-08-29 15:43:38'),
(36, 'FG0026', '6mm Green Mercury', 1, 1, 2, 3, 429.57, 390.00, 200, 'shop', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-08-29 15:49:27', '2026-08-29 15:49:27'),
(37, 'FG0027', '6mm Brown Mercury', 1, 1, 2, 3, 429.57, 390.00, 200, 'shop', 96.00, 144.00, 8.00, 12.00, 96.00, 1, '2026-08-29 15:52:36', '2026-08-29 15:52:36'),
(38, 'FG0028', '5mm Brown Mercury', 1, 1, 2, 3, 413.53, 370.00, 200, 'self', 96.00, 144.00, 8.00, 12.00, 96.00, 1, '2026-08-29 15:55:01', '2026-08-29 15:55:01'),
(39, 'FG0029', '6mm Grey Mercury', 1, 1, 2, 3, 482.32, 450.00, 100, 'shop', 96.00, 144.00, 8.00, 12.00, 96.00, 1, '2026-08-29 15:57:07', '2026-08-29 15:57:07'),
(40, 'FG0030', '6mm Blue Mercury', 1, 1, 2, 3, 429.57, 390.00, 100, 'shop', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-08-29 15:59:43', '2026-08-29 15:59:43'),
(41, 'FG0031', '6mm Golden Mercury', 1, 1, 2, 3, 429.57, 420.00, 0, 'shop', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-08-29 16:02:53', '2026-08-29 16:02:53'),
(42, 'FG0032', '3mm looking', 1, 2, 2, 4, 231.79, 210.00, 0, 'shop', 72.00, 72.00, 6.00, 6.00, 36.00, 1, '2026-08-29 16:06:14', '2026-08-29 16:06:14'),
(43, 'FG0033', '4mm looking', 1, 2, 2, 4, 280.86, 270.00, 0, 'shop', 72.00, 72.00, 6.00, 6.00, 36.00, 1, '2026-08-29 16:08:06', '2026-08-29 16:08:06'),
(44, 'FG0034', '6mm Blind', 1, 2, 2, 4, 324.17, 300.00, 0, '', 72.00, 84.00, 6.00, 7.00, 42.00, 1, '2026-08-29 16:10:25', '2026-08-29 16:10:25'),
(45, 'FG0035', '6mm Looking', 1, 2, 2, 4, 411.86, 380.00, 400, 'shop', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-08-29 16:12:58', '2026-08-29 16:12:58'),
(46, 'FG0036', '5mm Looking', 1, 2, 2, 4, 396.11, 370.00, 0, '', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-08-29 16:19:09', '2026-08-29 16:19:09'),
(48, 'FG0037', '12mm Brown', 1, 1, 2, 3, 1458.66, 1400.00, 0, '', 96.00, 144.00, 8.00, 12.00, 96.00, 1, '2026-08-31 14:44:30', '2026-08-31 14:44:30'),
(49, 'FG0038', '6mm Grey', 1, 1, 2, 3, 399.91, 370.00, 0, '', 84.00, 144.00, 7.00, 12.00, 84.00, 1, '2026-08-31 14:46:36', '2026-08-31 14:46:36');

-- --------------------------------------------------------

--
-- Table structure for table `product_sizes`
--

CREATE TABLE `product_sizes` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `size_label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `length_inch` decimal(10,2) DEFAULT '0.00',
  `width_inch` decimal(10,2) DEFAULT '0.00',
  `length_feet` decimal(10,2) DEFAULT '0.00',
  `width_feet` decimal(10,2) DEFAULT '0.00',
  `area_sqft` decimal(15,2) DEFAULT '0.00',
  `purchase_rate` decimal(15,2) DEFAULT NULL,
  `sale_rate` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_sizes`
--

INSERT INTO `product_sizes` (`id`, `product_id`, `size_label`, `length_inch`, `width_inch`, `length_feet`, `width_feet`, `area_sqft`, `purchase_rate`, `sale_rate`, `created_at`) VALUES
(22, 18, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 447.12, 450.00, '2026-08-28 15:17:32'),
(23, 19, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 447.12, 480.00, '2026-08-28 15:19:08'),
(24, 20, '6 x 7 ft', 72.00, 84.00, 6.00, 7.00, 42.00, 190.38, 170.00, '2026-08-29 14:39:59'),
(25, 21, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 231.89, 215.00, '2026-08-29 14:42:18'),
(26, 22, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 247.63, 225.00, '2026-08-29 14:51:26'),
(27, 22, '8 x 12 ft', 96.00, 144.00, 8.00, 12.00, 96.00, 247.63, 225.00, '2026-08-29 14:51:26'),
(28, 23, '7.5 x 12 ft', 90.00, 144.00, 7.50, 12.00, 90.00, 265.52, 240.00, '2026-08-29 14:55:59'),
(29, 23, '8 x 12 ft', 96.00, 144.00, 8.00, 12.00, 96.00, 265.52, 240.00, '2026-08-29 14:56:00'),
(30, 23, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 265.52, 240.00, '2026-08-29 14:56:00'),
(31, 23, '7 x 10 ft', 84.00, 120.00, 7.00, 10.00, 70.00, 265.52, 240.00, '2026-08-29 14:56:00'),
(32, 23, '6 x 7 ft', 72.00, 84.00, 6.00, 7.00, 42.00, 265.52, 240.00, '2026-08-29 14:56:00'),
(33, 23, '6 x 8 ft', 72.00, 96.00, 6.00, 8.00, 48.00, 265.52, 240.00, '2026-08-29 14:56:00'),
(34, 24, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 463.06, 420.00, '2026-08-29 15:01:46'),
(35, 24, '8 x 12 ft', 96.00, 144.00, 8.00, 12.00, 96.00, 463.06, 420.00, '2026-08-29 15:01:46'),
(36, 24, '7.5 x 12 ft', 90.00, 144.00, 7.50, 12.00, 90.00, 463.06, 420.00, '2026-08-29 15:01:46'),
(37, 24, '6 x 7 ft', 72.00, 84.00, 6.00, 7.00, 42.00, 463.06, 420.00, '2026-08-29 15:01:46'),
(38, 24, '5 x 7 ft', 60.00, 84.00, 5.00, 7.00, 35.00, 463.06, 420.00, '2026-08-29 15:01:46'),
(39, 25, '6.5 x 12 ft', 78.00, 144.00, 6.50, 12.00, 78.00, 745.04, 660.00, '2026-08-29 15:06:29'),
(40, 25, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 745.04, 660.00, '2026-08-29 15:06:29'),
(41, 25, '7.5 x 12 ft', 90.00, 144.00, 7.50, 12.00, 90.00, 745.04, 660.00, '2026-08-29 15:06:29'),
(42, 25, '8 x 12 ft', 96.00, 144.00, 8.00, 12.00, 96.00, 745.04, 660.00, '2026-08-29 15:06:29'),
(43, 25, '8.5 x 12 ft', 102.00, 144.00, 8.50, 12.00, 102.00, 745.04, 660.00, '2026-08-29 15:06:29'),
(44, 25, '6 x 7.5 ft', 72.00, 90.00, 6.00, 7.50, 45.00, 745.04, 660.00, '2026-08-29 15:06:29'),
(45, 26, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 328.37, 300.00, '2026-08-29 15:11:28'),
(46, 27, '8 x 12 ft', 96.00, 144.00, 8.00, 12.00, 96.00, 948.13, 900.00, '2026-08-29 15:13:51'),
(47, 28, '8 x 12 ft', 96.00, 144.00, 8.00, 12.00, 96.00, 1055.78, 950.00, '2026-08-29 15:15:47'),
(48, 28, '6 x 7 ft', 72.00, 84.00, 6.00, 7.00, 42.00, 1055.78, 950.00, '2026-08-29 15:15:47'),
(49, 29, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 1643.78, 1450.00, '2026-08-29 15:17:20'),
(50, 29, '8 x 12 ft', 96.00, 144.00, 8.00, 12.00, 96.00, 1643.78, 1450.00, '2026-08-29 15:17:20'),
(52, 31, '7.37 x 10.83 ft', 88.44, 129.96, 7.37, 10.83, 79.82, 345.70, 330.00, '2026-08-29 15:22:35'),
(53, 32, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 359.56, 330.00, '2026-08-29 15:24:13'),
(55, 34, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 345.70, 330.00, '2026-08-29 15:28:33'),
(56, 34, '8 x 12 ft', 96.00, 144.00, 8.00, 12.00, 96.00, 345.70, 330.00, '2026-08-29 15:28:33'),
(103, 35, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 359.56, 330.00, '2026-08-29 15:43:38'),
(104, 35, '8 x 12 ft', 96.00, 144.00, 8.00, 12.00, 96.00, 359.56, 330.00, '2026-08-29 15:43:38'),
(105, 36, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 429.57, 390.00, '2026-08-29 15:49:27'),
(106, 36, '7.5 x 12 ft', 90.00, 144.00, 7.50, 12.00, 90.00, 429.57, 390.00, '2026-08-29 15:49:27'),
(107, 36, '8 x 12 ft', 96.00, 144.00, 8.00, 12.00, 96.00, 429.57, 390.00, '2026-08-29 15:49:27'),
(108, 37, '8 x 12 ft', 96.00, 144.00, 8.00, 12.00, 96.00, 429.57, 390.00, '2026-08-29 15:52:36'),
(109, 37, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 429.57, 390.00, '2026-08-29 15:52:36'),
(110, 38, '8 x 12 ft', 96.00, 144.00, 8.00, 12.00, 96.00, 413.53, 370.00, '2026-08-29 15:55:01'),
(111, 38, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 413.53, 370.00, '2026-08-29 15:55:01'),
(112, 39, '8 x 12 ft', 96.00, 144.00, 8.00, 12.00, 96.00, 482.32, 450.00, '2026-08-29 15:57:07'),
(113, 39, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 482.32, 450.00, '2026-08-29 15:57:07'),
(114, 40, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 429.57, 390.00, '2026-08-29 15:59:43'),
(115, 41, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 429.57, 420.00, '2026-08-29 16:02:53'),
(116, 42, '6 x 6 ft', 72.00, 72.00, 6.00, 6.00, 36.00, 231.79, 210.00, '2026-08-29 16:06:14'),
(117, 42, '6 x 7 ft', 72.00, 84.00, 6.00, 7.00, 42.00, 231.79, 210.00, '2026-08-29 16:06:14'),
(118, 43, '6 x 6 ft', 72.00, 72.00, 6.00, 6.00, 36.00, 280.86, 270.00, '2026-08-29 16:08:06'),
(119, 43, '6 x 7 ft', 72.00, 84.00, 6.00, 7.00, 42.00, 280.86, 270.00, '2026-08-29 16:08:06'),
(120, 43, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 280.86, 270.00, '2026-08-29 16:08:06'),
(121, 44, '6 x 7 ft', 72.00, 84.00, 6.00, 7.00, 42.00, 324.17, 300.00, '2026-08-29 16:10:25'),
(122, 45, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 411.86, 380.00, '2026-08-29 16:12:58'),
(123, 45, '8 x 12 ft', 96.00, 144.00, 8.00, 12.00, 96.00, 411.86, 380.00, '2026-08-29 16:12:58'),
(124, 46, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 396.11, 370.00, '2026-08-29 16:19:09'),
(125, 46, '2 x 3 ft', 24.00, 36.00, 2.00, 3.00, 6.00, 396.11, 370.00, '2026-08-29 16:19:09'),
(159, 48, '8 x 12 ft', 96.00, 144.00, 8.00, 12.00, 96.00, 1458.66, 1400.00, '2026-08-31 14:44:30'),
(160, 49, '7 x 12 ft', 84.00, 144.00, 7.00, 12.00, 84.00, 399.91, 370.00, '2026-08-31 14:46:36');

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
  `std_height` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `std_width` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uom` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Inch',
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
(17, 11, 3, 12.00, 14.00, '12', '18', 'Inch', 1.00, 350.00, 0.00, 1.50, 525.00, 0.00, 0.00, 525.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_master`
--

CREATE TABLE `purchase_master` (
  `id` int NOT NULL,
  `invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchase_date` date NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `other_charges` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `paid_amount` decimal(15,2) DEFAULT '0.00',
  `remaining_amount` decimal(15,2) DEFAULT '0.00',
  `payment_type` enum('cash','bank','credit') COLLATE utf8mb4_unicode_ci DEFAULT 'credit',
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `refund_status` enum('none','partial','full') COLLATE utf8mb4_unicode_ci DEFAULT 'none',
  `refund_amount` decimal(15,2) DEFAULT '0.00',
  `refund_date` date DEFAULT NULL,
  `refund_reason` text COLLATE utf8mb4_unicode_ci
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
(11, 'PUR-00011', '2026-07-30', 2, 525.00, 0.00, 0.00, 0.00, 525.00, 0.00, 525.00, 'credit', 0, '4789641', 'abc', 1, 1, '2026-07-30 11:09:20', 'none', 0.00, NULL, NULL);

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
  `client_size` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `multiple_of` int DEFAULT '6',
  `std_height` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `std_width` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uom` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Inch',
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `area` decimal(15,2) DEFAULT '0.00',
  `rate` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00'
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
(15, 16, 3, 6.00, 6.00, NULL, 6, '0.5', '0.5', 'Inch', 1.00, 400.00, 0.25, 0.00, 100.00, 0.00, 0.00, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `quotation_master`
--

CREATE TABLE `quotation_master` (
  `id` int NOT NULL,
  `quotation_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quotation_date` date NOT NULL,
  `customer_id` int NOT NULL,
  `valid_until` date DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `other_charges` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `received_amount` decimal(15,2) DEFAULT '0.00',
  `remaining_amount` decimal(15,2) DEFAULT '0.00',
  `payment_type` enum('cash','bank','credit','partial') COLLATE utf8mb4_unicode_ci DEFAULT 'credit',
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','hold','pending','approved','rejected','converted') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotation_master`
--

INSERT INTO `quotation_master` (`id`, `quotation_no`, `quotation_date`, `customer_id`, `valid_until`, `subtotal`, `discount_percentage`, `discount_amount`, `other_charges`, `grand_total`, `received_amount`, `remaining_amount`, `payment_type`, `bank_account_id`, `reference_no`, `remarks`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(5, 'QTN-00002', '2026-06-12', 11, '2026-07-12', 8800.00, 0.00, 0.00, 0.00, 8800.00, 0.00, 0.00, 'credit', NULL, '', '', 'draft', 1, '2026-06-12 07:42:18', '2026-06-12 07:42:18'),
(7, 'QTN-00004', '2026-07-26', 1, '2026-08-25', 1500.00, 0.00, 0.00, 0.00, 1500.00, 0.00, 0.00, 'credit', NULL, '', '', 'draft', 1, '2026-07-26 18:19:06', '2026-07-26 18:19:06'),
(8, 'QTN-00005', '2026-07-30', 17, '2026-08-29', 24000.00, 0.00, 0.00, 0.00, 24000.00, 0.00, 0.00, 'credit', NULL, '', '', 'draft', 1, '2026-07-30 10:40:37', '2026-07-30 10:40:37'),
(12, 'QTN-00006', '2026-08-11', 1, '2026-09-10', 100.00, 0.00, 0.00, 0.00, 100.00, 0.00, 0.00, 'credit', NULL, 'TEST-HOLD', 'hold roundtrip test', 'draft', 1, '2026-08-11 08:10:14', '2026-08-11 08:10:14'),
(14, 'QTN-00007', '2026-08-12', 14, '2026-09-11', 283.70, 0.00, 0.00, 0.00, 283.70, 0.00, 0.00, 'credit', NULL, '4789641', '', 'draft', 1, '2026-08-12 06:39:56', '2026-08-12 06:39:56'),
(15, 'QTN-00008', '2026-08-20', 8, '2026-09-19', 44.00, 0.00, 0.00, 0.00, 44.00, 0.00, 0.00, 'credit', NULL, '7777', '', 'draft', 1, '2026-08-20 10:09:26', '2026-08-20 10:09:26'),
(16, 'QTN-00009', '2026-08-20', 8, '2026-09-19', 100.00, 0.00, 0.00, 0.00, 100.00, 0.00, 0.00, 'credit', NULL, '07777', '', 'draft', 1, '2026-08-20 10:32:41', '2026-08-20 10:32:41');

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
  `client_size` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `multiple_of` int DEFAULT '6',
  `std_height` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `std_width` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uom` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Inch',
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
(227, 37, 18, 6.30, 84.00, '6.3 x 84', 3, '9', '84', '', 1.00, 5.25, 0.00, 0.00, 590.00, 0.00, 3097.50, 0.00, 0.00),
(228, 37, 18, 6.10, 84.00, '6.1 x 84', 3, '9', '84', '', 1.00, 5.25, 0.00, 0.00, 590.00, 0.00, 3097.50, 0.00, 0.00),
(229, 37, 18, 6.00, 78.10, '6 x 78.1', 6, '6', '84', '', 1.00, 3.50, 0.00, 0.00, 590.00, 0.00, 2065.00, 0.00, 0.00),
(230, 37, 19, 24.00, 66.00, '24 x 66', 12, '24', '72', '', 2.00, 24.00, 0.00, 0.00, 510.00, 0.00, 12240.00, 0.00, 0.00),
(231, 38, 4, 84.00, 144.00, '84 x 144', 6, '84', '144', '', 2.00, 168.00, 0.00, 0.00, 240.00, 0.00, 40320.00, 0.00, 0.00),
(232, 38, 4, 84.00, 114.00, '84 x 114', 6, '84', '114', '', 1.00, 66.50, 0.00, 0.00, 240.00, 0.00, 15960.00, 0.00, 0.00),
(233, 38, 4, 66.00, 84.00, '66 x 84', 6, '66', '84', '', 1.00, 38.50, 0.00, 0.00, 240.00, 0.00, 9240.00, 0.00, 0.00),
(234, 38, 4, 84.00, 96.00, '84 x 96', 6, '84', '96', '', 1.00, 56.00, 0.00, 0.00, 240.00, 0.00, 13440.00, 0.00, 0.00),
(235, 38, 4, 18.00, 51.00, '18 x 51', 6, '18', '54', '', 1.00, 6.75, 0.00, 0.00, 240.00, 0.00, 1620.00, 0.00, 0.00),
(236, 38, 4, 48.00, 90.00, '48 x 90', 6, '48', '90', '', 1.00, 30.00, 0.00, 0.00, 240.00, 0.00, 7200.00, 0.00, 0.00),
(237, 38, 4, 12.00, 66.00, '12 x 66', 6, '12', '66', '', 1.00, 5.50, 0.00, 0.00, 240.00, 0.00, 1320.00, 0.00, 0.00),
(238, 38, 4, 15.00, 21.00, '15 x 21', 6, '18', '24', '', 1.00, 3.00, 0.00, 0.00, 240.00, 0.00, 720.00, 0.00, 0.00),
(239, 38, 16, 21.00, 84.00, '21 x 84', 3, '21', '84', '', 2.00, 24.50, 0.00, 0.00, 360.00, 0.00, 8820.00, 0.00, 0.00),
(240, 38, 16, 24.00, 84.00, '24 x 84', 6, '24', '84', '', 2.00, 28.00, 0.00, 0.00, 360.00, 0.00, 10080.00, 0.00, 0.00),
(241, 39, 36, 66.00, 26.70, '66 x 26.7', 3, '66', '27', '', 2.00, 24.75, 0.00, 0.00, 390.00, 0.00, 9652.50, 0.00, 0.00),
(242, 39, 36, 66.00, 15.00, '66 x 15', 3, '66', '15', '', 2.00, 13.75, 0.00, 0.00, 390.00, 0.00, 5362.50, 0.00, 0.00),
(243, 39, 36, 42.00, 11.70, '42 x 11.7', 6, '42', '12', '', 2.00, 7.00, 0.00, 0.00, 390.00, 0.00, 2730.00, 0.00, 0.00),
(244, 39, 36, 16.00, 60.00, '16 x 60', 6, '18', '60', '', 2.00, 15.00, 0.00, 0.00, 390.00, 0.00, 5850.00, 0.00, 0.00),
(245, 39, 36, 60.00, 27.00, '60 x 27', 6, '60', '30', '', 2.00, 25.00, 0.00, 0.00, 390.00, 0.00, 9750.00, 0.00, 0.00),
(246, 39, 36, 17.00, 9.20, '17 x 9.2', 6, '18', '12', '', 2.00, 3.00, 0.00, 0.00, 390.00, 0.00, 1170.00, 0.00, 0.00),
(247, 39, 36, 42.00, 11.70, '42 x 11.7', 6, '42', '12', '', 2.00, 7.00, 0.00, 0.00, 390.00, 0.00, 2730.00, 0.00, 0.00),
(248, 39, 23, 39.30, 72.00, '39.3 x 72', 6, '42', '72', '', 2.00, 42.00, 0.00, 0.00, 240.00, 0.00, 10080.00, 0.00, 0.00),
(249, 39, 23, 28.10, 78.00, '28.1 x 78', 6, '30', '78', '', 1.00, 16.25, 0.00, 0.00, 240.00, 0.00, 3900.00, 0.00, 0.00),
(250, 39, 23, 16.20, 78.00, '16.2 x 78', 6, '18', '78', '', 1.00, 9.75, 0.00, 0.00, 240.00, 0.00, 2340.00, 0.00, 0.00),
(251, 39, 23, 60.00, 78.00, '60 x 78', 6, '60', '78', '', 3.00, 97.50, 0.00, 0.00, 240.00, 0.00, 23400.00, 0.00, 0.00),
(252, 39, 23, 48.00, 78.00, '48 x 78', 6, '48', '78', '', 1.00, 26.00, 0.00, 0.00, 240.00, 0.00, 6240.00, 0.00, 0.00),
(253, 40, 35, 5.70, 10.40, '5.7 x 10.4', 6, '6', '12', '', 2.00, 1.00, 0.00, 0.00, 330.00, 0.00, 330.00, 0.00, 0.00),
(254, 40, 35, 21.00, 51.20, '21 x 51.2', 6, '24', '54', '', 2.00, 18.00, 0.00, 0.00, 330.00, 0.00, 5940.00, 0.00, 0.00),
(255, 40, 35, 28.60, 60.50, '28.6 x 60.5', 6, '30', '66', '', 2.00, 27.50, 0.00, 0.00, 330.00, 0.00, 9075.00, 0.00, 0.00),
(256, 40, 35, 46.00, 45.00, '46 x 45', 6, '48', '48', '', 1.00, 16.00, 0.00, 0.00, 330.00, 0.00, 5280.00, 0.00, 0.00),
(257, 40, 35, 9.00, 18.00, '9 x 18', 6, '12', '18', '', 2.00, 3.00, 0.00, 0.00, 330.00, 0.00, 990.00, 0.00, 0.00),
(258, 40, 35, 15.20, 14.60, '15.2 x 14.6', 6, '18', '18', '', 2.00, 4.50, 0.00, 0.00, 330.00, 0.00, 1485.00, 0.00, 0.00),
(259, 40, 35, 15.40, 15.60, '15.4 x 15.6', 6, '18', '18', '', 2.00, 4.50, 0.00, 0.00, 330.00, 0.00, 1485.00, 0.00, 0.00),
(260, 40, 35, 21.40, 42.60, '21.4 x 42.6', 6, '24', '48', '', 2.00, 16.00, 0.00, 0.00, 330.00, 0.00, 5280.00, 0.00, 0.00),
(261, 40, 35, 21.30, 41.40, '21.3 x 41.4', 6, '24', '42', '', 2.00, 14.00, 0.00, 0.00, 330.00, 0.00, 4620.00, 0.00, 0.00),
(262, 40, 35, 27.10, 71.60, '27.1 x 71.6', 6, '30', '72', '', 2.00, 30.00, 0.00, 0.00, 330.00, 0.00, 9900.00, 0.00, 0.00),
(263, 40, 35, 5.20, 12.00, '5.2 x 12', 6, '6', '12', '', 2.00, 1.00, 0.00, 0.00, 330.00, 0.00, 330.00, 0.00, 0.00),
(264, 40, 35, 48.00, 78.00, '48 x 78', 6, '48', '78', '', 1.00, 26.00, 0.00, 0.00, 330.00, 0.00, 8580.00, 0.00, 0.00),
(265, 40, 35, 36.00, 78.00, '36 x 78', 6, '36', '78', '', 1.00, 19.50, 0.00, 0.00, 330.00, 0.00, 6435.00, 0.00, 0.00),
(266, 40, 35, 32.00, 78.00, '32 x 78', 6, '36', '78', '', 1.00, 19.50, 0.00, 0.00, 330.00, 0.00, 6435.00, 0.00, 0.00),
(267, 40, 35, 8.40, 11.20, '8.4 x 11.2', 6, '12', '12', '', 2.00, 2.00, 0.00, 0.00, 330.00, 0.00, 660.00, 0.00, 0.00),
(268, 40, 35, 16.00, 60.40, '16 x 60.4', 6, '18', '66', '', 2.00, 16.50, 0.00, 0.00, 330.00, 0.00, 5445.00, 0.00, 0.00),
(269, 40, 35, 9.30, 12.40, '9.3 x 12.4', 6, '12', '18', '', 2.00, 3.00, 0.00, 0.00, 330.00, 0.00, 990.00, 0.00, 0.00),
(270, 40, 35, 20.30, 60.00, '20.3 x 60', 6, '24', '60', '', 2.00, 20.00, 0.00, 0.00, 330.00, 0.00, 6600.00, 0.00, 0.00),
(271, 40, 35, 15.60, 15.40, '15.6 x 15.4', 6, '18', '18', '', 2.00, 4.50, 0.00, 0.00, 330.00, 0.00, 1485.00, 0.00, 0.00),
(272, 40, 35, 27.40, 72.30, '27.4 x 72.3', 6, '30', '78', '', 2.00, 32.50, 0.00, 0.00, 330.00, 0.00, 10725.00, 0.00, 0.00),
(273, 40, 35, 29.20, 59.30, '29.2 x 59.3', 6, '30', '60', '', 2.00, 25.00, 0.00, 0.00, 330.00, 0.00, 8250.00, 0.00, 0.00),
(274, 40, 35, 20.30, 42.00, '20.3 x 42', 6, '24', '42', '', 2.00, 14.00, 0.00, 0.00, 330.00, 0.00, 4620.00, 0.00, 0.00),
(275, 40, 35, 32.00, 78.00, '32 x 78', 6, '36', '78', '', 1.00, 19.50, 0.00, 0.00, 330.00, 0.00, 6435.00, 0.00, 0.00),
(276, 40, 35, 27.00, 73.40, '27 x 73.4', 6, '30', '78', '', 2.00, 32.50, 0.00, 0.00, 330.00, 0.00, 10725.00, 0.00, 0.00),
(277, 40, 35, 27.10, 73.30, '27.1 x 73.3', 6, '30', '78', '', 2.00, 32.50, 0.00, 0.00, 330.00, 0.00, 10725.00, 0.00, 0.00),
(278, 40, 35, 44.00, 78.00, '44 x 78', 6, '48', '78', '', 1.00, 26.00, 0.00, 0.00, 330.00, 0.00, 8580.00, 0.00, 0.00),
(279, 40, 35, 36.00, 78.00, '36 x 78', 6, '36', '78', '', 1.00, 19.50, 0.00, 0.00, 330.00, 0.00, 6435.00, 0.00, 0.00),
(290, 42, 27, 18.00, 24.00, '18.00 x 24.00', 6, '18', '24', '', 1.00, 3.00, 0.00, 0.00, 950.00, 0.00, 2850.00, 0.00, 0.00),
(291, 43, 28, 12.00, 36.00, '12 x 36', 6, '12', '36', '', 1.00, 3.00, 0.00, 0.00, 950.00, 0.00, 2850.00, 0.00, 0.00),
(300, 45, 40, 84.00, 144.00, '84 x 144', 6, '84', '144', '', 2.00, 168.00, 0.00, 0.00, 390.00, 0.00, 65520.00, 0.00, 0.00),
(301, 45, 40, 84.00, 120.00, '84 x 120', 6, '84', '120', '', 1.00, 70.00, 0.00, 0.00, 390.00, 0.00, 27300.00, 0.00, 0.00),
(302, 45, 40, 42.00, 84.00, '42 x 84', 6, '42', '84', '', 1.00, 24.50, 0.00, 0.00, 390.00, 0.00, 9555.00, 0.00, 0.00),
(303, 45, 40, 21.00, 42.00, '21 x 42', 3, '21', '42', '', 2.00, 12.25, 0.00, 0.00, 390.00, 0.00, 4777.50, 0.00, 0.00),
(304, 45, 40, 27.10, 54.00, '27.1 x 54', 6, '30', '54', '', 1.00, 11.25, 0.00, 0.00, 390.00, 0.00, 4387.50, 0.00, 0.00),
(305, 45, 40, 24.00, 48.00, '24 x 48', 6, '24', '48', '', 1.00, 8.00, 0.00, 0.00, 390.00, 0.00, 3120.00, 0.00, 0.00),
(306, 45, 40, 21.00, 39.00, '21 x 39', 6, '24', '42', '', 1.00, 7.00, 0.00, 0.00, 390.00, 0.00, 2730.00, 0.00, 0.00),
(307, 46, 38, 21.50, 41.00, '21.5 x 41', 6, '24', '42', '', 2.00, 14.00, 0.00, 0.00, 380.00, 0.00, 5320.00, 0.00, 0.00),
(308, 46, 38, 18.00, 19.00, '18 x 19', 6, '18', '24', '', 1.00, 3.00, 0.00, 0.00, 380.00, 0.00, 1140.00, 0.00, 0.00),
(320, 48, 25, 12.00, 114.00, '12 x 114', 6, '12', '114', '', 1.00, 9.50, 0.00, 0.00, 690.00, 0.00, 6555.00, 0.00, 0.00),
(321, 49, 24, 13.40, 39.00, '13.4 x 39', 6, '18', '42', '', 1.00, 5.25, 0.00, 0.00, 880.00, 0.00, 4620.00, 0.00, 0.00),
(322, 49, 24, 8.40, 11.40, '8.4 x 11.4', 6, '12', '12', '', 1.00, 1.00, 0.00, 0.00, 880.00, 0.00, 880.00, 0.00, 0.00),
(323, 50, 25, 48.00, 62.20, '48 x 62.2', 6, '48', '66', '', 1.00, 22.00, 0.00, 0.00, 690.00, 0.00, 15180.00, 0.00, 0.00),
(324, 50, 25, 48.00, 66.00, '48 x 66', 6, '48', '66', '', 2.00, 44.00, 0.00, 0.00, 690.00, 0.00, 30360.00, 0.00, 0.00),
(325, 51, 45, 29.00, 25.00, '29 x 25', 6, '30', '30', '', 1.00, 6.25, 0.00, 0.00, 440.00, 0.00, 2750.00, 0.00, 0.00),
(326, 52, 24, 27.00, 45.00, '27 x 45', 3, '27', '45', '', 1.00, 8.44, 0.00, 0.00, 480.00, 0.00, 4050.00, 0.00, 0.00),
(327, 52, 25, 24.00, 45.00, '24 x 45', 3, '24', '45', '', 1.00, 7.50, 0.00, 0.00, 700.00, 0.00, 5250.00, 0.00, 0.00),
(328, 41, 45, 19.60, 120.00, '19.60 x 120.00', 3, '21', '120', '', 1.00, 17.50, 0.00, 0.00, 370.00, 0.00, 6475.00, 0.00, 0.00),
(329, 41, 45, 31.20, 120.00, '31.20 x 120.00', 3, '33', '120', '', 1.00, 27.50, 0.00, 0.00, 370.00, 0.00, 10175.00, 0.00, 0.00),
(330, 41, 45, 31.60, 48.00, '31.60 x 48.00', 6, '36', '48', '', 1.00, 12.00, 0.00, 0.00, 370.00, 0.00, 4440.00, 0.00, 0.00),
(331, 41, 45, 32.70, 120.00, '32.70 x 120.00', 6, '36', '120', '', 1.00, 30.00, 0.00, 0.00, 370.00, 0.00, 11100.00, 0.00, 0.00),
(332, 41, 45, 81.70, 113.30, '81.70 x 113.30', 6, '84', '114', '', 1.00, 66.50, 0.00, 0.00, 370.00, 0.00, 24605.00, 0.00, 0.00),
(333, 41, 45, 80.30, 113.30, '80.30 x 113.30', 6, '84', '114', '', 1.00, 66.50, 0.00, 0.00, 370.00, 0.00, 24605.00, 0.00, 0.00),
(334, 41, 45, 79.50, 113.30, '79.50 x 113.30', 6, '84', '114', '', 1.00, 66.50, 0.00, 0.00, 370.00, 0.00, 24605.00, 0.00, 0.00),
(335, 41, 45, 96.00, 113.30, '96.00 x 113.30', 6, '96', '114', '', 1.00, 76.00, 0.00, 0.00, 370.00, 0.00, 28120.00, 0.00, 0.00),
(336, 41, 45, 66.00, 120.00, '66.00 x 120.00', 6, '66', '120', '', 1.00, 55.00, 0.00, 0.00, 370.00, 0.00, 20350.00, 0.00, 0.00),
(337, 53, 23, 69.00, 96.00, '69 x 96', 3, '69', '96', '', 1.00, 46.00, 0.00, 0.00, 235.00, 0.00, 10810.00, 0.00, 0.00),
(338, 53, 23, 96.00, 144.00, '96 x 144', 6, '96', '144', '', 1.00, 96.00, 0.00, 0.00, 235.00, 0.00, 22560.00, 0.00, 0.00),
(339, 53, 23, 18.00, 84.00, '18 x 84', 6, '18', '84', '', 18.00, 189.00, 0.00, 0.00, 235.00, 0.00, 44415.00, 0.00, 0.00),
(340, 53, 37, 96.00, 96.00, '96 x 96', 6, '96', '96', '', 1.00, 64.00, 0.00, 0.00, 390.00, 0.00, 24960.00, 0.00, 0.00),
(341, 53, 37, 96.00, 144.00, '96 x 144', 6, '96', '144', '', 1.00, 96.00, 0.00, 0.00, 390.00, 0.00, 37440.00, 0.00, 0.00),
(342, 53, 37, 17.30, 17.40, '17.3 x 17.4', 6, '18', '18', '', 1.00, 2.25, 0.00, 0.00, 390.00, 0.00, 877.50, 0.00, 0.00),
(343, 53, 37, 9.30, 34.20, '9.3 x 34.2', 6, '12', '36', '', 1.00, 3.00, 0.00, 0.00, 390.00, 0.00, 1170.00, 0.00, 0.00),
(344, 54, 24, 35.30, 47.30, '35.3 x 47.3', 6, '36', '48', '', 1.00, 12.00, 0.00, 0.00, 440.00, 0.00, 5280.00, 0.00, 0.00),
(345, 54, 24, 23.00, 26.00, '23 x 26', 3, '24', '27', '', 2.00, 9.00, 0.00, 0.00, 440.00, 0.00, 3960.00, 0.00, 0.00),
(346, 54, 24, 30.00, 50.00, '30 x 50', 6, '30', '54', '', 1.00, 11.25, 0.00, 0.00, 440.00, 0.00, 4950.00, 0.00, 0.00),
(347, 54, 24, 15.60, 23.70, '15.6 x 23.7', 6, '18', '24', '', 1.00, 3.00, 0.00, 0.00, 440.00, 0.00, 1320.00, 0.00, 0.00),
(348, 54, 24, 15.00, 58.60, '15 x 58.6', 3, '15', '60', '', 1.00, 6.25, 0.00, 0.00, 440.00, 0.00, 2750.00, 0.00, 0.00),
(349, 54, 24, 17.20, 44.30, '17.2 x 44.3', 6, '18', '48', '', 1.00, 6.00, 0.00, 0.00, 440.00, 0.00, 2640.00, 0.00, 0.00),
(350, 54, 24, 17.00, 60.00, '17 x 60', 6, '18', '60', '', 1.00, 7.50, 0.00, 0.00, 420.00, 0.00, 3150.00, 0.00, 0.00),
(351, 54, 24, 30.00, 48.00, '30 x 48', 6, '30', '48', '', 1.00, 10.00, 0.00, 0.00, 420.00, 0.00, 4200.00, 0.00, 0.00),
(352, 55, 39, 17.30, 59.20, '17.3 x 59.2', 6, '18', '60', '', 1.00, 7.50, 0.00, 0.00, 450.00, 0.00, 3375.00, 0.00, 0.00),
(353, 55, 39, 17.60, 19.40, '17.6 x 19.4', 3, '18', '21', '', 2.00, 5.25, 0.00, 0.00, 450.00, 0.00, 2362.50, 0.00, 0.00),
(354, 56, 23, 60.00, 78.00, '60 x 78', 12, '60', '84', '', 1.00, 35.00, 0.00, 0.00, 240.00, 0.00, 8400.00, 0.00, 0.00),
(363, 59, 29, 30.00, 132.00, '30 x 132', 6, '30', '132', '', 1.00, 27.50, 0.00, 0.00, 1500.00, 0.00, 41250.00, 0.00, 0.00),
(364, 59, 29, 5.20, 30.00, '5.2 x 30', 6, '6', '30', '', 1.00, 1.25, 0.00, 0.00, 1500.00, 0.00, 1875.00, 0.00, 0.00),
(365, 59, 29, 19.40, 30.00, '19.4 x 30', 3, '21', '30', '', 1.00, 4.38, 0.00, 0.00, 1500.00, 0.00, 6562.50, 0.00, 0.00),
(366, 59, 29, 25.30, 30.00, '25.3 x 30', 3, '27', '30', '', 1.00, 5.63, 0.00, 0.00, 1500.00, 0.00, 8437.50, 0.00, 0.00),
(367, 59, 29, 6.00, 30.00, '6 x 30', 6, '6', '30', '', 1.00, 1.25, 0.00, 0.00, 1500.00, 0.00, 1875.00, 0.00, 0.00),
(368, 60, 37, 66.00, 72.00, '66 x 72', 6, '66', '72', '', 1.00, 33.00, 0.00, 0.00, 390.00, 0.00, 12870.00, 0.00, 0.00),
(369, 60, 37, 21.50, 72.00, '21.5 x 72', 6, '24', '72', '', 1.00, 12.00, 0.00, 0.00, 390.00, 0.00, 4680.00, 0.00, 0.00),
(370, 60, 37, 18.00, 90.00, '18 x 90', 6, '18', '90', '', 1.00, 11.25, 0.00, 0.00, 390.00, 0.00, 4387.50, 0.00, 0.00),
(371, 60, 37, 45.00, 48.00, '45 x 48', 6, '48', '48', '', 1.00, 16.00, 0.00, 0.00, 390.00, 0.00, 6240.00, 0.00, 0.00),
(372, 61, 22, 36.00, 48.00, '36 x 48', 6, '36', '48', '', 1.00, 12.00, 0.00, 0.00, 230.00, 0.00, 2760.00, 0.00, 0.00),
(382, 65, 35, 96.00, 144.00, '96 x 144', 6, '96', '144', '', 2.00, 192.00, 0.00, 0.00, 330.00, 0.00, 63360.00, 0.00, 0.00),
(383, 65, 35, 26.50, 62.50, '26.5 x 62.5', 6, '30', '66', '', 1.00, 13.75, 0.00, 0.00, 330.00, 0.00, 4537.50, 0.00, 0.00),
(384, 65, 35, 26.10, 69.70, '26.1 x 69.7', 3, '27', '72', '', 2.00, 27.00, 0.00, 0.00, 330.00, 0.00, 8910.00, 0.00, 0.00),
(391, 64, 45, 37.60, 56.00, '37.60 x 56.00', 6, '42', '60', '', 1.00, 17.50, 0.00, 0.00, 650.00, 0.00, 11375.00, 0.00, 0.00),
(392, 67, 25, 72.00, 80.20, '72.00 x 80.20', 6, '72', '84', '', 1.00, 42.00, 0.00, 0.00, 690.00, 0.00, 28980.00, 0.00, 0.00),
(393, 67, 25, 11.60, 79.00, '11.60 x 79.00', 6, '12', '84', '', 1.00, 7.00, 0.00, 0.00, 690.00, 0.00, 4830.00, 0.00, 0.00),
(394, 67, 25, 31.50, 28.10, '31.50 x 28.10', 3, '33', '30', '', 2.00, 13.75, 0.00, 0.00, 690.00, 0.00, 9487.50, 0.00, 0.00),
(396, 69, 23, 45.00, 48.00, '45 x 48', 6, '48', '48', '', 1.00, 16.00, 0.00, 0.00, 240.00, 0.00, 3840.00, 0.00, 0.00),
(397, 69, 23, 16.00, 42.00, '16 x 42', 6, '18', '42', '', 1.00, 5.25, 0.00, 0.00, 240.00, 0.00, 1260.00, 0.00, 0.00),
(398, 58, 25, 22.00, 60.00, '22.00 x 60.00', 6, '24', '60', '', 2.00, 20.00, 0.00, 0.00, 690.00, 0.00, 13800.00, 0.00, 0.00),
(399, 58, 25, 22.00, 48.00, '22.00 x 48.00', 6, '24', '48', '', 6.00, 48.00, 0.00, 0.00, 690.00, 0.00, 33120.00, 0.00, 0.00),
(400, 58, 25, 26.00, 61.00, '26.00 x 61.00', 6, '30', '66', '', 1.00, 13.75, 0.00, 0.00, 690.00, 0.00, 9487.50, 0.00, 0.00),
(401, 58, 25, 26.00, 54.00, '26.00 x 54.00', 6, '30', '54', '', 1.00, 11.25, 0.00, 0.00, 690.00, 0.00, 7762.50, 0.00, 0.00),
(402, 70, 25, 51.00, 102.00, '51 x 102', 3, '51', '102', '', 6.00, 216.75, 0.00, 0.00, 690.00, 0.00, 149557.50, 0.00, 0.00),
(403, 70, 25, 54.00, 102.00, '54 x 102', 6, '54', '102', '', 2.00, 76.50, 0.00, 0.00, 690.00, 0.00, 52785.00, 0.00, 0.00),
(404, 70, 25, 45.00, 102.00, '45 x 102', 3, '45', '102', '', 2.00, 63.75, 0.00, 0.00, 690.00, 0.00, 43987.50, 0.00, 0.00),
(405, 71, 45, 54.00, 84.00, '54 x 84', 6, '54', '84', '', 3.00, 94.50, 0.00, 0.00, 430.00, 0.00, 40635.00, 0.00, 0.00),
(406, 72, 25, 15.30, 73.60, '15.3 x 73.6', 6, '18', '78', '', 1.00, 9.75, 0.00, 0.00, 690.00, 0.00, 6727.50, 0.00, 0.00),
(407, 73, 24, 54.00, 85.60, '54 x 85.6', 6, '54', '90', '', 1.00, 33.75, 0.00, 0.00, 420.00, 0.00, 14175.00, 0.00, 0.00),
(408, 73, 23, 32.40, 55.40, '32.4 x 55.4', 6, '36', '60', '', 1.00, 15.00, 0.00, 0.00, 240.00, 0.00, 3600.00, 0.00, 0.00),
(409, 73, 23, 21.00, 51.70, '21 x 51.7', 6, '24', '54', '', 1.00, 9.00, 0.00, 0.00, 240.00, 0.00, 2160.00, 0.00, 0.00),
(410, 73, 23, 16.40, 54.00, '16.4 x 54', 6, '18', '54', '', 1.00, 6.75, 0.00, 0.00, 240.00, 0.00, 1620.00, 0.00, 0.00),
(411, 73, 45, 84.00, 144.00, '84 x 144', 6, '84', '144', '', 1.00, 84.00, 0.00, 0.00, 380.00, 0.00, 31920.00, 0.00, 0.00),
(412, 74, 18, 84.00, 144.00, '84 x 144', 6, '84', '144', '', 1.00, 84.00, 0.00, 0.00, 500.00, 0.00, 42000.00, 0.00, 0.00),
(413, 74, 18, 72.00, 96.00, '72 x 96', 6, '72', '96', '', 1.00, 48.00, 0.00, 0.00, 500.00, 0.00, 24000.00, 0.00, 0.00),
(414, 74, 18, 45.00, 68.00, '45 x 68', 6, '48', '72', '', 1.00, 24.00, 0.00, 0.00, 500.00, 0.00, 12000.00, 0.00, 0.00),
(415, 75, 45, 27.60, 31.60, '27.6 x 31.6', 6, '30', '36', '', 1.00, 7.50, 0.00, 0.00, 460.00, 0.00, 3450.00, 0.00, 0.00),
(416, 75, 45, 27.60, 28.60, '27.6 x 28.6', 6, '30', '30', '', 1.00, 6.25, 0.00, 0.00, 460.00, 0.00, 2875.00, 0.00, 0.00),
(417, 76, 27, 41.00, 41.00, '41 x 41', 6, '42', '42', '', 1.00, 12.25, 0.00, 0.00, 940.00, 0.00, 11515.00, 0.00, 0.00),
(418, 77, 24, 21.00, 42.00, '21 x 42', 6, '24', '42', '', 1.00, 7.00, 0.00, 0.00, 440.00, 0.00, 3080.00, 0.00, 0.00),
(419, 77, 24, 21.00, 15.20, '21 x 15.2', 3, '21', '18', '', 2.00, 5.25, 0.00, 0.00, 440.00, 0.00, 2310.00, 0.00, 0.00),
(420, 77, 24, 13.40, 28.60, '13.4 x 28.6', 6, '18', '30', '', 1.00, 3.75, 0.00, 0.00, 440.00, 0.00, 1650.00, 0.00, 0.00),
(421, 77, 25, 16.40, 41.00, '16.4 x 41', 6, '18', '42', '', 1.00, 5.25, 0.00, 0.00, 690.00, 0.00, 3622.50, 0.00, 0.00),
(422, 77, 45, 21.00, 42.00, '21 x 42', 3, '21', '42', '', 1.00, 6.13, 0.00, 0.00, 380.00, 0.00, 2327.50, 0.00, 0.00),
(423, 78, 24, 4.60, 18.00, '4.6 x 18', 3, '6', '18', '', 6.00, 4.50, 0.00, 0.00, 420.00, 0.00, 1890.00, 0.00, 0.00),
(424, 78, 24, 9.20, 90.00, '9.2 x 90', 6, '12', '90', '', 1.00, 7.50, 0.00, 0.00, 420.00, 0.00, 3150.00, 0.00, 0.00),
(425, 79, 25, 12.00, 90.00, '12 x 90', 6, '12', '90', '', 1.00, 7.50, 0.00, 0.00, 670.00, 0.00, 5025.00, 0.00, 0.00),
(426, 79, 45, 24.00, 36.00, '24 x 36', 6, '24', '36', '', 2.00, 12.00, 0.00, 0.00, 370.00, 0.00, 4440.00, 0.00, 0.00),
(427, 79, 45, 30.00, 36.00, '30 x 36', 6, '30', '36', '', 1.00, 7.50, 0.00, 0.00, 370.00, 0.00, 2775.00, 0.00, 0.00),
(430, 80, 23, 23.00, 81.10, '23.00 x 81.10', 6, '24', '84', '', 1.00, 14.00, 0.00, 0.00, 310.00, 0.00, 4340.00, 0.00, 0.00),
(431, 80, 23, 23.00, 81.60, '23.00 x 81.60', 6, '24', '84', '', 1.00, 14.00, 0.00, 0.00, 310.00, 0.00, 4340.00, 0.00, 0.00),
(432, 81, 45, 24.00, 30.00, '24 x 30', 6, '24', '30', '', 2.00, 10.00, 0.00, 0.00, 430.00, 0.00, 4300.00, 0.00, 0.00),
(433, 81, 45, 24.00, 24.00, '24 x 24', 6, '24', '24', '', 1.00, 4.00, 0.00, 0.00, 450.00, 0.00, 1800.00, 0.00, 0.00),
(434, 82, 25, 37.60, 84.00, '37.6 x 84', 3, '39', '84', '', 1.00, 22.75, 0.00, 0.00, 690.00, 0.00, 15697.50, 0.00, 0.00),
(435, 83, 22, 30.30, 68.00, '30.3 x 68', 6, '36', '72', '', 1.00, 18.00, 0.00, 0.00, 230.00, 0.00, 4140.00, 0.00, 0.00),
(436, 84, 24, 23.40, 27.10, '23.4 x 27.1', 6, '24', '30', '', 1.00, 5.00, 0.00, 0.00, 440.00, 0.00, 2200.00, 0.00, 0.00),
(437, 85, 31, 15.00, 16.70, '15 x 16.7', 3, '15', '18', '', 2.00, 3.75, 0.00, 0.00, 320.00, 0.00, 1200.00, 0.00, 0.00),
(438, 85, 31, 30.00, 72.00, '30 x 72', 6, '30', '72', '', 2.00, 30.00, 0.00, 0.00, 320.00, 0.00, 9600.00, 0.00, 0.00),
(439, 85, 31, 19.20, 71.60, '19.2 x 71.6', 3, '21', '72', '', 2.00, 21.00, 0.00, 0.00, 320.00, 0.00, 6720.00, 0.00, 0.00),
(440, 86, 23, 36.00, 36.00, '36 x 36', 6, '36', '36', '', 1.00, 9.00, 0.00, 0.00, 300.00, 0.00, 2700.00, 0.00, 0.00),
(441, 86, 23, 35.60, 35.60, '35.6 x 35.6', 6, '36', '36', '', 1.00, 9.00, 0.00, 0.00, 270.00, 0.00, 2430.00, 0.00, 0.00),
(442, 87, 25, 22.20, 66.00, '22.2 x 66', 6, '24', '66', '', 1.00, 11.00, 0.00, 0.00, 690.00, 0.00, 7590.00, 0.00, 0.00),
(443, 87, 25, 22.20, 54.00, '22.2 x 54', 6, '24', '54', '', 1.00, 9.00, 0.00, 0.00, 690.00, 0.00, 6210.00, 0.00, 0.00),
(444, 88, 25, 45.00, 84.00, '45 x 84', 3, '45', '84', '', 15.00, 393.75, 0.00, 0.00, 690.00, 0.00, 271687.50, 0.00, 0.00),
(445, 88, 25, 24.00, 77.40, '24 x 77.4', 6, '24', '78', '', 1.00, 13.00, 0.00, 0.00, 690.00, 0.00, 8970.00, 0.00, 0.00),
(446, 88, 25, 19.40, 30.00, '19.4 x 30', 3, '21', '30', '', 1.00, 4.38, 0.00, 0.00, 690.00, 0.00, 3018.75, 0.00, 0.00),
(456, 90, 25, 21.60, 84.00, '21.6 x 84', 6, '24', '84', '', 1.00, 14.00, 0.00, 0.00, 690.00, 0.00, 9660.00, 0.00, 0.00),
(457, 91, 23, 23.40, 36.00, '23.4 x 36', 3, '24', '36', '', 8.00, 48.00, 0.00, 0.00, 270.00, 0.00, 12960.00, 0.00, 0.00),
(458, 91, 25, 24.00, 82.10, '24 x 82.1', 6, '24', '84', '', 1.00, 14.00, 0.00, 0.00, 690.00, 0.00, 9660.00, 0.00, 0.00),
(459, 92, 22, 9.50, 84.00, '9.5 x 84', 6, '12', '84', '', 1.00, 7.00, 0.00, 0.00, 230.00, 0.00, 1610.00, 0.00, 0.00),
(460, 92, 22, 30.30, 72.00, '30.3 x 72', 3, '33', '72', '', 1.00, 16.50, 0.00, 0.00, 230.00, 0.00, 3795.00, 0.00, 0.00),
(461, 93, 23, 61.40, 45.00, '61.4 x 45', 3, '63', '45', '', 2.00, 39.38, 0.00, 0.00, 240.00, 0.00, 9450.00, 0.00, 0.00),
(462, 93, 23, 61.40, 45.00, '61.4 x 45', 3, '63', '45', '', 2.00, 39.38, 0.00, 0.00, 240.00, 0.00, 9450.00, 0.00, 0.00),
(463, 93, 23, 46.00, 45.00, '46 x 45', 3, '48', '45', '', 2.00, 30.00, 0.00, 0.00, 240.00, 0.00, 7200.00, 0.00, 0.00),
(495, 96, 35, 16.20, 48.20, '16.2 x 48.2', 6, '18', '54', '', 1.00, 6.75, 0.00, 0.00, 415.00, 0.00, 2801.25, 0.00, 0.00),
(496, 97, 35, 17.20, 23.10, '17.2 x 23.1', 6, '18', '24', '', 1.00, 3.00, 0.00, 0.00, 330.00, 0.00, 990.00, 0.00, 0.00),
(497, 97, 35, 16.50, 23.00, '16.5 x 23', 6, '18', '24', '', 1.00, 3.00, 0.00, 0.00, 330.00, 0.00, 990.00, 0.00, 0.00),
(498, 97, 35, 35.60, 18.00, '35.6 x 18', 6, '36', '18', '', 1.00, 4.50, 0.00, 0.00, 330.00, 0.00, 1485.00, 0.00, 0.00),
(499, 97, 35, 21.60, 16.40, '21.6 x 16.4', 6, '24', '18', '', 2.00, 6.00, 0.00, 0.00, 330.00, 0.00, 1980.00, 0.00, 0.00),
(500, 97, 35, 21.60, 17.70, '21.6 x 17.7', 6, '24', '18', '', 2.00, 6.00, 0.00, 0.00, 330.00, 0.00, 1980.00, 0.00, 0.00),
(501, 66, 24, 23.70, 26.60, '23.70 x 26.60', 3, '24', '27', '', 1.00, 4.50, 0.00, 0.00, 440.00, 0.00, 1980.00, 0.00, 0.00),
(502, 66, 24, 23.70, 38.10, '23.70 x 38.10', 3, '24', '39', '', 1.00, 6.50, 0.00, 0.00, 440.00, 0.00, 2860.00, 0.00, 0.00),
(503, 66, 25, 42.00, 109.50, '42.00 x 109.50', 6, '42', '114', '', 1.00, 33.25, 0.00, 0.00, 690.00, 0.00, 22942.50, 0.00, 0.00),
(504, 98, 23, 16.00, 34.00, '16 x 34', 6, '18', '36', '', 1.00, 4.50, 0.00, 0.00, 270.00, 0.00, 1215.00, 0.00, 0.00),
(505, 99, 48, 15.00, 60.00, '15 x 60', 6, '18', '60', '', 1.00, 7.50, 0.00, 0.00, 1400.00, 0.00, 10500.00, 0.00, 0.00),
(506, 100, 29, 28.00, 58.40, '28 x 58.4', 6, '30', '60', '', 1.00, 12.50, 0.00, 0.00, 1400.00, 0.00, 17500.00, 0.00, 0.00),
(507, 101, 29, 60.00, 102.00, '60 x 102', 6, '60', '102', '', 1.00, 42.50, 0.00, 0.00, 1400.00, 0.00, 59500.00, 0.00, 0.00),
(508, 102, 25, 28.00, 52.00, '28 x 52', 6, '30', '54', '', 1.00, 11.25, 0.00, 0.00, 690.00, 0.00, 7762.50, 0.00, 0.00),
(509, 102, 25, 28.00, 51.50, '28 x 51.5', 6, '30', '54', '', 1.00, 11.25, 0.00, 0.00, 690.00, 0.00, 7762.50, 0.00, 0.00),
(510, 102, 25, 21.00, 120.00, '21 x 120', 6, '24', '120', '', 1.00, 20.00, 0.00, 0.00, 690.00, 0.00, 13800.00, 0.00, 0.00),
(511, 102, 25, 21.00, 66.00, '21 x 66', 6, '24', '66', '', 1.00, 11.00, 0.00, 0.00, 690.00, 0.00, 7590.00, 0.00, 0.00),
(512, 103, 25, 28.00, 52.00, '28 x 52', 6, '30', '54', '', 1.00, 11.25, 0.00, 0.00, 690.00, 0.00, 7762.50, 0.00, 0.00),
(513, 103, 25, 28.00, 51.50, '28 x 51.5', 6, '30', '54', '', 1.00, 11.25, 0.00, 0.00, 690.00, 0.00, 7762.50, 0.00, 0.00),
(514, 103, 25, 21.00, 120.00, '21 x 120', 3, '21', '120', '', 1.00, 17.50, 0.00, 0.00, 690.00, 0.00, 12075.00, 0.00, 0.00),
(515, 103, 25, 21.00, 66.00, '21 x 66', 3, '21', '66', '', 1.00, 9.63, 0.00, 0.00, 690.00, 0.00, 6641.25, 0.00, 0.00),
(516, 103, 25, 21.00, 54.00, '21 x 54', 3, '21', '54', '', 1.00, 7.88, 0.00, 0.00, 690.00, 0.00, 5433.75, 0.00, 0.00),
(517, 104, 25, 8.00, 60.00, '8 x 60', 6, '12', '60', '', 7.00, 35.00, 0.00, 0.00, 690.00, 0.00, 24150.00, 0.00, 0.00),
(518, 104, 25, 8.00, 54.00, '8 x 54', 6, '12', '54', '', 1.00, 4.50, 0.00, 0.00, 690.00, 0.00, 3105.00, 0.00, 0.00),
(531, 106, 24, 32.50, 98.30, '32.50 x 98.30', 6, '36', '102', '', 1.00, 25.50, 0.00, 0.00, 440.00, 0.00, 11220.00, 0.00, 0.00),
(532, 106, 24, 35.40, 91.20, '35.40 x 91.20', 6, '36', '96', '', 2.00, 48.00, 0.00, 0.00, 440.00, 0.00, 21120.00, 0.00, 0.00),
(533, 106, 24, 46.00, 68.00, '46.00 x 68.00', 6, '48', '72', '', 1.00, 24.00, 0.00, 0.00, 550.00, 0.00, 13200.00, 0.00, 0.00),
(534, 106, 39, 39.00, 84.00, '39.00 x 84.00', 3, '39', '84', '', 2.00, 45.50, 0.00, 0.00, 450.00, 0.00, 20475.00, 0.00, 0.00),
(535, 106, 39, 21.00, 84.00, '21.00 x 84.00', 3, '21', '84', '', 4.00, 49.00, 0.00, 0.00, 450.00, 0.00, 22050.00, 0.00, 0.00),
(536, 106, 39, 36.00, 39.00, '36.00 x 39.00', 6, '36', '42', '', 1.00, 10.50, 0.00, 0.00, 450.00, 0.00, 4725.00, 0.00, 0.00),
(537, 106, 39, 30.00, 33.00, '30.00 x 33.00', 6, '30', '36', '', 1.00, 7.50, 0.00, 0.00, 450.00, 0.00, 3375.00, 0.00, 0.00),
(538, 106, 39, 18.00, 36.00, '18.00 x 36.00', 6, '18', '36', '', 1.00, 4.50, 0.00, 0.00, 450.00, 0.00, 2025.00, 0.00, 0.00),
(539, 105, 23, 72.00, 84.00, '72.00 x 84.00', 6, '72', '84', '', 14.00, 588.00, 0.00, 0.00, 235.00, 0.00, 138180.00, 0.00, 0.00),
(540, 105, 35, 72.00, 84.00, '72.00 x 84.00', 6, '72', '84', '', 6.00, 252.00, 0.00, 0.00, 320.00, 0.00, 80640.00, 0.00, 0.00),
(541, 105, 43, 72.00, 84.00, '72.00 x 84.00', 6, '72', '84', '', 4.00, 168.00, 0.00, 0.00, 250.00, 0.00, 42000.00, 0.00, 0.00),
(542, 105, 45, 72.00, 84.00, '72.00 x 84.00', 6, '72', '84', '', 4.00, 168.00, 0.00, 0.00, 365.00, 0.00, 61320.00, 0.00, 0.00),
(543, 107, 25, 12.00, 45.60, '12 x 45.6', 6, '12', '48', '', 1.00, 4.00, 0.00, 0.00, 690.00, 0.00, 2760.00, 0.00, 0.00),
(544, 107, 25, 8.40, 90.00, '8.4 x 90', 6, '12', '90', '', 1.00, 7.50, 0.00, 0.00, 690.00, 0.00, 5175.00, 0.00, 0.00),
(545, 107, 45, 18.00, 24.00, '18 x 24', 6, '18', '24', '', 4.00, 12.00, 0.00, 0.00, 440.00, 0.00, 5280.00, 0.00, 0.00),
(546, 107, 24, 24.00, 24.00, '24 x 24', 6, '24', '24', '', 4.00, 16.00, 0.00, 0.00, 480.00, 0.00, 7680.00, 0.00, 0.00),
(547, 108, 25, 29.60, 84.00, '29.6 x 84', 6, '30', '84', '', 1.00, 17.50, 0.00, 0.00, 690.00, 0.00, 12075.00, 0.00, 0.00),
(548, 108, 25, 18.00, 30.00, '18 x 30', 6, '18', '30', '', 1.00, 3.75, 0.00, 0.00, 690.00, 0.00, 2587.50, 0.00, 0.00),
(549, 108, 25, 30.00, 102.00, '30 x 102', 6, '30', '102', '', 1.00, 21.25, 0.00, 0.00, 690.00, 0.00, 14662.50, 0.00, 0.00),
(550, 108, 25, 24.00, 84.00, '24 x 84', 6, '24', '84', '', 1.00, 14.00, 0.00, 0.00, 690.00, 0.00, 9660.00, 0.00, 0.00),
(551, 47, 25, 30.00, 84.00, '30.00 x 84.00', 6, '30', '84', '', 2.00, 35.00, 0.00, 0.00, 680.00, 0.00, 23800.00, 0.00, 0.00),
(552, 47, 25, 42.00, 108.00, '42.00 x 108.00', 6, '42', '108', '', 1.00, 31.50, 0.00, 0.00, 680.00, 0.00, 21420.00, 0.00, 0.00),
(553, 47, 25, 21.00, 60.00, '21.00 x 60.00', 6, '24', '60', '', 1.00, 10.00, 0.00, 0.00, 680.00, 0.00, 6800.00, 0.00, 0.00),
(554, 47, 24, 18.00, 30.00, '18 x 30', 6, '18', '30', '', 1.00, 3.75, 0.00, 0.00, 445.00, 0.00, 1668.75, 0.00, 0.00),
(555, 47, 23, 22.10, 47.60, '22.1 x 47.6', 6, '24', '48', '', 1.00, 8.00, 0.00, 0.00, 240.00, 0.00, 1920.00, 0.00, 0.00),
(556, 68, 23, 42.00, 42.00, '42.00 x 42.00', 6, '42', '42', '', 1.00, 12.25, 0.00, 0.00, 300.00, 0.00, 3675.00, 0.00, 0.00),
(557, 62, 42, 24.00, 72.00, '24.00 x 72.00', 6, '24', '72', '', 2.00, 24.00, 0.00, 0.00, 200.00, 0.00, 4800.00, 0.00, 0.00),
(558, 62, 42, 18.00, 72.00, '18.00 x 72.00', 6, '18', '72', '', 2.00, 18.00, 0.00, 0.00, 200.00, 0.00, 3600.00, 0.00, 0.00),
(559, 62, 43, 30.00, 30.00, '30.00 x 30.00', 6, '30', '30', '', 1.00, 6.25, 0.00, 0.00, 280.00, 0.00, 1750.00, 0.00, 0.00),
(560, 63, 24, 30.00, 84.00, '30.00 x 84.00', 6, '30', '84', '', 1.00, 17.50, 0.00, 0.00, 440.00, 0.00, 7700.00, 0.00, 0.00),
(561, 63, 25, 12.00, 36.00, '12.00 x 36.00', 6, '12', '36', '', 1.00, 3.00, 0.00, 0.00, 690.00, 0.00, 2070.00, 0.00, 0.00),
(562, 63, 39, 6.00, 80.20, '6.00 x 80.20', 6, '6', '84', '', 4.00, 14.00, 0.00, 0.00, 450.00, 0.00, 6300.00, 0.00, 0.00),
(563, 63, 39, 5.60, 96.00, '5.60 x 96.00', 6, '6', '96', '', 1.00, 4.00, 0.00, 0.00, 450.00, 0.00, 1800.00, 0.00, 0.00),
(564, 63, 39, 4.70, 96.00, '4.70 x 96.00', 6, '6', '96', '', 1.00, 4.00, 0.00, 0.00, 450.00, 0.00, 1800.00, 0.00, 0.00),
(565, 44, 23, 11.00, 24.00, '11.00 x 24.00', 6, '12', '24', '', 1.00, 2.00, 0.00, 0.00, 240.00, 0.00, 480.00, 0.00, 0.00),
(566, 44, 23, 17.00, 71.00, '17.00 x 71.00', 6, '18', '72', '', 1.00, 9.00, 0.00, 0.00, 240.00, 0.00, 2160.00, 0.00, 0.00),
(567, 44, 23, 10.00, 48.20, '10.00 x 48.20', 6, '12', '54', '', 1.00, 4.50, 0.00, 0.00, 240.00, 0.00, 1080.00, 0.00, 0.00),
(568, 44, 23, 18.70, 78.00, '18.70 x 78.00', 3, '21', '78', '', 1.00, 11.38, 0.00, 0.00, 240.00, 0.00, 2731.20, 0.00, 0.00),
(569, 44, 23, 46.00, 66.00, '46.00 x 66.00', 6, '48', '66', '', 1.00, 22.00, 0.00, 0.00, 240.00, 0.00, 5280.00, 0.00, 0.00),
(570, 44, 44, 12.00, 69.70, '12.00 x 69.70', 6, '12', '72', '', 8.00, 48.00, 0.00, 0.00, 300.00, 0.00, 14400.00, 0.00, 0.00),
(571, 44, 44, 39.00, 69.70, '39.00 x 69.70', 6, '42', '72', '', 1.00, 21.00, 0.00, 0.00, 300.00, 0.00, 6300.00, 0.00, 0.00),
(572, 44, 44, 12.00, 66.60, '12.00 x 66.60', 6, '12', '72', '', 2.00, 12.00, 0.00, 0.00, 300.00, 0.00, 3600.00, 0.00, 0.00),
(573, 89, 25, 35.50, 84.00, '35.50 x 84.00', 6, '36', '84', '', 2.00, 42.00, 0.00, 0.00, 680.00, 0.00, 28560.00, 0.00, 0.00),
(574, 89, 25, 48.00, 102.00, '48.00 x 102.00', 6, '48', '102', '', 2.00, 68.00, 0.00, 0.00, 680.00, 0.00, 46240.00, 0.00, 0.00),
(575, 89, 25, 66.00, 102.00, '66.00 x 102.00', 6, '66', '102', '', 2.00, 93.50, 0.00, 0.00, 680.00, 0.00, 63580.00, 0.00, 0.00),
(576, 89, 25, 45.00, 102.00, '45.00 x 102.00', 3, '45', '102', '', 2.00, 63.75, 0.00, 0.00, 680.00, 0.00, 43350.00, 0.00, 0.00),
(577, 89, 25, 30.00, 102.00, '30.00 x 102.00', 6, '30', '102', '', 1.00, 21.25, 0.00, 0.00, 680.00, 0.00, 14450.00, 0.00, 0.00),
(578, 89, 25, 12.00, 102.00, '12.00 x 102.00', 6, '12', '102', '', 1.00, 8.50, 0.00, 0.00, 680.00, 0.00, 5780.00, 0.00, 0.00),
(579, 89, 25, 12.00, 102.00, '12.00 x 102.00', 3, '12', '102', '', 1.00, 8.50, 0.00, 0.00, 680.00, 0.00, 5780.00, 0.00, 0.00),
(580, 89, 25, 37.10, 102.00, '37.10 x 102.00', 3, '39', '102', '', 1.00, 27.63, 0.00, 0.00, 680.00, 0.00, 18788.40, 0.00, 0.00),
(581, 89, 25, 48.60, 102.00, '48.60 x 102.00', 3, '51', '102', '', 1.00, 36.13, 0.00, 0.00, 680.00, 0.00, 24568.40, 0.00, 0.00),
(582, 109, 24, 48.00, 72.00, '48 x 72', 6, '48', '72', '', 4.00, 96.00, 0.00, 0.00, 410.00, 0.00, 39360.00, 0.00, 0.00),
(583, 110, 24, 12.30, 96.00, '12.3 x 96', 6, '18', '96', '', 1.00, 12.00, 0.00, 0.00, 440.00, 0.00, 5280.00, 0.00, 0.00),
(584, 110, 24, 12.30, 60.00, '12.3 x 60', 6, '18', '60', '', 1.00, 7.50, 0.00, 0.00, 440.00, 0.00, 3300.00, 0.00, 0.00),
(585, 111, 23, 36.00, 36.00, '36 x 36', 6, '36', '36', '', 1.00, 9.00, 0.00, 0.00, 270.00, 0.00, 2430.00, 0.00, 0.00),
(586, 112, 25, 48.00, 48.00, '48 x 48', 6, '48', '48', '', 1.00, 16.00, 0.00, 0.00, 750.00, 0.00, 12000.00, 0.00, 0.00),
(587, 113, 45, 20.00, 30.00, '20 x 30', 3, '21', '30', '', 2.00, 8.75, 0.00, 0.00, 410.00, 0.00, 3587.50, 0.00, 0.00),
(588, 113, 24, 18.00, 33.00, '18 x 33', 3, '18', '33', '', 2.00, 8.25, 0.00, 0.00, 440.00, 0.00, 3630.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `sale_master`
--

CREATE TABLE `sale_master` (
  `id` int NOT NULL,
  `invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sale_date` date NOT NULL,
  `customer_id` int NOT NULL,
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `other_charges` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `received_amount` decimal(15,2) DEFAULT '0.00',
  `remaining_amount` decimal(15,2) DEFAULT '0.00',
  `payment_type` enum('cash','bank','credit','partial') COLLATE utf8mb4_unicode_ci DEFAULT 'cash',
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) DEFAULT '1',
  `refund_status` enum('none','partial','full') COLLATE utf8mb4_unicode_ci DEFAULT 'none',
  `refund_amount` decimal(15,2) DEFAULT '0.00',
  `refund_date` date DEFAULT NULL,
  `refund_reason` text COLLATE utf8mb4_unicode_ci,
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
(37, 'SAL-00033', '2026-08-28', 41, 20500.00, 0.00, 0.00, 0.00, 20500.00, 0.00, 20500.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-28 15:23:44'),
(38, 'SAL-00034', '2026-08-29', 48, 108720.00, 0.00, 0.00, 0.00, 108720.00, 0.00, 108720.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-29 09:22:22'),
(39, 'SAL-00035', '2026-08-31', 45, 83205.00, 0.00, 0.00, 0.00, 83205.00, 0.00, 83205.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 05:43:22'),
(40, 'SAL-00036', '2026-08-31', 45, 147840.00, 0.00, 0.00, 0.00, 147840.00, 0.00, 147840.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 05:56:59'),
(41, 'SAL-00037', '2026-08-31', 35, 154475.00, 0.00, 0.00, 12000.00, 166475.00, 0.00, 166475.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 07:59:19'),
(42, 'SAL-00038', '2026-08-31', 60, 2850.00, 0.00, 0.00, 0.00, 2850.00, 2850.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 08:57:35'),
(43, 'SAL-00039', '2026-08-31', 41, 2850.00, 0.00, 0.00, 0.00, 2850.00, 0.00, 2850.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 09:27:22'),
(44, 'SAL-00040', '2026-08-31', 62, 36031.20, 0.00, 0.00, 0.00, 36031.20, 36031.20, 0.00, 'bank', 1, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 09:47:47'),
(45, 'SAL-00041', '2026-08-31', 63, 117390.00, 0.00, 0.00, 0.00, 117390.00, 117390.00, 0.00, 'bank', 1, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 11:07:51'),
(46, 'SAL-00042', '2026-08-31', 60, 6460.00, 0.00, 0.00, 0.00, 6460.00, 6460.00, 0.00, 'bank', 1, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 11:09:40'),
(47, 'SAL-00043', '2026-08-31', 29, 55608.75, 0.00, 0.00, 3000.00, 58608.75, 0.00, 58608.75, 'credit', 0, '', 'polish', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 11:39:08'),
(48, 'SAL-00044', '2026-08-31', 45, 6555.00, 0.00, 0.00, 0.00, 6555.00, 6555.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 11:46:37'),
(49, 'SAL-00045', '2026-08-31', 60, 5500.00, 0.00, 0.00, 0.00, 5500.00, 2500.00, 3000.00, 'partial', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 12:12:16'),
(50, 'SAL-00046', '2026-08-31', 64, 45540.00, 0.00, 0.00, 0.00, 45540.00, 0.00, 45540.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 12:18:44'),
(51, 'SAL-00047', '2026-08-31', 65, 2750.00, 0.00, 0.00, 0.00, 2750.00, 0.00, 2750.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 12:23:55'),
(52, 'SAL-00048', '2026-08-31', 32, 9300.00, 0.00, 0.00, 0.00, 9300.00, 0.00, 9300.00, 'credit', 0, '', 'farmma', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 13:02:15'),
(53, 'SAL-00049', '2026-08-31', 64, 142232.50, 0.00, 0.00, 0.00, 142232.50, 0.00, 142232.50, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 13:54:35'),
(54, 'SAL-00050', '2026-08-31', 68, 28250.00, 0.00, 0.00, 0.00, 28250.00, 0.00, 28250.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 14:06:04'),
(55, 'SAL-00051', '2026-08-31', 32, 5737.50, 0.00, 0.00, 0.00, 5737.50, 0.00, 5737.50, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 14:12:44'),
(56, 'SAL-00052', '2026-08-31', 45, 8400.00, 0.00, 0.00, 0.00, 8400.00, 0.00, 8400.00, 'credit', 0, '', 'break kar deya thi', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 14:29:13'),
(58, 'SAL-00054', '2026-08-31', 66, 64170.00, 0.00, 0.00, 0.00, 64170.00, 0.00, 64170.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 14:33:54'),
(59, 'SAL-00055', '2026-08-31', 67, 60000.00, 0.00, 0.00, 0.00, 60000.00, 40000.00, 20000.00, 'partial', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 14:39:32'),
(60, 'SAL-00056', '2026-08-31', 70, 28177.50, 0.00, 0.00, 0.00, 28177.50, 0.00, 28177.50, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 15:44:42'),
(61, 'SAL-00057', '2026-08-31', 71, 2760.00, 0.00, 0.00, 0.00, 2760.00, 2760.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-08-31 16:02:17'),
(62, 'SAL-00058', '2026-08-31', 76, 10150.00, 0.00, 0.00, 0.00, 10150.00, 10150.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 06:13:43'),
(63, 'SAL-00059', '2026-08-31', 75, 19670.00, 0.00, 0.00, 0.00, 19670.00, 19670.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 06:17:18'),
(64, 'SAL-00060', '2026-08-31', 77, 11375.00, 0.00, 0.00, 0.00, 11375.00, 11375.00, 0.00, 'bank', 1, '', 'wall cutting', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 06:19:00'),
(65, 'SAL-00061', '2026-09-01', 78, 76807.50, 0.00, 0.00, 0.00, 76807.50, 0.00, 76807.50, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 06:26:08'),
(66, 'SAL-00062', '2026-09-01', 32, 27782.50, 0.00, 0.00, 0.00, 27782.50, 0.00, 27782.50, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 06:30:07'),
(67, 'SAL-00063', '2026-08-31', 73, 43297.50, 0.00, 0.00, 0.00, 43297.50, 0.00, 43297.50, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 06:35:44'),
(68, 'SAL-00064', '2026-08-31', 74, 3675.00, 0.00, 0.00, 0.00, 3675.00, 3675.00, 0.00, 'cash', 0, '', 'polish', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 06:38:46'),
(69, 'SAL-00065', '2026-08-31', 70, 5100.00, 0.00, 0.00, 0.00, 5100.00, 0.00, 5100.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 06:41:16'),
(70, 'SAL-00066', '2026-09-01', 79, 246330.00, 0.00, 0.00, 0.00, 246330.00, 100000.00, 146330.00, 'partial', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 08:15:32'),
(71, 'SAL-00067', '2026-09-01', 79, 40635.00, 0.00, 0.00, 0.00, 40635.00, 0.00, 40635.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 08:17:38'),
(72, 'SAL-00068', '2026-09-01', 80, 6727.50, 0.00, 0.00, 0.00, 6727.50, 0.00, 6727.50, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 08:20:52'),
(73, 'SAL-00069', '2026-09-01', 63, 53475.00, 0.00, 0.00, 0.00, 53475.00, 0.00, 53475.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 08:30:17'),
(74, 'SAL-00070', '2026-09-01', 63, 78000.00, 0.00, 0.00, 0.00, 78000.00, 0.00, 78000.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 08:32:46'),
(75, 'SAL-00071', '2026-09-01', 81, 6325.00, 0.00, 0.00, 0.00, 6325.00, 0.00, 6325.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 08:37:23'),
(76, 'SAL-00072', '2026-09-01', 82, 11515.00, 0.00, 0.00, 0.00, 11515.00, 5000.00, 6515.00, 'partial', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 08:50:38'),
(77, 'SAL-00073', '2026-09-01', 83, 12990.00, 0.00, 0.00, 0.00, 12990.00, 12990.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 08:55:41'),
(78, 'SAL-00074', '2026-09-01', 84, 5040.00, 0.00, 0.00, 0.00, 5040.00, 5040.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 09:01:08'),
(79, 'SAL-00075', '2026-09-01', 66, 12240.00, 0.00, 0.00, 0.00, 12240.00, 0.00, 12240.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 09:14:24'),
(80, 'SAL-00076', '2026-09-01', 85, 8680.00, 0.00, 0.00, 0.00, 8680.00, 0.00, 8680.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 09:19:56'),
(81, 'SAL-00077', '2026-09-01', 86, 6100.00, 0.00, 0.00, 0.00, 6100.00, 0.00, 6100.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 09:25:03'),
(82, 'SAL-00078', '2026-09-01', 87, 15697.50, 0.00, 0.00, 0.00, 15697.50, 0.00, 15697.50, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 09:29:29'),
(83, 'SAL-00079', '2026-09-01', 34, 4140.00, 0.00, 0.00, 0.00, 4140.00, 4140.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 11:37:40'),
(84, 'SAL-00080', '2026-09-01', 88, 2200.00, 0.00, 0.00, 0.00, 2200.00, 0.00, 2200.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 11:40:05'),
(85, 'SAL-00081', '2026-09-01', 89, 17520.00, 0.00, 0.00, 0.00, 17520.00, 0.00, 17520.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 11:46:59'),
(86, 'SAL-00082', '2026-09-01', 90, 5130.00, 0.00, 0.00, 0.00, 5130.00, 0.00, 5130.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 11:55:13'),
(87, 'SAL-00083', '2026-09-01', 66, 13800.00, 0.00, 0.00, 0.00, 13800.00, 0.00, 13800.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 12:07:32'),
(88, 'SAL-00084', '2026-09-01', 91, 283676.25, 0.00, 0.00, 0.00, 283676.25, 0.00, 283676.25, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 12:26:34'),
(89, 'SAL-00085', '2026-09-01', 79, 251096.80, 0.00, 0.00, 0.00, 251096.80, 251096.80, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 12:33:29'),
(90, 'SAL-00086', '2026-09-01', 57, 9660.00, 0.00, 0.00, 0.00, 9660.00, 0.00, 9660.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 12:35:04'),
(91, 'SAL-00087', '2026-09-01', 81, 22620.00, 0.00, 0.00, 0.00, 22620.00, 22620.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 12:48:10'),
(92, 'SAL-00088', '2026-09-01', 34, 5405.00, 0.00, 0.00, 0.00, 5405.00, 0.00, 5405.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 12:54:22'),
(93, 'SAL-00089', '2026-09-01', 92, 26100.00, 0.00, 0.00, 0.00, 26100.00, 26100.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 12:56:40'),
(96, 'SAL-00090', '2026-09-01', 58, 2801.25, 0.00, 0.00, 0.00, 2801.25, 2801.25, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 13:27:26'),
(97, 'SAL-00091', '2026-09-01', 55, 7425.00, 0.00, 0.00, 0.00, 7425.00, 0.00, 7425.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 14:55:21'),
(98, 'SAL-00092', '2026-09-01', 32, 1215.00, 0.00, 0.00, 0.00, 1215.00, 0.00, 1215.00, 'credit', 0, '', 'polish', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 14:58:39'),
(99, 'SAL-00093', '2026-09-01', 95, 10500.00, 0.00, 0.00, 0.00, 10500.00, 10500.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 15:00:27'),
(100, 'SAL-00094', '2026-09-01', 58, 17500.00, 0.00, 0.00, 0.00, 17500.00, 17500.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 15:01:36'),
(101, 'SAL-00095', '2026-09-01', 58, 59500.00, 0.00, 0.00, 0.00, 59500.00, 59500.00, 0.00, 'bank', 1, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 15:02:34'),
(102, 'SAL-00096', '2026-09-01', 96, 36915.00, 0.00, 0.00, 0.00, 36915.00, 35000.00, 1915.00, 'partial', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 15:05:26'),
(103, 'SAL-00097', '2026-09-01', 96, 39675.00, 0.00, 0.00, 0.00, 39675.00, 35000.00, 4675.00, 'partial', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 15:08:53'),
(104, 'SAL-00098', '2026-09-01', 97, 27255.00, 0.00, 0.00, 0.00, 27255.00, 27255.00, 0.00, 'cash', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 15:10:57'),
(105, 'SAL-00099', '2026-09-01', 98, 322140.00, 0.00, 0.00, 10000.00, 332140.00, 0.00, 332140.00, 'credit', 0, '', 'bill no 2001', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 15:30:49'),
(106, 'SAL-00100', '2026-09-01', 93, 98190.00, 0.00, 0.00, 3000.00, 101190.00, 0.00, 101190.00, 'credit', 0, '', 'bill no 2002', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 15:34:17'),
(107, 'SAL-00101', '2026-09-01', 100, 20895.00, 0.00, 0.00, 2000.00, 22895.00, 0.00, 22895.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 15:42:16'),
(108, 'SAL-00102', '2026-09-01', 99, 38985.00, 0.00, 0.00, 5000.00, 43985.00, 0.00, 43985.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-01 15:44:21'),
(109, 'SAL-00103', '2026-09-02', 55, 39360.00, 0.00, 0.00, 0.00, 39360.00, 0.00, 39360.00, 'credit', 0, '', 'no polish', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-02 06:06:17'),
(110, 'SAL-00104', '2026-09-02', 101, 8580.00, 0.00, 0.00, 0.00, 8580.00, 0.00, 8580.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-02 06:19:19'),
(111, 'SAL-00105', '2026-09-02', 102, 2430.00, 0.00, 0.00, 0.00, 2430.00, 0.00, 2430.00, 'credit', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-02 06:25:36'),
(112, 'SAL-00106', '2026-09-02', 103, 12000.00, 0.00, 0.00, 0.00, 12000.00, 7000.00, 5000.00, 'partial', 0, '', 'Gole', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-02 06:30:47'),
(113, 'SAL-00107', '2026-09-02', 104, 7217.50, 0.00, 0.00, 0.00, 7217.50, 3980.00, 3237.50, 'partial', 0, '', '', 1, 'none', 0.00, NULL, NULL, 1, '2026-09-02 06:34:32');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int NOT NULL,
  `supplier_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cnic` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ntn` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `opening_balance` decimal(15,2) DEFAULT '0.00',
  `balance_type` enum('payable','receivable') COLLATE utf8mb4_unicode_ci DEFAULT 'payable' COMMENT 'payable=Company owes supplier, receivable=Supplier owes company',
  `current_balance` decimal(15,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `supplier_code`, `supplier_name`, `company_name`, `contact_person`, `mobile`, `cnic`, `ntn`, `email`, `address`, `opening_balance`, `balance_type`, `current_balance`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(3, 'SUP-0001', 'Ghani Glass Limited', 'Ghani glass limited', '03018481249', '03018481249', '', '', '', '40-L  Model Town Extension Block L Lahore', 0.00, 'payable', 0.00, 1, '', '2026-08-29 14:33:04', '2026-08-29 14:33:04'),
(4, 'SUP-0002', 'Ghani Value Glass Limited', 'Ghani Value glass limited', '', '03046666646', '', '', '', '39-L  Model Town Extension Block L Lahore', 0.00, 'payable', 0.00, 1, '', '2026-08-29 14:34:15', '2026-08-29 14:34:15');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_ledger`
--

CREATE TABLE `supplier_ledger` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `reference_type` enum('OPENING','PURCHASE','PAYMENT','ADJUSTMENT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `debit` decimal(15,2) DEFAULT '0.00' COMMENT 'Supplier owes company',
  `credit` decimal(15,2) DEFAULT '0.00' COMMENT 'Company owes supplier',
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_ledger`
--

INSERT INTO `supplier_ledger` (`id`, `date`, `supplier_id`, `reference_type`, `reference_id`, `description`, `debit`, `credit`, `balance`, `created_at`) VALUES
(7, '2026-07-14', 2, 'PURCHASE', 6, 'Purchase Invoice: PUR-00006', 0.00, 1270080.00, 1270080.00, '2026-07-14 18:50:36'),
(8, '2026-07-20', 2, 'PURCHASE', 7, 'Purchase Invoice: PUR-00007', 0.00, 1151010.00, 2421090.00, '2026-07-20 18:13:37'),
(9, '2026-07-26', 2, 'PURCHASE', 8, 'Purchase Invoice: PUR-00008', 0.00, 1526448.00, 3947538.00, '2026-07-26 18:27:56'),
(12, '2026-07-30', 2, 'PURCHASE', 11, 'Purchase Invoice: PUR-00011', 0.00, 525.00, 3948063.00, '2026-07-30 11:09:20'),
(18, '2026-08-20', 2, 'OPENING', 2, 'Opening Balance - Payable (Company owes supplier)', 0.00, 2000000.00, 5948912.15, '2026-08-20 11:55:58'),
(20, '2026-08-28', 2, 'ADJUSTMENT', 0, '[Manual Debit] abc', 500.00, 0.00, 5948412.15, '2026-08-28 13:20:43');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payments`
--

CREATE TABLE `supplier_payments` (
  `id` int NOT NULL,
  `payment_date` date NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `payment_method` enum('cash','bank') COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_payments`
--

INSERT INTO `supplier_payments` (`id`, `payment_date`, `supplier_id`, `payment_method`, `bank_account_id`, `reference_no`, `purchase_invoice_no`, `amount`, `remarks`, `created_by`, `created_at`) VALUES
(3, '2026-08-03', NULL, 'cash', 0, '', NULL, 50000.00, 'abc', 1, '2026-08-03 10:51:07'),
(4, '2026-08-10', NULL, 'cash', 0, '', NULL, 5000.00, '', 1, '2026-08-10 05:28:00');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int NOT NULL,
  `unit_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Plain text password as per requirements',
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','manager','cashier') COLLATE utf8mb4_unicode_ci DEFAULT 'cashier',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`, `last_login`, `created_at`) VALUES
(1, 'admin', 'admin123', 'Administrator', 'admin@faysalglass.com', 'admin', 1, '2026-09-02 13:00:26', '2026-06-05 11:34:23'),
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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bank_book`
--
ALTER TABLE `bank_book`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `cash_book`
--
ALTER TABLE `cash_book`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `customer_ledger`
--
ALTER TABLE `customer_ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=180;

--
-- AUTO_INCREMENT for table `customer_payments`
--
ALTER TABLE `customer_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_receipts`
--
ALTER TABLE `customer_receipts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employee_ledger`
--
ALTER TABLE `employee_ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employee_payments`
--
ALTER TABLE `employee_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- AUTO_INCREMENT for table `hold_quotations_details`
--
ALTER TABLE `hold_quotations_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hold_quotations_master`
--
ALTER TABLE `hold_quotations_master`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hold_sales_details`
--
ALTER TABLE `hold_sales_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `hold_sales_master`
--
ALTER TABLE `hold_sales_master`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `inventory_ledger`
--
ALTER TABLE `inventory_ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1059;

--
-- AUTO_INCREMENT for table `opening_stock`
--
ALTER TABLE `opening_stock`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `product_sizes`
--
ALTER TABLE `product_sizes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT for table `purchase_details`
--
ALTER TABLE `purchase_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `purchase_master`
--
ALTER TABLE `purchase_master`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `quotation_details`
--
ALTER TABLE `quotation_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `quotation_master`
--
ALTER TABLE `quotation_master`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sale_details`
--
ALTER TABLE `sale_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=589;

--
-- AUTO_INCREMENT for table `sale_master`
--
ALTER TABLE `sale_master`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `supplier_ledger`
--
ALTER TABLE `supplier_ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- Constraints for table `employee_ledger`
--
ALTER TABLE `employee_ledger`
  ADD CONSTRAINT `employee_ledger_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `employee_payments`
--
ALTER TABLE `employee_payments`
  ADD CONSTRAINT `employee_payments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `employee_salary`
--
ALTER TABLE `employee_salary`
  ADD CONSTRAINT `employee_salary_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

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
