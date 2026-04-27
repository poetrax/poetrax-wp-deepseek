<?php
namespace BM\Core\Repository;

use BM\Core\Repository\AbstractRepository;
use BM\Core\Exceptions\DatabaseException;

class InteractionRepository extends AbstractRepository
{
    protected function getTableName(): string
    {
        return 'interaction';
    }

    /**
     * ===========================================
     * БАЗОВЫЕ МЕТОДЫ ДЛЯ ВСЕХ ТИПОВ ВЗАИМОДЕЙСТВИЙ
     * ===========================================
     */

    /**
     * Добавить взаимодействие (общий метод)
     */
    public function addInteraction(int $trackId, int $userId, string $type, ?string $ip = null, ?array $metadata = null): bool
    {
        // Проверяем уникальность для like, bookmark, recommend
        if (in_array($type, ['like', 'bookmark', 'recommend']) && $this->hasInteraction($trackId, $userId, $type)) {
            return false; // Уже есть
        }

        $data = [
            'track_id' => $trackId,
            'user_id' => $userId,
            'type' => $type,
            'ip' => $ip ?: $_SERVER['REMOTE_ADDR'] ?? null,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return (bool) $this->connection->insert($this->getTableName(), $data);
    }

    /**
     * Удалить взаимодействие (для like, bookmark, recommend)
     */
    public function removeInteraction(int $trackId, int $userId, string $type): bool
    {
        if (!in_array($type, ['like', 'bookmark', 'recommend'])) {
            return false; // play и share не удаляем
        }

        $sql = "
            DELETE FROM {$this->getTableName()}
            WHERE track_id = :track_id AND user_id = :user_id AND type = :type
        ";

        return (bool) $this->connection->query($sql, [
            'track_id' => $trackId,
            'user_id' => $userId,
            'type' => $type
        ])->rowCount();
    }

    /**
     * Проверить наличие взаимодействия
     */
    public function hasInteraction(int $trackId, int $userId, string $type): bool
    {
        $sql = "
            SELECT COUNT(*) as count FROM {$this->getTableName()}
            WHERE track_id = :track_id AND user_id = :user_id AND type = :type
        ";

        $result = $this->connection->fetchOne($sql, [
            'track_id' => $trackId,
            'user_id' => $userId,
            'type' => $type
        ]);

        return ($result->count ?? 0) > 0;
    }

    /**
     * ===========================================
     * ЛАЙКИ
     * ===========================================
     */

    public function like(int $trackId, int $userId, ?string $ip = null): bool
    {
        return $this->addInteraction($trackId, $userId, 'like', $ip);
    }

    public function unlike(int $trackId, int $userId): bool
    {
        return $this->removeInteraction($trackId, $userId, 'like');
    }

    public function hasLiked(int $trackId, int $userId): bool
    {
        return $this->hasInteraction($trackId, $userId, 'like');
    }

    /**
     * ===========================================
     * ЗАКЛАДКИ
     * ===========================================
     */

    public function bookmark(int $trackId, int $userId, ?string $ip = null): bool
    {
        return $this->addInteraction($trackId, $userId, 'bookmark', $ip);
    }

    public function unbookmark(int $trackId, int $userId): bool
    {
        return $this->removeInteraction($trackId, $userId, 'bookmark');
    }

    public function hasBookmarked(int $trackId, int $userId): bool
    {
        return $this->hasInteraction($trackId, $userId, 'bookmark');
    }

    /**
     * ===========================================
     * ПРОСЛУШИВАНИЯ
     * ===========================================
     */

    public function play(int $trackId, int $userId = 0, ?string $ip = null, ?int $duration = null): bool
    {
        $data = [
            'track_id' => $trackId,
            'user_id' => $userId ?: null,
            'type' => 'play',
            'ip' => $ip ?: $_SERVER['REMOTE_ADDR'] ?? null,
            'metadata' => $duration ? json_encode(['duration' => $duration]) : null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return (bool) $this->connection->insert($this->getTableName(), $data);
    }

    /**
     * ===========================================
     * ПОДЕЛИТЬСЯ
     * ===========================================
     */

    public function share(int $trackId, int $userId, ?string $ip = null, ?string $platform = null): bool
    {
        $metadata = $platform ? ['platform' => $platform] : null;
        return $this->addInteraction($trackId, $userId, 'share', $ip, $metadata);
    }

    /**
     * Получить статистику по шарам для трека
     */
    public function getShareStats(int $trackId): object
    {
        $sql = "
            SELECT 
                COUNT(*) as total_shares,
                COUNT(DISTINCT user_id) as unique_sharers,
                metadata
            FROM {$this->getTableName()}
            WHERE track_id = :track_id AND type = 'share'
        ";

        $rows = $this->connection->fetchAll($sql, ['track_id' => $trackId]);
        
        $stats = [
            'total' => 0,
            'unique' => 0,
            'by_platform' => []
        ];

        foreach ($rows as $row) {
            $stats['total'] += $row->total_shares;
            $stats['unique'] = max($stats['unique'], $row->unique_sharers);
            
            if ($row->metadata) {
                $metadata = json_decode($row->metadata);
                if (isset($metadata->platform)) {
                    $platform = $metadata->platform;
                    $stats['by_platform'][$platform] = ($stats['by_platform'][$platform] ?? 0) + 1;
                }
            }
        }

        return (object) $stats;
    }

    /**
     * Топ популярных по шарам
     */
    public function getTopShared(int $limit = 10): array
    {
        $sql = "
            SELECT track_id, COUNT(*) as share_count
            FROM {$this->getTableName()}
            WHERE type = 'share'
            GROUP BY track_id
            ORDER BY share_count DESC
            LIMIT :limit
        ";

        return $this->connection->fetchAll($sql, ['limit' => $limit]);
    }

    /**
     * ===========================================
     * РЕКОМЕНДАЦИИ (если решим добавить)
     * ===========================================
     */

    public function recommend(int $trackId, int $userId, ?string $ip = null, ?string $reason = null): bool
    {
        $metadata = $reason ? ['reason' => $reason] : null;
        return $this->addInteraction($trackId, $userId, 'recommend', $ip, $metadata);
    }

    public function unrecommend(int $trackId, int $userId): bool
    {
        return $this->removeInteraction($trackId, $userId, 'recommend');
    }

    public function hasRecommended(int $trackId, int $userId): bool
    {
        return $this->hasInteraction($trackId, $userId, 'recommend');
    }

    /**
     * ===========================================
     * СТАТИСТИКА
     * ===========================================
     */

    /**
     * Получить полную статистику трека
     */
    public function getTrackStats(int $trackId): object
    {
        $sql = "
            SELECT 
                COUNT(CASE WHEN type = 'like' THEN 1 END) as likes,
                COUNT(CASE WHEN type = 'bookmark' THEN 1 END) as bookmarks,
                COUNT(CASE WHEN type = 'play' THEN 1 END) as plays,
                COUNT(CASE WHEN type = 'share' THEN 1 END) as shares,
                COUNT(CASE WHEN type = 'recommend' THEN 1 END) as recommends,
                COALESCE(SUM(CASE WHEN type = 'play' THEN JSON_EXTRACT(metadata, '$.duration') ELSE 0 END), 0) as total_duration
            FROM {$this->getTableName()}
            WHERE track_id = :track_id
        ";

        $result = $this->connection->fetchOne($sql, ['track_id' => $trackId]);

        return (object) [
            'likes' => (int) ($result->likes ?? 0),
            'bookmarks' => (int) ($result->bookmarks ?? 0),
            'plays' => (int) ($result->plays ?? 0),
            'shares' => (int) ($result->shares ?? 0),
            'recommends' => (int) ($result->recommends ?? 0),
            'total_duration' => (int) ($result->total_duration ?? 0)
        ];
    }

    /**
     * Получить ID треков, с которыми взаимодействовал пользователь
     */
    public function getUserTrackIds(int $userId, ?string $type = null, int $limit = 50): array
    {
        $sql = "SELECT track_id FROM {$this->getTableName()} WHERE user_id = :user_id";
        $params = ['user_id' => $userId];

        if ($type) {
            $sql .= " AND type = :type";
            $params['type'] = $type;
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit";
        $params['limit'] = $limit;

        $rows = $this->connection->fetchAll($sql, $params);
        return array_column($rows, 'track_id');
    }

    /**
     * Получить популярные треки за период
     */
    public function getPopularTracks(string $period = 'week', string $type = 'play', int $limit = 10): array
    {
        $interval = match($period) {
            'day' => '1 DAY',
            'week' => '7 DAY',
            'month' => '30 DAY',
            default => '7 DAY'
        };

        $sql = "
            SELECT track_id, COUNT(*) as interaction_count
            FROM {$this->getTableName()}
            WHERE type = :type 
                AND created_at > NOW() - INTERVAL {$interval}
            GROUP BY track_id
            ORDER BY interaction_count DESC
            LIMIT :limit
        ";

        return $this->connection->fetchAll($sql, [
            'type' => $type,
            'limit' => $limit
        ]);
    }
}
