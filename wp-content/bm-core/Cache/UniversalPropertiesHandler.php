<?php
namespace BM\Cache;

class UniversalPropertiesHandler {
    private $pdo;
    private $config;
    
    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
        $this->init_config();
        $this->register_ajax_handlers();
    }
    
    private function init_config() {
        $this->config = [
            'instruments' => [
                'table' => 'bm_ctbl000_music_instrument',
                'columns' => 'id, name, suno_prompt',
                'where' => 'is_active = 1',
                'order' => 'name'
            ],
            'styles' => [
                'table' => 'bm_ctbl000_music_style',
                'columns' => 'id, name, suno_prompt', 
                'where' => 'is_active = 1',
                'order' => 'name'
            ],
            'voice_character' => [
                'table' => 'bm_ctbl000_voice_character',
                'columns' => 'id, name',
                'where' => 'is_active = 1',
                'order' => 'name'
            ],
            'track_theme' => [
                'table' => 'bm_ctbl000_theme',
                'columns' => 'id, name',
                'where' => 'is_active = 1',
                'order' => 'name'
            ],
            'genre' => [
                'table' => 'bm_ctbl000_music_genre',
                'columns' => 'id, name',
                'where' => 'is_active = 1 AND is_approved = 1',
                'order' => 'name'
            ],
            'voice_register' => [
                'table' => 'bm_ctbl000_voice_register',
                'columns' => 'id, name',
                'where' => 'is_active = 1',
                'order' => 'name'
            ],
            'mood' => [
                'table' => 'bm_ctbl000_mood',
                'columns' => 'id, name',
                'where' => 'is_active = 1',
                'order' => 'name'
            ],
            'poem' => [
                'table' => 'bm_ctbl000_poem',
                'columns' => 'id, name',
                'where' => 'is_active = 1 AND is_approved = 1',
                'order' => 'name'
            ],
            'poet' => [
                'table' => 'bm_ctbl000_poet',
                'columns' => 'id, name',
                'where' => 'is_active = 1 AND is_approved = 1',
                'order' => 'name'
            ],
            'music_style' => [
                'table' => 'bm_ctbl000_music_style',
                'columns' => 'id, name',
                'where' => 'is_active = 1 AND is_main = 1 AND is_approved = 1',
                'order' => 'name'
            ],
            'temp' => [
                'table' => 'bm_ctbl000_music_temp',
                'columns' => 'id, name, suno_prompt',
                'where' => 'is_active = 1',
                'order' => 'name'
            ],
            'presentation' => [
                'table' => 'bm_ctbl000_music_presentation',
                'columns' => 'id, name, suno_prompt',
                'where' => 'is_active = 1',
                'order' => 'name'
            ]
        ];
    }
    
    private function register_ajax_handlers() {
        add_action('wp_ajax_get_properties', [$this, 'handle_get_properties']);
        add_action('wp_ajax_nopriv_get_properties', [$this, 'handle_get_properties']);
    }
    
    public function handle_get_properties() {
        try {
            // Проверка nonce для безопасности
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'properties_nonce')) {
                throw new Exception('Security verification failed');
            }
            
            $type = sanitize_text_field($_POST['property_type'] ?? '');
            
            if (empty($type) || !isset($this->config[$type])) {
                throw new Exception('Invalid property type');
            }
            
            $config = $this->config[$type];
            $query = $this->build_query($config);
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            
            $properties = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Добавляем мета-информацию о типе
            $response = [
                'type' => $type,
                'data' => $properties,
                'total' => count($properties),
                'timestamp' => current_time('mysql')
            ];
            
            wp_send_json_success($response);
            
        } catch (PDOException $exception) {
            $this->send_error('Database error: ' . $exception->getMessage());
        } catch (Exception $exception) {
            $this->send_error($exception->getMessage());
        }
        
        wp_die();
    }
    
    private function build_query($config) {
        return sprintf(
            "SELECT %s FROM %s WHERE %s ORDER BY %s",
            $config['columns'],
            $config['table'], 
            $config['where'],
            $config['order']
        );
    }
    
    private function send_error($message) {
        error_log('Properties Handler Error: ' . $message);
        wp_send_json_error($message);
    }
    
    /**
     * Получить конфигурацию для клиентской части
     */
    public function get_client_config() {
        return array_map(function($config, $type) {
            return [
                'type' => $type,
                'table' => $config['table'],
                'has_suno_prompt' => strpos($config['columns'], 'suno_prompt') !== false
            ];
        }, $this->config, array_keys($this->config));
    }
}
