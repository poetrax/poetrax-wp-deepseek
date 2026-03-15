<?php
namespace BM\Taxonomies;

use BM\Database\Connection;

class EntityRelations {
    
    /**
     * Привязать сущность к её типу
     */
    public static function setEntityType($entity_id, $entity_type) {
        $term_id = EntityTypeTaxonomy::getTermId($entity_type);
        
        if (!$term_id) {
            return false;
        }
        
        // Получаем term_taxonomy_id
        global $wpdb;
        $term_taxonomy_id = $wpdb->get_var($wpdb->prepare(
            "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} 
             WHERE term_id = %d AND taxonomy = %s",
            $term_id,
            EntityTypeTaxonomy::TAXONOMY
        ));
        
        if (!$term_taxonomy_id) {
            return false;
        }
        
        // Удаляем старые связи для этого объекта (на всякий случай)
        self::removeAllRelations($entity_id);
        
        // Добавляем новую связь
        return $wpdb->insert(
            $wpdb->term_relationships,
            [
                'object_id'        => $entity_id,
                'term_taxonomy_id' => $term_taxonomy_id,
                'term_order'       => 0,
            ],
            ['%d', '%d', '%d']
        );
    }
    
    /**
     * Получить тип сущности по ID
     */
    public static function getEntityType($entity_id) {
        global $wpdb;
        
        $term_taxonomy_id = $wpdb->get_var($wpdb->prepare(
            "SELECT term_taxonomy_id FROM {$wpdb->term_relationships} 
             WHERE object_id = %d",
            $entity_id
        ));
        
        if (!$term_taxonomy_id) {
            return null;
        }
        
        $term_id = $wpdb->get_var($wpdb->prepare(
            "SELECT term_id FROM {$wpdb->term_taxonomy} 
             WHERE term_taxonomy_id = %d AND taxonomy = %s",
            $term_taxonomy_id,
            EntityTypeTaxonomy::TAXONOMY
        ));
        
        if (!$term_id) {
            return null;
        }
        
        $term = get_term($term_id, EntityTypeTaxonomy::TAXONOMY);
        
        return $term ? $term->slug : null;
    }
    
    /**
     * Получить все сущности определённого типа
     */
    public static function getEntitiesByType($entity_type, $limit = 100) {
        $term_id = EntityTypeTaxonomy::getTermId($entity_type);
        
        if (!$term_id) {
            return [];
        }
        
        global $wpdb;
        $term_taxonomy_id = $wpdb->get_var($wpdb->prepare(
            "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} 
             WHERE term_id = %d AND taxonomy = %s",
            $term_id,
            EntityTypeTaxonomy::TAXONOMY
        ));
        
        if (!$term_taxonomy_id) {
            return [];
        }
        
        return $wpdb->get_col($wpdb->prepare(
            "SELECT object_id FROM {$wpdb->term_relationships} 
             WHERE term_taxonomy_id = %d 
             ORDER BY term_order ASC 
             LIMIT %d",
            $term_taxonomy_id,
            $limit
        ));
    }
    
    /**
     * Удалить все связи для объекта
     */
    public static function removeAllRelations($object_id) {
        global $wpdb;
        return $wpdb->delete(
            $wpdb->term_relationships,
            ['object_id' => $object_id],
            ['%d']
        );
    }
    
    /**
     * При создании новой сущности автоматически привязываем тип
     */
    public static function onEntityCreated($entity_id, $entity_type) {
        self::setEntityType($entity_id, $entity_type);
        
        // Сбрасываем кэш
        wp_cache_delete('entity_type_' . $entity_id, 'bm_entity');
    }
}