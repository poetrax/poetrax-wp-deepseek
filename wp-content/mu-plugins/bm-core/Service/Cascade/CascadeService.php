<?php
namespace BM\Core\Service\Cascade;

use BM\Core\Database\Connection;

class UniversalCascadeSystem {
    private $pdo;
    
    private $cascade_config = [
        'poet_poem' => [
            'name' => 'Поэт → Стихотворение',
            'view' => 'poet_poem_view_for_cascade',
            'category_field' => 'category', 
            'name_field' => 'poem_name',
            'id_field' => 'poem_id'
        ],
        'suno_style' => [
            'name' => 'Suno Style Categories',
            'view' => 'bm_ctbl000_music_suno_style',
            'category_field' => 'category',
            'name_field' => 'name',
            'id_field' => 'id'
        ]
    ];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->register_ajax_handlers();
    }

    private function register_ajax_handlers() {
    }

   public function handle_get_cascade_data() {
    // МЯГКАЯ очистка - только если есть лишние буферы
    if (ob_get_length() > 0) {
        ob_clean(); // очищает текущий буфер, но не закрывает его
    }
    
    try {
        $cascade_type = htmlspecialchars($_POST['cascade_type'] ?? '');
        
        if (empty($cascade_type) || !isset($this->cascade_config[$cascade_type])) {
            throw new Exception('Invalid cascade type');
        }

        $config = $this->cascade_config[$cascade_type];
        $data = $this->get_cascade_data($config);

       return ['success' => true, 'data' => $data];

    } catch (Exception $exception) {
        throw new \Exception();
    }
    
    wp_die();
}

    /**
     * Универсальный метод для обоих каскадов
     */
    private function get_cascade_data($config) {
        $where_clause = ($config['view'] === 'bm_ctbl000_music_suno_style') ? ' WHERE is_active = 1' : '';
        
        $query = "
            SELECT 
                {$config['category_field']} as category,
                {$config['id_field']} as id,
                {$config['name_field']} as name
            FROM {$config['view']} 
            {$where_clause}
            ORDER BY {$config['category_field']}, {$config['name_field']}
        ";
        try {
        $stmt = $this->pdo->query($query);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Группируем по категориям
        $categories = [];
        foreach ($rows as $row) {
            $category = $row['category'];
            
            if (!isset($categories[$category])) {
                $categories[$category] = [
                    'id' => $category,
                    'name' => $category,
                    'children' => []
                ];
            }
            
            $child_data = [
                'id' => $row['id'],
                'name' => $row['name']
            ];
            
            $categories[$category]['children'][] = $child_data;
        }

        return array_values($categories);
        }
        catch(Exception $e)
        {
            throw new \Exception();
        }
    }

    /**
     * Генерация каскадных селектов
     */
    public function generate_cascade_select($cascade_type, $options = []) {
        if (!isset($this->cascade_config[$cascade_type])) {
            return '<p>Ошибка: неверный тип каскада</p>';
        }

        $config = $this->cascade_config[$cascade_type];
        $default_options = [
            'parent_label' => ''
            ,'child_label' => ''
            ,'parent_id' => $cascade_type . '_parent'
            ,'child_id' => $cascade_type . '_child'
            ,'container_class' => 'cascade-container'
        ];

        $options = array_merge($default_options, $options);
        $html = '<div data-cascade-type="' . esc_attr($cascade_type) . '" class="' . esc_attr($options['container_class']) . '">';
        // Родительский селект (категории)
        $html .= '<div class="one-half">';
        $html .= '<select class="cascade-parent" id="' . esc_attr($options['parent_id']) . '" 
                         name="' . esc_attr($options['parent_id']) . '">';
        $html .= '</select>';
        $html .= '</div>';
       
        // Дочерний селект (элементы)
        $html .= '<div class="one-half last">';
        $html .= '<select class="cascade-child" id="' . esc_attr($options['child_id']) . '" 
                        name="' . esc_attr($options['child_id']) . '" disabled>';
        $html .= '</select>';
        $html .= '</div>';
        $html .= '</div>';
       
        return $html;
    }

    // Шорткоды
    function cascade_poet_poem_shortcode($atts) {
        global $universal_cascade_system;
        $atts = shortcode_atts([
            'parent_label' => '',
            'child_label' => ''
        ], $atts);
        return $universal_cascade_system->generate_cascade_select('poet_poem', $atts);
    }

    function cascade_suno_style_shortcode($atts) {
        global $universal_cascade_system;
        $atts = shortcode_atts([
            'parent_label' => '',
            'child_label' => ''
        ], $atts);
        return $universal_cascade_system->generate_cascade_select('suno_style', $atts);
    }

    public function register_shortcodes() {
   
          add_shortcode('cascade_poet_poem', [$this, 'cascade_poet_poem_shortcode']);
          add_shortcode('cascade_suno_style', [$this, 'cascade_suno_style_shortcode']);
    }
}

