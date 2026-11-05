-- ============================================================================
-- ShopEase: 3D Shop Inventory & Sales Management System
-- Database Schema & Comprehensive Seed Data
-- Character Set: utf8mb4, Engine: InnoDB
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `shopease` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `shopease`;

-- Disable foreign key checks during drop and recreate
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `daily_closings`;
DROP TABLE IF EXISTS `expenses`;
DROP TABLE IF EXISTS `held_bills`;
DROP TABLE IF EXISTS `sale_items`;
DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. USERS TABLE
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'cashier') NOT NULL DEFAULT 'cashier',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `profile_photo` VARCHAR(255) NULL,
  `theme` ENUM('light', 'dark') NOT NULL DEFAULT 'light',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. CATEGORIES TABLE
-- ----------------------------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `icon_3d_path` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. SUPPLIERS TABLE
-- ----------------------------------------------------------------------------
CREATE TABLE `suppliers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `address` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. PRODUCTS TABLE
-- ----------------------------------------------------------------------------
CREATE TABLE `products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `supplier_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `sku_code` VARCHAR(50) NOT NULL UNIQUE,
  `cost_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `selling_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` INT NOT NULL DEFAULT 0,
  `low_stock_level` INT NOT NULL DEFAULT 5,
  `expiry_date` DATE NULL,
  `image` VARCHAR(255) NULL,
  `model_3d_path` VARCHAR(255) NULL,
  `model_3d_usdz_path` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_products_name` (`name`),
  INDEX `idx_products_category` (`category_id`),
  INDEX `idx_products_supplier` (`supplier_id`),
  INDEX `idx_products_is_active` (`is_active`),
  INDEX `idx_products_stock` (`stock_quantity`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_products_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. SALES TABLE
-- ----------------------------------------------------------------------------
CREATE TABLE `sales` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `invoice_no` VARCHAR(50) NOT NULL UNIQUE,
  `cashier_id` INT UNSIGNED NOT NULL,
  `customer_name` VARCHAR(100) NULL,
  `customer_phone` VARCHAR(50) NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_type` ENUM('Cash', 'Card', 'bKash') NOT NULL DEFAULT 'Cash',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sales_created_at` (`created_at`),
  INDEX `idx_sales_cashier` (`cashier_id`),
  INDEX `idx_sales_payment_type` (`payment_type`),
  CONSTRAINT `fk_sales_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. SALE ITEMS TABLE
-- ----------------------------------------------------------------------------
CREATE TABLE `sale_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `sale_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `product_name` VARCHAR(191) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `cost_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  INDEX `idx_sale_items_sale` (`sale_id`),
  INDEX `idx_sale_items_product` (`product_id`),
  CONSTRAINT `fk_sale_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_sale_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. HELD BILLS TABLE
-- ----------------------------------------------------------------------------
CREATE TABLE `held_bills` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cashier_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(100) NULL,
  `customer_name` VARCHAR(100) NULL,
  `cart_json` LONGTEXT NOT NULL,
  `item_count` INT NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_held_bills_cashier` (`cashier_id`),
  INDEX `idx_held_bills_created_at` (`created_at`),
  CONSTRAINT `fk_held_bills_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. EXPENSES TABLE (Admin only feature)
-- ----------------------------------------------------------------------------
CREATE TABLE `expenses` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `expense_date` DATE NOT NULL,
  `category` ENUM('Rent', 'Electricity', 'Salary', 'Transport', 'Supplies', 'Other') NOT NULL DEFAULT 'Other',
  `title` VARCHAR(150) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `note` TEXT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_expenses_date` (`expense_date`),
  INDEX `idx_expenses_category` (`category`),
  INDEX `idx_expenses_created_by` (`created_by`),
  CONSTRAINT `fk_expenses_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. DAILY CLOSINGS TABLE
-- ----------------------------------------------------------------------------
CREATE TABLE `daily_closings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `closing_date` DATE NOT NULL UNIQUE,
  `invoices_count` INT NOT NULL DEFAULT 0,
  `gross_sales` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discounts` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `cogs` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `expenses_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `net_profit` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `expected_cash` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `counted_cash` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `cash_difference` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `note` TEXT NULL,
  `closed_by` INT UNSIGNED NOT NULL,
  `closed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_daily_closings_date` (`closing_date`),
  CONSTRAINT `fk_daily_closings_user` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 10. LOGIN ATTEMPTS TABLE (Security & Brute Force Prevention)
-- ----------------------------------------------------------------------------
CREATE TABLE `login_attempts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(191) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_login_attempts_email_time` (`email`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 11. PASSWORD RESETS TABLE
-- ----------------------------------------------------------------------------
CREATE TABLE `password_resets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL,
  INDEX `idx_password_resets_token` (`token_hash`),
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 12. NOTIFICATIONS TABLE
-- ----------------------------------------------------------------------------
CREATE TABLE `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `type` VARCHAR(50) NOT NULL DEFAULT 'info',
  `message` VARCHAR(255) NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notifications_unread` (`is_read`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DATA
-- Demo Accounts:
-- Admin: admin@shopease.local / Admin123!
-- Cashier 1: john@shopease.local / Cashier123!
-- Cashier 2: sarah@shopease.local / Cashier123!
-- ============================================================================

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `is_active`, `profile_photo`, `theme`) VALUES
(1, 'Shop Admin', 'admin@shopease.local', '$2y$12$zhG8q0Uxgjx.OgUshe5TxuLmbyAhfmVE10VTFPvyZaJpKhGD1WGm.', 'admin', 1, NULL, 'light'),
(2, 'John Doe', 'john@shopease.local', '$2y$12$E7ZypkPc72ZsITj0IUi9FebTCIoYOLDDdjXOl2LK2j448UsLedSBu', 'cashier', 1, NULL, 'light'),
(3, 'Sarah Jenkins', 'sarah@shopease.local', '$2y$12$E7ZypkPc72ZsITj0IUi9FebTCIoYOLDDdjXOl2LK2j448UsLedSBu', 'cashier', 1, NULL, 'light');

-- 6 Realistic Categories
INSERT INTO `categories` (`id`, `name`, `description`, `icon_3d_path`) VALUES
(1, 'Audio Gear', 'Premium wireless headphones, studio earbuds, and portable Bluetooth speakers.', 'assets/models/categories/audio.glb'),
(2, 'Smart Wearables', 'Modern fitness smartwatches, fitness trackers, and health bands.', 'assets/models/categories/watch.glb'),
(3, 'Gaming Tech', 'Mechanical RGB keyboards, precision mice, and surround gaming headsets.', 'assets/models/categories/gaming.glb'),
(4, 'Desk & Workspace', 'Minimalist ergonomic desk lamps, phone stands, and wireless chargers.', 'assets/models/categories/desk.glb'),
(5, 'Smart Home', 'Connected ambient lighting, voice speakers, and environmental sensors.', 'assets/models/categories/smart_home.glb'),
(6, 'Power & Cables', 'High-speed GaN chargers, durable braided cables, and magnetic power banks.', 'assets/models/categories/power.glb');

-- 3 Suppliers
INSERT INTO `suppliers` (`id`, `name`, `phone`, `address`) VALUES
(1, 'Apex Tech Imports Ltd', '+880 1711-234567', 'Plot 42, Motijheel C/A, Dhaka 1000'),
(2, 'SonicWave Distribution', '+880 1819-876543', 'Level 4, Gulshan Trade Centre, Dhaka 1212'),
(3, 'Silicon Hub Wholesale', '+880 1912-345678', 'GEC Circle, Nasirabad, Chattogram 4000');

-- 25 Realistic Products (with low stock, near expiry, and model paths)
INSERT INTO `products` (`id`, `category_id`, `supplier_id`, `name`, `sku_code`, `cost_price`, `selling_price`, `stock_quantity`, `low_stock_level`, `expiry_date`, `image`, `model_3d_path`, `model_3d_usdz_path`, `is_active`) VALUES
(1, 1, 2, 'AeroSound Pro Noise-Cancelling Headphones', 'SKU-AUD-001', 95.00, 149.00, 18, 5, NULL, 'assets/uploads/products/headphone.jpg', 'assets/models/products/headphone.glb', NULL, 1),
(2, 1, 2, 'SonicPulse Studio Wireless Earbuds', 'SKU-AUD-002', 45.00, 79.00, 24, 5, NULL, 'assets/uploads/products/earbuds.jpg', 'assets/models/products/earbuds.glb', NULL, 1),
(3, 1, 2, 'BoomBox Mini Rugged Bluetooth Speaker', 'SKU-AUD-003', 30.00, 55.00, 3, 5, NULL, 'assets/uploads/products/speaker.jpg', 'assets/models/products/speaker.glb', NULL, 1), -- Low stock
(4, 1, 2, 'VocalPro USB Podcast Condenser Mic', 'SKU-AUD-004', 60.00, 99.00, 12, 5, NULL, 'assets/uploads/products/mic.jpg', 'assets/models/products/mic.glb', NULL, 1),

(5, 2, 1, 'PulseTrack Active Smartwatch 44mm', 'SKU-WEA-001', 80.00, 129.00, 15, 5, NULL, 'assets/uploads/products/watch.jpg', 'assets/models/products/watch.glb', NULL, 1),
(6, 2, 1, 'VibeFit Slim Health & Fitness Band', 'SKU-WEA-002', 25.00, 42.00, 2, 5, NULL, 'assets/uploads/products/fitband.jpg', 'assets/models/products/fitband.glb', NULL, 1), -- Low stock
(7, 2, 1, 'Apex Ultra Titanium GPS Sports Watch', 'SKU-WEA-003', 190.00, 279.00, 8, 4, NULL, 'assets/uploads/products/sportswatch.jpg', 'assets/models/products/sportswatch.glb', NULL, 1),
(8, 2, 1, 'BreathSense Smart Air Quality Ring', 'SKU-WEA-004', 110.00, 169.00, 6, 3, NULL, 'assets/uploads/products/smartring.jpg', 'assets/models/products/smartring.glb', NULL, 1),

(9, 3, 3, 'Vortex RGB Hot-Swap Mechanical Keyboard', 'SKU-GAM-001', 58.00, 95.00, 20, 5, NULL, 'assets/uploads/products/keyboard.jpg', 'assets/models/products/keyboard.glb', NULL, 1),
(10, 3, 3, 'Phantom 8K DPI Ultralight Wireless Mouse', 'SKU-GAM-002', 32.00, 59.00, 14, 5, NULL, 'assets/uploads/products/mouse.jpg', 'assets/models/products/mouse.glb', NULL, 1),
(11, 3, 3, 'HyperSpatial 7.1 Surround Gaming Headset', 'SKU-GAM-003', 48.00, 85.00, 4, 5, NULL, 'assets/uploads/products/headset.jpg', 'assets/models/products/headset.glb', NULL, 1), -- Low stock
(12, 3, 3, 'Precision Control XXL Desk Mat (Stealth)', 'SKU-GAM-004', 12.00, 24.00, 35, 10, NULL, 'assets/uploads/products/deskmat.jpg', 'assets/models/products/deskmat.glb', NULL, 1),

(13, 4, 1, 'AuraBeam Minimalist LED Monitor Light Bar', 'SKU-DSK-001', 35.00, 62.00, 16, 5, NULL, 'assets/uploads/products/lightbar.jpg', 'assets/models/products/lightbar.glb', NULL, 1),
(14, 4, 1, 'Orbit MagSafe 3-in-1 Aluminum Stand', 'SKU-DSK-002', 38.00, 68.00, 11, 4, NULL, 'assets/uploads/products/magstand.jpg', 'assets/models/products/magstand.glb', NULL, 1),
(15, 4, 1, 'ErgoLift Heavy-Duty Gas Spring Laptop Arm', 'SKU-DSK-003', 42.00, 75.00, 9, 3, NULL, 'assets/uploads/products/laptoparm.jpg', 'assets/models/products/laptoparm.glb', NULL, 1),
(16, 4, 1, 'ThermoDesk Smart Heated Coffee Mug & Pad', 'SKU-DSK-004', 28.00, 49.00, 7, 4, NULL, 'assets/uploads/products/mug.jpg', 'assets/models/products/mug.glb', NULL, 1),

(17, 5, 2, 'Lumina Flow RGB Ambient Smart Light Tube', 'SKU-HOM-001', 29.00, 52.00, 19, 5, NULL, 'assets/uploads/products/lighttube.jpg', 'assets/models/products/lighttube.glb', NULL, 1),
(18, 5, 2, 'AuraMini Smart Assistant Speaker', 'SKU-HOM-002', 34.00, 59.00, 13, 5, NULL, 'assets/uploads/products/smartspeaker.jpg', 'assets/models/products/smartspeaker.glb', NULL, 1),
(19, 5, 2, 'HydroPure Smart Air Humidifier & Diffuser', 'SKU-HOM-003', 24.00, 44.00, 2, 5, NULL, 'assets/uploads/products/diffuser.jpg', 'assets/models/products/diffuser.glb', NULL, 1), -- Low stock
(20, 5, 2, 'PureMist Essential Oil Cleaning Refill Pack', 'SKU-HOM-004', 8.00, 16.00, 22, 5, '2026-12-15', 'assets/uploads/products/oilrefill.jpg', 'assets/models/products/oilrefill.glb', NULL, 1), -- Near expiry

(21, 6, 3, 'VoltX 65W Dual GaN Fast Wall Charger', 'SKU-POW-001', 18.00, 32.00, 40, 8, NULL, 'assets/uploads/products/charger.jpg', 'assets/models/products/charger.glb', NULL, 1),
(22, 6, 3, 'MagPower 10,000mAh Magnetic Power Bank', 'SKU-POW-002', 26.00, 48.00, 17, 5, NULL, 'assets/uploads/products/powerbank.jpg', 'assets/models/products/powerbank.glb', NULL, 1),
(23, 6, 3, 'ArmorLink Braided USB-C 240W Cable (2m)', 'SKU-POW-003', 7.50, 15.00, 50, 10, NULL, 'assets/uploads/products/cable.jpg', 'assets/models/products/cable.glb', NULL, 1),
(24, 6, 3, 'SwiftCharge Solar Emergency 20Ah Battery', 'SKU-POW-004', 36.00, 65.00, 5, 5, NULL, 'assets/uploads/products/solarbattery.jpg', 'assets/models/products/solarbattery.glb', NULL, 1),
(25, 6, 3, 'ScreenCare Pro Optical Cleaning Fluid (100ml)', 'SKU-POW-005', 4.00, 9.50, 25, 5, '2026-11-28', 'assets/uploads/products/cleaner.jpg', 'assets/models/products/cleaner.glb', NULL, 1); -- Very near expiry

-- 15 Realistic Expenses
INSERT INTO `expenses` (`id`, `expense_date`, `category`, `title`, `amount`, `note`, `created_by`, `created_at`) VALUES
(1, DATE_SUB(CURDATE(), INTERVAL 28 DAY), 'Rent', 'Monthly Showroom & Warehouse Rent', 850.00, 'Main outlet rent paid via bank transfer', 1, DATE_SUB(NOW(), INTERVAL 28 DAY)),
(2, DATE_SUB(CURDATE(), INTERVAL 25 DAY), 'Electricity', 'Commercial Grid Power Utility Bill', 145.50, 'Monthly DESCO bill', 1, DATE_SUB(NOW(), INTERVAL 25 DAY)),
(3, DATE_SUB(CURDATE(), INTERVAL 24 DAY), 'Supplies', 'Packaging boxes, bubble wrap & receipt rolls', 62.00, 'Thermal paper 50 rolls and eco carry bags', 1, DATE_SUB(NOW(), INTERVAL 24 DAY)),
(4, DATE_SUB(CURDATE(), INTERVAL 21 DAY), 'Transport', 'Stock pickup from Chattogram Depot', 45.00, 'Courier transport fee for imported gaming stock', 1, DATE_SUB(NOW(), INTERVAL 21 DAY)),
(5, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'Other', 'High-Speed Fiber Internet Connection', 35.00, 'Dedicated retail broadband line', 1, DATE_SUB(NOW(), INTERVAL 20 DAY)),
(6, DATE_SUB(CURDATE(), INTERVAL 16 DAY), 'Supplies', 'Cleaning & showroom sanitization supplies', 22.00, 'Disinfectant sprays and microfiber towels', 1, DATE_SUB(NOW(), INTERVAL 16 DAY)),
(7, DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'Salary', 'Mid-month staff advances & overtime', 320.00, 'Overtime compensation for seasonal rush', 1, DATE_SUB(NOW(), INTERVAL 15 DAY)),
(8, DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'Transport', 'Customer same-day doorstep deliveries', 38.50, 'Pathao merchant delivery fees', 1, DATE_SUB(NOW(), INTERVAL 12 DAY)),
(9, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Other', 'Store branding signage maintenance', 48.00, 'LED backlight repair on main facade', 1, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(10, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'Supplies', 'Office stationery & printer cartridges', 29.00, 'A4 papers and replacement toner for billing', 1, DATE_SUB(NOW(), INTERVAL 8 DAY)),
(11, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Transport', 'Supplier freight delivery fee', 55.00, 'Consignment freight for audio gear', 1, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(12, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'Electricity', 'Generator diesel backup fuel', 40.00, '15L diesel fuel for emergency generator', 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(13, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Other', 'Drinking water dispenser 10-jar pack', 15.00, 'Water jars for showroom staff and customers', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(14, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Salary', 'Monthly Cashier & Floor Staff Base Salaries', 900.00, 'Full monthly salary disbursement', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(15, CURDATE(), 'Supplies', 'Barcode label stickers 20 rolls', 18.00, 'Direct thermal labels for newly arrived inventory', 1, NOW());

-- 40 Realistic Sales spread over the last 30 days
INSERT INTO `sales` (`id`, `invoice_no`, `cashier_id`, `customer_name`, `customer_phone`, `subtotal`, `discount_amount`, `total_amount`, `total_cost`, `payment_type`, `created_at`) VALUES
(1, 'INV-2026-0001', 2, 'Tanvir Ahmed', '01712334455', 149.00, 0.00, 149.00, 95.00, 'Card', DATE_SUB(NOW(), INTERVAL 29 DAY)),
(2, 'INV-2026-0002', 3, 'Farhana Karim', '01823445566', 208.00, 8.00, 200.00, 125.00, 'bKash', DATE_SUB(NOW(), INTERVAL 28 DAY)),
(3, 'INV-2026-0003', 2, 'Arif Hossain', '01934556677', 55.00, 0.00, 55.00, 30.00, 'Cash', DATE_SUB(NOW(), INTERVAL 28 DAY)),
(4, 'INV-2026-0004', 2, 'Walk-in Customer', NULL, 129.00, 0.00, 129.00, 80.00, 'Cash', DATE_SUB(NOW(), INTERVAL 27 DAY)),
(5, 'INV-2026-0005', 3, 'Kamrul Hasan', '01645667788', 154.00, 4.00, 150.00, 90.00, 'Card', DATE_SUB(NOW(), INTERVAL 26 DAY)),
(6, 'INV-2026-0006', 2, 'Mahmudul Haque', '01556778899', 85.00, 0.00, 85.00, 48.00, 'bKash', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(7, 'INV-2026-0007', 3, 'Walk-in Customer', NULL, 32.00, 0.00, 32.00, 18.00, 'Cash', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(8, 'INV-2026-0008', 2, 'Nadia Islam', '01767889900', 95.00, 5.00, 90.00, 58.00, 'Card', DATE_SUB(NOW(), INTERVAL 24 DAY)),
(9, 'INV-2026-0009', 3, 'Saiful Islam', '01878990011', 279.00, 10.00, 269.00, 190.00, 'Card', DATE_SUB(NOW(), INTERVAL 23 DAY)),
(10, 'INV-2026-0010', 2, 'Rashed Khan', '01989001122', 110.00, 0.00, 110.00, 64.00, 'bKash', DATE_SUB(NOW(), INTERVAL 22 DAY)),
(11, 'INV-2026-0011', 2, 'Walk-in Customer', NULL, 48.00, 0.00, 48.00, 26.00, 'Cash', DATE_SUB(NOW(), INTERVAL 22 DAY)),
(12, 'INV-2026-0012', 3, 'Zubair Rahman', '01711223344', 169.00, 9.00, 160.00, 110.00, 'Card', DATE_SUB(NOW(), INTERVAL 21 DAY)),
(13, 'INV-2026-0013', 2, 'Tahmina Akter', '01822334455', 62.00, 0.00, 62.00, 35.00, 'bKash', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(14, 'INV-2026-0014', 3, 'Walk-in Customer', NULL, 15.00, 0.00, 15.00, 7.50, 'Cash', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(15, 'INV-2026-0015', 2, 'Imran Kabir', '01933445566', 228.00, 8.00, 220.00, 138.00, 'Card', DATE_SUB(NOW(), INTERVAL 19 DAY)),
(16, 'INV-2026-0016', 3, 'Mehedi Hasan', '01544556677', 75.00, 0.00, 75.00, 42.00, 'bKash', DATE_SUB(NOW(), INTERVAL 18 DAY)),
(17, 'INV-2026-0017', 2, 'Walk-in Customer', NULL, 42.00, 0.00, 42.00, 25.00, 'Cash', DATE_SUB(NOW(), INTERVAL 18 DAY)),
(18, 'INV-2026-0018', 2, 'Anisur Rahman', '01755667788', 99.00, 4.00, 95.00, 60.00, 'Card', DATE_SUB(NOW(), INTERVAL 17 DAY)),
(19, 'INV-2026-0019', 3, 'Fariha Tasnim', '01866778899', 59.00, 0.00, 59.00, 34.00, 'bKash', DATE_SUB(NOW(), INTERVAL 16 DAY)),
(20, 'INV-2026-0020', 2, 'Walk-in Customer', NULL, 68.00, 0.00, 68.00, 38.00, 'Cash', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(21, 'INV-2026-0021', 3, 'Shafiqul Alam', '01977889900', 144.00, 4.00, 140.00, 90.00, 'Card', DATE_SUB(NOW(), INTERVAL 14 DAY)),
(22, 'INV-2026-0022', 2, 'Rubel Mia', '01788990011', 49.00, 0.00, 49.00, 28.00, 'Cash', DATE_SUB(NOW(), INTERVAL 13 DAY)),
(23, 'INV-2026-0023', 3, 'Sultana Razia', '01899001122', 178.00, 8.00, 170.00, 110.00, 'bKash', DATE_SUB(NOW(), INTERVAL 13 DAY)),
(24, 'INV-2026-0024', 2, 'Walk-in Customer', NULL, 52.00, 0.00, 52.00, 29.00, 'Cash', DATE_SUB(NOW(), INTERVAL 12 DAY)),
(25, 'INV-2026-0025', 2, 'Nasir Uddin', '01611223344', 190.00, 10.00, 180.00, 116.00, 'Card', DATE_SUB(NOW(), INTERVAL 11 DAY)),
(26, 'INV-2026-0026', 3, 'Shamim Reza', '01522334455', 44.00, 0.00, 44.00, 24.00, 'bKash', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(27, 'INV-2026-0027', 2, 'Walk-in Customer', NULL, 30.00, 0.00, 30.00, 15.00, 'Cash', DATE_SUB(NOW(), INTERVAL 9 DAY)),
(28, 'INV-2026-0028', 3, 'Afroza Begum', '01733445566', 129.00, 0.00, 129.00, 80.00, 'Card', DATE_SUB(NOW(), INTERVAL 8 DAY)),
(29, 'INV-2026-0029', 2, 'Mustafizur Rahman', '01844556677', 65.00, 5.00, 60.00, 36.00, 'bKash', DATE_SUB(NOW(), INTERVAL 8 DAY)),
(30, 'INV-2026-0030', 2, 'Walk-in Customer', NULL, 85.00, 0.00, 85.00, 48.00, 'Cash', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(31, 'INV-2026-0031', 3, 'Emon Chowdhury', '01955667788', 214.00, 14.00, 200.00, 137.00, 'Card', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(32, 'INV-2026-0032', 2, 'Sabrina Noor', '01666778899', 79.00, 0.00, 79.00, 45.00, 'bKash', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(33, 'INV-2026-0033', 3, 'Walk-in Customer', NULL, 95.00, 0.00, 95.00, 58.00, 'Cash', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(34, 'INV-2026-0034', 2, 'Golam Rabbani', '01777889900', 149.00, 9.00, 140.00, 95.00, 'Card', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(35, 'INV-2026-0035', 3, 'Hafizur Rahman', '01888990011', 55.00, 0.00, 55.00, 30.00, 'bKash', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(36, 'INV-2026-0036', 2, 'Walk-in Customer', NULL, 32.00, 0.00, 32.00, 18.00, 'Cash', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(37, 'INV-2026-0037', 2, 'Shahidul Islam', '01999001122', 129.00, 0.00, 129.00, 80.00, 'Card', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(38, 'INV-2026-0038', 3, 'Rumana Yasmin', '01511223344', 96.00, 6.00, 90.00, 52.00, 'bKash', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(39, 'INV-2026-0039', 2, 'Walk-in Customer', NULL, 48.00, 0.00, 48.00, 26.00, 'Cash', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(40, 'INV-2026-0040', 2, 'Mizanur Rahman', '01722334455', 208.00, 8.00, 200.00, 128.00, 'Cash', NOW());

-- Line items for sales (each copies product_name, unit_price, cost_price, subtotal)
INSERT INTO `sale_items` (`sale_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `cost_price`, `subtotal`) VALUES
(1, 1, 'AeroSound Pro Noise-Cancelling Headphones', 1, 149.00, 95.00, 149.00),
(2, 2, 'SonicPulse Studio Wireless Earbuds', 1, 79.00, 45.00, 79.00),
(2, 5, 'PulseTrack Active Smartwatch 44mm', 1, 129.00, 80.00, 129.00),
(3, 3, 'BoomBox Mini Rugged Bluetooth Speaker', 1, 55.00, 30.00, 55.00),
(4, 5, 'PulseTrack Active Smartwatch 44mm', 1, 129.00, 80.00, 129.00),
(5, 9, 'Vortex RGB Hot-Swap Mechanical Keyboard', 1, 95.00, 58.00, 95.00),
(5, 10, 'Phantom 8K DPI Ultralight Wireless Mouse', 1, 59.00, 32.00, 59.00),
(6, 11, 'HyperSpatial 7.1 Surround Gaming Headset', 1, 85.00, 48.00, 85.00),
(7, 21, 'VoltX 65W Dual GaN Fast Wall Charger', 1, 32.00, 18.00, 32.00),
(8, 9, 'Vortex RGB Hot-Swap Mechanical Keyboard', 1, 95.00, 58.00, 95.00),
(9, 7, 'Apex Ultra Titanium GPS Sports Watch', 1, 279.00, 190.00, 279.00),
(10, 13, 'AuraBeam Minimalist LED Monitor Light Bar', 1, 62.00, 35.00, 62.00),
(10, 22, 'MagPower 10,000mAh Magnetic Power Bank', 1, 48.00, 26.00, 48.00),
(11, 22, 'MagPower 10,000mAh Magnetic Power Bank', 1, 48.00, 26.00, 48.00),
(12, 8, 'BreathSense Smart Air Quality Ring', 1, 169.00, 110.00, 169.00),
(13, 13, 'AuraBeam Minimalist LED Monitor Light Bar', 1, 62.00, 35.00, 62.00),
(14, 23, 'ArmorLink Braided USB-C 240W Cable (2m)', 1, 15.00, 7.50, 15.00),
(15, 1, 'AeroSound Pro Noise-Cancelling Headphones', 1, 149.00, 95.00, 149.00),
(15, 2, 'SonicPulse Studio Wireless Earbuds', 1, 79.00, 45.00, 79.00),
(16, 15, 'ErgoLift Heavy-Duty Gas Spring Laptop Arm', 1, 75.00, 42.00, 75.00),
(17, 6, 'VibeFit Slim Health & Fitness Band', 1, 42.00, 25.00, 42.00),
(18, 4, 'VocalPro USB Podcast Condenser Mic', 1, 99.00, 60.00, 99.00),
(19, 18, 'AuraMini Smart Assistant Speaker', 1, 59.00, 34.00, 59.00),
(20, 14, 'Orbit MagSafe 3-in-1 Aluminum Stand', 1, 68.00, 38.00, 68.00),
(21, 9, 'Vortex RGB Hot-Swap Mechanical Keyboard', 1, 95.00, 58.00, 95.00),
(21, 16, 'ThermoDesk Smart Heated Coffee Mug & Pad', 1, 49.00, 28.00, 49.00),
(22, 16, 'ThermoDesk Smart Heated Coffee Mug & Pad', 1, 49.00, 28.00, 49.00),
(23, 2, 'SonicPulse Studio Wireless Earbuds', 1, 79.00, 45.00, 79.00),
(23, 4, 'VocalPro USB Podcast Condenser Mic', 1, 99.00, 60.00, 99.00),
(24, 17, 'Lumina Flow RGB Ambient Smart Light Tube', 1, 52.00, 29.00, 52.00),
(25, 5, 'PulseTrack Active Smartwatch 44mm', 1, 129.00, 80.00, 129.00),
(25, 13, 'AuraBeam Minimalist LED Monitor Light Bar', 1, 62.00, 35.00, 62.00),
(26, 19, 'HydroPure Smart Air Humidifier & Diffuser', 1, 44.00, 24.00, 44.00),
(27, 23, 'ArmorLink Braided USB-C 240W Cable (2m)', 2, 15.00, 7.50, 30.00),
(28, 5, 'PulseTrack Active Smartwatch 44mm', 1, 129.00, 80.00, 129.00),
(29, 24, 'SwiftCharge Solar Emergency 20Ah Battery', 1, 65.00, 36.00, 65.00),
(30, 11, 'HyperSpatial 7.1 Surround Gaming Headset', 1, 85.00, 48.00, 85.00),
(31, 1, 'AeroSound Pro Noise-Cancelling Headphones', 1, 149.00, 95.00, 149.00),
(31, 24, 'SwiftCharge Solar Emergency 20Ah Battery', 1, 65.00, 36.00, 65.00),
(32, 2, 'SonicPulse Studio Wireless Earbuds', 1, 79.00, 45.00, 79.00),
(33, 9, 'Vortex RGB Hot-Swap Mechanical Keyboard', 1, 95.00, 58.00, 95.00),
(34, 1, 'AeroSound Pro Noise-Cancelling Headphones', 1, 149.00, 95.00, 149.00),
(35, 3, 'BoomBox Mini Rugged Bluetooth Speaker', 1, 55.00, 30.00, 55.00),
(36, 21, 'VoltX 65W Dual GaN Fast Wall Charger', 1, 32.00, 18.00, 32.00),
(37, 5, 'PulseTrack Active Smartwatch 44mm', 1, 129.00, 80.00, 129.00),
(38, 22, 'MagPower 10,000mAh Magnetic Power Bank', 2, 48.00, 26.00, 96.00),
(39, 22, 'MagPower 10,000mAh Magnetic Power Bank', 1, 48.00, 26.00, 48.00),
(40, 2, 'SonicPulse Studio Wireless Earbuds', 1, 79.00, 45.00, 79.00),
(40, 5, 'PulseTrack Active Smartwatch 44mm', 1, 129.00, 80.00, 129.00);

-- Initial Notifications
INSERT INTO `notifications` (`id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, 'warning', 'Low stock alert: BoomBox Mini (3 left), VibeFit Slim (2 left)', 0, NOW()),
(2, 'info', 'System initialized with demo seed catalog and 40 sales records', 0, NOW()),
(3, 'danger', 'Expiry notice: ScreenCare Pro fluid expires on 2026-11-28', 0, NOW());
