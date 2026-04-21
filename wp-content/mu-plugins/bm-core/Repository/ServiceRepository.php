<?php
namespace BM\Core\Repository;

use BM\Core\Database\TableMapper;

class ServiceRepository extends AbstractRepository
{
    private const TABLE = 'service';

    protected function getTableName(): string
    {
        return TableMapper::getInstance()->get(self::TABLE);
    }

    /**
     * Получить все активные услуги
     */
    public function getAllActive(int $page = 1, int $limit = 50): array
    {
        $limit = min($limit, 100);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE is_active = 1 
                ORDER BY sort_order ASC, id ASC
                LIMIT ? OFFSET ?";
        
        $items = $this->connection->fetchAll($sql, [$limit, $offset]);
        
        $countSql = "SELECT COUNT(*) FROM {$this->getTableName()} WHERE is_active = 1";
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
     * Получить услугу по slug
     */
    public function getBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE slug = ? AND is_active = 1";
        return $this->connection->fetchOne($sql, [$slug]);
    }

    /**
     * Получить услугу по ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE id = ?";
        return $this->connection->fetchOne($sql, [$id]);
    }
}