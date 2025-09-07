<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('{"success":false,"message":"خطا"}');
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$customer_type = $_POST['customer_type'] ?? 'retail';

if (empty($name)) {
    die('{"success":false,"message":"نام مشتری الزامی است"}');
}

try {
    $pdo = new PDO('mysql:host=localhost;port=3307;dbname=admin_motor_shop', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    ]);
    
    // Add customer_type column if not exists
    try {
        $pdo->exec("ALTER TABLE customers ADD COLUMN customer_type ENUM('retail', 'wholesale') DEFAULT 'retail' AFTER address");
    } catch (Exception $e) {
        // Column already exists
    }
    
    // Check phone uniqueness
    if ($phone) {
        $check = $pdo->prepare("SELECT id FROM customers WHERE phone = ?");
        $check->execute([$phone]);
        if ($check->fetch()) {
            die('{"success":false,"message":"شماره تلفن قبلاً ثبت شده است"}');
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO customers (name, phone, address, customer_type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $phone, $address, $customer_type]);
    
    die('{"success":true,"message":"مشتری با موفقیت اضافه شد"}');
} catch (Exception $e) {
    die('{"success":false,"message":"خطا در ذخیره اطلاعات"}');
}