-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th7 31, 2025 lúc 02:09 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `shop_trangsuc`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Nhẫn'),
(2, 'Dây chuyền'),
(3, 'Đồng hồ'),
(4, 'Lắc tay'),
(5, 'Bông tai');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('Chờ xử lý','Đang xử lý','Đang giao','Đã giao','Đã hủy') NOT NULL DEFAULT 'Chờ xử lý',
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `shipping_address` text NOT NULL,
  `delivery_phone_number` varchar(20) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `promotion_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `vat_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `final_total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `final_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `order_date`, `shipping_address`, `delivery_phone_number`, `payment_method`, `notes`, `promotion_id`, `discount_amount`, `vat_amount`, `final_total_amount`, `final_total`) VALUES
(4, 2, 200000.00, 'Đã giao', '2025-07-30 22:24:43', '13 binh thanh', NULL, 'COD', '', 1, 60000.00, 11200.00, 151200.00, 0.00),
(5, 2, 100000.00, 'Đang giao', '2025-07-30 22:25:49', '13 binh thanh', NULL, 'COD', '', NULL, 0.00, 8000.00, 108000.00, 0.00),
(6, 2, 1099999.00, 'Đang xử lý', '2025-07-30 23:03:13', 'sdssa', NULL, 'COD', '', 2, 329999.70, 61599.94, 831599.24, 0.00),
(8, 2, 100000.00, 'Đang xử lý', '2025-07-31 02:42:40', 'dsda', NULL, 'COD', '', NULL, 0.00, 8000.00, 108000.00, 0.00),
(9, 2, 100000.00, 'Chờ xử lý', '2025-07-31 03:00:40', 'binh thanh', '012345678', 'COD', '', NULL, 0.00, 8000.00, 108000.00, 0.00),
(10, 3, 100000.00, 'Đang xử lý', '2025-07-31 03:08:32', 'sadsda', '123456789', 'COD', '', NULL, 0.00, 8000.00, 108000.00, 0.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_order` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price_at_order`) VALUES
(4, 4, 1, 2, 100000.00),
(5, 5, 1, 1, 100000.00),
(6, 6, 2, 1, 999999.00),
(7, 6, 1, 1, 100000.00),
(8, 8, 1, 1, 100000.00),
(9, 9, 1, 1, 100000.00),
(10, 10, 1, 1, 100000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `stock` int(11) NOT NULL,
  `created_at` date NOT NULL DEFAULT current_timestamp(),
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `category_id`, `stock`, `created_at`, `image_url`) VALUES
(2, 'Dây chuyền vàng', 'abcxyz', 999999.00, '1753827571_688948f360ced.png', 2, 4, '2025-07-30', NULL),
(3, 'Dây chuyền bạc 2', '2', 90000.00, '1753827614_6889491ec5d36.png', 2, 9, '2025-07-30', NULL),
(4, 'Dậy chuyền vàng 2', 'abc', 999999.00, '1753839277_688976ad72123.png', 2, 2, '2025-07-30', NULL),
(5, 'Nhẫn', 'Nhẫn đeo tay', 20000000.00, '1753907693_688a81eda494c.png', 1, 20, '2025-07-31', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('percentage','fixed_amount','free_shipping') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `min_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `applies_to` enum('all','specific_products','specific_categories') DEFAULT 'all',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `promotions`
--

INSERT INTO `promotions` (`id`, `code`, `name`, `description`, `type`, `value`, `min_amount`, `used_count`, `min_order_amount`, `start_date`, `end_date`, `is_public`, `usage_limit`, `usage_count`, `is_active`, `applies_to`, `created_at`, `updated_at`) VALUES
(1, 'saledaychuyen', '', NULL, 'percentage', 30.00, 0.00, 0, 0.00, '2025-07-30 09:20:00', '2026-01-01 09:20:00', 1, 2, 1, 1, 'all', '2025-07-30 02:26:31', '2025-07-30 15:24:43'),
(2, 'km1', '', NULL, 'percentage', 30.00, 10.00, 0, 0.00, '2025-07-30 22:53:00', '2025-08-07 22:53:00', 1, NULL, 1, 1, 'all', '2025-07-30 15:53:23', '2025-07-30 16:03:13');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `promotion_category_map`
--

CREATE TABLE `promotion_category_map` (
  `promotion_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `promotion_product_map`
--

CREATE TABLE `promotion_product_map` (
  `promotion_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `promotion_uses`
--

CREATE TABLE `promotion_uses` (
  `id` int(11) NOT NULL,
  `promotion_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `use_date` datetime NOT NULL DEFAULT current_timestamp(),
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `promotion_uses`
--

INSERT INTO `promotion_uses` (`id`, `promotion_id`, `user_id`, `order_id`, `use_date`, `used_at`) VALUES
(1, 1, 2, 4, '2025-07-30 22:24:43', '2025-07-30 15:24:43'),
(2, 2, 2, 6, '2025-07-30 23:03:13', '2025-07-30 16:03:13');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `shipping_address` text DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`, `shipping_address`, `phone_number`, `address`) VALUES
(1, 'admin', '$2y$10$5HSJp80oK2Nd.8yCWXoebugmRW39p/0JtqJNxzBjoouM76GpLdyZa', 'admin@gmail.com', 'admin', '2025-07-30 04:28:12', NULL, NULL, NULL),
(2, 'huy', '$2y$10$/YYzW5QoonMtYjlzOZpKN.8Yianw.lijqnh4TpIuGq1WL8eLIprNi', 'huy@gmail.com', 'user', '2025-07-30 09:32:39', NULL, '123456789', ''),
(3, 'hai', '$2y$10$6yNicXlPA.eB/te460Cyk.Zuun3ER7MVnimZiuS.WAi2k6..ggtZW', 'hai.22@gmail.com', 'user', '2025-07-31 03:07:57', NULL, '123456789', 'âdasdada');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Chỉ mục cho bảng `promotion_category_map`
--
ALTER TABLE `promotion_category_map`
  ADD PRIMARY KEY (`promotion_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Chỉ mục cho bảng `promotion_product_map`
--
ALTER TABLE `promotion_product_map`
  ADD PRIMARY KEY (`promotion_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `promotion_uses`
--
ALTER TABLE `promotion_uses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promotion_id` (`promotion_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `promotion_uses`
--
ALTER TABLE `promotion_uses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `promotion_category_map`
--
ALTER TABLE `promotion_category_map`
  ADD CONSTRAINT `promotion_category_map_ibfk_1` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_category_map_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `promotion_product_map`
--
ALTER TABLE `promotion_product_map`
  ADD CONSTRAINT `promotion_product_map_ibfk_1` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_product_map_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `promotion_uses`
--
ALTER TABLE `promotion_uses`
  ADD CONSTRAINT `promotion_uses_ibfk_1` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_uses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_uses_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
