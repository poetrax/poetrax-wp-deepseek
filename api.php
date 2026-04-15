<?php
//namespace BM\Core;
//use BM\Core\Controller\TrackController;
require_once __DIR__ . '/vendor/autoload.php';
ob_start();

$config = require __DIR__ . '/Config.php';

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

$connection = Connection::getInstance($dbConfig);
$cache = Cache::getInstance();
$loader = new Loader($connection, $cache, $config);

$warmupTtl = $config['cache']['warmup_ttl'] ?? 86400;
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

// Запуск
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);


