<?php
/**
 * Карточка поэта
 * @var object $poet Объект поэта
 */
if (!$poet) return;
?>

<div class="bm-poet-card" data-poet-id="<?= $poet->id ?>">
    <?php if (!empty($poet->avatar)): ?>
    <div class="bm-poet-card__avatar">
        <img src="<?= esc_url($poet->avatar) ?>" alt="<?= esc_attr($poet->short_name) ?>">
    </div>
    <?php endif; ?>
    
    <div class="bm-poet-card__info">
        <h3 class="bm-poet-card__name">
            <a href="<?= esc_url($poet->url) ?>">
                <?= esc_html($poet->short_name) ?>
            </a>
        </h3>
        
        <?php if (!empty($poet->years_life)): ?>
        <div class="bm-poet-card__years">
            <?= esc_html($poet->years_life) ?>
        </div>
        <?php endif; ?>
        
        <div class="bm-poet-card__stats">
            <span class="bm-poet-card__poems">
                📖 <?= $poet->poems_count ?? 0 ?> стихов
            </span>
            <span class="bm-poet-card__tracks">
                🎵 <?= $poet->tracks_count ?? 0 ?> треков
            </span>
        </div>
        
        <?php if (!empty($poet->genres)): ?>
        <div class="bm-poet-card__genres">
            <?php foreach (array_slice($poet->genres, 0, 3) as $genre): ?>
                <span class="bm-genre-tag"><?= esc_html($genre) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>