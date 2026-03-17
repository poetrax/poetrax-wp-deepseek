/**
 * Social Share Module
 * Кнопки для шаринга в социальных сетях
 */

class SocialShare {
    constructor() {
        this.container = document.getElementById('social-share-container');
        if (!this.container) return;

        this.init();
    }

    init() {
        this.currentUrl = encodeURIComponent(window.location.href);
        this.pageTitle = encodeURIComponent(document.title);
        this.renderButtons();
    }

    getShareUrl(platform) {
        const urls = {
            facebook: `https://www.facebook.com/sharer/sharer.php?u=${this.currentUrl}`,
            twitter: `https://twitter.com/intent/tweet?url=${this.currentUrl}&text=${this.pageTitle}`,
            vk: `https://vk.com/share.php?url=${this.currentUrl}&title=${this.pageTitle}`,
            telegram: `https://t.me/share/url?url=${this.currentUrl}&text=${this.pageTitle}`,
            whatsapp: `https://wa.me/?text=${this.pageTitle}%20${this.currentUrl}`,
            linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${this.currentUrl}`,
            pinterest: `https://pinterest.com/pin/create/button/?url=${this.currentUrl}&description=${this.pageTitle}`,
            odnoklassniki: `https://connect.ok.ru/offer?url=${this.currentUrl}&title=${this.pageTitle}`,
            viber: `viber://forward?text=${this.pageTitle}%20${this.currentUrl}`,
            email: `mailto:?subject=${this.pageTitle}&body=${this.currentUrl}`
        };

        return urls[platform] || '#';
    }

    getIcon(platform) {
        const icons = {
            facebook: '📘',
            twitter: '🐦',
            vk: '📱',
            telegram: '📨',
            whatsapp: '📞',
            linkedin: '🔗',
            pinterest: '📌',
            odnoklassniki: '👥',
            viber: '💬',
            email: '✉️'
        };

        return icons[platform] || '🔗';
    }

    getLabel(platform) {
        const labels = {
            facebook: 'Facebook',
            twitter: 'X',
            vk: 'VK',
            telegram: 'Telegram',
            whatsapp: 'WhatsApp',
            linkedin: 'LinkedIn',
            pinterest: 'Pinterest',
            odnoklassniki: 'OK',
            viber: 'Viber',
            email: 'Email'
        };

        return labels[platform] || platform;
    }

    renderButtons() {
        const platforms = [
            'facebook', 'vk', 'telegram', 'twitter', 'whatsapp', 
            'odnoklassniki', 'viber', 'linkedin', 'email'
        ];

        let html = '<div class="social-share-buttons">';
        
        platforms.forEach(platform => {
            html += `
                <button class="social-share-btn social-share-${platform}" 
                        data-platform="${platform}"
                        data-share-url="${this.getShareUrl(platform)}"
                        aria-label="Поделиться в ${this.getLabel(platform)}">
                    <span class="social-icon">${this.getIcon(platform)}</span>
                    <span class="social-label">${this.getLabel(platform)}</span>
                </button>
            `;
        });

        html += '</div>';
        
        this.container.innerHTML = html;
        this.bindEvents();
    }

    bindEvents() {
        this.container.querySelectorAll('.social-share-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleShare(btn);
            });
        });
    }

    handleShare(btn) {
        const platform = btn.dataset.platform;
        const url = btn.dataset.shareUrl;
        const trackId = this.container.dataset.trackId;

        // Пробуем нативный Web Share API если доступен
        if (navigator.share && (platform === 'native' || !platform)) {
            navigator.share({
                title: document.title,
                url: window.location.href
            }).catch(console.error);
            return;
        }

        // Открываем попап для соцсетей
        this.openSharePopup(url, platform);
        
        // Отправляем статистику
        if (trackId && window.ApiClient) {
            window.ApiClient.tracks.share(trackId, { platform }).catch(console.error);
        }
    }

    openSharePopup(url, platform) {
        const width = 600;
        const height = 400;
        const left = (window.screen.width - width) / 2;
        const top = (window.screen.height - height) / 2;

        window.open(
            url,
            `share-${platform}`,
            `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`
        );
    }

    getShareCount(platform) {
        // Здесь можно добавить запрос к API для получения статистики
        return 0;
    }

    updateShareCount(platform, count) {
        const btn = this.container.querySelector(`.social-share-${platform}`);
        if (!btn) return;

        let countEl = btn.querySelector('.share-count');
        if (!countEl) {
            countEl = document.createElement('span');
            countEl.className = 'share-count';
            btn.appendChild(countEl);
        }

        countEl.textContent = count > 0 ? count : '';
    }

    async refreshStats(trackId) {
        if (!trackId || !window.ApiClient) return;

        try {
            const stats = await window.ApiClient.tracks.get(trackId);
            
            if (stats.shares_by_platform) {
                Object.entries(stats.shares_by_platform).forEach(([platform, count]) => {
                    this.updateShareCount(platform, count);
                });
            }
        } catch (error) {
            console.error('Failed to load share stats:', error);
        }
    }

    static initAll() {
        document.querySelectorAll('[data-social-share]').forEach(container => {
            new SocialShare(container);
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Инициализируем для контейнера по умолчанию
    if (document.getElementById('social-share-container')) {
        window.socialShare = new SocialShare();
    }
    
    // Инициализируем для всех кастомных контейнеров
    SocialShare.initAll();
});
