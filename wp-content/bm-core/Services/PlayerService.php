<?php
namespace BM\Services;
use BM\Repositories\TrackRepository;

class PlayerService {
    
    /**
     * Рендеринг HTML5 плеера для трека
     */
    public static function renderPlayer($track, $options = []) {
        $defaults = [
            'autoplay' => false,
            'loop' => false,
            'show_controls' => true,
            'show_progress' => true,
            'show_volume' => true,
            'width' => '100%',
            'height' => 40,
        ];
        
        $options = array_merge($defaults, $options);
        
        // Генерируем уникальный ID для плеера
        $player_id = 'player_' . $track->id . '_' . uniqid();
        
        ob_start();
        ?>
        <div class="bm-player" id="<?= $player_id ?>" data-track-id="<?= $track->id ?>">
            <audio 
                src="<?= esc_url($track->track_file_path . '/' . $track->track_file_name . $track->track_file_ext) ?>"
                <?= $options['autoplay'] ? 'autoplay' : '' ?>
                <?= $options['loop'] ? 'loop' : '' ?>
                preload="metadata"
                style="width: <?= $options['width'] ?>; height: <?= $options['height'] ?>px;"
            ></audio>
            
            <?php if ($options['show_controls']): ?>
            <div class="bm-player__controls">
                <button class="bm-player__play" aria-label="Воспроизвести">
                    <span class="bm-player__icon-play">▶</span>
                    <span class="bm-player__icon-pause" style="display: none;">⏸</span>
                </button>
                
                <?php if ($options['show_progress']): ?>
                <div class="bm-player__progress-container">
                    <div class="bm-player__progress">
                        <div class="bm-player__progress-bar" style="width: 0%"></div>
                    </div>
                    <div class="bm-player__time">
                        <span class="bm-player__current">0:00</span> / 
                        <span class="bm-player__duration"><?= gmdate('i:s', $track->track_duration) ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($options['show_volume']): ?>
                <div class="bm-player__volume">
                    <button class="bm-player__volume-icon">🔊</button>
                    <input type="range" class="bm-player__volume-slider" min="0" max="1" step="0.1" value="1">
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <script>
        (function() {
            const player = document.getElementById('<?= $player_id ?>');
            const audio = player.querySelector('audio');
            const playBtn = player.querySelector('.bm-player__play');
            
            if (playBtn) {
                playBtn.addEventListener('click', function() {
                    if (audio.paused) {
                        audio.play();
                        playBtn.classList.add('is-playing');
                    } else {
                        audio.pause();
                        playBtn.classList.remove('is-playing');
                    }
                });
            }
            
            // Обновление прогресса
            const progressBar = player.querySelector('.bm-player__progress-bar');
            const currentTimeSpan = player.querySelector('.bm-player__current');
            
            if (audio && progressBar) {
                audio.addEventListener('timeupdate', function() {
                    const percent = (audio.currentTime / audio.duration) * 100;
                    progressBar.style.width = percent + '%';
                    
                    const minutes = Math.floor(audio.currentTime / 60);
                    const seconds = Math.floor(audio.currentTime % 60);
                    currentTimeSpan.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                });
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }
    
     public static function render($track, $options = []) {
        if (empty($track->track_file_path)) {
            return '';
        }
        
        $defaults = [
            'autoplay' => false,
            'loop' => false,
            'controls' => true,
            'width' => '100%',
            'height' => 40,
        ];
        
        $options = array_merge($defaults, $options);
        $player_id = 'player_' . uniqid();
        
        ob_start();
        ?>
        <div class="bm-te-player" id="<?php echo $player_id; ?>" data-track-id="<?php echo $track->id; ?>">
            <audio 
                src="<?php echo esc_url($track->track_file_path); ?>"
                <?php echo $options['autoplay'] ? 'autoplay' : ''; ?>
                <?php echo $options['loop'] ? 'loop' : ''; ?>
                <?php echo $options['controls'] ? 'controls' : ''; ?>
                preload="metadata"
                style="width: <?php echo $options['width']; ?>; height: <?php echo $options['height']; ?>px;"
            ></audio>
            
            <?php if (!$options['controls']): ?>
            <div class="bm-te-player-controls">
                <button class="bm-te-player-play">▶</button>
                <div class="bm-te-player-progress">
                    <div class="bm-te-player-progress-bar" style="width: 0%"></div>
                </div>
                <span class="bm-te-player-time">0:00</span>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Создание плейлиста
     */
    public static function renderPlaylist($tracks, $options = []) {
        $output = '<div class="bm-playlist">';
        
        foreach ($tracks as $index => $track) {
            $output .= '<div class="bm-playlist-item" data-track-id="' . $track->id . '">';
            $output .= '<span class="bm-playlist-item__index">' . ($index + 1) . '</span>';
            $output .= '<span class="bm-playlist-item__title">' . esc_html($track->track_name) . '</span>';
            
            if ($track->poet) {
                $output .= '<span class="bm-playlist-item__artist">' . esc_html($track->poet->short_name) . '</span>';
            }
            
            $output .= '<span class="bm-playlist-item__duration">' . gmdate('i:s', $track->track_duration) . '</span>';
            $output .= '<button class="bm-playlist-item__play" data-track="' . $track->id . '">▶</button>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        // Добавляем JavaScript для плейлиста
        $output .= '<script>
            document.querySelectorAll(".bm-playlist-item__play").forEach(btn => {
                btn.addEventListener("click", function() {
                    const trackId = this.dataset.track;
                    // Здесь можно вызвать загрузку трека в основной плеер
                    console.log("Play track:", trackId);
                });
            });
        </script>';
        
        return $output;
    }

  
    
    /**
     * Получение метаданных трека для плеера
     */
    public static function getTrackMetadata($track) {
        return [
            'id' => $track->id,
            'title' => $track->track_name,
            'artist' => $track->poet->short_name ?? 'Неизвестный поэт',
            'duration' => $track->track_duration,
            'url' => $track->track_file_path . '/' . $track->track_file_name . $track->track_file_ext,
            'cover' => $track->track_img ?? '',
            'lyrics' => $track->poem->poem_text ?? '',
        ];
    }

}