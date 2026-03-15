<?php
/**
 * Универсальная карточка для трека, стиха или поэта
 * @param object $item Объект, полученный из соответствующего репозитория
 */
if (!isset($item)) return;

// Определяем тип по наличию характерных свойств
if (property_exists($item, 'track_name')) {
    // Это трек
    $type = 'track';
    $title = $item->track_name;
    $url = $item->url ?? home_url('/track/' . $item->id . '/');
    $subtitle = $item->poet->short_name ?? '';
    $image = $item->track_img ?? '';
    $excerpt = $item->caption ?? '';
    $meta = gmdate('i:s', $item->track_duration) . ' • ' . number_format($item->plays_count ?? 0) . ' прослушиваний';
} elseif (property_exists($item, 'poem_text')) {
    // Это стихотворение
    $type = 'poem';
    $title = $item->name;
    $url = $item->url ?? home_url('/poem/' . $item->poem_slug . '/');
    $subtitle = $item->poet->short_name ?? '';
    $image = ''; // для стихов можно использовать изображение по умолчанию
    $excerpt = wp_trim_words(strip_tags($item->poem_text), 20);
    $meta = $item->poet ? 'Автор: ' . $item->poet->short_name : '';
} elseif (property_exists($item, 'last_name')) {
    // Это поэт
    $type = 'poet';
    $title = $item->short_name ?? $item->full_name_first ?? $item->last_name . ' ' . $item->first_name;
    $url = $item->url ?? home_url('/poet/' . $item->poet_slug . '/');
    $subtitle = 'Поэт';
    $image = ''; // можно добавить фото поэта
    $excerpt = ''; // биография
    $meta = '';
} else {
    return; // неизвестный тип
}
?>
<div class="bm-card bm-card--<?= $type ?>">
    <?php if ($image): ?>
    <div class="bm-card__image">
        <img src="<?= esc_url($image) ?>" alt="<?= esc_attr($title) ?>">
    </div>
    <?php endif; ?>
    <div class="bm-card__content">
        <h3 class="bm-card__title">
            <a href="<?= esc_url($url) ?>"><?= esc_html($title) ?></a>
        </h3>
        <?php if ($subtitle): ?>
        <div class="bm-card__subtitle"><?= esc_html($subtitle) ?></div>
        <?php endif; ?>
        <?php if ($excerpt): ?>
        <p class="bm-card__excerpt"><?= esc_html($excerpt) ?></p>
        <?php endif; ?>
        <?php if ($meta): ?>
        <div class="bm-card__meta"><?= esc_html($meta) ?></div>
        <?php endif; ?>
    </div>
</div>