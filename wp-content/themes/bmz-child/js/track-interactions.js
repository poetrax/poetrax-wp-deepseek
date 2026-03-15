'use strict';
class TrackInteractionsManager {
    constructor(baseUrl = null) {
        this.baseUrl = baseUrl || (window.trackInteractions ? window.trackInteractions.ajaxurl : null);
        this.nonce = window.trackInteractions ? window.trackInteractions.nonce : null;
        this.isWordPress = !!window.trackInteractions;
        this.cache = new Map();
    }

    async request(endpoint, data = {}, method = 'POST') {
        const url = this.isWordPress ? this.baseUrl : `${this.baseUrl}${endpoint}`;

        try {
            if (this.isWordPress) {
                // WordPress AJAX через FormData
                const formData = new FormData();
                formData.append('action', endpoint);
                if (this.nonce) {
                    formData.append('nonce', this.nonce);
                }

                for (const [key, value] of Object.entries(data)) {
                    if (value !== null && value !== undefined) {
                        formData.append(key, value.toString());
                    }
                }

                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                return await response.json();
            } else {
                // Node.js API через JSON
                const options = {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include'
                };

                if (method !== 'GET') {
                    options.body = JSON.stringify(data);
                }

                const response = await fetch(url, options);
                return await response.json();
            }
        } catch (error) {
            console.error('Request failed:', error);
            throw error;
        }
    }

    async getTrackStats(trackId, userId = null) {
        const cacheKey = `stats_${trackId}_${userId}`;

        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        try {
            let response;

            if (this.isWordPress) {
                response = await this.request('get_track_stats', {
                    track_id: trackId
                });
            } else {
                const params = userId ? `?userId=${userId}` : '';
                response = await fetch(`${this.baseUrl}/api/stats/${trackId}${params}`);
                response = await response.json();
            }

            if ((this.isWordPress && response.success) || (!this.isWordPress && !response.error)) {
                const stats = this.isWordPress ? response.data : response;
                this.cache.set(cacheKey, stats);
                return stats;
            } else {
                throw new Error(this.isWordPress ? response.data : response.error);
            }
        } catch (error) {
            console.error('Error getting stats:', error);
            return null;
        }
    }

    async addInteraction(trackId, type, playDuration = null, userId = null) {
        try {
            let response;

            if (this.isWordPress) {
                const data = {
                    track_id: trackId,
                    action_type: 'add',
                    interaction_type: type
                };

                if (playDuration !== null) {
                    data.play_duration = playDuration;
                }

                response = await this.request('track_interaction', data);
            } else {
                response = await this.request('/api/interaction', {
                    trackId: parseInt(trackId),
                    actionType: 'add',
                    interactionType: type,
                    playDuration: playDuration,
                    userId: userId ? parseInt(userId) : null
                });
            }

            if (response.success) {
                this.cache.set(`stats_${trackId}_${userId}`, response.stats);
                return {
                    success: true,
                    stats: response.stats,
                    message: response.message
                };
            } else {
                return {
                    success: false,
                    message: this.isWordPress ? response.data : response.error
                };
            }
        } catch (error) {
            console.error('Error adding interaction:', error);
            return {
                success: false,
                message: 'Network error'
            };
        }
    }

    async removeInteraction(trackId, type, userId = null) {
        try {
            let response;

            if (this.isWordPress) {
                response = await this.request('track_interaction', {
                    track_id: trackId,
                    action_type: 'remove',
                    interaction_type: type
                });
            } else {
                response = await this.request('/api/interaction', {
                    trackId: parseInt(trackId),
                    actionType: 'remove',
                    interactionType: type,
                    userId: userId ? parseInt(userId) : null
                });
            }

            if (response.success) {
                this.cache.set(`stats_${trackId}_${userId}`, response.stats);
                return {
                    success: true,
                    stats: response.stats,
                    message: response.message
                };
            } else {
                return {
                    success: false,
                    message: this.isWordPress ? response.data : response.error
                };
            }
        } catch (error) {
            console.error('Error removing interaction:', error);
            return {
                success: false,
                message: 'Network error'
            };
        }
    }

    async toggleLike(trackId, userId = null) {
        const stats = await this.getTrackStats(trackId, userId);

        if (stats && stats.user_has_liked) {
            return await this.removeInteraction(trackId, 'like', userId);
        } else {
            return await this.addInteraction(trackId, 'like', null, userId);
        }
    }

    async toggleBookmark(trackId, userId = null) {
        const stats = await this.getTrackStats(trackId, userId);

        if (stats && stats.user_has_bookmarked) {
            return await this.removeInteraction(trackId, 'bookmark', userId);
        } else {
            return await this.addInteraction(trackId, 'bookmark', null, userId);
        }
    }

    async addPlay(trackId, playDuration = null, userId = null) {
        return await this.addInteraction(trackId, 'audio', playDuration, userId);
    }

    clearCache(trackId, userId = null) {
        this.cache.delete(`stats_${trackId}_${userId}`);
    }

    clearAllCache() {
        this.cache.clear();
    }
}

class TrackInteractionsUI {
    constructor(manager) {
        this.manager = manager;
        this.containers = new Map();
        this.mediaElements = new Map();
    }

    init() {
        this.initContainers();
        this.bindEvents();
        this.initAudioTracking();
    }

    initContainers() {
        const containers = document.querySelectorAll('[data-track-id]');

        containers.forEach(container => {
            const trackId = container.dataset.trackId;
            const userId = container.dataset.userId || null;

            this.containers.set(trackId, {
                container,
                userId,
                stats: null
            });

            this.loadStats(trackId, userId);
        });
    }

    async loadStats(trackId, userId) {
        try {
            const stats = await this.manager.getTrackStats(trackId, userId);

            if (stats) {
                const containerInfo = this.containers.get(trackId);
                if (containerInfo) {
                    containerInfo.stats = stats;
                    this.updateUI(containerInfo.container, stats);
                }
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    updateUI(container, stats) {
        this.updateCounter(container, 'likes', stats.likes);
        this.updateCounter(container, 'bookmarks', stats.bookmarks);
        this.updateCounter(container, 'plays', stats.plays);
        this.updateCounter(container, 'listening-time', stats.total_listening_time || 0);

        this.updateButtonState(container, 'like', stats.user_has_liked);
        this.updateButtonState(container, 'bookmark', stats.user_has_bookmarked);
    }

    updateCounter(container, type, value) {
        const element = container.querySelector(`[data-counter="${type}"]`);
        if (element) {
            element.textContent = type === 'listening-time' ? this.formatDuration(value) : value;
        }
    }

    updateButtonState(container, type, isActive) {
        const button = container.querySelector(`[data-action="${type}"]`);
        if (button) {
            button.classList.toggle('active', isActive);
            button.dataset.active = isActive;
        }
    }

    bindEvents() {
        document.addEventListener('click', (e) => {
            const likeBtn = e.target.closest('[data-action="like"]');
            const bookmarkBtn = e.target.closest('[data-action="bookmark"]');

            if (likeBtn) {
                e.preventDefault();
                this.handleLikeClick(likeBtn);
            } else if (bookmarkBtn) {
                e.preventDefault();
                this.handleBookmarkClick(bookmarkBtn);
            }
        });
    }

    async handleLikeClick(button) {
        const container = button.closest('[data-track-id]');
        if (!container) return;

        const trackId = container.dataset.trackId;
        const userId = container.dataset.userId || null;

        button.disabled = true;
        const originalText = button.innerHTML;

        try {
            const result = await this.manager.toggleLike(trackId, userId);

            if (result.success) {
                this.showFeedback(button, result.message);
                this.updateUI(container, result.stats);
            } else {
                this.showError(button, result.message);
            }
        } catch (error) {
            console.error('Error toggling like:', error);
            this.showError(button, 'Operation failed');
        } finally {
            button.disabled = false;
        }
    }

    async handleBookmarkClick(button) {
        const container = button.closest('[data-track-id]');
        if (!container) return;

        const trackId = container.dataset.trackId;
        const userId = container.dataset.userId || null;

        button.disabled = true;

        try {
            const result = await this.manager.toggleBookmark(trackId, userId);

            if (result.success) {
                this.showFeedback(button, result.message);
                this.updateUI(container, result.stats);
            } else {
                this.showError(button, result.message);
            }
        } catch (error) {
            console.error('Error toggling bookmark:', error);
            this.showError(button, 'Operation failed');
        } finally {
            button.disabled = false;
        }
    }

    initAudioTracking() {
        // Обработка существующих медиа элементов
        document.querySelectorAll('audio, video').forEach(media => {
            this.setupMediaTracking(media);
        });

        // Обработка динамически добавленных медиа элементов
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        const mediaElements = node.querySelectorAll ? node.querySelectorAll('audio, video') : [];
                        mediaElements.forEach(media => this.setupMediaTracking(media));

                        if (node.tagName === 'AUDIO' || node.tagName === 'VIDEO') {
                            this.setupMediaTracking(node);
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    setupMediaTracking(media) {
        const container = media.closest('[data-track-id]');
        if (!container) return;

        const trackId = container.dataset.trackId;
        const userId = container.dataset.userId || null;

        if (this.mediaElements.has(media)) return;

        this.mediaElements.set(media, { trackId, userId });

        media.addEventListener('play', () => {
            this.handleMediaPlay(media, trackId, userId);
        });

        media.addEventListener('pause', () => {
            this.handleMediaPause(media);
        });

        media.addEventListener('ended', () => {
            this.handleMediaEnded(media);
        });
    }

    handleMediaPlay(media, trackId, userId) {
        // Добавляем начальное прослушивание
        this.manager.addPlay(trackId, null, userId).catch(console.error);

        // Запускаем отслеживание времени
        const startTime = Math.floor(media.currentTime);
        let lastReportedTime = startTime;
        let trackingInterval = null;

        const reportPlayback = () => {
            if (!media.paused && media.readyState > 0) {
                const currentTime = Math.floor(media.currentTime);
                const timeDiff = currentTime - lastReportedTime;

                if (timeDiff >= 30) { // Отправляем каждые 30 секунд
                    this.manager.addPlay(trackId, currentTime, userId).catch(console.error);
                    lastReportedTime = currentTime;
                }
            }
        };

        trackingInterval = setInterval(reportPlayback, 10000); // Проверяем каждые 10 секунд

        media._trackingData = {
            interval: trackingInterval,
            startTime: startTime
        };
    }

    handleMediaPause(media) {
        this.stopMediaTracking(media);
    }

    handleMediaEnded(media) {
        this.stopMediaTracking(media);

        const trackingData = this.mediaElements.get(media);
        if (trackingData) {
            // Отправляем финальное время прослушивания
            this.manager.addPlay(trackingData.trackId, Math.floor(media.duration), trackingData.userId)
                .catch(console.error);
        }
    }

    stopMediaTracking(media) {
        if (media._trackingData && media._trackingData.interval) {
            clearInterval(media._trackingData.interval);
            media._trackingData.interval = null;
        }
    }

    showFeedback(element, message) {
        // Удаляем существующие сообщения
        this.removeExistingMessages(element);

        const feedback = document.createElement('span');
        feedback.className = 'interaction-feedback';
        feedback.textContent = message;
        feedback.style.cssText = 'color: #28a745; margin-left: 8px; font-size: 12px;';

        element.parentNode.appendChild(feedback);

        setTimeout(() => {
            if (feedback.parentNode) {
                feedback.parentNode.removeChild(feedback);
            }
        }, 2000);
    }

    showError(element, message) {
        // Удаляем существующие сообщения
        this.removeExistingMessages(element);

        const error = document.createElement('span');
        error.className = 'interaction-error';
        error.textContent = message;
        error.style.cssText = 'color: #dc3545; margin-left: 8px; font-size: 12px;';

        element.parentNode.appendChild(error);

        setTimeout(() => {
            if (error.parentNode) {
                error.parentNode.removeChild(error);
            }
        }, 3000);
    }

    removeExistingMessages(element) {
        const existingFeedback = element.parentNode.querySelector('.interaction-feedback');
        const existingError = element.parentNode.querySelector('.interaction-error');

        if (existingFeedback) existingFeedback.remove();
        if (existingError) existingError.remove();
    }

    formatDuration(seconds) {
        if (!seconds || isNaN(seconds)) return '0:00';

        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);

        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    // Метод для обновления статистики извне
    refreshTrackStats(trackId, userId = null) {
        this.manager.clearCache(trackId, userId);
        this.loadStats(trackId, userId);
    }

    // Метод для добавления нового трека динамически
    addTrackContainer(containerElement) {
        const trackId = containerElement.dataset.trackId;
        const userId = containerElement.dataset.userId || null;

        this.containers.set(trackId, {
            container: containerElement,
            userId,
            stats: null
        });

        this.loadStats(trackId, userId);

        // Инициализируем медиа элементы в контейнере
        const mediaElements = containerElement.querySelectorAll('audio, video');
        mediaElements.forEach(media => this.setupMediaTracking(media));
    }
}

// Глобальная инициализация
document.addEventListener('DOMContentLoaded', function () {
    // Проверяем, есть ли объект trackInteractions (WordPress)
    const isWordPress = typeof trackInteractions !== 'undefined';
    const baseUrl = isWordPress ? null : (window.trackInteractionsConfig?.baseUrl || 'https://localhost:3000');

    const manager = new TrackInteractionsManager(baseUrl);
    const ui = new TrackInteractionsUI(manager);

    ui.init();

    // Делаем UI доступным глобально для вызовов из других скриптов
    window.trackInteractionsUI = ui;
});

//-------------------------------- обновленыый js
class TrackInteractionsManager {
    constructor() {
        this.ajaxurl = window.trackInteractions?.ajaxurl || '/wp-admin/admin-ajax.php';
        this.nonce = window.trackInteractions?.nonce;
        this.userId = window.trackInteractions?.user_id || null;
        this.cache = new Map();
        this.pendingRequests = new Map();
    }

    async request(action, data = {}) {
        const cacheKey = `${action}_${JSON.stringify(data)}`;

        if (this.pendingRequests.has(cacheKey)) {
            return this.pendingRequests.get(cacheKey);
        }

        const promise = new Promise(async (resolve, reject) => {
            try {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('nonce', this.nonce);

                for (const [key, value] of Object.entries(data)) {
                    if (value !== null && value !== undefined) {
                        formData.append(key, value.toString());
                    }
                }

                const response = await fetch(this.ajaxurl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (result.success) {
                    resolve(result);
                } else {
                    reject(new Error(result.data || 'Request failed'));
                }
            } catch (error) {
                reject(error);
            } finally {
                this.pendingRequests.delete(cacheKey);
            }
        });

        this.pendingRequests.set(cacheKey, promise);
        return promise;
    }

    async getTrackStats(trackId) {
        const cacheKey = `stats_${trackId}`;

        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        try {
            const response = await this.request('get_track_stats', {
                track_id: trackId,
                user_id: this.userId
            });

            const stats = response.data;
            this.cache.set(cacheKey, stats);
            return stats;
        } catch (error) {
            console.error('Error getting track stats:', error);
            // Возвращаем дефолтные значения при ошибке
            return {
                likes: 0,
                bookmarks: 0,
                plays: 0,
                total_listening_time: 0,
                user_has_liked: false,
                user_has_bookmarked: false
            };
        }
    }

    // ... остальные методы без изменений ...
}

class TrackInteractionsUI {
    constructor() {
        this.manager = new TrackInteractionsManager();
        this.containers = new Map();
        this.mediaTrackers = new Map();
        this.initialized = false;
    }

    init() {
        if (this.initialized) return;

        this.initContainers();
        this.bindGlobalEvents();
        this.initMediaTracking();

        this.initialized = true;
        console.log('Track Interactions UI initialized');
    }

    initContainers() {
        document.querySelectorAll('[data-track-id]').forEach(container => {
            this.addContainer(container);
        });
    }

    addContainer(container) {
        const trackId = container.dataset.trackId;
        if (!trackId) return;

        // Инициализируем состояние кнопок сразу
        this.setInitialButtonState(container);

        this.containers.set(trackId, {
            element: container,
            stats: null,
            isLoading: false
        });

        this.loadStats(trackId);
    }

    setInitialButtonState(container) {
        // Устанавливаем начальное состояние на основе data-атрибутов
        const trackId = container.dataset.trackId;
        const hasLiked = container.dataset.userHasLiked === 'true';
        const hasBookmarked = container.dataset.userHasBookmarked === 'true';

        this.updateButtonState(container, 'like', hasLiked);
        this.updateButtonState(container, 'bookmark', hasBookmarked);
    }

    async loadStats(trackId) {
        const containerInfo = this.containers.get(trackId);
        if (!containerInfo) return;

        containerInfo.isLoading = true;
        this.showLoading(containerInfo.element, true);

        try {
            const stats = await this.manager.getTrackStats(trackId);
            containerInfo.stats = stats;
            this.updateUI(containerInfo.element, stats);

            // Обновляем data-атрибуты для будущих проверок
            containerInfo.element.dataset.userHasLiked = stats.user_has_liked;
            containerInfo.element.dataset.userHasBookmarked = stats.user_has_bookmarked;

        } catch (error) {
            console.error('Failed to load stats for track', trackId, error);
        } finally {
            containerInfo.isLoading = false;
            this.showLoading(containerInfo.element, false);
        }
    }

    // ... остальные методы без изменений ...
}

// Инициализация
document.addEventListener('DOMContentLoaded', () => {
    window.trackInteractionsUI = new TrackInteractionsUI();
    window.trackInteractionsUI.init();
});
//-------------------------------- обновленыый js





// Для Node.js окружения можно добавить конфигурацию
/*
if (typeof window !== 'undefined') {
    window.trackInteractionsConfig = window.trackInteractionsConfig || {
        baseUrl: 'https://localhost:3000'
    };
}
*/
