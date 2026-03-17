/**
 * Universal Properties Loader Module
 * Загрузка справочных данных (инструменты, стили, жанры и т.д.)
 */

class UniversalPropertiesLoader {
    constructor(options = {}) {
        this.options = {
            cacheTimeout: 300000, // 5 минут
            ...options
        };

        this.cache = new Map();
        this.pendingRequests = new Map();
        this.config = window.propertiesConfig || {};
        
        this.init();
    }

    init() {
        this.loadConfig();
    }

    loadConfig() {
        if (!this.config || Object.keys(this.config).length === 0) {
            console.warn('UniversalPropertiesLoader: config not found');
        }
    }

    async loadProperties(propertyType, options = {}) {
        const cacheKey = `${propertyType}_${options.forceRefresh ? Date.now() : ''}`;

        // Проверяем кэш
        if (!options.forceRefresh && this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < this.options.cacheTimeout) {
                return cached.data;
            }
            this.cache.delete(cacheKey);
        }

        // Проверяем, не загружается ли уже
        if (this.pendingRequests.has(propertyType)) {
            return this.pendingRequests.get(propertyType);
        }

        const promise = this.fetchProperties(propertyType);
        this.pendingRequests.set(propertyType, promise);

        try {
            const data = await promise;
            
            // Сохраняем в кэш
            this.cache.set(cacheKey, {
                data,
                timestamp: Date.now()
            });

            return data;

        } finally {
            this.pendingRequests.delete(propertyType);
        }
    }

    async fetchProperties(propertyType) {
        if (!window.ApiClient) {
            throw new Error('ApiClient not found');
        }

        return await window.ApiClient.get(`/api/properties/${propertyType}`);
    }

    async loadMultiple(propertyTypes, options = {}) {
        const promises = propertyTypes.map(type => 
            this.loadProperties(type, options).catch(error => ({
                error: true,
                type,
                message: error.message
            }))
        );

        const results = await Promise.all(promises);
        
        return propertyTypes.reduce((acc, type, index) => {
            acc[type] = results[index];
            return acc;
        }, {});
    }

    getCached(propertyType) {
        for (const [key, value] of this.cache.entries()) {
            if (key.startsWith(propertyType)) {
                return value.data;
            }
        }
        return null;
    }

    clearCache(propertyType = null) {
        if (propertyType) {
            for (const key of this.cache.keys()) {
                if (key.startsWith(propertyType)) {
                    this.cache.delete(key);
                }
            }
        } else {
            this.cache.clear();
        }
    }

    async refresh(propertyType) {
        this.clearCache(propertyType);
        return this.loadProperties(propertyType, { forceRefresh: true });
    }

    renderSelect(container, items, options = {}) {
        const select = document.createElement('select');
        select.className = options.className || 'properties-select';
        select.multiple = options.multiple || false;

        // Пустой option
        if (!options.multiple && !options.hideEmpty) {
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = options.emptyText || '-- Выберите --';
            select.appendChild(emptyOption);
        }

        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            
            if (options.selected && options.selected.includes(item.id)) {
                option.selected = true;
            }

            if (item.suno_prompt) {
                option.dataset.sunoPrompt = item.suno_prompt;
            }

            select.appendChild(option);
        });

        if (typeof container === 'string') {
            document.querySelector(container).appendChild(select);
        } else if (container) {
            container.appendChild(select);
        }

        return select;
    }

    renderCheckboxGroup(container, items, options = {}) {
        const group = document.createElement('div');
        group.className = options.className || 'properties-checkbox-group';

        items.forEach(item => {
            const label = document.createElement('label');
            label.className = 'property-checkbox';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = options.name || 'properties';
            checkbox.value = item.id;

            if (options.selected && options.selected.includes(item.id)) {
                checkbox.checked = true;
            }

            label.appendChild(checkbox);
            label.appendChild(document.createTextNode(item.name));

            if (item.suno_prompt) {
                const promptSpan = document.createElement('span');
                promptSpan.className = 'suno-prompt-tip';
                promptSpan.textContent = '✨';
                promptSpan.title = item.suno_prompt;
                label.appendChild(promptSpan);
            }

            group.appendChild(label);
        });

        if (typeof container === 'string') {
            document.querySelector(container).appendChild(group);
        } else if (container) {
            container.appendChild(group);
        }

        return group;
    }

    async populateSelect(selectElement, propertyType, options = {}) {
        try {
            const data = await this.loadProperties(propertyType);
            
            // Очищаем select
            selectElement.innerHTML = '';

            // Добавляем пустой option
            if (!options.hideEmpty) {
                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = options.emptyText || '-- Выберите --';
                selectElement.appendChild(emptyOption);
            }

            // Добавляем опции
            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                
                if (item.suno_prompt) {
                    option.dataset.sunoPrompt = item.suno_prompt;
                }

                if (options.selected && options.selected === item.id) {
                    option.selected = true;
                }

                selectElement.appendChild(option);
            });

        } catch (error) {
            console.error(`Failed to populate select with ${propertyType}:`, error);
            selectElement.innerHTML = '<option value="">Ошибка загрузки</option>';
        }
    }

    async search(propertyType, query, limit = 20) {
        if (!window.ApiClient) {
            throw new Error('ApiClient not found');
        }

        return await window.ApiClient.get(`/api/properties/${propertyType}/search`, {
            q: query,
            limit
        });
    }

    getPropertyName(propertyType, id) {
        const cached = this.getCached(propertyType);
        if (!cached) return null;

        const item = cached.find(i => i.id == id);
        return item ? item.name : null;
    }

    getSunoPrompt(propertyType, id) {
        const cached = this.getCached(propertyType);
        if (!cached) return null;

        const item = cached.find(i => i.id == id);
        return item ? item.suno_prompt : null;
    }

    isValidType(propertyType) {
        return this.config.hasOwnProperty(propertyType);
    }

    getTypes() {
        return Object.keys(this.config);
    }

    getConfig() {
        return this.config;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.propertiesLoader = new UniversalPropertiesLoader();
});
