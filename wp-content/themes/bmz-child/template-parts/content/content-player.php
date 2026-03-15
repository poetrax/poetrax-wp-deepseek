<?php
//Templates/track-card.php
if (!$track) return;

$track_repo = new BM\Repositories\TrackRepository();
$user_id = get_current_user_id();

$is_liked = $user_id ? $track_repo->hasInteraction($track->id, $user_id, 'like') : false;
$is_bookmarked = $user_id ? $track_repo->hasInteraction($track->id, $user_id, 'bookmark') : false;
?>

<div class="bm-track-card" data-track-id="<?= $track->id ?>">
    <div class="bm-track-card__cover">
        <?php if ($track->track_img): ?>
            <img src="<?= esc_url($track->track_img) ?>" alt="<?= esc_attr($track->track_name) ?>">
        <?php else: ?>
            <div class="bm-track-card__default-cover">
               <!-- Иконка музыки -->
            </div>
        <?php endif; ?>
        
        <button class="bm-track-card__play" data-track="<?= $track->id ?>">
            ▶
        </button>
    </div>
    
    <div class="bm-track-card__info">
        <h3 class="bm-track-card__title">
            <a href="<?= esc_url($track->permalink) ?>">
                <?= esc_html($track->track_name) ?>
            </a>
        </h3>
        
        <?php if ($track->poet): ?>
            <div class="bm-track-card__poet">
                <a href="<?= esc_url($track->poet->url) ?>">
                    <?= esc_html($track->poet->short_name) ?>
                </a>
            </div>
        <?php endif; ?>
        
        <?php if ($track->poem): ?>
            <div class="bm-track-card__poem">
                <a href="<?= esc_url($track->poem->url) ?>">
                    <?= esc_html($track->poem->name) ?>
                </a>
            </div>
        <?php endif; ?>
        
        <div class="bm-track-card__meta">
            <span class="bm-track-card__duration">
                <?= gmdate('i:s', $track->track_duration) ?>
            </span>
            
            <span class="bm-track-card__date">
                <?= date_i18n('d.m.Y', strtotime($track->created_at)) ?>
            </span>
        </div>
        
        <div class="bm-track-card__actions">
            <button class="bm-track-card__like <?= $is_liked ? 'active' : '' ?>" 
                    data-track="<?= $track->id ?>"
                    data-type="like">
                <span class="bm-track-card__like-icon">❤</span>
                <span class="bm-track-card__like-count">
                    <?= (int)($track->likes_count ?? 0) ?>
                </span>
            </button>
            
            <button class="bm-track-card__bookmark <?= $is_bookmarked ? 'active' : '' ?>"
                    data-track="<?= $track->id ?>"
                    data-type="bookmark">
                <span class="bm-track-card__bookmark-icon">🔖</span>
            </button>
        </div>
    </div>
</div>

 <div  class="audio-track" data-track-id="' . esc_attr($index) . '">
                <div class="title-author"><h3><a href="'.esc_html($track->guid_track) .'">'. esc_html($track_name) . '</a></h3>
                    <p class="author">
                        <a href="/category/'.$track->slug_author .'/">' . esc_html($track->author_name) . '</a>';
                        if($type!=='like' && $type!=='my') {
                          $output .= '<i class="far fa-thumbs-up like-btn" aria-hidden="true" data-track-id="' . esc_html($track->track_id) . '"></i>';
                        } 

                        if($like_count!=0){
                            $output .= '<span title="Лайки" class="like-count">'. esc_html($like_count) .'</span>';
                        }
                    
                        $output .= '<i class="far fa-bookmark bookmark-btn" aria-hidden="true" data-track-id="' . esc_html($like_count) . '"></i>';
                        
                        if($bookmark_count!=0){
                            $output .= '<span title="Закладки" class="bookmark-count">'. esc_html($bookmark_count) .'</span>';
                        }

                        $output .= '<i class="fa fa-window-close-o" aria-hidden="true" data-track-id="' . esc_html($track->track_id) . '"></i>';
                      
                        $feather = sprintf(
                            '<i class="fas fa-feather-alt text-file-trigger" aria-hidden="true" title="Стихотворение" data-text-file="%s" data-name-poem="%s" data-popup-id="1247" role="button" style="cursor: pointer"></i>',
                            $poem_text,' ', $track_name
                        );
                        $output .= $feather;

                        //<i class="fa-light fa-download"></i>
                        $output .= '<a href="'. esc_url($track_file_path) .'"><i class="fa-regular fa-download" aria-hidden="true" data-track-id="' . esc_html($track->track_id) . '"></i></a>';

                $output .= '</p>
                </div>

                <img  
                src="'.$img_name.'"  
                alt="" class="image-simple" 
                decoding="async">

                <audio controls="" controlslist="nodownload noplaybackrate" onplay="window.handleAudioPlay(this)" data-track-id="' . esc_attr($index) . '">
                    <source src="'  . esc_url($track_file_path) . '" type="audio/mpeg">
                    Ваш браузер не поддерживает элемент audio.
                </audio>
            </div>';'

 <div class="track-item" 
         data-track-id="<?php echo esc_attr($track_id); ?>" 
         data-user-id="<?php echo esc_attr($user_id); ?>"
         data-user-has-liked="<?php echo $user_has_liked ? 'true' : 'false'; ?>"
         data-user-has-bookmarked="<?php echo $user_has_bookmarked ? 'true' : 'false'; ?>">
        
    <img 
        width="50" 
        height="50" 
        src="<?php echo esc_url($track_data['img_name']); ?>" 
        class="" 
        alt="" 
        decoding="async">

        <div class="track-header">
            <h3 class="track-title"><?php echo esc_html($track_data['track_name']); ?></h3>
            <p class="track-artist"><?php echo esc_html($track_data['author_name']); ?></p>
        </div>
        
        <div class="track-interactions">
            <!-- Like Button -->
            <button class="interaction-btn like-btn" 
                    data-action="like" 
                    data-active="<?php echo $user_has_liked ? 'true' : 'false'; ?>"
                    aria-label="Like this track">
                <span class="icon">♥</span>
                <span class="counter" data-counter="likes">0</span>
            </button>
            
            <!-- Bookmark Button -->
            <button class="interaction-btn bookmark-btn" 
                    data-action="bookmark" 
                    data-active="<?php echo $user_has_bookmarked ? 'true' : 'false'; ?>"
                    aria-label="Bookmark this track">
                <span class="icon">⭐</span>
                <span class="counter" data-counter="bookmarks">0</span>
            </button>
            
            <!-- Play Counter -->
            <span class="interaction-counter">
                <span class="icon">👂</span>
                <span class="counter" data-counter="plays">0</span>
            </span>
            
            <!-- Listening Time -->
            <span class="interaction-counter">
                <span class="icon">⏱️</span>
                <span class="counter" data-counter="listening-time">0:00</span>
            </span>
        </div>
        
        <!-- Audio Player -->
        <div class="track-player">
            <audio controls preload="metadata" data-track-id="<?php echo esc_attr($track_id); ?>">
                <source src="<?php echo esc_url($track_data['track_path']); ?>" type="audio/mpeg">
            </audio>
        </div>
    </div>

