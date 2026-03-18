'use strict';
class CascadeSelectManager {
    constructor() {
        console.log('✅ CascadeSelectManager: Constructor called');
        this.cache = new Map();
        this.init();
    }

    init() {
        console.log('✅ CascadeSelectManager: Init started');

        const containers = document.querySelectorAll('.cascade-container');
        console.log('✅ CascadeSelectManager: Found containers:', containers.length);

        // ⭐⭐⭐ ДОБАВЛЯЕМ ОБРАБОТЧИКИ СОБЫТИЙ ⭐⭐⭐
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('cascade-parent')) {
                console.log('🎯 Parent select changed:', e.target.value);
                this.updateChildSelect(e.target);
            }
        });

        if (containers.length > 0) {
            console.log('✅ CascadeSelectManager: Starting data loading');
            this.loadAllCascades();
        }
    }

    async loadAllCascades() {
        const containers = document.querySelectorAll('.cascade-container');
        console.log('✅ CascadeSelectManager: Loading data for', containers.length, 'containers');

        for (const container of containers) {
            await this.loadCascadeData(container);
        }
    }

    async loadCascadeData(container) {
        const cascadeType = container.dataset.cascadeType;
        console.log('🔄 Loading data for:', cascadeType);

        // Показываем индикатор загрузки
        const childSelect = container.querySelector('.cascade-child');
        childSelect.innerHTML = '<option value="">-- Загрузка... --</option>';

        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_cascade_data',
                    cascade_type: cascadeType
                })
            });

            // ⭐⭐⭐ ИСПРАВЛЕНИЕ BOM ⭐⭐⭐
            let responseText = await response.text();

            // Очищаем от BOM и невидимых символов
            responseText = this.cleanResponseText(responseText);
            console.log('✅ CascadeSelectManager: Cleaned response text: ', responseText.substring(0, 100) + '...');

            const data = JSON.parse(responseText);
            console.log('✅ CascadeSelectManager: JSON parsed successfully');

            if (data.success) {
                console.log('🎉 Data loaded');
                const parentSelect = container.querySelector('.cascade-parent');
                this.populateParentSelect(parentSelect, data.data.data, cascadeType);
            }

        } catch (error) {
            console.error('💥 AJAX error: ', error);
            childSelect.innerHTML = '<option value="">-- Ошибка загрузки --</option>';
        }
    }

    // Функция очистки ответа от BOM и мусора
    cleanResponseText(text) {
        if (typeof text !== 'string') return text;

        // Удаляем BOM и другие невидимые символы
        return text
            .replace(/^\uFEFF/, '')  // UTF-8 BOM
            .replace(/^\uFFFE/, '')  // UTF-16 BOM  
            .replace(/^[\x00-\x1F\x7F]+/, '') // Управляющие символы
            .trim();
    }

    populateParentSelect(selectElement, parents, cascadeType) {
        console.log('🔄 populateParentSelect called');

        parents.forEach((parent, index) => {
            const option = document.createElement('option');
            option.value = parent.id;
            option.textContent = parent.name;
            option.dataset.children = JSON.stringify(parent.children);
            selectElement.appendChild(option);
        });
        console.log('✅ populateParentSelect CascadeType: ', cascadeType);

        const firstName = cascadeType === 'poet_poem' ? 'Автор (поэт)' : 'Стили';

        var opt = new Option(firstName, firstName);
        selectElement.insertBefore(opt, selectElement.firstChild);
      
        console.log('✅ Parent select populated with', parents.length, 'options');

        // ⭐⭐⭐ АВТОВЫБОР ПЕРВОГО РОДИТЕЛЯ ⭐⭐⭐
        if (parents.length > 0) {
            //Выбираем первого родителя
            //HACK recomment
            //selectElement.value = parents[0].id;
            //console.log('✅ Auto-selected first parent:', parents[0].name);

            //HACK test
            selectElement.value = `${firstName}`;
            console.log('✅ Auto-selected first select: ', `${firstName}`);
            //Немедленно обновляем дочерний селект
            this.updateChildSelect(selectElement, cascadeType);
        }
    }

    updateChildSelect(parentSelect, cascadeType) {
        console.log('🔄 updateChildSelect called for value:', parentSelect.value);
        const container = parentSelect.closest('.cascade-container');
        const childSelect = container.querySelector('.cascade-child');
        const selectedOption = parentSelect.options[parentSelect.selectedIndex];

        // Сбрасываем дочерний селект
        childSelect.innerHTML = '';
        childSelect.disabled = true;
        console.log('✅ cascadeType: ', cascadeType);

        const firstName = cascadeType === 'poet_poem' ? 'Стих' : 'Стиль';
        console.log('✅ firstName: ', firstName);
        childSelect.innerHTML = `<option value="">-- ${firstName} --</option>`;
        console.log('✅ updateChildSelect CascadeType:', cascadeType);
        const noElements = cascadeType === 'poet_poem' ? 'Выберите поэта' : 'Выберите стиль';
        console.log('✅ noElements: ', noElements);

        if (!selectedOption?.value) {
            console.log('❌ No category selected');
            return;
        }

        const children = JSON.parse(selectedOption.dataset.children || '[]');
        console.log('📝 Children to add:', children.length);

        if (children.length > 0) {
            children.forEach(child => {
                const option = document.createElement('option');
                option.value = child.id;
                option.textContent = child.name;

                if (child.suno_prompt) {
                    option.dataset.sunoPrompt = child.suno_prompt;
                }

                childSelect.appendChild(option);
            });

            childSelect.disabled = false;
            console.log('✅ Child select populated with', children.length, 'items');

            // ⭐⭐⭐ АВТОВЫБОР ПЕРВОГО РЕБЕНКА ⭐⭐⭐
            if (children.length > 0) {
                childSelect.value = children[0].id;
                console.log('✅ Auto-selected first child:', children[0].name);

                // Триггерим событие для suno_prompt
                childSelect.dispatchEvent(new Event('change'));
            }

        } else {
            childSelect.innerHTML = `<option value="">-- ${noElements} --</option>`;
        }
    }
}

// Используем уникальное имя для глобальной переменной
document.addEventListener('DOMContentLoaded', function () {
    console.log('✅ DOM Ready - initializing CascadeSelectManager');
    window.cascadeSelectManager = new CascadeSelectManager();
});

