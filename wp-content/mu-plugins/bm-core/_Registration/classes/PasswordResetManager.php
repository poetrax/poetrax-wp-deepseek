<?php
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
        $stmt = $this->db->prepare("SELECT id FROM bm_ctbl000_user WHERE user_email = ?");
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
            "INSERT INTO bm_ctbl000_password_resets (email, token, expires_at) 
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
            "SELECT email FROM bm_ctbl000_password_resets 
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
            "UPDATE bm_ctbl000_user SET password_hash = ? WHERE user_email = ?"
        );
        
        $stmt->execute([$hashedPassword, $resetRequest['user_email']]);
        
        // Удаляем использованный токен
        $this->db->prepare("DELETE FROM bm_ctbl000_password_resets WHERE token = ?")
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
$email = filter_var($input['user_email'] ?? '', FILTER_SANITIZE_EMAIL);

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