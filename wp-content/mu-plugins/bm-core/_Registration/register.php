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