<?php
/**
 * Редактор трека
 * @var object $track_data Данные трека (если редактирование)
 * @var array $master_data Справочники
 */
$is_new = empty($track_data);
$track = $track_data ?? (object)[];
?>

<div class="wrap bm-track-editor">
    <h1><?= $is_new ? 'Новый трек' : 'Редактирование: ' . esc_html($track->track_name) ?></h1>
    
    <div class="bm-editor-layout">
        <!-- Левая колонка - плеер и основная информация -->
        <div class="bm-editor-main">
            
            <!-- Плеер (если есть файл) -->
            <?php if (!$is_new && !empty($track->track_file_path)): ?>
            <div class="bm-editor-player">
                <h3>Прослушивание</h3>
                <?= \BM\Services\PlayerService::renderPlayer($track, ['show_controls' => true]) ?>
            </div>
            <?php endif; ?>
            
            <!-- Загрузка файла -->
            <div class="bm-editor-section">
                <h3>Аудиофайл</h3>
                <div class="bm-file-uploader">
                    <input type="hidden" id="track_file_id" name="track_file_id" value="<?= $track->file_id ?? '' ?>">
                    <input type="hidden" id="track_file_path" name="track_file_path" value="<?= $track->track_file_path ?? '' ?>">
                    
                    <div class="bm-file-preview">
                        <?php if (!empty($track->track_file_path)): ?>
                        <div class="bm-file-info">
                            <span class="bm-file-name"><?= basename($track->track_file_path) ?></span>
                            <button type="button" class="button bm-remove-file">Удалить</button>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" class="button button-primary bm-upload-audio">
                        Выбрать файл
                    </button>
                    <p class="description">MP3, WAV, OGG. Максимальный размер: 50MB</p>
                </div>
            </div>
            
            <!-- Основные поля -->
            <div class="bm-editor-section">
                <h3>Основная информация</h3>
                
                <div class="bm-form-row">
                    <label for="track_name">Название трека *</label>
                    <input type="text" id="track_name" name="track_name" 
                           value="<?= esc_attr($track->track_name ?? '') ?>" class="regular-text" required>
                </div>
                
                <div class="bm-form-row">
                    <label for="track_slug">URL (slug)</label>
                    <input type="text" id="track_slug" name="track_slug" 
                           value="<?= esc_attr($track->track_slug ?? '') ?>" class="regular-text">
                    <p class="description">Оставьте пустым для автоматической генерации</p>
                </div>
                
                <div class="bm-form-row">
                    <label for="caption">Описание</label>
                    <textarea id="caption" name="caption" rows="3" class="large-text"><?= esc_textarea($track->caption ?? '') ?></textarea>
                </div>
            </div>
            
            <!-- Связь со стихотворением -->
            <div class="bm-editor-section">
                <h3>Стихотворение</h3>
                
                <div class="bm-poem-selector">
                    <div class="bm-form-row">
                        <label for="poet_id">Поэт</label>
                        <select id="poet_id" name="poet_id">
                            <option value="">Выберите поэта</option>
                            <?php foreach ($master_data['poets'] as $poet): ?>
                            <option value="<?= $poet->id ?>" <?= selected($track->poet_id ?? '', $poet->id) ?>>
                                <?= esc_html($poet->short_name) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="bm-form-row">
                        <label for="poem_search">Поиск стихотворения</label>
                        <input type="text" id="poem_search" class="regular-text" placeholder="Введите название...">
                        <input type="hidden" id="poem_id" name="poem_id" value="<?= $track->poem_id ?? '' ?>">
                        
                        <div class="bm-poem-results"></div>
                    </div>
                    
                    <?php if (!empty($track->poem)): ?>
                    <div class="bm-selected-poem">
                        <strong><?= esc_html($track->poem->name) ?></strong>
                        <span><?= esc_html($track->poem->poet->short_name ?? '') ?></span>
                        <button type="button" class="button bm-remove-poem">Убрать</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Правая колонка - музыкальные характеристики -->
        <div class="bm-editor-sidebar">
            
            <div class="bm-editor-section">
                <h3>Музыкальные характеристики</h3>
                
                <!-- Жанр и стиль -->
                <div class="bm-form-row">
                    <label for="genre_id">Жанр</label>
                    <select id="genre_id" name="music_details[genre_id]">
                        <option value="">Выберите жанр</option>
                        <?php foreach ($master_data['genres'] as $genre): ?>
                        <option value="<?= $genre->id ?>"><?= esc_html($genre->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Настроение -->
                <div class="bm-form-row">
                    <label for="mood_id">Настроение</label>
                    <select id="mood_id" name="mood_id">
                        <option value="">Выберите настроение</option>
                        <?php foreach ($master_data['moods'] as $mood): ?>
                        <option value="<?= $mood->id ?>"><?= esc_html($mood->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Тема -->
                <div class="bm-form-row">
                    <label for="theme_id">Тема</label>
                    <select id="theme_id" name="theme_id">
                        <option value="">Выберите тему</option>
                        <?php foreach ($master_data['themes'] as $theme): ?>
                        <option value="<?= $theme->id ?>"><?= esc_html($theme->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Темп -->
                <div class="bm-form-row">
                    <label for="temp_id">Темп</label>
                    <select id="temp_id" name="temp_id">
                        <option value="">Выберите темп</option>
                        <?php foreach ($master_data['tempos'] as $temp): ?>
                        <option value="<?= $temp->id ?>"><?= esc_html($temp->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Тип исполнения -->
                <div class="bm-form-row">
                    <label for="performance_type">Тип исполнения</label>
                    <select id="performance_type" name="performance_type">
                        <option value="song">Песня</option>
                        <option value="recitation">Чтец</option>
                        <option value="instrumental">Инструментал</option>
                    </select>
                </div>
                
                <!-- Вокал -->
                <div class="bm-form-row">
                    <label for="voice_gender">Тип голоса</label>
                    <select id="voice_gender" name="voice_gender">
                        <?php foreach ($master_data['voice_genders'] as $gender): ?>
                        <option value="<?= $gender->id ?>"><?= esc_html($gender->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Группа вокала -->
                <div class="bm-form-row">
                    <label for="voice_group">Вокальная группа</label>
                    <select id="voice_group" name="music_details[voice_group]">
                        <?php foreach ($master_data['voice_groups'] as $group): ?>
                        <option value="<?= $group->id ?>"><?= esc_html($group->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- BPM -->
                <div class="bm-form-row">
                    <label for="bpm">BPM (темп)</label>
                    <input type="number" id="bpm" name="music_details[bpm]" min="40" max="200" value="100">
                </div>
                
                <!-- Тональность -->
                <div class="bm-form-row bpm-row">
                    <label for="tonality_note">Тональность</label>
                    <select id="tonality_note" name="music_details[tonality_note]">
                        <option value="C">До (C)</option>
                        <option value="D">Ре (D)</option>
                        <option value="E">Ми (E)</option>
                        <option value="F">Фа (F)</option>
                        <option value="G">Соль (G)</option>
                        <option value="A">Ля (A)</option>
                        <option value="B">Си (B)</option>
                    </select>
                    
                    <select id="tonality_mood" name="music_details[tonality_mood]">
                        <option value="major">Мажор</option>
                        <option value="minor">Минор</option>
                    </select>
                </div>
                
                <!-- Инструменты (мультиселект) -->
                <div class="bm-form-row">
                    <label>Инструменты</label>
                    <div class="bm-checkbox-group">
                        <?php foreach ($master_data['instruments'] as $instrument): ?>
                        <label class="bm-checkbox">
                            <input type="checkbox" name="music_details[instrument_ids][]" value="<?= $instrument->id ?>">
                            <?= esc_html($instrument->name) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Дополнительные настройки -->
            <div class="bm-editor-section">
                <h3>Дополнительно</h3>
                
                <label class="bm-checkbox">
                    <input type="checkbox" name="is_payable" value="1" <?= checked($track->is_payable ?? 0, 1) ?>>
                    Платный контент
                </label>
                
                <label class="bm-checkbox">
                    <input type="checkbox" name="is_approved" value="1" <?= checked($track->is_approved ?? 0, 1) ?>>
                    Одобрено
                </label>
                
                <label class="bm-checkbox">
                    <input type="checkbox" name="is_active" value="1" <?= checked($track->is_active ?? 1, 1) ?>>
                    Активно
                </label>
                
                <label class="bm-checkbox">
                    <input type="checkbox" name="is_show_img" value="1" <?= checked($track->is_show_img ?? 1, 1) ?>>
                    Показывать изображение
                </label>
                
                <div class="bm-form-row">
                    <label for="age_restriction">Возрастное ограничение</label>
                    <select id="age_restriction" name="age_restriction">
                        <option value="0">0+</option>
                        <option value="6">6+</option>
                        <option value="12">12+</option>
                        <option value="16">16+</option>
                        <option value="18">18+</option>
                    </select>
                </div>
            </div>
            
            <!-- Кнопки сохранения -->
            <div class="bm-editor-actions">
                <input type="hidden" id="track_id" value="<?= $track->id ?? 0 ?>">
                <button type="button" class="button button-primary bm-save-track">Сохранить</button>
                <button type="button" class="button bm-preview-track">Предпросмотр</button>
                <?php if (!$is_new): ?>
                <button type="button" class="button button-link-delete bm-delete-track">Удалить</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>