<?php
namespace BM\Core\Repository;

use BM\Core\Database\TableMapper;

class BlockRepository extends AbstractRepository
{
    private const TABLE = 'block';

    protected function getTableName(): string
    {
        return TableMapper::getInstance()->get(self::TABLE);
    }

    /**
     * Создать блокировку
     */
    public function create(array $data): int
    {
        return $this->connection->insert($this->getTableName(), $data);
    }

    /**
     * Проверить, существует ли блокировка
     */
    public function exists(int $blockerId, int $blockedId, string $type, ?int $targetId = null): bool
    {
        $sql = "SELECT 1 FROM {$this->getTableName()} 
                WHERE blocker_user_id = ? 
                AND blocked_user_id = ? 
                AND block_type = ?";
        $params = [$blockerId, $blockedId, $type];
        
        if ($targetId !== null) {
            $sql .= " AND target_id = ?";
            $params[] = $targetId;
        }
        
        $sql .= " AND (expires_at IS NULL OR expires_at > NOW())";
        
        return !empty($this->connection->fetchOne($sql, $params));
    }

    /**
     * Получить все блокировки, где пользователь является блокирующим
     */
    public function getByBlocker(int $userId, int $page = 1, int $limit = 20): array
    {
        $limit = min($limit, 100);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT b.*, 
                       u.user_login as blocked_login,
                       u.display_name as blocked_name
                FROM {$this->getTableName()} b
                JOIN bm_ctbl000_user u ON b.blocked_user_id = u.id
                WHERE b.blocker_user_id = ?
                ORDER BY b.created_at DESC
                LIMIT ? OFFSET ?";
        
        $items = $this->connection->fetchAll($sql, [$userId, $limit, $offset]);
        
        // Общее количество
        $countSql = "SELECT COUNT(*) FROM {$this->getTableName()} WHERE blocker_user_id = ?";
        $total = (int) $this->connection->fetchOne($countSql, [$userId])['COUNT(*)'];
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Получить все блокировки, где пользователь является заблокированным
     */
    public function getByBlocked(int $userId, int $page = 1, int $limit = 20): array
    {
        $limit = min($limit, 100);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT b.*, 
                       u.user_login as blocker_login,
                       u.display_name as blocker_name
                FROM {$this->getTableName()} b
                JOIN bm_ctbl000_user u ON b.blocker_user_id = u.id
                WHERE b.blocked_user_id = ?
                ORDER BY b.created_at DESC
                LIMIT ? OFFSET ?";
        
        $items = $this->connection->fetchAll($sql, [$userId, $limit, $offset]);
        
        $countSql = "SELECT COUNT(*) FROM {$this->getTableName()} WHERE blocked_user_id = ?";
        $total = (int) $this->connection->fetchOne($countSql, [$userId])['COUNT(*)'];
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Удалить блокировку
     */
    public function delete(int $blockId, int $userId): bool
    {
        $sql = "DELETE FROM {$this->getTableName()} WHERE id = ? AND blocker_user_id = ?";
        $result = $this->connection->delete($this->getTableName(), "id = $blockId AND blocker_user_id = $userId");
        return $result > 0;
    }

    /**
     * Удалить все блокировки между пользователями
     */
    public function deleteAll(int $blockerId, int $blockedId): int
    {
        return $this->connection->delete(
            $this->getTableName(),
            "blocker_user_id = $blockerId AND blocked_user_id = $blockedId"
        );
    }

    /**
     * Очистить просроченные блокировки
     */
    public function cleanExpired(): int
    {
        $sql = "DELETE FROM {$this->getTableName()} WHERE expires_at IS NOT NULL AND expires_at < NOW()";
        return $this->connection->delete($this->getTableName(), "expires_at IS NOT NULL AND expires_at < NOW()");
    }
}