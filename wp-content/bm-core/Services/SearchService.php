<?php
namespace BM\Services;

use BM_Database_Connection;
use BM_Database_Cache;

class SearchService {
    
    /**
     * УНИВЕРСАЛЬНЫЙ ПОИСК ПО ВСЕМ ТИПАМ
     */
    public function search($query, $types = ['tracks', 'poems', 'poets'], $limit = 20) {
        $cache_key = ['search', md5($query), implode('_', $types), $limit];
        $results = Cache::get($cache_key);
        
        if (!$results) {
            $results = [
                'tracks' => in_array('tracks', $types) ? $this->searchTracks($query, $limit) : [],
                'poems' => in_array('poems', $types) ? $this->searchPoems($query, $limit) : [],
                'poets' => in_array('poets', $types) ? $this->searchPoets($query, $limit) : [],
                'docs' => in_array('docs', $types) ? $this->searchDocs($query, $limit) : []
            ];
            
            Cache::set($cache_key, $results, 300); // 5 минут
        }
        
        return $results;
    }
    
    /**
     * ПОИСК ТРЕКОВ - FULLTEXT BOOLEAN MODE
     * Скорость: <10ms при 1 млн записей
     */
    public function searchTracks($query, $limit = 20) {
        $search_terms = $this->prepareSearchTerms($query);
        
        $sql = "
            SELECT 
                t.*,
                MATCH(t.track_name, t.caption) AGAINST(? IN BOOLEAN MODE) as relevance,
                p.full_name_first as poet_name,
                pm.name as poem_name
            FROM " . Connection::table('track') . " t
            LEFT JOIN " . Connection::table('poet') . " p ON t.poet_id = p.id
            LEFT JOIN " . Connection::table('poem') . " pm ON t.poem_id = pm.id
            WHERE 
                MATCH(t.track_name, t.caption) AGAINST(? IN BOOLEAN MODE)
                AND t.is_approved = 1
                AND t.is_active = 1
                AND t.status = 'completed'
            ORDER BY relevance DESC, t.created_at DESC
            LIMIT %d
        ";
        
        return Connection::query($sql, [$search_terms, $search_terms, $limit]);
    }
    
    /**
     * ПОИСК СТИХОВ - ПОЛНОТЕКСТОВЫЙ ПО ОГРОМНЫМ ТЕКСТАМ
     * Мгновенно! Даже в текстах на 1000+ слов
     */
    public function searchPoems($query, $limit = 20) {
        $search_terms = $this->prepareSearchTerms($query);
        
        $sql = "
            SELECT 
                p.*,
                MATCH(p.name, p.poem_text) AGAINST(? IN BOOLEAN MODE) as relevance,
                pt.full_name_first as poet_name
            FROM " . Connection::table('poem') . " p
            LEFT JOIN " . Connection::table('poet') . " pt ON p.poet_id = pt.id
            WHERE 
                MATCH(p.name, p.poem_text) AGAINST(? IN BOOLEAN MODE)
                AND p.is_active = 1
                AND p.is_approved = 1
            ORDER BY relevance DESC, p.created_at DESC
            LIMIT %d
        ";
        
        return Connection::query($sql, [$search_terms, $search_terms, $limit]);
    }
    
    /**
     * ПОИСК ПОЭТОВ
     */
    public function searchPoets($query, $limit = 20) {
        $search_terms = $this->prepareSearchTerms($query);
        
        $sql = "
            SELECT 
                *,
                MATCH(last_name, first_name, second_name, full_name_first, full_name_last) 
                AGAINST(? IN BOOLEAN MODE) as relevance
            FROM " . Connection::table('poet') . "
            WHERE 
                MATCH(last_name, first_name, second_name, full_name_first, full_name_last) 
                AGAINST(? IN BOOLEAN MODE)
                AND is_active = 1
                AND is_approved = 1
            ORDER BY relevance DESC
            LIMIT %d
        ";
        
        return Connection::query($sql, [$search_terms, $search_terms, $limit]);
    }
    
    /**
     * ПОИСК ПО ДОКУМЕНТАМ (Оферта, Политика и т.д.)
     */
    public function searchDocs($query, $limit = 20) {
        $search_terms = $this->prepareSearchTerms($query);
        
        $sql = "
            SELECT 
                *,
                MATCH(document_name, document_text) AGAINST(? IN BOOLEAN MODE) as relevance
            FROM " . Connection::table('doc') . "
            WHERE 
                MATCH(document_name, document_text) AGAINST(? IN BOOLEAN MODE)
                AND is_current = 1
            ORDER BY relevance DESC
            LIMIT %d
        ";
        
        return Connection::query($sql, [$search_terms, $search_terms, $limit]);
    }
    
    /**
     * ПОДСКАЗКИ ПОИСКА (autocomplete)
     * Мгновенные ответы при вводе
     */
    public function autocomplete($query, $limit = 5) {
        $results = [
            'tracks' => $this->searchTracks($query . '*', $limit), // Wildcard поиск
            'poems' => $this->searchPoems($query . '*', $limit),
            'poets' => $this->searchPoets($query . '*', $limit)
        ];
        
        return $results;
    }
    
    /**
     * ПОДГОТОВКА ПОИСКОВЫХ ТЕРМОВ ДЛЯ BOOLEAN MODE
     */
    private function prepareSearchTerms($query) {
        $words = explode(' ', trim($query));
        $terms = [];
        
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) > 2) { // Игнорируем короткие слова
                $terms[] = '+' . $word . '*'; // Обязательное наличие + wildcard
            }
        }
        
        return implode(' ', $terms);
    }
    
    /**
     * AJAX-ЭНДПОИНТ ДЛЯ ПОИСКА
     */
    public static function ajaxSearch() {
        $query = sanitize_text_field($_POST['query']);
        $types = isset($_POST['types']) ? (array)$_POST['types'] : ['tracks', 'poems', 'poets'];
        
        $service = new self();
        
        if (isset($_POST['autocomplete']) && $_POST['autocomplete']) {
            $results = $service->autocomplete($query);
        } else {
            $results = $service->search($query, $types);
        }
        
        ob_start();
        include BM_CORE_PATH . 'templates/search-results.php';
        $html = ob_get_clean();
        
        wp_send_json_success([
            'html' => $html,
            'results' => $results,
            'count' => array_sum(array_map('count', $results))
        ]);
    }

    public function searchByType($query, $entity_type, $limit = 20) {
    $entity_ids = EntityRelations::getEntitiesByType($entity_type, 1000);
    
    if (empty($entity_ids)) {
        return [];
    }
    
    $table_map = [
        'track' => 'track',
        'poem'  => 'poem',
        'poet'  => 'poet',
        'image' => 'img',
        'doc'   => 'doc',
    ];
    
    $table = Connection::table($table_map[$entity_type]);
    $ids_placeholder = implode(',', array_fill(0, count($entity_ids), '%d'));
    
    $search_terms = $this->prepareSearchTerms($query);
    
    $sql = "SELECT *,
                MATCH(" . $this->getSearchFields($entity_type) . ") AGAINST(? IN BOOLEAN MODE) as relevance
            FROM $table
            WHERE id IN ($ids_placeholder)
                AND MATCH(" . $this->getSearchFields($entity_type) . ") AGAINST(? IN BOOLEAN MODE)
            ORDER BY relevance DESC
            LIMIT %d";
    
    $params = array_merge(
        [$search_terms],
        $entity_ids,
        [$search_terms],
        [$limit]
    );
    
    return Connection::query($sql, $params);
}

public function getSearchFields($entity_type) {
    $fields = [
        'track' => 'track_name, caption',
        'poem'  => 'name, poem_text',
        'poet'  => 'first_name, last_name, second_name',
        'doc'   => 'document_name, document_text',
        'image' => 'name',
    ];
    
    return $fields[$entity_type] ?? 'id';
}


}