<?php
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
$username = trim($input['user_login'] ?? '');
$password = $input['user_pass'] ?? '';

$userManager = new UserManager($db);
$user = $userManager->authenticateUser($username, $password);

if ($user) {
    // Создаем сессию или JWT токен
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_login'] = $user['user_login'];
    $_SESSION['user_email'] = $user['user_email'];
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'user_login' => $user['user_login'],
            'user_email' => $user['user_email']
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'errors' => $userManager->getErrors()
    ]);
}