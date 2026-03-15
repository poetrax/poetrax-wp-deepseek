<?php
class WordPressPasswordHasher {
    // Создание хеша пароля
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Проверка пароля
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // Нужно ли обновить хеш (если изменился алгоритм)
    public static function needsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
}

// Пример использования
class UserManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Регистрация пользователя
    public function registerUser($username, $password, $email) {
        $hashedPassword = WordPressPasswordHasher::hashPassword($password);
        
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)"
        );
        
        return $stmt->execute([$username, $hashedPassword, $email]);
    }
    
    // Авторизация пользователя
    public function authenticateUser($username, $password) {
        $stmt = $this->db->prepare(
            "SELECT id, username, password_hash FROM users WHERE username = ?"
        );
        
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && WordPressPasswordHasher::verifyPassword($password, $user['password_hash'])) {
            // Проверяем是否需要更新 хеш
            if (WordPressPasswordHasher::needsRehash($user['password_hash'])) {
                $this->updatePasswordHash($user['id'], $password);
            }
            return $user;
        }
        
        return false;
    }
    
    // Обновление хеша пароля
    private function updatePasswordHash($userId, $password) {
        $newHash = WordPressPasswordHasher::hashPassword($password);
        
        $stmt = $this->db->prepare(
            "UPDATE users SET password_hash = ? WHERE id = ?"
        );
        
        $stmt->execute([$newHash, $userId]);
    }
}

//3. Пример использования

// Подключение к базе данных
$db = new PDO('mysql:host=localhost;dbname=your_database', 'username', 'password');

$userManager = new UserManager($db);

// Регистрация
$userManager->registerUser('john_doe', 'securepassword123', 'john@example.com');

// Авторизация
$user = $userManager->authenticateUser('john_doe', 'securepassword123');

if ($user) {
    echo "Авторизация успешна!";
    // Создание сессии или JWT токена
} else {
    echo "Неверные credentials";
}

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


//2. Класс для работы с пользователями

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
            "SELECT id FROM users WHERE username = ? OR email = ?"
        );
        
        $stmt->execute([$username, $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
    
    // Полная проверка данных регистрации
    public function validateRegistrationData($username, $email, $password, $confirmPassword) {
        $this->clearErrors();
        
        // Проверка имени пользователя
        if (empty($username)) {
            $this->errors['username'] = "Имя пользователя обязательно";
        } elseif (strlen($username) < 3) {
            $this->errors['username'] = "Имя пользователя должно быть не менее 3 символов";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $this->errors['username'] = "Имя пользователя может содержать только буквы, цифры и подчеркивания";
        }
        
        // Проверка email
        if (empty($email)) {
            $this->errors['email'] = "Email обязателен";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = "Неверный формат email";
        }
        
        // Проверка паролей
        if (empty($password)) {
            $this->errors['password'] = "Пароль обязателен";
        } else {
            $passwordErrors = PasswordManager::validatePasswordStrength($password);
            if (!empty($passwordErrors)) {
                $this->errors['password'] = implode(", ", $passwordErrors);
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
                "INSERT INTO users (username, email, password_hash, created_at) 
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
            "SELECT id, username, email, password_hash FROM users WHERE username = ? OR email = ?"
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
        
        // Проверяем是否需要更新 хеш
        if (PasswordManager::needsRehash($user['password_hash'])) {
            $this->updatePasswordHash($user['id'], $password);
        }
        
        return $user;
    }
    
    // Обновление хеша пароля
    private function updatePasswordHash($userId, $password) {
        $newHash = PasswordManager::hashPassword($password);
        
        $stmt = $this->db->prepare(
            "UPDATE users SET password_hash = ? WHERE id = ?"
        );
        
        $stmt->execute([$newHash, $userId]);
    }
    
    // Смена пароля
    public function changePassword($userId, $currentPassword, $newPassword, $confirmPassword) {
        $this->clearErrors();
        
        // Получаем текущий хеш пароля
        $stmt = $this->db->prepare(
            "SELECT password_hash FROM users WHERE id = ?"
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
            "UPDATE users SET password_hash = ? WHERE id = ?"
        );
        
        return $stmt->execute([$newHash, $userId]);
    }
}


//3. Обработчик формы регистрации

// register.php
require_once 'classes/PasswordManager.php';
require_once 'classes/UserManager.php';

header('Content-Type: application/json');

// Подключение к базе данных
try {
    $db = new PDO('mysql:host=localhost;dbname=your_database;charset=utf8', 'username', 'password');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка подключения к базе данных']);
    exit;
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
    exit;
}

// Получаем данные из запроса
$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$confirmPassword = $input['confirmPassword'] ?? '';

// Создаем менеджер пользователей
$userManager = new UserManager($db);

// Пытаемся зарегистрировать пользователя
$userId = $userManager->registerUser($username, $email, $password, $confirmPassword);

if ($userId) {
    // Успешная регистрация
    echo json_encode([
        'success' => true,
        'message' => 'Пользователь успешно зарегистрирован',
        'userId' => $userId
    ]);
} else {
    // Ошибки регистрации
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'errors' => $userManager->getErrors()
    ]);
}

//4. Обработчик формы авторизации

// login.php
require_once 'classes/PasswordManager.php';
require_once 'classes/UserManager.php';

header('Content-Type: application/json');

// Подключение к базе данных
try {
    $db = new PDO('mysql:host=localhost;dbname=your_database;charset=utf8', 'username', 'password');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка подключения к базе данных']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

$userManager = new UserManager($db);
$user = $userManager->authenticateUser($username, $password);

if ($user) {
    // Создаем сессию или JWT токен
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'errors' => $userManager->getErrors()
    ]);
}

//5. Обработчик смены пароля
// change-password.php
require_once 'classes/PasswordManager.php';
require_once 'classes/UserManager.php';

header('Content-Type: application/json');

session_start();

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

// Подключение к базе данных
try {
    $db = new PDO('mysql:host=localhost;dbname=your_database;charset=utf8', 'username', 'password');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка подключения к базе данных']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$currentPassword = $input['currentPassword'] ?? '';
$newPassword = $input['newPassword'] ?? '';
$confirmPassword = $input['confirmPassword'] ?? '';

$userManager = new UserManager($db);
$success = $userManager->changePassword(
    $_SESSION['user_id'],
    $currentPassword,
    $newPassword,
    $confirmPassword
);

if ($success) {
    echo json_encode([
        'success' => true,
        'message' => 'Пароль успешно изменен'
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'errors' => $userManager->getErrors()
    ]);
}

/*
6. HTML форма с JavaScript валидацией

<!-- register.html -->
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <style>
        .error { color: red; font-size: 14px; }
        .success { color: green; }
        .form-group { margin-bottom: 15px; }
        input.error { border-color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Регистрация</h2>
        
        <form id="registerForm">
            <div class="form-group">
                <label>Имя пользователя:</label>
                <input type="text" name="username" required>
                <div class="error" id="usernameError"></div>
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
                <div class="error" id="emailError"></div>
            </div>
            
            <div class="form-group">
                <label>Пароль:</label>
                <input type="password" name="password" required>
                <div class="error" id="passwordError"></div>
                <small>Минимум 8 символов, заглавные и строчные буквы, цифры, спецсимволы</small>
            </div>
            
            <div class="form-group">
                <label>Подтвердите пароль:</label>
                <input type="password" name="confirmPassword" required>
                <div class="error" id="confirmPasswordError"></div>
            </div>
            
            <div class="error" id="generalError"></div>
            <div class="success" id="successMessage"></div>
            
            <button type="submit">Зарегистрироваться</button>
        </form>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Очищаем ошибки
            clearErrors();
            
            const formData = new FormData(this);
            const data = {
                username: formData.get('username'),
                email: formData.get('email'),
                password: formData.get('password'),
                confirmPassword: formData.get('confirmPassword')
            };
            
            try {
                const response = await fetch('register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess('Регистрация успешна!');
                    // Перенаправляем или очищаем форму
                    this.reset();
                } else {
                    showErrors(result.errors);
                }
                
            } catch (error) {
                showError('generalError', 'Ошибка сети');
            }
        });
        
        function showErrors(errors) {
            for (const [field, message] of Object.entries(errors)) {
                showError(field + 'Error', message);
            }
        }
        
        function showError(elementId, message) {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = message;
            }
        }
        
        function showSuccess(message) {
            document.getElementById('successMessage').textContent = message;
        }
        
        function clearErrors() {
            const errorElements = document.querySelectorAll('.error');
            errorElements.forEach(el => el.textContent = '');
            document.getElementById('successMessage').textContent = '';
        }
        
        // Валидация в реальном времени
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this.name, this.value);
            });
        });
        
        function validateField(fieldName, value) {
            // Простая клиентская валидация
            switch (fieldName) {
                case 'username':
                    if (value.length < 3) {
                        showError('usernameError', 'Минимум 3 символа');
                    }
                    break;
                case 'email':
                    if (!value.includes('@')) {
                        showError('emailError', 'Неверный формат email');
                    }
                    break;
                case 'password':
                    if (value.length < 8) {
                        showError('passwordError', 'Минимум 8 символов');
                    }
                    break;
            }
        }
    </script>
</body>
</html>
*/

//2. Восстановление пароля через Email
//PHP Backend для восстановления пароля

// PasswordResetManager.php
class PasswordResetManager {
    private $db;
    private $mailer;
    
    public function __construct($db, $mailer = null) {
        $this->db = $db;
        $this->mailer = $mailer;
    }
    
    // Запрос на восстановление пароля
    public function requestPasswordReset($email) {
        // Проверяем существование email
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => true]; // Для безопасности не сообщаем, что email не существует
        }
        
        // Генерируем токен
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Сохраняем токен в базу
        $stmt = $this->db->prepare(
            "INSERT INTO password_resets (email, token, expires_at) 
             VALUES (?, ?, ?) 
             ON DUPLICATE KEY UPDATE token = ?, expires_at = ?"
        );
        
        $stmt->execute([$email, $token, $expires, $token, $expires]);
        
        // Отправляем email
        if ($this->mailer) {
            $this->sendPasswordResetEmail($email, $token);
        }
        
        return ['success' => true];
    }
    
    // Сброс пароля
    public function resetPassword($token, $newPassword) {
        // Проверяем токен
        $stmt = $this->db->prepare(
            "SELECT email FROM password_resets 
             WHERE token = ? AND expires_at > NOW()"
        );
        
        $stmt->execute([$token]);
        $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resetRequest) {
            return ['success' => false, 'error' => 'Недействительный или просроченный токен'];
        }
        
        // Хешируем новый пароль
        $hashedPassword = PasswordManager::hashPassword($newPassword);
        
        // Обновляем пароль
        $stmt = $this->db->prepare(
            "UPDATE users SET password_hash = ? WHERE email = ?"
        );
        
        $stmt->execute([$hashedPassword, $resetRequest['email']]);
        
        // Удаляем использованный токен
        $this->db->prepare("DELETE FROM password_resets WHERE token = ?")
                 ->execute([$token]);
        
        return ['success' => true];
    }
    
    private function sendPasswordResetEmail($email, $token) {
        $resetLink = "https://yourdomain.com/reset-password?token=$token";
        
        $subject = "Восстановление пароля";
        $message = "
            <h2>Восстановление пароля</h2>
            <p>Для восстановления пароля перейдите по ссылке:</p>
            <a href='$resetLink'>$resetLink</a>
            <p>Ссылка действительна в течение 1 часа.</p>
            <p>Если вы не запрашивали восстановление пароля, проигнорируйте это письмо.</p>
        ";
        
        // Отправка email через PHPMailer или другую библиотеку
        $this->mailer->sendEmail($email, $subject, $message);
    }
}

// forgot-password.php
require_once 'PasswordResetManager.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Неверный формат email']);
    exit;
}

$resetManager = new PasswordResetManager($db);
$result = $resetManager->requestPasswordReset($email);

echo json_encode($result);

// reset-password.php
require_once 'PasswordResetManager.php';
require_once 'PasswordManager.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$newPassword = $input['newPassword'] ?? '';

// Валидация пароля
$passwordErrors = PasswordManager::validatePasswordStrength($newPassword);
if (!empty($passwordErrors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => implode(', ', $passwordErrors)]);
    exit;
}

$resetManager = new PasswordResetManager($db);
$result = $resetManager->resetPassword($token, $newPassword);

if ($result['success']) {
    echo json_encode(['success' => true, 'message' => 'Пароль успешно изменен']);
} else {
    http_response_code(400);
    echo json_encode($result);
}


//3. Двухфакторная аутентификация (2FA)
//PHP Backend для 2FA


// TwoFAManager.php
require_once 'vendor/autoload.php'; // Для Google Authenticator

use RobThree\Auth\TwoFactorAuth;

class TwoFAManager {
    private $db;
    private $tfa;
    
    public function __construct($db) {
        $this->db = $db;
        $this->tfa = new TwoFactorAuth();
    }
    
    // Генерация секрета для 2FA
    public function generateSecret($userId) {
        $secret = $this->tfa->createSecret();
        
        $stmt = $this->db->prepare(
            "UPDATE users SET twofa_secret = ?, twofa_enabled = false WHERE id = ?"
        );
        
        $stmt->execute([$secret, $userId]);
        
        return $secret;
    }
    
    // Получение QR кода
    public function getQRCode($userId, $secret, $username) {
        return $this->tfa->getQRCodeImageAsDataUri(
            'MyApp',
            $username,
            $secret
        );
    }
    
    // Включение 2FA
    public function enable2FA($userId, $code) {
        $stmt = $this->db->prepare("SELECT twofa_secret FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !$user['twofa_secret']) {
            return false;
        }
        
        // Проверяем код
        $isValid = $this->tfa->verifyCode($user['twofa_secret'], $code);
        
        if ($isValid) {
            $stmt = $this->db->prepare(
                "UPDATE users SET twofa_enabled = true WHERE id = ?"
            );
            $stmt->execute([$userId]);
            return true;
        }
        
        return false;
    }
    
    // Отключение 2FA
    public function disable2FA($userId) {
        $stmt = $this->db->prepare(
            "UPDATE users SET twofa_secret = NULL, twofa_enabled = false WHERE id = ?"
        );
        return $stmt->execute([$userId]);
    }
    
    // Проверка 2FA кода
    public function verifyCode($userId, $code) {
        $stmt = $this->db->prepare(
            "SELECT twofa_secret FROM users WHERE id = ? AND twofa_enabled = true"
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        return $this->tfa->verifyCode($user['twofa_secret'], $code);
    }
}

// enable-2fa.php
require_once 'TwoFAManager.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

$twoFAManager = new TwoFAManager($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Генерация нового секрета
    $secret = $twoFAManager->generateSecret($_SESSION['user_id']);
    $qrCode = $twoFAManager->getQRCode(
        $_SESSION['user_id'], 
        $secret, 
        $_SESSION['username']
    );
    
    echo json_encode(['success' => true, 'secret' => $secret, 'qrCode' => $qrCode]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Включение 2FA
    $input = json_decode(file_get_contents('php://input'), true);
    $code = $input['code'] ?? '';
    
    if ($twoFAManager->enable2FA($_SESSION['user_id'], $code)) {
        echo json_encode(['success' => true, 'message' => '2FA успешно включена']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Неверный код']);
    }
}

// verify-2fa.php
require_once 'TwoFAManager.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';

// Здесь должна быть логика получения userId из сессии или JWT
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

$twoFAManager = new TwoFAManager($db);

if ($twoFAManager->verifyCode($_SESSION['user_id'], $code)) {
    // Генерация финального JWT токена
    $jwt = generateJWT($_SESSION['user_id']);
    echo json_encode(['success' => true, 'token' => $jwt]);
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Неверный код 2FA']);
}






