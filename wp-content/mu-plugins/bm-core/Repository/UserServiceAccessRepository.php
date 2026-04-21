<?php
namespace BM\Core\Repository;

use BM\Core\Database\TableMapper;

class UserServiceAccessRepository extends AbstractRepository
{
    private const TABLE = 'user_service_access';

    protected function getTableName(): string
    {
        return TableMapper::getInstance()->get(self::TABLE);
    }

    /**
     * Проверить наличие активного доступа
     */
    public function hasAccess(int $userId, int $serviceId): bool
    {
        $sql = "SELECT 1 FROM {$this->getTableName()} 
                WHERE user_id = ? 
                AND service_id = ? 
                AND status = 'active'
                AND (end_date IS NULL OR end_date > NOW())";
        
        return !empty($this->connection->fetchOne($sql, [$userId, $serviceId]));
    }

    /**
     * Получить все активные доступы пользователя
     */
    public function getUserAccesses(int $userId): array
    {
        $sql = "SELECT a.*, s.name, s.slug, s.description 
                FROM {$this->getTableName()} a
                JOIN bm_ctbl000_service s ON a.service_id = s.id
                WHERE a.user_id = ? 
                AND a.status = 'active'
                AND (a.end_date IS NULL OR a.end_date > NOW())
                ORDER BY a.end_date ASC";
        
        return $this->connection->fetchAll($sql, [$userId]);
    }

    /**
     * Создать доступ к услуге
     */
    public function createAccess(int $userId, int $serviceId, string $accessType = 'full', ?int $durationDays = null): int
    {
        $data = [
            'user_id' => $userId,
            'service_id' => $serviceId,
            'access_type' => $accessType,
            'status' => 'active',
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => $durationDays ? date('Y-m-d H:i:s', strtotime("+$durationDays days")) : null
        ];
        
        // Проверяем, есть ли уже запись
        $existing = $this->connection->fetchOne(
            "SELECT id FROM {$this->getTableName()} WHERE user_id = ? AND service_id = ?",
            [$userId, $serviceId]
        );
        
        if ($existing) {
            // Обновляем существующую
            $this->connection->update(
                $this->getTableName(),
                ['status' => 'active', 'end_date' => $data['end_date']],
                "id = {$existing['id']}"
            );
            return $existing['id'];
        }
        
        return $this->connection->insert($this->getTableName(), $data);
    }

    /**
     * Отозвать доступ
     */
    public function revokeAccess(int $userId, int $serviceId): int
    {
        return $this->connection->update(
            $this->getTableName(),
            ['status' => 'expired'],
            "user_id = $userId AND service_id = $serviceId"
        );
    }

    /**
     * Очистить просроченные доступы
     */
    public function cleanExpired(): int
    {
        $sql = "UPDATE {$this->getTableName()} 
                SET status = 'expired' 
                WHERE status = 'active' 
                AND end_date IS NOT NULL 
                AND end_date < NOW()";
        
        $stmt = $this->connection->getPdo()->prepare($sql);
        $stmt->execute();
        return $stmt->rowCount();
    }
}