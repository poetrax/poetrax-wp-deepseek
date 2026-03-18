<?php
define('WP_USE_THEMES', false);
require_once('wp-load.php');

$token = $_GET['token'] ?? '';
$valid_token = md5('qazwsx12345!' . date('Y-m-d-H')); //TODO сделать строгим

if ($token !== $valid_token) {
    die('Доступ запрещен');
}

// Путь к реальному файлу (храните вне публичной папки!)
$file_path = '/secret-folder/track.mp3';

// Отправляем файл
header('Content-Type: audio/mpeg');
readfile($file_path);
