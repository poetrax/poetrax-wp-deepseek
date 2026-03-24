<?php
/**
 * Класс для работы с конфигурацией тестов
 */

class TestConfig
{
    private static $instance = null;
    private $config = [];
    
    private function __construct()
    {
        $this->loadConfig();
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfig()
    {
        $configFile = __DIR__ . '/config/test-config.php';
        if (file_exists($configFile)) {
            $this->config = require $configFile;
        } else {
            throw new Exception("Config file not found. Please copy test-config.example.php to test-config.php");
        }
    }
    
    public function getBaseUrl(): string
    {
        return $this->config['base_url'] ?? 'http://localhost:8080';
    }
    
    public function url(string $endpoint): string
    {
        return rtrim($this->getBaseUrl(), '/') . '/' . ltrim($endpoint, '/');
    }
    
    public function getTimeout(): int
    {
        return $this->config['timeout'] ?? 30;
    }
    
    public function isDebug(): bool
    {
        return $this->config['debug'] ?? false;
    }
}
