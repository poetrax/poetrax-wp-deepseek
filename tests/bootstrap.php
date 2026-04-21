<?php
require_once __DIR__ . '/../vendor/autoload.php';

use BM\Core\Config;
$config = Config::getInstance();

// Настройка окружения для тестов
$_ENV['DEEPSEEK_DB_HOST'] = 'poetrax_deepseek_mysql';
$_ENV['DEEPSEEK_DB_NAME'] = 'u3436142_poetrax_deepseek_db';
$_ENV['DEEPSEEK_DB_USER'] = 'u3436142_poetrax_deepseek_user';
$_ENV['DB_PASSWORD'] = 'CI57bdR7m6F9Xem7';