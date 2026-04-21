<?php
namespace BM\Core\Repository;

use BM\Core\Repository\AbstractRepository;
use BM\Core\Config\TableMapper;

class UserRepository extends AbstractRepository
{
    protected function getTableName(): string
    {
        return 'user';  // ключ из tables.php
    }

    /**
     * Найти пользователя по email
     */
    public function findByEmail(string $email)
    {
        return $this->findOneBy(['user_email' => $email]);
    }

    /**
     * Найти пользователя по логину
     */
    public function findByLogin(string $login)
    {
        return $this->findOneBy(['user_login' => $login]);
    }

    /**
     * Найти пользователя по email ИЛИ логину (для авторизации)
     */
    public function findByLoginOrEmail(string $username)
    {
        $sql = "
            SELECT * FROM {$this->getTableName()}
            WHERE user_login = :login OR user_email = :email
            LIMIT 1
        ";
        
        return $this->connection->fetchOne($sql, [
            'login' => $username,
            'email' => $username
        ]);
    }

    /**
     * Проверить существование пользователя
     */
    public function exists(string $login, string $email): bool
    {
        $sql = "
            SELECT COUNT(*) as total FROM {$this->getTableName()}
            WHERE user_login = :login OR user_email = :email
        ";
        
        $result = $this->connection->fetchOne($sql, [
            'login' => $login,
            'email' => $email
        ]);
        
        return $result->total > 0;
    }

    /**
     * Получить пользователей с активностью за последние N дней
     */
    public function getActive(int $days = 30, int $limit = 50)
    {
        $interactionTable = TableMapper::getInstance()->get('interaction');
       
        $sql = "
            SELECT DISTINCT u.*
            FROM {$this->getTableName()} u
            JOIN {$interactionTable} i ON u.id = i.user_id
            WHERE i.created_at > NOW() - INTERVAL :days DAY
            ORDER BY u.user_login
            LIMIT :limit
        ";
        
        return $this->connection->fetchAll($sql, [
            'days' => $days,
            'limit' => $limit
        ]);
    }

    /**
     * Обновить время последнего визита
     */
    public function updateLastVisit(int $userId): bool
    {
        return $this->update($userId, [
            'last_visit' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Получить полное имя пользователя
     */
    public function getFullName($user): string
    {
        if (!is_object($user)) {
            $user = $this->find($user);
        }
        
        return trim($user->user_first_name . ' ' . $user->user_last_name);
    }
}
