<?php
$poem = get_query_var('bm_poem');
if (!$poem) {
    wp_die('Стихотворение не найдено');
}
get_header();
?>
<div class="bm-poem-page">
    <h1><?php echo esc_html($poem->name); ?></h1>
    <?php if ($poem->poet): ?>
        <p class="bm-poem-author">Автор: <a href="<?php echo esc_url($poem->poet->url); ?>"><?php echo esc_html($poem->poet->short_name); ?></a></p>
    <?php endif; ?>
    <div class="bm-poem-text">
        <?php echo nl2br(esc_html($poem->poem_text)); ?>
    </div>
    <!-- Список треков на это стихотворение -->
</div>
<?php get_footer(); ?>