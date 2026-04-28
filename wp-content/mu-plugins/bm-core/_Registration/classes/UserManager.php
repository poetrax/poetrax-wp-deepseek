<?php
class UserManager {
    private $db;
    private $errors = [];
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function clearErrors() {
        $this->errors = [];
    }
    
    // Проверка существования пользователя
    public function userExists($username, $email) {
        $stmt = $this->db->prepare(
            "SELECT id FROM bm_ctbl000_user WHERE user_login = ? OR user_email = ?"
        );
        
        $stmt->execute([$username, $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
    
    // Полная проверка данных регистрации
    public function validateRegistrationData($username, $email, $password, $confirmPassword) {
        $this->clearErrors();
        
        // Проверка имени пользователя
        if (empty($username)) {
            $this->errors['user_login'] = "Имя пользователя обязательно";
        } elseif (strlen($username) < 3) {
            $this->errors['user_login'] = "Имя пользователя должно быть не менее 3 символов";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $this->errors['user_login'] = "Имя пользователя может содержать только буквы, цифры и подчеркивания";
        }
        
        // Проверка email
        if (empty($email)) {
            $this->errors['user_email'] = "Email обязателен";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors['user_email'] = "Неверный формат email";
        }
        
        // Проверка паролей
        if (empty($password)) {
            $this->errors['user_pass'] = "Пароль обязателен";
        } else {
            $passwordErrors = PasswordManager::validatePasswordStrength($password);
            if (!empty($passwordErrors)) {
                $this->errors['user_pass'] = implode(", ", $passwordErrors);
            }
        }
        
        if (empty($confirmPassword)) {
            $this->errors['confirmPassword'] = "Подтверждение пароля обязательно";
        } elseif (!PasswordManager::passwordsMatch($password, $confirmPassword)) {
            $this->errors['confirmPassword'] = "Пароли не совпадают";
        }
        
        return empty($this->errors);
    }
    
    // Регистрация пользователя
    public function registerUser($username, $email, $password, $confirmPassword) {
        // Валидация данных
        if (!$this->validateRegistrationData($username, $email, $password, $confirmPassword)) {
            return false;
        }
        
        // Проверка существования пользователя
        if ($this->userExists($username, $email)) {
            $this->errors['general'] = "Пользователь с таким именем или email уже существует";
            return false;
        }
        
        try {
            // Хеширование пароля
            $hashedPassword = PasswordManager::hashPassword($password);
            
            // Сохранение в базу
            $stmt = $this->db->prepare(
                "INSERT INTO bm_ctbl000_user (user_login, user_email, password_hash, created_at) 
                 VALUES (?, ?, ?, NOW())"
            );
            
            $result = $stmt->execute([$username, $email, $hashedPassword]);
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            
            $this->errors['general'] = "Ошибка при сохранении пользователя";
            return false;
            
        } catch (PDOException $e) {
            $this->errors['general'] = "Ошибка базы данных: " . $e->getMessage();
            return false;
        }
    }
    
    // Авторизация пользователя
    public function authenticateUser($username, $password) {
        $this->clearErrors();
        
        if (empty($username) || empty($password)) {
            $this->errors['general'] = "Имя пользователя и пароль обязательны";
            return false;
        }
        
        $stmt = $this->db->prepare(
            "SELECT id, user_login, user_email, password_hash FROM bm_ctbl000_user WHERE user_login = ? OR user_email = ?"
        );
        
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $this->errors['general'] = "Пользователь не найден";
            return false;
        }
        
        if (!PasswordManager::verifyPassword($password, $user['password_hash'])) {
            $this->errors['general'] = "Неверный пароль";
            return false;
        }
        
        // Проверяем хеш
        if (PasswordManager::needsRehash($user['password_hash'])) {
            $this->updatePasswordHash($user['id'], $password);
        }
        
        return $user;
    }
    
    // Обновление хеша пароля
    private function updatePasswordHash($userId, $password) {
        $newHash = PasswordManager::hashPassword($password);
        
        $stmt = $this->db->prepare(
            "UPDATE bm_ctbl000_user SET password_hash = ? WHERE id = ?"
        );
        
        $stmt->execute([$newHash, $userId]);
    }
    
    // Смена пароля
    public function changePassword($userId, $currentPassword, $newPassword, $confirmPassword) {
        $this->clearErrors();
        
        // Получаем текущий хеш пароля
        $stmt = $this->db->prepare(
            "SELECT password_hash FROM bm_ctbl000_user WHERE id = ?"
        );
        
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $this->errors['general'] = "Пользователь не найден";
            return false;
        }
        
        // Проверяем текущий пароль
        if (!PasswordManager::verifyPassword($currentPassword, $user['password_hash'])) {
            $this->errors['currentPassword'] = "Неверный текущий пароль";
            return false;
        }
        
        // Проверяем новый пароль
        $passwordErrors = PasswordManager::validatePasswordStrength($newPassword);
        if (!empty($passwordErrors)) {
            $this->errors['newPassword'] = implode(", ", $passwordErrors);
            return false;
        }
        
        // Проверяем совпадение паролей
        if (!PasswordManager::passwordsMatch($newPassword, $confirmPassword)) {
            $this->errors['confirmPassword'] = "Пароли не совпадают";
            return false;
        }
        
        // Обновляем пароль
        $newHash = PasswordManager::hashPassword($newPassword);
        
        $stmt = $this->db->prepare(
            "UPDATE bm_ctbl000_user SET password_hash = ? WHERE id = ?"
        );
        
        return $stmt->execute([$newHash, $userId]);
    }
}