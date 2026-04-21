<?php
namespace BM\Core;

class Config
{
    private static ?self $instance = null;
    private array $config = [];

    private function __construct()
    {
        $configFile = __DIR__ . '/../../../Config.php';
        if (!file_exists($configFile)) {
            throw new \Exception('Config file not found: ' . $configFile);
        }
        $this->config = require $configFile;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Получить значение по ключу (поддерживает точечную нотацию: 'database.host')
     */
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Проверить существование ключа
     */
    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }
            $value = $value[$segment];
        }

        return true;
    }

    /**
     * Получить всю конфигурацию
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Установить значение (для runtime-конфигурации, не сохраняется в файл)
     */
    public function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $ref = &$this->config;

        foreach ($keys as $i => $segment) {
            if ($i === count($keys) - 1) {
                $ref[$segment] = $value;
            } else {
                if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                    $ref[$segment] = [];
                }
                $ref = &$ref[$segment];
            }
        }
    }
}