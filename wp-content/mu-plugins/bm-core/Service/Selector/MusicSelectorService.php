<?php
/* Unified music selectors system */

/* Чтобы добавить новый селектор дописать конфигурацию:
'new_selector' => [
    'table' => 'bm_ctbl000_new_table',
    'columns' => 'id, name',
    'where' => 'is_active = 1',
    'builder' => 'create_select' // или 'build_ta'
]
*/
namespace BM\Core\Service\Selector;

use BM\Core\Database\Connection;


class MusicSelectorsSystem {
    private $pdo;
    
    // Configuration map for all selectors
    private $selectors_config = [
        'instruments' => [
            'table' => 'bm_ctbl000_music_instrument',
            'columns' => 'id, name, suno_prompt',
            'where' => 'is_active = 1',
            'type' => 'instrument',
            'builder' => 'build_ta'
        ],
        'styles' => [
            'table' => 'bm_ctbl000_music_style', 
            'columns' => 'id, name, suno_prompt',
            'where' => 'is_active = 1',
            'type' => 'style',
            'builder' => 'build_ta'
        ],
        'voice_character' => [
            'table' => 'bm_ctbl000_voice_character',
            'columns' => 'id, name, suno_prompt',
            'where' => 'is_active = 1',
            'builder' => 'create_select'
        ],
        'track_theme' => [
            'table' => 'bm_ctbl000_theme',
            'columns' => 'id, name',
            'where' => 'is_active = 1', 
            'builder' => 'create_select'
        ],
        'genre_select' => [
            'table' => 'bm_ctbl000_music_genre',
            'columns' => 'id, name, suno_prompt',
            'where' => 'is_active = 1 AND is_approved = 1',
            'builder' => 'create_select'
        ],
        'voice_register' => [
            'table' => 'bm_ctbl000_voice_register',
            'columns' => 'id, name',
            'where' => 'is_active = 1',
            'builder' => 'create_select'
        ],
        'track_mood' => [
            'table' => 'bm_ctbl000_mood',
            'columns' => 'id, name, suno_prompt',
            'where' => 'is_active = 1',
            'builder' => 'create_select'
        ],
        'poem_select' => [
            'table' => 'bm_ctbl000_poem',
            'columns' => 'id, name',
            'where' => 'is_active = 1 AND is_approved = 1',
            'builder' => 'create_select'
        ],
        'poet_select' => [
            'table' => 'bm_ctbl000_poet',
            'columns' => 'id, name',
            'where' => 'is_active = 1 AND is_approved = 1',
            'builder' => 'create_select'
        ],
        'style_select' => [
            'table' => 'bm_ctbl000_music_style',
            'columns' => 'id, name, suno_prompt',
            'where' => 'is_active = 1 AND is_main = 1 AND is_approved = 1',
            'builder' => 'create_select'
        ],
        'temp_select' => [
            'table' => 'bm_ctbl000_music_temp',
            'columns' => 'id, name, suno_prompt',
            'where' => 'is_active = 1',
            'builder' => 'create_select'
        ],
        'presentation_select' => [
            'table' => 'bm_ctbl000_music_presentation',
            'columns' => 'id, name, suno_prompt',
            'where' => 'is_active = 1',
            'builder' => 'create_select'
        ],
        'voice_group' => [
            'table' => 'bm_ctbl000_music_voice_group',
            'columns' => 'id, name',
            'where' => 'is_active = 1',
            'builder' => 'create_select' 
        ],
        'voice_gender' => [
            'table' => 'bm_ctbl000_music_voice_gender',
            'columns' => 'id, name',
            'where' => 'is_active = 1',
            'builder' => 'create_select' 
        ]
    ];
   
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Main method to generate selector by type
     */
    public function generate_selector($type) {
        try {
            if (!isset($this->selectors_config[$type])) {
                throw new InvalidArgumentException("Unknown selector type: {$type}");
            }

            $config = $this->selectors_config[$type];
            $query = $this->build_query($config);
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Call appropriate builder function
            return call_user_func($config['builder'], $items, $config['type'] ?? $type);

        } catch (PDOException $exception) {
            error_log("Database error in {$type}: " . $exception->getMessage());
            return $this->get_error_output($config['builder'] ?? 'create_select');
        } catch (Exception $exception) {
            error_log("Error in {$type}: " . $exception->getMessage());
            return $this->get_error_output($config['builder'] ?? 'create_select');
        }
    }

    /**
     * Build SQL query from configuration
     */
    private function build_query($config) {
        return "SELECT {$config['columns']} FROM {$config['table']} WHERE {$config['where']} ORDER BY name";
    }

    /**
     * Get appropriate error output based on builder type
     */
    private function get_error_output($builder_type) {
        if ($builder_type === 'build_ta') {
            return '<div class="properties-container"><p>Ошибка загрузки данных</p></div>';
        }
        return '<select><option>Ошибка загрузки данных</option></select>';
    }

    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        // TA-style selectors (checkbox grids)
        add_shortcode('ta_instruments', [$this, 'instruments_shortcode']);
        add_shortcode('ta_styles', [$this, 'styles_shortcode']);
        
        // Dropdown selectors
        add_shortcode('custom_voice_character', [$this, 'voice_character_shortcode']);
        add_shortcode('custom_track_theme', [$this, 'track_theme_shortcode']);
        add_shortcode('custom_genre_select', [$this, 'genre_select_shortcode']);
        add_shortcode('custom_voice_register', [$this, 'voice_register_shortcode']);
        add_shortcode('custom_track_mood', [$this, 'track_mood_shortcode']);
        add_shortcode('custom_poem', [$this, 'poem_shortcode']);
        add_shortcode('custom_poet', [$this, 'poet_shortcode']);
        add_shortcode('custom_style_select', [$this, 'style_select_shortcode']);
        add_shortcode('custom_temp', [$this, 'temp_select_shortcode']);
        add_shortcode('custom_presentation', [$this, 'presentation_select_shortcode']);
        add_shortcode('custom_voice_group', [$this, 'voice_group_shortcode']);
        add_shortcode('custom_voice_gender', [$this, 'voice_gender_shortcode']);
    }

    /**
     * Individual shortcode methods
     */
    public function instruments_shortcode() { return $this->generate_selector('instruments'); }
    public function styles_shortcode() { return $this->generate_selector('styles'); }
    public function voice_character_shortcode() { return $this->generate_selector('voice_character'); }
    public function track_theme_shortcode() { return $this->generate_selector('track_theme'); }
    public function genre_select_shortcode() { return $this->generate_selector('genre_select'); }
    public function voice_register_shortcode() { return $this->generate_selector('voice_register'); }
    public function track_mood_shortcode() { return $this->generate_selector('track_mood'); }
    public function poem_shortcode() { return $this->generate_selector('poem_select'); }
    public function poet_shortcode() { return $this->generate_selector('poet_select'); }
    public function style_select_shortcode() { return $this->generate_selector('style_select'); }
    public function temp_select_shortcode() { return $this->generate_selector('temp_select'); }
    public function presentation_select_shortcode() { return $this->generate_selector('presentation_select'); }
    public function voice_group_shortcode() { return $this->generate_selector('voice_group'); }
    public function voice_gender_shortcode() { return $this->generate_selector('voice_gender'); }
}

/**
 * Builder functions (kept separate for compatibility)
 */
function build_ta($items, $type, $name_group) {
    if (empty($items)) {
        return '<div class="properties-container"><p>No items found</p></div>';
    }
    
    $slctr = '<div class="properties-container">';
    $slctr .= '<div class="properties-textarea" contenteditable="false">';
    $slctr .= '<div onclick="check_grid_chb_clicked(this);" id="' . esc_attr($type) . 'sGrid" class="properties-grid">';
    
    foreach ($items as $item) {
        $item_id = (int)$item['id'];
        $item_name = esc_html($item['name']);
        
        $slctr .= '<div class="property-item" data-id="' . $item_id . '">';
        $slctr .= '<input type="checkbox" name="'.esc_attr($name_group).'" id="' . esc_attr($type) . '-' . $item_id . '">'; 
        $slctr .= '<label for="' . esc_attr($type) . '-' . $item_id . '">';
        $slctr .= $item_name;
        $slctr .= '</label>';
        $slctr .= '</div>';
    }
    
    $slctr .= '</div></div></div>';
    
    return $slctr;
}

function create_select($items, $name) {
    if (empty($items)) {
        return '<select><option>Нет данных</option></select>';
    }

    $output = '<select name="' . esc_attr($name) . '">';
       
    foreach ($items as $row) {
        $output .= '<option value="' . esc_attr($row['id']) . '">' . esc_html($row['name']) . '</option>';
    }
    $output .= '</select>';
    
    return $output;
}

function generate_ajax_selector($type, $options = []) {
    $default_options = [
        'container_class' => 'ajax-properties-container',
        'loading_text' => 'Загрузка...',
        'empty_text' => 'Нет данных'
    ];
    
    $options = array_merge($default_options, $options);
    
    $html = '<div class="' . esc_attr($options['container_class']) . '" data-property-type="' . esc_attr($type) . '">';
    $html .= '<div class="properties-loading">' . esc_html($options['loading_text']) . '</div>';
    $html .= '<div class="properties-content" style="display: none;"></div>';
    $html .= '<div class="properties-empty" style="display: none;">' . esc_html($options['empty_text']) . '</div>';
    $html .= '</div>';
    
    return $html;
}