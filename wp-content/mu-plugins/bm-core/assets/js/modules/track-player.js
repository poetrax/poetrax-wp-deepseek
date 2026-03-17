/**
 * Track Player Module
 * Управление аудиоплеером, воспроизведением треков
 * CSS должен быть в отдельном файле assets/css/components/player.css
 */

class TrackPlayer {
    constructor() {
        this.audio = new Audio();
        this.currentTrackId = null;
        this.isPlaying = false;
        this.currentTime = 0;
        this.duration = 0;
        this.volume = 1;
        this.playbackRate = 1;
        
        this.initEventListeners();
        this.loadFromLocalStorage();
    }

    initEventListeners() {
        // События аудио
        this.audio.addEventListener('timeupdate', () => this.onTimeUpdate());
        this.audio.addEventListener('durationchange', () => this.onDurationChange());
        this.audio.addEventListener('play', () => this.onPlay());
        this.audio.addEventListener('pause', () => this.onPause());
        this.audio.addEventListener('ended', () => this.onEnded());
        this.audio.addEventListener('volumechange', () => this.onVolumeChange());
        this.audio.addEventListener('ratechange', () => this.onRateChange());
        this.audio.addEventListener('error', (e) => this.onError(e));

        // Глобальные события для управления из других модулей
        document.addEventListener('bm:play-track', (e) => this.playTrack(e.detail));
        document.addEventListener('bm:pause-track', () => this.pause());
        document.addEventListener('bm:stop-track', () => this.stop());
        document.addEventListener('bm:seek-track', (e) => this.seek(e.detail.seconds));
        document.addEventListener('bm:volume-change', (e) => this.setVolume(e.detail.volume));
        document.addEventListener('bm:rate-change', (e) => this.setPlaybackRate(e.detail.rate));
    }

    playTrack({ trackId, trackUrl, cardElement, autoplay = true }) {
        // Если тот же трек — просто переключаем play/pause
        if (this.currentTrackId === trackId) {
            if (this.isPlaying) {
                this.pause();
            } else {
                this.play();
            }
            return;
        }

        // Новый трек
        const wasPlaying = this.isPlaying;
        this.stop(); // останавливаем предыдущий
        
        this.currentTrackId = trackId;
        this.audio.src = trackUrl;
        this.audio.load();
        
        // Записываем прослушивание в API
        if (window.ApiClient) {
            window.ApiClient.tracks.play(trackId, 0).catch(console.error);
        }
        
        if (autoplay) {
            this.play();
        }
        
        // Обновляем UI через классы
        this.updatePlayingIndicator(cardElement);
        
        // Диспатчим событие о смене трека
        document.dispatchEvent(new CustomEvent('bm:track-changed', {
            detail: { trackId, trackUrl, wasPlaying }
        }));
    }

    play() {
        this.audio.play()
            .then(() => {
                this.isPlaying = true;
                this.saveToLocalStorage();
                this.updateUI();
                document.dispatchEvent(new CustomEvent('bm:track-played', {
                    detail: { trackId: this.currentTrackId }
                }));
            })
            .catch(error => {
                console.error('Playback failed:', error);
                this.showError('Не удалось воспроизвести трек');
            });
    }

    pause() {
        this.audio.pause();
        this.isPlaying = false;
        this.saveToLocalStorage();
        this.updateUI();
        document.dispatchEvent(new CustomEvent('bm:track-paused', {
            detail: { trackId: this.currentTrackId }
        }));
    }

    stop() {
        this.audio.pause();
        this.audio.currentTime = 0;
        this.currentTrackId = null;
        this.isPlaying = false;
        this.saveToLocalStorage();
        this.updateUI();
        document.dispatchEvent(new CustomEvent('bm:track-stopped'));
    }

    seek(seconds) {
        this.audio.currentTime = seconds;
    }

    setVolume(value) {
        this.volume = Math.max(0, Math.min(1, value));
        this.audio.volume = this.volume;
    }

    setPlaybackRate(rate) {
        this.playbackRate = Math.max(0.5, Math.min(2, rate));
        this.audio.playbackRate = this.playbackRate;
    }

    // Обработчики событий
    onTimeUpdate() {
        this.currentTime = this.audio.currentTime;
        this.updateProgressUI();
    }

    onDurationChange() {
        this.duration = this.audio.duration;
        this.updateDurationUI();
    }

    onPlay() {
        this.isPlaying = true;
        this.updateUI();
    }

    onPause() {
        this.isPlaying = false;
        this.updateUI();
    }

    onEnded() {
        this.isPlaying = false;
        this.currentTime = 0;
        this.currentTrackId = null;
        this.updateUI();
        
        document.dispatchEvent(new CustomEvent('bm:track-ended', { 
            detail: this.currentTrackId 
        }));
    }

    onVolumeChange() {
        this.volume = this.audio.volume;
        this.saveToLocalStorage();
        this.updateVolumeUI();
    }

    onRateChange() {
        this.playbackRate = this.audio.playbackRate;
        this.updateRateUI();
    }

    onError(e) {
        console.error('Audio error:', e);
        document.dispatchEvent(new CustomEvent('bm:player-error', {
            detail: { 
                trackId: this.currentTrackId,
                error: e.target.error 
            }
        }));
    }

    // Обновление UI через классы
    updateUI() {
        // Обновляем кнопки play/pause
        document.querySelectorAll('[data-track-play]').forEach(btn => {
            const trackId = btn.dataset.trackId;
            
            if (trackId && parseInt(trackId) === this.currentTrackId) {
                btn.classList.toggle('is-playing', this.isPlaying);
                btn.setAttribute('aria-pressed', this.isPlaying);
            } else {
                btn.classList.remove('is-playing');
                btn.setAttribute('aria-pressed', 'false');
            }
        });
    }

    updateProgressUI() {
        const percent = (this.currentTime / this.duration) * 100 || 0;
        
        document.querySelectorAll('[data-track-progress]').forEach(el => {
            el.style.width = `${percent}%`;
        });

        document.querySelectorAll('[data-track-current-time]').forEach(el => {
            el.textContent = this.formatTime(this.currentTime);
        });
    }

    updateDurationUI() {
        document.querySelectorAll('[data-track-duration]').forEach(el => {
            el.textContent = this.formatTime(this.duration);
        });
    }

    updateVolumeUI() {
        document.querySelectorAll('[data-track-volume]').forEach(el => {
            if (el.tagName === 'INPUT') {
                el.value = this.volume;
            } else {
                el.textContent = Math.round(this.volume * 100) + '%';
            }
        });

        document.querySelectorAll('[data-track-volume-icon]').forEach(el => {
            let icon = '🔊';
            if (this.volume === 0) icon = '🔇';
            else if (this.volume < 0.3) icon = '🔈';
            else if (this.volume < 0.7) icon = '🔉';
            el.textContent = icon;
        });
    }

    updateRateUI() {
        document.querySelectorAll('[data-track-rate]').forEach(el => {
            el.textContent = this.playbackRate.toFixed(1) + 'x';
        });
    }

    updatePlayingIndicator(cardElement) {
        // Убираем индикатор с предыдущего трека
        document.querySelectorAll('.track-card.is-playing').forEach(el => {
            el.classList.remove('is-playing');
        });
        
        // Показываем индикатор на новом треке
        if (cardElement) {
            cardElement.classList.add('is-playing');
        }
    }

    // Сохранение/загрузка состояния
    saveToLocalStorage() {
        try {
            const state = {
                volume: this.volume,
                playbackRate: this.playbackRate,
                currentTrackId: this.currentTrackId,
                currentTime: this.currentTime,
                isPlaying: this.isPlaying
            };
            localStorage.setItem('bm_player_state', JSON.stringify(state));
        } catch (e) {
            // Игнорируем ошибки localStorage
        }
    }

    loadFromLocalStorage() {
        try {
            const saved = localStorage.getItem('bm_player_state');
            if (saved) {
                const state = JSON.parse(saved);
                this.setVolume(state.volume || 1);
                this.setPlaybackRate(state.playbackRate || 1);
                // Не восстанавливаем воспроизведение автоматически
                // только громкость и скорость
            }
        } catch (e) {
            // Игнорируем ошибки localStorage
        }
    }

    // Вспомогательные методы
    formatTime(seconds) {
        if (isNaN(seconds) || seconds === 0) return '0:00';
        
        const hrs = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);
        
        if (hrs > 0) {
            return `${hrs}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    showError(message) {
        // Диспатчим событие для UI, который покажет ошибку
        document.dispatchEvent(new CustomEvent('bm:player-error-message', {
            detail: { message }
        }));
    }

    // Геттеры
    getCurrentTrackId() {
        return this.currentTrackId;
    }

    getState() {
        return {
            currentTrackId: this.currentTrackId,
            isPlaying: this.isPlaying,
            currentTime: this.currentTime,
            duration: this.duration,
            volume: this.volume,
            playbackRate: this.playbackRate
        };
    }
}

// Создаём глобальный экземпляр
document.addEventListener('DOMContentLoaded', () => {
    window.player = new TrackPlayer();
});
