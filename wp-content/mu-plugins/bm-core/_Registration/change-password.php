<?php
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