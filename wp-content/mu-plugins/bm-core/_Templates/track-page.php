<?php
$track = get_query_var('bm_track');
if (!$track) {
    wp_die('Трек не найден');
}
get_header();
?>
<!-- В шаблоне - два типа ссылок -->
<!-- <a href="/track/<?= $track->track_slug ?>">Смотреть</a> -->
<!-- <a href="/admin/track/<?= $track->id ?>">Редактировать</a> -->
<div class="bm-track-page">
    <h1><?php echo esc_html($track->track_name); ?></h1>
    <?php if ($track->poet): ?>
        <p>Поэт: <a href="<?php echo esc_url($track->poet->url); ?>"><?php echo esc_html($track->poet->short_name); ?></a></p>
    <?php endif; ?>
    <?php if ($track->poem): ?>
        <p>Стихотворение: <a href="<?php echo esc_url($track->poem->url); ?>"><?php echo esc_html($track->poem->name); ?></a></p>
    <?php endif; ?>
    <!-- Плеер и информация о треке -->
</div>

// Комментарии

// Подключаем стили и скрипты
wp_enqueue_style('bm-comments', BM_TE_PLUGIN_URL . 'assets/css/comments.css');
wp_enqueue_script('bm-comments', BM_TE_PLUGIN_URL . 'assets/js/comments.js', ['jquery'], '1.0', true);

wp_localize_script('bm-comments', 'bm_comments', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('bm_te_nonce'),
]);

// Получаем комментарии
$comment_service = new \BM\Services\CommentService();
$comments_tree = $comment_service->getTree($track_id);
$comments_count = $comment_service->comment_repo->getCount($track_id);
?>

<div class="bm-comments">
    <h2 class="bm-comments-title">
        Комментарии (<?php echo $comments_count; ?>)
    </h2>
    
    <?php include BM_TE_PLUGIN_DIR . 'templates/comments/comment-form.php'; ?>
    
    <?php if (!empty($comments_tree)): ?>
        <?php include BM_TE_PLUGIN_DIR . 'templates/comments/comment-tree.php'; ?>
        
        <?php if ($comments_count > 10): ?>
            <div class="bm-comments-load-more" 
                 data-track-id="<?php echo $track_id; ?>" 
                 data-page="1">
                Загрузить ещё комментарии
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php get_footer(); ?>