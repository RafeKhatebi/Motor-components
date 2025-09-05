-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 02, 2025 at 04:13 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `motor_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'create', 'sales', 1, NULL, '{\"customer_id\":1,\"total_amount\":440,\"discount_percent\":2,\"discount_amount\":8.8,\"final_amount\":431.2,\"items_count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 12:55:28'),
(2, 1, 'create', 'sales', 2, NULL, '{\"customer_id\":null,\"total_amount\":220,\"discount_percent\":0,\"discount_amount\":0,\"final_amount\":220,\"items_count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 12:57:23'),
(3, 1, 'create', 'sales', 3, NULL, '{\"customer_id\":null,\"total_amount\":440,\"discount_percent\":0,\"discount_amount\":0,\"final_amount\":440,\"items_count\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 13:05:23'),
(4, 1, 'create', 'sales', 4, NULL, '{\"customer_id\":null,\"total_amount\":1100,\"discount_percent\":5,\"discount_amount\":55,\"final_amount\":1045,\"items_count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 13:22:10'),
(5, 1, 'create', 'sales', 5, NULL, '{\"customer_id\":1,\"total_amount\":660,\"discount_percent\":0,\"discount_amount\":0,\"final_amount\":660,\"items_count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-17 14:05:16'),
(6, 1, 'create', 'sales', 7, NULL, '{\"customer_id\":null,\"total_amount\":320,\"discount_percent\":0,\"discount_amount\":0,\"final_amount\":320,\"items_count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-18 07:54:05'),
(7, 1, 'create', 'sales', 8, NULL, '{\"customer_id\":2,\"total_amount\":440,\"discount_percent\":0,\"discount_amount\":0,\"final_amount\":440,\"items_count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-18 07:54:29'),
(8, 8, 'create', 'sales', 10, NULL, '{\"customer_id\":null,\"total_amount\":220,\"discount_percent\":0,\"discount_amount\":0,\"final_amount\":220,\"items_count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 17:39:16'),
(9, 8, 'create', 'sales', 11, NULL, '{\"customer_id\":3,\"total_amount\":520,\"discount_percent\":0,\"discount_amount\":0,\"final_amount\":520,\"items_count\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 17:39:44'),
(10, 8, 'create', 'sales', 12, NULL, '{\"customer_id\":3,\"total_amount\":300,\"discount_percent\":5,\"discount_amount\":15,\"final_amount\":285,\"items_count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-20 06:23:31'),
(11, 8, 'create', 'sales', 13, NULL, '{\"customer_id\":null,\"total_amount\":50,\"discount_percent\":0,\"discount_amount\":0,\"final_amount\":50,\"items_count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-21 08:05:59'),
(12, 8, 'create', 'sales', 14, NULL, '{\"customer_id\":2,\"total_amount\":250,\"discount_percent\":2,\"discount_amount\":5,\"final_amount\":245,\"items_count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-21 13:37:15'),
(13, 8, 'create', 'sales', 15, NULL, '{\"customer_id\":null,\"total_amount\":150,\"discount_percent\":0,\"discount_amount\":0,\"final_amount\":150,\"items_count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-21 14:02:56'),
(14, 8, 'create', 'sales', 16, NULL, '{\"customer_id\":null,\"total_amount\":120,\"discount_percent\":0,\"discount_amount\":0,\"final_amount\":120,\"items_count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-21 14:04:13'),
(15, 8, 'create', 'sales', 17, NULL, '{\"customer_id\":null,\"total_amount\":150,\"discount_percent\":0,\"discount_amount\":0,\"final_amount\":150,\"items_count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-21 14:07:16'),
(16, 8, 'create', 'sales', 18, NULL, '{\"customer_id\":2,\"total_amount\":90,\"discount_percent\":0,\"discount_amount\":0,\"final_amount\":90,\"items_count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-21 14:08:19');

-- --------------------------------------------------------

--
-- Table structure for table `barcode_scans`
--

CREATE TABLE `barcode_scans` (
  `id` int(11) NOT NULL,
  `barcode` varchar(100) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `scan_type` enum('sale','inventory','search') NOT NULL,
  `scanned_by` int(11) DEFAULT NULL,
  `scan_location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'موتور لمر', 'درجه یک', '2025-08-17 12:54:11'),
(2, 'موتور اوندا', 'اوندا  جدید', '2025-08-18 07:45:52'),
(4, 'سچرخ', 'ننن', '2025-08-19 17:36:53'),
(5, 'غلام سرور ', '123', '2025-08-20 06:18:30'),
(6, 'موتر 70', 'دو زینه', '2025-08-21 08:03:26'),
(7, 'Rafe Ahmad Khatebi', 'PopCorn', '2025-08-28 10:37:13'),
(8, 'Ali', 'نjj', '2025-08-28 14:17:55'),
(9, 'جدید جدید', 'جدید بود شد', '2025-08-28 17:52:13'),
(10, 'موتر کلان', 'موتر ', '2025-09-02 09:48:36');

-- --------------------------------------------------------

--
-- Table structure for table `compatible_parts`
--

CREATE TABLE `compatible_parts` (
  `id` int(11) NOT NULL,
  `main_product_id` int(11) DEFAULT NULL,
  `compatible_product_id` int(11) DEFAULT NULL,
  `compatibility_type` enum('exact','alternative','upgrade') DEFAULT 'alternative',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `customer_type` enum('retail','wholesale','garage','dealer') DEFAULT 'retail',
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `credit_limit` decimal(10,2) DEFAULT 0.00,
  `current_balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `address`, `customer_type`, `discount_percentage`, `credit_limit`, `current_balance`, `created_at`) VALUES
(1, 'علی احمد', '0728958423', 'Herat, Afghanistan', 'retail', 0.00, 0.00, 0.00, '2025-08-17 12:28:29'),
(2, 'شاه میر', '0798999789', 'هرات چشت', 'retail', 0.00, 0.00, 0.00, '2025-08-18 07:52:36'),
(3, 'نصیر احمد', '0798944187', 'هرات گذره', 'retail', 0.00, 0.00, 0.00, '2025-08-19 17:37:31'),
(4, 'محمد جدید', '0721958423', 'هرات', 'retail', 0.00, 0.00, 0.00, '2025-08-20 09:18:20'),
(5, 'غلام سخی', '0722958423', 'هرات شهر نو', 'retail', 0.00, 0.00, 0.00, '2025-08-21 13:39:27');

-- --------------------------------------------------------

--
-- Stand-in structure for view `dashboard_stats`
-- (See below for the actual view)
--
CREATE TABLE `dashboard_stats` (
`total_products` bigint(21)
,`total_customers` bigint(21)
,`today_sales` decimal(32,2)
,`low_stock_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `expense_transactions`
--

CREATE TABLE `expense_transactions` (
  `id` int(11) NOT NULL,
  `transaction_code` varchar(20) NOT NULL,
  `transaction_type` enum('expense','withdrawal') NOT NULL,
  `type_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `person_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_transactions`
--

INSERT INTO `expense_transactions` (`id`, `transaction_code`, `transaction_type`, `type_id`, `amount`, `person_name`, `description`, `transaction_date`, `created_at`, `updated_at`) VALUES
(2, 'TXN202508201092', 'withdrawal', 11, 200.00, 'خودم و خودم', 'هرات', '1404-05-29', '2025-08-20 13:22:23', '2025-08-20 13:22:39'),
(3, 'TXN202508212996', 'expense', 4, 470.00, 'برقی', 'ندارد', '1404-05-30', '2025-08-21 08:07:02', '2025-08-21 08:07:02'),
(4, 'TXN202508215049', 'withdrawal', 11, 120.00, 'نوید شاه', 'روز 4 شنبه', '1404-05-30', '2025-08-21 13:38:21', '2025-08-21 13:38:21'),
(5, 'TXN202508212293', 'expense', 3, 200.00, 'محمد امین', 'جدید', '1404-05-30', '2025-08-21 13:38:54', '2025-08-21 13:38:54'),
(6, 'TXN202509021421', 'expense', 2, 1200.00, 'نذیر احمد', '', '1404-06-11', '2025-09-02 10:04:58', '2025-09-02 10:09:38');

-- --------------------------------------------------------

--
-- Table structure for table `financial_transactions`
--

CREATE TABLE `financial_transactions` (
  `id` int(11) NOT NULL,
  `transaction_type` enum('sale','purchase','sale_return','purchase_return') NOT NULL,
  `type_id` int(11) NOT NULL,
  `reference_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `motor_brands`
--

CREATE TABLE `motor_brands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `motor_brands`
--

INSERT INTO `motor_brands` (`id`, `name`, `logo_path`, `created_at`) VALUES
(1, 'Honda', NULL, '2025-09-02 13:07:10'),
(2, 'Yamaha', NULL, '2025-09-02 13:07:10'),
(3, 'Suzuki', NULL, '2025-09-02 13:07:10'),
(4, 'Kawasaki', NULL, '2025-09-02 13:07:10'),
(5, 'Bajaj', NULL, '2025-09-02 13:07:10'),
(6, 'TVS', NULL, '2025-09-02 13:07:10'),
(7, 'Hero', NULL, '2025-09-02 13:07:10'),
(8, 'Royal Enfield', NULL, '2025-09-02 13:07:10');

-- --------------------------------------------------------

--
-- Table structure for table `motor_models`
--

CREATE TABLE `motor_models` (
  `id` int(11) NOT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `year_from` int(11) DEFAULT NULL,
  `year_to` int(11) DEFAULT NULL,
  `engine_size` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `oem_number` varchar(100) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `motor_model` varchar(100) DEFAULT NULL,
  `year_from` int(11) DEFAULT NULL,
  `year_to` int(11) DEFAULT NULL,
  `weight` decimal(8,2) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `warranty_months` int(11) DEFAULT 0,
  `shelf_location` varchar(50) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `part_type` enum('original','aftermarket','used') DEFAULT 'aftermarket',
  `country_origin` varchar(50) DEFAULT NULL,
  `buy_price` decimal(10,2) NOT NULL,
  `sell_price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `min_stock` int(11) DEFAULT 5,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category_id`, `code`, `oem_number`, `brand`, `motor_model`, `year_from`, `year_to`, `weight`, `dimensions`, `warranty_months`, `shelf_location`, `barcode`, `image_path`, `part_type`, `country_origin`, `buy_price`, `sell_price`, `stock_quantity`, `min_stock`, `description`, `created_at`) VALUES
(1, 'قاب زنحیر', 1, 'PRD-0001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'MP00000001', NULL, 'aftermarket', NULL, 150.00, 220.00, 3, 5, 'ندارد', '2025-08-17 12:54:52'),
(2, 'گیر بکس', 2, 'PRD-0002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'MP00000002', NULL, 'aftermarket', NULL, 120.00, 160.00, 1, 5, 'درجه یک', '2025-08-18 07:48:03'),
(3, 'کریم شاه', 2, '0001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'MP00000003', NULL, 'aftermarket', NULL, 122.00, 132.00, 12, 5, '', '2025-08-18 08:58:33'),
(4, 'یی', NULL, '0002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'MP00000004', NULL, 'aftermarket', NULL, 212.00, 212.00, 15, 5, NULL, '2025-08-18 08:59:05'),
(5, 'چراغ موتر لمر1', 1, '0003', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'MP00000005', NULL, 'aftermarket', NULL, 120.00, 150.00, 4, 5, 'ندارد', '2025-08-19 17:36:31'),
(6, 'سیم کلچ', 2, '0004', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'MP00000006', NULL, 'aftermarket', NULL, 40.00, 50.00, 6, 5, 'ندارد', '2025-08-21 08:05:19'),
(7, 'گرری درجه دار', 6, '0005', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'MP00000007', NULL, 'aftermarket', NULL, 100.00, 120.00, 5, 5, 'ندارد', '2025-08-28 17:36:53'),
(8, 'تایر موتر', 4, '0006', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'MP00000008', NULL, 'aftermarket', NULL, 70.00, 100.00, 11, 5, 'ندارد داد', '2025-08-28 17:42:25'),
(10, 'قاب زنحیر21', 4, '0007', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'MP00000010', NULL, 'aftermarket', NULL, 40.00, 120.00, 3, 8, '', '2025-09-02 11:57:03');

-- --------------------------------------------------------

--
-- Table structure for table `product_barcodes`
--

CREATE TABLE `product_barcodes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `barcode` varchar(100) NOT NULL,
  `barcode_type` enum('EAN13','CODE128','QR') DEFAULT 'CODE128',
  `is_primary` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_barcodes`
--

INSERT INTO `product_barcodes` (`id`, `product_id`, `barcode`, `barcode_type`, `is_primary`, `created_at`) VALUES
(1, 1, 'MP00000001', 'CODE128', 1, '2025-09-02 13:42:28'),
(2, 2, 'MP00000002', 'CODE128', 1, '2025-09-02 13:42:28'),
(3, 3, 'MP00000003', 'CODE128', 1, '2025-09-02 13:42:28'),
(4, 4, 'MP00000004', 'CODE128', 1, '2025-09-02 13:42:28'),
(5, 5, 'MP00000005', 'CODE128', 1, '2025-09-02 13:42:28'),
(6, 6, 'MP00000006', 'CODE128', 1, '2025-09-02 13:42:28'),
(7, 7, 'MP00000007', 'CODE128', 1, '2025-09-02 13:42:28'),
(8, 8, 'MP00000008', 'CODE128', 1, '2025-09-02 13:42:28'),
(9, 10, 'MP00000010', 'CODE128', 1, '2025-09-02 13:42:28');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `alt_text` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_prices`
--

CREATE TABLE `product_prices` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_type` enum('retail','wholesale','garage','dealer') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `min_quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_prices`
--

INSERT INTO `product_prices` (`id`, `product_id`, `customer_type`, `price`, `min_quantity`, `created_at`, `updated_at`) VALUES
(1, 1, 'wholesale', 198.00, 5, '2025-09-02 13:45:46', '2025-09-02 13:45:46'),
(2, 2, 'wholesale', 144.00, 5, '2025-09-02 13:45:46', '2025-09-02 13:45:46'),
(3, 3, 'wholesale', 118.80, 5, '2025-09-02 13:45:46', '2025-09-02 13:45:46'),
(4, 4, 'wholesale', 190.80, 5, '2025-09-02 13:45:46', '2025-09-02 13:45:46'),
(5, 5, 'wholesale', 135.00, 5, '2025-09-02 13:45:46', '2025-09-02 13:45:46');

-- --------------------------------------------------------

--
-- Table structure for table `product_sequence`
--

CREATE TABLE `product_sequence` (
  `id` int(11) NOT NULL DEFAULT 1,
  `next_value` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_sequence`
--

INSERT INTO `product_sequence` (`id`, `next_value`) VALUES
(1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_type` enum('cash','credit') DEFAULT 'cash',
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `remaining_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('paid','partial','unpaid') DEFAULT 'paid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('completed','returned') DEFAULT 'completed',
  `return_reason` text DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `returned_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `supplier_id`, `total_amount`, `payment_type`, `paid_amount`, `remaining_amount`, `payment_status`, `created_at`, `status`, `return_reason`, `returned_at`, `returned_by`) VALUES
(1, 1, 1800.00, 'cash', 1800.00, 0.00, 'paid', '2025-08-17 13:19:38', 'completed', NULL, NULL, NULL),
(2, 2, 480.00, 'cash', 360.00, 0.00, 'paid', '2025-08-18 07:50:52', 'completed', NULL, NULL, NULL),
(3, 2, 2544.00, 'cash', 2544.00, 0.00, 'paid', '2025-08-18 08:59:05', 'completed', NULL, NULL, NULL),
(4, 2, 480.00, 'cash', 480.00, 0.00, 'paid', '2025-08-21 13:37:39', 'completed', NULL, NULL, NULL),
(5, 3, 1440.00, 'cash', 1440.00, 0.00, 'paid', '2025-08-23 07:42:01', 'completed', NULL, NULL, NULL),
(6, 3, 3200.00, 'cash', 2800.00, 0.00, 'paid', '2025-09-02 10:04:22', 'completed', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `product_id`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 1, 1, 12, 150.00, 1800.00),
(3, 2, 1, 4, 120.00, 480.00),
(4, 3, 4, 12, 212.00, 2544.00),
(5, 4, 6, 12, 40.00, 480.00),
(6, 5, 5, 12, 120.00, 1440.00),
(9, 6, 4, 10, 100.00, 1000.00),
(10, 6, 8, 10, 220.00, 2200.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_payments`
--

CREATE TABLE `purchase_payments` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','bank','check') DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `payment_type` enum('cash','credit') DEFAULT 'cash',
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `remaining_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('paid','partial','unpaid') DEFAULT 'paid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('completed','returned') DEFAULT 'completed',
  `return_reason` text DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `returned_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `customer_id`, `total_amount`, `discount`, `final_amount`, `payment_type`, `paid_amount`, `remaining_amount`, `payment_status`, `created_at`, `status`, `return_reason`, `returned_at`, `returned_by`) VALUES
(3, NULL, 440.00, 0.00, 440.00, 'credit', 0.00, 95.00, 'partial', '2025-08-17 13:05:23', 'completed', NULL, NULL, NULL),
(4, NULL, 1100.00, 55.00, 1045.00, 'cash', 1045.00, 0.00, 'paid', '2025-08-17 13:22:10', 'completed', NULL, NULL, NULL),
(5, 1, 660.00, 0.00, 660.00, 'cash', 660.00, 0.00, 'paid', '2025-08-17 14:05:16', 'completed', NULL, NULL, NULL),
(7, NULL, 320.00, 0.00, 320.00, 'cash', 320.00, 0.00, 'paid', '2025-08-18 07:54:05', 'completed', NULL, NULL, NULL),
(8, 2, 440.00, 0.00, 440.00, 'cash', 440.00, 0.00, 'paid', '2025-08-18 07:54:29', 'completed', NULL, NULL, NULL),
(10, NULL, 220.00, 0.00, 220.00, 'cash', 220.00, 0.00, 'paid', '2025-08-19 17:39:16', 'completed', NULL, NULL, NULL),
(11, 3, 520.00, 0.00, 520.00, 'cash', 520.00, 0.00, 'paid', '2025-08-19 17:39:44', 'completed', NULL, NULL, NULL),
(12, 3, 300.00, 15.00, 285.00, 'cash', 285.00, 0.00, 'paid', '2025-08-20 06:23:31', 'completed', NULL, NULL, NULL),
(13, NULL, 50.00, 0.00, 50.00, 'cash', 50.00, 0.00, 'paid', '2025-08-21 08:05:59', 'completed', NULL, NULL, NULL),
(14, 2, 250.00, 5.00, 245.00, 'cash', 245.00, 0.00, 'paid', '2025-08-21 13:37:14', 'completed', NULL, NULL, NULL),
(15, NULL, 150.00, 0.00, 150.00, 'cash', 150.00, 0.00, 'paid', '2025-08-21 14:02:56', 'completed', NULL, NULL, NULL),
(16, NULL, 120.00, 0.00, 120.00, 'cash', 120.00, 0.00, 'paid', '2025-08-21 14:04:13', 'completed', NULL, NULL, NULL),
(17, NULL, 150.00, 0.00, 150.00, 'cash', 150.00, 0.00, 'paid', '2025-08-21 14:07:16', 'completed', NULL, NULL, NULL),
(18, 2, 90.00, 0.00, 90.00, 'cash', 90.00, 0.00, 'paid', '2025-08-21 14:08:19', 'completed', NULL, NULL, NULL),
(19, 2, 320.00, 0.00, 320.00, 'cash', 320.00, 0.00, 'paid', '2025-08-23 07:54:20', 'completed', NULL, NULL, NULL),
(20, 1, 250.00, 5.00, 245.00, 'credit', 100.00, 145.00, 'partial', '2025-08-23 07:54:58', 'completed', NULL, NULL, NULL),
(21, 5, 750.00, 0.00, 750.00, 'cash', 750.00, 0.00, 'paid', '2025-09-02 09:59:35', 'completed', NULL, NULL, NULL),
(22, 4, 1298.00, 54.30, 593.19, 'cash', 1031.70, 0.00, 'paid', '2025-09-02 10:00:15', 'completed', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_price`, `total_price`) VALUES
(3, 3, 1, 1, 220.00, 220.00),
(4, 3, 1, 1, 220.00, 220.00),
(5, 4, 1, 5, 220.00, 1100.00),
(6, 5, 1, 3, 220.00, 660.00),
(7, 7, 2, 2, 160.00, 320.00),
(10, 8, 1, 2, 220.00, 440.00),
(11, 10, 1, 1, 220.00, 220.00),
(12, 11, 1, 1, 220.00, 220.00),
(13, 11, 5, 2, 150.00, 300.00),
(14, 12, 5, 2, 150.00, 300.00),
(15, 13, 6, 1, 50.00, 50.00),
(16, 14, 6, 5, 50.00, 250.00),
(17, 15, 6, 5, 30.00, 150.00),
(18, 16, 5, 1, 120.00, 120.00),
(19, 17, 6, 5, 30.00, 150.00),
(20, 18, 6, 3, 30.00, 90.00),
(21, 19, 2, 2, 160.00, 320.00),
(22, 20, 6, 5, 50.00, 250.00),
(23, 21, 5, 5, 150.00, 750.00),
(26, 22, 5, 3, 150.00, 450.00),
(27, 22, 4, 4, 212.00, 848.00);

-- --------------------------------------------------------

--
-- Table structure for table `sale_payments`
--

CREATE TABLE `sale_payments` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','bank','check') DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_payments`
--

INSERT INTO `sale_payments` (`id`, `sale_id`, `amount`, `payment_date`, `payment_method`, `notes`, `created_at`) VALUES
(1, 3, 200.00, '1404-05-27', 'cash', 'بار اول 200', '2025-08-18 07:56:16'),
(2, 3, 100.00, '1404-05-30', 'cash', 'دفعه 2', '2025-08-21 08:12:02'),
(3, 3, 45.00, '1404-05-31', 'cash', 'nhh', '2025-08-22 15:42:41');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(255) DEFAULT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'shop_name', 'فروشگاه برادران نورازد', '2025-09-02 10:54:49'),
(2, 'shop_phone', '0799024122', '2025-08-21 08:17:32'),
(3, 'shop_address', ' هرات گذره نارسیده به پل هوای دست چپ', '2025-08-21 08:17:32'),
(4, 'currency', 'afghani', '2025-08-17 14:08:07'),
(5, 'language', 'fa', '2025-08-22 14:06:34'),
(6, 'date_format', 'jalali', '2025-08-18 08:00:40'),
(7, 'auto_backup', '1', '2025-08-17 14:08:07'),
(8, 'notifications', '1', '2025-08-17 14:08:07'),
(25, 'shop_logo', 'uploads/logos/logo_1756810489.jpg', '2025-09-02 10:54:49'),
(208, 'min_profit_margin', '5', '2025-08-21 14:02:26');

-- --------------------------------------------------------

--
-- Table structure for table `super_admin`
--

CREATE TABLE `super_admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `master_key` varchar(255) NOT NULL,
  `hardware_id` varchar(255) NOT NULL,
  `status` enum('active','disabled') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `super_admin`
--

INSERT INTO `super_admin` (`id`, `username`, `password`, `full_name`, `master_key`, `hardware_id`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'superadmin', '$2y$10$wjYQABponkGQUowZKvKIseAoeyexV8NMD56603kSgwiy5bQlgjzuq', 'سوپر ادمین سیستم', '389571d45a36a4b891e933c3d18767a585b292e1949f7b7f883dc655ba316662', '2d9bb11e98160d0fd2cf8448f20a47176da2dc07f50aee378bc99e811090f2df', 'active', '2025-08-20 00:19:01', '2025-08-19 19:47:30', '2025-08-19 19:49:01');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `phone`, `address`, `created_at`) VALUES
(1, 'Rafe Ahmad Khatebi', '0728958423', 'Herat, Afghanistan\r\nHerat', '2025-08-17 13:18:54'),
(2, 'غلام سرور', '0721158223', 'Herat, Afghanistan\r\nHerat', '2025-08-18 07:55:35'),
(3, 'حاجی سخی نورازی', '0723958423', '', '2025-08-21 13:55:38');

-- --------------------------------------------------------

--
-- Table structure for table `system_license`
--

CREATE TABLE `system_license` (
  `id` int(11) NOT NULL,
  `hardware_id` varchar(255) NOT NULL,
  `license_key` varchar(500) NOT NULL,
  `status` enum('active','expired','disabled') DEFAULT 'active',
  `issued_date` datetime NOT NULL,
  `expiry_date` datetime NOT NULL,
  `max_users` int(11) DEFAULT 5,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_license`
--

INSERT INTO `system_license` (`id`, `hardware_id`, `license_key`, `status`, `issued_date`, `expiry_date`, `max_users`, `features`, `created_at`, `updated_at`) VALUES
(1, '2d9bb11e98160d0fd2cf8448f20a47176da2dc07f50aee378bc99e811090f2df', 'l+axJmRTV5aGufE9WFOhvXJhaVhtZnAyZUJvYjRxUTljbVNQK25sOUhoUVBkUXBWV3VTNWFWVjYyeGFBRTRiMmxOLzEyZXlRNHdaVlcxRWt1VklkWmZybGluRlNZK2hkSE5Ma1M1V1dXOER4RmtFSDNTZzFPWjJob3NIektCTEh0akdrYk1hU0pNVnlyVzI1c1JYRnI4YmVyTnpkVVRFQmRUKzI2K2VMTWNraG5CTWEyRUtvM1FHMU03R1ZadzJKeC9md1dsaUtFdGpjOVRPVjRyc0tqck5IYVU4ZmF2aGtjUy9vZEhEMWhCNUJ6WkZybzdqcnpzdFdWY1k9', 'active', '2025-08-19 21:15:26', '2026-08-19 21:15:26', 5, '[\"advanced_reports\"]', '2025-08-19 19:15:36', '2025-08-19 19:15:36');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaction_types`
--

CREATE TABLE `transaction_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('expense','withdrawal') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaction_types`
--

INSERT INTO `transaction_types` (`id`, `name`, `type`, `created_at`) VALUES
(1, 'خرید کالا', 'expense', '2025-08-20 12:03:31'),
(2, 'حقوق پرسنل', 'expense', '2025-08-20 12:03:31'),
(3, 'اجاره مغازه', 'expense', '2025-08-20 12:03:31'),
(4, 'قبض برق', 'expense', '2025-08-20 12:03:31'),
(5, 'قبض آب', 'expense', '2025-08-20 12:03:31'),
(6, 'قبض گاز', 'expense', '2025-08-20 12:03:31'),
(7, 'تعمیرات', 'expense', '2025-08-20 12:03:31'),
(8, 'حمل و نقل', 'expense', '2025-08-20 12:03:31'),
(9, 'تبلیغات', 'expense', '2025-08-20 12:03:31'),
(10, 'سایر هزینه‌ها', 'expense', '2025-08-20 12:03:31'),
(11, 'برداشت شخصی', 'withdrawal', '2025-08-20 12:03:31'),
(12, 'وام', 'withdrawal', '2025-08-20 12:03:31'),
(13, 'سرمایه‌گذاری', 'withdrawal', '2025-08-20 12:03:31'),
(14, 'سایر برداشت‌ها', 'withdrawal', '2025-08-20 12:03:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','manager','employee') DEFAULT 'employee',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `created_at`) VALUES
(8, 'admin', '$2y$10$AdhGNu1BidvDidK9ZYkSd.RiJYsLZFTRdymm00AyFhwS4nxfBOohq', 'عزیز احمد نورزاد', 'admin', '2025-08-18 09:20:19'),
(10, 'manger', '$2y$10$Fx9VPZTmzi5u2CNwyyZ4Ce.EjJsvvTkHHlp1paFKTZ1nnWNdCpnwq', 'Nasir ahmad', 'manager', '2025-08-19 17:48:01'),
(11, 'student', '$2y$10$evs52lHcV.Fm4dbq5Gi7B.daEuVYiOehxHKUmejCfv5QdIFk3IBfe', 'student', 'employee', '2025-08-21 13:54:06');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `volume_discounts`
--

CREATE TABLE `volume_discounts` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `min_quantity` int(11) NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL,
  `customer_type` enum('retail','wholesale','garage','dealer') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `volume_discounts`
--

INSERT INTO `volume_discounts` (`id`, `product_id`, `min_quantity`, `discount_percentage`, `customer_type`, `created_at`) VALUES
(1, 4, 10, 5.00, NULL, '2025-09-02 13:45:46'),
(2, 1, 10, 5.00, NULL, '2025-09-02 13:45:46'),
(3, 5, 10, 5.00, NULL, '2025-09-02 13:45:46');

-- --------------------------------------------------------

--
-- Table structure for table `warranties`
--

CREATE TABLE `warranties` (
  `id` int(11) NOT NULL,
  `sale_item_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `warranty_start` date NOT NULL,
  `warranty_end` date NOT NULL,
  `warranty_months` int(11) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `warranty_type` enum('manufacturer','shop','extended') DEFAULT 'shop',
  `status` enum('active','expired','claimed','void') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `warranties`
--

INSERT INTO `warranties` (`id`, `sale_item_id`, `product_id`, `customer_id`, `warranty_start`, `warranty_end`, `warranty_months`, `serial_number`, `warranty_type`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 1, NULL, '2025-08-17', '2026-02-17', 6, NULL, 'shop', 'active', NULL, '2025-09-02 13:45:58', '2025-09-02 13:45:58'),
(2, 4, 1, NULL, '2025-08-17', '2026-02-17', 6, NULL, 'shop', 'active', NULL, '2025-09-02 13:45:58', '2025-09-02 13:45:58'),
(3, 5, 1, NULL, '2025-08-17', '2026-02-17', 6, NULL, 'shop', 'active', NULL, '2025-09-02 13:45:58', '2025-09-02 13:45:58'),
(4, 6, 1, 1, '2025-08-17', '2026-02-17', 6, NULL, 'shop', 'active', NULL, '2025-09-02 13:45:58', '2025-09-02 13:45:58'),
(5, 7, 2, NULL, '2025-08-18', '2026-02-18', 6, NULL, 'shop', 'active', NULL, '2025-09-02 13:45:58', '2025-09-02 13:45:58');

-- --------------------------------------------------------

--
-- Table structure for table `warranty_claims`
--

CREATE TABLE `warranty_claims` (
  `id` int(11) NOT NULL,
  `warranty_id` int(11) NOT NULL,
  `claim_date` date NOT NULL,
  `issue_description` text NOT NULL,
  `claim_type` enum('repair','replace','refund') NOT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `resolution` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warranty_history`
--

CREATE TABLE `warranty_history` (
  `id` int(11) NOT NULL,
  `warranty_id` int(11) NOT NULL,
  `action` enum('created','claimed','repaired','replaced','expired','voided') NOT NULL,
  `description` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `dashboard_stats`
--
DROP TABLE IF EXISTS `dashboard_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `dashboard_stats`  AS SELECT (select count(0) from `products`) AS `total_products`, (select count(0) from `customers`) AS `total_customers`, (select coalesce(sum(`sales`.`final_amount`),0) from `sales` where cast(`sales`.`created_at` as date) = curdate() and (`sales`.`status` is null or `sales`.`status` <> 'returned')) AS `today_sales`, (select count(0) from `products` where `products`.`stock_quantity` <= `products`.`min_stock`) AS `low_stock_count` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_table` (`table_name`),
  ADD KEY `idx_audit_date` (`created_at`),
  ADD KEY `idx_audit_log_date_user` (`created_at`,`user_id`);

--
-- Indexes for table `barcode_scans`
--
ALTER TABLE `barcode_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `scanned_by` (`scanned_by`),
  ADD KEY `idx_barcode_scans_barcode` (`barcode`),
  ADD KEY `idx_barcode_scans_date` (`created_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `compatible_parts`
--
ALTER TABLE `compatible_parts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_compatible_main` (`main_product_id`),
  ADD KEY `idx_compatible_alt` (`compatible_product_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customers_phone` (`phone`),
  ADD KEY `idx_customers_type` (`customer_type`);

--
-- Indexes for table `expense_transactions`
--
ALTER TABLE `expense_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_code` (`transaction_code`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `financial_transactions`
--
ALTER TABLE `financial_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `motor_brands`
--
ALTER TABLE `motor_brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `motor_models`
--
ALTER TABLE `motor_models`
  ADD PRIMARY KEY (`id`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_stock` (`stock_quantity`),
  ADD KEY `idx_products_code` (`code`),
  ADD KEY `idx_products_stock_min` (`stock_quantity`,`min_stock`),
  ADD KEY `idx_products_oem` (`oem_number`),
  ADD KEY `idx_products_brand` (`brand`),
  ADD KEY `idx_products_model` (`motor_model`),
  ADD KEY `idx_products_barcode` (`barcode`);

--
-- Indexes for table `product_barcodes`
--
ALTER TABLE `product_barcodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `idx_product_barcodes_barcode` (`barcode`),
  ADD KEY `idx_product_barcodes_product` (`product_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_prices`
--
ALTER TABLE `product_prices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_customer_type` (`product_id`,`customer_type`),
  ADD KEY `idx_product_prices_type` (`customer_type`);

--
-- Indexes for table `product_sequence`
--
ALTER TABLE `product_sequence`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `returned_by` (`returned_by`),
  ADD KEY `idx_purchases_supplier` (`supplier_id`),
  ADD KEY `idx_purchases_date` (`created_at`),
  ADD KEY `idx_purchases_supplier_date` (`supplier_id`,`created_at`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_purchase_items_purchase` (`purchase_id`),
  ADD KEY `idx_purchase_items_product` (`product_id`);

--
-- Indexes for table `purchase_payments`
--
ALTER TABLE `purchase_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_id` (`purchase_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `returned_by` (`returned_by`),
  ADD KEY `idx_sales_customer` (`customer_id`),
  ADD KEY `idx_sales_date` (`created_at`),
  ADD KEY `idx_sales_date_status` (`created_at`,`status`),
  ADD KEY `idx_sales_customer_date` (`customer_id`,`created_at`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sale_items_sale` (`sale_id`),
  ADD KEY `idx_sale_items_product` (`product_id`),
  ADD KEY `idx_sale_items_product_quantity` (`product_id`,`quantity`);

--
-- Indexes for table `sale_payments`
--
ALTER TABLE `sale_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `super_admin`
--
ALTER TABLE `super_admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_license`
--
ALTER TABLE `system_license`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hardware_id` (`hardware_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `transaction_types`
--
ALTER TABLE `transaction_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sessions_user` (`user_id`),
  ADD KEY `idx_sessions_activity` (`last_activity`);

--
-- Indexes for table `volume_discounts`
--
ALTER TABLE `volume_discounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_volume_discounts_qty` (`min_quantity`);

--
-- Indexes for table `warranties`
--
ALTER TABLE `warranties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_item_id` (`sale_item_id`),
  ADD KEY `idx_warranties_product` (`product_id`),
  ADD KEY `idx_warranties_customer` (`customer_id`),
  ADD KEY `idx_warranties_status` (`status`),
  ADD KEY `idx_warranties_dates` (`warranty_start`,`warranty_end`);

--
-- Indexes for table `warranty_claims`
--
ALTER TABLE `warranty_claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `warranty_id` (`warranty_id`),
  ADD KEY `resolved_by` (`resolved_by`),
  ADD KEY `idx_warranty_claims_status` (`status`),
  ADD KEY `idx_warranty_claims_date` (`claim_date`);

--
-- Indexes for table `warranty_history`
--
ALTER TABLE `warranty_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `warranty_id` (`warranty_id`),
  ADD KEY `performed_by` (`performed_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `barcode_scans`
--
ALTER TABLE `barcode_scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `compatible_parts`
--
ALTER TABLE `compatible_parts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `expense_transactions`
--
ALTER TABLE `expense_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `financial_transactions`
--
ALTER TABLE `financial_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `motor_brands`
--
ALTER TABLE `motor_brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `motor_models`
--
ALTER TABLE `motor_models`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_barcodes`
--
ALTER TABLE `product_barcodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_prices`
--
ALTER TABLE `product_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `purchase_payments`
--
ALTER TABLE `purchase_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `sale_payments`
--
ALTER TABLE `sale_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=257;

--
-- AUTO_INCREMENT for table `super_admin`
--
ALTER TABLE `super_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_license`
--
ALTER TABLE `system_license`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaction_types`
--
ALTER TABLE `transaction_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `volume_discounts`
--
ALTER TABLE `volume_discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `warranties`
--
ALTER TABLE `warranties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `warranty_claims`
--
ALTER TABLE `warranty_claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `warranty_history`
--
ALTER TABLE `warranty_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barcode_scans`
--
ALTER TABLE `barcode_scans`
  ADD CONSTRAINT `barcode_scans_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `barcode_scans_ibfk_2` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `compatible_parts`
--
ALTER TABLE `compatible_parts`
  ADD CONSTRAINT `compatible_parts_ibfk_1` FOREIGN KEY (`main_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `compatible_parts_ibfk_2` FOREIGN KEY (`compatible_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expense_transactions`
--
ALTER TABLE `expense_transactions`
  ADD CONSTRAINT `expense_transactions_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `transaction_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `financial_transactions`
--
ALTER TABLE `financial_transactions`
  ADD CONSTRAINT `financial_transactions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `financial_transactions_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `transaction_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `motor_models`
--
ALTER TABLE `motor_models`
  ADD CONSTRAINT `motor_models_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `motor_brands` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_barcodes`
--
ALTER TABLE `product_barcodes`
  ADD CONSTRAINT `product_barcodes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_prices`
--
ALTER TABLE `product_prices`
  ADD CONSTRAINT `product_prices_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `purchase_payments`
--
ALTER TABLE `purchase_payments`
  ADD CONSTRAINT `purchase_payments_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `sale_payments`
--
ALTER TABLE `sale_payments`
  ADD CONSTRAINT `sale_payments_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `volume_discounts`
--
ALTER TABLE `volume_discounts`
  ADD CONSTRAINT `volume_discounts_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `warranties`
--
ALTER TABLE `warranties`
  ADD CONSTRAINT `warranties_ibfk_1` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warranties_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `warranties_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `warranty_claims`
--
ALTER TABLE `warranty_claims`
  ADD CONSTRAINT `warranty_claims_ibfk_1` FOREIGN KEY (`warranty_id`) REFERENCES `warranties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warranty_claims_ibfk_2` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `warranty_history`
--
ALTER TABLE `warranty_history`
  ADD CONSTRAINT `warranty_history_ibfk_1` FOREIGN KEY (`warranty_id`) REFERENCES `warranties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warranty_history_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
