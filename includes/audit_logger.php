<?php
/**
 * Audit Logger for tracking important system operations
 */

class AuditLogger
{

    private static $db = null;

    private static function getDB()
    {
        if (self::$db === null) {
            $database = new Database();
            self::$db = $database->getConnection();
        }
        return self::$db;
    }

    /**
     * Log an action to the audit trail
     */
    public static function log($action, $table_name, $record_id = null, $old_values = null, $new_values = null)
    {
        try {
            $db = self::getDB();

            $user_id = $_SESSION['user_id'] ?? null;
            $ip_address = self::getClientIP();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            $query = "INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $user_id,
                $action,
                $table_name,
                $record_id,
                $old_values ? json_encode($old_values) : null,
                $new_values ? json_encode($new_values) : null,
                $ip_address,
                $user_agent
            ]);

        } catch (Exception $e) {
            error_log('Audit log error: ' . $e->getMessage());
        }
    }

    /**
     * Log product operations
     */
    public static function logProduct($action, $product_id, $old_data = null, $new_data = null)
    {
        self::log($action, 'products', $product_id, $old_data, $new_data);
    }

    /**
     * Log sale operations
     */
    public static function logSale($action, $sale_id, $old_data = null, $new_data = null)
    {
        self::log($action, 'sales', $sale_id, $old_data, $new_data);
    }

    /**
     * Log purchase operations
     */
    public static function logPurchase($action, $purchase_id, $old_data = null, $new_data = null)
    {
        self::log($action, 'purchases', $purchase_id, $old_data, $new_data);
    }

    /**
     * Log user operations
     */
    public static function logUser($action, $user_id, $old_data = null, $new_data = null)
    {
        self::log($action, 'users', $user_id, $old_data, $new_data);
    }

    /**
     * Log stock changes
     */
    public static function logStockChange($product_id, $old_quantity, $new_quantity, $reason)
    {
        $old_data = ['stock_quantity' => $old_quantity];
        $new_data = ['stock_quantity' => $new_quantity, 'reason' => $reason];
        self::log('stock_change', 'products', $product_id, $old_data, $new_data);
    }

    /**
     * Get client IP address
     */
    private static function getClientIP()
    {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get audit logs for a specific table and record
     */
    public static function getAuditLogs($table_name, $record_id = null, $limit = 50)
    {
        try {
            $db = self::getDB();

            $query = "SELECT al.*, u.username, u.full_name 
                      FROM audit_log al 
                      LEFT JOIN users u ON al.user_id = u.id 
                      WHERE al.table_name = ?";

            $params = [$table_name];

            if ($record_id !== null) {
                $query .= " AND al.record_id = ?";
                $params[] = $record_id;
            }

            $query .= " ORDER BY al.created_at DESC LIMIT ?";
            $params[] = $limit;

            $stmt = $db->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get audit logs error: ' . $e->getMessage());
            return [];
        }
    }
}
?>