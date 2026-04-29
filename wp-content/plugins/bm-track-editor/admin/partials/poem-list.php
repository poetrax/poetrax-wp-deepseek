<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Стихотворения', 'bm-track-editor'); ?></h1>
    
    <?php if (empty($poems)): ?>
        <p><?php _e('Нет стихотворений', 'bm-track-editor'); ?></p>
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
                <?php foreach ($poems as $poem): 
                    $tracks_count = $this->connection->get_var(
                        "SELECT COUNT(*) FROM " . BM_TE_TABLE_TRACK . " WHERE poem_id = %d",
                        $poem->id
                    );
                ?>
                <tr>
                    <td><?php echo $poem->id; ?></td>
                    <td><?php echo esc_html($poem->name); ?></td>
                    <td><?php echo esc_html($poem->poet_name ?? ''); ?></td>
                    <td><?php echo $tracks_count; ?></td>
                    <td><?php echo date_i18n('d.m.Y', strtotime($poem->created_at)); ?></td>
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