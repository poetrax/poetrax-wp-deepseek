<?php
require_once __DIR__ . '/src/bootstrap.php';

use BM\Core\Router;
use BM\Core\Controller\TrackController;

$router = new Router();
$router->get('/api/tracks', [TrackController::class, 'index']);
$router->get('/api/tracks/{id}', [TrackController::class, 'show']);
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
