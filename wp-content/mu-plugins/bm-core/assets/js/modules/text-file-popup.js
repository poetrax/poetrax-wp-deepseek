/**
 * Text File Popup Module
 * Загрузка и отображение текстовых файлов (стихи) в попапе
 */

class TextFilePopup {
    constructor() {
        this.triggers = document.querySelectorAll('[data-text-file]');
        this.popup = document.getElementById('text-file-popup');
        this.popupContent = this.popup?.querySelector('.popup-content');
        this.popupTitle = this.popup?.querySelector('.popup-title');
        this.popupBody = this.popup?.querySelector('.popup-body');
        this.popupClose = this.popup?.querySelector('.popup-close');
        
        this.init();
    }

    init() {
        if (!this.triggers.length || !this.popup) return;

        this.bindEvents();
        this.createOverlay();
    }

    bindEvents() {
        this.triggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                this.open(trigger);
            });
        });

        if (this.popupClose) {
            this.popupClose.addEventListener('click', () => this.close());
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen()) {
                this.close();
            }
        });
    }

    createOverlay() {
        this.overlay = document.createElement('div');
        this.overlay.className = 'popup-overlay';
        this.overlay.addEventListener('click', () => this.close());
        document.body.appendChild(this.overlay);
    }

    async open(trigger) {
        const filePath = trigger.dataset.textFile;
        const poemName = trigger.dataset.poemName || 'Стихотворение';

        if (!filePath) return;

        this.showLoading(poemName);
        this.showPopup();

        try {
            const content = await this.loadFile(filePath);
            this.showContent(content, poemName);
        } catch (error) {
            console.error('Failed to load text file:', error);
            this.showError('Не удалось загрузить файл', poemName);
        }
    }

    close() {
        this.popup.classList.remove('active');
        this.overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    isOpen() {
        return this.popup.classList.contains('active');
    }

    showPopup() {
        this.popup.classList.add('active');
        this.overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    showLoading(title) {
        if (this.popupTitle) this.popupTitle.textContent = title;
        if (this.popupBody) {
            this.popupBody.innerHTML = `
                <div class="popup-loader">
                    <div class="loader-spinner"></div>
                    <span>Загрузка...</span>
                </div>
            `;
        }
    }

    showContent(content, title) {
        if (this.popupTitle) this.popupTitle.textContent = title;
        if (this.popupBody) {
            this.popupBody.innerHTML = `
                <div class="popup-text">
                    <pre>${this.escape(content)}</pre>
                </div>
            `;
        }
    }

    showError(message, title) {
        if (this.popupTitle) this.popupTitle.textContent = 'Ошибка';
        if (this.popupBody) {
            this.popupBody.innerHTML = `
                <div class="popup-error">
                    <span class="error-icon">⚠️</span>
                    <p>${this.escape(message)}</p>
                </div>
            `;
        }
    }

    async loadFile(filePath) {
        if (!window.ApiClient) {
            throw new Error('ApiClient not found');
        }

        const data = await window.ApiClient.get(`/api/text-file`, {
            path: filePath
        });

        return data.content;
    }

    escape(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    preloadFiles(filePaths) {
        if (!window.ApiClient) return;

        filePaths.forEach(path => {
            window.ApiClient.get(`/api/text-file`, { path })
                .catch(() => {});
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.textFilePopup = new TextFilePopup();
});
