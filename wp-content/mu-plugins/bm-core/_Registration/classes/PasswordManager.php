<?php
class PasswordManager {
    
    // Проверка сложности пароля
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "Пароль должен содержать минимум 8 символов";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Пароль должен содержать хотя бы одну заглавную букву";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Пароль должен содержать хотя бы одну строчную букву";
        }
        
        if (!preg_match('/\d/', $password)) {
            $errors[] = "Пароль должен содержать хотя бы одну цифру";
        }
        
        if (!preg_match('/[@$!%*?&]/', $password)) {
            $errors[] = "Пароль должен содержать хотя бы один спецсимвол (@$!%*?&)";
        }
        
        return $errors;
    }
    
    // Создание хеша пароля
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }
    
    // Проверка пароля
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // Нужно ли обновить хеш
    public static function needsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
    
    // Проверка совпадения паролей
    public static function passwordsMatch($password, $confirmPassword) {
        return $password === $confirmPassword;
    }
}