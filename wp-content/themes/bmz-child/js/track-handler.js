// track-handler.js
class TrackHandler {
    constructor(options = {}) {
        this.options = {
            baseUrl: 'https://bestmz.com',
            paramName: 'ti',
            cacheEnabled: true,
            cacheDuration: 3600000, // 1 час в мс
            apiEndpoint: '/api/track',
            ...options
        };

        this.cache = new Map();
        this.init();
    }

    init() {
        // Автоматически обрабатываем текущий URL при загрузке
        if (typeof window !== 'undefined') {
            this.processCurrentUrl();

            // Отслеживаем изменения URL
            window.addEventListener('popstate', () => {
                this.processCurrentUrl();
            });
        }
    }

    /**
     * Генерация URL
     */
    generateUrl(trackId, additionalParams = {}) {
        const params = new URLSearchParams();
        params.set(this.options.paramName, trackId);

        // Добавляем дополнительные параметры
        Object.entries(additionalParams).forEach(([key, value]) => {
            if (value !== null && value !== undefined) {
                params.set(key, value);
            }
        });

        return `${this.options.baseUrl}?${params.toString()}`;
    }

    /**
     * Обработка текущего URL
     */
    async processCurrentUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        const trackId = urlParams.get(this.options.paramName);

        if (!trackId) {
            return null;
        }

        try {
            const data = await this.getTrackData(trackId);
            this.renderTrack(data);
            return data;
        } catch (error) {
            console.error('Error processing track URL:', error);
            this.showError('Трек не найден');
            return null;
        }
    }

    /**
     * Получение данных трека
     */
    async getTrackData(trackId) {
        // Проверяем кэш
        if (this.options.cacheEnabled) {
            const cached = this.getFromCache(trackId);
            if (cached) {
                return cached;
            }
        }

        // Загружаем с сервера
        const data = await this.fetchTrackData(trackId);

        // Сохраняем в кэш
        if (this.options.cacheEnabled) {
            this.saveToCache(trackId, data);
        }

        return data;
    }

    /**
     * Загрузка данных с сервера
     */
    async fetchTrackData(trackId) {
        const response = await fetch(`${this.options.apiEndpoint}/${trackId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Unknown error');
        }

        return data.data;
    }

    /**
     * Предварительная загрузка треков
     */
    async prefetchTracks(trackIds) {
        const promises = trackIds.map(id => this.getTrackData(id));
        return Promise.allSettled(promises);
    }

    /**
     * Рендеринг трека
     */
    renderTrack(data) {
        // Обновляем заголовок страницы
        document.title = `${data.track_name} - ${data.poet_name}`;

        // Обновляем Open Graph мета-теги
        this.updateMetaTags(data.og_tags);

        // Рендерим основной контент
        const container = document.getElementById('track-container') || document.body;
        container.innerHTML = this.createTrackTemplate(data);

        // Инициализируем аудио плеер
        if (data.listen_url) {
            this.initAudioPlayer(data.listen_url, data.track_duration);
        }

        // Отправляем аналитику
        this.trackView(data.id);
    }

    /**
     * Шаблон для отображения трека
     */
    createTrackTemplate(data) {
        return `
            <div class="track-page" data-track-id="${data.id}">
                <div class="track-header">
                    <h1 class="track-title">${this.escapeHtml(data.track_name)}</h1>
                    <div class="track-meta">
                        <span class="track-poet">${this.escapeHtml(data.poet_name)}</span>
                        <span class="track-poem">${this.escapeHtml(data.poem_name)}</span>
                        <span class="track-duration">${data.duration_formatted}</span>
                    </div>
                </div>
                
                <div class="track-player">
                    <audio controls preload="metadata">
                        <source src="${data.listen_url}" type="audio/mpeg">
                    </audio>
                    <div class="player-controls">
                        <button class="btn-play">▶️ Воспроизвести</button>
                        <button class="btn-download" data-url="${data.download_url}">
                            ⬇️ Скачать (${data.file_size_formatted})
                        </button>
                    </div>
                </div>
                
                <div class="track-info">
                    <div class="track-description">
                        <h3>Описание</h3>
                        <p>${this.escapeHtml(data.track_theme || 'Нет описания')}</p>
                    </div>
                    
                    <div class="track-tags">
                        <h3>Теги</h3>
                        <div class="tags-list">
                            ${data.genre_names.map(genre =>
            `<span class="tag">${this.escapeHtml(genre)}</span>`
        ).join('')}
                            ${data.mood_name ? `<span class="tag mood">${this.escapeHtml(data.mood_name)}</span>` : ''}
                            ${data.theme_name ? `<span class="tag theme">${this.escapeHtml(data.theme_name)}</span>` : ''}
                        </div>
                    </div>
                    
                    <div class="track-stats">
                        <div class="stat">
                            <span class="stat-label">Прослушиваний:</span>
                            <span class="stat-value">${data.stats.plays}</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Лайков:</span>
                            <span class="stat-value">${data.stats.likes}</span>
                        </div>
                    </div>
                </div>
                
                <div class="track-share">
                    <h3>Поделиться</h3>
                    <div class="share-buttons">
                        <button class="btn-share" data-service="vk">VK</button>
                        <button class="btn-share" data-service="telegram">Telegram</button>
                        <button class="btn-share" data-service="twitter">Twitter</button>
                        <button class="btn-copy-url">📋 Копировать ссылку</button>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Управление кэшем
     */
    getFromCache(trackId) {
        const item = this.cache.get(trackId);

        if (item && Date.now() - item.timestamp < this.options.cacheDuration) {
            return item.data;
        }

        this.cache.delete(trackId);
        return null;
    }

    saveToCache(trackId, data) {
        this.cache.set(trackId, {
            data,
            timestamp: Date.now()
        });

        // Ограничиваем размер кэша
        if (this.cache.size > 100) {
            const firstKey = this.cache.keys().next().value;
            this.cache.delete(firstKey);
        }
    }

    /**
     * Вспомогательные методы
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    updateMetaTags(tags) {
        Object.entries(tags).forEach(([property, content]) => {
            if (!content) return;

            let selector = `meta[property="${property}"]`;
            if (property.startsWith('twitter:')) {
                selector = `meta[name="${property}"]`;
            }

            let meta = document.querySelector(selector);

            if (!meta) {
                meta = document.createElement('meta');
                if (property.startsWith('twitter:')) {
                    meta.setAttribute('name', property);
                } else {
                    meta.setAttribute('property', property);
                }
                document.head.appendChild(meta);
            }

            meta.setAttribute('content', content);
        });
    }

    initAudioPlayer(url, duration) {
        // Инициализация кастомного аудио плеера
        const audio = document.querySelector('audio');
        if (audio) {
            audio.addEventListener('play', () => {
                this.trackPlay();
            });

            audio.addEventListener('ended', () => {
                this.trackComplete();
            });
        }
    }

    trackView(trackId) {
        // Отправка статистики просмотра
        navigator.sendBeacon?.('/api/track/view', new URLSearchParams({
            track_id: trackId,
            referrer: document.referrer
        }));
    }

    trackPlay() {
        // Статистика начала прослушивания
        console.log('Track play started');
    }

    trackComplete() {
        // Статистика завершения прослушивания
        console.log('Track play completed');
    }

    showError(message) {
        const container = document.getElementById('track-container') || document.body;
        container.innerHTML = `
            <div class="error-message">
                <h2>Ошибка</h2>
                <p>${message}</p>
                <a href="/" class="btn-home">На главную</a>
            </div>
        `;
    }
}

// Инициализация
document.addEventListener('DOMContentLoaded', () => {
    window.trackHandler = new TrackHandler({
        baseUrl: window.location.origin,
        apiEndpoint: '/wp-json/bestmz/v1/track'
    });
});