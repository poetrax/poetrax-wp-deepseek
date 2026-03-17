/**
 * Track Handler Module
 * Обработка параметров трека в URL и загрузка данных
 */

class TrackHandler {
    constructor(options = {}) {
        this.options = {
            paramName: 'ti',
            containerId: 'track-container',
            ...options
        };

        this.container = document.getElementById(this.options.containerId);
        this.init();
    }

    init() {
        this.processCurrentUrl();

        window.addEventListener('popstate', () => {
            this.processCurrentUrl();
        });
    }

    processCurrentUrl() {
        const params = new URLSearchParams(window.location.search);
        const trackId = params.get(this.options.paramName);

        if (!trackId) {
            this.showPlaceholder();
            return;
        }

        this.loadTrackData(trackId);
    }

    async loadTrackData(trackId) {
        this.showLoading();

        try {
            if (!window.ApiClient) {
                throw new Error('ApiClient not found');
            }

            const track = await window.ApiClient.tracks.get(trackId);
            this.renderTrack(track);
            this.updateMetaTags(track);
            
        } catch (error) {
            console.error('Failed to load track:', error);
            this.showError('Трек не найден');
        }
    }

    renderTrack(track) {
        if (!this.container) return;

        const template = this.createTrackTemplate(track);
        this.container.innerHTML = template;

        // Триггерим событие для плеера
        if (track.listen_url && window.player) {
            setTimeout(() => {
                document.dispatchEvent(new CustomEvent('bm:play-track', {
                    detail: {
                        trackId: track.id,
                        trackUrl: track.listen_url,
                        autoplay: false
                    }
                }));
            }, 100);
        }
    }

    createTrackTemplate(track) {
        return `
            <div class="track-page" data-track-id="${track.id}">
                <div class="track-header">
                    <h1 class="track-title">${this.escape(track.track_name)}</h1>
                    
                    <div class="track-meta">
                        ${track.poet_name ? `
                            <span class="track-poet">
                                <a href="/poet/${track.poet_id}">${this.escape(track.poet_name)}</a>
                            </span>
                        ` : ''}
                        
                        ${track.poem_name ? `
                            <span class="track-poem">
                                <a href="/poem/${track.poem_id}">${this.escape(track.poem_name)}</a>
                            </span>
                        ` : ''}
                        
                        <span class="track-duration">${this.formatDuration(track.duration)}</span>
                    </div>
                </div>

                <div class="track-player-container">
                    <audio id="track-player" controls preload="metadata">
                        <source src="${track.listen_url}" type="audio/mpeg">
                    </audio>
                </div>

                <div class="track-info">
                    ${track.description ? `
                        <div class="track-description">
                            <h3>Описание</h3>
                            <p>${this.escape(track.description)}</p>
                        </div>
                    ` : ''}

                    <div class="track-tags">
                        ${this.renderTags(track)}
                    </div>

                    <div class="track-stats">
                        <div class="stat-item">
                            <span class="stat-label">Прослушиваний:</span>
                            <span class="stat-value" data-count="plays">${track.stats?.plays || 0}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Лайков:</span>
                            <span class="stat-value" data-count="likes">${track.stats?.likes || 0}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Закладок:</span>
                            <span class="stat-value" data-count="bookmarks">${track.stats?.bookmarks || 0}</span>
                        </div>
                    </div>
                </div>

                <div class="track-actions">
                    <button class="action-btn like-btn" data-interaction="like" data-track-id="${track.id}">
                        ❤️ <span class="count" data-count="likes">${track.stats?.likes || 0}</span>
                    </button>
                    
                    <button class="action-btn bookmark-btn" data-interaction="bookmark" data-track-id="${track.id}">
                        🔖 <span class="count" data-count="bookmarks">${track.stats?.bookmarks || 0}</span>
                    </button>
                    
                    <button class="action-btn share-btn" data-interaction="share" data-track-id="${track.id}">
                        📤 Поделиться
                    </button>
                </div>

                ${track.lyrics ? `
                    <div class="track-lyrics">
                        <h3>Текст</h3>
                        <pre>${this.escape(track.lyrics)}</pre>
                    </div>
                ` : ''}
            </div>
        `;
    }

    renderTags(track) {
        const tags = [];

        if (track.genre_name) {
            tags.push(`<span class="tag genre">${this.escape(track.genre_name)}</span>`);
        }
        if (track.mood_name) {
            tags.push(`<span class="tag mood">${this.escape(track.mood_name)}</span>`);
        }
        if (track.theme_name) {
            tags.push(`<span class="tag theme">${this.escape(track.theme_name)}</span>`);
        }

        return tags.join('');
    }

    updateMetaTags(track) {
        // Open Graph
        this.setMetaTag('og:title', track.track_name);
        this.setMetaTag('og:description', track.description || `Трек на стихи ${track.poet_name || 'поэта'}`);
        this.setMetaTag('og:image', track.cover_url || '');
        this.setMetaTag('og:url', window.location.href);
        this.setMetaTag('og:type', 'music.song');

        // Twitter Card
        this.setMetaTag('twitter:card', 'summary_large_image');
        this.setMetaTag('twitter:title', track.track_name);
        this.setMetaTag('twitter:description', track.description || '');
        this.setMetaTag('twitter:image', track.cover_url || '');

        // Audio meta
        this.setMetaTag('music:duration', track.duration);
        this.setMetaTag('music:musician', track.poet_name || '');
    }

    setMetaTag(property, content) {
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
    }

    showLoading() {
        if (!this.container) return;

        this.container.innerHTML = `
            <div class="track-loading">
                <div class="loading-spinner"></div>
                <p>Загрузка трека...</p>
            </div>
        `;
    }

    showError(message) {
        if (!this.container) return;

        this.container.innerHTML = `
            <div class="track-error">
                <h2>Ошибка</h2>
                <p>${this.escape(message)}</p>
                <a href="/" class="home-link">На главную</a>
            </div>
        `;
    }

    showPlaceholder() {
        if (!this.container) return;

        this.container.innerHTML = `
            <div class="track-placeholder">
                <p>Трек не выбран</p>
            </div>
        `;
    }

    formatDuration(seconds) {
        if (!seconds || isNaN(seconds)) return '0:00';
        
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    escape(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    generateUrl(trackId, additionalParams = {}) {
        const params = new URLSearchParams();
        params.set(this.options.paramName, trackId);

        Object.entries(additionalParams).forEach(([key, value]) => {
            if (value) params.set(key, value);
        });

        return `${window.location.origin}?${params.toString()}`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('track-container')) {
        window.trackHandler = new TrackHandler();
    }
});
