<?php
namespace BM\Core\Database;

use BM\Core\Config;

class TableMapper
{
    private array $tables;
    private static ?self $instance = null;

    private function __construct()
    {
    	$this->tables = Config::getInstance()->get('tables', []);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Получить реальное имя таблицы по ключу
     * @throws \InvalidArgumentException
     */
    public function get(string $key): string
    {
        if (!isset($this->tables[$key])) {
            throw new \InvalidArgumentException("Table key '{$key}' not defined in config");
        }
        return $this->tables[$key];
    }

    /**
     * Проверить, существует ли ключ таблицы
     */
    public function has(string $key): bool
    {
        return isset($this->tables[$key]);
    }

    /**
     * Получить все таблицы
     */
    public function getAll(): array
    {
        return $this->tables;
    }
}

