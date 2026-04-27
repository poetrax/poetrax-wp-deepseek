<?php
namespace BM\Core;
use PDO;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/wp-content/mu-plugins/bm-core-loader.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

ob_start(); // буферизация вывода


// Диагностика — временно
$requestUri = $_SERVER['REQUEST_URI'];
file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - " . $requestUri . PHP_EOL, FILE_APPEND);

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
use BM\Core\Config;

$connection = Connection::getInstance($dbConfig);
$cache = Cache::getInstance();
$loader = new Loader($connection, $cache, $config);
$config = Config::getInstance();
$config->get('key');
$warmupTtl = $config->get('cache.warmup_ttl') ?? 86400;
if (!$cache->has('table:warmed_up')) {
    $loader->warmupCache();
    $cache->set('table:warmed_up', time(), $warmupTtl);
}

// ============================================
// МАРШРУТИЗАЦИЯ
// ============================================
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// GET /api/tracks
if ($requestUri === '/api/tracks' && $method === 'GET') {
    header('Content-Type: application/json');
    $pdo = Connection::getPDO();
    $stmt = $pdo->query("SELECT * FROM bm_ctbl000_track LIMIT 50");
    $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode(['success' => true, 'data' => $tracks]);
    exit;
}


// GET /api/tracks/popular
if ($requestUri === '/api/tracks/popular' && $method === 'GET') {
    header('Content-Type: application/json');
    $pdo = Connection::getPDO();
    $stmt = $pdo->query("SELECT * FROM bm_ctbl000_track ORDER BY count_play DESC LIMIT 10");
    $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $tracks]);
    exit;
}

// GET /api/tracks/{id}
if (preg_match('#^/api/tracks/(\d+)$#', $requestUri, $matches) && $method === 'GET') {
    header('Content-Type: application/json');
    $id = (int)$matches[1];
    $pdo = Connection::getPDO();
    $stmt = $pdo->prepare("SELECT * FROM bm_ctbl000_track WHERE id = ?");
    $stmt->execute([$id]);
    $track = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$track) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Track not found']);
        exit;
    }
    echo json_encode(['success' => true, 'data' => $track]);
    exit;
}


/*
// ==================================================
// ЖЕСТКИЙ ИСПРАВЛЕННЫЙ ПОИСК
// ==================================================
if ($requestUri === '/api/tracks/search' && $method === 'GET') {
    // Отключаем вывод любых ошибок в браузер, чтобы они не сломали JSON
    error_reporting(0);
    ini_set('display_errors', 0);
    
    header('Content-Type: application/json');
    
    // 1. Получаем и чистим параметр
    $raw_query = trim($_GET['q'] ?? '');
    $query = htmlspecialchars(strip_tags($raw_query));
    
    // 2. Если строка короткая — пустой результат
    if (mb_strlen($query, 'UTF-8') < 2) {
        echo json_encode(['success' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        // 3. Прямое подключение через PDO
        $pdo = new PDO(
            "mysql:host=poetrax_deepseek_mysql;dbname=u3436142_poetrax_deepseek_db;charset=utf8mb4",
            "u3436142_poetrax_deepseek_user",
            "CI57bdR7m6F9Xem7"
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 4. Готовим запрос с явным указанием нативной строки
        $sql = "SELECT * FROM bm_ctbl000_track WHERE track_name LIKE :search LIMIT 20";
        $stmt = $pdo->prepare($sql);
        
        // 5. КЛЮЧЕВОЙ МОМЕНТ: формируем строку для поиска и принудительно указываем кодировку
        $search_param = '%' . $query . '%';
        $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        
        $stmt->execute();
        $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 6. Отдаём результат
        echo json_encode(['success' => true, 'data' => $tracks], JSON_UNESCAPED_UNICODE);
        
    } catch (PDOException $e) {
        // В случае ошибки возвращаем JSON с ошибкой, а не пустой массив
        echo json_encode([
            'success' => false, 
            'error' => 'Database error: ' . $e->getMessage(),
            'query_received' => $query
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
*/


// GET /api/tracks/search

//временно
file_put_contents('/tmp/search_debug.log', print_r($_GET, true) . PHP_EOL, FILE_APPEND);
//временно

if ($requestUri === '/api/tracks/search' && $method === 'GET') {
    header('Content-Type: application/json');
    $query = trim($_GET['q'] ?? '');
    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    
    $pdo = Connection::getPDO();
    $stmt = $pdo->prepare("SELECT * FROM bm_ctbl000_track WHERE track_name LIKE ? LIMIT 20");
    $stmt->execute(["%$query%"]);
    $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $tracks]);
    exit;
}



// GET /api/recommendations/user
if ($requestUri === '/api/recommendations/user' && $method === 'GET') {
    header('Content-Type: application/json');
    $userId = (int)($_GET['user_id'] ?? 0);
    // Заглушка
    $pdo = Connection::getPDO();
    $stmt = $pdo->query("SELECT * FROM bm_ctbl000_track ORDER BY RAND() LIMIT 5");
    $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $tracks]);
    exit;
}

// GET /api/recommendations/track/{id}
if (preg_match('#^/api/recommendations/track/(\d+)$#', $requestUri, $matches) && $method === 'GET') {
    header('Content-Type: application/json');
    $trackId = (int)$matches[1];
    // Заглушка
    $pdo = Connection::getPDO();
    $stmt = $pdo->prepare("SELECT * FROM bm_ctbl000_track WHERE id != ? LIMIT 5");
    $stmt->execute([$trackId]);
    $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $tracks]);
    exit;
}

// 404
http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint not found']);


// Запускаем сессию для авторизации
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Создаём роутер
$router = new Router();

// ============================================
// API маршруты
// ============================================

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

// ============================================
// Запуск
// ============================================

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

$router->post('/api/filter/tracks', [FilterController::class, 'filter']);
$router->post('/api/filter/poets', [FilterController::class, 'filter']);
$router->post('/api/filter/poems', [FilterController::class, 'filter']);
$router->post('/api/filter/users', [FilterController::class, 'filter']);
$router->get('/api/filter/{entity}/available', [FilterController::class, 'availableFilters']);

// Фильтрация сущностей через свойства треков
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