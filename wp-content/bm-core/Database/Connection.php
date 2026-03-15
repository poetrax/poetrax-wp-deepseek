<?php
namespace BM\Database;

class Connection {
    private static $wpdb = null;
    private static $tables = [];
    
    public static function init() {
        global $wpdb;
        self::$wpdb = $wpdb;
        
        self::$tables = [
            'track' => 'bm_ctbl000_track',
            'user' => 'bm_ctbl000_user',
            'poem' => 'bm_ctbl000_poem',
            'poet' => 'bm_ctbl000_poet',
            'doc' => 'bm_ctbl000_docs',
            'img' => 'bm_ctbl000_img',
            'img_theme' => 'bm_ctbl000_img_theme',
            'interaction' => 'bm_ctbl000_interaction',
            'mood' => 'bm_ctbl000_mood',
            'music_direction' => 'bm_ctbl000_music_direction',
            'music_detail' => 'bm_ctbl000_music_detail',
            'music_genre' => 'bm_ctbl000_music_genre',
            'music_instrument' => 'bm_ctbl000_music_instrument',
            'music_presentation' => 'bm_ctbl000_music_presentation',
            'music_style' => 'bm_ctbl000_music_style',
            'music_style_full' => 'bm_ctbl000_music_style_full',
            'music_suno_style' => 'bm_ctbl000_music_suno_style',
            'music_temp' => 'bm_ctbl000_music_temp',
            'music_voice_gender' => 'bm_ctbl000_music_voice_gender',
            'music_voice_group' => 'bm_ctbl000_music_voice_group',
            'payment' => 'bm_ctbl000_payment',
            'properties_cache' => 'bm_ctbl000_properties_cache',
            'theme' => 'bm_ctbl000_theme',
            'track_music_detail' => 'bm_ctbl000_track_music_detail',
            'track_self_text' => 'bm_ctbl000_track_self_text',
            'user_account' => 'bm_ctbl000_user_account',
            'user_session' => 'bm_ctbl000_user_session',
            'voice_character' => 'bm_ctbl000_voice_character',
            'voice_register' => 'bm_ctbl000_voice_register',
            'comments' => 'bm_ctbl000_comments',
        ];
    }

    // Получить объект для запросов
    public static function get_db() {
        return self::$wpdb;
    }

    public static function table($key) {
        if (!isset(self::$tables[$key])) {
            throw new \Exception("Table {$key} not defined");
        }
        return self::$tables[$key];
    }
    
    public static function query($sql, $params = []) {
        global $wpdb;
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        return $wpdb->get_results($sql);
    }
    
    public static function row($sql, $params = []) {
        global $wpdb;
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        return $wpdb->get_row($sql);
    }
    
    public static function var($sql, $params = []) {
        global $wpdb;
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        return $wpdb->get_var($sql);
    }
   
    public static function insert($table, $data) {
        global $wpdb;
        $wpdb->insert(self::table($table), $data);
        return $wpdb->insert_id;
    }
    
    public static function update($table, $data, $where) {
        global $wpdb;
        return $wpdb->update(self::table($table), $data, $where);
    }
    
    public static function delete($table, $where) {
        global $wpdb;
        return $wpdb->delete(self::table($table), $where);
    }
    
    //Экранирование SQL
    public static function escape($value) {
        global $wpdb;
        return $wpdb->_escape($value);
    }

    //Подготовка LIKE запроса
    public static function escape_like($value) {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }
}
