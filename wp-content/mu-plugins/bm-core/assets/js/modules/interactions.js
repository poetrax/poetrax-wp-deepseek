/**
 * Interactions Module
 * Управление лайками, закладками, поделиться
 * CSS должен быть в отдельном файле assets/css/components/interactions.css
 */

class Interactions {
    constructor() {
        this.pendingRequests = new Map();
        this.debounceTimers = new Map();
        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // Лайки
        document.addEventListener('click', (e) => {
            const likeBtn = e.target.closest('[data-interaction="like"]');
            if (likeBtn) {
                e.preventDefault();
                this.handleLike(likeBtn);
            }
        });

        // Закладки
        document.addEventListener('click', (e) => {
            const bookmarkBtn = e.target.closest('[data-interaction="bookmark"]');
            if (bookmarkBtn) {
                e.preventDefault();
                this.handleBookmark(bookmarkBtn);
            }
        });

        // Поделиться
        document.addEventListener('click', (e) => {
            const shareBtn = e.target.closest('[data-interaction="share"]');
            if (shareBtn) {
                e.preventDefault();
                this.handleShare(shareBtn);
            }
        });

        // Рекомендации (если есть)
        document.addEventListener('click', (e) => {
            const recommendBtn = e.target.closest('[data-interaction="recommend"]');
            if (recommendBtn) {
                e.preventDefault();
                this.handleRecommend(recommendBtn);
            }
        });
    }

    async handleLike(btn) {
        const trackId = btn.dataset.trackId;
        if (!trackId) return;

        const container = btn.closest('[data-track-container]');
        const countEl = container?.querySelector('[data-count="likes"]');
        const currentCount = countEl ? parseInt(countEl.textContent) || 0 : 0;

        // Оптимистичное обновление UI
        const wasActive = btn.classList.contains('active');
        this.toggleButtonState(btn, !wasActive);
        
        if (countEl) {
            countEl.textContent = wasActive ? currentCount - 1 : currentCount + 1;
        }

        try {
            const result = await this.toggleInteraction(trackId, 'like');
            
            if (!result.success) {
                // Откат при ошибке
                this.toggleButtonState(btn, wasActive);
                if (countEl) {
                    countEl.textContent = currentCount;
                }
                this.showError(result.message || 'Не удалось поставить лайк');
            }
        } catch (error) {
            console.error('Like error:', error);
            // Откат при ошибке
            this.toggleButtonState(btn, wasActive);
            if (countEl) {
                countEl.textContent = currentCount;
            }
            this.showError('Ошибка сети');
        }
    }

    async handleBookmark(btn) {
        const trackId = btn.dataset.trackId;
        if (!trackId) return;

        const container = btn.closest('[data-track-container]');
        const countEl = container?.querySelector('[data-count="bookmarks"]');
        const currentCount = countEl ? parseInt(countEl.textContent) || 0 : 0;

        // Оптимистичное обновление UI
        const wasActive = btn.classList.contains('active');
        this.toggleButtonState(btn, !wasActive);
        
        if (countEl) {
            countEl.textContent = wasActive ? currentCount - 1 : currentCount + 1;
        }

        try {
            const result = await this.toggleInteraction(trackId, 'bookmark');
            
            if (!result.success) {
                // Откат при ошибке
                this.toggleButtonState(btn, wasActive);
                if (countEl) {
                    countEl.textContent = currentCount;
                }
                this.showError(result.message || 'Не удалось добавить в закладки');
            }
        } catch (error) {
            console.error('Bookmark error:', error);
            // Откат при ошибке
            this.toggleButtonState(btn, wasActive);
            if (countEl) {
                countEl.textContent = currentCount;
            }
            this.showError('Ошибка сети');
        }
    }

    async handleShare(btn) {
        const trackId = btn.dataset.trackId;
        const trackName = btn.dataset.trackName || 'Трек';
        const shareUrl = btn.dataset.shareUrl || window.location.href;

        // Собираем данные для шаринга
        const shareData = {
            title: trackName,
            url: shareUrl
        };

        // Пробуем нативный Web Share API
        if (navigator.share) {
            try {
                await navigator.share(shareData);
                this.recordShare(trackId, 'native');
                return;
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error('Share cancelled or failed:', err);
                }
                // Если пользователь отменил — ничего не делаем
                if (err.name === 'AbortError') return;
            }
        }

        // Fallback: показываем попап с соцсетями
        this.showSharePopup(trackId, trackName, shareUrl);
    }

    async handleRecommend(btn) {
        const trackId = btn.dataset.trackId;
        if (!trackId) return;

        const container = btn.closest('[data-track-container]');
        const countEl = container?.querySelector('[data-count="recommends"]');
        const currentCount = countEl ? parseInt(countEl.textContent) || 0 : 0;

        // Оптимистичное обновление UI
        const wasActive = btn.classList.contains('active');
        this.toggleButtonState(btn, !wasActive);
        
        if (countEl) {
            countEl.textContent = wasActive ? currentCount - 1 : currentCount + 1;
        }

        try {
            const result = await this.toggleInteraction(trackId, 'recommend');
            
            if (!result.success) {
                // Откат при ошибке
                this.toggleButtonState(btn, wasActive);
                if (countEl) {
                    countEl.textContent = currentCount;
                }
                this.showError(result.message || 'Не удалось рекомендовать');
            }
        } catch (error) {
            console.error('Recommend error:', error);
            // Откат при ошибке
            this.toggleButtonState(btn, wasActive);
            if (countEl) {
                countEl.textContent = currentCount;
            }
            this.showError('Ошибка сети');
        }
    }

    async toggleInteraction(trackId, type) {
        // Предотвращаем дублирование запросов
        const requestKey = `${trackId}_${type}`;
        
        if (this.pendingRequests.has(requestKey)) {
            return this.pendingRequests.get(requestKey);
        }

        const promise = (async () => {
            try {
                if (!window.ApiClient) {
                    throw new Error('ApiClient not found');
                }

                // Проверяем текущее состояние
                const stats = await window.ApiClient.tracks.get(trackId);
                const isActive = stats[`user_has_${type}`] || false;

                let result;
                if (isActive) {
                    result = await window.ApiClient.tracks[`un${type}`](trackId);
                } else {
                    result = await window.ApiClient.tracks[type](trackId);
                }

                return { success: true, data: result };
            } catch (error) {
                console.error(`${type} error:`, error);
                return { success: false, message: error.message };
            } finally {
                this.pendingRequests.delete(requestKey);
            }
        })();

        this.pendingRequests.set(requestKey, promise);
        return promise;
    }

    async recordShare(trackId, platform) {
        if (!window.ApiClient) return;
        
        try {
            await window.ApiClient.tracks.share(trackId, { platform });
        } catch (error) {
            console.error('Share recording error:', error);
        }
    }

    showSharePopup(trackId, trackName, shareUrl) {
        // Диспатчим событие для открытия модального окна с соцсетями
        document.dispatchEvent(new CustomEvent('bm:show-share-popup', {
            detail: {
                trackId,
                trackName,
                shareUrl,
                platforms: ['vk', 'telegram', 'twitter', 'facebook']
            }
        }));
    }

    toggleButtonState(btn, active) {
        btn.classList.toggle('active', active);
        btn.setAttribute('aria-pressed', active);
    }

    showError(message) {
        // Диспатчим событие для показа уведомления
        document.dispatchEvent(new CustomEvent('bm:show-notification', {
            detail: { message, type: 'error' }
        }));
    }

    // Метод для обновления статистики извне
    async refreshStats(trackId) {
        if (!window.ApiClient) return;

        try {
            const stats = await window.ApiClient.tracks.get(trackId);
            
            // Обновляем все счётчики на странице
            document.querySelectorAll(`[data-track-id="${trackId}"] [data-count]`).forEach(el => {
                const type = el.dataset.count;
                if (stats[type] !== undefined) {
                    el.textContent = stats[type];
                }
            });

            // Обновляем состояние кнопок
            document.querySelectorAll(`[data-track-id="${trackId}"][data-interaction]`).forEach(btn => {
                const type = btn.dataset.interaction;
                const userHas = stats[`user_has_${type}`] || false;
                this.toggleButtonState(btn, userHas);
            });

        } catch (error) {
            console.error('Refresh stats error:', error);
        }
    }

    // Метод для пакетного обновления
    async refreshAllStats(trackIds) {
        await Promise.all(trackIds.map(id => this.refreshStats(id)));
    }
}

// Создаём глобальный экземпляр
document.addEventListener('DOMContentLoaded', () => {
    window.interactions = new Interactions();
});
