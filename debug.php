<?php
require_once __DIR__ . '/vendor/autoload.php';

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

$connection = $this->connection->getInstance($dbConfig);
$pdo = $this->connection->getPDO();

// Показать 5 примеров названий треков
$stmt = $pdo->query("SELECT id, track_name FROM bm_ctbl000_track LIMIT 5");
$samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Sample tracks:\n";
foreach ($samples as $sample) {
    echo "ID: {$sample['id']}, Name: {$sample['track_name']}\n";
}

// Поиск "прости"
$query = "прости";
$stmt = $pdo->prepare("SELECT id, track_name FROM bm_ctbl000_track WHERE track_name LIKE ? LIMIT 10");
$stmt->execute(["%$query%"]);
$tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nSearch results for 'прости': " . json_encode($tracks, JSON_UNESCAPED_UNICODE) . "\n";

// Поиск с большой буквы
$query = "Прости";
$stmt = $pdo->prepare("SELECT id, track_name FROM bm_ctbl000_track WHERE track_name LIKE ? LIMIT 10");
$stmt->execute(["%$query%"]);
$tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nSearch results for 'Прости': " . json_encode($tracks, JSON_UNESCAPED_UNICODE) . "\n";
