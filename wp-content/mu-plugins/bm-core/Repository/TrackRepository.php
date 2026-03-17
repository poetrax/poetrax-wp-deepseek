<?php
namespace BM\Core\Repository;

use BM\Core\Repository\AbstractRepository;

class TrackRepository extends AbstractRepository
{
    protected function getTableName(): string
    {
        return 'track';  // ключ из tables.php
    }

    /**
     * Найти треки по поэту
     */
    public function findByPoet(int $poetId, int $limit = 20)
    {
        return $this->findBy(['poet_id' => $poetId], $limit, 'created_at DESC');
    }

    /**
     * Найти треки по стихотворению
     */
    public function findByPoem(int $poemId, int $limit = 10)
    {
        return $this->findBy(['poem_id' => $poemId], $limit, 'created_at DESC');
    }

    /**
     * Найти треки по пользователю (автору трека)
     */
    public function findByUser(int $userId, int $limit = 20)
    {
        return $this->findBy(['user_id' => $userId], $limit, 'created_at DESC');
    }

    /**
     * Получить популярные треки (по количеству прослушиваний)
     */
    public function getPopular(int $limit = 10)
    {
        $table = $this->connection->table($this->getTableName());
        $interactionTable = $this->connection->table('interaction');
        
        $sql = "
            SELECT t.*, COUNT(i.id) as plays_count
            FROM {$table} t
            LEFT JOIN {$interactionTable} i ON t.id = i.track_id AND i.type = 'play'
            WHERE t.is_approved = 1 AND t.is_active = 1
            GROUP BY t.id
            ORDER BY plays_count DESC
            LIMIT :limit
        ";
        
        return $this->connection->fetchAll($sql, ['limit' => $limit]);
    }

    /**
     * Получить новые треки
     */
    public function getRecent(int $limit = 10)
    {
        $table = $this->connection->table($this->getTableName());
        
        $sql = "
            SELECT * FROM {$table}
            WHERE is_approved = 1 AND is_active = 1
            ORDER BY created_at DESC
            LIMIT :limit
        ";
        
        return $this->connection->fetchAll($sql, ['limit' => $limit]);
    }

    /**
     * Поиск по названию
     */
    public function searchByName(string $query, int $limit = 20)
    {
        $table = $this->connection->table($this->getTableName());
        
        $sql = "
            SELECT * FROM {$table}
            WHERE track_name LIKE :query
            AND is_approved = 1
            AND is_active = 1
            ORDER BY created_at DESC
            LIMIT :limit
        ";
        
        return $this->connection->fetchAll($sql, [
            'query' => '%' . $query . '%',
            'limit' => $limit
        ]);
    }

    /**
     * Обновить счетчик прослушиваний
     */
    public function incrementPlays(int $trackId)
    {
        // Здесь можно обновлять денормализованное поле count_play в таблице track
        // или просто вернуть true, а статистика будет считаться через interaction
        return true;
    }
}
