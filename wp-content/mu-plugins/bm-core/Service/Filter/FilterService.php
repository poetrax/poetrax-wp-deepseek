<?php
namespace BM\Core\Service\Filter;

use BM\Core\Database\Connection;
use BM\Core\Repository\TrackRepository;
use BM\Core\Repository\PoetRepository;
use BM\Core\Repository\PoemRepository;
use BM\Core\Repository\UserRepository;
use BM\Core\Config\TableMapper;

class FilterService
{
    private Connection $db;
    private TrackRepository $trackRepo;
    private PoetRepository $poetRepo;
    private PoemRepository $poemRepo;
    private UserRepository $userRepo;
    
    public function __construct()
    {
        $this->db = Connection::getInstance();
        $this->trackRepo = new TrackRepository();
        $this->poetRepo = new PoetRepository();
        $this->poemRepo = new PoemRepository();
        $this->userRepo = new UserRepository();
    }
    
    /**
     * Фильтрация треков
     */
    public function filterTracks(array $filters, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT t.* FROM " . TableMapper::getInstance()->get('track') . " t";
        $where = ["t.is_approved = 1", "t.is_active = 1", "t.status = 'completed'"];
        $params = [];
        
        // Фильтр по поэту
        if (!empty($filters['poet_id'])) {
            $where[] = "t.poet_id = :poet_id";
            $params['poet_id'] = $filters['poet_id'];
        }
        
        // Фильтр по стихотворению
        if (!empty($filters['poem_id'])) {
            $where[] = "t.poem_id = :poem_id";
            $params['poem_id'] = $filters['poem_id'];
        }
        
        // Фильтр по жанру (через связанную таблицу)
        if (!empty($filters['genre_id'])) {
            $sql .= " JOIN " . TableMapper::getInstance()->get('track_music_detail') . " md ON t.id = md.track_id";
            $where[] = "md.genre_id = :genre_id";
            $params['genre_id'] = $filters['genre_id'];
        }
        
        // Фильтр по настроению
        if (!empty($filters['mood_id'])) {
            $where[] = "t.mood_id = :mood_id";
            $params['mood_id'] = $filters['mood_id'];
        }
        
        // Фильтр по длительности
        if (!empty($filters['duration_max'])) {
            $where[] = "t.track_duration <= :duration_max";
            $params['duration_max'] = $filters['duration_max'];
        }
        
        // Сортировка
        $orderBy = " ORDER BY t.created_at DESC";
        
        $sql .= " WHERE " . implode(" AND ", $where) . $orderBy . " LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Фильтрация поэтов
     */
    public function filterPoets(array $filters, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT p.* FROM " . TableMapper::getInstance()->get('poet') . " p";
        $where = ["p.is_active = 1", "p.is_approved = 1"];
        $params = [];
        
        // Фильтр по веку
        if (!empty($filters['century_id'])) {
            $where[] = "p.century_id = :century_id";
            $params['century_id'] = $filters['century_id'];
        }
        
        // Фильтр по направлению
        if (!empty($filters['movement_id'])) {
            $where[] = "p.movement_id = :movement_id";
            $params['movement_id'] = $filters['movement_id'];
        }
        
        // Фильтр по году рождения
        if (!empty($filters['birth_year_from'])) {
            $where[] = "p.birth_year >= :birth_year_from";
            $params['birth_year_from'] = $filters['birth_year_from'];
        }
        
        // Фильтр "есть треки"
        if (!empty($filters['has_tracks'])) {
            $sql .= " JOIN " . TableMapper::getInstance()->get('track') . " t ON p.id = t.poet_id";
            $where[] = "t.is_approved = 1";
        }
        
        // Сортировка
        $orderBy = " ORDER BY p.last_name, p.first_name";
        
        $sql .= " WHERE " . implode(" AND ", $where) . $orderBy . " LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Фильтрация стихов
     */
    public function filterPoems(array $filters, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT pm.* FROM " . TableMapper::getInstance()->get('poem') . " pm";
        $where = ["pm.is_active = 1", "pm.is_approved = 1"];
        $params = [];
        
        // Фильтр по поэту
        if (!empty($filters['poet_id'])) {
            $where[] = "pm.poet_id = :poet_id";
            $params['poet_id'] = $filters['poet_id'];
        }
        
        // Фильтр по языку
        if (!empty($filters['language'])) {
            $where[] = "pm.poem_lang = :language";
            $params['language'] = $filters['language'];
        }
        
        // Фильтр по длине текста
        if (!empty($filters['min_length'])) {
            $where[] = "LENGTH(pm.poem_text) >= :min_length";
            $params['min_length'] = $filters['min_length'];
        }
        
        // Фильтр "есть треки"
        if (!empty($filters['has_tracks'])) {
            $sql .= " JOIN " . TableMapper::getInstance()->get('track') . " t ON pm.id = t.poem_id";
            $where[] = "t.is_approved = 1";
        }
        
        // Сортировка
        $orderBy = " ORDER BY pm.name";
        
        $sql .= " WHERE " . implode(" AND ", $where) . $orderBy . " LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Фильтрация пользователей
     */
    public function filterUsers(array $filters, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT u.* FROM " . TableMapper::getInstance()->get('user') . " u";
        $where = ["1=1"];
        $params = [];
        
        // Фильтр по статусу
        if (isset($filters['is_approved'])) {
            $where[] = "u.is_approved = :is_approved";
            $params['is_approved'] = $filters['is_approved'] ? 1 : 0;
        }
        
        // Фильтр по дате регистрации
        if (!empty($filters['registered_after'])) {
            $where[] = "u.created_at >= :registered_after";
            $params['registered_after'] = $filters['registered_after'];
        }
        
        // Фильтр по количеству треков
        if (!empty($filters['min_tracks'])) {
            $sql .= " LEFT JOIN " . TableMapper::getInstance()->get('track') . " t ON u.id = t.user_id";
            $sql .= " GROUP BY u.id HAVING COUNT(t.id) >= :min_tracks";
            $params['min_tracks'] = $filters['min_tracks'];
        }
        
        // Сортировка
        $orderBy = " ORDER BY u.created_at DESC";
        
        $sql .= " WHERE " . implode(" AND ", $where) . $orderBy . " LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Получить доступные фильтры для типа сущности
     */
    public function getAvailableFilters(string $entityType): array
    {
        $filters = [
            'tracks' => [
                'poet_id' => ['type' => 'int', 'label' => 'Поэт'],
                'poem_id' => ['type' => 'int', 'label' => 'Стихотворение'],
                'genre_id' => ['type' => 'int', 'label' => 'Жанр'],
                'mood_id' => ['type' => 'int', 'label' => 'Настроение'],
                'duration_max' => ['type' => 'int', 'label' => 'Макс. длительность (сек)'],
                'created_after' => ['type' => 'date', 'label' => 'После даты'],
            ],
            'poets' => [
                'century_id' => ['type' => 'int', 'label' => 'Век'],
                'movement_id' => ['type' => 'int', 'label' => 'Направление'],
                'birth_year_from' => ['type' => 'int', 'label' => 'Год рождения с'],
                'birth_year_to' => ['type' => 'int', 'label' => 'Год рождения по'],
                'has_tracks' => ['type' => 'bool', 'label' => 'Есть треки'],
                // Новые фильтры через треки
                'track_genre_id' => ['type' => 'int', 'label' => 'Жанр треков'],
                'track_mood_id' => ['type' => 'int', 'label' => 'Настроение треков'],
                'track_style_id' => ['type' => 'int', 'label' => 'Стиль треков'],
                'track_instrument_id' => ['type' => 'int', 'label' => 'Инструменты в треках'],
                'performance_type' => ['type' => 'string', 'label' => 'Тип исполнения'],
                'voice_gender' => ['type' => 'string', 'label' => 'Вокал'],
                'min_tracks' => ['type' => 'int', 'label' => 'Минимум треков'],
            ],
            'poems' => [
                'poet_id' => ['type' => 'int', 'label' => 'Поэт'],
                'language' => ['type' => 'string', 'label' => 'Язык'],
                'min_length' => ['type' => 'int', 'label' => 'Мин. длина текста'],
                'has_tracks' => ['type' => 'bool', 'label' => 'Есть треки'],
                // Новые фильтры через треки
                'track_genre_id' => ['type' => 'int', 'label' => 'Жанр треков'],
                'track_mood_id' => ['type' => 'int', 'label' => 'Настроение треков'],
                'track_style_id' => ['type' => 'int', 'label' => 'Стиль треков'],
                'track_instrument_id' => ['type' => 'int', 'label' => 'Инструменты'],
                'bpm_min' => ['type' => 'int', 'label' => 'Мин. BPM'],
                'bpm_max' => ['type' => 'int', 'label' => 'Макс. BPM'],
                'min_tracks' => ['type' => 'int', 'label' => 'Минимум треков'],
            ],
            'users' => [
                'is_approved' => ['type' => 'bool', 'label' => 'Подтверждён'],
                'registered_after' => ['type' => 'date', 'label' => 'Зарегистрирован после'],
                'min_tracks' => ['type' => 'int', 'label' => 'Мин. количество треков'],
                // Новые фильтры через созданные треки
                'created_track_genre_id' => ['type' => 'int', 'label' => 'Жанр созданных треков'],
                'created_track_mood_id' => ['type' => 'int', 'label' => 'Настроение созданных треков'],
                'created_track_style_id' => ['type' => 'int', 'label' => 'Стиль созданных треков'],
                'min_created_tracks' => ['type' => 'int', 'label' => 'Минимум созданных треков'],
            ]
        ];
        
        return $filters[$entityType] ?? [];
    }
	
	  /**
     * Фильтрация поэтов через свойства их треков
     */
    public function filterPoetsByTrackProperties(array $filters, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT DISTINCT p.* FROM " . TableMapper::getInstance()->get('poet') . " p";
        $sql .= " JOIN " . TableMapper::getInstance()->get('track') . " t ON p.id = t.poet_id";
        $sql .= " LEFT JOIN " . TableMapper::getInstance()->get('track_music_detail') . " md ON t.id = md.track_id";
        
        $where = ["t.is_approved = 1", "t.is_active = 1", "t.status = 'completed'"];
        $params = [];
        
        // Фильтр по жанру треков
        if (!empty($filters['track_genre_id'])) {
            $where[] = "md.genre_id = :genre_id";
            $params['genre_id'] = $filters['track_genre_id'];
        }
        
        // Фильтр по настроению треков
        if (!empty($filters['track_mood_id'])) {
            $where[] = "t.mood_id = :mood_id";
            $params['mood_id'] = $filters['track_mood_id'];
        }
        
        // Фильтр по стилю треков
        if (!empty($filters['track_style_id'])) {
            $where[] = "md.style_id = :style_id";
            $params['style_id'] = $filters['track_style_id'];
        }
        
        // Фильтр по инструментам в треках
        if (!empty($filters['track_instrument_id'])) {
            $where[] = "md.instrument_ids LIKE :instrument_id";
            $params['instrument_id'] = '%' . $filters['track_instrument_id'] . '%';
        }
        
        // Фильтр по типу исполнения
        if (!empty($filters['performance_type'])) {
            $where[] = "t.performance_type = :performance_type";
            $params['performance_type'] = $filters['performance_type'];
        }
        
        // Фильтр по голосу
        if (!empty($filters['voice_gender'])) {
            $where[] = "t.voice_gender = :voice_gender";
            $params['voice_gender'] = $filters['voice_gender'];
        }
        
        // Минимальное количество треков у поэта
        if (!empty($filters['min_tracks'])) {
            $sql .= " GROUP BY p.id HAVING COUNT(t.id) >= :min_tracks";
            $params['min_tracks'] = $filters['min_tracks'];
        }
        
        $sql .= " WHERE " . implode(" AND ", $where);
        
        // Сортировка по умолчанию
        $sql .= " ORDER BY p.last_name, p.first_name LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Фильтрация стихов через свойства их треков
     */
    public function filterPoemsByTrackProperties(array $filters, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT DISTINCT pm.* FROM " . TableMapper::getInstance()->get('poem') . " pm";
        $sql .= " JOIN " . TableMapper::getInstance()->get('track') . " t ON pm.id = t.poem_id";
        $sql .= " LEFT JOIN " . TableMapper::getInstance()->get('track_music_detail') . " md ON t.id = md.track_id";
        
        $where = ["t.is_approved = 1", "t.is_active = 1", "t.status = 'completed'", "pm.is_active = 1"];
        $params = [];
        
        // Фильтр по жанру треков
        if (!empty($filters['track_genre_id'])) {
            $where[] = "md.genre_id = :genre_id";
            $params['genre_id'] = $filters['track_genre_id'];
        }
        
        // Фильтр по настроению треков
        if (!empty($filters['track_mood_id'])) {
            $where[] = "t.mood_id = :mood_id";
            $params['mood_id'] = $filters['track_mood_id'];
        }
        
        // Фильтр по стилю треков
        if (!empty($filters['track_style_id'])) {
            $where[] = "md.style_id = :style_id";
            $params['style_id'] = $filters['track_style_id'];
        }
        
        // Фильтр по инструментам
        if (!empty($filters['track_instrument_id'])) {
            $where[] = "md.instrument_ids LIKE :instrument_id";
            $params['instrument_id'] = '%' . $filters['track_instrument_id'] . '%';
        }
        
        // Фильтр по BPM (темпу)
        if (!empty($filters['bpm_min'])) {
            $where[] = "md.bpm >= :bpm_min";
            $params['bpm_min'] = $filters['bpm_min'];
        }
        
        if (!empty($filters['bpm_max'])) {
            $where[] = "md.bpm <= :bpm_max";
            $params['bpm_max'] = $filters['bpm_max'];
        }
        
        // Минимальное количество треков у стихотворения
        if (!empty($filters['min_tracks'])) {
            $sql .= " GROUP BY pm.id HAVING COUNT(t.id) >= :min_tracks";
            $params['min_tracks'] = $filters['min_tracks'];
        }
        
        $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " ORDER BY pm.name LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Фильтрация пользователей через свойства их треков
     */
    public function filterUsersByTrackProperties(array $filters, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT DISTINCT u.* FROM " . TableMapper::getInstance()->get('user') . " u";
        $sql .= " JOIN " . TableMapper::getInstance()->get('track') . " t ON u.id = t.user_id";
        $sql .= " LEFT JOIN " . TableMapper::getInstance()->get('track_music_detail') . " md ON t.id = md.track_id";
        
        $where = ["t.is_approved = 1"];
        $params = [];
        
        // Фильтр по жанру созданных треков
        if (!empty($filters['created_track_genre_id'])) {
            $where[] = "md.genre_id = :genre_id";
            $params['genre_id'] = $filters['created_track_genre_id'];
        }
        
        // Фильтр по настроению созданных треков
        if (!empty($filters['created_track_mood_id'])) {
            $where[] = "t.mood_id = :mood_id";
            $params['mood_id'] = $filters['created_track_mood_id'];
        }
        
        // Фильтр по стилю созданных треков
        if (!empty($filters['created_track_style_id'])) {
            $where[] = "md.style_id = :style_id";
            $params['style_id'] = $filters['created_track_style_id'];
        }
        
        // Фильтр по статусу пользователя
        if (isset($filters['is_approved'])) {
            $where[] = "u.is_approved = :is_approved";
            $params['is_approved'] = $filters['is_approved'] ? 1 : 0;
        }
        
        // Минимальное количество созданных треков
        if (!empty($filters['min_created_tracks'])) {
            $sql .= " GROUP BY u.id HAVING COUNT(t.id) >= :min_tracks";
            $params['min_tracks'] = $filters['min_created_tracks'];
        }
        
        $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
}
