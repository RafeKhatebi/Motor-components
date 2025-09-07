<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die('{"success":false,"message":"خطا"}');
}

try {
    $pdo = new PDO('mysql:host=localhost;port=3307;dbname=admin_motor_shop', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $stmt = $pdo->query("SELECT MAX(CAST(code AS UNSIGNED)) as max_code FROM products WHERE code REGEXP '^[0-9]+$'");
    $result = $stmt->fetch();
    $nextCode = ($result['max_code'] ?? 0) + 1;
    
    die('{"success":true,"code":"' . str_pad($nextCode, 4, '0', STR_PAD_LEFT) . '"}');
} catch (Exception $e) {
    die('{"success":false,"message":"خطا"}');
}