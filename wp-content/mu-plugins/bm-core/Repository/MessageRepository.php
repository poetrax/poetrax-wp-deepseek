<?php
namespace BM\Core\Repository;

use BM\Core\Database\TableMapper;

class MessageRepository extends AbstractRepository
{
    private const TABLE = 'message';

    protected function getTableName(): string
    {
        return TableMapper::getInstance()->get(self::TABLE);
    }

    /**
     * Отправить сообщение
     */
    public function send(int $fromId, int $toId, string $message, ?string $subject = null, ?int $parentId = null): int
    {
        $data = [
            'from_user_id' => $fromId,
            'to_user_id' => $toId,
            'message' => $message,
            'subject' => $subject,
            'parent_message_id' => $parentId,
            'is_read' => 0
        ];
        
        return $this->connection->insert($this->getTableName(), $data);
    }

    /**
     * Получить входящие сообщения
     */
    public function getInbox(int $userId, int $page = 1, int $limit = 20): array
    {
        $limit = min($limit, 100);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT m.*, 
                       u.user_login as from_login,
                       u.display_name as from_name
                FROM {$this->getTableName()} m
                JOIN bm_ctbl000_user u ON m.from_user_id = u.id
                WHERE m.to_user_id = ? 
                AND m.is_deleted_by_recipient = 0
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?";
        
        $items = $this->connection->fetchAll($sql, [$userId, $limit, $offset]);
        
        $countSql = "SELECT COUNT(*) FROM {$this->getTableName()} 
                     WHERE to_user_id = ? AND is_deleted_by_recipient = 0";
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
     * Получить отправленные сообщения
     */
    public function getSent(int $userId, int $page = 1, int $limit = 20): array
    {
        $limit = min($limit, 100);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT m.*, 
                       u.user_login as to_login,
                       u.display_name as to_name
                FROM {$this->getTableName()} m
                JOIN bm_ctbl000_user u ON m.to_user_id = u.id
                WHERE m.from_user_id = ? 
                AND m.is_deleted_by_sender = 0
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?";
        
        $items = $this->connection->fetchAll($sql, [$userId, $limit, $offset]);
        
        $countSql = "SELECT COUNT(*) FROM {$this->getTableName()} 
                     WHERE from_user_id = ? AND is_deleted_by_sender = 0";
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
     * Получить сообщение по ID
     */
    public function getById(int $messageId, int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE id = ? AND (from_user_id = ? OR to_user_id = ?)";
        return $this->connection->fetchOne($sql, [$messageId, $userId, $userId]);
    }

    /**
     * Отметить как прочитанное
     */
    public function markAsRead(int $messageId, int $userId): int
    {
        return $this->connection->update(
            $this->getTableName(),
            ['is_read' => 1],
            "id = $messageId AND to_user_id = $userId"
        );
    }

    /**
     * Удалить сообщение (мягкое удаление)
     */
    public function delete(int $messageId, int $userId): int
    {
        // Сначала получаем сообщение
        $message = $this->getById($messageId, $userId);
        if (!$message) {
            return 0;
        }
        
        if ($message['from_user_id'] == $userId) {
            // Отправитель удаляет
            return $this->connection->update(
                $this->getTableName(),
                ['is_deleted_by_sender' => 1],
                "id = $messageId"
            );
        } else {
            // Получатель удаляет
            return $this->connection->update(
                $this->getTableName(),
                ['is_deleted_by_recipient' => 1],
                "id = $messageId"
            );
        }
    }

    /**
     * Получить количество непрочитанных сообщений
     */
    public function getUnreadCount(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->getTableName()} 
                WHERE to_user_id = ? AND is_read = 0 AND is_deleted_by_recipient = 0";
        $result = $this->connection->fetchOne($sql, [$userId]);
        return (int) ($result['COUNT(*)'] ?? 0);
    }
}