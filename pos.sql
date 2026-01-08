-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 04, 2026 at 08:32 AM
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
-- Database: `pos_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `created_at`) VALUES
(1, 'Beverages', '2025-12-22 02:03:56'),
(2, 'Snacks', '2025-12-22 02:03:56'),
(3, 'Dairy', '2025-12-22 02:03:56'),
(4, 'Household', '2025-12-22 02:03:56'),
(5, 'Beverages', '2025-12-22 02:05:24'),
(6, 'Snacks', '2025-12-22 02:05:24'),
(7, 'Dairy', '2025-12-22 02:05:24'),
(8, 'Household', '2025-12-22 02:05:24');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_name`, `contact_number`, `created_at`) VALUES
(1, 'Juan Dela Cruz', '09112223333', '2025-12-22 02:03:56'),
(2, 'Maria Santos', '09223334444', '2025-12-22 02:03:56'),
(5, 'micah', NULL, '2025-12-30 07:30:58'),
(6, 'as', NULL, '2025-12-30 07:48:48'),
(7, 'asd', '123', '2025-12-30 15:45:47');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `transaction_type` enum('in','out') NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`transaction_id`, `product_id`, `supplier_id`, `quantity`, `transaction_type`, `transaction_date`) VALUES
(1, 1, 1, 50, 'in', '2025-12-22 02:03:56'),
(2, 2, 1, 100, 'in', '2025-12-22 02:03:56'),
(3, 3, 2, 40, 'in', '2025-12-22 02:03:56'),
(4, 4, 2, 30, 'in', '2025-12-22 02:03:56'),
(5, 3, NULL, 5, 'out', '2025-12-22 02:03:56'),
(6, 1, 1, 50, 'in', '2025-12-22 02:05:50'),
(7, 2, 1, 100, 'in', '2025-12-22 02:05:50'),
(8, 3, 2, 40, 'in', '2025-12-22 02:05:50'),
(9, 4, 2, 30, 'in', '2025-12-22 02:05:50'),
(10, 3, NULL, 5, 'out', '2025-12-22 02:05:50'),
(11, 1, NULL, 2, 'out', '2025-12-30 07:30:58'),
(12, 6, NULL, 12, 'out', '2025-12-30 07:48:48'),
(13, 5, NULL, 2, 'out', '2025-12-30 07:48:48'),
(14, 9, NULL, 2, 'out', '2025-12-31 17:21:59');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `price`, `stock`, `created_at`) VALUES
(1, 1, 'Coca Cola 1.5L', 65.00, 48, '2025-12-22 02:03:56'),
(2, 1, 'Mineral Water 500ml', 20.00, 100, '2025-12-22 02:03:56'),
(3, 2, 'Potato Chips', 35.00, 40, '2025-12-22 02:03:56'),
(4, 3, 'Fresh Milk 1L', 75.00, 30, '2025-12-22 02:03:56'),
(5, 4, 'Laundry Detergent', 120.00, 23, '2025-12-22 02:03:56'),
(6, 1, 'Coca Cola 1.5L', 65.00, 38, '2025-12-22 02:05:37'),
(7, 1, 'Mineral Water 500ml', 20.00, 100, '2025-12-22 02:05:37'),
(8, 2, 'Potato Chips', 35.00, 40, '2025-12-22 02:05:37'),
(9, 3, 'Fresh Milk 1L', 75.00, 28, '2025-12-22 02:05:37');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sale_id`, `user_id`, `customer_id`, `total_amount`, `sale_date`) VALUES
(5, 1, 1, 135.00, '2025-12-22 02:06:27'),
(6, 1, 5, 130.00, '2025-12-30 07:30:58'),
(7, 1, 6, 1020.00, '2025-12-30 07:48:48'),
(8, 1, 7, 150.00, '2025-12-31 17:21:59');

-- --------------------------------------------------------

--
-- Table structure for table `sale_details`
--

CREATE TABLE `sale_details` (
  `sale_detail_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `product_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_details`
--

INSERT INTO `sale_details` (`sale_detail_id`, `sale_id`, `product_name`, `quantity`, `price`, `product_id`) VALUES
(1, 5, 'Coca Cola 1.5L', 1, 65.00, 1),
(2, 5, 'Potato Chips', 2, 35.00, 3),
(3, 6, 'Coca Cola 1.5L', 2, 65.00, 1),
(4, 7, 'Coca Cola 1.5L', 12, 65.00, 6),
(5, 7, 'Laundry Detergent', 2, 120.00, 5),
(6, 8, 'Fresh Milk 1L', 2, 75.00, 9);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `contact_info` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_info`, `created_at`) VALUES
(1, 'ABC Distributors', '09123456789', '2025-12-22 02:03:56'),
(2, 'Fresh Goods Supply', '09987654321', '2025-12-22 02:03:56'),
(3, 'ABC Distributors', '09123456789', '2025-12-22 02:05:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cashier') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$Wd9o834iXFefNVU4Zwtlb.pnfSsn55OiVf0tsqryRfta1Isg3UetS', 'admin', '2025-12-21 14:04:32'),
(2, 'cashier', '$2y$10$rwwgDXWc3dElOBxCKBrIW.oLv4s6ZVJEWOS7ItNXMu9V3E2Agvy6K', 'cashier', '2025-12-30 15:41:48'),
(3, 'cashier2', '$2y$10$JNfS3Ze.QcjzIVdcewr3MOlmCCxqYqVc3oOWy4dmLAMEmkO2jYgPu', 'cashier', '2025-12-30 15:46:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_sales_customer` (`customer_id`);

--
-- Indexes for table `sale_details`
--
ALTER TABLE `sale_details`
  ADD PRIMARY KEY (`sale_detail_id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sale_details`
--
ALTER TABLE `sale_details`
  MODIFY `sale_detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `sale_details`
--
ALTER TABLE `sale_details`
  ADD CONSTRAINT `sale_details_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`),
  ADD CONSTRAINT `sale_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
