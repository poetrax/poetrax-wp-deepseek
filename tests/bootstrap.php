<?php
// tests/bootstrap.php

// Автозагрузка Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Настройка для тестов
date_default_timezone_set('UTC');

// Если нужно подключить автозагрузку BM Core
$bmCoreLoader = __DIR__ . '/../wp-content/mu-plugins/bm-core-loader.php';
if (file_exists($bmCoreLoader)) {
    require_once $bmCoreLoader;
}

// Настройка переменных окружения для тестов
putenv('APP_ENV=testing');

// Алиасы для обратной совместимости (если нужно)
if (!class_exists('BM\Core\Repository\PoemRepository') && class_exists('BM\Repositories\PoemRepository')) {
    class_alias('BM\Repositories\PoemRepository', 'BM\Core\Repository\PoemRepository');
}
