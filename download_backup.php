<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$file = $_GET['file'] ?? '';
if (empty($file) || strpos($file, '..') !== false) {
    die('Invalid file');
}

$filepath = 'backups/' . basename($file);
if (!file_exists($filepath)) {
    die('File not found');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
?>