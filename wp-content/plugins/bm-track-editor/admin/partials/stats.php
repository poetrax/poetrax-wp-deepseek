<div class="wrap">
    <h1><?php _e('Статистика', 'bm-track-editor'); ?></h1>
    
    <div class="bm-te-card">
        <h3>Общая статистика</h3>
        <table class="wp-list-table widefat fixed striped">
            <tr>
                <td><strong><?php _e('Всего треков:', 'bm-track-editor'); ?></strong></td>
                <td><?php echo $global_stats['total_tracks'] ?? 0; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Всего стихов:', 'bm-track-editor'); ?></strong></td>
                <td><?php echo $global_stats['total_poems'] ?? 0; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Всего поэтов:', 'bm-track-editor'); ?></strong></td>
                <td><?php echo $global_stats['total_poets'] ?? 0; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Прослушиваний сегодня:', 'bm-track-editor'); ?></strong></td>
                <td><?php echo $global_stats['plays_today'] ?? 0; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Всего прослушиваний:', 'bm-track-editor'); ?></strong></td>
                <td><?php echo $global_stats['total_plays'] ?? 0; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Всего отметок нравится:', 'bm-track-editor'); ?></strong></td>
                <td><?php echo $global_stats['total_likes'] ?? 0; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Всего закладок:', 'bm-track-editor'); ?></strong></td>
                <td><?php echo $global_stats['total_bookmarks'] ?? 0; ?></td>
            </tr>
        </table>
    </div>
</div>
