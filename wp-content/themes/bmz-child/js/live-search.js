class BMLiveSearch {
    constructor(input, resultsContainer, options = {}) {
        this.input = input;
        this.container = resultsContainer;
        this.options = {
            minChars: 3,
            delay: 300,
            ...options
        };

        this.timeout = null;
        this.init();
    }

    init() {
        this.input.addEventListener('input', () => {
            clearTimeout(this.timeout);

            const query = this.input.value.trim();

            if (query.length < this.options.minChars) {
                this.container.classList.remove('active');
                return;
            }

            this.timeout = setTimeout(() => this.search(query), this.options.delay);
        });

        // Закрытие по клику вне
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target) && !this.input.contains(e.target)) {
                this.container.classList.remove('active');
            }
        });
    }

    search(query) {
        const formData = new FormData();
        formData.append('action', 'bm_live_search');
        formData.append('query', query);
        formData.append('autocomplete', '1');

        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.container.innerHTML = data.data.html;
                    this.container.classList.add('active');
                }
            });
    }
}

// Инициализация
document.querySelectorAll('.bm-search-input').forEach(input => {
    const container = document.getElementById(input.dataset.results);
    if (container) {
        new BMLiveSearch(input, container);
    }
});