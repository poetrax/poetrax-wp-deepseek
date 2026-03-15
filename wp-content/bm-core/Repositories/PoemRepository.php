<?php
namespace BM\Repositories;

use BM\Database\QueryBuilder;
use BM\Database\Connection;
use BM\Database\Cache;
use BM\Taxonomies\EntityRelations;

class PoemRepository  implements RepositoryInterface {
    
    /**
     * Получить стихотворение по ID
     */
    public function find($id) {
        $cache_key = ['poem', $id];
        $poem = Cache::get($cache_key);
        
        if (!$poem) {
            $poem = QueryBuilder::table('poem')
                ->where('id', $id)
                ->where('is_active', 1)
                ->where('is_approved', 1)
                ->first();
            
            if ($poem) {
                $this->enrichPoem($poem);
                Cache::set($cache_key, $poem, 3600);
            }
        }
        
        return $poem;
    }
    
    /**
     * Получить стихотворение по slug
     */
    public function findBySlug($slug) {
        $cache_key = ['poem', 'slug', $slug];
        $poem = Cache::get($cache_key);
        
        if (!$poem) {
            $poem = QueryBuilder::table('poem')
                ->where('poem_slug', $slug)
                ->where('is_active', 1)
                ->where('is_approved', 1)
                ->first();
            
            if ($poem) {
                $this->enrichPoem($poem);
                Cache::set($cache_key, $poem, 3600);
            }
        }
        
        return $poem;
    }
    

    /**
     * Получить стихи поэта
     */
    public function getByPoet($poet_id, $limit = 20) {
        $cache_key = ['poems', 'poet', $poet_id, $limit];
        $poems = Cache::get($cache_key);
        
        if (!$poems) {
            $poems = QueryBuilder::table('poem')
                ->where('poet_id', $poet_id)
                ->where('is_active', 1)
                ->where('is_approved', 1)
                ->orderBy('name')
                ->limit($limit)
                ->get();
            
            Cache::set($cache_key, $poems, 3600);
        }
        
        return $poems;
    }
    
    /**
     * Популярные стихи (по количеству треков)
     */
    public function getPopular($limit = 10) {
        $cache_key = ['poems', 'popular', $limit];
        $poems = Cache::get($cache_key);
        
        if (!$poems) {
            $sql = "
                SELECT p.*, COUNT(t.id) as tracks_count
                FROM " . Connection::table('poem') . " p
                LEFT JOIN " . Connection::table('track') . " t 
                    ON p.id = t.poem_id 
                    AND t.is_approved = 1 
                    AND t.is_active = 1
                    AND t.status = 'completed'
                WHERE p.is_active = 1 AND p.is_approved = 1
                GROUP BY p.id
                HAVING tracks_count > 0
                ORDER BY tracks_count DESC
                LIMIT %d
            ";
            
            $poems = Connection::query($sql, [$limit]);
            Cache::set($cache_key, $poems, 3600);
        }
        
        return $poems;
    }
    
    /**
     * Поиск стихов
     */
    public function search($query, $limit = 20) {
        return QueryBuilder::table('poem')
            ->where('is_active', 1)
            ->where('is_approved', 1)
            ->whereLike('name', $query)
            ->orWhereLike('poem_text', $query)
            ->limit($limit)
            ->get();
    }
    
    /**
     * Обогатить стихотворение данными
     */
    private function enrichPoem(&$poem) {
        if ($poem->poet_id) {
            $poet_repo = new PoetRepository();
            $poem->poet = $poet_repo->find($poem->poet_id);
        }
        
        // Количество треков
        $track_repo = new TrackRepository();
        $tracks = $track_repo->getByPoem($poem->id, 1);
        $poem->tracks_count = count($tracks); // В реальности нужно COUNT
        
        $poem->url = home_url('/poem/' . $poem->poem_slug . '/');
    }

 

    public function getRecent($limit = 10) {
        $cache_key = ['poems', 'recent', $limit];
        $poems = Cache::get($cache_key);
        if (!$poems) {
            $poems = QueryBuilder::table('poem')
                ->where('is_active', 1)
                ->where('is_approved', 1)
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get();
            foreach ($poems as $poem) {
                $this->enrichPoem($poem);
            }
            Cache::set($cache_key, $poems, 300);
        }
        return $poems;
    }

     /**
     * Создать новое стихотворение
     */
    public function create($data) {
        if (empty($data['name']) || empty($data['poet_id'])) {
            throw new \InvalidArgumentException('name и poet_id обязательны');
        }
        
        // Генерируем slug, если не указан
        if (empty($data['poem_slug'])) {
            $data['poem_slug'] = sanitize_title($data['name']);
        }
        
        $defaults = [
            'is_active' => 1,
            'is_approved' => 0,
            'created_at' => current_time('mysql'),
            'poem_lang' => 'ru',
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        $id = Connection::insert('poem', $data);
        
        if ($id) {
            EntityRelations::onEntityCreated($id, 'poem');
            
            Cache::delete(['poems', 'popular']);
            Cache::delete(['poems', 'recent']);
            
            do_action('bm_poem_created', $id, $data);
        }
        
        return $id;
    }
    
    /**
     * Обновить стихотворение
     */
    public function update($id, $data) {
        unset($data['id']);
        
        $result = Connection::update('poem', $data, ['id' => $id]);
        
        if ($result) {
            EntityRelations::setEntityType($id, 'poem');
            
            Cache::delete(['poem', $id]);
            Cache::delete(['poem', 'slug', $data['poem_slug'] ?? '']);
            Cache::delete(['poems', 'popular']);
            
            do_action('bm_poem_updated', $id, $data);
        }
        
        return $result;
    }
    
    /**
     * Удалить стихотворение
     */
    public function delete($id) {
        // Проверяем, есть ли треки на это стихотворение
        $track_repo = new TrackRepository();
        $tracks = $track_repo->getByPoem($id, 1);
        
        if (!empty($tracks)) {
            throw new \Exception('Нельзя удалить стихотворение, на которое есть треки');
        }
        
        $result = Connection::delete('poem', ['id' => $id]);
        
        if ($result) {
            EntityRelations::removeAllRelations($id);
            
            Cache::delete(['poem', $id]);
            Cache::delete(['poems', 'popular']);
            Cache::delete(['poems', 'recent']);
            
            do_action('bm_poem_deleted', $id);
        }
        
        return $result;
    }
    
    /**
     * Получить все стихотворения
     */
    public function getAll($limit = 100, $offset = 0) {
        $cache_key = ['poems', 'all', $limit, $offset];
        $poems = Cache::get($cache_key);
        
        if (!$poems) {
            $poems = QueryBuilder::table('poem')
                ->where('is_active', 1)
                ->orderBy('name')
                ->limit($limit, $offset)
                ->get();
            
            foreach ($poems as $poem) {
                $this->enrichPoem($poem);
            }
            
            Cache::set($cache_key, $poems, 600);
        }
        
        return $poems;
    }
}