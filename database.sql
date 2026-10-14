-- ========================================================
-- Database Schema: ระบบจองสินค้า (เสื้อ) ออนไลน์ (Shirt Pre-Order System)
-- Database: db_shirt_booking
-- Encoding: UTF-8 Unicode (utf8mb4_unicode_ci)
-- ========================================================

CREATE DATABASE IF NOT EXISTS `db_shirt_booking` 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `db_shirt_booking`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `product_sizes`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `booking_rounds`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. ตารางผู้ดูแลระบบ (Users: Superadmin & Admin)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `fullname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `role` ENUM('superadmin', 'admin') NOT NULL DEFAULT 'admin',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. ตารางการตั้งค่าระบบ (System Settings)
CREATE TABLE `system_settings` (
  `setting_key` VARCHAR(50) PRIMARY KEY,
  `setting_value` TEXT NOT NULL,
  `setting_description` VARCHAR(255) DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ตารางรอบการจอง (Booking Rounds / Batches)
CREATE TABLE `booking_rounds` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `round_code` VARCHAR(50) NOT NULL UNIQUE,
  `round_name` VARCHAR(150) NOT NULL,
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ตารางสินค้า (Products)
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `round_id` INT DEFAULT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'เสื้อ',
  `description` TEXT DEFAULT NULL,
  `main_image` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_products_round` FOREIGN KEY (`round_id`) REFERENCES `booking_rounds`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. ตารางรูปภาพเพิ่มเติมของสินค้า (Product Images Gallery)
CREATE TABLE `product_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_images_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ตารางไซต์ ราคา และสต็อก (Product Sizes & Stock Variants)
CREATE TABLE `product_sizes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `size_name` VARCHAR(50) NOT NULL,
  `chest_inch` DECIMAL(4,1) DEFAULT NULL,
  `length_inch` DECIMAL(4,1) DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock` INT NOT NULL DEFAULT 0,
  `is_unlimited` TINYINT(1) NOT NULL DEFAULT 0,
  `reserved_stock` INT NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_sizes_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. ตารางคำสั่งซื้อ (Orders)
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_code` VARCHAR(30) NOT NULL UNIQUE,
  `round_id` INT NOT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `customer_email` VARCHAR(100) NOT NULL,
  `customer_organization` VARCHAR(255) DEFAULT NULL,
  `delivery_method` ENUM('pickup', 'delivery') NOT NULL DEFAULT 'pickup',
  `shipping_address` TEXT DEFAULT NULL,
  `shipping_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `subtotal_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending_payment', 'waiting_verification', 'confirmed', 'rejected', 'completed', 'cancelled') NOT NULL DEFAULT 'pending_payment',
  `expires_at` DATETIME NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_phone` (`customer_phone`),
  INDEX `idx_status` (`status`),
  INDEX `idx_expires` (`expires_at`),
  CONSTRAINT `fk_orders_round` FOREIGN KEY (`round_id`) REFERENCES `booking_rounds`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. ตารางรายการสินค้าในคำสั่งซื้อ (Order Items)
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_size_id` INT NOT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `size_name` VARCHAR(50) NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
  CONSTRAINT `fk_items_size` FOREIGN KEY (`product_size_id`) REFERENCES `product_sizes`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. ตารางการชำระเงินและสลิป (Payments)
CREATE TABLE `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `slip_image` VARCHAR(255) NOT NULL,
  `transfer_datetime` DATETIME NOT NULL,
  `customer_note` TEXT DEFAULT NULL,
  `verified_by` INT DEFAULT NULL,
  `verified_at` DATETIME DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `reject_reason` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- ข้อมูลเริ่มต้น (Initial Seed Data)
-- ========================================================

-- ผู้ดูแลระบบ: superadmin / admin1234, admin / admin1234
INSERT INTO `users` (`username`, `password`, `fullname`, `email`, `phone`, `role`) VALUES
('superadmin', '$2y$10$RWhmYxVxr76PRYVKbN81qebMMXXKAYEp.EAHNCYjZsAyZd9UzBj1.', 'ผู้ดูแลระบบสูงสุด (Superadmin)', 'superadmin@cmtc.ac.th', '0812345678', 'superadmin'),
('admin', '$2y$10$RWhmYxVxr76PRYVKbN81qebMMXXKAYEp.EAHNCYjZsAyZd9UzBj1.', 'เจ้าหน้าที่รับจอง (Admin)', 'admin@cmtc.ac.th', '0898765432', 'admin');

-- ตั้งค่าระบบ
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_description`) VALUES
('site_title', 'ระบบจองเสื้อออนไลน์ - CMTC Official Store', 'ชื่อเว็บไซต์/ระบบ'),
('promptpay_number', '0812345678', 'เบอร์พร้อมเพย์ หรือ เลขประจำตัวผู้เสียภาษี 13 หลัก สำหรับรับเงิน'),
('promptpay_name', 'วิทยาลัยเทคนิคเชียงใหม่ (CMTC Shop)', 'ชื่อบัญชีพร้อมเพย์ที่แสดงให้ผู้สั่งจองตรวจสอบ'),
('cart_timeout_minutes', '30', 'ระยะเวลาล็อกสต็อกเพื่อรอแนบสลิป (นาที)'),
('shipping_base_fee', '50.00', 'ค่าจัดส่งพัสดุเริ่มต้น (บาท)'),
('shipping_extra_fee', '10.00', 'ค่าจัดส่งเพิ่มต่อตัวถัดไป (บาท)'),
('pickup_location', 'ตึกอำนวยการ แผนกวิชาช่างเทคนิคคอมพิวเตอร์ ชั้น 2 (เวลาราชการ 08:30 - 16:30 น.)', 'สถานที่รับสินค้าด้วยตนเอง'),
('contact_phone', '053-217-708', 'เบอร์โทรติดต่อสอบถาม');

-- รอบการจองเริ่มต้น
INSERT INTO `booking_rounds` (`round_code`, `round_name`, `start_date`, `end_date`, `is_active`, `description`) VALUES
('ROUND-2026-01', 'รอบที่ 1: เสื้อกิจกรรม CMTC 87 ปี (Pre-Order รุ่นพิเศษ)', '2026-10-01 08:00:00', '2026-11-15 23:59:59', 1, 'เปิดรับจองเสื้อกิจกรรมและเสื้อรุ่นเฉลิมฉลอง 87 ปี วิทยาลัยเทคนิคเชียงใหม่ ผลิตตามยอดสั่งจอง');

-- สินค้าตัวอย่าง
INSERT INTO `products` (`id`, `round_id`, `code`, `name`, `category`, `description`, `main_image`, `is_active`, `sort_order`) VALUES
(1, 1, 'POLO-CMTC-87', 'เสื้อโปโล 87 ปี วิทยาลัยเทคนิคเชียงใหม่ (Polo Shirt)', 'เสื้อ', 'เสื้อโปโลเนื้อผ้า CVC ทอพิเศษ ระบายอากาศดีเยี่ยม ปักตราสัญลักษณ์ 87 ปี ชัดเจน สวมใส่สบายทั้งในและนอกห้องเรียน', 'assets/uploads/products/polo_87_main.jpg', 1, 1),
(2, 1, 'TSHIRT-TECH-GRA', 'เสื้อยืดที่ระลึก Cotton 100% สกรีนลายกราฟิก Tech Edition', 'เสื้อ', 'เสื้อยืดผ้าฝ้ายธรรมชาติ 100% ลายพิมพ์กราฟิกช่างเทคนิค ดีไซน์โมเดิร์น สตรีทแวร์ นุ่มสบาย ไม่หดตัว', 'assets/uploads/products/tshirt_tech_main.jpg', 1, 2),
(3, 1, 'JACKET-SPORT-CMTC', 'เสื้อแจ็คเก็ตกีฬา CMTC Sports Club (ผ้าวอร์มเกรดพรีเมียม)', 'เสื้อ', 'เสื้อแจ็คเก็ตผ้าวอร์มสองหน้า ซิปหน้าอย่างดี กระเป๋าข้างพร้อมซิป ซับในระบายเหงื่อ เหมาะสำหรับใส่กันลมและเล่นกีฬา', 'assets/uploads/products/jacket_sport_main.jpg', 1, 3),
(4, 1, 'MUG-CMTC-EST1937', 'แก้วน้ำเซรามิก CMTC Premium Navy Edition (พร้อมกล่องของขวัญ)', 'แก้วน้ำ', 'แก้วน้ำเซรามิกเนื้อหนาพิเศษเคลือบเงาสีน้ำเงินพรีเมียม สกรีนตราสัญลักษณ์วิทยาลัยเทคนิคเชียงใหม่สีทองหรูหรา บรรจุในกล่องของขวัญคราฟต์พรีเมียม เหมาะสำหรับใช้ประจำวันหรือมอบเป็นของที่ระลึก ทนความร้อนสูง', 'assets/uploads/products/cmtc_mug_main.jpg', 1, 4),
(5, 1, 'KEYCHAIN-PIN-CMTC87', 'ชุดเข็มกลัดและพวงกุญแจที่ระลึก 87 ปี CMTC (Gift Box Edition)', 'ของที่ระลึก', 'เซ็ตของที่ระลึกระดับพรีเมียม โลหะชุบทองสลักตราสัญลักษณ์ CMTC บรรจุในกล่องกำมะหยี่สีน้ำเงินสุดหรู เหมาะสำหรับเป็นของสะสมและของขวัญ', 'assets/uploads/products/souvenir_pin_main.jpg', 1, 5),
(6, 1, 'TOTEBAG-CMTC-ECO', 'กระเป๋าผ้าแคนวาสรักษ์โลก CMTC Tech Eco Tote Bag', 'อื่นๆ', 'กระเป๋าผ้าแคนวาสหนาพิเศษ 14 ออนซ์ สายสะพายสีกรมท่า แข็งแรงทนทาน จุเอกสารและแล็ปท็อปได้สบาย สไตล์มินิมอลโมเดิร์น', 'assets/uploads/products/totebag_eco_main.jpg', 1, 6);

-- ขนาด ราคา และสต็อกสำหรับสินค้า 1: เสื้อโปโล 87 ปี
INSERT INTO `product_sizes` (`product_id`, `size_name`, `chest_inch`, `length_inch`, `price`, `stock`, `reserved_stock`, `sort_order`) VALUES
(1, 'S', 36.0, 26.0, 280.00, 50, 0, 1),
(1, 'M', 38.0, 27.0, 280.00, 100, 0, 2),
(1, 'L', 40.0, 28.0, 280.00, 120, 0, 3),
(1, 'XL', 42.0, 29.0, 280.00, 80, 0, 4),
(1, '2XL', 44.0, 30.0, 310.00, 40, 0, 5),
(1, '3XL', 46.0, 31.0, 330.00, 20, 0, 6);

-- ขนาด ราคา และสต็อกสำหรับสินค้า 2: เสื้อยืดที่ระลึก
INSERT INTO `product_sizes` (`product_id`, `size_name`, `chest_inch`, `length_inch`, `price`, `stock`, `reserved_stock`, `sort_order`) VALUES
(2, 'S', 34.0, 25.0, 220.00, 40, 0, 1),
(2, 'M', 38.0, 27.0, 220.00, 80, 0, 2),
(2, 'L', 42.0, 29.0, 220.00, 80, 0, 3),
(2, 'XL', 44.0, 30.0, 220.00, 50, 0, 4),
(2, '2XL', 46.0, 31.0, 250.00, 30, 0, 5);

-- ขนาด ราคา และสต็อกสำหรับสินค้า 3: เสื้อแจ็คเก็ต
INSERT INTO `product_sizes` (`product_id`, `size_name`, `chest_inch`, `length_inch`, `price`, `stock`, `reserved_stock`, `sort_order`) VALUES
(3, 'M', 40.0, 26.0, 590.00, 30, 0, 1),
(3, 'L', 42.0, 27.0, 590.00, 40, 0, 2),
(3, 'XL', 44.0, 28.0, 590.00, 40, 0, 3),
(3, '2XL', 46.0, 29.0, 650.00, 20, 0, 4);

-- ขนาด ราคา และสต็อกสำหรับสินค้า 4: แก้วน้ำเซรามิก CMTC (Pre-order ผลิตตามยอดจอง ไม่จำกัด)
INSERT INTO `product_sizes` (`product_id`, `size_name`, `chest_inch`, `length_inch`, `price`, `stock`, `is_unlimited`, `reserved_stock`, `sort_order`) VALUES
(4, '12 oz (350ml)', NULL, NULL, 159.00, 0, 1, 0, 1),
(4, '16 oz (480ml)', NULL, NULL, 189.00, 0, 1, 0, 2);

-- ขนาด ราคา และสต็อกสำหรับสินค้า 5: ชุดของที่ระลึก 87 ปี CMTC
INSERT INTO `product_sizes` (`product_id`, `size_name`, `chest_inch`, `length_inch`, `price`, `stock`, `is_unlimited`, `reserved_stock`, `sort_order`) VALUES
(5, 'ชุดกิฟต์เซ็ตคู่ (เข็มกลัด + พวงกุญแจ)', NULL, NULL, 250.00, 50, 0, 0, 1),
(5, 'พวงกุญแจโลหะพรีเมียม (เดี่ยว)', NULL, NULL, 150.00, 100, 0, 0, 2),
(5, 'เข็มกลัดลงยา CMTC (เดี่ยว)', NULL, NULL, 120.00, 100, 0, 0, 3);

-- ขนาด ราคา และสต็อกสำหรับสินค้า 6: กระเป๋าผ้าแคนวาสรักษ์โลก (หมวดหมู่อื่นๆ)
INSERT INTO `product_sizes` (`product_id`, `size_name`, `chest_inch`, `length_inch`, `price`, `stock`, `is_unlimited`, `reserved_stock`, `sort_order`) VALUES
(6, 'สีขาวครีมสายกรมท่า (Standard)', NULL, NULL, 199.00, 80, 0, 0, 1),
(6, 'สีขาวครีมสายกรมท่า (มีซิป + ช่องใส่ขวดน้ำ)', NULL, NULL, 239.00, 60, 0, 0, 2);

-- รูปภาพเพิ่มเติมของสินค้า (Gallery)
INSERT INTO `product_images` (`product_id`, `image_path`, `sort_order`) VALUES
(1, 'assets/uploads/products/polo_87_gallery1.jpg', 1),
(4, 'assets/uploads/products/cmtc_mug_gallery1.jpg', 1),
(4, 'assets/uploads/products/cmtc_mug_gallery2.jpg', 2);
