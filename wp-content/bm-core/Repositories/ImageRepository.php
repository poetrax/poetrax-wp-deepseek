<?php
namespace BM\Repositories;

use BM\Database\QueryBuilder;
use BM\Database\Connection;
use BM\Database\Cache;
use BM\Taxonomies\EntityRelations;

class ImageRepository implements RepositoryInterface {
    /**
     * Получить изображения группы
     */
    public function getGroupImages($group_id, $size = 'medium') {
        $cache_key = ['images', 'group', $group_id, $size];
        $images = Cache::get($cache_key);
        
        if (!$images) {
            $images = QueryBuilder::table('img')
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
            Cache::set($cache_key, $images, 3600);
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
        
        $id = Connection::insert('img', $data);
        
        if ($id) {
            EntityRelations::onEntityCreated($id, 'image');
            
            Cache::delete(['images', 'group', $data['img_group_id'] ?? '']);
            Cache::delete(['images', 'theme', $data['img_theme_id'] ?? '']);
            
            do_action('bm_image_created', $id, $data);
        }
        
        return $id;
    }
    
    /**
     * Обновить изображение
     */
    public function update($id, $data) {
        unset($data['id']);
        
        $result = Connection::update('img', $data, ['id' => $id]);
        
        if ($result) {
            EntityRelations::setEntityType($id, 'image');
            
            Cache::delete(['image', $id]);
            Cache::delete(['images', 'sizes', $data['img_group_id'] ?? '']);
            
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
        
        $result = Connection::delete('img', ['id' => $id]);
        
        if ($result) {
            EntityRelations::removeAllRelations($id);
            
            Cache::delete(['image', $id]);
            Cache::delete(['images', 'group', $image->img_group_id]);
            Cache::delete(['images', 'sizes', $image->img_group_id]);
            
            do_action('bm_image_deleted', $id);
        }
        
        return $result;
    }
    
    /**
     * Получить все изображения
     */
    public function getAll($limit = 100, $offset = 0) {
        $cache_key = ['images', 'all', $limit, $offset];
        $images = Cache::get($cache_key);
        
        if (!$images) {
            $images = QueryBuilder::table('img')
                ->where('is_active', 1)
                ->orderBy('created_at', 'DESC')
                ->limit($limit, $offset)
                ->get();
            
            foreach ($images as $image) {
                $this->enrichImage($image);
            }
            
            Cache::set($cache_key, $images, 600);
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