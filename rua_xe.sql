-- Smart Carwash System - MySQL database
-- Import file này bằng phpMyAdmin để tạo database dùng chung cho nhóm.
-- Tài khoản quản trị mặc định: admin@gmail.com / 123456

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";
SET NAMES utf8mb4;

DROP DATABASE IF EXISTS `rua_xe`;
CREATE DATABASE `rua_xe` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rua_xe`;

-- 1. Hạng thành viên
CREATE TABLE `ranks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `discount` INT NOT NULL DEFAULT 0,
  `min_point` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `ranks` (`id`,`name`,`discount`,`min_point`) VALUES
(1,'Đồng',0,0),
(2,'Bạc',5,100),
(3,'Vàng',10,300),
(4,'Kim cương',15,600);

-- 2. Chi nhánh
CREATE TABLE `branches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `address` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `branches` (`id`,`name`,`address`) VALUES
(1,'Chi nhánh Quận 1','123 Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP.HCM'),
(2,'Chi nhánh Cầu Giấy','45 Cầu Giấy, Hà Nội'),
(3,'Chi nhánh Thủ Đức','22 Võ Văn Ngân, TP. Thủ Đức, TP.HCM');

-- 3. Người dùng: khách hàng, nhân viên, quản trị
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) UNIQUE NOT NULL,
  `hashed_password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `branch_id` INT DEFAULT NULL,
  `role` ENUM('customer','admin','staff') NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `point` INT DEFAULT 0,
  `rank_id` INT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_users_branches` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_ranks` FOREIGN KEY (`rank_id`) REFERENCES `ranks` (`id`) ON DELETE SET NULL,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`id`,`email`,`hashed_password`,`name`,`branch_id`,`role`,`phone`,`address`,`point`,`rank_id`,`is_active`) VALUES
(1,'admin@gmail.com','$2y$12$yQGBtU.nRTdsfxeoZdCUYOsLW6IUn1KXQbEUQD9jkGwVEDi1CmNRS','Hệ Thống Admin',1,'admin','0999999999','TP.HCM',0,NULL,1),
(2,'staff.q1@gmail.com','$2y$12$yQGBtU.nRTdsfxeoZdCUYOsLW6IUn1KXQbEUQD9jkGwVEDi1CmNRS','Nguyễn Minh Đức',1,'staff','0911111111','Quận 1, TP.HCM',0,NULL,1),
(3,'staff.cg@gmail.com','$2y$12$yQGBtU.nRTdsfxeoZdCUYOsLW6IUn1KXQbEUQD9jkGwVEDi1CmNRS','Trần Hoài Nam',2,'staff','0922222222','Cầu Giấy, Hà Nội',0,NULL,1),
(4,'tuan@gmail.com','$2y$12$yQGBtU.nRTdsfxeoZdCUYOsLW6IUn1KXQbEUQD9jkGwVEDi1CmNRS','Nguyễn Văn Tuấn',NULL,'customer','0912345678','Bình Thạnh, TP.HCM',350,3,1),
(5,'huong@gmail.com','$2y$12$yQGBtU.nRTdsfxeoZdCUYOsLW6IUn1KXQbEUQD9jkGwVEDi1CmNRS','Trần Thị Hương',NULL,'customer','0987654321','Quận 7, TP.HCM',120,2,1),
(6,'duc@gmail.com','$2y$12$yQGBtU.nRTdsfxeoZdCUYOsLW6IUn1KXQbEUQD9jkGwVEDi1CmNRS','Phạm Minh Đức',NULL,'customer','0901234567','Thủ Đức, TP.HCM',720,4,1),
(7,'mai@gmail.com','$2y$12$yQGBtU.nRTdsfxeoZdCUYOsLW6IUn1KXQbEUQD9jkGwVEDi1CmNRS','Nguyễn Thị Mai',NULL,'customer','0966667777','Gò Vấp, TP.HCM',80,1,1),
(8,'dat@gmail.com','$2y$12$yQGBtU.nRTdsfxeoZdCUYOsLW6IUn1KXQbEUQD9jkGwVEDi1CmNRS','Bùi Quang Đạt',NULL,'customer','0977778888','Quận 3, TP.HCM',0,1,1);

-- 4. Phiên đăng nhập/token
CREATE TABLE `sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `refresh_token` VARCHAR(255) UNIQUE NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_sessions_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Xe của khách
CREATE TABLE `vehicles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `brand` VARCHAR(100) NOT NULL,
  `vehicle_type` VARCHAR(100) NOT NULL,
  `license_plate` VARCHAR(50) NOT NULL,
  `color` VARCHAR(50) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_vehicles_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_vehicle_plate` (`license_plate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `vehicles` (`id`,`user_id`,`brand`,`vehicle_type`,`license_plate`,`color`) VALUES
(1,4,'Toyota','Sedan','30A-123.45','Đen'),
(2,5,'Honda','SUV','29D-987.65','Trắng'),
(3,6,'Mercedes','Sedan','30H-888.88','Bạc'),
(4,7,'Mazda','Hatchback','51G-555.55','Đỏ'),
(5,8,'Ford','Bán tải','30K-777.89','Xanh');

-- 6. Dịch vụ
CREATE TABLE `services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `duration` INT NOT NULL COMMENT 'Thời gian xử lý tính theo phút',
  `description` TEXT DEFAULT NULL,
  `price` INT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_services_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `services` (`id`,`name`,`duration`,`description`,`price`,`is_active`) VALUES
(1,'Rửa bọt tuyết cơ bản',20,'Rửa sạch vỏ xe, xịt gầm, hút bụi thảm để chân.',50000,1),
(2,'Rửa tiêu chuẩn',30,'Rửa ngoài, xịt gầm, hút bụi nội thất cơ bản, lau khô.',80000,1),
(3,'Rửa chuyên sâu',45,'Rửa ngoài, xịt gầm chi tiết, hút bụi kỹ, dưỡng lốp.',150000,1),
(4,'Chăm sóc khoang máy',60,'Vệ sinh khoang động cơ bằng hơi nước nóng và dung dịch chuyên dụng.',250000,1),
(5,'Vệ sinh nội thất & khử mùi',90,'Dọn nội thất, khử mùi, diệt khuẩn bằng hơi nước nóng.',450000,1),
(6,'Phủ Ceramic bảo vệ sơn',120,'Đánh bóng toàn thân xe, phủ lớp Ceramic tăng độ bóng và chống trầy.',1500000,1);

-- 7. Đơn đặt lịch
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `staff_id` INT DEFAULT NULL,
  `vehicle_id` INT NOT NULL,
  `pickup_by_staff` TINYINT(1) DEFAULT 0,
  `return_by_staff` TINYINT(1) DEFAULT 0,
  `branch_id` INT NOT NULL,
  `booking_time` DATETIME NOT NULL,
  `total` INT NOT NULL,
  `status` ENUM('PENDING','CONFIRMED','PROCESSING','COMPLETED','CANCELLED') DEFAULT 'PENDING',
  `note` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_orders_staff` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_vehicles` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_orders_branches` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  INDEX `idx_orders_status` (`status`),
  INDEX `idx_orders_booking_time` (`booking_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `orders` (`id`,`customer_id`,`staff_id`,`vehicle_id`,`pickup_by_staff`,`return_by_staff`,`branch_id`,`booking_time`,`total`,`status`,`note`,`created_at`) VALUES
(1,4,2,1,0,0,1,'2026-06-09 09:00:00',80000,'COMPLETED','Khách thanh toán tại quầy.','2026-06-08 20:00:00'),
(2,5,2,2,1,1,1,'2026-06-10 10:30:00',150000,'COMPLETED','Đón xe tại nhà khách.','2026-06-09 18:12:00'),
(3,6,3,3,0,0,2,'2026-06-11 14:00:00',1500000,'PROCESSING','Khách yêu cầu kiểm tra vết xước sơn.','2026-06-10 19:00:00'),
(4,7,NULL,4,0,0,3,'2026-06-12 09:30:00',450000,'CONFIRMED','Khách sẽ đến sớm 10 phút.','2026-06-11 12:40:00'),
(5,8,NULL,5,1,0,1,'2026-06-12 15:00:00',150000,'PENDING','Cần gọi xác nhận trước khi đến.','2026-06-11 14:20:00'),
(6,4,2,1,0,0,1,'2026-06-13 16:30:00',50000,'PENDING',NULL,'2026-06-11 17:30:00');

-- 8. Chi tiết đơn
CREATE TABLE `order_details` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `service_id` INT NOT NULL,
  `price` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_details_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_details_services` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `order_details` (`order_id`,`service_id`,`price`) VALUES
(1,2,80000),(2,3,150000),(3,6,1500000),(4,5,450000),(5,3,150000),(6,1,50000);

-- 9. Thanh toán
CREATE TABLE `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `method` ENUM('CASH','BANK_TRANSFER','MOMO','CARD') NOT NULL DEFAULT 'CASH',
  `amount` INT NOT NULL,
  `status` ENUM('UNPAID','PAID','FAILED','REFUNDED') NOT NULL DEFAULT 'UNPAID',
  `paid_at` DATETIME DEFAULT NULL,
  `transaction_code` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_payments_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  INDEX `idx_payments_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `payments` (`order_id`,`method`,`amount`,`status`,`paid_at`,`transaction_code`) VALUES
(1,'CASH',80000,'PAID','2026-06-09 10:00:00','CASH-0001'),
(2,'BANK_TRANSFER',150000,'PAID','2026-06-10 11:40:00','BANK-0002'),
(3,'CARD',1500000,'UNPAID',NULL,NULL),
(4,'MOMO',450000,'UNPAID',NULL,NULL),
(5,'CASH',150000,'UNPAID',NULL,NULL),
(6,'CASH',50000,'UNPAID',NULL,NULL);

-- 10. Lịch sử đổi trạng thái đơn
CREATE TABLE `order_status_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `old_status` VARCHAR(30) DEFAULT NULL,
  `new_status` VARCHAR(30) NOT NULL,
  `changed_by` INT DEFAULT NULL,
  `note` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_logs_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_logs_users` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `order_status_logs` (`order_id`,`old_status`,`new_status`,`changed_by`,`note`) VALUES
(1,NULL,'PENDING',1,'Khách tạo lịch'),
(1,'PENDING','CONFIRMED',1,'Admin xác nhận'),
(1,'CONFIRMED','COMPLETED',2,'Nhân viên hoàn thành dịch vụ'),
(2,NULL,'PENDING',1,'Khách tạo lịch'),
(2,'PENDING','COMPLETED',2,'Hoàn tất và đã thanh toán'),
(3,NULL,'PROCESSING',3,'Đang xử lý tại chi nhánh Cầu Giấy');

-- 11. Feedback
CREATE TABLE `feedbacks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `content` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_feedbacks_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `feedbacks` (`order_id`,`rating`,`content`) VALUES
(1,5,'Dịch vụ nhanh, xe sạch và nhân viên tư vấn nhiệt tình.'),
(2,4,'Có dịch vụ đón xe tiện, lần sau sẽ tiếp tục sử dụng.');

-- 12. Thông báo
CREATE TABLE `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `announcements` (`title`,`content`) VALUES
('Khuyến mãi tháng 6','Giảm 10% cho khách hàng hạng Vàng và Kim cương.'),
('Thông báo lịch bảo trì','Hệ thống tạm ngưng nhận lịch online từ 22:00 đến 23:00 ngày 15/06.');

