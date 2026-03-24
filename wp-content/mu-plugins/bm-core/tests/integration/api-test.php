<?php
/**
 * Интеграционный тест API
 * Запустить: php wp-content/mu-plugins/bm-core/tests/integration/api-test.php
 */

require_once __DIR__ . '/../../../../../wp-load.php';

use BM\Core\Router;

$tests = [
    'GET /api/tracks' => function() {
        $ch = curl_init('http://localhost:8080/api/tracks');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $code === 200;
    },
    
    'GET /api/tracks/popular' => function() {
        $ch = curl_init('http://localhost:8080/api/tracks/popular');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $code === 200;
    },
    
    'GET /api/recommendations/user' => function() {
        $ch = curl_init('http://localhost:8080/api/recommendations/user');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $code === 200;
    }
];

echo "Running integration tests...\n\n";

$passed = 0;
$failed = 0;

foreach ($tests as $name => $test) {
    echo "Testing: $name ... ";
    
    try {
        $result = $test();
        
        if ($result) {
            echo "✅ PASSED\n";
            $passed++;
        } else {
            echo "❌ FAILED\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n";
echo "Results: $passed passed, $failed failed\n";
echo $failed === 0 ? "✅ ALL TESTS PASSED\n" : "❌ SOME TESTS FAILED\n";
