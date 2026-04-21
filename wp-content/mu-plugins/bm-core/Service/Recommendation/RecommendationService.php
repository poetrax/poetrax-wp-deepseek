<?php
namespace BM\Core\Service\Recommendation;

use BM\Core\Database\Connection;
use BM\Core\Repository\TrackRepository;
use BM\Core\Repository\InteractionRepository;
use BM\Core\Repository\UserRepository;
use BM\Core\Config\TableMapper;

class RecommendationService
{
    private Connection $db;
    private TrackRepository $trackRepo;
    private InteractionRepository $interactionRepo;
    private UserRepository $userRepo;
    
    public function __construct()
    {
        $this->db = Connection::getInstance();
        $this->trackRepo = new TrackRepository();
        $this->interactionRepo = new InteractionRepository();
        $this->userRepo = new UserRepository();
    }
    
    /**
     * Рекомендации для пользователя (персонализированные)
     */
    public function forUser(int $userId, int $limit = 20): array
    {
        // 1. Получаем историю пользователя
        $likedTracks = $this->interactionRepo->getUserTrackIds($userId, 'like', 50);
        $playedTracks = $this->interactionRepo->getUserTrackIds($userId, 'play', 50);
        $bookmarkedTracks = $this->interactionRepo->getUserTrackIds($userId, 'bookmark', 50);
        
        // Объединяем все взаимодействия
        $userTracks = array_unique(array_merge($likedTracks, $playedTracks, $bookmarkedTracks));
        
        if (empty($userTracks)) {
            // Новый пользователь — даём популярное
            return $this->getPopular($limit);
        }
        
        // 2. Находим похожие треки (по жанрам, настроению, поэтам)
        $recommendations = $this->findSimilarTracks($userTracks, $limit);
        
        // 3. Убираем уже прослушанные
        $recommendations = array_filter($recommendations, function($track) use ($userTracks) {
            return !in_array($track->id, $userTracks);
        });
        
        return array_slice($recommendations, 0, $limit);
    }
    
    /**
     * Похожие треки (на основе одного трека)
     */
    public function similarToTrack(int $trackId, int $limit = 10): array
    {
        $track = $this->trackRepo->find($trackId);
        
        if (!$track) {
            return [];
        }
        
        $sql = "SELECT t.* FROM " . TableMapper::getInstance()->get('track') . " t";
        $sql .= " LEFT JOIN " . TableMapper::getInstance()->get('track_music_detail') . " md ON t.id = md.track_id";
        $sql .= " WHERE t.is_approved = 1 AND t.is_active = 1 AND t.status = 'completed' AND t.id != :track_id";
        
        $params = ['track_id' => $trackId];
        $score = [];
        
        // Поиск по разным критериям с весами
        
        // Тот же поэт (+3)
        if ($track->poet_id) {
            $sql .= " OR (t.poet_id = :poet_id)";
            $params['poet_id'] = $track->poet_id;
            $score['poet'] = 3;
        }
        
        // Тот же жанр (+2)
        // TODO: через подзапрос для получения жанра текущего трека
        
        // То же настроение (+2)
        if ($track->mood_id) {
            $sql .= " OR (t.mood_id = :mood_id)";
            $params['mood_id'] = $track->mood_id;
            $score['mood'] = 2;
        }
        
        // Похожая длительность (±30 сек) (+1)
        $sql .= " OR (t.track_duration BETWEEN :duration_min AND :duration_max)";
        $params['duration_min'] = max(0, $track->track_duration - 30);
        $params['duration_max'] = $track->track_duration + 30;
        $score['duration'] = 1;
        
        // TODO: полноценный scoring в отдельном запросе
        
        $sql .= " ORDER BY t.created_at DESC LIMIT :limit";
        $params['limit'] = $limit * 2; // побольше для последующей фильтрации
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Популярные треки (для всех)
     */
    public function getPopular(int $limit = 20): array
    {
        return $this->trackRepo->getPopular($limit);
    }
    
    /**
     * Новинки (недавно добавленные)
     */
    public function getNewReleases(int $limit = 20): array
    {
        return $this->trackRepo->getRecent($limit);
    }
    
    /**
     * Для поэта (на основе его треков)
     */
    public function forPoet(int $poetId, int $limit = 10): array
    {
        $sql = "SELECT t.* FROM " . TableMapper::getInstance()->get('track') . " t";
        $sql .= " WHERE t.poet_id = :poet_id";
        $sql .= " AND t.is_approved = 1 AND t.is_active = 1";
        $sql .= " ORDER BY t.created_at DESC LIMIT :limit";
        
        return $this->db->fetchAll($sql, [
            'poet_id' => $poetId,
            'limit' => $limit
        ]);
    }
    
    /**
     * Для стихотворения (разные версии одного стиха)
     */
    public function forPoem(int $poemId, int $limit = 10): array
    {
        $sql = "SELECT t.* FROM " . TableMapper::getInstance()->get('track') . " t";
        $sql .= " WHERE t.poem_id = :poem_id";
        $sql .= " AND t.is_approved = 1 AND t.is_active = 1";
        $sql .= " ORDER BY t.created_at DESC LIMIT :limit";
        
        return $this->db->fetchAll($sql, [
            'poem_id' => $poemId,
            'limit' => $limit
        ]);
    }
    
    /**
     * Тренды (что слушают сейчас)
     */
    public function getTrending(int $limit = 20): array
    {
        $sql = "SELECT t.*, COUNT(i.id) as plays_last_week";
        $sql .= " FROM " . TableMapper::getInstance()->get('track') . " t";
        $sql .= " JOIN " . TableMapper::getInstance()->get('interaction') . " i ON t.id = i.track_id AND i.type = 'play'";
        $sql .= " WHERE i.created_at > NOW() - INTERVAL 7 DAY";
        $sql .= " AND t.is_approved = 1 AND t.is_active = 1";
        $sql .= " GROUP BY t.id ORDER BY plays_last_week DESC LIMIT :limit";
        
        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }
    
    /**
     * Персонализированные тренды (с учётом предпочтений)
     */
    public function personalizedTrends(int $userId, int $limit = 20): array
    {
        // Получаем предпочтения пользователя
        $likedGenres = $this->getUserPreferredGenres($userId);
        
        if (empty($likedGenres)) {
            return $this->getTrending($limit);
        }
        
        $sql = "SELECT t.*, COUNT(i.id) as plays_last_week";
        $sql .= " FROM " . TableMapper::getInstance()->get('track') . " t";
        $sql .= " JOIN " . TableMapper::getInstance()->get('interaction') . " i ON t.id = i.track_id AND i.type = 'play'";
        $sql .= " JOIN " . TableMapper::getInstance()->get('track_music_detail') . " md ON t.id = md.track_id";
        $sql .= " WHERE i.created_at > NOW() - INTERVAL 7 DAY";
        $sql .= " AND md.genre_id IN (" . implode(',', $likedGenres) . ")";
        $sql .= " AND t.is_approved = 1 AND t.is_active = 1";
        $sql .= " GROUP BY t.id ORDER BY plays_last_week DESC LIMIT :limit";
        
        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }
    
    /**
     * Получить предпочтительные жанры пользователя
     */
    private function getUserPreferredGenres(int $userId): array
    {
        $sql = "SELECT DISTINCT md.genre_id";
        $sql .= " FROM " . TableMapper::getInstance()->get('interaction') . " i";
        $sql .= " JOIN " . TableMapper::getInstance()->get('track') . " t ON i.track_id = t.id";
        $sql .= " JOIN " . TableMapper::getInstance()->get('track_music_detail') . " md ON t.id = md.track_id";
        $sql .= " WHERE i.user_id = :user_id";
        $sql .= " AND i.type IN ('like', 'play')";
        $sql .= " AND md.genre_id IS NOT NULL";
        $sql .= " GROUP BY md.genre_id ORDER BY COUNT(*) DESC LIMIT 10";
        
        $result = $this->db->fetchAll($sql, ['user_id' => $userId]);
        return array_column($result, 'genre_id');
    }
    
    /**
     * Найти похожие треки (коллаборативная фильтрация)
     */
    private function findSimilarTracks(array $userTracks, int $limit): array
    {
        if (empty($userTracks)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($userTracks), '?'));
        
        // Находим пользователей с похожими вкусами
        $sql = "SELECT i2.user_id, COUNT(*) as common_tracks";
        $sql .= " FROM " . TableMapper::getInstance()->get('interaction') . " i1";
        $sql .= " JOIN " . TableMapper::getInstance()->get('interaction') . " i2 ON i1.track_id = i2.track_id";
        $sql .= " WHERE i1.track_id IN ($placeholders)";
        $sql .= " AND i1.user_id != i2.user_id";
        $sql .= " GROUP BY i2.user_id ORDER BY common_tracks DESC LIMIT 50";
        
        $params = array_merge($userTracks, $userTracks);
        $similarUsers = $this->db->fetchAll($sql, $params);
        
        if (empty($similarUsers)) {
            return $this->getPopular($limit);
        }
        
        $userIds = array_column($similarUsers, 'user_id');
        $userPlaceholders = implode(',', array_fill(0, count($userIds), '?'));
        
        // Берём треки, которые нравятся похожим пользователям
        $sql = "SELECT DISTINCT t.*, COUNT(*) as relevance";
        $sql .= " FROM " . TableMapper::getInstance()->get('track') . " t";
        $sql .= " JOIN " . TableMapper::getInstance()->get('interaction') . " i ON t.id = i.track_id";
        $sql .= " WHERE i.user_id IN ($userPlaceholders)";
        $sql .= " AND i.type IN ('like', 'play')";
        $sql .= " AND t.id NOT IN ($placeholders)";
        $sql .= " GROUP BY t.id ORDER BY relevance DESC LIMIT :limit";
        
        $allParams = array_merge($userIds, $userTracks, [$limit]);
        return $this->db->fetchAll($sql, $allParams);
    }
}
