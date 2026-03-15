<?php
$track_repo = new BM\Repositories\TrackRepository();
$poem_repo  = new BM\Repositories\PoemRepository();
$poet_repo  = new BM\Repositories\PoetRepository();

$recent_tracks = $track_repo->getRecent(4);

$popular_poems = $poem_repo->getPopular(3);

$random_poets = $poet_repo->getRandom(3);

get_header(); 


?>




<div class="bm-mixed-grid">
    <h2>Последние треки</h2>
    <div class="bm-grid">
        <?php foreach ($recent_tracks as $item) echo bm_render_card($item); ?>
    </div>

    <h2>Популярные стихи</h2>
    <div class="bm-grid">
        <?php foreach ($popular_poems as $item) echo bm_render_card($item); ?>
    </div>

    <h2>Случайные поэты</h2>
    <div class="bm-grid">
        <?php foreach ($random_poets as $item) echo bm_render_card($item); ?>
    </div>
</div>

<?php get_sidebar(); ?>
<?php
get_footer();


