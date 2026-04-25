<?php
namespace BM\Repositories;

use BM\Core\Database\Connection;  // Исправлен неймспейс
use BM\Core\Database\Cache;
use BM\Database\QueryBuilder;
use BM\Taxonomies\EntityRelations;
use PDO;

class PoemRepository implements RepositoryInterface
{
    private Connection $connection;
    private string $table = 'poems';

    public function __construct(?Connection $connection = null)
    {
        $this->connection = $connection ?? Connection::getInstance();
    }

    /**
     * Найти стих по ID
     */
    public function find($id): ?array
    {
        $sql = "SELECT * FROM {$this->getTable()} WHERE id = :id AND is_active = 1";
        return $this->connection->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Найти стих по slug
     */
    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM {$this->getTable()} WHERE slug = :slug AND is_active = 1";
        return $this->connection->fetchOne($sql, ['slug' => $slug]);
    }

    /**
     * Популярные стихи (по количеству треков)
     */
    public function getPopular(int $limit = 10): array
    {
        $cacheKey = "poems:popular:{$limit}";

        // Пытаемся получить из кэша
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $sql = "
            SELECT p.*, COUNT(t.id) as tracks_count
            FROM {$this->getTable()} p
            LEFT JOIN tracks t 
                ON p.id = t.poem_id 
                AND t.is_approved = 1 
                AND t.is_active = 1
                AND t.status = 'completed'
            WHERE p.is_active = 1 AND p.is_approved = 1
            GROUP BY p.id
            HAVING tracks_count > 0
            ORDER BY tracks_count DESC
            LIMIT :limit
        ";

        $poems = $this->connection->fetchAll($sql, ['limit' => $limit]);

        // Сохраняем в кэш
        Cache::set($cacheKey, $poems, 3600);

        return $poems;
    }

    /**
     * Получить стихи по поэту
     */
    public function getByPoet(int $poetId, int $limit = 20, int $offset = 0): array
    {
        $sql = "
            SELECT p.* 
            FROM {$this->getTable()} p
            WHERE p.poet_id = :poet_id 
                AND p.is_active = 1 
                AND p.is_approved = 1
            ORDER BY p.publish_date DESC
            LIMIT :limit OFFSET :offset
        ";

        return $this->connection->fetchAll($sql, [
            'poet_id' => $poetId,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    /**
     * Поиск стихов
     */
    public function search(string $query, int $limit = 20): array
    {
        $sql = "
            SELECT p.* 
            FROM {$this->getTable()} p
            WHERE (p.title LIKE :query 
                OR p.content LIKE :query 
                OR p.excerpt LIKE :query)
                AND p.is_active = 1 
                AND p.is_approved = 1
            ORDER BY p.publish_date DESC
            LIMIT :limit
        ";

        return $this->connection->fetchAll($sql, [
            'query' => '%' . $query . '%',
            'limit' => $limit
        ]);
    }

    /**
     * Получить количество стихов поэта
     */
    public function countByPoet(int $poetId): int
    {
        $sql = "
            SELECT COUNT(*) as count
            FROM {$this->getTable()}
            WHERE poet_id = :poet_id 
                AND is_active = 1 
                AND is_approved = 1
        ";

        $result = $this->connection->fetchOne($sql, ['poet_id' => $poetId]);
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Получить последние стихи
     */
    public function getLatest(int $limit = 10): array
    {
        $sql = "
            SELECT p.*, poet.name as poet_name
            FROM {$this->getTable()} p
            LEFT JOIN poets poet ON p.poet_id = poet.id
            WHERE p.is_active = 1 AND p.is_approved = 1
            ORDER BY p.created_at DESC
            LIMIT :limit
        ";

        return $this->connection->fetchAll($sql, ['limit' => $limit]);
    }

    /**
     * Получить имя таблицы
     */
    protected function getTable(): string
    {
        // Можно использовать конфиг или префикс
        $prefix = defined('BM_TABLE_PREFIX') ? BM_TABLE_PREFIX : 'bm_';
        return $prefix . 'poems';
    }

    // Реализация методов интерфейса RepositoryInterface
    public function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->connection->insert($this->getTable(), $data);
    }

    public function update(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->connection->update($this->getTable(), $data, "id = :id", ['id' => $id]);
    }

    public function delete(int $id): int
    {
        // Soft delete
        return $this->connection->update(
            $this->getTable(),
            ['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')],
            "id = :id",
            ['id' => $id]
        );
    }

    public function findAll(array $criteria = [], array $orderBy = [], int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->getTable()} WHERE is_active = 1";
        $params = [];

        foreach ($criteria as $field => $value) {
            $sql .= " AND {$field} = :{$field}";
            $params[$field] = $value;
        }

        if (!empty($orderBy)) {
            $order = [];
            foreach ($orderBy as $field => $direction) {
                $order[] = "{$field} {$direction}";
            }
            $sql .= " ORDER BY " . implode(', ', $order);
        }

        $sql .= " LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->connection->fetchAll($sql, $params);
    }
}