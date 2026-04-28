<?php
/**
 * Страница поэта с полной интеграцией сервисов
 */
$poet = get_query_var('bm_poet');
if (!$poet) {
    wp_die('Поэт не найден');
}

use BM\Services\TrackService;
use BM\Services\StatsService;
use BM\Services\PlayerService;

$track_service = new TrackService();
$stats_service = new StatsService();

$tracks = $track_service->track_repo->getByPoet($poet->id, 12);
$poems = $track_service->poem_repo->getByPoet($poet->id, 12);
$stats = $stats_service->getPoetStats($poet->id);

get_header();
?>

<div class="bm-poet-page">
    
    <!-- Шапка поэта -->
    <div class="bm-poet-header">
        <?php if (!empty($poet->avatar)): ?>
        <div class="bm-poet-avatar">
            <img src="<?= esc_url($poet->avatar) ?>" alt="<?= esc_attr($poet->short_name) ?>">
        </div>
        <?php endif; ?>
        
        <div class="bm-poet-info">
            <h1><?= esc_html($poet->full_name_first) ?></h1>
            <?php if (!empty($poet->years_life)): ?>
            <div class="bm-poet-years"><?= esc_html($poet->years_life) ?></div>
            <?php endif; ?>
            
            <!-- Статистика -->
            <div class="bm-poet-stats">
                <div class="bm-stat-item">
                    <span class="bm-stat-value"><?= $stats->poems_count ?></span>
                    <span class="bm-stat-label">стихов</span>
                </div>
                <div class="bm-stat-item">
                    <span class="bm-stat-value"><?= $stats->tracks_count ?></span>
                    <span class="bm-stat-label">треков</span>
                </div>
                <div class="bm-stat-item">
                    <span class="bm-stat-value"><?= $stats->total_plays ?></span>
                    <span class="bm-stat-label">прослушиваний</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Популярный трек (если есть) -->
    <?php if ($stats->popular_track): ?>
    <div class="bm-popular-track">
        <h2>Популярный трек</h2>
        <?php 
        $track = $track_service->track_repo->find($stats->popular_track->id);
        $track->player_html = PlayerService::renderPlayer($track, ['compact' => true]);
        include 'track-card.php'; 
        ?>
    </div>
    <?php endif; ?>
    
    <!-- Все треки -->
    <h2>Треки на стихи поэта</h2>
    <div class="bm-tracks-grid">
        <?php foreach ($tracks as $track): ?>
            <?php 
            $track->player_html = PlayerService::renderPlayer($track, ['compact' => true]);
            include 'track-card.php'; 
            ?>
        <?php endforeach; ?>
    </div>
    
    <!-- Стихи -->
    <h2>Стихотворения</h2>
    <div class="bm-poems-grid">
        <?php foreach ($poems as $poem): ?>
            <?php include 'poem-card.php'; ?>
        <?php endforeach; ?>
    </div>
    
    <!-- Рекомендации (если есть треки) -->
    <?php if (!empty($tracks)): ?>
    <h2>Вам также может понравиться</h2>
    <div class="bm-recommendations">
        <?php 
        $recommendations = $track_service->getRecommendations($tracks[0]->id, 6);
        foreach ($recommendations as $rec): 
            include 'track-card.php';
        endforeach; 
        ?>
    </div>
    <?php endif; ?>
    
</div>

<?php get_footer(); ?>