<?php
header('Content-Type: application/json; charset=utf-8');

global $pdo;

// Запрос для получения треков конкретного стихотворения
try {
    $sql = "
        SELECT 
            track_name,
            track_path,
            track_format,
            track_duration,
            poet_name,
            poem_name
        FROM bm_ctbl000_track
        WHERE poem_id = :poem_id
        AND status = 'completed'
        AND is_approved = 1
        ORDER BY track_name
        LIMIT 100  -- Ограничение для защиты от слишком больших выборок
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':poem_id' => $poemId]);
    $tracks = $stmt->fetchAll();
    
    // Проверяем наличие ссылок на треки
    foreach ($tracks as &$track) {
        if (empty($track['track_path'])) {
            $track['track_path'] = '#';
        }
    }
    
    echo json_encode([
        'success' => true,
        'tracks' => $tracks,
        'count' => count($tracks),
        'poem_id' => $poemId
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Ошибка запроса: ' . $e->getMessage()]);
}
