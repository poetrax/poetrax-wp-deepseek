<?php
namespace BM\Core\Repository;

use BM\Core\Database\QueryBuilder;

class TrackRepository extends AbstractRepository
{
    private const TABLE_NAME = 'bm_ctbl000_track';  // ← реальное имя таблицы
 	private const FIELD_IS_APPROVED = 'is_approved';
    private const FIELD_IS_ACTIVE = 'is_active';
    private const FIELD_CREATED_AT = 'created_at';
    private const DEFAULT_LIMIT = 10;
    private const MAX_LIMIT = 100;

    private QueryBuilder $queryBuilder;
	private $config;  

    public function __construct($config = null)
    {
        parent::__construct();
        $this->config = $config;
        $this->queryBuilder = new QueryBuilder($this->connection);
    }

	protected function getTableName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * Найти треки по поэту
     */
    public function findByPoet(int $poetId, int $limit = 20): array
    {
        $limit = min($limit, self::MAX_LIMIT);
        
        return $this->queryBuilder
            ->reset()
            ->table($this->getTableName())
            ->where('poet_id', $poetId)
            ->where(self::FIELD_IS_APPROVED, 1)
            ->where(self::FIELD_IS_ACTIVE, 1)
            ->orderBy(self::FIELD_CREATED_AT, 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Найти треки по стихотворению
     */
    public function findByPoem(int $poemId, int $limit = 10): array
    {
        $limit = min($limit, self::MAX_LIMIT);
        
        return $this->queryBuilder
            ->reset()
            ->table($this->getTableName())
            ->where('poem_id', $poemId)
            ->where(self::FIELD_IS_APPROVED, 1)
            ->where(self::FIELD_IS_ACTIVE, 1)
            ->orderBy(self::FIELD_CREATED_AT, 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Найти треки по пользователю (автору трека)
     */
    public function findByUser(int $userId, int $limit = 20): array
    {
        $limit = min($limit, self::MAX_LIMIT);
        
        return $this->queryBuilder
            ->reset()
            ->table($this->getTableName())
            ->where('user_id', $userId)
            ->where(self::FIELD_IS_APPROVED, 1)
            ->where(self::FIELD_IS_ACTIVE, 1)
            ->orderBy(self::FIELD_CREATED_AT, 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить популярные треки (по количеству прослушиваний)
     */
    public function getPopular(int $limit = 10): array
    {
        $limit = min($limit, self::MAX_LIMIT);
        
        $sql = "
            SELECT t.*, COUNT(i.id) as plays_count
            FROM {$this->getTableName()} t
            LEFT JOIN {$this->connection->table('interaction')} i 
                ON t.id = i.track_id AND i.type = 'play'
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
    public function getRecent(int $limit = self::DEFAULT_LIMIT): array
    {
        $limit = min($limit, self::MAX_LIMIT);
        
        return $this->queryBuilder
            ->reset()
            ->table($this->getTableName())
            ->where(self::FIELD_IS_APPROVED, 1)
            ->where(self::FIELD_IS_ACTIVE, 1)
            ->orderBy(self::FIELD_CREATED_AT, 'DESC')
            ->limit($limit)
            ->get();
    }
	/**
	 * Get total count of tracks (approved and active)
	 * 
	 * @return int
	 */
	public function count(): int
	{
		return $this->queryBuilder
			->reset()
			->table($this->getTableName())
			->where(self::FIELD_IS_APPROVED, 1)
			->where(self::FIELD_IS_ACTIVE, 1)
			->count();
	}
    /**
     * Поиск по названию
     */
    public function searchByName(string $query, int $limit = 20): array
    {
        $limit = min($limit, self::MAX_LIMIT);
        
        if (strlen($query) < 2) {
            return [];
        }
        
        return $this->queryBuilder
            ->reset()
            ->table($this->getTableName())
            ->where('track_name', 'LIKE', "%{$query}%")
            ->where(self::FIELD_IS_APPROVED, 1)
            ->where(self::FIELD_IS_ACTIVE, 1)
            ->orderBy(self::FIELD_CREATED_AT, 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Обновить счетчик прослушиваний
     */
    public function incrementPlays(int $trackId): bool
    {
        // Здесь можно обновлять денормализованное поле count_play в таблице track
        // или просто вернуть true, а статистика будет считаться через interaction
        return true;
    }

    /**
     * Получить пагинированные треки
     */
    public function getPaginated(?int $page = null, ?int $limit = null): array
    {
        $defaultPage = $this->config['pagination']['default_page'] ?? 1;
        $defaultLimit = $this->config['pagination']['default_limit'] ?? 20;
        $maxLimit = $this->config['pagination']['max_limit'] ?? 100;

        $page = $page ?? $defaultPage;
        $limit = $limit ?? $defaultLimit;
        $limit = min($limit, $maxLimit);

        return $this->queryBuilder
            ->reset()
            ->table($this->getTableName())
            ->where(self::FIELD_IS_APPROVED, 1)
            ->where(self::FIELD_IS_ACTIVE, 1)
            ->orderBy(self::FIELD_CREATED_AT, 'DESC')
            ->paginate($page, $limit);
    }
}
