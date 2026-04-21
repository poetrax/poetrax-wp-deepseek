<?php
namespace BM\Core\Repository;

use BM\Core\Database\TableMapper;

class AdminRepository extends AbstractRepository
{
    private const TABLE = 'admin';

    protected function getTableName(): string
    {
        return TableMapper::getInstance()->get(self::TABLE);
    }

    /**
     * Проверить, является ли пользователь администратором
     */
    public function isAdmin(int $userId): bool
    {
        $sql = "SELECT 1 FROM {$this->getTableName()} WHERE user_id = ?";
        return !empty($this->connection->fetchOne($sql, [$userId]));
    }

    /**
     * Получить права администратора
     */
    public function getAdminPermissions(int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE user_id = ?";
        $admin = $this->connection->fetchOne($sql, [$userId]);
        
        if (!$admin) {
            return null;
        }
        
        // Если суперадминистратор — все права
        if ($admin['role'] === 'super_admin') {
            return [
                'can_manage_users' => true,
                'can_manage_tracks' => true,
                'can_manage_poets' => true,
                'can_manage_poems' => true,
                'can_manage_payments' => true,
                'can_manage_orders' => true,
                'can_manage_services' => true,
                'can_manage_blocks' => true,
                'can_view_reports' => true,
                'can_manage_settings' => true
            ];
        }
        
        return [
            'can_manage_users' => (bool) $admin['can_manage_users'],
            'can_manage_tracks' => (bool) $admin['can_manage_tracks'],
            'can_manage_poets' => (bool) $admin['can_manage_poets'],
            'can_manage_poems' => (bool) $admin['can_manage_poems'],
            'can_manage_payments' => (bool) $admin['can_manage_payments'],
            'can_manage_orders' => (bool) $admin['can_manage_orders'],
            'can_manage_services' => (bool) $admin['can_manage_services'],
            'can_manage_blocks' => (bool) $admin['can_manage_blocks'],
            'can_view_reports' => (bool) $admin['can_view_reports'],
            'can_manage_settings' => (bool) $admin['can_manage_settings']
        ];
    }

    /**
     * Проверить конкретное право
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        $permissions = $this->getAdminPermissions($userId);
        if (!$permissions) {
            return false;
        }
        
        return $permissions[$permission] ?? false;
    }

    /**
     * Получить всех администраторов
     */
    public function getAll(int $page = 1, int $limit = 20): array
    {
        $limit = min($limit, 100);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT a.*, u.user_login, u.user_email, u.display_name
                FROM {$this->getTableName()} a
                JOIN bm_ctbl000_user u ON a.user_id = u.id
                ORDER BY a.role ASC, a.created_at ASC
                LIMIT ? OFFSET ?";
        
        $items = $this->connection->fetchAll($sql, [$limit, $offset]);
        
        $countSql = "SELECT COUNT(*) FROM {$this->getTableName()}";
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
     * Назначить администратора
     */
    public function makeAdmin(int $userId, string $role = 'admin', int $createdBy, array $permissions = []): int
    {
        $data = [
            'user_id' => $userId,
            'role' => $role,
            'can_manage_users' => $permissions['can_manage_users'] ?? 0,
            'can_manage_tracks' => $permissions['can_manage_tracks'] ?? 0,
            'can_manage_poets' => $permissions['can_manage_poets'] ?? 0,
            'can_manage_poems' => $permissions['can_manage_poems'] ?? 0,
            'can_manage_payments' => $permissions['can_manage_payments'] ?? 0,
            'can_manage_orders' => $permissions['can_manage_orders'] ?? 0,
            'can_manage_services' => $permissions['can_manage_services'] ?? 0,
            'can_manage_blocks' => $permissions['can_manage_blocks'] ?? 0,
            'can_view_reports' => $permissions['can_view_reports'] ?? 0,
            'can_manage_settings' => $permissions['can_manage_settings'] ?? 0,
            'created_by' => $createdBy
        ];
        
        return $this->connection->insert($this->getTableName(), $data);
    }

    /**
     * Удалить администратора
     */
    public function removeAdmin(int $userId): int
    {
        return $this->connection->delete($this->getTableName(), "user_id = $userId");
    }

    /**
     * Обновить права администратора
     */
    public function updatePermissions(int $userId, array $permissions): int
    {
        return $this->connection->update($this->getTableName(), $permissions, "user_id = $userId");
    }
}