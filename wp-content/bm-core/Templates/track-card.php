<?php
/**
 * Единый шаблон карточки трека
 * 
 * @var object $track Объект трека (из TrackRepository)
 * @var string $context Контекст вывода (main, favorites, likes, recommendations)
 */

// Получаем сервисы
$interaction_service = new BM\Services\InteractionService();
$user_id = get_current_user_id();

// Статусы взаимодействия
$is_liked = $user_id ? $interaction_service->has_interaction($track->id, $user_id, 'like') : false;
$is_bookmarked = $user_id ? $interaction_service->has_interaction($track->id, $user_id, 'bookmark') : false;

// Статистика
$stats = $interaction_service->get_track_stats($track->id);
?>

<div class="track-card track-card--<?php echo esc_attr($context); ?>" 
     data-track-id="<?php echo $track->id; ?>"
     data-track-context="<?php echo esc_attr($context); ?>">
    
    <!-- Обложка -->
    <div class="track-card__cover">
        <?php if ($track->track_img): ?>
            <img src="<?php echo esc_url($track->track_img); ?>" 
                 alt="<?php echo esc_attr($track->track_name); ?>"
                 loading="lazy">
        <?php else: ?>
            <div class="track-card__cover-placeholder">
               <!-- Музыкальная иконка -->
            </div>
        <?php endif; ?>
        
        <!-- Кнопка воспроизведения -->
        <button class="track-card__play" 
                data-track-id="<?php echo $track->id; ?>"
                data-track-url="<?php echo esc_url($track->track_file_path); ?>"
                aria-label="Воспроизвести">
           
        </button>
        
        <!-- Индикатор воспроизведения (для текущего трека) -->
        <div class="track-card__playing-indicator" style="display: none;">
            <span></span><span></span><span></span>
        </div>
    </div>
    
    <!-- Информация о треке -->
    <div class="track-card__info">
        <h3 class="track-card__title">
            <a href="<?php echo esc_url($track->permalink ?? '#'); ?>">
                <?php echo esc_html($track->track_name); ?>
            </a>
        </h3>
        
        <?php if ($track->poet): ?>
        <div class="track-card__poet">
            <a href="<?php echo esc_url($track->poet->url ?? '#'); ?>">
                <?php echo esc_html($track->poet->short_name); ?>
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Мета-информация -->
        <div class="track-card__meta">
            <span class="track-card__duration">
                <?php echo gmdate('i:s', $track->track_duration); ?>
            </span>
            
            <?php if ($track->bpm): ?>
            <span class="track-card__bpm">
                <?php echo $track->bpm; ?> BPM
            </span>
            <?php endif; ?>
        </div>
        
        <!-- Действия -->
        <div class="track-card__actions">
            <!-- Лайк -->
            <button class="track-card__action track-card__action--like <?php echo $is_liked ? 'active' : ''; ?>" 
                    data-track-id="<?php echo $track->id; ?>"
                    data-action="like"
                    aria-label="<?php echo $is_liked ? 'Убрать лайк' : 'Поставить лайк'; ?>">
               
                <span class="action-count"><?php echo $stats->likes; ?></span>
            </button>
            
            <!-- Закладка -->
            <button class="track-card__action track-card__action--bookmark <?php echo $is_bookmarked ? 'active' : ''; ?>" 
                    data-track-id="<?php echo $track->id; ?>"
                    data-action="bookmark"
                    aria-label="<?php echo $is_bookmarked ? 'Убрать из закладок' : 'Добавить в закладки'; ?>">
                
                <span class="action-count"><?php echo $stats->bookmarks; ?></span>
            </button>
            
            <!-- Количество прослушиваний (только информативно) -->
            <span class="track-card__plays">
              
                <span class="plays-count"><?php echo $stats->plays; ?></span>
            </span>

             <!--  КНОПКА "ПЕРО" -->
            <?php if ($track->poem_id || !empty($track->poem_text)): ?>
            <button class="track-card__action track-card__action--poem" 
                    data-track-id="<?php echo $track->id; ?>"
                    data-poem-id="<?php echo $track->poem_id; ?>"
                    data-action="show-poem"
                    aria-label="Показать стихотворение">
               
                <span class="action-label">Стихи</span>
            </button>
            <?php endif; ?>


        </div>

    </div>
</div>

