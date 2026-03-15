<?php
// Полный контроль над страницей, без WordPress

// Получаем название трека из URL (передаётся из .htaccess)
$track = isset($_GET['track']) ? $_GET['track'] : '';

// Очищаем входные данные
$track = preg_replace('/[^a-z0-9-]+/', '', $track);

if (empty($track)) {
    header('HTTP/1.0 404 Not Found');
    echo 'Трек не найден';
    exit;
}

// Путь к папке с аудио
$audio_dir = $_SERVER['DOCUMENT_ROOT'] . '/wp-content/uploads/store/audio/mp3/';
$audio_url_base = '/wp-content/uploads/store/audio/mp3/';

// Ищем файл
$found_file = null;
$files = scandir($audio_dir);

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'mp3') {
        if (strpos($file, $track) !== false) {
            $found_file = $file;
            break;
        }
    }
}

if (!$found_file) {
    header('HTTP/1.0 404 Not Found');
    include('404.php');
    exit;
}

// Извлекаем информацию из имени файла
// Пример: 32-18-razuverenie-evgenij_abramovich_baratynskij.mp3
$parts = explode('-', $found_file);
$track_name = ucwords(str_replace('_', ' ', $parts[2] ?? $track));
$artist_parts = array_slice($parts, 3, -1);
$artist = ucwords(str_replace('_', ' ', implode(' ', $artist_parts)));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $track_name; ?> — <?php echo $artist; ?></title>
    <meta name="description" content="Слушайте <?php echo $track_name; ?> — <?php echo $artist; ?> онлайн">
    
    <!-- Open Graph для соцсетей -->
    <meta property="og:title" content="<?php echo $track_name; ?> — <?php echo $artist; ?>">
    <meta property="og:type" content="music.song">
    <meta property="og:url" content="https://poetrax.ru/<?php echo $track; ?>">
    <meta property="og:audio" content="https://poetrax.ru<?php echo $audio_url_base . $found_file; ?>">
    
    <!-- Подключаем стили вашей темы вручную -->
    <link rel="stylesheet" href="/wp-content/themes/ваша-тема/style.css">
    
    <style>
        /* Дополнительные стили для плеера */
        .custom-player-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .track-info {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        audio {
            width: 100%;
            margin: 20px 0;
        }
        
        .artist {
            color: #666;
            font-size: 1.2em;
        }
        
        .download-link {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        
        .download-link:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <!-- Копируем сюда шапку вашего сайта -->
    <header class="site-header">
        <div class="container">
            <div class="logo">
                <a href="/">BESTMZ</a>
            </div>
            <nav class="main-navigation">
                <ul>
                    <li><a href="/">Главная</a></li>
                    <li><a href="/treki">Треки</a></li>
                    <li><a href="/o-nas">О нас</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="custom-player-container">
        <article class="track-full">
            <h1><?php echo $track_name; ?></h1>
            <p class="artist"><?php echo $artist; ?></p>
            
            <div class="track-info">
                <audio controls preload="metadata">
                    <source src="<?php echo $audio_url_base . $found_file; ?>" type="audio/mpeg">
                    Ваш браузер не поддерживает аудиоплеер.
                </audio>
                
                <p>
                    <strong>Длительность:</strong> 
                    <span class="duration">--:--</span>
                </p>
                
                <p>
                    <strong>Размер файла:</strong> 
                    <?php 
                    $size = filesize($audio_dir . $found_file);
                    echo round($size / 1024 / 1024, 2) . ' MB';
                    ?>
                </p>
                
                <a href="<?php echo $audio_url_base . $found_file; ?>" download class="download-link">
                    Скачать MP3
                </a>
            </div>
            
            <div class="track-description">
                <h3>Описание</h3>
                <p>
                    <?php echo $track_name; ?> — это стихотворение 
                    <?php echo $artist; ?>, которое было положено на музыку.
                    Прекрасный образец русской поэзии XIX века.
                </p>
            </div>
        </article>
    </main>

    <script>
    // Получаем длительность аудио
    document.addEventListener('DOMContentLoaded', function() {
        var audio = document.querySelector('audio');
        var durationSpan = document.querySelector('.duration');
        
        audio.addEventListener('loadedmetadata', function() {
            var minutes = Math.floor(audio.duration / 60);
            var seconds = Math.floor(audio.duration % 60);
            durationSpan.textContent = minutes + ':' + 
                (seconds < 10 ? '0' + seconds : seconds);
        });
    });
    </script>

    <!-- Копируем сюда подвал -->
    <footer class="site-footer">
        <div class="container">
            <p>&copy; 2026 BESTMZ. Все права защищены.</p>
        </div>
    </footer>
</body>
</html>