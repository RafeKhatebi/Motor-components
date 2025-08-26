<?php
/**
 * Secure File Upload Handler
 */
class FileUpload {
    
    private static $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    
    private static $maxSize = 5 * 1024 * 1024; // 5MB
    
    public static function uploadLogo($file, $uploadDir = 'uploads/logos/') {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'خطا در آپلود فایل'];
        }
        
        // Check file size
        if ($file['size'] > self::$maxSize) {
            return ['success' => false, 'message' => 'حجم فایل بیش از حد مجاز است'];
        }
        
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!array_key_exists($mimeType, self::$allowedTypes)) {
            return ['success' => false, 'message' => 'نوع فایل مجاز نیست'];
        }
        
        // Generate secure filename
        $extension = self::$allowedTypes[$mimeType];
        $filename = 'logo_' . uniqid() . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Create directory if not exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Additional security: verify image
            if (!getimagesize($filepath)) {
                unlink($filepath);
                return ['success' => false, 'message' => 'فایل معتبر نیست'];
            }
            
            return ['success' => true, 'path' => $filepath];
        }
        
        return ['success' => false, 'message' => 'خطا در ذخیره فایل'];
    }
    
    public static function deleteFile($filepath) {
        if (file_exists($filepath) && is_file($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
}
?>