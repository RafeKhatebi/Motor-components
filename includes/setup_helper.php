<?php
/**
 * Setup Helper - Functions for first login and system setup
 */

class SetupHelper {
    
    /**
     * Check if this is the first login (only super admin exists)
     */
    public static function isFirstLogin($db) {
        try {
            $query = "SELECT COUNT(*) as user_count FROM users";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['user_count'] == 1;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if system setup is complete
     */
    public static function isSetupComplete($db) {
        try {
            // Check if more than one user exists
            $user_query = "SELECT COUNT(*) as user_count FROM users";
            $user_stmt = $db->prepare($user_query);
            $user_stmt->execute();
            $user_result = $user_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if basic settings are configured
            $settings_query = "SELECT COUNT(*) as settings_count FROM settings WHERE setting_key IN ('shop_name', 'shop_phone')";
            $settings_stmt = $db->prepare($settings_query);
            $settings_stmt->execute();
            $settings_result = $settings_stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user_result['user_count'] > 1 || $settings_result['settings_count'] >= 2;
        } catch (Exception $e) {
            return true; // Assume setup is complete if we can't check
        }
    }
}
?>