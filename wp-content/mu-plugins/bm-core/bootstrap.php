<?php

use BM\Core\Container;
use BM\Core\Database\Connection;
use BM\Core\Database\Cache;
use BM\Repositories\TrackRepository;
use BM\Repositories\PoetRepository;
use BM\Repositories\PoemRepository;
use BM\Admin\TrackEditor;


// 1. Создаём контейнер
$container = new Container();

// 2. Регистрируем зависимости (привязываем интерфейсы/классы к фабрикам)
$container->set(Connection::class, function($c) {
    return new Connection();  // Параметры Connection берёт из $_ENV
});

$container->set(Cache::class, function($c) {
    return new Cache($c->get(Connection::class));  // Cache зависит от Connection
});

// Регистрация репозиториев (если они не создаются автоматически)
$container->set(TrackRepository::class, function($c) {
    return new TrackRepository(
        $c->get(Connection::class),
        $c->get(Cache::class)
    );
});

$container->set(PoetRepository::class, function($c) {
    return new PoetRepository(
        $c->get(Connection::class),
        $c->get(Cache::class)
    );
});

$container->set(PoemRepository::class, function($c) {
    return new PoemRepository(
        $c->get(Connection::class),
        $c->get(Cache::class)
    );
});

// Регистрация TrackEditor (контейнер может создать и без явной регистрации)
$container->set(TrackEditor::class, function($c) {
    return new TrackEditor(
        $c->get(TrackRepository::class),
        $c->get(PoetRepository::class),
        $c->get(PoemRepository::class)
    );
});

// 3. Регистрируем репозитории (если нужны специфические настройки)
$container->set(PoemRepository::class, function($c) {
    return new PoemRepository(
        $c->get(Connection::class),
        $c->get(Cache::class)
    );
});

// 4. Сохраняем контейнер в глобальную переменную
global $app_container;
$app_container = $container;