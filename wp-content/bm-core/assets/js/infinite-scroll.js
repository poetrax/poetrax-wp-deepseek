class BMInfiniteScroll {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            loadingClass: 'bm-loading',
            endMessage: 'Больше нет треков',
            ...options
        };

        this.lastId = null;
        this.loading = false;
        this.hasMore = true;
        this.filters = {};

        this.initObserver();
    }

    initObserver() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.loading && this.hasMore) {
                    this.loadMore();
                }
            });
        });

        observer.observe(this.container.querySelector('.bm-scroll-trigger'));
    }

    loadMore() {
        this.loading = true;
        this.container.classList.add(this.options.loadingClass);

        const formData = new FormData();
        formData.append('action', 'bm_infinite_scroll');
        formData.append('last_id', this.lastId);
        formData.append('filters', JSON.stringify(this.filters));

        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.container.insertAdjacentHTML('beforeend', data.data.html);
                    this.lastId = data.data.last_id;
                    this.hasMore = data.data.has_more;
                    this.loading = false;
                }
            });
    }
}

// Инициализация
new BMInfiniteScroll(document.querySelector('.bm-track-grid'));