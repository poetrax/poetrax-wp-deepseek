<?php
/**
 * Список треков
 * 
 * @var array $tracks Массив треков
 * @var int $total_pages Всего страниц
 * @var int $current_page Текущая страница
 */
?>
<div class="wrap bm-te-wrap">
    <div class="bm-te-header">
        <h1 class="wp-heading-inline"><?php _e('Треки', 'bm-track-editor'); ?></h1>
        <a href="?page=bm-track-editor" class="page-title-action bm-te-button bm-te-button-primary">
            <?php _e('Добавить новый', 'bm-track-editor'); ?>
        </a>
    </div>
    
    <?php if (get_option('bm_te_missing_tables')): ?>
    <div class="bm-te-notice bm-te-notice-warning">
        <p><strong><?php _e('Внимание!', 'bm-track-editor'); ?></strong> 
        <?php _e('Отсутствуют некоторые таблицы базы данных:', 'bm-track-editor'); ?>
        <?php echo implode(', ', get_option('bm_te_missing_tables')); ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Фильтры -->
    <div class="bm-te-filters">
        <select id="bm-te-poet-filter">
            <option value=""><?php _e('Все поэты', 'bm-track-editor'); ?></option>
            <?php
          
            $poets = $this->connection->get_results("SELECT id, short_name FROM " . BM_TE_TABLE_POET . " ORDER BY last_name");
            foreach ($poets as $poet):
            ?>
            <option value="<?php echo $poet->id; ?>" <?php selected($_GET['poet_id'] ?? '', $poet->id); ?>>
                <?php echo esc_html($poet->short_name); ?>
            </option>
            <?php endforeach; ?>
        </select>
        
        <input type="text" id="bm-te-search" placeholder="<?php _e('Поиск...', 'bm-track-editor'); ?>">
    </div>
    
    <!-- Таблица треков -->
    <?php if (empty($tracks)): ?>
        <div class="bm-te-card">
            <p><?php _e('Нет треков', 'bm-track-editor'); ?></p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped bm-te-table">
            <thead>
                <tr>
                    <th width="50">ID</th>
                    <th><?php _e('Название', 'bm-track-editor'); ?></th>
                    <th><?php _e('Поэт', 'bm-track-editor'); ?></th>
                    <th><?php _e('Длительность', 'bm-track-editor'); ?></th>
                    <th><?php _e('Дата', 'bm-track-editor'); ?></th>
                    <th width="150"><?php _e('Действия', 'bm-track-editor'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tracks as $track): ?>
                <tr class="bm-te-track-row-<?php echo $track->id; ?>">
                    <td><?php echo $track->id; ?></td>
                    <td>
                        <strong><?php echo esc_html($track->track_name); ?></strong>
                        <?php if (BM_TE_Settings::get('show_preview_player') && !empty($track->track_file_path)): ?>
                        <button class="bm-te-preview-track" data-track="<?php echo $track->id; ?>">
                            ▶ <?php _e('Превью', 'bm-track-editor'); ?>
                        </button>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($track->poet_name ?? ''); ?></td>
                    <td><?php echo $track->track_duration ? gmdate('i:s', $track->track_duration) : '—'; ?></td>
                    <td><?php echo date_i18n('d.m.Y', strtotime($track->created_at)); ?></td>
                    <td class="bm-te-actions">
                        <a href="?page=bm-track-editor&id=<?php echo $track->id; ?>" class="button button-small">
                            <?php _e('Ред.', 'bm-track-editor'); ?>
                        </a>
                        <button class="button button-small bm-te-delete" data-id="<?php echo $track->id; ?>">
                            <?php _e('Уд.', 'bm-track-editor'); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Пагинация -->
        <?php if ($total_pages > 1): ?>
        <div class="bm-te-pagination">
            <?php
            echo paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $current_page
            ]);
            ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Скрытый плеер для превью -->
<div id="bm-te-preview-player" style="display: none;"></div>

<script>
jQuery(document).ready(function($) {
    $('.bm-te-preview-track').on('click', function() {
        var trackId = $(this).data('track');
        // TODO: загрузить и проиграть трек
        alert('Превью трека #' + trackId);
    });
});
</script>
