<?php
/**
 * Карточка стихотворения для отображения в списках
 * @var object $poem Объект стихотворения
 * @var bool $show_author Показывать автора (по умолчанию true)
 * @var bool $show_excerpt Показывать отрывок (по умолчанию true)
 */
if (!$poem) return;

$show_author = $show_author ?? true;
$show_excerpt = $show_excerpt ?? true;

// Получаем первые строки стихотворения для превью
$excerpt = '';
if ($show_excerpt && !empty($poem->poem_text)) {
    $lines = explode("\n", strip_tags($poem->poem_text));
    $excerpt = implode(' ', array_slice($lines, 0, 3));
    if (count($lines) > 3) $excerpt .= '...';
}
?>

<div class="bm-poem-card" data-poem-id="<?= $poem->id ?>">
    <div class="bm-poem-card__content">
        <h3 class="bm-poem-card__title">
            <a href="<?= esc_url($poem->url) ?>">
                <?= esc_html($poem->name) ?>
            </a>
        </h3>
        
        <?php if ($show_author && $poem->poet): ?>
        <div class="bm-poem-card__author">
            <a href="<?= esc_url($poem->poet->url) ?>">
                <?= esc_html($poem->poet->short_name) ?>
            </a>
        </div>
        <?php endif; ?>
        
        <?php if ($excerpt): ?>
        <div class="bm-poem-card__excerpt">
            <?= esc_html($excerpt) ?>
        </div>
        <?php endif; ?>
        
        <div class="bm-poem-card__meta">
            <span class="bm-poem-card__tracks">
                🎵 <?= $poem->tracks_count ?? 0 ?> треков
            </span>
            <span class="bm-poem-card__date">
                📅 <?= date_i18n('d.m.Y', strtotime($poem->created_at)) ?>
            </span>
        </div>
    </div>
</div>