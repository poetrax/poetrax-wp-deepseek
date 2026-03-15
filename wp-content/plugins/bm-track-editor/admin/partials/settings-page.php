<div class="wrap bm-te-settings-page">
    <h1><?php _e('Настройки редактора треков', 'bm-track-editor'); ?></h1>
    
    <div class="bm-te-settings-layout">
        <!-- Основная форма настроек -->
        <div class="bm-te-settings-main">
            <form method="post" action="options.php" class="bm-te-settings-form">
                <?php
                settings_fields('bm_te_settings_group');
                do_settings_sections('bm-track-settings');
                ?>
                
                <div class="bm-te-settings-actions">
                    <?php submit_button(__('Сохранить настройки', 'bm-track-editor'), 'primary', 'submit', false); ?>
                    <button type="button" class="button bm-te-reset-defaults">
                        <?php _e('Сбросить к умолчанию', 'bm-track-editor'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Боковая панель с информацией -->
        <div class="bm-te-settings-sidebar">
            <div class="bm-te-info-card">
                <h3><?php _e('Информация о плагине', 'bm-track-editor'); ?></h3>
                <ul>
                    <li><strong><?php _e('Версия:', 'bm-track-editor'); ?></strong> <?php echo BM_TE_VERSION; ?></li>
                    <li><strong><?php _e('Автор:', 'bm-track-editor'); ?></strong> BestMZ</li>
                    <li><strong><?php _e('Сайт:', 'bm-track-editor'); ?></strong> <a href="https://poetrax.ru" target="_blank">poetrax.ru</a></li>
                </ul>
            </div>
            
            <div class="bm-te-info-card">
                <h3><?php _e('Статистика', 'bm-track-editor'); ?></h3>
                <ul>
                    <li><strong><?php _e('Треков:', 'bm-track-editor'); ?></strong> <?php echo BM_TE_Admin::get_tracks_count(); ?></li>
                    <li><strong><?php _e('FULLTEXT индексы:', 'bm-track-editor'); ?></strong> 
                        <?php echo BM_TE_Admin::check_fulltext_index() ? '✅' : '❌'; ?>
                    </li>
                </ul>
            </div>
            
            <div class="bm-te-info-card">
                <h3><?php _e('Быстрые действия', 'bm-track-editor'); ?></h3>
                <div class="bm-te-quick-actions">
                    <a href="?page=bm-track-editor" class="button button-primary">
                        <?php _e('➕ Новый трек', 'bm-track-editor'); ?>
                    </a>
                    <a href="?page=bm-te-cache-clear" class="button">
                        <?php _e('🗑️ Очистить кэш', 'bm-track-editor'); ?>
                    </a>
                </div>
            </div>
            
            <div class="bm-te-info-card">
                <h3><?php _e('Поддержка', 'bm-track-editor'); ?></h3>
                <p><?php _e('Если у вас возникли вопросы или предложения, пожалуйста, свяжитесь с нами:', 'bm-track-editor'); ?></p>
                <ul>
                    <li>📧 <a href="mailto:support@poetrax.ru">support@poetrax.ru</a></li>
                    <li>📚 <a href="#" target="_blank"><?php _e('Документация', 'bm-track-editor'); ?></a></li>
                    <li>🐛 <a href="#" target="_blank"><?php _e('Сообщить об ошибке', 'bm-track-editor'); ?></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.bm-te-settings-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 30px;
    margin-top: 20px;
}

.bm-te-settings-main {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.bm-te-settings-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.bm-te-info-card {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.bm-te-info-card h3 {
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.bm-te-info-card ul {
    margin: 0;
    list-style: none;
}

.bm-te-info-card li {
    margin-bottom: 10px;
}

.bm-te-quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.bm-te-quick-actions .button {
    text-align: center;
}

.bm-te-settings-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

@media (max-width: 1200px) {
    .bm-te-settings-layout {
        grid-template-columns: 1fr;
    }
}
</style>