<?php
namespace BM\Core\Repository;

use BM\Core\Database\QueryBuilder;
use BM\Core\Database\TableMapper;

class TrackRepository extends AbstractRepository
{
    private const TABLE_NAME = 'track';  // ← реальное имя таблицы
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
 * Get filtered tracks with pagination
 * 
 * @param array $filters
 * @param int $page
 * @param int $limit
 * @return array
 */
public function getFiltered(array $filters, int $page = 1, int $limit = 20): array
{
    $qb = $this->queryBuilder
        ->reset()
        ->table($this->getTableName())
        ->where(self::FIELD_IS_APPROVED, 1)
        ->where(self::FIELD_IS_ACTIVE, 1);
    
    // Голос (male/female)
    if (!empty($filters['voice_gender'])) {
        $qb->where('voice_gender', $filters['voice_gender']);
    }
    
    // Язык (ru/en)
    if (!empty($filters['lang'])) {
        $qb->where('track_lang', $filters['lang']);
    }
    
    // Настроение
    if (!empty($filters['mood_id'])) {
        $qb->where('mood_id', (int)$filters['mood_id']);
    }
    
    // Тема
    if (!empty($filters['theme_id'])) {
        $qb->where('theme_id', (int)$filters['theme_id']);
    }
    
    // Жанр
    if (!empty($filters['genre_id'])) {
        $qb->where('genre_id', (int)$filters['genre_id']);
    }
    
    // Стиль
    if (!empty($filters['style_id'])) {
        $qb->where('style_id', (int)$filters['style_id']);
    }
    
    // Возвращаем результат пагинации
    return $qb->paginate($page, $limit);
}
//ПРИМЕР ИСПОЛЬЗОВАНИЯ cacheManager
    /**
     * Получить трек по ID (с кэшем)
     */
    public function find(int $id): ?array
    {
        $key = "track:{$id}";

        return $this->cacheManager->remember($key, function () use ($id) {
            $sql = "SELECT * FROM {$this->getTableName()} WHERE id = ?";
            return $this->connection->fetchOne($sql, [$id]);
        });
    }

    /**
     * Получить популярные треки (с кэшем)
     */
    public function getPopular(int $limit = 10): array
    {
        $limit = min($limit, self::MAX_LIMIT);
        $key = "tracks:popular:limit:{$limit}";

        return $this->cacheManager->remember($key, function () use ($limit) {
            $interactionTable = TableMapper::getInstance()->get('interaction');
            $table = $this->getTableName();

            $sql = "
            SELECT t.*, COUNT(i.id) as plays_count
            FROM {$table} t
            LEFT JOIN {$interactionTable} i 
                ON t.id = i.track_id AND i.type = 'play'
            WHERE t.is_approved = 1 AND t.is_active = 1
            GROUP BY t.id
            ORDER BY plays_count DESC
            LIMIT :limit
        ";

            return $this->connection->fetchAll($sql, ['limit' => $limit]);
        });
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

    /**
     * Получить треки с пагинацией (с кэшем)
     */
    public function getPaginatedCache(int $page = 1, int $limit = 20): array
    {
        $key = "tracks:paginated";

        return $this->cacheManager->rememberPaginated($key, function () use ($page, $limit) {
            $offset = ($page - 1) * $limit;
            $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE is_approved = 1 AND is_active = 1
                LIMIT ? OFFSET ?";

            $items = $this->connection->fetchAll($sql, [$limit, $offset]);

            $countSql = "SELECT COUNT(*) FROM {$this->getTableName()} 
                     WHERE is_approved = 1 AND is_active = 1";
            $total = (int) $this->connection->fetchOne($countSql)['COUNT(*)'];

            return [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ];
        }, $page, $limit);
    }
}
