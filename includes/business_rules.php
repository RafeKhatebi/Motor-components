<?php
/**
 * Business Rules Validation
 */

class BusinessRules
{

    /**
     * Validate product pricing
     */
    public static function validateProductPricing($buy_price, $sell_price)
    {
        $errors = [];

        if ($buy_price <= 0) {
            $errors[] = 'قیمت خرید باید مثبت باشد';
        }

        if ($sell_price <= 0) {
            $errors[] = 'قیمت فروش باید مثبت باشد';
        }

        if ($sell_price <= $buy_price) {
            $errors[] = 'قیمت فروش باید بیشتر از قیمت خرید باشد';
        }

        return $errors;
    }

    /**
     * Validate stock quantities
     */
    public static function validateStock($stock_quantity, $min_stock = null)
    {
        $errors = [];

        if ($stock_quantity < 0) {
            $errors[] = 'موجودی نمیتواند منفی باشد';
        }

        if ($min_stock !== null && $min_stock < 0) {
            $errors[] = 'حداقل موجودی نمیتواند منفی باشد';
        }

        return $errors;
    }

    /**
     * Validate discount amount
     */
    public static function validateDiscount($discount, $total_amount)
    {
        $errors = [];

        if ($discount < 0) {
            $errors[] = 'تخفیف نمیتواند منفی باشد';
        }

        if ($discount > $total_amount) {
            $errors[] = 'تخفیف نمیتواند بیشتر از مبلغ کل باشد';
        }

        return $errors;
    }

    /**
     * Validate sale transaction
     */
    public static function validateSaleTransaction($items, $discount = 0)
    {
        $errors = [];
        $total_amount = 0;

        if (empty($items)) {
            $errors[] = 'حداقل یک محصول باید انتخاب شود';
            return $errors;
        }

        foreach ($items as $item) {
            if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                $errors[] = 'تعداد محصولات باید مثبت باشد';
            }

            if (!isset($item['price']) || $item['price'] <= 0) {
                $errors[] = 'قیمت محصولات باید مثبت باشد';
            }

            if (isset($item['quantity']) && isset($item['price'])) {
                $total_amount += $item['quantity'] * $item['price'];
            }
        }

        // Validate discount
        $discount_errors = self::validateDiscount($discount, $total_amount);
        $errors = array_merge($errors, $discount_errors);

        return $errors;
    }

    /**
     * Check if return is allowed
     */
    public static function canReturnTransaction($transaction_date, $max_days = 30)
    {
        $transaction_timestamp = strtotime($transaction_date);
        $current_timestamp = time();
        $days_passed = ($current_timestamp - $transaction_timestamp) / (24 * 60 * 60);

        return $days_passed <= $max_days;
    }

    /**
     * Validate phone number format
     */
    public static function validatePhoneNumber($phone)
    {
        if (empty($phone)) {
            return true; // Phone is optional
        }

        // Afghan phone number format: 07XXXXXXXX
        if (!preg_match('/^07\d{8}$/', $phone)) {
            return 'شماره تلفن باید با 07 شروع شود و 10 رقم باشد';
        }

        return true;
    }

    /**
     * Validate user role
     */
    public static function validateUserRole($role)
    {
        $valid_roles = ['admin', 'manager', 'employee'];

        if (!in_array($role, $valid_roles)) {
            return 'نقش کاربری نامعتبر است';
        }

        return true;
    }

    /**
     * Check if user can be deleted
     */
    public static function canDeleteUser($user_id, $current_user_id)
    {
        if ($user_id == $current_user_id) {
            return 'نمیتوانید خودتان را حذف کنید';
        }

        return true;
    }
}
?>