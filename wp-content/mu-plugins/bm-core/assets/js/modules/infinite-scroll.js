/**
 * Infinite Scroll Module
 * Загружает следующие страницы при прокрутке
 */

class InfiniteScroll {
    constructor(container, options = {}) {
        this.container = typeof container === 'string' 
            ? document.querySelector(container) 
            : container;
            
        this.options = {
            loadingClass: 'is-loading',
            endMessage: 'Больше нет записей',
            scrollTrigger: '.scroll-trigger',
            ...options
        };

        this.lastId = null;
        this.loading = false;
        this.hasMore = true;
        this.filters = {};
        this.observer = null;

        this.init();
    }

    init() {
        if (!this.container) {
            console.error('InfiniteScroll: container not found');
            return;
        }

        this.createTrigger();
        this.initObserver();
    }

    createTrigger() {
        // Создаём триггер, если его нет
        let trigger = this.container.querySelector(this.options.scrollTrigger);
        
        if (!trigger) {
            trigger = document.createElement('div');
            trigger.className = this.options.scrollTrigger.replace('.', '');
            trigger.style.height = '1px';
            this.container.appendChild(trigger);
        }
    }

    initObserver() {
        const trigger = this.container.querySelector(this.options.scrollTrigger);
        
        if (!trigger) return;

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.loading && this.hasMore) {
                    this.loadMore();
                }
            });
        });

        this.observer.observe(trigger);
    }

    async loadMore() {
        if (this.loading || !this.hasMore) return;

        this.loading = true;
        this.container.classList.add(this.options.loadingClass);

        try {
            const data = await this.fetchPage();
            
            if (data.items && data.items.length > 0) {
                this.renderItems(data.items);
                this.lastId = data.last_id || this.lastId;
                this.hasMore = data.has_more || false;
            } else {
                this.hasMore = false;
                this.showEndMessage();
            }
        } catch (error) {
            console.error('InfiniteScroll error:', error);
            this.hasMore = false;
        } finally {
            this.loading = false;
            this.container.classList.remove(this.options.loadingClass);
        }
    }

    async fetchPage() {
        // Используем глобальный ApiClient
        if (!window.ApiClient) {
            throw new Error('ApiClient not found');
        }

        const endpoint = this.options.endpoint || this.container.dataset.endpoint || '/api/tracks';
        
        return await window.ApiClient.get(endpoint, {
            last_id: this.lastId,
            ...this.filters
        });
    }

    renderItems(items) {
        items.forEach(item => {
            const element = this.createItemElement(item);
            this.container.appendChild(element);
        });
    }

    createItemElement(item) {
        // Используем шаблон из data-атрибута или создаём по умолчанию
        const template = this.options.itemTemplate || this.container.dataset.itemTemplate;
        
        if (template) {
            const div = document.createElement('div');
            div.innerHTML = template;
            return div.firstElementChild;
        }

        // Простой шаблон по умолчанию
        const div = document.createElement('div');
        div.className = 'infinite-item';
        div.innerHTML = `
            <div class="item-content">
                <h3>${item.title || item.name || 'Без названия'}</h3>
            </div>
        `;
        return div;
    }

    showEndMessage() {
        const message = document.createElement('div');
        message.className = 'infinite-end-message';
        message.textContent = this.options.endMessage;
        this.container.appendChild(message);
    }

    reset(filters = {}) {
        this.lastId = null;
        this.hasMore = true;
        this.filters = filters;
        this.container.innerHTML = '';
        this.createTrigger();
    }

    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }
}

// Авто-инициализация для элементов с data-infinite-scroll
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-infinite-scroll]').forEach(container => {
        new InfiniteScroll(container, {
            endpoint: container.dataset.endpoint,
            itemTemplate: container.dataset.itemTemplate
        });
    });
});
