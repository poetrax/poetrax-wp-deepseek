<?php
namespace BM\Core\Database;

use PDO;
use PDOException;
use BM\Core\Config;

class Connection
{
    private static $instance = null;
    private static $pdo = null;
    private $config;

    private function __construct(array $config)
    {
        $config = Config::getInstance();
        $required = ['host', 'database', 'username', 'password'];
        foreach ($required as $key) {
            if (empty($config->get($key))) { 
                throw new \Exception("Database configuration missing: {$key}");
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
            throw new \Exception('Database connection failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public static function getInstance(?array $config = null): self
    {
        if (self::$instance === null) {
            if ($config === null) {
                throw new \Exception('Database configuration required for first initialization');
            }
        }
        try {
            self::$pdo->query('SELECT 1');
        } catch (\PDOException $e) {
            self::$instance->connect();
        }
        return self::$instance;
    }

   

    // fetchAll()
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getPDO(): PDO
    {
        if (self::$pdo === null) {
            throw new \Exception('Database not initialized. Call getInstance() first.');
        }
        return self::$pdo;
    }

    public static function checkConnection(): bool
    {
        if (self::$pdo === null) {
            throw new \Exception('No database connection');
        }
        try {
            self::$pdo->query('SELECT 1');
        } catch (\PDOException $e) {
            throw new \Exception('Database connection lost: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
        return true;
    }


    // 3. query()
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

  
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        //$stmt = $this->query($sql, $data);
        return (int) $this->pdo->lastInsertId();
    }

    
    public function update(string $table, array $data, string $where): int
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }
        $setStr = implode(', ', $set);
        $sql = "UPDATE {$table} SET {$setStr} WHERE {$where}";
        $stmt = $this->query($sql, $data);
        return $stmt->rowCount();
    }

  
    public function delete(string $table, string $where): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql);
        return $stmt->rowCount();
    }


    /**
     * Escape value (PDO uses prepared statements, but this is for compatibility)
     */
    public static function escape($value)
    {
        self::checkConnection();
        return substr(self::$pdo->quote($value), 1, -1);
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction()
    {
        self::checkConnection();
        return self::$pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit()
    {
        self::checkConnection();
        return self::$pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollBack()
    {
        self::checkConnection();
        return self::$pdo->rollBack();
    }



    public function __destruct()
    {
        self::$pdo = null;
    }
}