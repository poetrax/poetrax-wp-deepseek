<?php if (empty(array_filter($results))): ?>
    <div class="bm-search-no-results">
        Ничего не найдено по запросу "<?= esc_html($query) ?>"
    </div>
<?php else: ?>

    <?php if (!empty($results['tracks'])): ?>
        <div class="bm-search-section">
            <h3>Треки</h3>
            <div class="bm-track-grid">
                <?php foreach ($results['tracks'] as $track): ?>
                    <?php include BM_CORE_PATH . 'templates/track-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($results['poems'])): ?>
        <div class="bm-search-section">
            <h3>Стихотворения</h3>
            <div class="bm-poem-list">
                <?php foreach ($results['poems'] as $poem): ?>
                    <div class="bm-poem-item">
                        <a href="/poem/<?= $poem->poem_slug ?>">
                            <h4><?= esc_html($poem->name) ?></h4>
                            <?php if ($poem->poet_name): ?>
                                <span class="bm-poem-author">
                                    <?= esc_html($poem->poet_name) ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($results['poets'])): ?>
        <div class="bm-search-section">
            <h3>Поэты</h3>
            <div class="bm-poet-list">
                <?php foreach ($results['poets'] as $poet): ?>
                    <div class="bm-poet-item">
                        <a href="/poet/<?= $poet->poet_slug ?>">
                            <?= esc_html($poet->full_name_first ?: $poet->short_name) ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>