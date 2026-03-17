<?php
namespace BM\Core\Controller;

use BM\Core\Http\JsonResponse;

abstract class BaseController
{
    /**
     * Отправить успешный ответ
     */
    protected function jsonSuccess($data = null, string $message = '', int $code = 200): string
    {
        http_response_code($code);
        header('Content-Type: application/json');
        
        return json_encode([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'timestamp' => time()
        ]);
    }

    /**
     * Отправить ответ с ошибкой
     */
    protected function jsonError(string $message, int $code = 400, $details = null): string
    {
        http_response_code($code);
        header('Content-Type: application/json');
        
        return json_encode([
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code,
                'details' => $details
            ],
            'timestamp' => time()
        ]);
    }

    /**
     * Получить параметр из запроса (GET, POST или JSON)
     */
    protected function getParam(string $name, $default = null)
    {
        // Проверяем JSON body
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input[$name])) {
            return $input[$name];
        }

        // Проверяем POST
        if (isset($_POST[$name])) {
            return $_POST[$name];
        }

        // Проверяем GET
        if (isset($_GET[$name])) {
            return $_GET[$name];
        }

        return $default;
    }

    /**
     * Получить все параметры запроса
     */
    protected function getParams(): array
    {
        $params = array_merge($_GET, $_POST);
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input)) {
            $params = array_merge($params, $input);
        }

        return $params;
    }

    /**
     * Проверить метод запроса
     */
    protected function isMethod(string $method): bool
    {
        return $_SERVER['REQUEST_METHOD'] === strtoupper($method);
    }

    /**
     * Проверить, авторизован ли пользователь
     */
    protected function isAuthenticated(): bool
    {
        // TODO: добавить проверку JWT или сессии
        return isset($_SESSION['user_id']);
    }

    /**
     * Получить ID текущего пользователя
     */
    protected function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
}
