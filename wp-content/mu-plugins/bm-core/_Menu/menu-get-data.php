<?php
header('Content-Type: application/json; charset=utf-8');

global $pdo;

// Запрос для получения поэтов и их стихов
try {
    $sql = "
        SELECT 
            p.id AS poet_id,
            p.short_name,
            p.full_name_last,
            p.poet_slug,
            GROUP_CONCAT(
                JSON_OBJECT(
                    'id', pm.id,
                    'name', pm.name,
                    'poem_slug', pm.poem_slug
                )
            ) as poems_json
        FROM bm_ctbl000_poet p
        LEFT JOIN bm_ctbl000_poem pm ON p.id = pm.poet_id
        WHERE p.is_active = 1 
        AND p.is_approved = 1
        AND (pm.is_active = 1 OR pm.id IS NULL)
        AND (pm.is_approved = 1 OR pm.id IS NULL)
        GROUP BY p.id, p.short_name, p.full_name_last, p.poet_slug
        ORDER BY p.short_name
    ";
    
    $stmt = $pdo->query($sql);
    $poets = [];
    
    while ($row = $stmt->fetch()) {
        $poet = [
            'id' => (int)$row['poet_id'],
            'short_name' => $row['short_name'],
            'full_name' => $row['full_name_last'],
            'slug' => $row['poet_slug'],
            'poems' => []
        ];
        
        // Парсим JSON со стихами
        if (!empty($row['poems_json'])) {
            $poemsData = json_decode('[' . $row['poems_json'] . ']', true);
            if (is_array($poemsData)) {
                $poet['poems'] = array_filter($poemsData, function($poem) {
                    return !empty($poem['id']);
                });
            }
        }
        
        $poets[] = $poet;
    }
    
    // Оптимизация для больших объемов данных
    if (count($poets) > 0) {
        // Кэширование на 5 минут для производительности
        header('Cache-Control: max-age=300, public');
    }
    
    echo json_encode([
        'success' => true,
        'poets' => $poets,
        'count' => count($poets)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Ошибка запроса: ' . $e->getMessage()]);
}
