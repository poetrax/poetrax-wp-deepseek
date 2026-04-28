<?php
/**
 * Компонент плеера
 * @var object $track Трек
 * @var array $options Настройки отображения
 */
use BM\Services\PlayerService;

$options = $options ?? [];
$player_html = PlayerService::renderPlayer($track, $options);
echo $player_html;
?>

<!-- Добавляем глобальные стили для плеера -->
<style>
.bm-player {
    margin: 10px 0;
    background: #f8f8f8;
    border-radius: 8px;
    padding: 10px;
}

.bm-player audio {
    width: 100%;
}

.bm-player__controls {
    display: flex;
    align-items: center;
    gap: 15px;
}

.bm-player__play {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #4CAF50;
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bm-player__progress-container {
    flex: 1;
}

.bm-player__progress {
    height: 6px;
    background: #ddd;
    border-radius: 3px;
    overflow: hidden;
}

.bm-player__progress-bar {
    height: 100%;
    background: #4CAF50;
    width: 0;
    transition: width 0.1s linear;
}

.bm-player__time {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.bm-player__volume {
    display: flex;
    align-items: center;
    gap: 5px;
}

.bm-player__volume-slider {
    width: 80px;
}
</style>