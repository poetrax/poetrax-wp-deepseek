<?php
/**
 * Template Name: Страница трека
 * Description: Шаблон для отображения одного трека с анимацией картинки
 */

$track_id = get_query_var('track_id'); // или из URL
$track_repo = new BM\Repositories\TrackRepository();
$interaction_service = new BM\Services\InteractionService();
$comment_service = new BM\Services\CommentService();

$track = $track_repo->find($track_id);
if (!$track) {
    wp_die('Трек не найден', 404);
}

// Статистика
$stats = $interaction_service->get_track_stats($track_id);
$user_id = get_current_user_id();
$is_liked = $user_id ? $interaction_service->has_interaction($track_id, $user_id, 'like') : false;
$is_bookmarked = $user_id ? $interaction_service->has_interaction($track_id, $user_id, 'bookmark') : false;

// Комментарии
$comments_tree = $comment_service->getTree($track_id);
$comments_count = $comment_service->comment_repo->getCount($track_id);

// Стихи
$poem = $track->poem_id ? $track_repo->poem_repo->find($track->poem_id) : null;

get_header();
?>

<div class="track-single" data-track-id="<?php echo $track->id; ?>">
    
    <!-- Хлебные крошки -->
    <div class="track-single__breadcrumbs">
        <a href="/">Главная</a> 
        <span class="separator">›</span>
        <a href="/tracks/">Треки</a>
        <span class="separator">›</span>
        <span class="current"><?php echo esc_html($track->track_name); ?></span>
    </div>
    
    <!-- Основной блок с картинкой и плеером -->
    <div class="track-single__hero">
        
        <!-- Левая колонка - картинка с анимацией -->
        <div class="track-single__cover-container">
            <div class="track-single__cover" id="track-cover">
                <?php if ($track->track_img): ?>
                    <img src="<?php echo esc_url($track->track_img); ?>" 
                         alt="<?php echo esc_attr($track->track_name); ?>"
                         class="track-single__cover-image"
                         id="track-cover-image">
                <?php else: ?>
                    <div class="track-single__cover-placeholder">
                       
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Правая колонка - информация и плеер -->
        <div class="track-single__info">
            <h1 class="track-single__title"><?php echo esc_html($track->track_name); ?></h1>
            
            <?php if ($track->poet): ?>
            <div class="track-single__poet">
                <span class="label">Поэт:</span>
                <a href="<?php echo esc_url($track->poet->url); ?>" class="value">
                    <?php echo esc_html($track->poet->short_name); ?>
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($poem): ?>
            <div class="track-single__poem-link">
                <span class="label">Стихотворение:</span>
                <a href="<?php echo esc_url($poem->url); ?>" class="value">
                    <?php echo esc_html($poem->name); ?>
                </a>
            </div>
            <?php endif; ?>
            
            <!-- Мета-информация -->
            <div class="track-single__meta">
                <?php if ($track->track_duration): ?>
                <span class="meta-item duration">
                  
                    <?php echo gmdate('i:s', $track->track_duration); ?>
                </span>
                <?php endif; ?>
                
                <?php if ($track->bpm): ?>
                <span class="meta-item bpm">
                   
                    <?php echo $track->bpm; ?> BPM
                </span>
                <?php endif; ?>
                
                <span class="meta-item plays">
                   
                    <?php echo $stats->plays; ?> прослушиваний
                </span>
            </div>
            
            <!-- Плеер -->
            <div class="track-single__player">
                <audio id="track-player" controls preload="metadata">
                    <source src="<?php echo esc_url($track->track_file_path); ?>" 
                            type="audio/mpeg">
                </audio>
            </div>
            
            <!-- Кнопки взаимодействия -->
            <div class="track-single__actions">
                <button class="action-btn action-btn--like <?php echo $is_liked ? 'active' : ''; ?>" 
                        data-track-id="<?php echo $track->id; ?>"
                        data-action="like">
                   
                    <span class="action-count"><?php echo $stats->likes; ?></span>
                    <span class="action-label">Нравится</span>
                </button>
                
                <button class="action-btn action-btn--bookmark <?php echo $is_bookmarked ? 'active' : ''; ?>" 
                        data-track-id="<?php echo $track->id; ?>"
                        data-action="bookmark">
                   
                    <span class="action-count"><?php echo $stats->bookmarks; ?></span>
                    <span class="action-label">В закладки</span>
                </button>
                
                <button class="action-btn action-btn--share" data-track-id="<?php echo $track->id; ?>">
                  
                    <span class="action-label">Поделиться</span>
                </button>
            </div>
            
            <!-- Теги / характеристики -->
            <?php if ($track->mood_id || $track->theme_id || $track->genre_id): ?>
            <div class="track-single__tags">
                <?php if ($track->mood_id): ?>
                <span class="tag"><?php echo esc_html($track->mood_name ?? 'Настроение'); ?></span>
                <?php endif; ?>
                
                <?php if ($track->theme_id): ?>
                <span class="tag"><?php echo esc_html($track->theme_name ?? 'Тема'); ?></span>
                <?php endif; ?>
                
                <?php if ($track->genre_id): ?>
                <span class="tag"><?php echo esc_html($track->genre_name ?? 'Жанр'); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Блок со стихами -->
    <?php if ($poem && !empty($poem->poem_text)): ?>
    <div class="track-single__poem">
        <h2 class="section-title">Стихотворение</h2>
        <div class="poem-text">
            <?php echo nl2br(esc_html($poem->poem_text)); ?>
        </div>
        
        <?php if ($poem->poet): ?>
        <div class="poem-author">
            — <?php echo esc_html($poem->poet->full_name_first); ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Блок с комментариями -->
    <div class="track-single__comments">
        <h2 class="section-title">
            Комментарии 
            <?php if ($comments_count): ?>
                <span class="comments-count">(<?php echo $comments_count; ?>)</span>
            <?php endif; ?>
        </h2>
        
        <!-- Форма комментария (подключается из шаблона) -->
        <?php include BM_CORE_PATH . 'Templates/comments/comment-form.php'; ?>
        
        <!-- Дерево комментариев -->
        <?php if (!empty($comments_tree)): ?>
            <?php include BM_CORE_PATH . 'Templates/comments/comment-tree.php'; ?>
        <?php else: ?>
            <p class="no-comments">Пока нет комментариев. Будьте первым!</p>
        <?php endif; ?>
    </div>
    
    <!-- Рекомендации -->
    <?php
    $track_service = new BM\Services\TrackService();
    $recommendations = $track_service->getRecommendations($track_id, 6);
    if (!empty($recommendations)):
    ?>
    <div class="track-single__recommendations">
        <h2 class="section-title">Вам также может понравиться</h2>
        <div class="recommendations-grid">
            <?php foreach ($recommendations as $rec_track): ?>
                <?php include BM_CORE_PATH . 'Templates/track-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<?php get_footer(); ?>