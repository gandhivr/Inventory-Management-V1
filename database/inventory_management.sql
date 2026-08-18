-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 21, 2025 at 07:33 PM
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
-- Database: `inventory_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `buyer_profile`
--

CREATE TABLE `buyer_profile` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buyer_profile`
--

INSERT INTO `buyer_profile` (`id`, `user_id`, `address`, `phone`) VALUES
(1, 7, 'shrkhej', '1122334455'),
(2, 9, 'patan', '9265796638'),
(5, 17, 'uk gandhi', '9925457204');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`) VALUES
(3, 9, 5, 3, '2025-09-25 06:25:13');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Electronics', 'Electronic devices and accessories', '2025-09-29 16:35:21'),
(2, 'Clothing', 'Apparel and fashion items', '2025-09-29 16:35:21'),
(3, 'Books', 'Books and educational materials', '2025-09-29 16:35:21'),
(4, 'Home & Garden', 'Home improvement and garden supplies', '2025-09-29 16:35:21'),
(5, 'Sports', 'Sports equipment and accessories', '2025-09-29 16:35:21'),
(6, 'Food & Beverages', 'Food items and beverages', '2025-09-29 16:35:21'),
(7, 'Beauty & Health', 'Beauty and health products', '2025-09-29 16:35:21'),
(8, 'Automotive', 'Car and vehicle accessories', '2025-09-29 16:35:21');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `created_at`) VALUES
(1, 9, 10000.00, 'completed', '2025-09-25 01:02:14'),
(2, 11, 5000.00, 'completed', '2025-09-26 04:00:31'),
(4, 17, 80.00, 'completed', '2025-11-10 15:52:25'),
(5, 17, 100.00, 'completed', '2025-11-10 15:59:09'),
(6, 11, 100.00, 'completed', '2025-11-15 17:31:44');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 3, 1, 10000.00),
(2, 2, 5, 1, 5000.00),
(5, 4, 9, 1, 80.00),
(6, 5, 15, 1, 100.00),
(7, 6, 15, 1, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `supplier_id`, `stock_quantity`, `created_at`, `image_path`, `is_deleted`, `deleted_at`) VALUES
(3, 'Apple Iphone 17', 'Apple Company', 10000.00, 'uploads/products/68d42c2b1aed1.jpeg', 8, 99, '2025-09-24 17:36:43', 'uploads/products/68dab980e1e76.jpeg', 0, NULL),
(5, 'samsung s24 ultra', 'Best Phone Ever', 5000.00, 'uploads/products/68d49d5b57c56.jpeg', 8, 99, '2025-09-25 01:39:39', 'uploads/products/68dab97311da3.jpeg', 0, NULL),
(6, 'laptop beg', 'it is safe bag which can protect are laptop', 70.00, 'uploads/products/68dacc2944845.jpeg', 13, 100, '2025-09-29 18:12:57', 'uploads/products/68dad416a15b4.jpeg', 0, NULL),
(7, 'gamming chair', 'it is working and gamming chair', 80.00, 'uploads/products/68dacc60acf94.jpeg', 13, 100, '2025-09-29 18:13:52', 'uploads/products/68dad42677ba2.jpeg', 0, NULL),
(8, 'multi laptop charger(asus)', 'with this chrger we can charge any laptop of asus', 50.00, 'uploads/products/68dacc94cbf26.jpeg', 8, 99, '2025-09-29 18:14:44', 'uploads/products/68dad436e51b1.jpeg', 0, NULL),
(9, 'Amazon Hedephone', 'Headphones are personal audio devices, essentially miniature speakers that fit over or into the ears, converting electrical signals into sound waves for private listening. They are used with various electronic devices to listen to music, communicate, or focus on audio without disturbing others. Headphones come in several forms, including over-ear and in-ear types, and can be connected via cables or wirelessly using Bluetooth.', 80.00, 'uploads/products/68dacd4285a3f.jpeg', 14, 99, '2025-09-29 18:17:38', 'uploads/products/68dad450afb96.jpeg', 0, NULL),
(10, 'Multi Tasking Table', 'multi tasking', 75.00, 'uploads/products/68dad05c6ab20.jpeg', 14, 100, '2025-09-29 18:30:52', 'uploads/products/68dad47763263.jpeg', 0, NULL),
(12, 'macbook', 'apple Product', 100.00, 'uploads/products/68dad10d1ceb5.jpeg', 14, 98, '2025-09-29 18:33:49', 'uploads/products/68dad4608a873.jpeg', 0, NULL),
(15, 'Mobile', 'lsjlJXN', 100.00, 'uploads/products/68e0b7e6e5bba.jpeg', 15, 98, '2025-10-04 06:00:06', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `supplier_profile`
--

CREATE TABLE `supplier_profile` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_profile`
--

INSERT INTO `supplier_profile` (`id`, `user_id`, `company_name`, `address`, `phone`) VALUES
(3, 13, 'Effortless Supply', 'nqwlkndnalk', '09173142547'),
(4, 8, 'demo Company', 'demo address', '4455667788'),
(5, 14, 'God Life', 'heven', '9988776655'),
(6, 15, 'harsh & sons', 'Anupam Socity Vibhag-1  B-33 jodhpur char rasta ,Ahmedabad', '09173142547'),
(7, 18, 'Charbhuja Special', 'Sankhari', '5678493211'),
(14, 25, 'Shinchan Creation', 'Tokito', '0099887766');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','supplier','buyer') NOT NULL,
  `status` enum('active','blocked') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `username`, `password`, `role`, `status`, `created_at`, `deleted_at`) VALUES
(1, '', 'admin@admin.com', 'admin', '$2y$10$dRyXVFwCTWna6MsgbGqXC.Oe4vtFHaq0Sc/HmhsNFUqNcXS9f8ADi', 'admin', 'active', '2025-09-23 03:26:44', NULL),
(7, 'Dhruv Joshi', 'dhruv@gmail.com', 'dhruv joshi', '$2y$10$LeGJp7.5zQvNha0WGxrUq.ykv6q8S1jWHZRc5FsFeOPaXTv86/jmu', 'buyer', '', '2025-09-23 16:15:33', '2025-11-19 10:45:24'),
(8, 'Doremon', 'demo@gmail.com', 'demo', '$2y$10$AVn7pLzaMFwzV93qV//AGe2s8UOM4Fv/JacdSZXZ7xz..6yN.Q7eW', 'supplier', 'active', '2025-09-23 16:24:39', NULL),
(9, 'Yogita Gandhi', 'yogitagandhi@gmail.com', 'Yogita Gandhi', '$2y$10$hz1fe7RGMjGs14RVtyFc5eUk62xDdlYVzvABfvWiDbzwMj81WHTE.', 'buyer', 'active', '2025-09-24 15:19:55', NULL),
(11, '', 'saad@gmail.com', 'Saad', '$2y$10$vD5l5uwuoSPYQLKlibHO8uTUmgDT2sLmH/2DaKtvdfhla4s.oiKaC', 'buyer', 'active', '2025-09-26 03:59:42', NULL),
(13, 'John', 'vrajgandhi06@gmail.com', 'John', '$2y$10$id1PamQLmZAN8ncY9ydoOORBWJXC4ntdoPBlsf8WLN43ZeX9Aac/m', 'supplier', 'active', '2025-09-29 13:53:22', NULL),
(14, 'God Life', 'godlife123@gmail.com', 'God Life', '$2y$10$5dlFLYkj8gPCGuBU7K4yieOSf1WldQksZoQuLzfFeJbWS7WokFZQW', 'supplier', 'active', '2025-09-29 18:15:52', NULL),
(15, 'Harsh', 'harsh123@gmail.com', 'Harsh', '$2y$10$zWWbhQOPhn4FVgAb1bmk.OmpOMulHZ/ZrUWJaWueoMt65RAMyOz6.', 'supplier', 'active', '2025-09-29 19:19:33', NULL),
(17, 'umesh gandhi', 'ukgandhi999@gmail.com', 'umesh gandhi', '$2y$10$mxbVOoAqpQPpeL46STl2ZOAWWEZcy4ZjptBYX6ZOzT.NxbMbLEJ/O', 'buyer', 'active', '2025-11-10 15:51:26', NULL),
(18, 'Charbhuja', 'charbhuja2@gmail.com', 'Charbhuja', '$2y$10$AL8ZAU.CbFtXbTZm0LrSyORwzcKVf89Vc.8pClXFJnsYowD3dUKXi', 'supplier', 'active', '2025-11-15 07:33:09', NULL),
(25, 'Shinchan', 'khyatigandhi333@gmail.com', 'Shinchan', '$2y$10$.ACgKDVPeqL.YYjep0VNtuAieLHq4FQ1SNUT7OSpF9a/dXqipOLS6', 'supplier', '', '2025-11-21 18:17:20', '2025-11-21 18:17:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buyer_profile`
--
ALTER TABLE `buyer_profile`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `supplier_profile`
--
ALTER TABLE `supplier_profile`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buyer_profile`
--
ALTER TABLE `buyer_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `supplier_profile`
--
ALTER TABLE `supplier_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buyer_profile`
--
ALTER TABLE `buyer_profile`
  ADD CONSTRAINT `buyer_profile_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_profile`
--
ALTER TABLE `supplier_profile`
  ADD CONSTRAINT `supplier_profile_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
