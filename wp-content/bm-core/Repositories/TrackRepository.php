<?php
namespace BM\Repositories;

use BM\Database\QueryBuilder;
use BM\Database\Connection;
use BM\Database\Cache;
use BM\Taxonomies\EntityRelations;

class TrackRepository implements RepositoryInterface {
    
    /**
     * Получить трек по ID
     */
    public function find($id) {
        $cache_key = ['track', $id];
        $track = Cache::get($cache_key);
        
        if (!$track) {
            $track = QueryBuilder::table('track')
                ->where('id', $id)
                ->first();
            
            if ($track) {
                $this->enrichTrack($track);
                Cache::set($cache_key, $track, 3600);
            }
        }
        
        return $track;
    }

    /**
     * Получить популярные треки
     */
    public function getPopular($limit = 10, $offset = 0) {
        $cache_key = ['tracks', 'popular', $limit, $offset];
        $tracks = Cache::get($cache_key);
        
        if (!$tracks) {
            $sql = "
                SELECT t.*, COUNT(i.id) as interaction_count
                FROM " . Connection::table('track') . " t
                LEFT JOIN " . Connection::table('interaction') . " i 
                    ON t.id = i.track_id
                WHERE t.is_approved = 1 
                    AND t.is_active = 1
                    AND t.status = 'completed'
                GROUP BY t.id
                ORDER BY interaction_count DESC, t.created_at DESC
                LIMIT %d OFFSET %d
            ";
            
            $tracks = Connection::query($sql, [$limit, $offset]);
            
            foreach ($tracks as $track) {
                $this->enrichTrack($track);
            }
            
            Cache::set($cache_key, $tracks, 1800); // 30 минут
        }
        
        return $tracks;
    }
    
    /**
     * Получить новые треки
     */
    public function getRecent($limit = 10) {
        $cache_key = ['tracks', 'recent', $limit];
        $tracks = Cache::get($cache_key);
        
        if (!$tracks) {
            $tracks = QueryBuilder::table('track')
                ->where('is_approved', 1)
                ->where('is_active', 1)
                ->where('status', 'completed')
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get();
            
            foreach ($tracks as $track) {
                $this->enrichTrack($track);
            }
            
            Cache::set($cache_key, $tracks, 300); // 5 минут
        }
        
        return $tracks;
    }
    
    /**
     * Получить треки пользователя
     */
    public function getUserTracks($user_id, $limit = 20) {
        return QueryBuilder::table('track')
            ->where('user_id', $user_id)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Поиск треков
     */
    public function search($query, $limit = 20) {
        $search_term = '%' . Connection::escape($query) . '%';
        
        return QueryBuilder::table('track')
            ->where('is_approved', 1)
            ->where('is_active', 1)
            ->where('status', 'completed')
            ->whereLike('track_name', $query)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Получить треки по поэту
     */
    public function getByPoet($poet_id, $limit = 20) {
        $cache_key = ['tracks', 'poet', $poet_id, $limit];
        $tracks = Cache::get($cache_key);
        
        if (!$tracks) {
            $tracks = QueryBuilder::table('track')
                ->where('poet_id', $poet_id)
                ->where('is_approved', 1)
                ->where('is_active', 1)
                ->where('status', 'completed')
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get();
            
            foreach ($tracks as $track) {
                $this->enrichTrack($track);
            }
            
            Cache::set($cache_key, $tracks, 3600);
        }
        
        return $tracks;
    }
    
    /**
     * Получить треки по стихотворению
     */
    public function getByPoem($poem_id, $limit = 10) {
        return QueryBuilder::table('track')
            ->where('poem_id', $poem_id)
            ->where('is_approved', 1)
            ->where('is_active', 1)
            ->where('status', 'completed')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Получить рекомендации
     */
    public function getRecommendations($track_id, $limit = 5) {
        $track = $this->find($track_id);
        if (!$track) return [];
        
        $cache_key = ['tracks', 'recommendations', $track_id, $limit];
        $recommendations = Cache::get($cache_key);
        
        if (!$recommendations) {
            $recommendations = QueryBuilder::table('track')
                ->where('id', '!=', $track_id)
                ->where('is_approved', 1)
                ->where('is_active', 1)
                ->where('status', 'completed');
            
            // По тому же поэту
            if ($track->poet_id) {
                $recommendations->where('poet_id', $track->poet_id);
            }
            
            $recommendations = $recommendations
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get();
            
            Cache::set($cache_key, $recommendations, 1800);
        }
        
        return $recommendations;
    }
    
    /**
     * Добавить взаимодействие (лайк, закладка, просмотр)
     */
    public function addInteraction($track_id, $user_id, $type, $ip = null) {
        $data = [
            'track_id' => $track_id,
            'user_id' => $user_id,
            'type' => $type,
            'ip' => $ip,
            'created_at' => current_time('mysql')
        ];
        
        $id = Connection::insert('interaction', $data);
        
        if ($id) {
            do_action('bm_track_interaction', $track_id, $user_id, $type);
            Cache::flush_by_pattern('tracks*');
        }
        
        return $id;
    }
    
    /**
     * Проверить взаимодействие
     */
    public function hasInteraction($track_id, $user_id, $type) {
        $sql = "SELECT COUNT(*) FROM " . Connection::table('interaction') . "
                WHERE track_id = %d AND user_id = %d AND type = '%s'";
        
        $count = Connection::var($sql, [$track_id, $user_id, $type]);
        return $count > 0;
    }
    
    /**
     * Обогатить трек дополнительными данными
     */
    private function enrichTrack(&$track) {
        // Добавляем информацию о поэте
        if ($track->poet_id) {
            $poet_repo = new PoetRepository();
            $track->poet = $poet_repo->find($track->poet_id);
        }
        
        // Добавляем информацию о стихотворении
        if ($track->poem_id) {
            $poem_repo = new PoemRepository();
            $track->poem = $poem_repo->find($track->poem_id);
        }
        
        // Добавляем музыкальные детали
        $details = QueryBuilder::table('track_music_detail')
            ->where('track_id', $track->id)
            ->first();
        
        $track->music_details = $details;
        
        // Формируем URL
        $track->url = home_url('/track/' . $track->track_slug . '/');
        $track->permalink = $track->guid_track ?? $track->url;
        
        // Подсчет взаимодействий
        $stats = $this->getTrackStats($track->id);
        $track->likes_count = $stats->likes ?? 0;
        $track->bookmarks_count = $stats->bookmarks ?? 0;
        $track->plays_count = $stats->plays ?? 0;
    }
    
    /**
     * Получить статистику трека
     */
    private function getTrackStats($track_id) {
        $cache_key = ['track_stats', $track_id];
        $stats = Cache::get($cache_key);
        
        if (!$stats) {
            $sql = "
                SELECT 
                    SUM(type = 'like') as likes,
                    SUM(type = 'bookmark') as bookmarks,
                    SUM(type = 'play') as plays
                FROM " . Connection::table('interaction') . "
                WHERE track_id = %d
            ";
            
            $stats = Connection::row($sql, [$track_id]);
            Cache::set($cache_key, $stats, 300);
        }
        
        return $stats;
    }

     /**
     * ПАГИНАЦИЯ ЧЕРЕЗ OFFSET (для маленьких данных)
     */
    public function getPaginated($page = 1, $per_page = 20) {
        $offset = ($page - 1) * $per_page;
        
        return QueryBuilder::table('track')
            ->where('is_approved', 1)
            ->where('is_active', 1)
            ->where('status', 'completed')
            ->orderBy('created_at', 'DESC')
            ->limit($per_page, $offset)
            ->get();
    }
    
    /**
     * БЕСКОНЕЧНАЯ ПРОКРУТКА (Keyset Pagination) - МГНОВЕННО
     * Работает при 1 млн записей так же быстро, как при 10
     */
    public function getInfinite($last_id = null, $limit = 20, $filters = []) {
        $query = QueryBuilder::table('track')
            ->select([
                't.*',
                'p.full_name_first as poet_name',
                'p.poet_slug',
                'pm.name as poem_name',
                'pm.poem_slug'
            ])
            ->join('poet', 't.poet_id = p.id', 'LEFT')
            ->join('poem', 't.poem_id = pm.id', 'LEFT')
            ->where('t.is_approved', 1)
            ->where('t.is_active', 1)
            ->where('t.status', 'completed');
        
        // КЛЮЧЕВАЯ ПАГИНАЦИЯ - O(1) вместо O(N)
        if ($last_id) {
            $query->where('t.id', '<', $last_id); // ИЛИ > для обратной
        }
        
        // Применяем фильтры
        $this->applyFilters($query, $filters);
        
        return $query
            ->orderBy('t.id', 'DESC') // ВСЕГДА ПО ID!
            ->limit($limit)
            ->get();
    }
    
    /**
     * AJAX-эндпоинт для бесконечной прокрутки
     */
    public static function ajaxInfiniteScroll() {
        $last_id = isset($_POST['last_id']) ? (int)$_POST['last_id'] : null;
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 20;
        $filters = isset($_POST['filters']) ? $_POST['filters'] : [];
        
        $repo = new self();
        $tracks = $repo->getInfinite($last_id, $limit, $filters);
        
        ob_start();
        foreach ($tracks as $track) {
            include BM_CORE_PATH . 'templates/track-card.php';
        }
        $html = ob_get_clean();
        
        wp_send_json_success([
            'html' => $html,
            'last_id' => end($tracks)->id ?? null,
            'has_more' => count($tracks) === $limit
        ]);
    }


/**
     * ПОЛУЧИТЬ ВСЕ МАСТЕР-ДАННЫЕ ДЛЯ ФИЛЬТРОВ
     */
    public function getFilterMasterData() {
        $cache_key = ['filters', 'master_data'];
        $data = Cache::get($cache_key);
        
        if (!$data) {
            $data = [
                'moods' => QueryBuilder::table('mood')
                    ->where('is_active', 1)
                    ->orderBy('name')
                    ->get(),
                    
                'themes' => QueryBuilder::table('theme')
                    ->where('is_active', 1)
                    ->orderBy('name')
                    ->get(),
                    
                'tempos' => QueryBuilder::table('music_temp')
                    ->where('is_active', 1)
                    ->orderBy('name')
                    ->get(),
                    
                'presentations' => QueryBuilder::table('music_presentation')
                    ->where('is_active', 1)
                    ->orderBy('name')
                    ->get(),
                    
                'voice_genders' => QueryBuilder::table('music_voice_gender')
                    ->where('is_active', 1)
                    ->get(),
                    
                'voice_groups' => QueryBuilder::table('music_voice_group')
                    ->where('is_active', 1)
                    ->get(),
                    
                'directions' => QueryBuilder::table('music_direction')
                    ->where('is_active', 1)
                    ->orderBy('name')
                    ->get(),
                    
                'genres' => QueryBuilder::table('music_genre')
                    ->where('is_active', 1)
                    ->orderBy('name')
                    ->get(),
                    
                'instruments' => QueryBuilder::table('music_instrument')
                    ->where('is_active', 1)
                    ->orderBy('name')
                    ->get(),
                    
                'styles' => QueryBuilder::table('music_style')
                    ->where('is_active', 1)
                    ->where('is_approved', 1)
                    ->orderBy('name')
                    ->get(),
                    
                'voice_characters' => QueryBuilder::table('voice_character')
                    ->where('is_active', 1)
                    ->orderBy('name')
                    ->get(),
                    
                'voice_registers' => QueryBuilder::table('voice_register')
                    ->where('is_active', 1)
                    ->orderBy('name')
                    ->get()
            ];
            
            Cache::set($cache_key, $data, 3600 * 24); // 24 часа
        }
        
        return $data;
    }
    
    /**
     * ФИЛЬТРАЦИЯ ТРЕКОВ С JOIN МАСТЕР-ДАННЫХ
     */
    public function filterTracks($filters, $last_id = null, $limit = 20) {
        $query = QueryBuilder::table('track')
            ->select([
                't.*',
                'm.name as mood_name',
                'th.name as theme_name',
                'tmp.name as tempo_name',
                'p.name as presentation_name',
                'vg.name as voice_gender_name',
                'vgr.name as voice_group_name',
                'vc.name as voice_character_name',
                'vr.name as voice_register_name'
            ])
            ->join('mood', 't.mood_id = m.id', 'LEFT')
            ->join('theme', 't.theme_id = th.id', 'LEFT')
            ->join('music_temp', 't.temp_id = tmp.id', 'LEFT')
            ->join('music_presentation', 't.presentation_id = p.id', 'LEFT')
            ->join('music_voice_gender', 't.voice_gender = vg.id', 'LEFT')
            ->join('music_voice_group', 't.voice_group = vgr.id', 'LEFT')
            ->join('voice_character', 't.voice_character_id = vc.id', 'LEFT')
            ->join('voice_register', 't.voice_register_id = vr.id', 'LEFT')
            ->where('t.is_approved', 1)
            ->where('t.is_active', 1)
            ->where('t.status', 'completed');
        
        // ПРИМЕНЕНИЕ ФИЛЬТРОВ
        $this->applyFilters($query, $filters);
        
        if ($last_id) {
            $query->where('t.id', '<', $last_id);
        }
        
        return $query
            ->orderBy('t.id', 'DESC')
            ->limit($limit)
            ->get();
    }
    
    /**
     * ПРИМЕНЕНИЕ ФИЛЬТРОВ
     */
    private function applyFilters($query, $filters) {
        // Базовые фильтры
        if (!empty($filters['poet_id'])) {
            $query->where('t.poet_id', $filters['poet_id']);
        }
        
        if (!empty($filters['poem_id'])) {
            $query->where('t.poem_id', $filters['poem_id']);
        }
        
        if (!empty($filters['user_id'])) {
            $query->where('t.user_id', $filters['user_id']);
        }
        
        // МУЗЫКАЛЬНЫЕ ФИЛЬТРЫ
        if (!empty($filters['mood_id'])) {
            $query->where('t.mood_id', $filters['mood_id']);
        }
        
        if (!empty($filters['theme_id'])) {
            $query->where('t.theme_id', $filters['theme_id']);
        }
        
        if (!empty($filters['temp_id'])) {
            $query->where('t.temp_id', $filters['temp_id']);
        }
        
        if (!empty($filters['presentation_id'])) {
            $query->where('t.presentation_id', $filters['presentation_id']);
        }
        
        if (!empty($filters['suno_style_id'])) {
            $query->where('t.suno_style_id', $filters['suno_style_id']);
        }
        
        if (!empty($filters['voice_gender'])) {
            $query->where('t.voice_gender', $filters['voice_gender']);
        }
        
        if (!empty($filters['voice_character_id'])) {
            $query->where('t.voice_character_id', $filters['voice_character_id']);
        }
        
        // ФИЛЬТР ПО ЖАНРАМ (через track_music_detail)
        if (!empty($filters['genre_id'])) {
            $query->join('track_music_detail', 't.id = md.track_id')
                  ->where('md.genre_id', $filters['genre_id']);
        }
        
        // ФИЛЬТР ПО СТИЛЯМ (через track_music_detail)
        if (!empty($filters['style_id'])) {
            $query->join('track_music_detail', 't.id = md.track_id')
                  ->where('md.style_id', $filters['style_id']);
        }
        
        // ФИЛЬТР ПО ИНСТРУМЕНТАМ (JSON поиск)
        if (!empty($filters['instrument_id'])) {
            $query->join('track_music_detail', 't.id = md.track_id')
                  ->where('md.instrument_ids', 'LIKE', '%' . $filters['instrument_id'] . '%');
        }
        
        // ФИЛЬТР ПО ТОНАЛЬНОСТИ
        if (!empty($filters['tonality_note'])) {
            $query->join('track_music_detail', 't.id = md.track_id')
                  ->where('md.tonality_note', $filters['tonality_note']);
        }
        
        if (!empty($filters['tonality_mood'])) {
            $query->join('track_music_detail', 't.id = md.track_id')
                  ->where('md.tonality_mood', $filters['tonality_mood']);
        }
        
        // ФИЛЬТР ПО BPM (диапазон)
        if (!empty($filters['bpm_min'])) {
            $query->join('track_music_detail', 't.id = md.track_id')
                  ->where('md.bpm', '>=', (int)$filters['bpm_min']);
        }
        
        if (!empty($filters['bpm_max'])) {
            $query->join('track_music_detail', 't.id = md.track_id')
                  ->where('md.bpm', '<=', (int)$filters['bpm_max']);
        }
        
        // ФИЛЬТР ПО ДЛИТЕЛЬНОСТИ
        if (!empty($filters['duration_max'])) {
            $query->where('t.track_duration', '<=', (int)$filters['duration_max']);
        }
        
        // ФИЛЬТР ПО ГОДУ
        if (!empty($filters['year'])) {
            $query->whereRaw('YEAR(t.created_at) = %d', [(int)$filters['year']]);
        }
        
        // ФИЛЬТР ПО ТИПУ ИСПОЛНЕНИЯ
        if (!empty($filters['performance_type'])) {
            $query->where('t.performance_type', $filters['performance_type']);
        }
    }
    
    /**
     * AJAX-ЭНДПОИНТ ДЛЯ ФИЛЬТРАЦИИ
     */
    public static function ajaxFilter() {
        $filters = json_decode(stripslashes($_POST['filters']), true);
        $last_id = isset($_POST['last_id']) ? (int)$_POST['last_id'] : null;
        
        $repo = new self();
        $tracks = $repo->filterTracks($filters, $last_id);
        
        ob_start();
        foreach ($tracks as $track) {
            include BM_CORE_PATH . 'templates/track-card.php';
        }
        $html = ob_get_clean();
        
        wp_send_json_success([
            'html' => $html,
            'last_id' => end($tracks)->id ?? null,
            'has_more' => count($tracks) === 20,
            'count' => count($tracks)
        ]);
    }
   
    public function findBySlug($slug) {
        $cache_key = ['track', 'slug', $slug];
        $track = Cache::get($cache_key);
        if (!$track) {
            $track = QueryBuilder::table('track')
                ->where('track_slug', $slug)
                ->where('is_active', 1)
                ->where('is_approved', 1)
                ->first();
            if ($track) {
                $this->enrichTrack($track);
                Cache::set($cache_key, $track, 3600);
            }
        }
        return $track;
    }

      /**
     * Создать новый трек
     */
    public function create($data) {
        // Валидация обязательных полей
        if (empty($data['user_id']) || empty($data['track_name'])) {
            throw new \InvalidArgumentException('user_id и track_name обязательны');
        }
        
        // Устанавливаем значения по умолчанию
        $defaults = [
            'is_approved' => 0,
            'is_active' => 1,
            'status' => 'new',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'performance_type' => 'song',
            'voice_gender' => 'male',
            'is_site_placement' => 1,
            'is_send_email' => 0,
            'age_restriction' => 0,
            'is_show_img' => 1,
            'is_show_caption' => 1,
            'is_advertising' => 0,
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // Вставка в БД
        $id = Connection::insert('track', $data);
        
        if ($id) {
            // Привязываем тип сущности
            EntityRelations::onEntityCreated($id, 'track');
            
            // Очищаем кэш
            Cache::delete(['tracks', 'recent']);
            Cache::delete(['tracks', 'popular']);
            
            // Хук для дальнейших действий
            do_action('bm_track_created', $id, $data);
        }
        
        return $id;
    }
    
    /**
     * Обновить трек
     */
    public function update($id, $data) {
        // Не даём менять ID
        unset($data['id']);
        
        // Добавляем время обновления
        $data['updated_at'] = current_time('mysql');
        
        $result = Connection::update('track', $data, ['id' => $id]);
        
        if ($result) {
            // Убеждаемся, что тип правильный
            EntityRelations::setEntityType($id, 'track');
            
            // Очищаем кэш этого трека
            Cache::delete(['track', $id]);
            Cache::delete(['tracks', 'recent']);
            Cache::delete(['tracks', 'popular']);
            
            do_action('bm_track_updated', $id, $data);
        }
        
        return $result;
    }
    
    /**
     * Удалить трек
     */
    public function delete($id) {
        $result = Connection::delete('track', ['id' => $id]);
        
        if ($result) {
            // Удаляем связи с таксономиями
            EntityRelations::removeAllRelations($id);
            
            // Очищаем кэш
            Cache::delete(['track', $id]);
            Cache::delete(['tracks', 'recent']);
            Cache::delete(['tracks', 'popular']);
            
            do_action('bm_track_deleted', $id);
        }
        
        return $result;
    }
    
    /**
     * Получить все треки (с пагинацией)
     */
    public function getAll($limit = 100, $offset = 0) {
        $cache_key = ['tracks', 'all', $limit, $offset];
        $tracks = Cache::get($cache_key);
        
        if (!$tracks) {
            $tracks = QueryBuilder::table('track')
                ->orderBy('created_at', 'DESC')
                ->limit($limit, $offset)
                ->get();
            
            foreach ($tracks as $track) {
                $this->enrichTrack($track);
            }
            
            Cache::set($cache_key, $tracks, 300);
        }
        
        return $tracks;
    }
    
    /**
     * Одобрить трек (админка)
     */
    public function approve($id) {
        return $this->update($id, ['is_approved' => 1]);
    }
    
    /**
     * Отклонить трек
     */
    public function reject($id, $message = '') {
        return $this->update($id, [
            'is_approved' => 0,
            'admin_message' => $message,
            'status' => 'cancelled'
        ]);
    }

}