let propertiesSelector = null;
class PropertiesSelector {
    constructor() {
        console.log('constructor');
        this.properties = [];
        this.selectedProperties = new Set();
        this.filteredProperties = [];
        this.currentCategory = 'all';
        this.searchTerm = '';

        this.init();
        this.loadProperties();
        this.attachEventListeners();
        //Глобальный экземпляр
        propertiesSelector = this;
    }

    init() {
        this.elements = {
            propertiesGrid: document.getElementById('propertiesGrid'),
            selectedList: document.getElementById('selectedList'),
            categoriesList: document.getElementById('categoriesList'),
            searchInput: document.getElementById('searchInput'),
            selectedCount: document.getElementById('selectedCount'),
            selectAll: document.getElementById('selectAll'),
            clearAll: document.getElementById('clearAll'),
            saveSelection: document.getElementById('saveSelection'),
            resetSelection: document.getElementById('resetSelection'),
            message: document.getElementById('message')
        };

        // Проверка элементов
        for (const [key, element] of Object.entries(this.elements)) {
            if (!element) {
                console.error(`Element not found: ${key}`);
            }
        }
        console.log('init completed');
    }

    attachEventListeners() {
        // Привязка обработчиков событий для категорий
        if (this.elements.categoriesList) {
            this.elements.categoriesList.addEventListener('click', (e) => {
                const categoryItem = e.target.closest('.category-item');
                if (categoryItem) {
                    this.handleCategoryChange(categoryItem.dataset.category);
                }
            });
        }

        if (this.elements.searchInput) {
            this.elements.searchInput.addEventListener('input', (e) => {
                this.handleSearch(e.target.value);
            });
        }

        // Обработчики для кнопок управления
        if (this.elements.selectAll) {
            this.elements.selectAll.addEventListener('click', () => {
                this.selectAll();
            });
        }

        if (this.elements.clearAll) {
            this.elements.clearAll.addEventListener('click', () => {
                this.clearAll();
            });
        }

        if (this.elements.saveSelection) {
            this.elements.saveSelection.addEventListener('click', () => {
                this.saveSelection();
            });
        }

        if (this.elements.resetSelection) {
            this.elements.resetSelection.addEventListener('click', () => {
                this.resetSelection();
            });
        }

        console.log('Event listeners attached');
    };

    async loadProperties() {
        try {
            this.showLoading();
            // AJAX запрос
            const response = await fetch(textFileAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_properties',
                    file_path: '',
                    nonce: textFileAjax.nonce
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await this.parseResponse(response);
            this.properties = data.data;
            this.filteredProperties = data.data;
            this.renderCategories();
            this.renderProperties();
            this.updateSelectedCount();
          
        } catch (error) {
            console.error('Error loading properties:', error);
            this.showMessage('Ошибка загрузки свойств: ' + error.message, 'error');
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
        if (typeof text !== 'string') return text;
        return text
            .replace(/^\uFEFF/, '') // Remove UTF-8 BOM
            .replace(/^\uFFFE/, '') // Remove UTF-16 BOM
            .replace(/^[\x00-\x1F\x7F]+/, '') // Remove control characters
            .trim();
    }

    renderCategories() {
        if (this.properties.length === 0) return;

        const categories = ['all', ...new Set(this.properties.map(p => p.category))];
        console.log('renderCategories:', categories);

        this.elements.categoriesList.innerHTML = categories.map(category => `
            <div class="category-item ${category === 'all' ? 'active' : ''}" 
                 data-category="${category}">
                ${category === 'all' ? 'Все категории' : category}
            </div>
        `).join('');
    }

    showLoading() {
        if (this.elements.propertiesGrid) {
            this.elements.propertiesGrid.innerHTML = '<div class="loading">Загрузка...</div>';
        }
    }

    renderProperties() {
        if (!this.elements.propertiesGrid) return;

        if (this.filteredProperties.length === 0) {
            this.elements.propertiesGrid.innerHTML = '<div class="no-properties">Свойства не найдены</div>';
            return;
        }

        this.elements.propertiesGrid.innerHTML = this.filteredProperties.map(property => `
        <div class="property-item" data-id="${property.id}">
            <input type="checkbox" id="property-${property.id}" 
                   ${this.selectedProperties.has(property.id) ? 'checked' : ''}>
            <label for="property-${property.id}">
                <strong>${property.name || 'Без названия'}</strong>
                ${property.category ? `<span class="category">${property.category}</span>` : ''}
            </label>
        </div>
    `).join('');

    // Привязка обработчиков для чекбоксов
        this.elements.propertiesGrid.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.handlePropertySelection(e.target);
            });
        });
    }

    renderSelectedProperties() {
        if (!this.elements.selectedList) return;

        const selectedArray = Array.from(this.selectedProperties);

        if (selectedArray.length === 0) {
            this.elements.selectedList.innerHTML = '<div class="no-selected">Нет выбранных свойств</div>';
            return;
        }

        // Получаем информацию о выбранных свойствах
        const selectedPropertiesInfo = this.properties.filter(property =>
            this.selectedProperties.has(parseInt(property.id))
        );

        this.elements.selectedList.innerHTML = selectedPropertiesInfo.map(property => `
        <div class="selected-item" data-id="${parseInt(property.id)}">
             <span>${property.name || 'Без названия'}</span>
            ${property.category ? `<span class="category">${property.category}</span>` : ''}
           <button class="remove-btn" data-id="${parseInt(property.id)}">x</button>
        </div>
    `).join('');

        // ОБРАБОТЧИКИ ДЛЯ КНОПОК УДАЛЕНИЯ
        this.elements.selectedList.querySelectorAll('.remove-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const propertyId = button.dataset.id;
                this.toggleProperty(propertyId);
            });
        });
    }

    updateSelectedCount() {
        if (this.elements.selectedCount) {
            this.elements.selectedCount.textContent = `Выбрано: ${this.selectedProperties.size}`;
        }
    }

    clearSelectedCount() {
        if (this.elements.selectedCount) {
            this.elements.selectedCount.textContent = `Выбрано: 0`;
        }
    }

    handleCategoryChange(category) {
        this.currentCategory = category;

        // Обновление активного класса
        this.elements.categoriesList.querySelectorAll('.category-item').forEach(item => {
            item.classList.toggle('active', item.dataset.category === category);
        });

        this.filterProperties();
    }

    handleSearch(term) {
        this.searchTerm = term.toLowerCase();
        this.filterProperties();
    }

    filterProperties() {
        this.filteredProperties = this.properties.filter(property => {
            const matchesCategory = this.currentCategory === 'all' || property.category === this.currentCategory;
            const matchesSearch = !this.searchTerm ||
                (property.name && property.name.toLowerCase().includes(this.searchTerm)) ||
                (property.category && property.category.toLowerCase().includes(this.searchTerm));
            return matchesCategory && matchesSearch;
        });
        this.renderProperties();
    }

    handlePropertySelection(checkbox) {
        let propertyId = checkbox.parentElement.dataset.id;
        propertyId = parseInt(propertyId);
      
        if (checkbox.checked) {
            this.selectedProperties.add(propertyId);
        } else {
            this.selectedProperties.delete(propertyId);
        }

        this.renderSelectedProperties(); 
        this.updateSelectedCount();
    }

    toggleProperty(propertyId) {
        propertyId = parseInt(propertyId);
        if (this.selectedProperties.has(propertyId)) {
            this.selectedProperties.delete(propertyId);
        } else {
            this.selectedProperties.add(propertyId);
        }
        this.elements.propertiesGrid.querySelector(`input[id="property-${propertyId}"]`).checked = "";
        this.renderSelectedProperties();
        this.updateSelectedCount();
    }

    filterByCategory(category) {
        this.currentCategory = category;
        this.filterProperties();
    }

    selectAll() {
        this.filteredProperties.forEach(property => {
            this.selectedProperties.add(property.id);
        });
        this.renderProperties();
        this.renderSelectedProperties();
        this.updateSelectedCount();
    }

    clearAll() {
        this.selectedProperties.clear();
        this.renderProperties();
        this.renderSelectedProperties();
        this.clearSelectedCount();
    }

    resetSelection() {
        this.clearAll();
        this.elements.searchInput.value = '';
        this.searchTerm = '';
        this.currentCategory = 'all';
        this.filterProperties();

        // Сброс активной категории
        document.querySelectorAll('.category-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelector('[data-category="all"]').classList.add('active');
    }

    async saveSelection() {
        try {
            const selectedProperties = Array.from(this.selectedProperties);

            const formData = new URLSearchParams();
            formData.append('action', 'save_selection');
            formData.append('selectedProperties', JSON.stringify(selectedProperties));
            formData.append('nonce', textFileAjax.nonce); // Используем тот же nonce что и для get_properties
           
            console.log('Sending save request with nonce:', textFileAjax.nonce);

            const response = await fetch(textFileAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const responseText = await response.text();
            console.log('Raw response:', responseText);

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError, 'Response text:', responseText);
                throw new Error('Invalid JSON response from server');
            }

            console.log('Parsed response data:', data);

            if (data.success) {
                this.showMessage(`Выбор сохранен успешно! Выбрано свойств: ${data.data.selectedCount}`, 'success');
            } else {
                this.showMessage('Ошибка сохранения выбора: ' + (data.data?.message || ''), 'error');
            }
        } catch (error) {
            console.error('Error saving selection:', error);
            this.showMessage('Ошибка сохранения выбора: ' + error.message, 'error');
        }
    }
   
    // Временная функция для тестирования (только для разработки)
    async testSave() {
        const testData = ['1', '2', '3']; // Простые строки для теста
        console.log('Test data:', testData);
        console.log('Test JSON:', JSON.stringify(testData));

        const formData = new URLSearchParams();
        formData.append('action', 'save_selection');
        formData.append('selectedProperties', JSON.stringify(testData));
        formData.append('nonce', textFileAjax.nonce);

        console.log('FormData entries:');
        for (let [key, value] of formData.entries()) {
            console.log(key, ':', value);
        }
    }

    showMessage(message, type = 'info') {
        if (this.elements.message) {
            this.elements.message.textContent = message;
            this.elements.message.className = `message ${type}`;
            this.elements.message.style.display = 'block';

            setTimeout(() => {
                this.elements.message.style.display = 'none';
            }, 1000);
        }
        console.log(`${type.toUpperCase()}: ${message}`);
    }
}

// Инициализация с обработкой ошибок
document.addEventListener('DOMContentLoaded', () => {
    try {
        propertiesSelector = new PropertiesSelector();
    } catch (error) {
        console.error('Failed to initialize PropertiesSelector: ', error);
    }
});
