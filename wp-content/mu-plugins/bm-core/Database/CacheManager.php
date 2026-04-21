<?php
namespace BM\Core\Cache;

use BM\Core\Database\Connection;

class CacheManager
{
    private Cache $cache;
    private Connection $connection;
    private int $defaultTtl;

    public function __construct(Cache $cache, Connection $connection, int $defaultTtl = 3600)
    {
        $this->cache = $cache;
        $this->connection = $connection;
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * Получить данные из кэша или из БД (с автоматическим сохранением в кэш)
     * 
     * @param string $key Ключ кэша
     * @param callable $callback Функция, возвращающая данные из БД
     * @param int|null $ttl Время жизни кэша (null = defaultTtl)
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        // Проверяем кэш
        $cached = $this->cache->get($key);
        if ($cached !== null) {
            return $cached;
        }

        // Получаем данные из БД
        $data = $callback();

        // Сохраняем в кэш
        if ($data !== null) {
            $this->cache->set($key, $data, $ttl ?? $this->defaultTtl);
        }

        return $data;
    }

    /**
     * Получить данные из кэша или из БД (для списков с пагинацией)
     */
    public function rememberPaginated(string $key, callable $callback, int $page, int $limit, ?int $ttl = null): array
    {
        $cacheKey = $key . ":page:{$page}:limit:{$limit}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $data = $callback();

        if (!empty($data)) {
            $this->cache->set($cacheKey, $data, $ttl ?? $this->defaultTtl);
        }

        return $data;
    }

    /**
     * Очистить кэш по ключу или группе
     */
    public function forget(string $key): void
    {
        $this->cache->delete($key);
    }

    /**
     * Очистить кэш по префиксу
     */
    public function forgetByPrefix(string $prefix): void
    {
        $this->cache->flushByPrefix($prefix);
    }

    /**
     * Очистить весь кэш (для админки)
     */
    public function flush(): void
    {
        $this->cache->flushAll();
    }

    //ПРИМЕР ИСПОЛЬЗОВАНИЯ  в скрипте
    /**
     * Обновить трек (очищаем кэш)
     */
    /*
    public function update(int $id, array $data): int
    {
        $result = $this->connection->update($this->getTableName(), $data, "id = $id");

        if ($result) {
            // Очищаем кэш этого трека
            $this->cacheManager->forget("track:{$id}");
            // Очищаем кэш списков (можно по префиксу)
            $this->cacheManager->forgetByPrefix("tracks:");
        }

        return $result;
    }
    */
}