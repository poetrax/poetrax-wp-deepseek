<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/wp-content/mu-plugins/bm-core-loader.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    echo json_encode([
        'success' => true,
        'message' => 'API is working',
        'timestamp' => time()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
