<?php
namespace BM\Core\Database;

use PDO;
use PDOException;

class Connection
{
    private static $instance = null;
    private $pdo;
    private $config;

    private function __construct($config)
    {
        $this->config = $config;
        $this->connect();
    }

    public static function getInstance($config = null)
    {
        if (self::$instance === null) {
            $config = $config ?: require __DIR__ . '/../Config/database.php';
            self::$instance = new self($config['connections'][$config['default']]);
        }
        return self::$instance;
    }

    private function connect()
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['database'],
                $this->config['charset']
            );
            
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
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
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    public function insert($table, $data)
    {
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") 
                VALUES ({$placeholders})";
        
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where)
    {
        $set = [];
        foreach (array_keys($data) as $field) {
            $set[] = "{$field} = :{$field}";
        }
        
        $whereClause = [];
        foreach (array_keys($where) as $field) {
            $whereClause[] = "{$field} = :where_{$field}";
            $data["where_{$field}"] = $where[$field];
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $set) . 
               " WHERE " . implode(' AND ', $whereClause);
        
        return $this->query($sql, $data)->rowCount();
    }

    public function delete($table, $where)
    {
        $whereClause = [];
        foreach (array_keys($where) as $field) {
            $whereClause[] = "{$field} = :{$field}";
        }
        
        $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereClause);
        
        return $this->query($sql, $where)->rowCount();
    }
}
