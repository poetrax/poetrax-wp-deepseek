<?php
namespace BM\Core\Repository;

use BM\Core\Database\TableMapper;

class PoemRepository extends AbstractRepository
{
    private const FIELD_IS_ACTIVE = 'is_active';
    private const FIELD_IS_APPROVED = 'is_approved';
    private const FIELD_CREATED_AT = 'created_at';
    private const DEFAULT_LIMIT = 10;
    private const MAX_LIMIT = 100;

    protected function getTableName(): string
    {
        return TableMapper::getInstance()->get('poem');
    }

    /**
     * Найти стихотворение по ID
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE id = ?";
        return $this->connection->fetchOne($sql, [$id]);
    }

    /**
     * Найти стихотворение по slug
     */
    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE poem_slug = ?";
        return $this->connection->fetchOne($sql, [$slug]);
    }

    /**
     * Найти стихотворения по поэту
     */
    public function findByPoet(int $poetId, int $limit = self::DEFAULT_LIMIT): array
    {
        $limit = min($limit, self::MAX_LIMIT);
        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE poet_id = ? 
                AND " . self::FIELD_IS_ACTIVE . " = 1 
                AND " . self::FIELD_IS_APPROVED . " = 1
                ORDER BY name
                LIMIT ?";
        return $this->connection->fetchAll($sql, [$poetId, $limit]);
    }

    /**
     * Получить все стихотворения (с пагинацией)
     */
    public function getAll(int $page = 1, int $limit = self::DEFAULT_LIMIT): array
    {
        $limit = min($limit, self::MAX_LIMIT);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE " . self::FIELD_IS_ACTIVE . " = 1 
                AND " . self::FIELD_IS_APPROVED . " = 1
                ORDER BY name
                LIMIT ? OFFSET ?";
        
        $items = $this->connection->fetchAll($sql, [$limit, $offset]);
        
        // Общее количество
        $countSql = "SELECT COUNT(*) FROM {$this->getTableName()} 
                     WHERE " . self::FIELD_IS_ACTIVE . " = 1 
                     AND " . self::FIELD_IS_APPROVED . " = 1";
        $total = (int) $this->connection->fetchOne($countSql)['COUNT(*)'];
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Поиск стихотворений по названию
     */
    public function searchByName(string $query, int $limit = self::DEFAULT_LIMIT): array
    {
        if (strlen($query) < 2) {
            return [];
        }
        
        $limit = min($limit, self::MAX_LIMIT);
        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE name LIKE ? 
                AND " . self::FIELD_IS_ACTIVE . " = 1 
                AND " . self::FIELD_IS_APPROVED . " = 1
                LIMIT ?";
        
        return $this->connection->fetchAll($sql, ["%$query%", $limit]);
    }

    /**
     * Получить текст стихотворения
     */
    public function getPoemText(int $poemId): ?string
    {
        $sql = "SELECT poem_text FROM {$this->getTableName()} WHERE id = ?";
        $result = $this->connection->fetchOne($sql, [$poemId]);
        return $result['poem_text'] ?? null;
    }

    /**
     * Создать новое стихотворение
     */
    public function create(array $data): int
    {
        return $this->connection->insert($this->getTableName(), $data);
    }

    /**
     * Обновить стихотворение
     */
    public function update(int $id, array $data): int
    {
        return $this->connection->update($this->getTableName(), $data, "id = $id");
    }

    /**
     * Удалить стихотворение (мягкое удаление)
     */
    public function delete(int $id): int
    {
        return $this->connection->update($this->getTableName(), 
            [self::FIELD_IS_ACTIVE => 0], 
            "id = $id"
        );
    }

    /**
     * Получить количество стихотворений у поэта
     */
    public function countByPoet(int $poetId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->getTableName()} 
                WHERE poet_id = ? 
                AND " . self::FIELD_IS_ACTIVE . " = 1";
        $result = $this->connection->fetchOne($sql, [$poetId]);
        return (int) ($result['COUNT(*)'] ?? 0);
    }
}