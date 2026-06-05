-- ============================================================
-- LocalLoop C2C E-Commerce Platform
-- Database Schema v1.0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('buyer','seller','both') DEFAULT 'both',
  `status` ENUM('active','suspended') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: listings
-- ============================================================
CREATE TABLE IF NOT EXISTS `listings` (
  `listing_id` INT AUTO_INCREMENT PRIMARY KEY,
  `seller_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active','sold','removed') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: orders
-- ============================================================
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` INT AUTO_INCREMENT PRIMARY KEY,
  `buyer_id` INT NOT NULL,
  `listing_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `total_price` DECIMAL(10,2) NOT NULL,
  `payment_status` ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`buyer_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`listing_id`) REFERENCES `listings`(`listing_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: reviews
-- ============================================================
CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` INT AUTO_INCREMENT PRIMARY KEY,
  `reviewer_id` INT NOT NULL,
  `seller_id` INT NOT NULL,
  `listing_id` INT NOT NULL,
  `rating` TINYINT NOT NULL,
  `comment` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`reviewer_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`listing_id`) REFERENCES `listings`(`listing_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: admin_roles
-- ============================================================
CREATE TABLE IF NOT EXISTS `admin_roles` (
  `role_id` INT AUTO_INCREMENT PRIMARY KEY,
  `role_name` VARCHAR(50) NOT NULL,
  `permissions` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: admins
-- ============================================================
CREATE TABLE IF NOT EXISTS `admins` (
  `admin_id` INT AUTO_INCREMENT PRIMARY KEY,
  `role_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `admin_roles`(`role_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA: Categories
-- ============================================================
INSERT INTO `categories` (`name`, `description`) VALUES
('Clothing & Fashion', 'Second-hand and new clothing, shoes, and accessories'),
('Electronics', 'Phones, laptops, TVs, and other electronic devices'),
('Furniture & Home', 'Household furniture and home decor items'),
('Handmade Goods', 'Locally crafted and handmade products'),
('Construction Materials', 'Building materials, tools, and supplies'),
('Books & Education', 'Textbooks, novels, and educational materials'),
('Vehicles & Parts', 'Cars, motorbikes, bicycles, and spare parts'),
('Other', 'Miscellaneous items not listed above');

-- ============================================================
-- SEED DATA: Admin Roles
-- ============================================================
INSERT INTO `admin_roles` (`role_name`, `permissions`) VALUES
('Super Admin', 'all'),
('Moderator', 'view_users,manage_listings,manage_orders,manage_reviews');

-- ============================================================
-- SEED DATA: Default Super Admin
-- Login: admin@localloop.co.za | Password: Admin@123
-- Hash generated with password_hash('Admin@123', PASSWORD_BCRYPT)
-- ============================================================
INSERT INTO `admins` (`role_id`, `name`, `email`, `password`) VALUES
(1, 'LocalLoop Admin', 'admin@localloop.co.za', '.PfzsA7qVEf$2y$10$TKh8H1E6Y0eOPOXQ9MCYzlyVMa9NxVBXC1.3cVYQi.');

-- ============================================================
-- SEED DATA: Sample Users (password: User@123)
-- ============================================================
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES
('Thabo Mokoena', 'thabo@example.com', '$2y$10$TKh8H1.PfzsA7qVEfE6Y0eOPOXQ9MCYzlyVMa9NxVBXC1.3cVYQi.', 'both', 'active'),
('Lindiwe Dlamini', 'lindiwe@example.com', '$2y$10$TKh8H1.PfzsA7qVEfE6Y0eOPOXQ9MCYzlyVMa9NxVBXC1.3cVYQi.', 'both', 'active'),
('Sipho Ndlovu', 'sipho@example.com', '$2y$10$TKh8H1.PfzsA7qVEfE6Y0eOPOXQ9MCYzlyVMa9NxVBXC1.3cVYQi.', 'both', 'active');

-- ============================================================
-- SEED DATA: Sample Listings
-- ============================================================
INSERT INTO `listings` (`seller_id`, `category_id`, `title`, `description`, `price`, `status`) VALUES
(1, 1, 'Vintage Denim Jacket', 'Barely used Levi denim jacket, size M. Great condition.', 250.00, 'active'),
(2, 2, 'Samsung Galaxy A32', 'Fully working phone, no cracks. Charger included.', 1800.00, 'active'),
(3, 3, '3-Seater Couch', 'Grey fabric couch, lightly used. Collection only.', 1200.00, 'active'),
(1, 4, 'Handmade Bead Necklaces', 'Traditional Zulu beadwork. Various colours available.', 80.00, 'active'),
(2, 5, 'Cement Bags x10', 'Leftover from renovation. Pick up from Soweto.', 350.00, 'active');