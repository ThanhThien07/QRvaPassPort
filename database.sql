-- ==========================================
-- QRvaPassPort - Database Schema Definition
-- Antigravity - Premium AI Developer
-- Target Database: MySQL / MariaDB
-- ==========================================

-- 1. Tạo cơ sở dữ liệu nếu chưa tồn tại
CREATE DATABASE IF NOT EXISTS `qrvapassport` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `qrvapassport`;

-- 2. Tạo cấu trúc bảng `passports` để lưu trữ thông tin khách mời
CREATE TABLE IF NOT EXISTS `passports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `passport_code` VARCHAR(50) UNIQUE NOT NULL COMMENT 'Mã Passport duy nhất dạng PP-HS-XXXXX hoặc PP-PH-XXXXX',
    `fullname` VARCHAR(255) NOT NULL COMMENT 'Họ và tên khách đăng ký',
    `role` ENUM('student', 'parent') NOT NULL COMMENT 'Đối tượng đăng ký: Học sinh hoặc Phụ huynh',
    `student_name` VARCHAR(255) NULL COMMENT 'Tên học sinh (nếu đối tượng là phụ huynh)',
    `student_class` VARCHAR(50) NOT NULL COMMENT 'Lớp học tập / Lớp của con',
    `phone` VARCHAR(20) NOT NULL COMMENT 'Số điện thoại liên hệ',
    `avatar` VARCHAR(255) NOT NULL COMMENT 'Đường dẫn ảnh đại diện / thẻ căn cước',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian đăng ký'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

