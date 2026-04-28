<?php
/**
 * Форма добавления комментария
 * @param int $track_id ID трека
 */
?>

<div class="bm-comment-form bm-comment-form-main">
    <h3>Оставить комментарий</h3>
    
    <form class="bm-comment-form" data-track-id="<?php echo $track_id; ?>">
        <input type="hidden" name="track_id" value="<?php echo $track_id; ?>">
        <input type="hidden" name="parent_id" class="bm-comment-parent" value="0">
        
        <div class="bm-comment-message"></div>
        
        <div class="bm-form-row">
            <label for="comment-author">Имя *</label>
            <input type="text" id="comment-author" name="author" 
                   placeholder="Ваше имя" required>
        </div>
        
        <div class="bm-form-row">
            <label for="comment-email">Email</label>
            <input type="email" id="comment-email" name="email" 
                   placeholder="your@email.com">
        </div>
        
        <div class="bm-form-row">
            <label for="comment-url">Сайт</label>
            <input type="url" id="comment-url" name="url" 
                   placeholder="https://your-site.com">
        </div>
        
        <div class="bm-form-row">
            <label for="comment-content">Комментарий *</label>
            <textarea id="comment-content" name="content" 
                      placeholder="Ваш комментарий..." required></textarea>
        </div>
        
        <div class="bm-form-row checkbox">
            <input type="checkbox" id="comment-save" name="save_data" checked>
            <label for="comment-save">Запомнить мои данные</label>
        </div>
        
        <div class="bm-form-submit">
            <button type="submit" class="bm-submit-button">Отправить</button>
        </div>
    </form>
</div>