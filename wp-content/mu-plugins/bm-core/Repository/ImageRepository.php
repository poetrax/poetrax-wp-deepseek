<?php
namespace BM\Core\Repository;

use BM\Core\Database\QueryBuilder;
use BM\Core\Database\Connection;
use BM\Core\Database\Cache;
use BM\Taxonomies\EntityRelations;

class ImageRepository implements RepositoryInterface {
    
    private Connection $connection;
    private Cache $cache;
    
    public function __construct() {
     
    }
    
    /**
     * Получить изображения группы
     */
    public function getGroupImages($group_id, $size = 'medium') {
        $cache_key = implode('_', ['images', 'group', $group_id, $size]);
        
        // Получаем из кэша
        $images = $this->cache->get($cache_key);
        
        if (!$images) {
            $images = $this->querybuilder($this->connection)->table('img')
                ->where('img_group_id', $group_id)
                ->where('is_active', 1)
                ->where('is_approved', 1)
                ->orderBy('id')
                ->get();
            
            // Фильтруем по размеру
            $size_map = [
                'thumbnail' => 150,
                'small' => 300,
                'medium' => 600,
                'large' => 1024
            ];
            
            $target_size = $size_map[$size] ?? 300;
            
            $filtered = [];
            foreach ($images as $img) {
                if ($img->width <= $target_size) {
                    $img->url = $this->getImageUrl($img);
                    $filtered[] = $img;
                }
            }
            
            $images = $filtered;
            $this->cache->set($cache_key, $images, 3600);
        }
        
        return $images;
    }
    
    /**
     * Получить URL изображения
     */
    private function getImageUrl($img) {
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'] . '/bm-images/';
        return $base_url . $img->name;
    }

    /**
     * Добавить новое изображение
     */
    public function create($data) {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('name обязателен');
        }
        
        // Определяем расширение
        if (empty($data['ext']) && !empty($data['name'])) {
            $data['ext'] = pathinfo($data['name'], PATHINFO_EXTENSION);
        }
        
        $defaults = [
            'is_active' => 1,
            'is_approved' => 0,
            'created_at' => current_time('mysql'),
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        $id = $this->connection->insert('img', $data);
        
        if ($id) {
            EntityRelations::onEntityCreated($id, 'image');
            
            $this->cache->delete('images_group_' . ($data['img_group_id'] ?? ''));
            $this->cache->delete('images_theme_' . ($data['img_theme_id'] ?? ''));
            
            do_action('bm_image_created', $id, $data);
        }
        return $id;
    }
    
    public function count(array $conditions = []) {
        $this->querybuilder($this->connection)->table('img')->select('COUNT(*) as total');
        
        foreach ($conditions as $field => $value) {
           $this->querybuilder($this->connection)->where($field, $value);
        }
        
        $result = $this->querybuilder($this->connection)->first();
        return $result ? (int)$result->total : 0;
    }

    public function exists(array $conditions) {
        return $this->count($conditions) > 0;
    }

    public function find($id) {
        $cache_key = "image_{$id}";
        $image = $this->cache->get($cache_key);
        
        if (!$image) {
            $image = $this->connection->fetchOne(
                "SELECT * FROM img WHERE id = ?",
                [$id]
            );
            if ($image) {
                $this->cache->set($cache_key, $image, 3600);
            }
        }
        return $image;
    }

    public function findAll($limit = 100, $offset = 0) {
        return $this->getAll($limit, $offset);
    }
    
    public function findBy(array $conditions, $limit = null, $orderBy = null) {
        $this->querybuilder($this->connection)->table('img');
        
        foreach ($conditions as $field => $value) {
            $this->querybuilder($this->connection)->where($field, $value);
        }
        
        if ($orderBy) {
            $this->querybuilder($this->connection)->orderBy($orderBy);
        }
        
        if ($limit) {
            $this->querybuilder($this->connection)->limit($limit);
        }
        
        return $this->querybuilder($this->connection)->get();
    }

    public function findOneBy(array $conditions) {
        $result = $this->findBy($conditions, 1);
        return $result ? $result[0] : null;
    }

    /**
     * Обновить изображение
     */
    public function update($id, $data) {
        unset($data['id']);
        
        $result = $this->connection->update('img', $data, ['id' => $id]);
        
        if ($result) {
            EntityRelations::setEntityType($id, 'image');
            
            $this->cache->delete("image_{$id}");
            $this->cache->delete('images_group_' . ($data['img_group_id'] ?? ''));
            
            do_action('bm_image_updated', $id, $data);
        }
        
        return $result;
    }
    
    /**
     * Удалить изображение
     */
    public function delete($id) {
        $image = $this->find($id);
        
        if (!$image) {
            return false;
        }
        
        // Удаляем физический файл
        if (!empty($image->url)) {
            $file_path = str_replace(home_url('/'), ABSPATH, $image->url);
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        $result = $this->connection->delete('img', ['id' => $id]);
        
        if ($result) {
            EntityRelations::removeAllRelations($id);
            
            $this->cache->delete("image_{$id}");
            $this->cache->delete('images_group_' . $image->img_group_id);
            
            do_action('bm_image_deleted', $id);
        }
        
        return $result;
    }
    
    /**
     * Получить все изображения
     */
    public function getAll($limit = 100, $offset = 0) {
        $cache_key = "images_all_{$limit}_{$offset}";
        $images = $this->cache->get($cache_key);
        
        if (!$images) {
            $images = $this->querybuilder($this->connection)->table('img')
                ->where('is_active', 1)
                ->orderBy('created_at', 'DESC')
                ->limit($limit, $offset)
                ->get();
            
            foreach ($images as $image) {
                $image->url = $this->getImageUrl($image);
            }
            
            $this->cache->set($cache_key, $images, 600);
        }
        
        return $images;
    }
    
    /**
     * Одобрить изображение
     */
    public function approve($id) {
        return $this->update($id, ['is_approved' => 1]);
    }
}