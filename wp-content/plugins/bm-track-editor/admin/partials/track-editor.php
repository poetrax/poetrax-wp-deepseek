<?php
/**
 * Редактор трека
 * 
 * @var int $track_id ID трека (0 для нового)
 * @var array $poets Список поэтов
 * @var array $moods Настроения
 * @var array $themes Темы
 * @var array $genres Жанры
 * @var array $instruments Инструменты
 */

// Получаем данные трека если редактирование
$track = null;
$details = null;

if ($track_id) {
    global $wpdb;
    $track = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . BM_TE_TABLE_TRACK . " WHERE id = %d",
        $track_id
    ));
    
    if ($track) {
        $details = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . BM_TE_TABLE_MUSIC_DETAIL . " WHERE track_id = %d",
            $track_id
        ));
    }
}
?>

<div class="wrap bm-te-wrap">
    <div class="bm-te-header">
        <h1><?php echo $track_id ? __('Редактирование трека', 'bm-track-editor') : __('Новый трек', 'bm-track-editor'); ?></h1>
    </div>
    
    <form id="bm-track-form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="track_id" value="<?php echo $track_id; ?>">
        
        <div class="bm-te-grid">
            <!-- Левая колонка - основные поля -->
            <div class="bm-te-main">
                <!-- Основная информация -->
                <div class="bm-te-card">
                    <h3><?php _e('Основная информация', 'bm-track-editor'); ?></h3>
                    
                    <div class="bm-te-form-row">
                        <label for="track_name"><?php _e('Название трека', 'bm-track-editor'); ?> *</label>
                        <input type="text" id="track_name" name="track_name" 
                               value="<?php echo esc_attr($track->track_name ?? ''); ?>" 
                               class="regular-text" required>
                    </div>
                    
                    <div class="bm-te-form-row">
                        <label for="track_slug"><?php _e('URL (slug)', 'bm-track-editor'); ?></label>
                        <input type="text" id="track_slug" name="track_slug" 
                               value="<?php echo esc_attr($track->track_slug ?? ''); ?>" 
                               class="regular-text">
                        <?php if (BM_TE_Settings::get('enable_auto_slug')): ?>
                        <p class="description"><?php _e('Оставьте пустым для автоматической генерации', 'bm-track-editor'); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="bm-te-form-row">
                        <label for="caption"><?php _e('Описание', 'bm-track-editor'); ?></label>
                        <textarea id="caption" name="caption" rows="4" class="large-text"><?php echo esc_textarea($track->caption ?? ''); ?></textarea>
                    </div>
                </div>
                
                <!-- Связь со стихотворением -->
                <div class="bm-te-card">
                    <h3><?php _e('Стихотворение', 'bm-track-editor'); ?></h3>
                    
                    <div class="bm-te-form-row">
                        <label for="poet_id"><?php _e('Поэт', 'bm-track-editor'); ?></label>
                        <select id="poet_id" name="poet_id">
                            <option value=""><?php _e('Выберите поэта', 'bm-track-editor'); ?></option>
                            <?php foreach ($poets as $poet): ?>
                            <option value="<?php echo $poet->id; ?>" <?php selected($track->poet_id ?? '', $poet->id); ?>>
                                <?php echo esc_html($poet->short_name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if (BM_TE_Settings::get('enable_poem_search')): ?>
                    <div class="bm-te-form-row">
                        <label for="poem_search"><?php _e('Поиск стихотворения', 'bm-track-editor'); ?></label>
                        <input type="text" id="poem_search" class="regular-text" 
                               placeholder="<?php _e('Введите название...', 'bm-track-editor'); ?>">
                        <input type="hidden" id="poem_id" name="poem_id" value="<?php echo $track->poem_id ?? ''; ?>">
                        
                        <div class="bm-te-poem-results"></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($track->poem_id)): 
                        $poem = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM " . BM_TE_TABLE_POEM . " WHERE id = %d",
                            $track->poem_id
                        ));
                    ?>
                    <div class="bm-te-selected-poem">
                        <strong><?php echo esc_html($poem->name); ?></strong>
                        <button type="button" class="bm-te-remove-poem button button-small"><?php _e('Убрать', 'bm-track-editor'); ?></button>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Аудиофайл -->
                <div class="bm-te-card">
                    <h3><?php _e('Аудиофайл', 'bm-track-editor'); ?></h3>
                    
                    <div id="bm-te-upload-area" class="bm-te-upload-area">
                        <input type="hidden" id="track_file_path" name="track_file_path" 
                               value="<?php echo esc_attr($track->track_file_path ?? ''); ?>">
                        <input type="hidden" id="track_file_id" name="track_file_id" 
                               value="<?php echo esc_attr($track->file_id ?? ''); ?>">
                        
                        <div class="bm-te-upload-content">
                            <span class="dashicons dashicons-upload"></span>
                            <p><?php _e('Перетащите файл сюда или', 'bm-track-editor'); ?></p>
                            <button type="button" id="bm-te-upload-button" class="button">
                                <?php _e('Выберите файл', 'bm-track-editor'); ?>
                            </button>
                            <input type="file" id="bm-te-file-input" accept="audio/*" style="display: none;">
                            
                            <p class="description">
                                <?php 
                                $formats = implode(', ', BM_TE_Settings::get('allowed_audio_types', ['mp3', 'wav']));
                                $max_size = BM_TE_Settings::get('max_file_size', 50);
                                printf(__('Допустимые форматы: %s. Максимальный размер: %d MB', 'bm-track-editor'), 
                                       strtoupper($formats), $max_size);
                                ?>
                            </p>
                        </div>
                        
                        <div class="bm-te-upload-progress" style="display: none;">
                            <div class="bm-te-upload-progress-bar" style="width: 0%;"></div>
                        </div>
                        
                        <?php if (!empty($track->track_file_path)): ?>
                        <div class="bm-te-file-info">
                            <span class="file-name"><?php echo basename($track->track_file_path); ?></span>
                            <button type="button" class="button button-small bm-te-remove-file">
                                <?php _e('Удалить', 'bm-track-editor'); ?>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Правая колонка - музыкальные характеристики -->
            <div class="bm-te-sidebar">
                <!-- Музыкальные характеристики -->
                <div class="bm-te-card">
                    <h3><?php _e('Музыка', 'bm-track-editor'); ?></h3>
                    
                    <div class="bm-te-form-row">
                        <label for="mood_id"><?php _e('Настроение', 'bm-track-editor'); ?></label>
                        <select id="mood_id" name="mood_id">
                            <option value=""><?php _e('Не выбрано', 'bm-track-editor'); ?></option>
                            <?php foreach ($moods as $mood): ?>
                            <option value="<?php echo $mood->id; ?>" <?php selected($track->mood_id ?? '', $mood->id); ?>>
                                <?php echo esc_html($mood->name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="bm-te-form-row">
                        <label for="theme_id"><?php _e('Тема', 'bm-track-editor'); ?></label>
                        <select id="theme_id" name="theme_id">
                            <option value=""><?php _e('Не выбрано', 'bm-track-editor'); ?></option>
                            <?php foreach ($themes as $theme): ?>
                            <option value="<?php echo $theme->id; ?>" <?php selected($track->theme_id ?? '', $theme->id); ?>>
                                <?php echo esc_html($theme->name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="bm-te-form-row">
                        <label for="genre_id"><?php _e('Жанр', 'bm-track-editor'); ?></label>
                        <select id="genre_id" name="genre_id">
                            <option value=""><?php _e('Не выбрано', 'bm-track-editor'); ?></option>
                            <?php foreach ($genres as $genre): ?>
                            <option value="<?php echo $genre->id; ?>" <?php selected($details->genre_id ?? '', $genre->id); ?>>
                                <?php echo esc_html($genre->name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if (BM_TE_Settings::get('enable_bpm_editor')): ?>
                    <div class="bm-te-form-row">
                        <label for="bpm"><?php _e('BPM', 'bm-track-editor'); ?></label>
                        <input type="number" id="bpm" name="music_details[bpm]" 
                               value="<?php echo $details->bpm ?? 100; ?>" min="40" max="200" step="1">
                    </div>
                    <?php endif; ?>
                    
                    <?php if (BM_TE_Settings::get('enable_tonality_editor')): ?>
                    <div class="bm-te-form-row bm-te-row-double">
                        <div>
                            <label for="tonality_note"><?php _e('Тональность', 'bm-track-editor'); ?></label>
                            <select id="tonality_note" name="music_details[tonality_note]">
                                <?php foreach (['C', 'D', 'E', 'F', 'G', 'A', 'B'] as $note): ?>
                                <option value="<?php echo $note; ?>" <?php selected($details->tonality_note ?? '', $note); ?>>
                                    <?php echo $note; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="tonality_mood"><?php _e('Лад', 'bm-track-editor'); ?></label>
                            <select id="tonality_mood" name="music_details[tonality_mood]">
                                <option value="major" <?php selected($details->tonality_mood ?? '', 'major'); ?>>
                                    <?php _e('Мажор', 'bm-track-editor'); ?>
                                </option>
                                <option value="minor" <?php selected($details->tonality_mood ?? '', 'minor'); ?>>
                                    <?php _e('Минор', 'bm-track-editor'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="bm-te-form-row">
                        <label for="track_duration"><?php _e('Длительность (сек)', 'bm-track-editor'); ?></label>
                        <input type="number" id="track_duration" name="track_duration" 
                               value="<?php echo $track->track_duration ?? 0; ?>" min="0" step="1">
                    </div>
                </div>
                
                <!-- Инструменты -->
                <?php if (BM_TE_Settings::get('enable_instrument_selection')): ?>
                <div class="bm-te-card">
                    <h3><?php _e('Инструменты', 'bm-track-editor'); ?></h3>
                    
                    <div class="bm-te-checkbox-group">
                        <?php 
                        $selected_instruments = $details ? explode(',', $details->instrument_ids ?? '') : [];
                        foreach ($instruments as $instrument): 
                        ?>
                        <label class="bm-te-checkbox">
                            <input type="checkbox" name="music_details[instrument_ids][]" 
                                   value="<?php echo $instrument->id; ?>"
                                   <?php checked(in_array($instrument->id, $selected_instruments)); ?>>
                            <?php echo esc_html($instrument->name); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Статус -->
                <div class="bm-te-card">
                    <h3><?php _e('Статус', 'bm-track-editor'); ?></h3>
                    
                    <label class="bm-te-checkbox">
                        <input type="checkbox" name="is_approved" value="1" 
                               <?php checked($track->is_approved ?? 0, 1); ?>>
                        <?php _e('Одобрено', 'bm-track-editor'); ?>
                    </label>
                    
                    <label class="bm-te-checkbox">
                        <input type="checkbox" name="is_active" value="1" 
                               <?php checked($track->is_active ?? 1, 1); ?>>
                        <?php _e('Активно', 'bm-track-editor'); ?>
                    </label>
                    
                    <label class="bm-te-checkbox">
                        <input type="checkbox" name="is_payable" value="1" 
                               <?php checked($track->is_payable ?? 0, 1); ?>>
                        <?php _e('Платный контент', 'bm-track-editor'); ?>
                    </label>
                </div>
                
                <!-- Кнопки -->
                <div class="bm-te-actions">
                    <button type="submit" id="bm-te-save-track" class="button button-primary button-large">
                        <?php _e('Сохранить', 'bm-track-editor'); ?>
                    </button>
                    
                    <?php if ($track_id): ?>
                    <button type="button" class="button bm-te-delete" data-id="<?php echo $track_id; ?>">
                        <?php _e('Удалить', 'bm-track-editor'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>