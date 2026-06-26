<?php
// Tệp chẩn đoán tạm thời để kiểm tra cấu hình PHP trên Railway
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Hệ thống PHP trên Railway đang hoạt động!</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

echo "<h2>Kiểm tra các Extension đã cài đặt:</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'mysqli', 'json', 'gd'];
echo "<ul>";
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? "<strong style='color: green;'>Đã cài đặt (Loaded)</strong>" : "<strong style='color: red;'>Chưa cài đặt (NOT Loaded)</strong>";
    echo "<li>Extension <strong>$ext</strong>: $status</li>";
}
echo "</ul>";

echo "<h2>Cấu hình chi tiết (phpinfo):</h2>";
phpinfo();
