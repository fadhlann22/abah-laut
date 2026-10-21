-- Abah Laut Database
-- Compatible with MySQL 5.7+ / MariaDB (XAMPP default)
-- Import via phpMyAdmin after creating database db_pkk

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table: users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `whatsapp` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin','petugas','pengunjung') NOT NULL DEFAULT 'pengunjung',
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_whatsapp` (`whatsapp`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: categories
-- ----------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: menus
-- ----------------------------
DROP TABLE IF EXISTS `menus`;
CREATE TABLE `menus` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `image_path` VARCHAR(255) NULL DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_menus_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_menus_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: orders
-- ----------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `petugas_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `order_code` VARCHAR(30) NOT NULL,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_code` (`order_code`),
  KEY `idx_user` (`user_id`),
  KEY `idx_petugas` (`petugas_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_petugas` FOREIGN KEY (`petugas_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: order_items
-- ----------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `menu_id` BIGINT UNSIGNED NOT NULL,
  `menu_name` VARCHAR(150) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(12,2) NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  `sauce_name` VARCHAR(100) NOT NULL DEFAULT 'Saus Asam Manis',
  `sauce_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_menu` (`menu_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_items_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: reservations
-- ----------------------------
DROP TABLE IF EXISTS `reservations`;
CREATE TABLE `reservations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `reservation_code` VARCHAR(30) NOT NULL,
  `reservation_date` DATE NOT NULL,
  `reservation_time` TIME NOT NULL,
  `guest_count` INT UNSIGNED NOT NULL DEFAULT 1,
  `status` ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reservation_code` (`reservation_code`),
  KEY `idx_user` (`user_id`),
  KEY `idx_date` (`reservation_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_reservations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: activity_logs
-- ----------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `related_type` VARCHAR(50) NULL,
  `related_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------
-- Seed Data
-- ----------------------------

-- Users (password hashes for: admin123, petugas123, pengunjung123)
INSERT INTO `users` (`id`, `whatsapp`, `password`, `name`, `role`, `status`) VALUES
(1, '081234567890', '$2y$10$aiuZaZo7k6kWgHRB9IcFm.wakAeybYyBu4M87MxacDLqceevtxCq2', 'Admin Abah Laut', 'admin', 'active'),
(2, '081298765432', '$2y$10$m/oj9z8vhNZx6f5GXTNUJ..8kXHsIikpMEdHUX6M.jb2J36sI8U4G', 'Budi Petugas', 'petugas', 'active'),
(3, '081112223333', '$2y$10$mOMKViXG4mMXkFgs9JBgQOacvjz6jzEu3AjJfa4XQNGcBybsyKQzy', 'Siti Pengunjung', 'pengunjung', 'active'),
(4, '081223344455', '$2y$10$m/oj9z8vhNZx6f5GXTNUJ..8kXHsIikpMEdHUX6M.jb2J36sI8U4G', 'Andi Petugas', 'petugas', 'active');

-- Categories
INSERT INTO `categories` (`id`, `name`, `description`, `status`) VALUES
(1, 'Makanan Laut', 'Hidangan seafood segar khas Abah Laut', 'active'),
(2, 'Makanan Utama', 'Nasi dan lauk pauk pilihan', 'active'),
(3, 'Minuman', 'Minuman segar dan hangat', 'active'),
(4, 'Camilan', 'Snack dan makanan ringan', 'active');

-- Menus (image_path relative to project root)
INSERT INTO `menus` (`id`, `category_id`, `name`, `description`, `price`, `image_path`, `status`, `created_by`) VALUES
(1, 1, 'Ikan Bakar Madu', 'Ikan segar dibakar dengan bumbu madu special Abah Laut', 85000.00, 'uploads/menus/ikan-bakar.jpg', 'active', 2),
(2, 1, 'Udang Goreng Tepung', 'Udang crispy dengan saus asam manis', 65000.00, 'uploads/menus/udang-goreng.jpg', 'active', 2),
(3, 1, 'Cumi Saus Padang', 'Cumi segar dengan bumbu padang pedas', 55000.00, 'uploads/menus/cumi-padang.jpg', 'active', 2),
(4, 1, 'Kepiting Saus Tiram', 'Kepiting jumbo dengan saus tiram gurih', 120000.00, 'uploads/menus/kepiting.jpg', 'active', 2),
(5, 2, 'Nasi Goreng Seafood', 'Nasi goreng spesial dengan campuran seafood', 45000.00, 'uploads/menus/nasi-goreng.jpg', 'active', 2),
(6, 2, 'Nasi Putih', 'Nasi putih hangat', 8000.00, 'uploads/menus/nasi-putih.jpg', 'active', 2),
(7, 3, 'Es Teh Manis', 'Teh manis dingin segar', 8000.00, 'uploads/menus/es-teh.jpg', 'active', 2),
(8, 3, 'Jus Alpukat', 'Jus alpukat segar dengan susu', 18000.00, 'uploads/menus/jus-alpukat.jpg', 'active', 2),
(9, 3, 'Air Mineral', 'Air mineral 600ml', 5000.00, 'uploads/menus/air-mineral.jpg', 'active', 2),
(10, 4, 'Kerupuk Udang', 'Kerupuk udang renyah', 12000.00, 'uploads/menus/kerupuk.jpg', 'active', 2);

-- Orders and order_items intentionally start empty in the fresh db_pkk database.

-- Sample Reservations
INSERT INTO `reservations` (`id`, `user_id`, `reservation_code`, `reservation_date`, `reservation_time`, `guest_count`, `status`, `notes`, `created_at`) VALUES
(1, 3, 'RSV-20261015-001', '2026-10-15', '19:00:00', 4, 'confirmed', 'Meja dekat jendela', '2026-10-10 14:00:00'),
(2, 3, 'RSV-20261020-002', '2026-10-20', '12:30:00', 2, 'pending', NULL, '2026-10-12 09:00:00');

-- Activity Logs
INSERT INTO `activity_logs` (`user_id`, `action`, `description`, `related_type`, `related_id`, `ip_address`, `created_at`) VALUES
(1, 'login', 'Admin berhasil login', NULL, NULL, '127.0.0.1', '2026-10-12 08:00:00'),
(2, 'login', 'Petugas berhasil login', NULL, NULL, '127.0.0.1', '2026-10-12 08:30:00'),
(2, 'menu_create', 'Menambahkan menu baru: Ikan Bakar Madu', 'menu', 1, '127.0.0.1', '2026-10-01 10:00:00'),
(2, 'order_update', 'Mengubah status pesanan ORD-20261012-001 menjadi completed', 'order', 1, '127.0.0.1', '2026-10-10 13:00:00'),
(2, 'order_update', 'Mengubah status pesanan ORD-20261012-002 menjadi processing', 'order', 2, '127.0.0.1', '2026-10-11 18:30:00'),
(3, 'login', 'Pengunjung berhasil login', NULL, NULL, '127.0.0.1', '2026-10-12 09:00:00'),
(3, 'reservation_create', 'Membuat reservasi RSV-20261020-002', 'reservation', 2, '127.0.0.1', '2026-10-12 09:05:00');
