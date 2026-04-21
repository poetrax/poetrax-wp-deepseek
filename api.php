<?php
//namespace BM\Core;
//use BM\Core\Controller\TrackController;
require_once __DIR__ . '/vendor/autoload.php';
ob_start();

// ============================================
// CORS
// ============================================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 86400");

// Обработка preflight-запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


$_ENV['DEEPSEEK_DB_HOST'] = 'poetrax_deepseek_mysql';
$_ENV['DEEPSEEK_DB_NAME'] = 'u3436142_poetrax_deepseek_db';
$_ENV['DEEPSEEK_DB_USER'] = 'u3436142_poetrax_deepseek_user';
$_ENV['DB_PASSWORD'] = 'CI57bdR7m6F9Xem7';

$dbConfig = [
    'host' => $_ENV['DEEPSEEK_DB_HOST'],
    'database' => $_ENV['DEEPSEEK_DB_NAME'],
    'username' => $_ENV['DEEPSEEK_DB_USER'],
    'password' => $_ENV['DB_PASSWORD'],
];

use BM\Core\Database\Connection;
use BM\Core\Database\Cache;
use BM\Core\Database\Loader;
use BM\Core\Router;
use BM\Core\Controller\TrackController;
use BM\Core\Controller\FilterController;
use BM\Core\Controller\RecommendationController;
use BM\Core\Cache\CacheManager;
use BM\Core\Config;

$config = Config::getInstance();
$connection = Connection::getInstance($dbConfig);
$cache = Cache::getInstance();
$cacheManager = new CacheManager($cache, $connection);
$loader = new Loader($connection, $cache, $config);
$warmupTtl = $config->get('cache.warmup_ttl', 86400);

if (!$cache->has('table:warmed_up')) {
    $loader->warmupCache();
    $cache->set('table:warmed_up', time(), $warmupTtl);
}

// Сессии (опционально)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ============================================
// РОУТЕР
// ============================================
$router = new Router();

// Треки
$router->get('/api/tracks', [TrackController::class, 'index']);
$router->get('/api/tracks/popular', [TrackController::class, 'popular']);
$router->get('/api/tracks/recent', [TrackController::class, 'recent']);
$router->get('/api/tracks/search', [TrackController::class, 'search']);
$router->get('/api/tracks/{id}', [TrackController::class, 'show']);
$router->post('/api/tracks', [TrackController::class, 'store']);
$router->post('/api/tracks/{id}/play', [TrackController::class, 'play']);
$router->post('/api/tracks/{id}/like', [TrackController::class, 'like']);
$router->delete('/api/tracks/{id}/like', [TrackController::class, 'unlike']);

// Фильтры
$router->post('/api/filter/tracks', [FilterController::class, 'filter']);
$router->post('/api/filter/poets', [FilterController::class, 'filter']);
$router->post('/api/filter/poems', [FilterController::class, 'filter']);
$router->post('/api/filter/users', [FilterController::class, 'filter']);
$router->get('/api/filter/{entity}/available', [FilterController::class, 'availableFilters']);
$router->post('/api/filter/poets/by-track-properties', [FilterController::class, 'filterPoetsByTrackProperties']);
$router->post('/api/filter/poems/by-track-properties', [FilterController::class, 'filterPoemsByTrackProperties']);
$router->post('/api/filter/users/by-track-properties', [FilterController::class, 'filterUsersByTrackProperties']);

// Рекомендации
$router->get('/api/recommendations/user', [RecommendationController::class, 'forUser']);
$router->get('/api/recommendations/track/{id}', [RecommendationController::class, 'similarToTrack']);
$router->get('/api/recommendations/popular', [RecommendationController::class, 'popular']);
$router->get('/api/recommendations/new', [RecommendationController::class, 'newReleases']);
$router->get('/api/recommendations/trending', [RecommendationController::class, 'trending']);
$router->get('/api/recommendations/poet/{id}', [RecommendationController::class, 'forPoet']);
$router->get('/api/recommendations/poem/{id}', [RecommendationController::class, 'forPoem']);

// Поэмы
$router->get('/api/poems', [PoemController::class, 'index']);
$router->get('/api/poems/{id}', [PoemController::class, 'show']);
$router->get('/api/poems/search', [PoemController::class, 'search']);
$router->get('/api/poems/by-poet/{poetId}', [PoemController::class, 'byPoet']);
$router->get('/api/poems/{id}/text', [PoemController::class, 'text']);

// Поэты (дополнительные маршруты)
$router->get('/api/poets/{id}/details', [PoetController::class, 'details']);
$router->get('/api/poets/stats', [PoetController::class, 'stats']);

// Блокировки
$router->get('/api/blocks/my', [BlockController::class, 'myBlocks']);
$router->get('/api/blocks/on-me', [BlockController::class, 'blocksOnMe']);
$router->get('/api/blocks/check', [BlockController::class, 'check']);
$router->post('/api/blocks', [BlockController::class, 'store']);
$router->delete('/api/blocks/{id}', [BlockController::class, 'delete']);

// Сервис доступа
$router->get('/api/services', [ServiceController::class, 'index']);
$router->get('/api/services/check', [ServiceController::class, 'check']);
$router->post('/api/services/{slug}/buy', [ServiceController::class, 'buy']);

// Сообщения
$router->get('/api/messages/inbox', [MessageController::class, 'inbox']);
$router->get('/api/messages/sent', [MessageController::class, 'sent']);
$router->get('/api/messages/unread/count', [MessageController::class, 'unreadCount']);
$router->get('/api/messages/{id}', [MessageController::class, 'show']);
$router->post('/api/messages', [MessageController::class, 'store']);
$router->delete('/api/messages/{id}', [MessageController::class, 'delete']);

// Мерч
$router->get('/api/products', [ProductController::class, 'index']);
$router->get('/api/products/{id}', [ProductController::class, 'show']);
$router->get('/api/products/slug/{slug}', [ProductController::class, 'showBySlug']);
$router->get('/api/cart', [CartController::class, 'index']);
$router->post('/api/cart/items', [CartController::class, 'addItem']);
$router->put('/api/cart/items/{id}', [CartController::class, 'updateItem']);
$router->delete('/api/cart/items/{id}', [CartController::class, 'removeItem']);
$router->delete('/api/cart', [CartController::class, 'clear']);
$router->get('/api/orders', [OrderController::class, 'index']);
$router->get('/api/orders/{id}', [OrderController::class, 'show']);
$router->post('/api/orders', [OrderController::class, 'store']);

// Корзина
$router->get('/api/cart', [CartController::class, 'index']);
$router->post('/api/cart/items', [CartController::class, 'addItem']);
$router->put('/api/cart/items/{id}', [CartController::class, 'updateItem']);
$router->delete('/api/cart/items/{id}', [CartController::class, 'removeItem']);
$router->delete('/api/cart', [CartController::class, 'clear']);

// Заказы
$router->get('/api/orders', [OrderController::class, 'index']);
$router->get('/api/orders/{id}', [OrderController::class, 'show']);
$router->post('/api/orders', [OrderController::class, 'store']);


// Блокировки
$router->get('/api/blocks/my', [BlockController::class, 'myBlocks']);
$router->get('/api/blocks/on-me', [BlockController::class, 'blocksOnMe']);
$router->get('/api/blocks/check', [BlockController::class, 'check']);
$router->post('/api/blocks', [BlockController::class, 'store']);
$router->delete('/api/blocks/{id}', [BlockController::class, 'delete']);

// Сервис доступа
$router->get('/api/services', [ServiceController::class, 'index']);
$router->get('/api/services/check', [ServiceController::class, 'check']);
$router->post('/api/services/{slug}/buy', [ServiceController::class, 'buy']);


// Запуск
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

