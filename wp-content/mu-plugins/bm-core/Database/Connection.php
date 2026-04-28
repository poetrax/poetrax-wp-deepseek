<?php
namespace BM\Core\Database;

use PDO;
use PDOException;
use Exception;

class Connection
{
    private static $instance = null;
    private static $pdo = null;
    private $config;

    private function __construct()
    {
        $this->connect();
    }

    private function connect(): void
    {
        try {

            // Получаем параметры из переменных окружения
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
            $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'database';
            $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'user';
            $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            self::$pdo = new PDO($dsn, $user, $password, $options);
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }


    /**
     * Получить скалярное значение (для COUNT, SUM, AVG и т.д.)
     */
    public function var($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }


    public static function getInstance(?array $config = null): self
    {
        if (self::$instance === null) {
            if ($config === null) {
                throw new Exception('Database configuration required for first initialization');
            }
        }
        try {
            self::$pdo->query('SELECT 1');
        } catch (PDOException $e) {
            self::$instance->connect();
        }
        return self::$instance;
    }

 

    // fetchAll()
    public function fetchAllASSOC(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получить все строки
     */
    public function fetchAllOBJ($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
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
        } catch (PDOException $e) {
            throw new Exception('Database connection lost: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
        return true;
    }


    public function queryDynamic($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function queryStatic($sql, $params = [])
    {
        $connection = self::getInstance();
        $pdo = $connection->getPdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
  
    public function insertByQuery(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->queryDynamic($sql, $data);
        return (int) $this->pdo->lastInsertId();
    }

    // Для INSERT с возвратом ID
    public static function insertWithParams($sql, $params = [])
    {
        $connection = self::getInstance();
        $pdo = $connection->getPdo();
        return $pdo->lastInsertId();
    }
    
    public function updateByQuery(string $table, array $data, string $where): int
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }
        $setStr = implode(', ', $set);
        $sql = "UPDATE {$table} SET {$setStr} WHERE {$where}";
        $stmt = $this->queryDynamic($sql, $data);
        return $stmt->rowCount();
    }

    public static function updateByParams($sql, $params = [])
    {
        $connection = self::getInstance();
        $pdo = $connection->getPdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    // Для одного результата
    public static function selectOne($sql, $params = [])
    {
        $connection = self::getInstance();
        $pdo = $connection->getPdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
  
    public function delete(string $table, string $where): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->queryDynamic($sql);
        return $stmt->rowCount();
    }
    
    /**
     * Статические методы для быстрых запросов
     */
    
    //Использование
    //$poems = Connection::select($sql, $params);
   
    /**
     * Быстрый SELECT с параметрами
     */
    public static function select($sql, $params = [])
    {
        $connection = self::getInstance();  
        $pdo = $connection->getPdo();       
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function fetchOneOBJ($sql, $params = [])
    {
        $connection = self::getInstance();
        $pdo = $connection->getPdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }


    public function fetchOneASSOC(string $sql, array $params = []): ?array
    {
        $stmt = $this->queryDynamic($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public static function execute($sql, $params = [])
    {
        $connection = self::getInstance();
        $pdo = $connection->getPdo();
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Получить одно значение (например COUNT)
     */
    public function fetchValue($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Получить одну строку
     */
    public function fetchOne($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_OBJ);
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