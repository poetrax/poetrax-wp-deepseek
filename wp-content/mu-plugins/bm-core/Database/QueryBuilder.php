<?php
namespace BM\Core\Database;

use PDO;
use Exception;

class QueryBuilder
{
    private PDO $pdo;
    private string $table = '';
    private array $select = ['*'];
    private array $where = [];
    private array $params = [];
    private ?int $limit = null;
    private int $offset = 0;

    public function __construct(Connection $connection)
    {
        $this->pdo = $connection->getPDO();
    }

    public function table(string $tableName): self
    {
        $this->table = $tableName;
        return $this;
    }

    public function select(array $fields): self
    {
        $this->select = $fields;
        return $this;
    }

    public function where(string $field, string $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        $this->where[] = [$field, $operator, '?'];
        $this->params[] = $value;
        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->buildSelect();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);
        return $stmt->fetchAll();
    }

    public function first(): ?array
    {
        $this->limit(1);
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->table, $columns, $placeholders);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(array $data, array $where): int
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = sprintf('%s = :%s', $key, $key);
        }
        $setStr = implode(', ', $set);
        $whereClause = $this->buildWhere($where);
        $sql = sprintf('UPDATE %s SET %s WHERE %s', $this->table, $setStr, $whereClause);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($data, $where));
        return $stmt->rowCount();
    }

    public function delete(array $where): int
    {
        $whereClause = $this->buildWhere($where);
        $sql = sprintf('DELETE FROM %s WHERE %s', $this->table, $whereClause);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($where);
        return $stmt->rowCount();
    }

    private function buildSelect(): string
    {
        $select = implode(', ', $this->select);
        $sql = sprintf('SELECT %s FROM %s', $select, $this->table);
        if (!empty($this->where)) {
            $conditions = array_map(fn($w) => sprintf('%s %s %s', $w[0], $w[1], $w[2]), $this->where);
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        if ($this->limit !== null) {
            $sql .= sprintf(' LIMIT %d OFFSET %d', $this->limit, $this->offset);
        }
        return $sql;
    }

    private function buildWhere(array $conditions): string
    {
        $parts = [];
        foreach ($conditions as $field => $value) {
            $parts[] = sprintf('%s = :%s', $field, $field);
            $this->params["where_{$field}"] = $value;
        }
        return implode(' AND ', $parts);
    }
}