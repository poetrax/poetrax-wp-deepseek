<?php
namespace BM\Core\Repository;

use BM\Core\Database\TableMapper;

class ProductRepository extends AbstractRepository
{
    private const TABLE = 'product';

    protected function getTableName(): string
    {
        return TableMapper::getInstance()->get(self::TABLE);
    }

    /**
     * Получить все товары (с пагинацией и фильтрацией)
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $limit = min($limit, 100);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->getTableName()} p
                LEFT JOIN bm_ctbl000_product_category c ON p.category_id = c.id
                WHERE p.is_active = 1";
        $params = [];
        
        // Фильтр по категории
        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = (int) $filters['category_id'];
        }
        
        // Фильтр по категории slug
        if (!empty($filters['category_slug'])) {
            $sql .= " AND c.slug = ?";
            $params[] = $filters['category_slug'];
        }
        
        // Фильтр по цене
        if (isset($filters['min_price'])) {
            $sql .= " AND p.price >= ?";
            $params[] = (float) $filters['min_price'];
        }
        if (isset($filters['max_price'])) {
            $sql .= " AND p.price <= ?";
            $params[] = (float) $filters['max_price'];
        }
        
        // Поиск по названию
        if (!empty($filters['search'])) {
            $sql .= " AND p.name LIKE ?";
            $params[] = "%{$filters['search']}%";
        }
        
        // Сортировка
        $sortField = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'DESC';
        $sql .= " ORDER BY p.{$sortField} {$sortOrder}";
        
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $items = $this->connection->fetchAll($sql, $params);
        
        // Общее количество (для пагинации)
        $countSql = "SELECT COUNT(*) FROM {$this->getTableName()} p
                     LEFT JOIN bm_ctbl000_product_category c ON p.category_id = c.id
                     WHERE p.is_active = 1";
        $countParams = [];
        
        if (!empty($filters['category_id'])) {
            $countSql .= " AND p.category_id = ?";
            $countParams[] = (int) $filters['category_id'];
        }
        if (!empty($filters['search'])) {
            $countSql .= " AND p.name LIKE ?";
            $countParams[] = "%{$filters['search']}%";
        }
        
        $total = (int) $this->connection->fetchOne($countSql, $countParams)['COUNT(*)'];
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Получить товар по ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->getTableName()} p
                LEFT JOIN bm_ctbl000_product_category c ON p.category_id = c.id
                WHERE p.id = ?";
        return $this->connection->fetchOne($sql, [$id]);
    }

    /**
     * Получить товар по slug
     */
    public function getBySlug(string $slug): ?array
    {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->getTableName()} p
                LEFT JOIN bm_ctbl000_product_category c ON p.category_id = c.id
                WHERE p.slug = ? AND p.is_active = 1";
        return $this->connection->fetchOne($sql, [$slug]);
    }

    /**
     * Получить изображения товара
     */
    public function getImages(int $productId): array
    {
        $sql = "SELECT * FROM bm_ctbl000_product_image WHERE product_id = ? ORDER BY sort_order ASC";
        return $this->connection->fetchAll($sql, [$productId]);
    }

    /**
     * Получить варианты товара
     */
    public function getVariants(int $productId): array
    {
        $sql = "SELECT * FROM bm_ctbl000_product_variant WHERE product_id = ?";
        return $this->connection->fetchAll($sql, [$productId]);
    }

    /**
     * Проверить наличие товара
     */
    public function checkStock(int $productId, int $quantity = 1, ?int $variantId = null): bool
    {
        if ($variantId) {
            $sql = "SELECT stock FROM bm_ctbl000_product_variant WHERE id = ?";
            $result = $this->connection->fetchOne($sql, [$variantId]);
            return $result && $result['stock'] >= $quantity;
        }
        
        $sql = "SELECT stock FROM {$this->getTableName()} WHERE id = ?";
        $result = $this->connection->fetchOne($sql, [$productId]);
        return $result && $result['stock'] >= $quantity;
    }

    /**
     * Обновить остаток товара
     */
    public function updateStock(int $productId, int $quantity, ?int $variantId = null): int
    {
        if ($variantId) {
            $sql = "UPDATE bm_ctbl000_product_variant SET stock = stock - ? WHERE id = ?";
            $stmt = $this->connection->getPdo()->prepare($sql);
            $stmt->execute([$quantity, $variantId]);
            return $stmt->rowCount();
        }
        
        $sql = "UPDATE {$this->getTableName()} SET stock = stock - ? WHERE id = ?";
        $stmt = $this->connection->getPdo()->prepare($sql);
        $stmt->execute([$quantity, $productId]);
        return $stmt->rowCount();
    }
}