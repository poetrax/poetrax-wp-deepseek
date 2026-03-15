'use strict';
class TextFilePopup {
    constructor() {
        console.log('constructor_TextFilePopup');
        this.triggers = document.querySelectorAll('.text-file-trigger');
        this.init();
    }

    init() {
        // Проверяем необходимые зависимости
        if (typeof textFileAjax === 'undefined') {
            console.error('TextFileAjax object not found');
            return;
        }

        // Проверяем доступность Popup Maker
        if (typeof PUM === 'undefined') {
            console.error('Popup Maker не загружен');
            return;
        }
       
        // Обработчики для textFiles
        this.triggers.forEach(trigger => {
            trigger.addEventListener('click', () => {
                console.log('trigger ', trigger);
                this.loadFileContent(trigger);
            });
        });

        // Закрытие по ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closePopup();
            }
        });

        console.log('TextFilePopup initialized');
    }

    async loadFileContent(trigger) {
        console.log('trigger.dataset ', trigger.dataset);

        const filePath = trigger.dataset.textFile;
        const popupId = trigger.dataset.popupId;
        const poemName = trigger.dataset.namePoem;

        console.log('TextFilePopup: Loading file', { filePath, popupId, poemName });

        if (!filePath) {
            console.error('No file path specified');
            return;
        }

        // Показываем индикатор загрузки
        this.showLoading();

        try {
           
            const response = await fetch(textFileAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'load_text_file',
                    file_path: filePath,
                    nonce: textFileAjax.nonce
                })
            });
           
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

        console.log(`TextFilePopup: Response status: ${response.status}`);

        // Обрабатываем ответ
        const data = await this.parseResponse(response);
        console.log('TextFilePopup: Processed data');
      
            if (response.ok) {
                console.log('data.data ', data.data.content);
                const content = data.data.content;
                this.showContent(content, poemName);
            } else {
                this.showError(data.data.content || 'Файл не найден');
            }
        } catch (error) {
            console.error('TextFilePopup: Error loading file:', error);
            if (error.name === 'AbortError') {
                this.showError('Превышено время ожидания загрузки файла');
            } else {
                this.showError('Ошибка загрузки файла: ' + error.message);
            }
        }
    }

    // Улучшенный парсинг ответа
    async parseResponse(res) {
        // Получаем текст
        let text = await res.text();

        // Очищаем от BOM и лишних символов
        text = this.cleanResponseText(text);

        try {
            return JSON.parse(text);
        } catch (parseError) {
            console.error('TextFilePopup: JSON parse error:', parseError);

            // Пробуем найти JSON в тексте если есть мусор вокруг
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

    // Очистка текста ответа
    cleanResponseText(text) {
        return text
            .replace(/^\uFEFF/, '') // Remove UTF-8 BOM
            .replace(/^\uFFFE/, '') // Remove UTF-16 BOM
            .replace(/^[\x00-\x1F\x7F]+/, '') // Remove control characters
            .trim();
    }

    showLoading() {
        console.log('Showing loading for popup');
        try {
            // Открываем popup
            if (window.PUM && typeof PUM.open === 'function') {
                PUM.open(1247);
            } else {
                console.warn('TextFilePopup: PUM not available, using fallback');
                this.openFallbackPopup();
            }

            // Устанавливаем содержимое
            this.setPopupContent(`
                <div class="text-file-loading">
                <div class="loading-spinner"></div>
                <p>Загрузка стиха...</p>
                </div>
            `);
          
        } catch (error) {
            console.error('TextFilePopup: Error showing loading:', error);
        }

    }

    showContent(content, poemName) {
        console.log('Showing content for popmake-1247');
        this.setPopupContent(`
            <div class="text-file-content">
                <div class="text-header">
                    <h3>${poemName}</h3>
                </div>
                <div class="text-body">
                    <pre>${this.escapeHtml(content)}</pre>
                </div>
            </div>
        `);
    }

    // Функция экранирования HTML
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showError(message) {
        console.error('Showing error:', message);
        this.setPopupContent(`
            <div class="text-file-error">
                <div class="text-header">
                    <h3>Ошибка</h3>
                </div>
                <div class="error-message">
                    <i aria-hidden="true" class="fas fa-exclamation-triangle"></i>
                    <p>${message}</p>
                </div>
            </div>
        `);
    }

    setPopupContent(content) {
        try {
            console.log('TextFilePopup: Setting content for popup');
            const popup = document.querySelector('#pum-1247');
            if (!popup) {
                console.error('TextFilePopup: Popup not found');
                return;
            }

            const contentContainer = popup.querySelector('.pum-content');
            if (!contentContainer) {
                console.error('TextFilePopup: Content container not found');
                return;
            }
            contentContainer.innerHTML = content;

        } catch (error) {
            console.error('TextFilePopup: Error setting popup content: ', error);
        }
    }

   
    closePopup() {
        if (window.PUM) {
            PUM.closeAll();
        }
    }

    openFallbackPopup() {
        const existingFallback = document.querySelector('.text-file-fallback-popup');
        if (existingFallback) {
            existingFallback.remove();
        }

        const fallbackDiv = document.createElement('div');
        fallbackDiv.className = 'text-file-fallback-popup';
        fallbackDiv.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 20px rgba(0,0,0,0.3);
        z-index: 999999;
        max-width: 80%;
        max-height: 80%;
        overflow: auto;
    `;
        fallbackDiv.innerHTML = `
        <div class="text-file-loading">
            <div class="loading-spinner"></div>
            <p>Загрузка стиха...</p>
        </div>
    `;
        document.body.appendChild(fallbackDiv);
    }
}

// Безопасная инициализация
document.addEventListener('DOMContentLoaded', () => {
    try {
        //Даем время для загрузки всех зависимостей
        setTimeout(() => {
            try {
                new TextFilePopup();
                console.log('TextFilePopup initialized successfully');
            } catch (error) {
                console.error('Error initializing TextFilePopup:', error);
            }
        }, 100);
    } catch (error) {
        console.error('Error initializing TextFilePopup:', error);
    }
});