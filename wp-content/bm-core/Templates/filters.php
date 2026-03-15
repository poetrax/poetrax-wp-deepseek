<?php
$track_repo = new BM\Repositories\TrackRepository();
$master_data = $track_repo->getFilterMasterData();

$current_filters = $_GET['filters'] ?? [];
?>

<div class="bm-filters-panel">
    <form class="bm-filters-form" method="GET">
        
        <!-- НАСТРОЕНИЕ -->
        <div class="bm-filter-group">
            <h4>Настроение</h4>
            <select name="filters[mood_id]">
                <option value="">Любое</option>
                <?php foreach ($master_data['moods'] as $mood): ?>
                    <option value="<?= $mood->id ?>" <?= selected($current_filters['mood_id'] ?? '', $mood->id) ?>>
                        <?= esc_html($mood->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- ТЕМА -->
        <div class="bm-filter-group">
            <h4>Тема</h4>
            <select name="filters[theme_id]">
                <option value="">Любая</option>
                <?php foreach ($master_data['themes'] as $theme): ?>
                    <option value="<?= $theme->id ?>">
                        <?= esc_html($theme->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- ТЕМП -->
        <div class="bm-filter-group">
            <h4>Темп</h4>
            <select name="filters[temp_id]">
                <option value="">Любой</option>
                <?php foreach ($master_data['tempos'] as $tempo): ?>
                    <option value="<?= $tempo->id ?>">
                        <?= esc_html($tempo->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- ЖАНР -->
        <div class="bm-filter-group">
            <h4>Жанр</h4>
            <select name="filters[genre_id]">
                <option value="">Любой</option>
                <?php foreach ($master_data['genres'] as $genre): ?>
                    <option value="<?= $genre->id ?>">
                        <?= esc_html($genre->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- ГОЛОС -->
        <div class="bm-filter-group">
            <h4>Вокал</h4>
            <select name="filters[voice_gender]">
                <option value="">Любой</option>
                <?php foreach ($master_data['voice_genders'] as $gender): ?>
                    <option value="<?= $gender->id ?>">
                        <?= esc_html($gender->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- ИНСТРУМЕНТЫ -->
        <div class="bm-filter-group">
            <h4>Инструмент</h4>
            <select name="filters[instrument_id]">
                <option value="">Любой</option>
                <?php foreach ($master_data['instruments'] as $instrument): ?>
                    <option value="<?= $instrument->id ?>">
                        <?= esc_html($instrument->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- BPM ДИАПАЗОН -->
        <div class="bm-filter-group">
            <h4>Темп (BPM)</h4>
            <div class="bm-range">
                <input type="number" name="filters[bpm_min]" placeholder="От" min="40" max="200">
                <input type="number" name="filters[bpm_max]" placeholder="До" min="40" max="200">
            </div>
        </div>
        
        <!-- ТИП ИСПОЛНЕНИЯ -->
        <div class="bm-filter-group">
            <h4>Тип</h4>
            <label>
                <input type="radio" name="filters[performance_type]" value="song">
                Песня
            </label>
            <label>
                <input type="radio" name="filters[performance_type]" value="recitation">
                Чтец
            </label>
            <label>
                <input type="radio" name="filters[performance_type]" value="instrumental">
                Инструментал
            </label>
        </div>
        
        <button type="submit" class="bm-button">Применить</button>
        <a href="<?= remove_query_arg('filters') ?>" class="bm-button bm-button--outline">Сбросить</a>
    </form>
</div>