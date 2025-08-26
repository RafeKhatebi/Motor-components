<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/SettingsHelper.php';
$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=sales_export_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV headers
fputcsv($output, ['شماره فاکتور', 'مشتری', 'مبلغ کل', 'تخفیف', 'مبلغ نهایی', 'تاریخ']);

// Get sales data
$query = "SELECT s.id, COALESCE(c.name, 'مشتری نقدی') as customer_name, s.total_amount, s.discount, s.final_amount, s.created_at 
          FROM sales s 
          LEFT JOIN customers c ON s.customer_id = c.id 
          WHERE (s.status IS NULL OR s.status != 'returned')
          ORDER BY s.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['customer_name'],
        number_format($row['total_amount']),
        number_format($row['discount']),
        number_format($row['final_amount']),
        date('Y/m/d H:i', strtotime($row['created_at']))
    ]);
}

fclose($output);
?>