<?php
 namespace BM\Hierarchy;
class EnhancedPoetRouter {
    private $hierarchy_api;
    
    public function __construct() {
        $this->hierarchy_api = new PoetHierarchyAPI();
        
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_requests']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    public function add_rewrite_rules() {
        // Поддержка любого уровня вложенности (до 5 уровней)
        for ($i = 1; $i <= 5; $i++) {
            $pattern = str_repeat('/([^/]+)', $i);
            $vars = '';
            for ($j = 1; $j <= $i; $j++) {
                $vars .= '&poet_level' . $j . '=$matches[' . $j . ']';
            }
            
            add_rewrite_rule(
                '^poet' . $pattern . '/?$',
                'index.php?poet_path=' . $vars,
                'top'
            );
        }
        
        // Поддержка полного пути
        add_rewrite_rule(
            '^poet/(.+?)/?$',
            'index.php?poet_full_path=$matches[1]',
            'top'
        );
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'poet_path';
        $vars[] = 'poet_full_path';
        for ($i = 1; $i <= 5; $i++) {
            $vars[] = 'poet_level' . $i;
        }
        return $vars;
    }
    
    public function handle_requests() {
        global $wp_query;
        
        $full_path = get_query_var('poet_full_path');
        
        if (!$full_path) {
            return;
        }
        
        // Разбираем путь
        $path_parts = explode('/', trim($full_path, '/'));
        $last_part = end($path_parts);
        
        // Пытаемся найти поэта по полному пути
        $result = $this->find_by_full_path($full_path);
        
        if ($result) {
            if ($result['type'] === 'poet') {
                $this->show_poet($result['data']);
            } elseif ($result['type'] === 'movement') {
                $this->show_movement($result['data']);
            } elseif ($result['type'] === 'century') {
                $this->show_century($result['data']);
            }
            exit;
        }
        
        // Если не нашли - 404
        $wp_query->set_404();
        status_header(404);
    }
    
    private function find_by_full_path($path) {
        $mysqli = $this->hierarchy_api->get_mysqli();
        
        // Ищем поэта
        $stmt = $mysqli->prepare("
            SELECT 'poet' as type, p.*, 
                   c.name as century_name, 
                   m.name as movement_name
            FROM bm_ctbl000_poet p
            LEFT JOIN bm_ctbl000_poet_centuries c ON p.century_id = c.id
            LEFT JOIN bm_ctbl000_poet_movements m ON p.movement_id = m.id
            WHERE p.full_slug = ?
        ");
        $stmt->bind_param('s', $path);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return [
                'type' => 'poet',
                'data' => $result->fetch_assoc()
            ];
        }
        
        // Ищем направление
        $movement_path = $path;
        $stmt = $mysqli->prepare("
            SELECT m.*, c.name as century_name
            FROM bm_ctbl000_poet_movements m
            LEFT JOIN bm_ctbl000_poet_centuries c ON m.century_id = c.id
            WHERE CONCAT(c.slug, '/', get_movement_path(m.id)) = ?
        ");
        $stmt->bind_param('s', $movement_path);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return [
                'type' => 'movement',
                'data' => $result->fetch_assoc()
            ];
        }
        
        // Ищем век
        $stmt = $mysqli->prepare("
            SELECT * FROM bm_ctbl000_poet_centuries WHERE slug = ?
        ");
        $stmt->bind_param('s', $path);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return [
                'type' => 'century',
                'data' => $result->fetch_assoc()
            ];
        }
        
        return null;
    }
    
    private function show_poet($poet) {
        // Получаем произведения
        $mysqli = $this->hierarchy_api->get_mysqli();
        $stmt = $mysqli->prepare("
            SELECT * FROM works 
            WHERE poet_id = ? 
            ORDER BY published_at DESC
        ");
        $stmt->bind_param('i', $poet['id']);
        $stmt->execute();
        $works = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        set_query_var('poet_data', $poet);
        set_query_var('works_data', $works);
        set_query_var('breadcrumbs', $this->hierarchy_api->get_breadcrumbs($poet['slug']));
        
        locate_template('templates/poet-single.php', true);
    }
    
    private function show_movement($movement) {
        $mysqli = $this->hierarchy_api->get_mysqli();
        
        // Получаем поднаправления
        $sub_stmt = $mysqli->prepare("
            SELECT * FROM bm_ctbl000_poet_movements 
            WHERE parent_id = ? 
            ORDER BY display_order, name
        ");
        $sub_stmt->bind_param('i', $movement['id']);
        $sub_stmt->execute();
        $subbm_ctbl000_poet_movements = $sub_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Получаем поэтов
        $poet_stmt = $mysqli->prepare("
            SELECT * FROM bm_ctbl000_poet 
            WHERE movement_id = ? 
            ORDER BY name
        ");
        $poet_stmt->bind_param('i', $movement['id']);
        $poet_stmt->execute();
        $bm_ctbl000_poet = $poet_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        set_query_var('movement_data', $movement);
        set_query_var('subbm_ctbl000_poet_movements_data', $subbm_ctbl000_poet_movements);
        set_query_var('bm_ctbl000_poet_data', $bm_ctbl000_poet);
        
        locate_template('templates/movement-archive.php', true);
    }
    
    private function show_century($century) {
        $mysqli = $this->hierarchy_api->get_mysqli();
        
        // Получаем направления века
        $movement_stmt = $mysqli->prepare("
            SELECT * FROM bm_ctbl000_poet_movements 
            WHERE century_id = ? AND level = 2
            ORDER BY display_order, name
        ");
        $movement_stmt->bind_param('i', $century['id']);
        $movement_stmt->execute();
        $bm_ctbl000_poet_movements = $movement_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        set_query_var('century_data', $century);
        set_query_var('bm_ctbl000_poet_movements_data', $bm_ctbl000_poet_movements);
        
        locate_template('templates/century-archive.php', true);
    }
    
    public function enqueue_scripts() {
        if (get_query_var('poet_full_path')) {
            wp_enqueue_style('poet-hierarchy', get_template_directory_uri() . '/css/poet-hierarchy.css');
        }
    }
}

// Инициализация
new EnhancedPoetRouter();