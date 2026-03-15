<?php
namespace BM\Core;

use BM\Repositories\TrackRepository;
use BM\Repositories\PoemRepository;
use BM\Repositories\PoetRepository;
use BM\Services\TrackService;
use BM\Services\PlayerService;
use BM\Services\StatsService;
use BM\Services\SearchService;

class Container {
    
    private static $instances = [];
    
    /**
     * Получить сервис (Singleton)
     */
    public static function get($name) {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = self::create($name);
        }
        
        return self::$instances[$name];
    }
    
    /**
     * Создать сервис
     */
    private static function create($name) {
        switch ($name) {
            case 'track_repository':
                return new TrackRepository();
                
            case 'poem_repository':
                return new PoemRepository();
                
            case 'poet_repository':
                return new PoetRepository();
                
            case 'track_service':
                return new TrackService();
                
            case 'stats_service':
                return new StatsService();
                
            case 'search_service':
                return new SearchService();
                
            default:
                throw new \Exception("Service not found: {$name}");
        }
    }
    
    /**
     * Рендеринг карточки любого типа
     */
    public static function renderCard($item) {
        if (property_exists($item, 'track_name')) {
            return self::renderTrackCard($item);
        } elseif (property_exists($item, 'poem_text')) {
            return self::renderPoemCard($item);
        } elseif (property_exists($item, 'last_name')) {
            return self::renderPoetCard($item);
        }
        
        return '';
    }
    
    private static function renderTrackCard($track) {
        ob_start();
        include BM_CORE_PATH . 'Templates/track-card.php';
        return ob_get_clean();
    }
    
    private static function renderPoemCard($poem) {
        ob_start();
        include BM_CORE_PATH . 'Templates/poem-card.php';
        return ob_get_clean();
    }
    
    private static function renderPoetCard($poet) {
        ob_start();
        include BM_CORE_PATH . 'Templates/poet-card.php';
        return ob_get_clean();
    }
}