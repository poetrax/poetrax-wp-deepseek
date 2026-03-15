'use strict';
class UniversalPropertiesLoader {
    constructor(options = {}) {
        this.config = window.propertiesConfig || {};
        this.ajaxUrl = window.ajaxurl || (options.ajaxUrl || '');
        this.nonce = window.propertiesNonce || (options.nonce || '');
        this.cache = new Map();
        this.cacheTimeout = options.cacheTimeout || 300000; // 5 минут
    }

    /**
     * Основной метод загрузки свойств
     */
    async loadProperties(propertyType, options = {}) {
        const cacheKey = propertyType + (options.forceRefresh ? '_' + Date.now() : '');

        // Проверка кэша
        if (!options.forceRefresh && this.getFromCache(cacheKey)) {
            return this.getFromCache(cacheKey);
        }

        try {
            const response = await this.makeAjaxRequest(propertyType);
            const data = await this.parseResponse(response);

            // Кэшируем результат
            this.setToCache(cacheKey, data);

            return data;

        } catch (error) {
            console.error(`Error loading ${propertyType}:`, error);
            throw error;
        }
    }

    /**
     * Универсальный AJAX запрос
     */
    async makeAjaxRequest(propertyType) {
        const response = await fetch(this.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_properties',
                property_type: propertyType,
                nonce: this.nonce
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response;
    }

    /**
     * Улучшенный парсинг ответа (ваш код)
     */
    async parseResponse(response) {
        let text = await response.text();
        text = this.cleanResponseText(text);

        try {
            return JSON.parse(text);
        } catch (parseError) {
            console.error('UniversalPropertiesLoader: JSON parse error:', parseError);

            const jsonMatch = text.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
                try {
                    return JSON.parse(jsonMatch[0]);
                } catch (secondError) {
                    throw new Error('Invalid JSON format in response');
                }
            }
            throw parseError;
        }
    }

    /**
     * Очистка текста ответа (ваш код)
     */
    cleanResponseText(text) {
        if (typeof text !== 'string') return text;
        return text
            .replace(/^\uFEFF/, '')
            .replace(/^\uFFFE/, '')
            .replace(/^[\x00-\x1F\x7F]+/, '')
            .trim();
    }

    /**
     * Кэширование
     */
    setToCache(key, data) {
        this.cache.set(key, {
            data: data,
            timestamp: Date.now()
        });
    }

    getFromCache(key) {
        const cached = this.cache.get(key);
        if (cached && (Date.now() - cached.timestamp) < this.cacheTimeout) {
            return cached.data;
        }
        return null;
    }

    clearCache(type = null) {
        if (type) {
            for (let key of this.cache.keys()) {
                if (key.startsWith(type)) {
                    this.cache.delete(key);
                }
            }
        } else {
            this.cache.clear();
        }
    }

    /**
     * Вспомогательные методы
     */
    async getAllProperties(types = null) {
        const typesToLoad = types || Object.keys(this.config);
        const results = {};

        for (const type of typesToLoad) {
            try {
                results[type] = await this.loadProperties(type);
            } catch (error) {
                results[type] = { error: error.message };
            }
        }

        return results;
    }

    getConfig() {
        return this.config;
    }

    isValidType(type) {
        return this.config.hasOwnProperty(type);
    }
}

// Пример использования
document.addEventListener('DOMContentLoaded', function () {
    // Инициализация загрузчика
    const propertiesLoader = new UniversalPropertiesLoader();

    // Пример загрузки инструментов
    async function loadInstruments() {
        try {
            const result = await propertiesLoader.loadProperties('instruments');
            if (result.success) {
                console.log('Instruments loaded:', result.data);
                displayProperties(result.data.data, 'instruments');
            }
        } catch (error) {
            console.error('Failed to load instruments:', error);
        }
    }

    // Пример загрузки всех свойств
    async function loadAllMusicProperties() {
        const types = [
            'instruments',
            'styles',
            'voice_character',
            'track_theme',
            'genre_select',
            'voice_register',
            'track_mood',
            'poem_select',
            'poet_select',
            'style_select',
            'temp_select',
            'presentation_select'
        ];
        const results = await propertiesLoader.getAllProperties(types);
        console.log('All music properties:', results);
    }

    // Функция для отображения свойств
    function displayProperties(properties, type) {
        const container = document.getElementById(`${type}-container`);
        if (!container) return;

        container.innerHTML = properties.map(item => `
            <div class="property-item" data-id="${item.id}">
                <input type="checkbox" id="${type}-${item.id}">
                <label for="${type}-${item.id}">${item.name}</label>
                ${item.suno_prompt ? `<div class="suno-prompt">${item.suno_prompt}</div>` : ''}
            </div>
        `).join('');
    }

    // Инициализация
    loadInstruments();
});
