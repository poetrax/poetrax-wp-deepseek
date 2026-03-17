<?php
namespace BM\Core;

class Router
{
    private array $routes = [];
    private array $params = [];

    /**
     * Добавить GET маршрут
     */
    public function get(string $path, callable|array $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Добавить POST маршрут
     */
    public function post(string $path, callable|array $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Добавить PUT маршрут
     */
    public function put(string $path, callable|array $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Добавить DELETE маршрут
     */
    public function delete(string $path, callable|array $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Добавить маршрут
     */
    private function addRoute(string $method, string $path, callable|array $handler): self
    {
        $this->routes[$method][$path] = $handler;
        return $this;
    }

    /**
     * Запустить роутер
     */
    public function dispatch(string $requestUri, string $requestMethod): void
    {
        // Убираем query string
        $uri = parse_url($requestUri, PHP_URL_PATH);
        
        // Проверяем точное совпадение
        if (isset($this->routes[$requestMethod][$uri])) {
            $this->handle($this->routes[$requestMethod][$uri]);
            return;
        }

        // Ищем маршруты с параметрами
        foreach ($this->routes[$requestMethod] ?? [] as $route => $handler) {
            if ($this->matchRoute($route, $uri)) {
                $this->handle($handler);
                return;
            }
        }

        // 404
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => 'Маршрут не найден',
                'code' => 404
            ]
        ]);
    }

    /**
     * Проверить совпадение маршрута
     */
    private function matchRoute(string $route, string $uri): bool
    {
        $pattern = preg_replace('/\{([a-z]+)\}/', '(?P<$1>[^/]+)', $route);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $this->params[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Вызвать обработчик
     */
    private function handle(callable|array $handler): void
    {
        if (is_callable($handler)) {
            echo $handler();
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$controller, $method] = $handler;
            
            if (class_exists($controller) && method_exists($controller, $method)) {
                $instance = new $controller();
                
                // Передаём параметры из маршрута в метод
                $result = $instance->$method(...array_values($this->params));
                
                if (is_string($result)) {
                    echo $result;
                }
                return;
            }
        }

        throw new \RuntimeException('Invalid handler');
    }
}
