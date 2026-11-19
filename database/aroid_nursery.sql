-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2026 at 01:58 PM
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
-- Database: `aroid_nursery`
--

-- --------------------------------------------------------

--
-- Table structure for table `inventories`
--

CREATE TABLE `inventories` (
  `id` int(10) UNSIGNED NOT NULL,
  `plant_type_id` int(10) UNSIGNED NOT NULL,
  `status` enum('quarantine','cultivation','ready') NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventories`
--

INSERT INTO `inventories` (`id`, `plant_type_id`, `status`, `quantity`, `updated_at`) VALUES
(1, 1, 'quarantine', 2, '2026-11-19 19:38:44'),
(2, 1, 'cultivation', 5, '2026-11-19 19:38:44'),
(3, 1, 'ready', 10, '2026-11-19 19:51:13'),
(4, 2, 'ready', 11, '2026-11-19 19:38:44'),
(5, 3, 'quarantine', 9, '2026-11-19 19:38:44'),
(6, 4, 'cultivation', 14, '2026-11-19 19:38:44'),
(7, 5, 'cultivation', 20, '2026-11-19 19:38:44');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` int(10) UNSIGNED NOT NULL,
  `plant_type_id` int(10) UNSIGNED NOT NULL,
  `sale_item_id` int(10) UNSIGNED DEFAULT NULL,
  `movement_type` enum('IN','SALE') NOT NULL,
  `status` enum('quarantine','cultivation','ready') NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `note` text DEFAULT NULL,
  `occurred_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_movements`
--

INSERT INTO `inventory_movements` (`id`, `plant_type_id`, `sale_item_id`, `movement_type`, `status`, `quantity`, `note`, `occurred_at`) VALUES
(1, 1, NULL, 'IN', 'quarantine', 2, 'Stok awal pengujian', '2026-11-19 19:41:03'),
(2, 1, NULL, 'IN', 'cultivation', 5, 'Stok awal pengujian', '2026-11-19 19:41:03'),
(3, 1, NULL, 'IN', 'ready', 12, 'Stok awal pengujian', '2026-11-19 19:41:03'),
(4, 2, NULL, 'IN', 'ready', 11, 'Stok awal pengujian', '2026-11-19 19:41:03'),
(5, 3, NULL, 'IN', 'quarantine', 9, 'Stok awal pengujian', '2026-11-19 19:41:03'),
(6, 4, NULL, 'IN', 'cultivation', 14, 'Stok awal pengujian', '2026-11-19 19:41:03'),
(7, 5, NULL, 'IN', 'cultivation', 20, 'Stok awal pengujian', '2026-11-19 19:41:03'),
(8, 1, 1, 'SALE', 'ready', 2, 'Penjualan melalui live Instagram', '2026-11-19 19:51:13');

-- --------------------------------------------------------

--
-- Table structure for table `plant_types`
--

CREATE TABLE `plant_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `type_name` varchar(150) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `current_price` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plant_types`
--

INSERT INTO `plant_types` (`id`, `type_name`, `photo`, `current_price`, `is_active`, `created_at`) VALUES
(1, 'Monstera Albo', NULL, 3500000, 1, '2026-11-19 19:37:47'),
(2, 'Philodendron Pink Princess', NULL, 1250000, 1, '2026-11-19 19:37:47'),
(3, 'Anthurium Crystallinum', NULL, 850000, 1, '2026-11-19 19:37:47'),
(4, 'Alocasia Frydek', NULL, 450000, 1, '2026-11-19 19:37:47'),
(5, 'Syngonium Neon Robusta', NULL, 125000, 1, '2026-11-19 19:37:47');

-- --------------------------------------------------------

--
-- Table structure for table `price_histories`
--

CREATE TABLE `price_histories` (
  `id` int(10) UNSIGNED NOT NULL,
  `plant_type_id` int(10) UNSIGNED NOT NULL,
  `old_price` bigint(20) UNSIGNED NOT NULL,
  `new_price` bigint(20) UNSIGNED NOT NULL,
  `note` text DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `price_histories`
--

INSERT INTO `price_histories` (`id`, `plant_type_id`, `old_price`, `new_price`, `note`, `changed_at`) VALUES
(1, 1, 3000000, 3500000, 'Penyesuaian harga pasar', '2026-11-19 19:43:23');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(10) UNSIGNED NOT NULL,
  `sale_code` varchar(30) NOT NULL,
  `sales_channel` enum('direct','live','social_media') NOT NULL,
  `sale_date` datetime NOT NULL DEFAULT current_timestamp(),
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `sale_code`, `sales_channel`, `sale_date`, `note`) VALUES
(1, 'TEST-001', 'live', '2026-11-19 19:51:12', 'Pengujian penjualan melalui live Instagram');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `sale_id` int(10) UNSIGNED NOT NULL,
  `plant_type_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `unit_price` bigint(20) UNSIGNED NOT NULL,
  `subtotal` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `plant_type_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 1, 2, 3500000, 7000000);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventories`
--
ALTER TABLE `inventories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_plant_status` (`plant_type_id`,`status`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_movement_sale_item` (`sale_item_id`),
  ADD KEY `fk_movement_plant` (`plant_type_id`);

--
-- Indexes for table `plant_types`
--
ALTER TABLE `plant_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `price_histories`
--
ALTER TABLE `price_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_price_plant` (`plant_type_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sale_code` (`sale_code`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_item_sale` (`sale_id`),
  ADD KEY `fk_item_plant` (`plant_type_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inventories`
--
ALTER TABLE `inventories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `plant_types`
--
ALTER TABLE `plant_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `price_histories`
--
ALTER TABLE `price_histories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventories`
--
ALTER TABLE `inventories`
  ADD CONSTRAINT `fk_inventory_plant` FOREIGN KEY (`plant_type_id`) REFERENCES `plant_types` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD CONSTRAINT `fk_movement_plant` FOREIGN KEY (`plant_type_id`) REFERENCES `plant_types` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movement_sale_item` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `price_histories`
--
ALTER TABLE `price_histories`
  ADD CONSTRAINT `fk_price_plant` FOREIGN KEY (`plant_type_id`) REFERENCES `plant_types` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `fk_item_plant` FOREIGN KEY (`plant_type_id`) REFERENCES `plant_types` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_item_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
