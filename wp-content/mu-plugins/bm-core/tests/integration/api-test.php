<?php
/**
 * Интеграционный тест API
 */

require_once dirname(__DIR__) . '/TestConfig.php';

$config = TestConfig::getInstance();

echo "🚀 Тестирование API\n";
echo "📡 URL: " . $config->getBaseUrl() . "\n\n";

$tests = [
    'GET /api/tracks' => function() use ($config) {
        $ch = curl_init($config->url('api/tracks'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code === 200;
    }
];

$passed = 0;
$failed = 0;

foreach ($tests as $name => $test) {
    echo "🔍 Тест: $name ... ";
    
    try {
        $result = $test();
        if ($result) {
            echo "✅\n";
            $passed++;
        } else {
            echo "❌\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n";
echo "📊 Результаты: $passed passed, $failed failed\n";