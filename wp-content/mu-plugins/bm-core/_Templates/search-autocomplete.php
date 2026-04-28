<?php
// БЫСТРЫЕ ПОДСКАЗКИ ДЛЯ ЖИВОГО ПОИСКА
$has_results = false;
?>

<?php if (!empty($results['tracks'])): ?>
    <?php $has_results = true; ?>
    <div class="bm-search-section">
        <h4>Треки</h4>
        <?php foreach ($results['tracks'] as $track): ?>
            <div class="bm-search-item">
                <a href="<?= esc_url($track->permalink ?? '#') ?>">
                    <strong><?= esc_html($track->track_name) ?></strong>
                    <?php if ($track->poet_name): ?>
                        <span class="type"><?= esc_html($track->poet_name) ?></span>
                    <?php endif; ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($results['poems'])): ?>
    <?php $has_results = true; ?>
    <div class="bm-search-section">
        <h4>Стихотворения</h4>
        <?php foreach ($results['poems'] as $poem): ?>
            <div class="bm-search-item">
                <a href="/poem/<?= esc_attr($poem->poem_slug) ?>">
                    <strong><?= esc_html($poem->name) ?></strong>
                    <?php if ($poem->poet_name): ?>
                        <span class="type"><?= esc_html($poem->poet_name) ?></span>
                    <?php endif; ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($results['poets'])): ?>
    <?php $has_results = true; ?>
    <div class="bm-search-section">
        <h4>Поэты</h4>
        <?php foreach ($results['poets'] as $poet): ?>
            <div class="bm-search-item">
                <a href="/poet/<?= esc_attr($poet->poet_slug) ?>">
                    <?= esc_html($poet->full_name_first ?: $poet->short_name) ?>
                    <span class="type">Поэт</span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!$has_results): ?>
    <div class="bm-search-empty">
        Ничего не найдено
    </div>
<?php endif; ?>