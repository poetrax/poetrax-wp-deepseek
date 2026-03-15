<?php
namespace BM\Services;

use BM\Repositories\TrackRepository;
use BM\Repositories\PoetRepository;
use BM\Repositories\PoemRepository;
use BM\Database\Cache;

class TrackService {
    
    public $track_repo;
    public $poet_repo;
    public $poem_repo;
    
    public function __construct() {
        $this->track_repo = new TrackRepository();
        $this->poet_repo = new PoetRepository();
        $this->poem_repo = new PoemRepository();
    }
    
    /**
     * 1. ПОЛУЧЕНИЕ ДАННЫХ С ОБОГАЩЕНИЕМ
     */
    public function getTrackWithDetails($track_id) {
        // Репозиторий даёт базовый объект
        $track = $this->track_repo->find($track_id);
        
        if (!$track) {
            return null;
        }
        
        // Сервис добавляет вычисляемые данные
        $track->recommendations = $this->getRecommendations($track_id, 3);
        $track->stats = $this->getTrackStats($track_id);
        $track->related_poems = $this->getRelatedPoems($track);
        $track->player_html = PlayerService::renderPlayer($track, ['compact' => true]);
        
        return $track;
    }
    
    /**
     * 2. РЕКОМЕНДАЦИИ (сложная логика)
     */
    public function getRecommendations($track_id, $limit = 5) {
        $cache_key = ['track_recommendations', $track_id, $limit];
        $recommendations = Cache::get($cache_key);
        
        if (!$recommendations) {
            $track = $this->track_repo->find($track_id);
            
            if (!$track) {
                return [];
            }
            
            $recommendations = [];
            $exclude_ids = [$track_id];
            
            // Категория 1: Тот же поэт
            if ($track->poet_id) {
                $by_poet = $this->track_repo->getByPoet($track->poet_id, $limit * 2);
                foreach ($by_poet as $t) {
                    if (!in_array($t->id, $exclude_ids)) {
                        $t->recommendation_reason = 'Тот же поэт';
                        $recommendations[] = $t;
                        $exclude_ids[] = $t->id;
                        if (count($recommendations) >= $limit) break;
                    }
                }
            }
            
            // Категория 2: То же настроение
            if (count($recommendations) < $limit && $track->mood_id) {
                $by_mood = $this->track_repo->filterTracks([
                    'mood_id' => $track->mood_id,
                    'exclude_ids' => $exclude_ids
                ], null, $limit - count($recommendations));
                
                foreach ($by_mood as $t) {
                    $t->recommendation_reason = 'Похожее настроение';
                    $recommendations[] = $t;
                    $exclude_ids[] = $t->id;
                }
            }
            
            // Категория 3: Популярное
            if (count($recommendations) < $limit) {
                $popular = $this->track_repo->getPopular($limit * 2);
                foreach ($popular as $t) {
                    if (!in_array($t->id, $exclude_ids)) {
                        $t->recommendation_reason = 'Популярное';
                        $recommendations[] = $t;
                        if (count($recommendations) >= $limit) break;
                    }
                }
            }
            
            Cache::set($cache_key, $recommendations, 1800);
        }
        
        return $recommendations;
    }
    
    /**
     * 3. СОЗДАНИЕ ТРЕКА (интеграция с разными источниками)
     */
    public function createTrack($data) {
        // Валидация
        if (empty($data['track_name'])) {
            throw new \Exception('Название трека обязательно');
        }
        
        // Если указан poem_id, подтягиваем данные автоматически
        if (!empty($data['poem_id'])) {
            $poem = $this->poem_repo->find($data['poem_id']);
            if ($poem) {
                $data['poet_id'] = $poem->poet_id;
                $data['poem_text'] = $poem->poem_text;
                if (empty($data['track_name'])) {
                    $data['track_name'] = $poem->name;
                }
            }
        }
        
        // Генерация slug
        if (empty($data['track_slug'])) {
            $data['track_slug'] = sanitize_title($data['track_name']);
            $data['track_slug'] = $this->makeUniqueSlug($data['track_slug']);
        }
        
        // Создание через репозиторий
        $track_id = $this->track_repo->create($data);
        
        if ($track_id) {
            // Дополнительные действия после создания
            $this->afterTrackCreated($track_id, $data);
        }
        
        return $track_id;
    }
    
    /**
     * 4. РАБОТА СО СТАТИСТИКОЙ
     */
    public function recordPlay($track_id, $user_id = null, $ip = null) {
        global $wpdb;
        
        // Запись в таблицу interaction
        $wpdb->insert(
            \BM\Database\Connection::table('interaction'),
            [
                'track_id' => $track_id,
                'user_id' => $user_id ?: 0,
                'type' => 'play',
                'ip' => $ip ?: $_SERVER['REMOTE_ADDR'],
                'created_at' => current_time('mysql')
            ]
        );
        
        // Обновляем статистику в кэше
        Cache::delete(['track_stats', $track_id]);
        Cache::delete(['tracks', 'popular']);
        
        // Вызываем событие
        do_action('bm_track_played', $track_id, $user_id);
    }
    
    public function getTrackStats($track_id) {
        $cache_key = ['track_stats', $track_id];
        $stats = Cache::get($cache_key);
        
        if (!$stats) {
            global $wpdb;
            $stats = $wpdb->get_row($wpdb->prepare("
                SELECT 
                    COUNT(CASE WHEN type = 'play' THEN 1 END) as plays,
                    COUNT(CASE WHEN type = 'like' THEN 1 END) as likes,
                    COUNT(CASE WHEN type = 'bookmark' THEN 1 END) as bookmarks
                FROM " . \BM\Database\Connection::table('interaction') . "
                WHERE track_id = %d
            ", $track_id));
            
            Cache::set($cache_key, $stats, 300);
        }
        
        return $stats;
    }
    
    /**
     * 5. ПОИСК СВЯЗАННЫХ СТИХОВ
     */
    public function getRelatedPoems($track) {
        if (!$track->poet_id) {
            return [];
        }
        
        return $this->poem_repo->getByPoet($track->poet_id, 5);
    }
    
    /**
     * 6. ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
     */
    public function makeUniqueSlug($slug, $exclude_id = null) {
        $original = $slug;
        $counter = 1;
        
        while ($this->track_repo->slugExists($slug, $exclude_id)) {
            $slug = $original . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    public function afterTrackCreated($track_id, $data) {
        // Очистка кэша
        Cache::delete(['tracks', 'recent']);
        
        // Отправка уведомлений
        if (!empty($data['is_send_email'])) {
            // Логика отправки email
        }
        
        // Хук для расширения
        do_action('bm_track_created_complete', $track_id, $data);
    }
}