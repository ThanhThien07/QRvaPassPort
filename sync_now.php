<?php
/**
 * One-time CSV Sync Runner
 */
require_once 'config.php';
syncDatabaseToCsv($pdo);
echo "Đồng bộ database.csv thành công! File này đã tự động xóa.";
@unlink(__FILE__);
