/**
 * Live Search Module
 * Мгновенный поиск с автодополнением
 */

class LiveSearch {
    constructor(input, options = {}) {
        this.input = typeof input === 'string' 
            ? document.querySelector(input) 
            : input;
            
        this.options = {
            minChars: 3,
            delay: 300,
            resultsContainer: '#search-results',
            endpoint: '/api/search',
            maxResults: 10,
            ...options
        };

        this.resultsContainer = typeof this.options.resultsContainer === 'string'
            ? document.querySelector(this.options.resultsContainer)
            : this.options.resultsContainer;

        this.timeout = null;
        this.currentQuery = '';
        this.abortController = null;

        this.init();
    }

    init() {
        if (!this.input) {
            console.error('LiveSearch: input not found');
            return;
        }

        if (!this.resultsContainer) {
            console.error('LiveSearch: results container not found');
            return;
        }

        this.bindEvents();
    }

    bindEvents() {
        // Ввод текста
        this.input.addEventListener('input', () => {
            const query = this.input.value.trim();
            this.currentQuery = query;

            // Очищаем предыдущий таймер
            clearTimeout(this.timeout);

            // Если запрос пустой или слишком короткий
            if (query.length < this.options.minChars) {
                this.hideResults();
                return;
            }

            // Ждём, пока пользователь перестанет печатать
            this.timeout = setTimeout(() => {
                this.search(query);
            }, this.options.delay);
        });

        // Фокус на поле ввода
        this.input.addEventListener('focus', () => {
            if (this.resultsContainer.children.length > 0) {
                this.showResults();
            }
        });

        // Клик вне результатов
        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target) && !this.resultsContainer.contains(e.target)) {
                this.hideResults();
            }
        });

        // Клавиши
        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideResults();
            }
        });
    }

    async search(query) {
        // Отменяем предыдущий запрос
        if (this.abortController) {
            this.abortController.abort();
        }

        this.abortController = new AbortController();

        try {
            this.showLoader();

            // Используем глобальный ApiClient
            if (!window.ApiClient) {
                throw new Error('ApiClient not found');
            }

            const results = await window.ApiClient.get(this.options.endpoint, {
                q: query,
                limit: this.options.maxResults
            }, {
                signal: this.abortController.signal
            });

            this.renderResults(results);
            this.showResults();

        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }
            console.error('LiveSearch error:', error);
            this.renderError(error.message);
        } finally {
            this.abortController = null;
        }
    }

    renderResults(results) {
        if (!results || Object.keys(results).length === 0) {
            this.renderEmpty();
            return;
        }

        let html = '';

        // Треки
        if (results.tracks && results.tracks.length > 0) {
            html += this.renderSection('Треки', results.tracks, (item) => `
                <a href="/track/${item.id}" class="search-item">
                    <span class="search-item-title">${this.escape(item.track_name)}</span>
                    <span class="search-item-subtitle">${this.escape(item.poet_name || '')}</span>
                </a>
            `);
        }

        // Стихи
        if (results.poems && results.poems.length > 0) {
            html += this.renderSection('Стихотворения', results.poems, (item) => `
                <a href="/poem/${item.id}" class="search-item">
                    <span class="search-item-title">${this.escape(item.name)}</span>
                    <span class="search-item-subtitle">${this.escape(item.poet_name || '')}</span>
                </a>
            `);
        }

        // Поэты
        if (results.poets && results.poets.length > 0) {
            html += this.renderSection('Поэты', results.poets, (item) => `
                <a href="/poet/${item.id}" class="search-item">
                    <span class="search-item-title">${this.escape(item.full_name || item.name)}</span>
                </a>
            `);
        }

        // Ссылка на полный поиск
        if (results.total && results.total > this.options.maxResults) {
            html += `
                <div class="search-footer">
                    <a href="/search?q=${encodeURIComponent(this.currentQuery)}">
                        Все результаты (${results.total})
                    </a>
                </div>
            `;
        }

        this.resultsContainer.innerHTML = html;
    }

    renderSection(title, items, renderItem) {
        if (!items || items.length === 0) return '';

        return `
            <div class="search-section">
                <div class="search-section-title">${title}</div>
                ${items.map(renderItem).join('')}
            </div>
        `;
    }

    renderEmpty() {
        this.resultsContainer.innerHTML = `
            <div class="search-empty">
                Ничего не найдено по запросу "${this.escape(this.currentQuery)}"
            </div>
        `;
    }

    renderError(message) {
        this.resultsContainer.innerHTML = `
            <div class="search-error">
                Ошибка поиска: ${this.escape(message)}
            </div>
        `;
    }

    showLoader() {
        this.resultsContainer.innerHTML = `
            <div class="search-loader">
                <div class="loader-spinner"></div>
                <span>Поиск...</span>
            </div>
        `;
    }

    showResults() {
        this.resultsContainer.classList.add('active');
    }

    hideResults() {
        this.resultsContainer.classList.remove('active');
    }

    escape(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Авто-инициализация
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-live-search]').forEach(input => {
        new LiveSearch(input, {
            resultsContainer: input.dataset.results || '#search-results',
            endpoint: input.dataset.endpoint || '/api/search'
        });
    });
});
