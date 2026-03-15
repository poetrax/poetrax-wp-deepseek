<?php
/**
 * Полное замещение логики для poet URL
 */

class PoetURLHandler {
    
    private $hierarchy;
    
    public function __construct() {
        $this->hierarchy = HierarchyManager::getInstance();
        
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_requests']);
        add_filter('wp_title', [$this, 'set_poet_title'], 10, 2);
        add_filter('document_title_parts', [$this, 'set_document_title']);
    }
    
    /**
     * Добавляем правила перезаписи
     */
    public function add_rewrite_rules() {
        // Основное правило для поэтов
        add_rewrite_rule(
            '^poet/([^/]+)/?$',
            'index.php?poet_slug=$matches[1]&poet_page=1',
            'top'
        );
        
        // Для иерархических URL (век/направление/поэт)
        add_rewrite_rule(
            '^poet/([^/]+)/([^/]+)/([^/]+)/?$',
            'index.php?poet_century=$matches[1]&poet_movement=$matches[2]&poet_slug=$matches[3]',
            'top'
        );
        
        add_rewrite_rule(
            '^poet/([^/]+)/([^/]+)/?$',
            'index.php?poet_century=$matches[1]&poet_movement=$matches[2]',
            'top'
        );
        
        add_rewrite_rule(
            '^poet/([^/]+)/?$',
            'index.php?poet_century=$matches[1]',
            'top'
        );
        
        // Редирект со старых URL на новые
        add_rewrite_rule(
            '^category/([^/]+)/?$',
            'index.php?old_poet_slug=$matches[1]',
            'top'
        );
    }
    
    /**
     * Добавляем переменные запроса
     */
    public function add_query_vars($vars) {
        $vars[] = 'poet_slug';
        $vars[] = 'poet_century';
        $vars[] = 'poet_movement';
        $vars[] = 'poet_page';
        $vars[] = 'old_poet_slug';
        return $vars;
    }
    
    /**
     * Обработка запросов
     */
    public function handle_requests() {
        global $wp_query;
        
        // Обработка старых URL (редирект)
        $old_slug = get_query_var('old_poet_slug');
        if ($old_slug) {
            $this->handle_old_url($old_slug);
            return;
        }
        
        // Обработка новых URL
        $poet_slug = get_query_var('poet_slug');
        $century_slug = get_query_var('poet_century');
        $movement_slug = get_query_var('poet_movement');
        
        if ($poet_slug) {
            $this->handle_poet($poet_slug, $century_slug, $movement_slug);
        } elseif ($movement_slug) {
            $this->handle_movement($movement_slug, $century_slug);
        } elseif ($century_slug) {
            $this->handle_century($century_slug);
        }
    }
    
    /**
     * Обработка страницы поэта с вашей бизнес-логикой
     */
    private function handle_poet($slug, $century_slug = null, $movement_slug = null) {
        // Получаем данные из вашей таблицы
        $poet = $this->hierarchy->poet()->getFull($slug);
        
        if (!$poet) {
            global $wp_query;
            $wp_query->set_404();
            return;
        }
        
        // Проверяем соответствие URL иерархии
        if ($century_slug || $movement_slug) {
            $expected_url = $this->hierarchy->poet()->getUrl($poet);
            $current_url = $_SERVER['REQUEST_URI'];
            
            // Если URL не соответствует иерархии - редирект на правильный
            if (parse_url($expected_url, PHP_URL_PATH) !== parse_url($current_url, PHP_URL_PATH)) {
                wp_redirect($expected_url, 301);
                exit;
            }
        }
        
        // Получаем произведения поэта
        $works = $this->hierarchy->poet()->getWorks($poet['id']);
        
        // Хлебные крошки
        $breadcrumbs = $this->hierarchy->poet()->getBreadcrumbs($poet);
        
        // Передаем данные в шаблон
        set_query_var('poet_data', $poet);
        set_query_var('works_data', $works);
        set_query_var('breadcrumbs', $breadcrumbs);
        
        // Используем ваш кастомный шаблон
        $template = locate_template('templates/poet-single.php');
        if (empty($template)) {
            $template = __DIR__ . '/templates/poet-single.php';
        }
        
        load_template($template, false);
        exit;
    }
    
    /**
     * Обработка старого URL
     */
    private function handle_old_url($slug) {
        // Проверяем, есть ли такой поэт в вашей таблице
        $poet = $this->hierarchy->poet()->getBySlug($slug);
        
        if ($poet) {
            // Редирект на новый URL с вашей бизнес-логикой
            $new_url = $this->hierarchy->poet()->getUrl($poet);
            wp_redirect($new_url, 301);
            exit;
        }
        
        // Если нет - пробуем найти в WordPress
        $term = get_term_by('slug', $slug, 'category');
        if ($term) {
            wp_redirect('/poet/' . $slug . '/', 302);
            exit;
        }
        
        global $wp_query;
        $wp_query->set_404();
    }
    
    /**
     * Обработка страницы направления
     */
    private function handle_movement($movement_slug, $century_slug) {
        $movement = $this->hierarchy->movement()->getBySlug($movement_slug);
        
        if (!$movement) {
            global $wp_query;
            $wp_query->set_404();
            return;
        }
        
        // Получаем поэтов направления
        $poets = $this->hierarchy->poet()->search('', ['movement_id' => $movement['id']]);
        
        set_query_var('movement_data', $movement);
        set_query_var('poets_data', $poets);
        
        $template = locate_template('templates/movement-archive.php');
        load_template($template, false);
        exit;
    }
    
    /**
     * Обработка страницы века
     */
    private function handle_century($century_slug) {
        $century = $this->hierarchy->century()->getBySlug($century_slug);
        
        if (!$century) {
            global $wp_query;
            $wp_query->set_404();
            return;
        }
        
        // Получаем направления века
        $movements = $this->hierarchy->century()->getMovements($century['id']);
        // Получаем поэтов века
        $poets = $this->hierarchy->poet()->search('', ['century_id' => $century['id']]);
        
        set_query_var('century_data', $century);
        set_query_var('movements_data', $movements);
        set_query_var('poets_data', $poets);
        
        $template = locate_template('templates/century-archive.php');
        load_template($template, false);
        exit;
    }
    
    /**
     * Устанавливаем заголовок для страницы поэта
     */
    public function set_poet_title($title, $sep) {
        $poet_slug = get_query_var('poet_slug');
        if ($poet_slug) {
            $poet = $this->hierarchy->poet()->getBySlug($poet_slug);
            if ($poet) {
                return $poet['name'] . ' ' . $sep . ' ' . get_bloginfo('name');
            }
        }
        return $title;
    }
    
    /**
     * Устанавливаем части заголовка для SEO
     */
    public function set_document_title($title_parts) {
        $poet_slug = get_query_var('poet_slug');
        if ($poet_slug) {
            $poet = $this->hierarchy->poet()->getBySlug($poet_slug);
            if ($poet) {
                $title_parts['title'] = $poet['name'];
                
                if ($poet['birth_year'] || $poet['death_year']) {
                    $years = [];
                    if ($poet['birth_year']) $years[] = $poet['birth_year'];
                    if ($poet['death_year']) $years[] = $poet['death_year'];
                    $title_parts['title'] .= ' (' . implode('-', $years) . ')';
                }
            }
        }
        return $title_parts;
    }
}

// Инициализируем обработчик
new PoetURLHandler();