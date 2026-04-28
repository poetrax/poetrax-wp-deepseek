<?php
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
            "UPDATE bm_ctbl000_user SET twofa_secret = ?, twofa_enabled = false WHERE id = ?"
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
        $stmt = $this->db->prepare("SELECT twofa_secret FROM bm_ctbl000_user WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !$user['twofa_secret']) {
            return false;
        }
        
        // Проверяем код
        $isValid = $this->tfa->verifyCode($user['twofa_secret'], $code);
        
        if ($isValid) {
            $stmt = $this->db->prepare(
                "UPDATE bm_ctbl000_user SET twofa_enabled = true WHERE id = ?"
            );
            $stmt->execute([$userId]);
            return true;
        }
        
        return false;
    }
    
    // Отключение 2FA
    public function disable2FA($userId) {
        $stmt = $this->db->prepare(
            "UPDATE bm_ctbl000_user SET twofa_secret = NULL, twofa_enabled = false WHERE id = ?"
        );
        return $stmt->execute([$userId]);
    }
    
    // Проверка 2FA кода
    public function verifyCode($userId, $code) {
        $stmt = $this->db->prepare(
            "SELECT twofa_secret FROM bm_ctbl000_user WHERE id = ? AND twofa_enabled = true"
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