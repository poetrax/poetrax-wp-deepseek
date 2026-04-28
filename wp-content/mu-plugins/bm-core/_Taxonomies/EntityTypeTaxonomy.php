<?php
namespace BM\Taxonomies;

class EntityTypeTaxonomy {
    
    const TAXONOMY = 'bm_entity_type';
    
    /**
     * Регистрация таксономии
     */
    public static function register() {
        register_taxonomy(
            self::TAXONOMY,
            null, // не привязываем к стандартным типам постов
            [
                'labels' => [
                    'name'          => 'Типы сущностей',
                    'singular_name' => 'Тип сущности',
                ],
                'public'       => false, // не показываем в админке
                'hierarchical' => false,
                'rewrite'      => false,
                'query_var'    => false,
            ]
        );
    }
    
    /**
     * Получить ID термина для типа сущности
     */
    public static function getTermId($entity_type) {
        $term = term_exists($entity_type, self::TAXONOMY);
        
        if (!$term) {
            $term = wp_insert_term(
                self::getEntityTypeName($entity_type),
                self::TAXONOMY,
                ['slug' => $entity_type]
            );
        }
        
        return is_array($term) ? $term['term_id'] : $term;
    }
    
    /**
     * Человеко-читаемое название типа
     */
    private static function getEntityTypeName($type) {
        $names = [
            'track'  => 'Трек',
            'poem'   => 'Стихотворение',
            'poet'   => 'Поэт',
            'image'  => 'Изображение',
            'doc'    => 'Документ',
        ];
        
        return $names[$type] ?? ucfirst($type);
    }
}