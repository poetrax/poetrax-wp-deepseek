<?php
namespace BM\Admin;

use BM\Core\Container;
use BM\Services\PlayerService;
use BM\Repositories\TrackRepository;
use BM\Repositories\PoetRepository;
use BM\Repositories\PoemRepository;
use BM\Core\Database\TableMapper;

class TrackEditor {
    
    private $track_repo;
    private $poet_repo;
    private $poem_repo;
    private $track_id;
    private $track_data;
    
    public function __construct() {
        $this->track_repo = Container::get('track_repository');
        $this->poet_repo = Container::get('poet_repository');
        $this->poem_repo = Container::get('poem_repository');
    }
    
    /**
     * Инициализация редактора
     */
    public function init() {
        // Регистрация страницы в админке Конфликт с bm-track-editor вызывается дважды здесь не нужно (?) 
        //add_action('admin_menu', [$this, 'addAdminPages']);
        
        // Регистрация AJAX обработчиков
        add_action('wp_ajax_bm_save_track', [$this, 'ajaxSaveTrack']);
        add_action('wp_ajax_bm_upload_audio', [$this, 'ajaxUploadAudio']);
        add_action('wp_ajax_bm_search_poems', [$this, 'ajaxSearchPoems']);
        add_action('wp_ajax_bm_get_track_data', [$this, 'ajaxGetTrackData']);
        
        // Подключение стилей и скриптов
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }
    
    /**
     * Добавление страниц в админку
     */
    public function addAdminPages() {
        // Главная страница со списком треков
        add_menu_page(
            'Управление треками',
            'Треки',
            'edit_posts',
            'bm-tracks',
            [$this, 'renderTrackList'],
            'dashicons-format-audio',
            30
        );
        
        // Страница добавления/редактирования
        add_submenu_page(
            'bm-tracks',
            'Редактор трека',
            'Новый трек',
            'edit_posts',
            'bm-track-editor',
            [$this, 'renderTrackEditor']
        );
    }
    
    /**
     * Рендеринг списка треков
     */
    public function renderTrackList() {
        $tracks = $this->track_repo->getAll(50);
        include BM_CORE_PATH . 'Templates/admin/track-list.php';
    }
    
    /**
     * Рендеринг редактора трека
     */
    public function renderTrackEditor() {
        $this->track_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($this->track_id) {
            $this->track_data = $this->track_repo->find($this->track_id);
            if (!$this->track_data) {
                wp_die('Трек не найден');
            }
        }
        
        // Получаем все справочники для выпадающих списков
        $master_data = $this->getMasterData();
        
        include BM_CORE_PATH . 'Templates/admin/track-editor.php';
    }
    
    /**
     * Получение всех справочников
     */
    private function getMasterData() {
      
        
        return [
            'poets' => $this->poet_repo->getAll(),
            'moods' => $wpdb->get_results("SELECT * FROM " . TableMapper::getInstance()->get('mood') . " WHERE is_active = 1"),
            'themes' => $wpdb->get_results("SELECT * FROM " . TableMapper::getInstance()->get('theme') . " WHERE is_active = 1"),
            'tempos' => $wpdb->get_results("SELECT * FROM " . TableMapper::getInstance()->get('music_temp') . " WHERE is_active = 1"),
            'presentations' => $wpdb->get_results("SELECT * FROM " . TableMapper::getInstance()->get('music_presentation') . " WHERE is_active = 1"),
            'genres' => $wpdb->get_results("SELECT * FROM " . TableMapper::getInstance()->get('music_genre') . " WHERE is_active = 1"),
            'instruments' => $wpdb->get_results("SELECT * FROM " . TableMapper::getInstance()->get('music_instrument') . " WHERE is_active = 1"),
            'voice_genders' => $wpdb->get_results("SELECT * FROM " . TableMapper::getInstance()->get('music_voice_gender') . " WHERE is_active = 1"),
            'voice_groups' => $wpdb->get_results("SELECT * FROM " . TableMapper::getInstance()->get('music_voice_group') . " WHERE is_active = 1"),
            'voice_characters' => $wpdb->get_results("SELECT * FROM " . TableMapper::getInstance()->get('voice_character') . " WHERE is_active = 1"),
            'voice_registers' => $wpdb->get_results("SELECT * FROM " . TableMapper::getInstance()->get('voice_register') . " WHERE is_active = 1"),
        ];
    }
    
    /**
     * AJAX: Сохранение трека
     */
    public function ajaxSaveTrack() {
        check_ajax_referer('bm_track_editor', 'nonce');
        
        $data = json_decode(stripslashes($_POST['data']), true);
        
        try {
            if (!empty($data['id'])) {
                // Обновление существующего
                $result = $this->track_repo->update($data['id'], $data);
                $track_id = $data['id'];
            } else {
                // Создание нового
                $track_id = $this->track_repo->create($data);
            }
            
            // Сохраняем музыкальные детали
            if (!empty($data['music_details'])) {
                $this->saveMusicDetails($track_id, $data['music_details']);
            }
            
            wp_send_json_success([
                'id' => $track_id,
                'message' => 'Трек сохранен'
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Загрузка аудиофайла
     */
    public function ajaxUploadAudio() {
        check_ajax_referer('bm_track_editor', 'nonce');
        
        if (!isset($_FILES['audio_file'])) {
            wp_send_json_error(['message' => 'Файл не загружен']);
        }
        
        $file = $_FILES['audio_file'];
        
        // Проверка типа файла
        $allowed_types = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg'];
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(['message' => 'Неверный формат файла. Разрешены: MP3, WAV, OGG']);
        }
        
        // Загрузка в медиабиблиотеку WordPress
        $attachment_id = media_handle_upload('audio_file', 0);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => $attachment_id->get_error_message()]);
        }
        
        $attachment = wp_get_attachment_metadata($attachment_id);
        
        wp_send_json_success([
            'file_id' => $attachment_id,
            'file_url' => wp_get_attachment_url($attachment_id),
            'file_path' => get_attached_file($attachment_id),
            'duration' => $attachment['length'] ?? 0,
            'file_size' => filesize(get_attached_file($attachment_id))
        ]);
    }
    
    /**
     * AJAX: Поиск стихов
     */
    public function ajaxSearchPoems() {
        $query = sanitize_text_field($_POST['query']);
        
        $poems = $this->poem_repo->search($query, 20);
        
        $results = [];
        foreach ($poems as $poem) {
            $results[] = [
                'id' => $poem->id,
                'name' => $poem->name,
                'poet_name' => $poem->poet->short_name ?? '',
                'text' => substr(strip_tags($poem->poem_text), 0, 200) . '...'
            ];
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * AJAX: Получение данных трека
     */
    public function ajaxGetTrackData() {
        $track_id = (int)$_POST['track_id'];
        
        $track = $this->track_repo->find($track_id);
        
        if (!$track) {
            wp_send_json_error(['message' => 'Трек не найден']);
        }
        
        // Получаем музыкальные детали
      
        $music_details = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . TableMapper::getInstance()->get('track_music_detail') . " WHERE track_id = %d",
            $track_id
        ));
        
        wp_send_json_success([
            'track' => $track,
            'music_details' => $music_details
        ]);
    }
    
    /**
     * Сохранение музыкальных деталей
     */
    private function saveMusicDetails($track_id, $details) {
      
        
        $details['track_id'] = $track_id;
        
        // Проверяем, есть ли уже запись
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . TableMapper::getInstance()->get('track_music_detail') . " WHERE track_id = %d",
            $track_id
        ));
        
        if ($exists) {
            $wpdb->update(
                TableMapper::getInstance()->get('track_music_detail'),
                $details,
                ['track_id' => $track_id]
            );
        } else {
            $wpdb->insert(
                TableMapper::getInstance()->get('track_music_detail'),
                $details
            );
        }
    }
    
    /**
     * Подключение стилей и скриптов
     */
    public function enqueueAssets($hook) {
        if (strpos($hook, 'bm-track') === false) {
            return;
        }
        
        wp_enqueue_media(); // Для загрузчика файлов
        
        wp_enqueue_style(
            'bm-track-editor',
            BM_CORE_URL . 'Admin/assets/css/editor.css',
            [],
            '1.0.0'
        );
        
        wp_enqueue_script(
            'bm-track-editor',
            BM_CORE_URL . 'Admin/assets/js/editor.js',
            ['jquery', 'jquery-ui-sortable'],
            '1.0.0',
            true
        );
        
        wp_localize_script('bm-track-editor', 'bmEditor', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bm_track_editor'),
            'strings' => [
                'save_success' => 'Трек сохранен',
                'save_error' => 'Ошибка сохранения',
                'confirm_delete' => 'Удалить трек?'
            ]
        ]);
    }
}