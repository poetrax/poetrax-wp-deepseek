<?php
namespace BM\Cache;

class PropertiesConfig {
    private array $config;
    
    public function __construct() {
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
    
    public function get(string $type): ?array {
        return $this->config[$type] ?? null;
    }
    
    public function has(string $type): bool {
        return isset($this->config[$type]);
    }
    
    public function getAll(): array {
        return $this->config;
    }
    
    public function getTypes(): array {
        return array_keys($this->config);
    }
    
    public function getClientConfig(): array {
        return array_map(function($config, $type) {
            return [
                'type' => $type,
                'table' => $config['table'],
                'has_suno_prompt' => strpos($config['columns'], 'suno_prompt') !== false
            ];
        }, $this->config, array_keys($this->config));
    }
}