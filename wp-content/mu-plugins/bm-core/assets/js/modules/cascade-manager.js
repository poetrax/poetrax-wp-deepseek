/**
 * Cascade Manager Module
 * Управление каскадными селектами (родитель-потомок)
 */

class CascadeManager {
    constructor() {
        this.cache = new Map();
        this.containers = document.querySelectorAll('.cascade-container');
        this.init();
    }

    init() {
        if (!this.containers.length) return;

        this.loadAllCascades();
        this.bindEvents();
    }

    bindEvents() {
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('cascade-parent')) {
                this.updateChildSelect(e.target);
            }
            if (e.target.classList.contains('cascade-child')) {
                this.updateSunoPrompt(e.target);
            }
        });
    }

    async loadAllCascades() {
        const promises = Array.from(this.containers).map(container => 
            this.loadCascadeData(container)
        );
        
        await Promise.all(promises);
    }

    async loadCascadeData(container) {
        const cascadeType = container.dataset.cascadeType;
        const parentSelect = container.querySelector('.cascade-parent');
        
        if (!cascadeType || !parentSelect) return;

        try {
            // Проверяем кэш
            if (this.cache.has(cascadeType)) {
                this.populateParentSelect(parentSelect, this.cache.get(cascadeType));
                return;
            }

            // Загружаем с сервера
            if (!window.ApiClient) {
                throw new Error('ApiClient not found');
            }

            const data = await window.ApiClient.get(`/api/cascade/${cascadeType}`);
            
            this.cache.set(cascadeType, data);
            this.populateParentSelect(parentSelect, data);

        } catch (error) {
            console.error(`Failed to load cascade data for ${cascadeType}:`, error);
            parentSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
        }
    }

    populateParentSelect(selectElement, items) {
        // Сохраняем выбранное значение, если есть
        const currentValue = selectElement.value;
        
        selectElement.innerHTML = '<option value="">-- Выберите категорию --</option>';

        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            
            if (item.children) {
                option.dataset.children = JSON.stringify(item.children);
            }
            
            selectElement.appendChild(option);
        });

        // Восстанавливаем выбранное значение
        if (currentValue && Array.from(selectElement.options).some(opt => opt.value === currentValue)) {
            selectElement.value = currentValue;
            this.updateChildSelect(selectElement);
        }
    }

    updateChildSelect(parentSelect) {
        const container = parentSelect.closest('.cascade-container');
        const childSelect = container.querySelector('.cascade-child');
        const sunoPromptContainer = container.querySelector('.suno-prompt-container');
        const selectedOption = parentSelect.options[parentSelect.selectedIndex];

        // Сбрасываем дочерний селект
        childSelect.innerHTML = '<option value="">-- Выберите значение --</option>';
        childSelect.disabled = true;

        // Скрываем suno prompt
        if (sunoPromptContainer) {
            sunoPromptContainer.style.display = 'none';
        }

        if (!selectedOption || !selectedOption.value) return;

        const children = JSON.parse(selectedOption.dataset.children || '[]');

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

            // Автовыбор первого элемента
            if (children.length > 0) {
                childSelect.value = children[0].id;
                this.updateSunoPrompt(childSelect);
            }
        }
    }

    updateSunoPrompt(childSelect) {
        const container = childSelect.closest('.cascade-container');
        const sunoPromptContainer = container.querySelector('.suno-prompt-container');
        const sunoPromptText = container.querySelector('.suno-prompt-text');
        const showSunoPrompt = container.dataset.showSunoPrompt === 'true';

        if (!showSunoPrompt || !sunoPromptContainer || !sunoPromptText) return;

        const selectedOption = childSelect.options[childSelect.selectedIndex];
        const promptText = selectedOption?.dataset.sunoPrompt || '';

        if (promptText) {
            sunoPromptText.textContent = promptText;
            sunoPromptContainer.style.display = 'block';
        } else {
            sunoPromptContainer.style.display = 'none';
        }
    }

    getSelectedValues(container) {
        const parentSelect = container.querySelector('.cascade-parent');
        const childSelect = container.querySelector('.cascade-child');
        
        const parentOption = parentSelect.options[parentSelect.selectedIndex];
        const childOption = childSelect.options[childSelect.selectedIndex];

        return {
            parent_id: parentSelect.value,
            parent_name: parentOption?.text || '',
            child_id: childSelect.value,
            child_name: childOption?.text || '',
            suno_prompt: childOption?.dataset.sunoPrompt || ''
        };
    }

    reset(container) {
        const parentSelect = container.querySelector('.cascade-parent');
        const childSelect = container.querySelector('.cascade-child');

        if (parentSelect) parentSelect.value = '';
        if (childSelect) {
            childSelect.innerHTML = '<option value="">-- Выберите значение --</option>';
            childSelect.disabled = true;
        }

        const sunoPromptContainer = container.querySelector('.suno-prompt-container');
        if (sunoPromptContainer) {
            sunoPromptContainer.style.display = 'none';
        }
    }

    clearCache(type = null) {
        if (type) {
            this.cache.delete(type);
        } else {
            this.cache.clear();
        }
    }

    async refresh(type) {
        this.clearCache(type);
        
        const containers = type 
            ? Array.from(this.containers).filter(c => c.dataset.cascadeType === type)
            : this.containers;

        await Promise.all(Array.from(containers).map(c => this.loadCascadeData(c)));
    }

    static initAll() {
        if (!window.cascadeManager) {
            window.cascadeManager = new CascadeManager();
        }
        return window.cascadeManager;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    CascadeManager.initAll();
});
