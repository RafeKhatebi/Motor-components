<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('{"success":false,"message":"خطا"}');
}

$name = trim($_POST['name'] ?? '');
$code = trim($_POST['code'] ?? '');
$category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
$buy_price = filter_input(INPUT_POST, 'buy_price', FILTER_VALIDATE_FLOAT);
$sell_price = filter_input(INPUT_POST, 'sell_price', FILTER_VALIDATE_FLOAT);
$stock_quantity = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT) ?: 0;
$shelf_location = trim($_POST['shelf_location'] ?? '');
$description = trim($_POST['description'] ?? '');

if (empty($name) || empty($code) || !$category_id || $buy_price === false || $sell_price === false) {
    die('{"success":false,"message":"لطفا تمام فیلدهای ضروری را پر کنید"}');
}

if ($sell_price <= $buy_price) {
    die('{"success":false,"message":"قیمت فروش باید بیشتر از قیمت خرید باشد"}');
}

try {
    $pdo = new PDO('mysql:host=localhost;port=3307;dbname=admin_motor_shop', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    ]);
    
    // Check duplicate code
    $check = $pdo->prepare("SELECT id FROM products WHERE code = ?");
    $check->execute([$code]);
    if ($check->fetch()) {
        die('{"success":false,"message":"کد محصول قبلاً استفاده شده است"}');
    }
    
    $stmt = $pdo->prepare("INSERT INTO products (name, code, category_id, buy_price, sell_price, stock_quantity, min_stock, shelf_location, description) VALUES (?, ?, ?, ?, ?, ?, 5, ?, ?)");
    $stmt->execute([$name, $code, $category_id, $buy_price, $sell_price, $stock_quantity, $shelf_location, $description]);
    
    die('{"success":true,"message":"محصول با موفقیت اضافه شد"}');
} catch (Exception $e) {
    die('{"success":false,"message":"خطا در ذخیره اطلاعات"}');
}