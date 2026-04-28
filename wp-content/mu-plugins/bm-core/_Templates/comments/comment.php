<?php
/**
 * Один комментарий
 * @var object $comment
 * @var bool $is_child Флаг дочернего комментария
 */
$level = $comment->level ?? 0;
$avatar = get_avatar($comment->author_email, 40);
$likes = (int) get_comment_meta($comment->id, 'likes', true);
$is_author = $comment->user_id && $comment->user_id == get_the_author_meta('ID');
?>

<div class="bm-comment" id="comment-<?php echo $comment->id; ?>" 
     data-id="<?php echo $comment->id; ?>" 
     data-level="<?php echo $level; ?>">
    
    <div class="bm-comment-header">
        <div class="bm-comment-avatar">
            <?php echo $avatar; ?>
        </div>
        <span class="bm-comment-author">
            <?php echo esc_html($comment->author); ?>
        </span>
        <span class="bm-comment-date">
            <?php echo date_i18n('d.m.Y H:i', strtotime($comment->created_at)); ?>
        </span>
        
        <?php if ($is_author): ?>
            <span class="bm-comment-badge author">Автор</span>
        <?php endif; ?>
        
        <?php if (!$comment->is_approved): ?>
            <span class="bm-comment-badge moderated">На модерации</span>
        <?php endif; ?>
    </div>
    
    <div class="bm-comment-content">
        <?php echo nl2br(esc_html($comment->content)); ?>
    </div>
    
    <div class="bm-comment-actions">
        <button class="bm-comment-reply" data-id="<?php echo $comment->id; ?>">
            Ответить
        </button>
        
        <button class="bm-comment-like <?php echo $likes ? 'liked' : ''; ?>" 
                data-id="<?php echo $comment->id; ?>">
            ❤️ <span class="like-count"><?php echo $likes; ?></span>
        </button>
    </div>
</div>