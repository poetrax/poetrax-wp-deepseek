class PoetsManager {
    constructor() {
        this.currentPage = 1;
        this.totalPages = 1;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadPoets();
    }

    bindEvents() {
        // Поиск
        document.getElementById('search-poets').addEventListener('input', (e) => {
            this.currentPage = 1;
            this.loadPoets(e.target.value);
        });

        // Кнопка добавления
        document.getElementById('add-poet-btn').addEventListener('click', () => {
            this.showPoetForm();
        });

        // Пагинация
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('page-btn')) {
                const page = parseInt(e.target.dataset.page);
                if (page && page !== this.currentPage) {
                    this.currentPage = page;
                    this.loadPoets();
                }
            }
        });
    }

    async loadPoets(search = '') {
        const loader = document.getElementById('poets-loader');
        const container = document.getElementById('poets-container');

        loader.style.display = 'block';
        container.innerHTML = '';

        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: 20,
                ...(search && { search })
            });

            const response = await fetch(`/api/poets.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.renderPoets(data.data);
                this.updatePagination(data.pagination);
            }
        } catch (error) {
            console.error('Ошибка загрузки поэтов:', error);
            container.innerHTML = '<div class="error">Ошибка загрузки</div>';
        } finally {
            loader.style.display = 'none';
        }
    }

    renderPoets(poets) {
        const container = document.getElementById('poets-container');

        if (poets.length === 0) {
            container.innerHTML = '<div class="empty-state">Нет поэтов</div>';
            return;
        }

        const html = poets.map(poet => `
            <div class="poet-card" data-id="${poet.id}">
                <div class="poet-header">
                    <div class="poet-avatar">
                        ${poet.photo ?
                `<img src="${poet.photo}" alt="${poet.last_name}">` :
                `<div class="avatar-placeholder">${poet.last_name.charAt(0)}</div>`
            }
                    </div>
                    <div class="poet-info">
                        <h3>${poet.last_name} ${poet.first_name || ''}</h3>
                        <div class="poet-meta">
                            ${poet.birth_year ? `<span>🎂 ${poet.birth_year}</span>` : ''}
                            ${poet.death_year ? `<span>⚰️ ${poet.death_year}</span>` : ''}
                            <span class="status ${poet.is_active ? 'active' : 'inactive'}">
                                ${poet.is_active ? 'Активен' : 'Неактивен'}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="poet-actions">
                    <button class="btn-sm btn-edit" onclick="poetsManager.editPoet(${poet.id})">
                        ✏️ Редактировать
                    </button>
                    <button class="btn-sm btn-delete" onclick="poetsManager.deletePoet(${poet.id})">
                        🗑️ Удалить
                    </button>
                    <button class="btn-sm btn-view" onclick="poetsManager.viewPoet(${poet.id})">
                        👁️ Просмотр
                    </button>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    async showPoetForm(poetId = null) {
        let poetData = null;

        if (poetId) {
            try {
                const response = await fetch(`/api/poets.php?id=${poetId}`);
                const data = await response.json();
                if (data.success) poetData = data.data;
            } catch (error) {
                console.error('Ошибка загрузки данных поэта:', error);
            }
        }

        // Создание модального окна с формой
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>${poetId ? 'Редактировать поэта' : 'Добавить поэта'}</h2>
                    <button class="modal-close" onclick="this.closest('.modal').remove()">×</button>
                </div>
                <form id="poet-form" class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Фамилия *</label>
                            <input type="text" name="last_name" value="${poetData?.last_name || ''}" required>
                        </div>
                        <div class="form-group">
                            <label>Имя</label>
                            <input type="text" name="first_name" value="${poetData?.first_name || ''}">
                        </div>
                        <div class="form-group">
                            <label>Отчество</label>
                            <input type="text" name="second_name" value="${poetData?.second_name || ''}">
                        </div>
                        <div class="form-group">
                            <label>Суффикс</label>
                            <input type="text" name="name_sfx" value="${poetData?.name_sfx || ''}">
                        </div>
                        <div class="form-group">
                            <label>Год рождения</label>
                            <input type="number" name="birth_year" value="${poetData?.birth_year || ''}">
                        </div>
                        <div class="form-group">
                            <label>Год смерти</label>
                            <input type="number" name="death_year" value="${poetData?.death_year || ''}">
                        </div>
                        <div class="form-group full-width">
                            <label>Биография</label>
                            <textarea name="bio" rows="4">${poetData?.bio || ''}</textarea>
                        </div>
                        <div class="form-group full-width">
                            <label>Фотография URL</label>
                            <input type="url" name="photo" value="${poetData?.photo || ''}">
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active" ${poetData?.is_active ? 'checked' : ''}>
                                Активен
                            </label>
                        </div>
                    </div>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">
                        Отмена
                    </button>
                    <button type="button" class="btn btn-primary" onclick="poetsManager.savePoet(${poetId || ''})">
                        ${poetId ? 'Обновить' : 'Создать'}
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
    }

    async savePoet(poetId = null) {
        const form = document.getElementById('poet-form');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Преобразование чекбокса
        data.is_active = data.is_active === 'on';

        try {
            const url = '/api/poets.php' + (poetId ? `?id=${poetId}` : '');
            const method = poetId ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                alert(poetId ? 'Поэт обновлен' : 'Поэт создан');
                document.querySelector('.modal').remove();
                this.loadPoets();
            } else {
                alert(`Ошибка: ${result.error || result.errors?.join(', ')}`);
            }
        } catch (error) {
            console.error('Ошибка сохранения:', error);
            alert('Ошибка сохранения');
        }
    }

    async deletePoet(id) {
        if (!confirm('Удалить поэта? Это также удалит все его стихи и треки.')) {
            return;
        }

        try {
            const response = await fetch(`/api/poets.php?id=${id}`, {
                method: 'DELETE'
            });

            const result = await response.json();

            if (result.success) {
                this.loadPoets();
            } else {
                alert(`Ошибка удаления: ${result.error}`);
            }
        } catch (error) {
            console.error('Ошибка удаления:', error);
            alert('Ошибка удаления');
        }
    }

    updatePagination(pagination) {
        this.totalPages = pagination.pages;

        const container = document.getElementById('pagination');
        if (!container) return;

        let html = '';

        if (pagination.pages > 1) {
            html += `<button class="page-btn ${this.currentPage === 1 ? 'disabled' : ''}" 
                     data-page="${this.currentPage - 1}">‹</button>`;

            // Показываем первые страницы
            for (let i = 1; i <= Math.min(5, pagination.pages); i++) {
                html += `<button class="page-btn ${i === this.currentPage ? 'active' : ''}" 
                         data-page="${i}">${i}</button>`;
            }

            if (pagination.pages > 5) {
                html += '<span class="page-dots">...</span>';
                // Последние 2 страницы
                for (let i = Math.max(6, pagination.pages - 1); i <= pagination.pages; i++) {
                    html += `<button class="page-btn ${i === this.currentPage ? 'active' : ''}" 
                             data-page="${i}">${i}</button>`;
                }
            }

            html += `<button class="page-btn ${this.currentPage === pagination.pages ? 'disabled' : ''}" 
                     data-page="${this.currentPage + 1}">›</button>`;
        }

        container.innerHTML = html;
    }
}

// Инициализация
//const poetsManager = new PoetsManager();
