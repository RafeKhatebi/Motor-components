<?php
/**
 * Input Validation Helper
 */
class Validator
{

    public static function sanitizeString($input, $maxLength = 255)
    {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return mb_substr($input, 0, $maxLength);
    }

    public static function validatePrice($price)
    {
        $price = filter_var($price, FILTER_VALIDATE_FLOAT);
        return $price !== false && $price > 0;
    }

    public static function validateQuantity($quantity)
    {
        $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
        return $quantity !== false && $quantity >= 0;
    }

    public static function validatePhone($phone)
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        return preg_match('/^(\+93|0)?[0-9]{9,10}$/', $phone);
    }

    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validateProductCode($code)
    {
        return preg_match('/^[A-Z0-9\-]{3,20}$/', $code);
    }

    public static function validateRequired($fields, $data)
    {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $errors[] = "فیلد {$field} الزامی است";
            }
        }
        return $errors;
    }
}
?>