<?php
namespace BM\Core\Database;

use PDO;
use PDOException;

class Connection
{
    private static $instance = null;
    private static $pdo = null;
    private $config;

    private function __construct(array $config)
    {
        $required = ['host', 'database', 'username', 'password'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new Exception("Database configuration missing: {$key}");
            }
        }
        $this->config = $config;
        $this->connect();
    }

    private function connect(): void
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                $this->config['host'],
                $this->config['database']
            );
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            self::$pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $options);
        } catch (\PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public static function getInstance(?array $config = null): self
    {
        if (self::$instance === null) {
            if ($config === null) {
                throw new Exception('Database configuration required for first initialization');
            }
            self::$instance = new self($config);
        }
        try {
            self::$pdo->query('SELECT 1');
        } catch (\PDOException $e) {
            self::$instance->connect();
        }
        return self::$instance;
    }

    public static function getPDO(): PDO
    {
        if (self::$pdo === null) {
            throw new Exception('Database not initialized. Call getInstance() first.');
        }
        return self::$pdo;
    }

    public static function checkConnection(): bool
    {
        if (self::$pdo === null) {
            throw new Exception('No database connection');
        }
        try {
            self::$pdo->query('SELECT 1');
        } catch (\PDOException $e) {
            throw new Exception('Database connection lost: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
        return true;
    }

    public function __destruct()
    {
        self::$pdo = null;
    }
}