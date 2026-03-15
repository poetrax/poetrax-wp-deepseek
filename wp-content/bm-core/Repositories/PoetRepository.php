<?php
namespace BM\Repositories;

use BM\Database\QueryBuilder;
use BM\Database\Connection;
use BM\Database\Cache;
use BM\Taxonomies\EntityRelations;

class PoetRepository implements RepositoryInterface {
    
    /**
     * Получить поэта по ID
     */
    public function find($id) {
        $cache_key = ['poet', $id];
        $poet = Cache::get($cache_key);
        
        if (!$poet) {
            $poet = QueryBuilder::table('poet')
                ->where('id', $id)
                ->where('is_active', 1)
                ->where('is_approved', 1)
                ->first();
            
            if ($poet) {
                $this->enrichPoet($poet);
                Cache::set($cache_key, $poet, 3600);
            }
        }
        
        return $poet;
    }
    
    /**
     * Получить поэта по slug
     */
    public function findBySlug($slug) {
        $cache_key = ['poet', 'slug', $slug];
        $poet = Cache::get($cache_key);
        
        if (!$poet) {
            $poet = QueryBuilder::table('poet')
                ->where('poet_slug', $slug)
                ->where('is_active', 1)
                ->where('is_approved', 1)
                ->first();
            
            if ($poet) {
                $this->enrichPoet($poet);
                Cache::set($cache_key, $poet, 3600);
            }
        }
        
        return $poet;
    }
    

  
    
    /**
     * Популярные поэты
     */
    public function getPopular($limit = 10) {
        $cache_key = ['poets', 'popular', $limit];
        $poets = Cache::get($cache_key);
        
        if (!$poets) {
            $sql = "
                SELECT p.*, COUNT(t.id) as tracks_count
                FROM " . Connection::table('poet') . " p
                LEFT JOIN " . Connection::table('track') . " t 
                    ON p.id = t.poet_id 
                    AND t.is_approved = 1 
                    AND t.is_active = 1
                    AND t.status = 'completed'
                WHERE p.is_active = 1 AND p.is_approved = 1
                GROUP BY p.id
                HAVING tracks_count > 0
                ORDER BY tracks_count DESC
                LIMIT %d
            ";
            
            $poets = Connection::query($sql, [$limit]);
            Cache::set($cache_key, $poets, 3600);
        }
        
        return $poets;
    }
    
    /**
     * Обогатить поэта данными
     */
    private function enrichPoet(&$poet) {
        $poet->full_name = trim($poet->first_name . ' ' . $poet->last_name);
        $poet->short_name = $poet->short_name ?? $poet->full_name;
        $poet->url = home_url('/poet/' . $poet->poet_slug . '/');
    }

    public function getRelatedTracks($poet_id, $limit = 20) {
        // Сначала получаем все треки (они имеют тип 'track')
        $track_ids = EntityRelations::getEntitiesByType('track', 1000);
    
        if (empty($track_ids)) {
            return [];
        }
    
        // Фильтруем те, где poet_id = наш
        $placeholders = implode(',', array_fill(0, count($track_ids), '%d'));
    
        $sql = "SELECT * FROM " . Connection::table('track') . "
                WHERE id IN ($placeholders) AND poet_id = %d
                ORDER BY created_at DESC
                LIMIT %d";
    
        $params = array_merge($track_ids, [$poet_id, $limit]);
    
        $tracks = Connection::query($sql, $params);
    
        // Обогащаем
        foreach ($tracks as $track) {
            $this->enrichTrack($track); // если метод доступен
        }
    
        return $tracks;
    }

 

  public function getRandom($limit = 3) {
        $cache_key = ['poets', 'random', $limit];
        $poets = Cache::get($cache_key);
        if (!$poets) {
            global $wpdb;
            $table = Connection::table('poet');
            $sql = $wpdb->prepare(
                "SELECT * FROM $table WHERE is_active = 1 AND is_approved = 1 ORDER BY RAND() LIMIT %d",
                $limit
            );
            $poets = $wpdb->get_results($sql);
            foreach ($poets as $poet) {
                $this->enrichPoet($poet);
            }
            Cache::set($cache_key, $poets, 3600);
        }
        return $poets;
  }

  public function getRecent($limit = 10) {
        $cache_key = ['poets', 'recent', $limit];
        $poets = Cache::get($cache_key);
        if (!$poets) {
            $poets = QueryBuilder::table('poet')
                ->where('is_active', 1)
                ->where('is_approved', 1)
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get();
            foreach ($poets as $poet) {
                $this->enrichPoem($poet);
            }
            Cache::set($cache_key, $poets, 300);
        }
        return $poems;
  }
   /**
     * Создать нового поэта
     */
    public function create($data) {
        if (empty($data['last_name'])) {
            throw new \InvalidArgumentException('last_name обязателен');
        }
        
        // Генерируем slug
        if (empty($data['poet_slug'])) {
            $name_parts = array_filter([
                $data['last_name'] ?? '',
                $data['first_name'] ?? '',
                $data['second_name'] ?? ''
            ]);
            $data['poet_slug'] = sanitize_title(implode(' ', $name_parts));
        }
        
        // Формируем полные имена
        $data['full_name_first'] = trim(implode(' ', array_filter([
            $data['first_name'] ?? '',
            $data['second_name'] ?? '',
            $data['last_name'] ?? ''
        ])));
        
        $data['full_name_last'] = trim(implode(' ', array_filter([
            $data['last_name'] ?? '',
            $data['first_name'] ?? '',
            $data['second_name'] ?? ''
        ])));
        
        $data['first_last_name'] = trim(implode(' ', array_filter([
            $data['first_name'] ?? '',
            $data['last_name'] ?? ''
        ])));
        
        $data['short_name'] = $this->generateShortName($data);
        
        $defaults = [
            'is_active' => 1,
            'is_approved' => 1,
            'created_at' => current_time('mysql'),
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        $id = Connection::insert('poet', $data);
        
        if ($id) {
            EntityRelations::onEntityCreated($id, 'poet');
            
            Cache::delete(['poets', 'all']);
            Cache::delete(['poets', 'popular']);
            
            do_action('bm_poet_created', $id, $data);
        }
        
        return $id;
    }
    
    /**
     * Обновить поэта
     */
    public function update($id, $data) {
        unset($data['id']);
        
        // Пересчитываем имена, если изменились компоненты
        if (isset($data['last_name']) || isset($data['first_name']) || isset($data['second_name'])) {
            $current = $this->find($id);
            $last = $data['last_name'] ?? $current->last_name;
            $first = $data['first_name'] ?? $current->first_name;
            $second = $data['second_name'] ?? $current->second_name;
            
            $data['full_name_first'] = trim("$first $second $last");
            $data['full_name_last'] = trim("$last $first $second");
            $data['first_last_name'] = trim("$first $last");
            $data['short_name'] = $this->generateShortName([
                'last_name' => $last,
                'first_name' => $first,
                'second_name' => $second,
                'name_sfx' => $data['name_sfx'] ?? $current->name_sfx ?? ''
            ]);
        }
        
        $result = Connection::update('poet', $data, ['id' => $id]);
        
        if ($result) {
            EntityRelations::setEntityType($id, 'poet');
            
            Cache::delete(['poet', $id]);
            Cache::delete(['poet', 'slug', $data['poet_slug'] ?? '']);
            Cache::delete(['poets', 'all']);
            Cache::delete(['poets', 'popular']);
            
            do_action('bm_poet_updated', $id, $data);
        }
        
        return $result;
    }
    
    /**
     * Удалить поэта
     */
    public function delete($id) {
        // Проверяем, есть ли стихи этого поэта
        $poem_repo = new PoemRepository();
        $poems = $poem_repo->getByPoet($id, 1);
        
        if (!empty($poems)) {
            throw new \Exception('Нельзя удалить поэта, у которого есть стихи');
        }
        
        // Проверяем, есть ли треки этого поэта
        $track_repo = new TrackRepository();
        $tracks = $track_repo->getByPoet($id, 1);
        
        if (!empty($tracks)) {
            throw new \Exception('Нельзя удалить поэта, у которого есть треки');
        }
        
        $result = Connection::delete('poet', ['id' => $id]);
        
        if ($result) {
            EntityRelations::removeAllRelations($id);
            
            Cache::delete(['poet', $id]);
            Cache::delete(['poets', 'all']);
            Cache::delete(['poets', 'popular']);
            
            do_action('bm_poet_deleted', $id);
        }
        
        return $result;
    }

    /**
     * Получить всех поэтов
     */
    public function getAll($limit = 100, $offset = 0) {
        $cache_key = ['poets', 'all', $limit, $offset];
        $poets = Cache::get($cache_key);
        
        if (!$poets) {
            $poets = QueryBuilder::table('poet')
                ->where('is_active', 1)
                ->where('is_approved', 1)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit($limit, $offset)
                ->get();
            
            foreach ($poets as $poet) {
                $this->enrichPoet($poet);
            }
            
            Cache::set($cache_key, $poets, 3600);
        }
        
        return $poets;
    }
    
    /**
     * Сгенерировать краткое имя (А. А. Блок)
     */
    private function generateShortName($data) {
        $parts = [];
        
        if (!empty($data['first_name'])) {
            $parts[] = mb_substr($data['first_name'], 0, 1) . '.';
        }
        
        if (!empty($data['second_name'])) {
            $parts[] = mb_substr($data['second_name'], 0, 1) . '.';
        }
        
        if (!empty($data['last_name'])) {
            $parts[] = $data['last_name'];
        }
        
        $short = implode(' ', $parts);
        
        if (!empty($data['name_sfx'])) {
            $short .= ' ' . $data['name_sfx'];
        }
        
        return $short;
    }

}