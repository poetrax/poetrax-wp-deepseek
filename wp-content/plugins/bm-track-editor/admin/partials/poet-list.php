<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Поэты', 'bm-track-editor'); ?></h1>
    
    <?php if (empty($poets)): ?>
        <p><?php _e('Нет поэтов', 'bm-track-editor'); ?></p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php _e('Название', 'bm-track-editor'); ?></th>
                    <th><?php _e('Поэт', 'bm-track-editor'); ?></th>
                    <th><?php _e('Треков', 'bm-track-editor'); ?></th>
                    <th><?php _e('Дата', 'bm-track-editor'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($poets as $poet): 
                    $tracks_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM " . BM_TE_TABLE_TRACK . " WHERE poet_id = %d",
                        $poet->id
                    ));
                ?>
                <tr>
                    <td><?php echo $poet->id; ?></td>
                    <td><?php echo esc_html($poet->name); ?></td>
                    <td><?php echo esc_html($poet->poet_name ?? ''); ?></td>
                    <td><?php echo $tracks_count; ?></td>
                    <td><?php echo date_i18n('d.m.Y', strtotime($poet->created_at)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $current_page
                ]); ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>