<?php
require_once 'config.php';
header('Content-Type: text/plain; charset=utf-8');

try {
    $stmt = $pdo->query("SELECT * FROM passports ORDER BY id DESC");
    $rows = $stmt->fetchAll();
    echo "TOTAL ROWS: " . count($rows) . "\n\n";
    foreach ($rows as $row) {
        echo "ID: {$row['id']} | Name: {$row['fullname']} | Role: {$row['role']} | Phone: {$row['phone']} | Code: {$row['passport_code']} | Created: {$row['created_at']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
