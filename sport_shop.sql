-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 07, 2025 at 03:39 AM
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
-- Database: `sport_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `full_name`, `email`, `is_active`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Quản trị viên', 'admin@adamsport.com', 1, '2025-11-19 09:47:05');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `created_at`) VALUES
(1, 'Vợt cầu lông', 'vot-cau-long', 'Các loại vợt cầu lông chính hãng', '2025-11-19 09:47:04'),
(2, 'Giày thể thao', 'giay-the-thao', 'Giày chuyên dụng cho cầu lông', '2025-11-19 09:47:04'),
(3, 'Cầu lông', 'cau-long', 'Các loại cầu lông thi đấu và tập luyện', '2025-11-19 09:47:04'),
(4, 'Phụ kiện', 'phu-kien', 'Túi, vợt, phụ kiện khác', '2025-11-19 09:47:04');

-- --------------------------------------------------------

--
-- Table structure for table `chat_history`
--

CREATE TABLE `chat_history` (
  `id` int(11) NOT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `user_message` text NOT NULL,
  `bot_reply` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `message`, `ip_address`, `user_agent`, `is_read`, `created_at`, `updated_at`) VALUES
(1, 'Phú Nguyễn', 'yogame.pro123@gmail.com', '0765701720', 'sản phẩm tốt lắm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 1, '2025-11-27 07:45:00', '2025-12-02 15:26:21'),
(2, 'Phú Nguyễn', 'yogame.pro123@gmail.com', '0765701720', 'sản phẩm tốt lắm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 1, '2025-11-27 07:45:05', '2025-12-02 15:26:10'),
(3, 'Phú Nguyễn', 'yogame.pro123@gmail.com', '0765701720', 'sản phẩm tốt lắm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 1, '2025-11-27 07:45:38', '2025-12-02 15:26:19'),
(4, 'Phú Nguyễn', 'yogame.pro123@gmail.com', '0765701720', 'sản phẩm tuyệt vời, tôi muốn mua số lượng lớn', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 1, '2025-11-27 07:54:57', '2025-12-02 15:26:24'),
(5, 'Phú Nguyễn', 'yogame.pro123@gmail.com', '0765701720', 'sản phẩm tốt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 1, '2025-11-27 07:55:57', '2025-12-02 15:26:17'),
(6, 'Phú Nguyễn', 'yogame.pro123@gmail.com', '0765701720', 'tôi muốn mua số lượng lớn', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 1, '2025-11-27 14:32:58', '2025-12-02 15:26:15'),
(7, 'Phú Nguyễn', 'yogame.pro123@gmail.com', '0765701720', 'tôi muốn mua số lượng lớn', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 1, '2025-11-27 14:33:01', '2025-12-02 15:26:13'),
(8, 'Phú Nguyễn', 'yogame.pro123@gmail.com', '0765701720', 'tôi muốn mua số lượng lớn', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 1, '2025-11-27 14:33:03', '2025-11-27 14:33:27');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `address`, `created_at`) VALUES
(1, 'Nguyễn Văn A', 'nguyenvana@email.com', '0901234567', '123 Nguyễn Văn Linh, Đà Nẵng', '2025-11-19 09:47:05'),
(2, 'Trần Thị B', 'tranthib@email.com', '0912345678', '456 Hải Phòng, Đà Nẵng', '2025-11-19 09:47:05');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `customer_name`, `customer_phone`, `customer_email`, `customer_address`, `total_amount`, `status`, `payment_method`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'Nguyễn Văn A', '0901234567', 'nguyenvana@email.com', NULL, 4700000.00, 'delivered', 'cod', NULL, '2025-11-19 09:47:05', '2025-11-19 09:47:05'),
(2, 2, 'Trần Thị B', '0912345678', 'tranthib@email.com', NULL, 1200000.00, '', 'momo', NULL, '2025-11-19 09:47:05', '2025-12-02 06:20:29'),
(5, NULL, 'Phú Nguyễn', '0765701720', 'yogame.pro123@gmail.com', '470 Võ Chí Công', 4800000.00, '', 'cod', NULL, '2025-12-01 13:46:32', '2025-12-02 06:20:39'),
(6, NULL, 'Phú Nguyễn', '0765701720', 'yogame.pro123@gmail.com', '470', 7199000.00, '', 'cod', NULL, '2025-12-02 06:16:43', '2025-12-02 06:22:19'),
(7, NULL, 'Phú Nguyễn', '0765701720', 'yogame.pro123@gmail.com', '470', 6899000.00, '', 'banking', NULL, '2025-12-02 06:17:27', '2025-12-02 15:26:38'),
(8, NULL, 'Phú Nguyễn', '0765701720', 'yogame.pro123@gmail.com', '470', 14868000.00, 'pending', 'cod', NULL, '2025-12-02 06:56:02', '2025-12-02 06:56:02');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_price`, `quantity`, `subtotal`) VALUES
(1, 1, 2, 'Vợt Yonex ArcSaber 11', 3500000.00, 1, 3500000.00),
(2, 1, 6, 'Cầu lông Victor Champion', 250000.00, 4, 1000000.00),
(3, 1, 8, 'Túi đựng vợt Yonex', 800000.00, 1, 800000.00),
(4, 2, 1, 'Vợt Lining Windstorm 72', 1200000.00, 1, 1200000.00),
(5, 5, 1, 'Vợt Lining Windstorm 72', 1200000.00, 4, 4800000.00),
(6, 6, 4, 'Giày Yonex Eclipsion Z2', 2500000.00, 1, 2500000.00),
(7, 6, 13, 'Yonex Actrox 88D Pro ', 4699000.00, 1, 4699000.00),
(8, 7, 13, 'Yonex Actrox 88D Pro ', 4699000.00, 1, 4699000.00),
(9, 7, 12, 'Giày Mizuno Wave Claw 2', 2200000.00, 1, 2200000.00),
(10, 8, 11, 'yonex actrox 11 pro ', 4499000.00, 1, 4499000.00),
(11, 8, 13, 'Vợt Cầu Lông Yonex Astrox 88D Pro 2024', 4799000.00, 1, 4799000.00),
(12, 8, 19, 'Giày cầu lông Victor A170 II-AG chính hãng', 1080000.00, 1, 1080000.00),
(13, 8, 20, 'Vợt cầu lông Victor Auraspeed FANTÔME F HYQ chính hãng', 4490000.00, 1, 4490000.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `category_id`, `image_url`, `stock`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Vợt Lining Windstorm 72', 'Vợt nhẹ cho người mới tập, cân bằng tốt', 1200000.00, 1, 'assets/images/products/product_1764121328_69265af035c33.jpg', 11, 1, '2025-11-19 09:47:05', '2025-12-01 13:46:32'),
(2, 'Vợt cầu lông Yonex Arcsaber 11 Pro', 'Vợt cao cấp cho người chơi chuyên nghiệp', 4859000.00, 1, 'assets/images/products/product_1764658231_692e8c370f4ea.webp', 8, 1, '2025-11-19 09:47:05', '2025-12-02 06:50:31'),
(3, 'Vợt Cầu Lông Victor Thruster F C Ultra X chính hãng', 'Vợt tấn công mạnh mẽ, phù hợp đánh đơn', 4200000.00, 1, 'assets/images/products/product_1764658500_692e8d44466ff.webp', 10, 1, '2025-11-19 09:47:05', '2025-12-02 06:57:36'),
(4, 'Giày Yonex Eclipsion Z2', 'Giày chuyên dụng với đệm khí êm ái', 2500000.00, 2, 'assets/images/products/product_1764121813_69265cd5bfd48.jpg', 11, 1, '2025-11-19 09:47:05', '2025-12-02 06:16:43'),
(5, 'Giày cầu lông Victor A970 cADV/B - Xanh chính hãng', 'Giày nhẹ, thoáng khí, ôm chân', 2570000.00, 2, 'assets/images/products/product_1764121944_69265d582f342.webp', 92, 1, '2025-11-19 09:47:05', '2025-11-26 01:52:24'),
(6, 'SET Vợt cầu lông Victor One Piece - Wado Ichimonji', 'Cầu lông cao cấp cho thi đấu', 3900000.00, 1, 'assets/images/products/product_1764121964_69265d6c46cf5.png', 50, 1, '2025-11-19 09:47:05', '2025-12-02 06:53:50'),
(7, 'Cầu lông Yonex AS-50', 'Cầu lông chính hãng Yonex, độ bền cao', 300000.00, 3, 'assets/images/products/product_1764121014_692659b68a2c2.jpg', 30, 1, '2025-11-19 09:47:05', '2025-11-26 01:36:54'),
(8, 'Túi đựng vợt Yonex', 'Túi đựng vợt 6 que, chống sốc', 800000.00, 4, 'assets/images/products/product_1764121131_69265a2b79fcb.jpg', 20, 1, '2025-11-19 09:47:05', '2025-11-26 01:38:51'),
(11, 'yonex actrox 11 pro ', 'yonex actrox 11 pro ', 4499000.00, 1, 'assets/images/products/product_1764658334_692e8c9e42091.webp', 22, 1, '2025-11-26 00:58:21', '2025-12-02 06:56:02'),
(12, 'Giày Mizuno Wave Claw 2', 'Giày nhẹ, thoáng khí, ôm chân', 2200000.00, 2, 'assets/images/products/product_1764121829_69265ce546fb4.jpg', 8, 1, '2025-11-26 01:50:29', '2025-12-02 06:17:27'),
(13, 'Vợt Cầu Lông Yonex Astrox 88D Pro 2024', 'Smash uy lực, được nhiều VĐV sử dụng', 4799000.00, 1, 'assets/images/products/product_1764658319_692e8c8f0744e.webp', 20, 1, '2025-11-27 05:54:26', '2025-12-02 06:56:02'),
(14, 'Vợt cầu lông Felet RG Low Vibration chính hãng', '', 1069000.00, 3, 'assets/images/products/product_1764657539_692e898302652.webp', 23, 1, '2025-12-02 06:38:59', '2025-12-02 06:38:59'),
(15, 'Vợt Cầu Lông Kumpoo Power Control K520 Pro Plus - Xanh chính hãng', '', 620000.00, 3, 'assets/images/products/product_1764657729_692e8a417dbb6.webp', 25, 1, '2025-12-02 06:42:09', '2025-12-02 06:42:09'),
(16, 'Giày cầu lông Yonex Dominant 6', '', 979000.00, 2, 'assets/images/products/product_1764657806_692e8a8eeef0d.webp', 25, 1, '2025-12-02 06:43:26', '2025-12-02 06:43:26'),
(17, 'Giày cầu lông Yonex Valor-1', '', 1200000.00, 2, 'assets/images/products/product_1764657880_692e8ad81531a.webp', 25, 1, '2025-12-02 06:44:40', '2025-12-02 06:44:40'),
(18, 'Giày cầu lông Victor A970 cADV/B - Xanh chính hãng', '', 2570000.00, 2, 'assets/images/products/product_1764657944_692e8b18278b7.webp', 25, 1, '2025-12-02 06:45:44', '2025-12-02 06:45:44'),
(19, 'Giày cầu lông Victor A170 II-AG chính hãng', '', 1080000.00, 2, 'assets/images/products/product_1764658027_692e8b6b8cb23.webp', 13, 1, '2025-12-02 06:47:07', '2025-12-02 06:56:02'),
(20, 'Vợt cầu lông Victor Auraspeed FANTÔME F HYQ chính hãng', '', 4490000.00, 1, 'assets/images/products/product_1764658103_692e8bb746fb8.webp', 42, 1, '2025-12-02 06:48:23', '2025-12-02 06:56:02'),
(21, 'Vợt cầu lông Yonex Arcsaber 71 Light vàng chính hãng', 'Vợt tấn công mạnh mẽ, phù hợp đánh đơn', 1075000.00, 1, 'assets/images/products/product_1764658652_692e8ddcdb027.webp', 10, 1, '2025-12-02 06:57:32', '2025-12-02 06:57:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `chat_history`
--
ALTER TABLE `chat_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

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
  ADD KEY `category_id` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `chat_history`
--
ALTER TABLE `chat_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
