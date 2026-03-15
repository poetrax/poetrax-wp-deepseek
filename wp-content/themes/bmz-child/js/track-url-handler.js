class TrackUrlHandler {
    constructor(baseUrl = 'https://poetrax.ru') {
        this.baseUrl = baseUrl;
    }
    
    // Чтение параметров из текущего URL
    readParams() {
        const params = new URLSearchParams(window.location.search);
        const result = {};
        
        for (const [key, value] of params) {
            result[key] = this.decodeParam(key, value);
        }
        
        return result;
    }
    
    // Декодирование параметра
    decodeParam(key, value) {
        if (key.startsWith('i') && ['igs', 'iss', 'iis'].includes(key)) {
            // Для ID через подчеркивание
            return value.split('_').map(Number).filter(id => !isNaN(id));
        } else if (key === 'dc') {
            // Таймстамп
            return parseInt(value);
        } else {
            // Текст
            return decodeURIComponent(value.replace(/_/g, ' '));
        }
    }
    
    // Создание URL
    createUrl(params) {
        const urlParams = new URLSearchParams();
        
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined) {
                urlParams.set(key, this.encodeParam(key, value));
            }
        });
        
        return `${this.baseUrl}?${urlParams.toString()}`;
    }
    
    // Кодирование параметра
    encodeParam(key, value) {
        if (Array.isArray(value)) {
            // Массив ID в строку через подчеркивание
            return value.join('_');
        } else if (typeof value === 'string') {
            // Текст: пробелы в подчеркивания
            return encodeURIComponent(value.replace(/\s+/g, '_'));
        } else {
            return value.toString();
        }
    }
    
    // AJAX запрос с параметрами
    async fetchTrackData(params) {
        const url = this.createUrl(params);
        
        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) throw new Error('Network response was not ok');
            
            return await response.json();
        } catch (error) {
            console.error('Error fetching track data:', error);
            throw error;
        }
    }
}

// Использование в браузере
/*
const urlHandler = new TrackUrlHandler();
const params = urlHandler.readParams();

// Пример AJAX запроса
async function loadTrackData(trackId) {
    try {
        const data = await urlHandler.fetchTrackData({ it: trackId });
        // Обработка данных
    } catch (error) {
        // Обработка ошибок
    }
}
*/
