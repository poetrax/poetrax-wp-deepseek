<?php
global $pdo;

// Получаем данные из POST запроса
$input = json_decode(file_get_contents('php://input'), true);
$trackId = $input['track_id'] ?? null;
$type = $input['type'] ?? null;
$action = $input['action'] ?? 'add';

// В реальном приложении здесь должна быть аутентификация пользователя
$userId = get_current_user_id(); // Заглушка - нужно реализовать получение ID пользователя
$ip = $_SERVER['REMOTE_ADDR'];

if (!$trackId || !$type || !in_array($type, ['like', 'bookmark'])) {
    //echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Проверяем, не существует ли уже запись
    $checkStmt = $pdo->prepare("
        SELECT id FROM bm_ctbl000_interaction 
        WHERE track_id = ? AND user_id = ? AND type = ?
    ");
    $checkStmt->execute([$trackId, $userId, $type]);
    $existing = $checkStmt->fetch();

    if ($existing && $action === 'add') {
        //echo json_encode(['success' => false, 'message' => 'Already exists']);
        exit;
    }

    if ($action === 'add') {
        // Добавляем новую запись
        $stmt = $pdo->prepare("
            INSERT INTO bm_ctbl000_interaction (track_id, user_id, type, ip, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$trackId, $userId, $type, $ip]);
    } else {
        // Удаляем запись (если нужно реализовать удаление)
        $stmt = $pdo->prepare("
            DELETE FROM bm_ctbl000_interaction 
            WHERE track_id = ? AND user_id = ? AND type = ?
        ");
        $stmt->execute([$trackId, $userId, $type]);
    }

    // Получаем общее количество лайков для трека
    if ($type === 'like') {
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM bm_ctbl000_interaction 
            WHERE track_id = ? AND type = 'like'
        ");
        $countStmt->execute([$trackId]);
        $likesCount = $countStmt->fetch()['count'];
        
        //echo json_encode(['success' => true, 'likes_count' => $likesCount]);
    } else {
        //echo json_encode(['success' => true]);
    }

} catch (PDOException $e) {
    //echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
