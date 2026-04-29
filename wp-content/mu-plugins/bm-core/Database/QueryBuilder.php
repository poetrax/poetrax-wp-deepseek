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
    private array $orderBy = [];
    private array $groupBy = [];
    private array $joins = [];

    public function __construct(Connection $connection)
    {
        $this->pdo = $connection->getPDO();
    }

    /**
     * Сбросить состояние билдера для переиспользования
     */
    public function reset(): self
    {
        $this->table = '';
        $this->select = ['*'];
        $this->where = [];
        $this->params = [];
        $this->limit = null;
        $this->offset = 0;
        $this->orderBy = [];
        $this->groupBy = [];
        $this->joins = [];
        return $this;
    }

    /**
     * Указать таблицу для запроса
     */
    public function table(string $tableName): self
    {
        $this->table = $tableName;
        return $this;
    }

    public static function tableStatic(string $tableName)
    {
        $table = $tableName;
        return $table;
    }

    /**
     * Указать поля для выборки
     */
    public function select(array $fields): self
    {
        $this->select = $fields;
        return $this;
    }

    /**
     * Добавить условие WHERE
     */
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

    /**
     * Добавить условие WHERE IN
     */
    public function whereIn(string $field, array $values): self
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->where[] = [$field, 'IN', "($placeholders)"];
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    /**
     * Добавить условие WHERE NULL
     */
    public function whereNull(string $field): self
    {
        $this->where[] = [$field, 'IS', 'NULL'];
        return $this;
    }

    /**
     * Добавить условие WHERE NOT NULL
     */
    public function whereNotNull(string $field): self
    {
        $this->where[] = [$field, 'IS NOT', 'NULL'];
        return $this;
    }

    /**
     * Добавить JOIN
     */
    public function join(string $table, string $condition, string $type = 'INNER'): self
    {
        $this->joins[] = "$type JOIN $table ON $condition";
        return $this;
    }

    /**
     * Добавить LEFT JOIN
     */
    public function leftJoin(string $table, string $condition): self
    {
        return $this->join($table, $condition, 'LEFT');
    }

    /**
     * Добавить RIGHT JOIN
     */
    public function rightJoin(string $table, string $condition): self
    {
        return $this->join($table, $condition, 'RIGHT');
    }

    /**
     * Добавить GROUP BY
     */
    public function groupBy(string $field): self
    {
        $this->groupBy[] = $field;
        return $this;
    }

    /**
     * Добавить ORDER BY
     */
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy[] = "$field $direction";
        return $this;
    }

    /**
     * Установить LIMIT и OFFSET
     */
    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * Выполнить SELECT и получить все строки
     */
    public function get(): array
    {
        $sql = $this->buildSelect();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Выполнить SELECT и получить первую строку
     */
    public function first(): ?array
    {
        $this->limit(1);
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    /**
     * Получить количество строк (без учёта LIMIT)
     */
    public function count(): int
    {
        $originalSelect = $this->select;
        $originalOrderBy = $this->orderBy;
        $originalLimit = $this->limit;
        $originalOffset = $this->offset;

        $this->select = ['COUNT(*) as total'];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = 0;

        $sql = $this->buildSelect();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Восстанавливаем исходное состояние
        $this->select = $originalSelect;
        $this->orderBy = $originalOrderBy;
        $this->limit = $originalLimit;
        $this->offset = $originalOffset;

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Пагинация (возвращает данные + мета-информацию)
     */
    public function paginate(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $this->limit($perPage, $offset);
        $data = $this->get();
        $total = $this->count();

        return [
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ];
    }

    /**
     * Выполнить INSERT
     */
    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->table, $columns, $placeholders);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Выполнить UPDATE
     */
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

    /**
     * Выполнить DELETE
     */
    public function delete(array $where): int
    {
        $whereClause = $this->buildWhere($where);
        $sql = sprintf('DELETE FROM %s WHERE %s', $this->table, $whereClause);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($where);
        return $stmt->rowCount();
    }

    /**
     * Построить SELECT-запрос
     */
    private function buildSelect(): string
    {
        $select = implode(', ', $this->select);
        $sql = sprintf('SELECT %s FROM %s', $select, $this->table);

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->where)) {
            $conditions = array_map(fn($w) => sprintf('%s %s %s', $w[0], $w[1], $w[2]), $this->where);
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= sprintf(' LIMIT %d OFFSET %d', $this->limit, $this->offset);
        }

        return $sql;
    }

    /**
     * Построить WHERE-условие для UPDATE/DELETE
     */
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

/*
// Пример с пагинацией
$result = $this->querybuilder($this->connection)-
    ->TableMapper::getInstance()->get('track')
    ->where('is_approved', 1)
    ->where('is_active', 1)
    ->orderBy('created_at', 'DESC')
    ->paginate(2, 20);  // страница 2, по 20 записей

print_r($result['data']);        // массив треков
print_r($result['pagination']);  // мета-информация
*/