<?php
/**
 * Permission Management System
 */

class PermissionManager
{

    const PERMISSIONS = [
        'admin' => [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',
            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',
            'sales.view',
            'sales.create',
            'sales.edit',
            'sales.delete',
            'sales.return',
            'purchases.view',
            'purchases.create',
            'purchases.edit',
            'purchases.delete',
            'purchases.return',
            'reports.view',
            'reports.financial',
            'backup.create',
            'backup.download',
            'settings.view',
            'settings.edit'
        ],
        'manager' => [
            'products.view',
            'products.create',
            'products.edit',
            'categories.view',
            'categories.create',
            'categories.edit',
            'customers.view',
            'customers.create',
            'customers.edit',
            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'sales.view',
            'sales.create',
            'sales.return',
            'purchases.view',
            'purchases.create',
            'purchases.return',
            'reports.view',
            'reports.financial'
        ],
        'employee' => [
            'products.view',
            'categories.view',
            'customers.view',
            'customers.create',
            'sales.view',
            'sales.create'
        ]
    ];

    public static function hasPermission($permission)
    {
        if (!isset($_SESSION['role'])) {
            return false;
        }

        $role = $_SESSION['role'];
        $permissions = self::PERMISSIONS[$role] ?? [];

        return in_array($permission, $permissions);
    }

    public static function requirePermission($permission)
    {
        if (!self::hasPermission($permission)) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'دسترسی مجاز نیست']);
                exit();
            } else {
                header('Location: /motor/dashboard.php?error=access_denied');
                exit();
            }
        }
    }

    public static function canAccess($page)
    {
        $pagePermissions = [
            'users.php' => 'users.view',
            'products.php' => 'products.view',
            'categories.php' => 'categories.view',
            'customers.php' => 'customers.view',
            'suppliers.php' => 'suppliers.view',
            'sales.php' => 'sales.view',
            'purchases.php' => 'purchases.view',
            'reports.php' => 'reports.view',
            'backup.php' => 'backup.create',
            'settings.php' => 'settings.view'
        ];

        $permission = $pagePermissions[$page] ?? null;
        return $permission ? self::hasPermission($permission) : true;
    }
}

// Helper functions for backward compatibility
function hasPermission($permission)
{
    return PermissionManager::hasPermission($permission);
}

function requirePermission($permission)
{
    return PermissionManager::requirePermission($permission);
}
?>