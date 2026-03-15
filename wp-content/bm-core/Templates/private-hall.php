<?php
/**
 * Template Name: Личный кабинет
 */

get_header();

$user_id = get_current_user_id();
if (!$user_id) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$interaction_service = new BM\Services\InteractionService();
$track_repo = new BM\Repositories\TrackRepository();

// Получаем ID треков для разных разделов
$my_track_ids = $track_repo->get_user_tracks($user_id, 50); // Треки пользователя
$liked_ids = $interaction_service->get_user_tracks($user_id, 'like', 50);
$bookmarked_ids = $interaction_service->get_user_tracks($user_id, 'bookmark', 50);

// Получаем полные объекты треков
$my_tracks = $track_repo->get_by_ids($my_track_ids);
$liked_tracks = $track_repo->get_by_ids($liked_ids);
$bookmarked_tracks = $track_repo->get_by_ids($bookmarked_ids);

// Рекомендации
$recommended_tracks = $track_repo->get_recommendations_for_user($user_id, 20);
?>

<div class="profile-page">
    <h1 class="profile-title">Личный кабинет</h1>
    
    <!-- Навигация по разделам -->
    <div class="profile-tabs">
        <button class="profile-tab active" data-tab="my">Мои треки</button>
        <button class="profile-tab" data-tab="liked">Понравившиеся</button>
        <button class="profile-tab" data-tab="bookmarked">Закладки</button>
        <button class="profile-tab" data-tab="recommended">Рекомендации</button>
    </div>
    
    <!-- Мои треки -->
    <div class="profile-tab-content active" id="tab-my">
        <h2>Мои треки</h2>
        <?php if (empty($my_tracks)): ?>
            <p class="profile-empty">У вас пока нет своих треков</p>
        <?php else: ?>
            <div class="tracks-grid tracks-grid--profile">
                <?php foreach ($my_tracks as $track): ?>
                    <?php include BM_CORE_PATH . 'Templates/track-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Понравившиеся -->
    <div class="profile-tab-content" id="tab-liked">
        <h2>Понравившиеся треки</h2>
        <?php if (empty($liked_tracks)): ?>
            <p class="profile-empty">У вас пока нет понравившихся треков</p>
        <?php else: ?>
            <div class="tracks-grid tracks-grid--profile">
                <?php foreach ($liked_tracks as $track): ?>
                    <?php include BM_CORE_PATH . 'Templates/track-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Закладки -->
    <div class="profile-tab-content" id="tab-bookmarked">
        <h2>Закладки</h2>
        <?php if (empty($bookmarked_tracks)): ?>
            <p class="profile-empty">У вас пока нет закладок</p>
        <?php else: ?>
            <div class="tracks-grid tracks-grid--profile">
                <?php foreach ($bookmarked_tracks as $track): ?>
                    <?php include BM_CORE_PATH . 'Templates/track-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Рекомендации -->
    <div class="profile-tab-content" id="tab-recommended">
        <h2>Рекомендации для вас</h2>
        <?php if (empty($recommended_tracks)): ?>
            <p class="profile-empty">Скоро появятся рекомендации</p>
        <?php else: ?>
            <div class="tracks-grid tracks-grid--profile">
                <?php foreach ($recommended_tracks as $track): ?>
                    <?php include BM_CORE_PATH . 'Templates/track-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Переключение вкладок
    $('.profile-tab').on('click', function() {
        const tab = $(this).data('tab');
        
        $('.profile-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.profile-tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });
});
</script>

<style>
.profile-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 20px;
}

.profile-title {
    margin-bottom: 30px;
    font-size: 32px;
}

.profile-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 10px;
}

.profile-tab {
    background: none;
    border: none;
    padding: 10px 20px;
    font-size: 16px;
    font-weight: 500;
    color: #666;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.2s;
}

.profile-tab:hover {
    background: #f5f5f5;
    color: #333;
}

.profile-tab.active {
    background: #007cba;
    color: #fff;
}

.profile-tab-content {
    display: none;
}

.profile-tab-content.active {
    display: block;
}

.profile-empty {
    text-align: center;
    padding: 60px;
    background: #f9f9f9;
    border-radius: 8px;
    color: #999;
}

.tracks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

.tracks-grid--profile .track-card {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.tracks-grid--profile .track-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .profile-tabs {
        flex-wrap: wrap;
    }
    
    .tracks-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>