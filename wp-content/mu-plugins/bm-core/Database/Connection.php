<?php
namespace BM\Core\Database;

use PDO;
use PDOException;
use Exception;

class Connection
{
    private static $instance = null;
    private $pdo;
    private $config;

    private function __construct($config = null)
    {
        $this->config = $config ?: [
            'host' => getenv('DB_HOST') ?: 'poetrax_deepseek_mysql',
            'database' => getenv('DB_NAME') ?: 'u3436142_poetrax_deepseek_db',
            'username' => getenv('DB_USER') ?: 'u3436142_poetrax_deepseek_user',
            'password' => getenv('DB_PASSWORD') ?: 'CI57bdR7m6F9Xem7',
        ];
        $this->connect();
    }

    private function connect()
    {
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['database']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance($config = null)
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchOne($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }

    public function insert($table, $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where)
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }
        $setStr = implode(', ', $set);
        $sql = "UPDATE {$table} SET {$setStr} WHERE {$where}";
        return $this->query($sql, $data)->rowCount();
    }

    public function delete($table, $where)
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql)->rowCount();
    }
}
