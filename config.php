<?php
/**
 * QRvaPassPort - Database Configuration & Auto-Initialization
 * Antigravity - Premium AI Developer
 */

// Bật hiển thị lỗi để dễ dàng debug khi deploy (Có thể tắt đi khi chạy chính thức)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cấu hình Database (Tự động thích ứng môi trường Local Laragon và Railway)
$db_host = getenv('MYSQLHOST') ?: 'localhost';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : '';
$db_name = getenv('MYSQLDATABASE') ?: 'qrvapassport';
$db_port = getenv('MYSQLPORT') ?: '3306';

// Hỗ trợ tự động parse từ MYSQL_URL hoặc DATABASE_URL (Railway thường cung cấp chuỗi kết nối này)
$url_env = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');
if (!empty($url_env)) {
    $parsed_url = parse_url($url_env);
    if ($parsed_url !== false) {
        $db_host = $parsed_url['host'] ?? $db_host;
        $db_port = $parsed_url['port'] ?? $db_port;
        $db_user = $parsed_url['user'] ?? $db_user;
        $db_pass = $parsed_url['pass'] ?? $db_pass;
        $db_name = isset($parsed_url['path']) ? ltrim($parsed_url['path'], '/') : $db_name;
    }
}

define('DB_HOST', $db_host);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);
define('DB_NAME', $db_name);
define('DB_PORT', $db_port);

try {
    // 1. Tự động tạo database nếu đang ở môi trường local (Laragon/XAMPP)
    if (DB_HOST === 'localhost' || DB_HOST === '127.0.0.1') {
        $pdo_init = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
        
        // Tạo cơ sở dữ liệu nếu chưa tồn tại
        $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo_init = null; // Đóng kết nối tạm thời
    }

    // 2. Kết nối trực tiếp vào Database của dự án
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);

    // 3. Tự động tạo bảng `passports` nếu chưa tồn tại
    $sql_create_table = "
    CREATE TABLE IF NOT EXISTS `passports` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `passport_code` VARCHAR(50) UNIQUE NOT NULL,
        `fullname` VARCHAR(255) NOT NULL,
        `role` ENUM('student', 'parent') NOT NULL,
        `student_name` VARCHAR(255) NULL,
        `student_class` VARCHAR(50) NOT NULL,
        `phone` VARCHAR(20) NOT NULL,
        `avatar` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql_create_table);

} catch (PDOException $e) {
    die("Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage());
}

/**
 * Hàm lấy URL gốc động của ứng dụng.
 * Giúp mã QR hoạt động trên mọi mạng và IP khác nhau khi truy cập (Localhost, IP LAN, hoặc Tên miền công cộng).
 */
function getBaseUrl() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST']; // Lấy cả tên miền/IP và cổng (ví dụ: 192.168.1.15 hoặc localhost)
    
    // Lấy thư mục chứa dự án
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $dir = dirname($scriptName);
    $dir = str_replace('\\', '/', $dir);
    
    if ($dir !== '/') {
        $dir = rtrim($dir, '/') . '/';
    }
    
    return $protocol . "://" . $host . $dir;
}

/**
 * Hàm tạo mã Passport ngẫu nhiên duy nhất dựa trên vai trò
 * Học sinh: PP-HS-XXXXX, Phụ huynh: PP-PH-XXXXX
 */
function generatePassportCode($pdo, $role) {
    $prefix = ($role === 'student') ? 'PP-HS' : 'PP-PH';
    $isUnique = false;
    $code = '';

    while (!$isUnique) {
        // Sinh 5 số ngẫu nhiên
        $randDigits = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $code = $prefix . '-' . $randDigits;

        // Kiểm tra xem mã đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `passports` WHERE `passport_code` = ?");
        $stmt->execute([$code]);
        if ($stmt->fetchColumn() == 0) {
            $isUnique = true;
        }
    }
    return $code;
}
?>
