<?php
require_once '../init_security.php';
require_once '../config/database.php';

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']));
}

$database = new Database();
$db = $database->getConnection();

if ($_POST) {
    try {
        $ui_settings = [
            'theme_color' => $_POST['theme_color'] ?? 'blue',
            'sidebar_style' => $_POST['sidebar_style'] ?? 'dark',
            'dashboard_layout' => $_POST['dashboard_layout'] ?? 'modern',
            'table_density' => $_POST['table_density'] ?? 'comfortable',
            'animation_speed' => $_POST['animation_speed'] ?? 'normal',
            'show_animations' => isset($_POST['show_animations']) ? '1' : '0',
            'compact_mode' => isset($_POST['compact_mode']) ? '1' : '0'
        ];

        foreach ($ui_settings as $key => $value) {
            $query = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
            $stmt = $db->prepare($query);
            $stmt->execute([$key, $value]);
        }

        echo json_encode(['success' => true, 'message' => 'تنظیمات ظاهری بروزرسانی شد']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'خطا: ' . $e->getMessage()]);
    }
}
?>