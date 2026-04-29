<?php
namespace BM\Core\User;


class UserManager
{
    use BM\Core\Database\Connection;
    use BM\Core\Database\Connection;

    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function findOrCreate(array $data): array
    {
        $email = $data['your_email'] ?? null;

        if (!$email) {
            throw new \InvalidArgumentException('Email is required');
        }

        // Ищем пользователя
        $user = $this->db
            ->query("SELECT * FROM bm_ctbl000_user WHERE user_email = ?", [$email])
            ->fetch();

        // Создаём если нет
        if (!$user) {
            $this->db->query(
                "INSERT INTO bm_ctbl000_user (user_email, created_at) VALUES (?, NOW())",
                [$email]
            );

            $user = $this->db
                ->query("SELECT * FROM bm_ctbl000_user WHERE user_email = ?", [$email])
                ->fetch();
        }

        return $user;
    }
}

// Использование:
//$userManager = new UserManager($connection);
//$user = $userManager->findOrCreate(['your_email' => 'test@example.com']);


function process_form_data($data) {

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Валидация и очистка данных
        $errors = [];
    
        // Обязательные поля
        $required_fields = [
            'your_name' => 'Имя',
            'your_last_name' => 'Фамилия',
            'your_email' => 'Email',
            'your_display_name' => 'Псевдоним',
            'track_name' => 'Название трека',
            'self_made_text' => 'Текст',
            'track_poet_name' => 'Автор (поэт)',
            'track_poem_title' => 'Стихотворение (название)'
        ];

            if ($_POST['self_made'] === 'Да') {
                $required_fields=['self_made_text'];
            }
            else {
                $required_fields=['track_poet_name'];
                $required_fields=['track_poem_title'];
            }
    
            foreach ($required_fields as $field => $name) {
                if (empty($data[$field])) {
                    $errors[] = "Поле \"{$name}\" обязательно для заполнения";
                }
            }
    
        // Валидация email
        if (!filter_var($_POST['your_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = " Некорректный email адрес";
        }
    
        // Валидация url
        if (!filter_var($_POST['your_url'], FILTER_VALIDATE_URL)) {
            $errors[] = " Некорректный url адрес";
        }

        // Если есть ошибки - возвращаем их
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
    
        try {
            connection->beginTransaction();
        
            //Поиск или создание пользователя
            $user = findOrCreateUser($_POST);
        
            //Создание основного заказа
            $track_id = createTrackOrder($user['id'], $_POST);
        
            //Обработка текста (собственный или поэтический)
            processTrackText($track_id, $_POST);
        
            //Обработка музыкальных деталей (если это песня)
            if ($_POST['track_performance'] === 'Песня (музыка и текст)') {
                processMusicDetails($track_id, $_POST);
            }
        
            //Обработка предложений стилей/жанров
            processStyleGenreSuggestions($track_id, $user['id'], $_POST);

            connection->commit();
        
            // Успешный ответ
            echo json_encode([
                'success' => true, 
                'message' => 'Заказ успешно создан',
                'track_id' => $track_id
            ]);
        
        } catch (\Exception $e) {
            connection->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Ошибка при сохранении заказа: ' . $e->getMessage()]);
        }
    }


function createTrackOrder($user_id, $data) {
   global $pdo;
   $stmt = $pdo->prepare("
        INSERT INTO bm_ctbl000_track 
        (payable, user_id, track_name, status, self_made, performance_type, 
         voice_gender, voice_character, site_placement, send_email, ip, created_at) 
        VALUES (?, ?, ?, 'new', ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $payable = ($data['payable_track'] === 'Да') ? 1 : 0;
    $self_made = ($data['self_made'] === 'Да') ? 1 : 0;
    $performance_type = ($data['track_performance'] === 'Песня (музыка и текст)') ? 'song' : 'recitation';
    $site_placement = ($data['track_site_placement'] === 'Да') ? 1 : 0;
    $send_email = ($data['track_send_email'] === 'Да') ? 1 : 0;
    $ip = $_SERVER['REMOTE_ADDR'];
   
    $stmt->execute([
        $payable,
        $user_id,
        sanitize_text_field(trim($data['track_name'])),
        $self_made,
        $performance_type,
        sanitize_text_field($data['voice_gender']),
        sanitize_text_field($data['voice_character']),
        $site_placement,
        $send_email,
        $ip
    ]);
    
    return $pdo->lastInsertId();
}

function processTrackText($track_id, $data) {
   global $pdo;
   if ($data['self_made'] === 'Да') {
        // Собственный текст
        $stmt = $pdo->prepare("
            INSERT INTO bm_ctbl000_track_self_text (track_id, text) 
            VALUES (?, ?)
        ");
        $stmt->execute([$track_id, trim($data['self_made_text'])]);
    } else {
        // Поэтический текст
        $stmt = $pdo->prepare("
            INSERT INTO bm_ctbl000_track_poet_text 
            (track_id, poet_name, poem_title, adaptation_requirements) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $track_id,
            trim($data['track_poet_name']),
            trim($data['track_poem_title']),
            trim($data['track_text_requiriment'])
        ]);
    }
}

function processMusicDetails($track_id, $data) {
    global $pdo;
    // Преобразование тональности
    $tonality_note = $data['track_notes'];
    $tonality_level = $data['track_dz_bm'];
    $tonality_mood = $data['track_maj_min'];
    $stmt = $pdo->prepare("
        INSERT INTO bm_ctbl000_track_music_detail 
        (track_id, bpm, tonality_note, tonality_level, tonality_mood, 
         instruments, special_requirements, voice_group, voice_register) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $track_id,
        intval($data['track_bpm']),
        $tonality_note,
        $tonality_level,
        $tonality_mood,
        trim($data['track_instruments']),
        trim($data['track_special']),
        strtolower($data['voice_group']),
        $data['voice_register']
    ]);
}

function processStyleGenreSuggestions($track_id, $user_id, $data) {
    global $pdo;
    // Обработка предложения стиля TODO ??? здесь каскад
    if (!empty($data['suggestion_style'])) {
        $stmt = $pdo->prepare("
            INSERT INTO bm_ctbl000_style_genre_suggestion 
            (name, user_id, track_id, type, status) 
            VALUES (?, ?, ?, 'style', 'pending')
        ");
        $stmt->execute([trim($data['suggestion_style']), $user_id, $track_id]);
    }
    
    // Обработка предложения жанра
    if (!empty($data['suggestion_genre'])) {
        $stmt = $pdo->prepare("
            INSERT INTO bm_ctbl000_style_genre_suggestion 
            (name, user_id, track_id, type, status) 
            VALUES (?, ?, ?, 'genre', 'pending')
        ");
        $stmt->execute([trim($data['suggestion_genre']), $user_id, $track_id]);
    }
}

//Обработка формы заказа трека----------------------------//

add_action('wpcf7_before_send_mail', 'process_custom_form_data');
add_action('wpcf7_mail_failed', 'process_custom_form_data');



function process_custom_form_data($contact_form) {
    if ($contact_form->id() != 1169) {
        return;
    }
    
    // Получаем данные формы
    $submission = WPCF7_Submission::get_instance();
    
    if ($submission) {
        $posted_data = $submission->get_posted_data();
        
        // Обработка данных формы
        process_form_data($posted_data);
    }
}


/*
function process_form_data($data) {

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Валидация и очистка данных
            $errors = [];
    
            // Обязательные поля
            $required_fields = [
                'your_name' => 'Имя',
                'your_last_name' => 'Фамилия',
                'your_email' => 'Email',
                'your_display_name' => 'Псевдоним',
                'track_name' => 'Название трека'
            ];

            if ($_POST['self_made'] === 'Да') {
                $required_fields=['self_made_text'=>'Текст'];
            }
            else {
                $required_fields=['track_poet_name'=>'Автор (поэт)'];
                $required_fields=['track_poem_title'=>'Стихотворение (название)'];
            }
    
            foreach ($required_fields as $field => $name) {
                if (empty($data[$field])) {
                    $errors[] = "Поле \"{$name}\" обязательно для заполнения";
                }
            }
    
        // Валидация email
        if (!filter_var($_POST['your_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = " Некорректный email адрес";
        }
    
        // Валидация url
        if (!filter_var($_POST['your_url'], FILTER_VALIDATE_URL)) {
            $errors[] = " Некорректный url адрес";
        }

        // Если есть ошибки - возвращаем их
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
    
        try {
            Pdo::beginTransaction();
           
            //Поиск или создание пользователя
            $user = findOrCreateUser($_POST);
        
            //Создание основного заказа
            $track_id = createTrackOrder($user['id'], $_POST);
        
            //$file???
            setAudioInfo($track_id, $file);
            
            //Обработка текста (собственный или поэтический)
            processTrackText($track_id, $_POST);
        
            //Обработка музыкальных деталей (если это песня)
            if ($_POST['track_performance'] === 'Песня (музыка и текст)') {
                processMusicDetails($track_id, $_POST);
            }

            Pdo::commit();
      
        
        } catch (Exception $e) {
            Pdo::rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Ошибка при сохранении заказа: ' . $e->getMessage()]);
        }
    }
}

// Вспомогательные функции
function findOrCreateUser($data) {
    $query = $pdo->prepare("SELECT * FROM bm_ctbl000_user WHERE user_email = ?");
    $user = Pdo::row($query,[$data['your_email']]); 
  
    if ($user) {
        // Полное обновление пользователя
        // Готовим запрос только для тех полей, которые есть в форме и не пусты
        $update_fields = [];
        $update_params = [];
        
        // Сопоставление полей формы с полями БД
        $field_mapping = [
            'your_name' => 'user_first_name',
            'your_last_name' => 'user_last_name', 
            'your_display_name' => 'display_name',
            'mask_phone' => 'user_phone',
            'your_url' => 'user_url'
        ];
        
        foreach ($field_mapping as $form_field => $db_field) {
            if (!empty($data[$form_field])) {
                // Обновляем поле, если оно было пустое у пользователя ИЛИ просто всегда обновляем
                if (empty($user[$db_field]) || true) {
                    $update_fields[] = "{$db_field} = ?";
                    $update_params[] = trim($data[$form_field]);
                }
            }
        }

        // Если есть что обновлять
        if (!empty($update_fields)) {
            $update_params[] = $user['id'];
            $query = "UPDATE bm_ctbl000_user SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $this->connection->query($query,[$update_params]);
        }
        
        return $user;
        
    } else {
        // Создание нового пользователя
        $stmt = $pdo->prepare("
        INSERT INTO bm_ctbl000_user 
            (user_first_name, user_last_name, display_name, user_email, user_phone, user_url, user_registered) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            trim($data['your_name']),
            trim($data['your_last_name']), 
            trim($data['your_display_name']),
            trim($data['your_email']),
            trim($data['mask_phone']),
            trim($data['your_url'])
        ]);
        
        return ['id' => $pdo->lastInsertId()];
    }
}

function createTrackOrder($user_id, $data) {
   global $pdo;
   $stmt = $pdo->prepare("
        INSERT INTO bm_ctbl000_track 
        (payable, user_id, track_name, status, is_self_made, performance_type, 
         voice_gender, voice_character_id, is_site_placement, is_send_email, ip) 
        VALUES (?, ?, ?, 'new', ?, ?, ?, ?, ?, ?, ?)
   
    ");
      //Обработка предложений стилей/жанров/поэтов/стихов
      $suggested=processStyleGenreSuggestions($track_id, $user['id'], $_POST);
     
      $poet_id = $suggested['poet_id']; 
      $poem_id = $suggested['poem_id']; 
      if ($poet_id!=='') {}
      if ($poem_id!=='') {}

    //TODO еще poet_id poem_id author_name slug_author
 
    $payable = ($data['payable_track'] === 'Да') ? 1 : 0;
    $self_made = ($data['self_made'] === 'Да') ? 1 : 0;
    
    $performance_type = match($data['track_performance']) {
        'Песня (музыка и текст)' => 'song',
        'Инструментал (только музыка)' => 'instrumental',
        default => 'recitation'
    };

    $site_placement = ($data['track_site_placement'] === 'Да') ? 1 : 0;
    $send_email = ($data['track_send_email'] === 'Да') ? 1 : 0;
    //TODO еще poet_id poem_id
    $ip = $_SERVER['REMOTE_ADDR'];
   
    $stmt->execute([
        $payable,
        $user_id,
        sanitize_text_field(trim($data['track_name'])),
        $self_made,
        $performance_type,
        sanitize_text_field($data['voice_gender']),
        //value not text
        $data['voice_character'],
        $site_placement,
        $send_email,
        sanitize_text_field($ip)
    ]);
    
    return $pdo->lastInsertId();
}

function processTrackText($track_id, $data) {
   global $pdo;
   if ($data['self_made'] === 'Да') {
        // Собственный текст
        $user_id=get_current_user_id();
        $stmt = $pdo->prepare("
            INSERT INTO bm_ctbl000_track_self_text (track_id,user_id, text) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$track_id, $user_id, trim($data['self_made_text'])]);
    } else {
        // Поэтический текст
        $stmt = $pdo->prepare("
            INSERT INTO bm_ctbl000_track_poet_text 
            (track_id, poet_name, poem_title, adaptation_requirements) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $track_id,
            sanitize_text_field(trim($data['track_poet_name'])),
            sanitize_text_field(trim($data['track_poem_title'])),
            sanitize_text_field(trim($data['track_text_requiriment']))
        ]);
    }
}

function processMusicDetails($track_id, $data) {
    global $pdo;
    // Преобразование тональности
    $tonality_note = $data['track_notes'];
    $tonality_level = $data['track_dz_bm'];
    $tonality_mood = $data['track_maj_min'];
    $stmt = $pdo->prepare("
        INSERT INTO bm_ctbl000_track_music_detail 
        (track_id, bpm, tonality_note, tonality_level, tonality_mood, 
         instruments, special_requirements, voice_group, voice_register_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
      //Обработка предложений стилей/жанров/поэтов/стихов
      $suggested=processStyleGenreSuggestions($track_id, $user['id'], $_POST);
      $genre_id=$suggested['genre_id'];
      $style_id=$suggested['style_id'];
      if($genre_id!==''){}
      if($style_id!==''){}

    $stmt->execute([
        $track_id,
        intval($data['track_bpm']),
        $tonality_note,
        $tonality_level,
        $tonality_mood,
        sanitize_text_field(trim($data['track_instruments'])),
        sanitize_text_field(trim($data['track_special'])),
        sanitize_text_field(strtolower($data['voice_group'])),
        //value
        $data['voice_register'] 
    ]);
}

function processStyleGenreSuggestions($track_id, $user_id, $data) {
    $suggested=[];
    global $pdo;
    // Обработка предложения поэта
    if (!empty($data['suggestion_poet'])) {
        $stmt = $pdo->prepare("
            INSERT INTO bm_ctbl000_poet 
            (name,is_suggestion) 
            VALUES 
            (?,1)
        ");
        $stmt->execute([
            sanitize_text_field(trim($data['suggestion_poet'])),
            $slug_poet
        ]);
        $poet_id = $pdo->lastInsertId();
        $suggested=['poet_id'=>$poet_id];
    }
    if(!$poet_id){ $suggested=['poet_id'=>''];}
  
     // Обработка предложения стихотворения
    if (!empty($data['suggestion_poem'])) {
        $stmt = $pdo->prepare("
            INSERT INTO bm_ctbl000_poem 
            (name,is_suggestion) 
            VALUES 
            (?,1)
        ");
        $stmt->execute([
            sanitize_text_field(trim($data['suggestion_poem']))
        ]);
        $poem_id = $pdo->lastInsertId();
        $suggested=['poem_id'=>$poem_id];
    }
    if(!$poem_id){ $suggested=['poem_id'=>''];}
    return $suggested;
}

*/

}