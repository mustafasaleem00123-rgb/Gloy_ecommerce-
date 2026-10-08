-- =====================================================
-- Aura Skincare E-Commerce Database Schema & Seed Data
-- Database Engine: MySQL / MariaDB (utf8mb4)
-- =====================================================

CREATE DATABASE IF NOT EXISTS `skincare_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `skincare_db`;

-- Drop existing tables if re-importing
DROP TABLE IF EXISTS `newsletter_subscribers`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `product_variants`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `user_addresses`;
DROP TABLE IF EXISTS `users`;

-- -----------------------------------------------------
-- 1. USERS TABLE
-- -----------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `role` ENUM('user', 'admin') DEFAULT 'user',
  `status` ENUM('active', 'disabled') DEFAULT 'active',
  `reset_token` VARCHAR(64) NULL,
  `reset_expires` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 2. USER ADDRESSES TABLE
-- -----------------------------------------------------
CREATE TABLE `user_addresses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `address_line` VARCHAR(255) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(20) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `is_default` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 3. PRODUCTS TABLE
-- -----------------------------------------------------
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `subtitle` VARCHAR(255) NULL,
  `description` TEXT NOT NULL,
  `ingredients` TEXT NOT NULL,
  `how_to_use` TEXT NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `skin_type` VARCHAR(50) NOT NULL DEFAULT 'All',
  `price` DECIMAL(10,2) NOT NULL,
  `stock` INT NOT NULL DEFAULT 50,
  `image` VARCHAR(255) NOT NULL,
  `model3d_url` VARCHAR(255) NULL,
  `is_bestseller` TINYINT(1) DEFAULT 0,
  `rating_avg` DECIMAL(3,2) DEFAULT 5.00,
  `review_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 4. PRODUCT VARIANTS TABLE
-- -----------------------------------------------------
CREATE TABLE `product_variants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `size_label` VARCHAR(50) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `stock` INT NOT NULL DEFAULT 30,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 5. CART TABLE
-- -----------------------------------------------------
CREATE TABLE `cart` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `session_id` VARCHAR(100) NULL,
  `product_id` INT NOT NULL,
  `variant_id` INT NULL,
  `qty` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`variant_id`) REFERENCES `product_variants`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 6. ORDERS TABLE
-- -----------------------------------------------------
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `address` TEXT NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(20) NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `payment_status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
  `order_status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 7. ORDER ITEMS TABLE
-- -----------------------------------------------------
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `variant_id` INT NULL,
  `qty` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 8. PAYMENTS TABLE
-- -----------------------------------------------------
CREATE TABLE `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `transaction_id` VARCHAR(100) NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 9. REVIEWS TABLE
-- -----------------------------------------------------
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` TEXT NOT NULL,
  `approved` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 10. MESSAGES TABLE
-- -----------------------------------------------------
CREATE TABLE `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 11. NEWSLETTER SUBSCRIBERS TABLE
-- -----------------------------------------------------
CREATE TABLE `newsletter_subscribers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- SEED DATA
-- =====================================================

-- Users (Admin password: Admin123!, User password: User123!)
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `phone`, `role`, `status`) VALUES
(1, 'Admin Manager', 'admin@auraskincare.com', '$2y$10$EqQgRiGw3sEcCeJE9pY73.yEcoHiuSMlQWEWBXpuUk8I55yE.C.nm', '+15550192834', 'admin', 'active'),
(2, 'Jane Doe', 'jane@example.com', '$2y$10$rNHQIJDIyuw3qI5MavIfguN9HxsvrS.gngMYVpm9mXdbFoBAu0O56', '+15550148392', 'user', 'active'),
(3, 'Sophia Chen', 'sophia@example.com', '$2y$10$rNHQIJDIyuw3qI5MavIfguN9HxsvrS.gngMYVpm9mXdbFoBAu0O56', '+15550183749', 'user', 'active'),
(4, 'Marcus Vance', 'marcus@example.com', '$2y$10$rNHQIJDIyuw3qI5MavIfguN9HxsvrS.gngMYVpm9mXdbFoBAu0O56', '+15550129481', 'user', 'active');

-- User Address
INSERT INTO `user_addresses` (`user_id`, `address_line`, `city`, `postal_code`, `phone`, `is_default`) VALUES
(2, '742 Evergreen Terrace', 'Springfield', '97477', '+15550148392', 1);

-- Products
INSERT INTO `products` (`id`, `name`, `slug`, `subtitle`, `description`, `ingredients`, `how_to_use`, `category`, `skin_type`, `price`, `stock`, `image`, `is_bestseller`, `rating_avg`, `review_count`) VALUES
(1, 'Gloy Glass Skin Moisturizer', 'gloy-glass-skin-moisturizer', 'Deep Hydration & Barrier Support', 'Give your skin the hydration it needs with Gloy Glass Skin Moisturizer. Its nourishing formula deeply hydrates, smooths, and softens the skin while helping improve the appearance of an uneven skin tone for a fresh, healthy-looking glow.', 'Ceramides (Barrier Support & Moisture Lock), Shea Butter (Deeply Nourishes & Softens), Glycerine + Water (Draws Lasting Hydration), Vitamin C, Kojic Acid (Brightens Uneven Tone), Olive Oil (Nourishes & Smooths)', 'Apply evenly onto clean face and neck. Gently massage in upward motion.', 'Moisturizers', 'All', 2150.00, 100, 'gloy_moisturizer_wood.jpg', 1, 4.95, 48),
(2, 'Glutathione Brightening Serum', 'glutathione-brightening-serum', 'Lightweight Brightening & Tone Perfecting Elixir', 'A lightweight brightening serum formulated with Glutathione, Vitamin C, and Niacinamide to help improve the appearance of dull skin and promote a more bright, fresh, and even-looking skin tone. Evens Out Skin Tone, Hydrates Skin, Reduces Dark Wrinkles & Spots.', 'Glutathione (Tone Perfecting), Vitamin C (Radiance & Brightening), Niacinamide (Refines Complexion & Pore Support), Hyaluronic Acid (Plumping Hydration)', 'Daily glow in every drop. Dispense 3-4 drops onto clean fingertips. Gently press into face and neck morning and evening.', 'Serums', 'All', 2250.00, 120, 'gloy_serum_wood.jpg', 1, 4.98, 64),
(3, 'Gloy Hydration & Brightening Duo', 'gloy-hydration-brightening-duo', 'Complete Radiance Routine (Moisturizer + Serum)', 'Experience the ultimate 2-step Gloy routine. Includes full-size Gloy Glass Skin Moisturizer (50ml) and Glutathione Brightening Serum (30ml) for maximum hydration and tone brightening.', 'Ceramides, Shea Butter, Glycerine, Vitamin C, Kojic Acid, Olive Oil, Glutathione, Niacinamide', 'Step 1: Apply 3-4 drops of Glutathione Serum onto clean skin. Step 2: Lock in moisture with Gloy Glass Skin Moisturizer.', 'Bundles', 'All', 4100.00, 80, 'gloy_hero_duo_hd.jpg', 1, 5.00, 32);

-- Product Variants
INSERT INTO `product_variants` (`id`, `product_id`, `size_label`, `price`, `stock`) VALUES
(1, 1, '50ml Jar', 2150.00, 100),
(2, 2, '30ml Dropper', 2250.00, 120),
(3, 3, 'Full Set Duo (50ml + 30ml)', 4100.00, 80);

-- Reviews
INSERT INTO `reviews` (`product_id`, `user_id`, `rating`, `comment`, `approved`, `created_at`) VALUES
(1, 2, 5, 'Gloy Glass Skin Moisturizer keeps my skin deeply hydrated without feeling greasy!', 1, '2026-08-14 10:30:00'),
(1, 3, 5, 'Obsessed with the rich texture. My skin stays smooth and glowing all day.', 1, '2026-08-20 14:15:00'),
(2, 2, 5, 'The Glutathione Serum gave my dull skin an amazing radiance in just 2 weeks!', 1, '2026-08-18 09:45:00'),
(3, 3, 5, 'Saved my skin barrier! The Duo bundle is the ultimate daily routine.', 1, '2026-08-25 11:20:00');

-- Sample Orders for Admin Dashboard Analytics
INSERT INTO `orders` (`id`, `order_number`, `user_id`, `name`, `email`, `phone`, `address`, `city`, `postal_code`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `created_at`) VALUES
(1001, 'GLOY-20260901-8391', 2, 'Jane Doe', 'jane@example.com', '+923168882788', 'House 42, Block 5, Clifton', 'Karachi', '75600', 4400.00, 'Card', 'Paid', 'Delivered', '2026-09-10 11:20:00'),
(1002, 'GLOY-20260915-4921', 3, 'Sophia Chen', 'sophia@example.com', '+923168882788', '124 Main Boulevard, Gulberg', 'Lahore', '54000', 4100.00, 'COD', 'Pending (COD)', 'Shipped', '2026-09-15 15:40:00'),
(1003, 'GLOY-20260920-7712', 4, 'Marcus Vance', 'marcus@example.com', '+923168882788', '456 F-7 Markaz', 'Islamabad', '44000', 4300.00, 'Card', 'Paid', 'Processing', '2026-09-20 09:15:00');

-- Order Items
INSERT INTO `order_items` (`order_id`, `product_id`, `variant_id`, `qty`, `price`) VALUES
(1001, 1, 1, 1, 2150.00),
(1001, 2, 2, 1, 2250.00),
(1002, 3, 3, 1, 4100.00),
(1003, 1, 1, 2, 2150.00);

-- Payments
INSERT INTO `payments` (`order_id`, `payment_method`, `transaction_id`, `amount`, `status`, `created_at`) VALUES
(1001, 'Card', 'TXN_CARD_984102941', 4400.00, 'Completed', '2026-09-10 11:20:05'),
(1002, 'COD', 'TXN_COD_1002', 4100.00, 'Pending Collection', '2026-09-15 15:40:02'),
(1003, 'Card', 'TXN_CARD_481029411', 4300.00, 'Completed', '2026-09-20 09:15:04');

-- Messages
INSERT INTO `messages` (`name`, `email`, `subject`, `message`, `is_read`, `created_at`) VALUES
('Elena Rostova', 'elena@example.com', 'Inquiry about Wholesale & Partnerships', 'Hello Aura Skincare team! I am interested in featuring your product line in our eco-spa boutique.', 0, '2026-09-21 14:00:00');

-- Newsletter Subscribers
INSERT INTO `newsletter_subscribers` (`email`, `created_at`) VALUES
('jane@example.com', '2026-09-01 10:00:00'),
('sophia@example.com', '2026-09-05 12:00:00');
