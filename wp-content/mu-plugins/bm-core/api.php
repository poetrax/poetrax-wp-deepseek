<?php
// api.php - единая точка входа для API

namespace BM\Core;

use BM\Core\Controller\TrackController;

// Загружаем автозагрузчик

require_once '/var/www/html/vendor/autoload.php';


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