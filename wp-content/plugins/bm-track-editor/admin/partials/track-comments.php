<div class="bm-te-comments">
    <h3>Комментарии к треку</h3>
    
    <div class="bm-te-comments-stats">
        Всего: <?php echo $stats->total ?? 0; ?>,
        Одобрено: <?php echo $stats->approved ?? 0; ?>
    </div>
    
    <?php if (empty($comments)): ?>
        <p>Нет комментариев</p>
    <?php else: ?>
        <div class="bm-te-comments-tree">
            <?php foreach ($comments as $comment): ?>
                <?php self::render_comment_thread($comment); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="bm-te-add-comment">
        <h4>Добавить комментарий</h4>
        <form class="bm-te-comment-form" data-track="<?php echo $track_id; ?>">
            <input type="text" name="author" placeholder="Имя" required>
            <input type="email" name="email" placeholder="Email">
            <textarea name="content" placeholder="Комментарий" required></textarea>
            <button type="submit" class="button">Отправить</button>
        </form>
    </div>
</div>

<?php
public static function render_comment_thread($comment, $depth = 0) {
    ?>
    <div class="bm-te-comment" style="margin-left: <?php echo $depth * 20; ?>px">
        <div class="bm-te-comment-header">
            <strong><?php echo esc_html($comment->author); ?></strong>
            <span class="bm-te-comment-date"><?php echo date_i18n('d.m.Y H:i', strtotime($comment->created_at)); ?></span>
            <?php if (!$comment->is_approved): ?>
                <span class="bm-te-comment-moderation">(на модерации)</span>
            <?php endif; ?>
        </div>
        <div class="bm-te-comment-content">
            <?php echo nl2br(esc_html($comment->content)); ?>
        </div>
        <div class="bm-te-comment-actions">
            <button class="button button-small bm-te-comment-reply" data-id="<?php echo $comment->id; ?>">
                Ответить
            </button>
            <?php if (!$comment->is_approved): ?>
                <button class="button button-small bm-te-comment-approve" data-id="<?php echo $comment->id; ?>">
                    Одобрить
                </button>
            <?php endif; ?>
            <button class="button button-small bm-te-comment-delete" data-id="<?php echo $comment->id; ?>">
                Удалить
            </button>
        </div>
        
        <?php if (!empty($comment->children)): ?>
            <div class="bm-te-comment-children">
                <?php foreach ($comment->children as $child): ?>
                    <?php self::render_comment_thread($child, $depth + 1); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}