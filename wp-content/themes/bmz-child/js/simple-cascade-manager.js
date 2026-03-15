class SimpleCascadeManager {
    constructor() {
        this.cache = new Map();
        this.init();
    }

    init() {
        this.loadAllCascades();

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
        const containers = document.querySelectorAll('.cascade-container');

        for (const container of containers) {
            await this.loadCascadeData(container);
        }
    }

    async loadCascadeData(container) {
        const cascadeType = container.dataset.cascadeType;
        const parentSelect = container.querySelector('.cascade-parent');

        if (!cascadeType) return;

        try {
            if (this.cache.has(cascadeType)) {
                this.populateParentSelect(parentSelect, this.cache.get(cascadeType));
                return;
            }

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

            const data = await response.json();

            if (data.success) {
                this.cache.set(cascadeType, data.data.data);
                this.populateParentSelect(parentSelect, data.data.data);
            }
        } catch (error) {
            console.error('Failed to load cascade data:', error);
            parentSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
        }
    }

    populateParentSelect(selectElement, parents) {
        selectElement.innerHTML = '<option value="">-- Выберите категорию --</option>';

        parents.forEach(parent => {
            const option = document.createElement('option');
            option.value = parent.id;
            option.textContent = parent.name;
            option.dataset.children = JSON.stringify(parent.children);
            selectElement.appendChild(option);
        });
    }

    updateChildSelect(parentSelect) {
        const container = parentSelect.closest('.cascade-container');
        const childSelect = container.querySelector('.cascade-child');
        const selectedOption = parentSelect.options[parentSelect.selectedIndex];

        // Сбрасываем дочерний селект и suno_prompt
        childSelect.innerHTML = '<option value="">-- Выберите стиль --</option>';
        childSelect.disabled = true;
        this.hideSunoPrompt(container);

        if (!selectedOption.value) return;

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
        }
    }

    updateSunoPrompt(childSelect) {
        const container = childSelect.closest('.cascade-container');
        const showSunoPrompt = container.dataset.showSunoPrompt === '1';

        if (!showSunoPrompt) return;

        const selectedOption = childSelect.options[childSelect.selectedIndex];
        const sunoPrompt = selectedOption?.dataset.sunoPrompt;

        this.showSunoPrompt(container, sunoPrompt);
    }

    showSunoPrompt(container, promptText) {
        const promptContainer = container.querySelector('.suno-prompt-container');
        const promptTextEl = container.querySelector('.suno-prompt-text');

        if (promptContainer && promptTextEl) {
            if (promptText) {
                promptTextEl.textContent = promptText;
                promptContainer.style.display = 'block';
            } else {
                promptContainer.style.display = 'none';
            }
        }
    }

    hideSunoPrompt(container) {
        const promptContainer = container.querySelector('.suno-prompt-container');
        if (promptContainer) {
            promptContainer.style.display = 'none';
        }
    }

    // Получить выбранные значения
    getSelectedValues(container) {
        const parentSelect = container.querySelector('.cascade-parent');
        const childSelect = container.querySelector('.cascade-child');
        const selectedChild = childSelect.options[childSelect.selectedIndex];

        return {
            parent_id: parentSelect.value,
            parent_name: parentSelect.options[parentSelect.selectedIndex]?.text,
            child_id: childSelect.value,
            child_name: selectedChild?.text,
            suno_prompt: selectedChild?.dataset.sunoPrompt || ''
        };
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.cascadeManager = new SimpleCascadeManager();
});