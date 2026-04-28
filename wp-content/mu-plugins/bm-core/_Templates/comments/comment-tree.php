<?php
/**
 * Дерево комментариев
 * @var array $comments Дерево комментариев
 */
?>

<div class="bm-comments-tree">
    <?php foreach ($comments as $comment): ?>
        <?php include 'comment.php'; ?>
        
        <?php if (!empty($comment->children)): ?>
            <div class="bm-comment-children">
                <?php foreach ($comment->children as $child): ?>
                    <?php 
                    $comment = $child;
                    include 'comment.php'; 
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>