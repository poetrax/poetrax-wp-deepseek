<?php
/**
 * Template Name: Аудио трек
 * Description: Шаблон для отображения страниц аудиотреков
 */

get_header();

// Получаем slug трека из URL
$track_slug = get_query_var('track_name');
error_log('$track_slug '.$track_slug);
$audio_url = get_audio_url_by_slug($track_slug);

if (!$audio_url) {
    // Если файл не найден — 404
    get_template_part('404');
    get_footer();
    exit;
}
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
     
        <article id="track-<?php echo esc_attr($track_slug); ?>" class="track-article">
            
            <header class="entry-header">
                <h1 class="entry-title">
                    <?php echo esc_html(ucfirst(str_replace('-', ' ', $track_slug))); ?>
                </h1>
            </header>

            <div class="entry-content">
                <div class="audio-player-container">

                    <audio controls nopreload style="width: 100%;">
                        <source src="<?php echo esc_url($audio_url); ?>" type="audio/mpeg">
                        Ваш браузер не поддерживает аудиоплеер.
                    </audio>

                </div>

                <div class="track-meta">
                    <?php
                    // Здесь можно вывести мета-информацию о треке
                    // Например, извлечь исполнителя из имени файла
                    $artist = extract_artist_from_filename($track_slug);
                    if ($artist) {
                        echo '<p class="track-artist">Исполнитель: ' . esc_html($artist) . '</p>';
                    }
                    ?>
                </div>

                <div class="track-description">
                    <?php
                    // Если нужно описание — можно хранить в отдельном файле или БД
                    ?>
                </div>
            </div>

            <?php
            // Если нужны комментарии к трекам
            if (comments_open() || get_comments_number()) {
                comments_template();
            }
            ?>

        </article>
        
    </main>
</div>

<?php
get_sidebar(); // если нужен сайдбар
get_footer();

// Вспомогательная функция для извлечения исполнителя
function extract_artist_from_filename($slug) {
    // Пример: 32-18-razuverenie-evgenij_abramovich_baratynskij
    $parts = explode('-', $slug);
    if (count($parts) > 3) {
        // Удаляем первые два числа и слово трека
        $artist_parts = array_slice($parts, 3);
        return ucwords(str_replace('_', ' ', implode(' ', $artist_parts)));
    }
    return false;
}