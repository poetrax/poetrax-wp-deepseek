<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost();
        break;
    case 'PUT':
        handlePut();
        break;
    case 'DELETE':
        handleDelete();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Метод не поддерживается']);
}

function handleGet() {
    $id = $_GET['id'] ?? null;
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 20;
    $search = $_GET['search'] ?? '';
    $offset = ($page - 1) * $limit;
    
    if ($id) {
        // Получение одного поэта
        $sql = "SELECT * FROM bm_ctbl000_poet WHERE id = ?";
        $result = Connection::query($sql, [$id])->fetch();
        
        if ($result) {
            echo json_encode(['success' => true, 'data' => $result]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Поэт не найден']);
        }
    } else {
        // Получение списка с пагинацией
        $where = '';
        $params = [];
        
        if ($search) {
            $where = "WHERE (last_name LIKE ? OR first_name LIKE ?)";
            $searchTerm = "%$search%";
            $params = [$searchTerm, $searchTerm];
        }
        
        // Общее количество
        $countSql = "SELECT COUNT(*) as total FROM bm_ctbl000_poet $where";
        $total = Connection::query($countSql, $params)->fetch()['total'];
        
        // Данные
        $sql = "SELECT * FROM bm_ctbl000_poet $where 
                ORDER BY last_name, first_name 
                LIMIT ? OFFSET ?";
        
        $params[] = (int)$limit;
        $params[] = (int)$offset;
        
        $poets = Connection::query($sql, $params)->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $poets,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
}

function handlePost() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Валидация
    $errors = [];
    if (empty($data['last_name'])) $errors[] = 'Фамилия обязательна';
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        return;
    }
    
    $sql = "INSERT INTO bm_ctbl000_poet 
            (last_name, first_name, second_name, name_sfx, birth_year, death_year, bio, photo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    try {
        Connection::query($sql, [
            $data['last_name'],
            $data['first_name'] ?? null,
            $data['second_name'] ?? null,
            $data['name_sfx'] ?? null,
            $data['birth_year'] ?? null,
            $data['death_year'] ?? null,
            $data['bio'] ?? null,
            $data['photo'] ?? null
        ]);
        
        $id = Database::lastInsertId();
        echo json_encode(['success' => true, 'id' => $id]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handlePut() {
    parse_str(file_get_contents("php://input"), $put_vars);
    $id = $put_vars['id'] ?? $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID не указан']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $fields = [];
    $params = [];
    
    foreach (['last_name', 'first_name', 'second_name', 'name_sfx', 
              'birth_year', 'death_year', 'bio', 'photo', 'is_active'] as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        echo json_encode(['success' => false, 'error' => 'Нет данных для обновления']);
        return;
    }
    
    $params[] = $id;
    $sql = "UPDATE bm_ctbl000_poet SET " . implode(', ', $fields) . " WHERE id = ?";
    
    try {
        Connection::query($sql, $params);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleDelete() {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID не указан']);
        return;
    }
    
    try {
        $sql = "DELETE FROM bm_ctbl000_poet WHERE id = ?";
        Connection::query($sql, [$id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
