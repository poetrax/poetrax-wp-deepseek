<?php
namespace BM\Core\Repository;

use BM\Core\Repository\AbstractRepository;
use BM\Core\Database\TableMapper;
class PoetRepository extends AbstractRepository
{
    protected function getTableName(): string
    {
        return 'poet';
    }
 
    /**
     * Получить популярных поэтов (по количеству треков)
     */
    public function getPopular(int $limit = 10): array
    {
        $trackTable = TableMapper::getInstance()->get('track');
        $sql = "
            SELECT p.*, COUNT(t.id) as tracks_count
            FROM {$this->getTableName()} p
            LEFT JOIN {$trackTable} t ON p.id = t.poet_id
            WHERE p.is_active = 1 AND p.is_approved = 1
            GROUP BY p.id
            WHERE t.id IS NOT NULL 
            ORDER BY tracks_count DESC
            LIMIT :limit
        ";
        
        return $this->connection->fetchAll($sql, ['limit' => $limit]);
    }
    

    /**
     * Найти поэта по slug
     */
    public function findBySlug(string $slug)
    {   
        return $this->findOneBy(['poet_slug' => $slug]);
    }

    /**
     * Поиск по имени
     */
    public function searchByName(string $query, int $limit = 20)
    {
        $sql = "
            SELECT * FROM {$this->getTableName()}
            WHERE (first_name LIKE :query 
                OR last_name LIKE :query 
                OR second_name LIKE :query)
                AND is_active = 1
                AND is_approved = 1
            ORDER BY last_name, first_name
            LIMIT :limit
        ";
        
        return $this->connection->fetchAll($sql, [
            'query' => '%' . $query . '%',
            'limit' => $limit
        ]);
    }

    /**
     * Получить случайных поэтов
     */
    public function getRandom(int $limit = 3)
    {
        $sql = "
            SELECT * FROM {$this->getTableName()}
            WHERE is_active = 1 AND is_approved = 1
            ORDER BY RAND()
            LIMIT :limit
        ";
        
        return $this->connection->fetchAll($sql, ['limit' => $limit]);
    }

    /**
     * Получить последних добавленных поэтов
     */
    public function getRecent(int $limit = 10)
    {
        $sql = "
            SELECT * FROM {$this->getTableName()}
            WHERE is_active = 1 AND is_approved = 1
            ORDER BY created_at DESC
            LIMIT :limit
        ";
        
        return $this->connection->fetchAll($sql, ['limit' => $limit]);
    }

    /**
     * Форматировать годы жизни
     */
    public function formatYears($poet): string
    {
        $years = [];
        if (!empty($poet->birth_year)) {
            $years[] = $poet->birth_year;
        }
        if (!empty($poet->death_year)) {
            $years[] = $poet->death_year;
        }
        
        return implode('—', $years);
    }


}
