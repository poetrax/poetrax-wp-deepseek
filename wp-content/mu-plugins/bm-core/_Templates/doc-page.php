<?php
/**
 * Шаблон страницы документа
 * @var object $doc Объект документа
 */
if (!$doc) {
    wp_die('Документ не найден');
}

get_header();
?>

<div class="bm-doc-page">
    <article class="bm-doc">
        <header class="bm-doc__header">
            <h1 class="bm-doc__title">
                <?= esc_html($doc->document_name) ?>
            </h1>
            <div class="bm-doc__meta">
                Версия: <?= esc_html($doc->document_version) ?>
                <br>
                Дата: <?= $doc->date_formatted ?>
            </div>
        </header>
        
        <div class="bm-doc__content">
            <?= $doc->clean_text ?>
        </div>
        
        <?php
        // Получаем другие версии
        $doc_repo = new BM\Repositories\DocRepository();
        $versions = $doc_repo->getVersions($doc->document_type, 5);
        ?>
        
        <?php if (count($versions) > 1): ?>
        <footer class="bm-doc__footer">
            <h3>Другие версии документа</h3>
            <ul class="bm-doc-versions">
                <?php foreach ($versions as $version): ?>
                    <?php if ($version->id != $doc->id): ?>
                    <li>
                        <a href="<?= home_url('/doc/' . $version->id) ?>">
                            Версия <?= esc_html($version->document_version) ?>
                            от <?= date_i18n('d.m.Y', strtotime($version->created_at)) ?>
                        </a>
                    </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </footer>
        <?php endif; ?>
    </article>
</div>

<?php get_footer(); ?>