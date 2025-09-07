<?php
/**
 * مدیریت سوپر ادمین جداگانه
 */
class SuperAdminManager
{
    private $db;
    private $masterKey = 'MOTOR_SUPER_ADMIN_2024';

    public function __construct($database)
    {
        $this->db = $database;
        $this->initializeSuperAdminTable();
    }

    /**
     * ایجاد جدول سوپر ادمین
     */
    private function initializeSuperAdminTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS super_admin (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            master_key VARCHAR(255) NOT NULL,
            hardware_id VARCHAR(255) NOT NULL,
            status ENUM('active', 'disabled') DEFAULT 'active',
            last_login DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB";

        $this->db->exec($sql);
    }

    /**
     * ایجاد سوپر ادمین اولیه
     */
    public function createInitialSuperAdmin($hardwareId)
    {
        // بررسی وجود سوپر ادمین
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM super_admin");
        $stmt->execute();

        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'سوپر ادمین قبلاً ایجاد شده'];
        }

        // تولید اطلاعات پیشفرض
        $username = 'superadmin';
        $password = $this->generateSecurePassword();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $masterKeyHash = hash('sha256', $this->masterKey . $hardwareId);

        $stmt = $this->db->prepare("
            INSERT INTO super_admin (username, password, full_name, master_key, hardware_id) 
            VALUES (?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $username,
            $hashedPassword,
            'سوپر ادمین سیستم',
            $masterKeyHash,
            $hardwareId
        ]);

        if ($result) {
            return [
                'success' => true,
                'username' => $username,
                'password' => $password,
                'message' => 'سوپر ادمین ایجاد شد'
            ];
        }

        return ['success' => false, 'message' => 'خطا در ایجاد سوپر ادمین'];
    }

    /**
     * احراز هویت سوپر ادمین
     */
    public function authenticate($username, $password, $hardwareId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM super_admin 
            WHERE username = ? AND status = 'active' AND hardware_id = ?
        ");
        $stmt->execute([$username, $hardwareId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            return ['success' => false, 'message' => 'سوپر ادمین یافت نشد'];
        }

        if (!password_verify($password, $admin['password'])) {
            return ['success' => false, 'message' => 'رمز عبور اشتباه'];
        }

        // بروزرسانی آخرین ورود
        $stmt = $this->db->prepare("UPDATE super_admin SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$admin['id']]);

        return [
            'success' => true,
            'admin' => $admin,
            'message' => 'ورود موفق'
        ];
    }

    /**
     * بررسی دسترسی سوپر ادمین
     */
    public function isSuperAdmin($userId = null)
    {
        if (!isset($_SESSION['super_admin_id'])) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM super_admin 
            WHERE id = ? AND status = 'active'
        ");
        $stmt->execute([$_SESSION['super_admin_id']]);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * تغییر رمز عبور سوپر ادمین
     */
    public function changePassword($adminId, $oldPassword, $newPassword)
    {
        $stmt = $this->db->prepare("SELECT password FROM super_admin WHERE id = ?");
        $stmt->execute([$adminId]);
        $currentHash = $stmt->fetchColumn();

        if (!password_verify($oldPassword, $currentHash)) {
            return ['success' => false, 'message' => 'رمز عبور فعلی اشتباه'];
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE super_admin SET password = ? WHERE id = ?");
        $result = $stmt->execute([$newHash, $adminId]);

        return [
            'success' => $result,
            'message' => $result ? 'رمز عبور تغییر کرد' : 'خطا در تغییر رمز'
        ];
    }

    /**
     * دریافت اطلاعات سوپر ادمین
     */
    public function getSuperAdminInfo($adminId)
    {
        $stmt = $this->db->prepare("
            SELECT id, username, full_name, last_login, created_at 
            FROM super_admin WHERE id = ?
        ");
        $stmt->execute([$adminId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * غیرفعال کردن سوپر ادمین
     */
    public function disableSuperAdmin($adminId)
    {
        $stmt = $this->db->prepare("UPDATE super_admin SET status = 'disabled' WHERE id = ?");
        return $stmt->execute([$adminId]);
    }

    /**
     * تولید رمز عبور امن
     */
    private function generateSecurePassword($length = 12)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        return substr(str_shuffle($chars), 0, $length);
    }

    /**
     * بررسی وجود سوپر ادمین
     */
    public function superAdminExists()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM super_admin WHERE status = 'active'");
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    /**
     * ایجاد session سوپر ادمین
     */
    public function createSuperAdminSession($admin)
    {
        $_SESSION['super_admin_id'] = $admin['id'];
        $_SESSION['super_admin_username'] = $admin['username'];
        $_SESSION['super_admin_name'] = $admin['full_name'];
        $_SESSION['is_super_admin'] = true;
    }

    /**
     * حذف session سوپر ادمین
     */
    public function destroySuperAdminSession()
    {
        unset($_SESSION['super_admin_id']);
        unset($_SESSION['super_admin_username']);
        unset($_SESSION['super_admin_name']);
        unset($_SESSION['is_super_admin']);
    }
}
?>