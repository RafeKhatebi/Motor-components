<?php
/**
 * سیستم مدیریت لایسنس آفلاین
 */
class LicenseManager
{
    private $db;
    private $encryptionKey = 'MOTOR_SHOP_LICENSE_KEY_2024';
    private $superAdminManager;

    public function __construct($database, $superAdminManager = null)
    {
        $this->db = $database;
        $this->superAdminManager = $superAdminManager;
        $this->initializeLicenseTable();
    }

    /**
     * ایجاد جدول لایسنس
     */
    private function initializeLicenseTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS system_license (
            id INT PRIMARY KEY AUTO_INCREMENT,
            hardware_id VARCHAR(255) UNIQUE NOT NULL,
            license_key VARCHAR(500) NOT NULL,
            status ENUM('active', 'expired', 'disabled') DEFAULT 'active',
            issued_date DATETIME NOT NULL,
            expiry_date DATETIME NOT NULL,
            max_users INT DEFAULT 5,
            features JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB";

        $this->db->exec($sql);
    }

    /**
     * تولید شناسه یونیک سیستم
     */
    public function generateHardwareID()
    {
        $components = [
            php_uname('n'), // hostname
            php_uname('s'), // OS
            $_SERVER['SERVER_NAME'] ?? 'localhost',
            $_SERVER['DOCUMENT_ROOT'] ?? '',
            disk_total_space('.') ?? 0
        ];

        $rawId = implode('|', $components);
        return hash('sha256', $rawId);
    }

    /**
     * تولید کلید لایسنس
     */
    public function generateLicenseKey($hardwareId, $expiryDays = 365, $maxUsers = 5, $features = [])
    {
        $data = [
            'hardware_id' => $hardwareId,
            'issued' => time(),
            'expires' => time() + ($expiryDays * 24 * 3600),
            'max_users' => $maxUsers,
            'features' => $features
        ];

        $jsonData = json_encode($data);
        $encrypted = $this->encrypt($jsonData);
        return base64_encode($encrypted);
    }

    /**
     * فعالسازی لایسنس
     */
    public function activateLicense($licenseKey)
    {
        try {
            $decrypted = $this->decrypt(base64_decode($licenseKey));
            $data = json_decode($decrypted, true);

            if (!$data) {
                return ['success' => false, 'message' => 'کلید لایسنس نامعتبر'];
            }

            $currentHardwareId = $this->generateHardwareID();

            if ($data['hardware_id'] !== $currentHardwareId) {
                return ['success' => false, 'message' => 'لایسنس برای این سیستم صادر نشده'];
            }

            if ($data['expires'] < time()) {
                return ['success' => false, 'message' => 'لایسنس منقضی شده'];
            }

            // ذخیره در دیتابیس
            $stmt = $this->db->prepare("
                INSERT INTO system_license 
                (hardware_id, license_key, status, issued_date, expiry_date, max_users, features) 
                VALUES (?, ?, 'active', ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                license_key = VALUES(license_key),
                status = 'active',
                expiry_date = VALUES(expiry_date),
                max_users = VALUES(max_users),
                features = VALUES(features),
                updated_at = CURRENT_TIMESTAMP
            ");

            $stmt->execute([
                $data['hardware_id'],
                $licenseKey,
                date('Y-m-d H:i:s', $data['issued']),
                date('Y-m-d H:i:s', $data['expires']),
                $data['max_users'],
                json_encode($data['features'])
            ]);

            return ['success' => true, 'message' => 'لایسنس با موفقیت فعال شد'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'خطا در فعالسازی: ' . $e->getMessage()];
        }
    }

    /**
     * بررسی اعتبار لایسنس
     */
    public function validateLicense()
    {
        $hardwareId = $this->generateHardwareID();

        $stmt = $this->db->prepare("
            SELECT * FROM system_license 
            WHERE hardware_id = ? AND status = 'active'
            ORDER BY updated_at DESC LIMIT 1
        ");
        $stmt->execute([$hardwareId]);
        $license = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$license) {
            return ['valid' => false, 'message' => 'لایسنس یافت نشد'];
        }

        if (strtotime($license['expiry_date']) < time()) {
            // به‌روزرسانی وضعیت به منقضی
            $this->updateLicenseStatus($hardwareId, 'expired');
            return ['valid' => false, 'message' => 'لایسنس منقضی شده'];
        }

        return [
            'valid' => true,
            'license' => $license,
            'days_remaining' => ceil((strtotime($license['expiry_date']) - time()) / (24 * 3600))
        ];
    }

    /**
     * غیرفعال کردن لایسنس
     */
    public function disableLicense($hardwareId = null)
    {
        $hardwareId = $hardwareId ?: $this->generateHardwareID();
        return $this->updateLicenseStatus($hardwareId, 'disabled');
    }

    /**
     * تمدید لایسنس
     */
    public function extendLicense($hardwareId, $additionalDays)
    {
        $stmt = $this->db->prepare("
            UPDATE system_license 
            SET expiry_date = DATE_ADD(expiry_date, INTERVAL ? DAY),
                status = 'active',
                updated_at = CURRENT_TIMESTAMP
            WHERE hardware_id = ?
        ");

        return $stmt->execute([$additionalDays, $hardwareId]);
    }

    /**
     * بازنشانی لایسنس
     */
    public function resetLicense($hardwareId = null)
    {
        $hardwareId = $hardwareId ?: $this->generateHardwareID();

        $stmt = $this->db->prepare("DELETE FROM system_license WHERE hardware_id = ?");
        return $stmt->execute([$hardwareId]);
    }

    /**
     * دریافت اطلاعات لایسنس
     */
    public function getLicenseInfo($hardwareId = null)
    {
        $hardwareId = $hardwareId ?: $this->generateHardwareID();

        $stmt = $this->db->prepare("SELECT * FROM system_license WHERE hardware_id = ?");
        $stmt->execute([$hardwareId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * به‌روزرسانی وضعیت لایسنس
     */
    private function updateLicenseStatus($hardwareId, $status)
    {
        $stmt = $this->db->prepare("
            UPDATE system_license 
            SET status = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE hardware_id = ?
        ");
        return $stmt->execute([$status, $hardwareId]);
    }

    /**
     * رمزگذاری
     */
    private function encrypt($data)
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $this->encryptionKey, 0, $iv);
        return $iv . $encrypted;
    }

    /**
     * رمزگشایی
     */
    private function decrypt($data)
    {
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $this->encryptionKey, 0, $iv);
    }

    /**
     * بررسی دسترسی ویژگی
     */
    public function hasFeature($feature)
    {
        $validation = $this->validateLicense();
        if (!$validation['valid'])
            return false;

        $features = json_decode($validation['license']['features'], true) ?: [];
        return in_array($feature, $features) || empty($features);
    }

    /**
     * بررسی حداکثر کاربران
     */
    public function checkUserLimit()
    {
        $validation = $this->validateLicense();
        if (!$validation['valid'])
            return false;

        $maxUsers = $validation['license']['max_users'];

        // شمارش کاربران فعال (بدون سوپر ادمین)
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        $currentUsers = $stmt->fetchColumn();

        return $currentUsers < $maxUsers;
    }

    /**
     * بررسی دسترسی سوپر ادمین برای مدیریت لایسنس
     */
    public function requireSuperAdminAccess()
    {
        if (!$this->superAdminManager || !$this->superAdminManager->isSuperAdmin()) {
            throw new Exception('فقط سوپر ادمین میتواند لایسنس را مدیریت کند');
        }
    }
}
?>