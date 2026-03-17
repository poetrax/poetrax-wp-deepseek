<?php
// api.php - единая точка входа для API

namespace BM\Core;

use BM\Core\Controller\TrackController;

// Загружаем автозагрузчик
require_once __DIR__ . '/../../../../vendor/autoload.php';

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
