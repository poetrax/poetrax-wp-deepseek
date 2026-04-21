// api.js
const API = {
    base: 'http://poetrax-local.ru:8086/api',

    // Текущий пользователь (временно, потом через JWT)
    currentUserId: 1,

    async request(endpoint, options = {}) {
        try {
            const response = await fetch(`${this.base}${endpoint}`, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-User-Id': this.currentUserId
                },
                ...options
            });

            const data = await response.json();

            // Обработка платного доступа
            if (data.requires_payment) {
                this.showPaymentModal(data.service, data.message);
                return null;
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            return null;
        }
    },

    showPaymentModal(service, message) {
        const modal = document.getElementById('payment-modal');
        const messageEl = document.getElementById('payment-message');
        const buyBtn = document.getElementById('payment-buy');

        if (!modal) return;

        messageEl.textContent = message || `Доступ к ${service} требует подписки`;
        buyBtn.onclick = () => this.buyService(service);
        modal.style.display = 'flex';
    },

    async buyService(service) {
        const result = await this.request(`/services/${service}/buy`, { method: 'POST' });
        if (result && result.success) {
            alert('Доступ активирован!');
            location.reload();
        }
    },

    // Треки
    tracks: {
        getAll: (params = {}) => API.request(`/tracks?${new URLSearchParams(params)}`),
        getById: (id) => API.request(`/tracks/${id}`),
        search: (q) => API.request(`/tracks/search?q=${encodeURIComponent(q)}`),
        popular: () => API.request('/tracks/popular')
    },

    // Поэты
    poets: {
        getAll: () => API.request('/poets'),
        getById: (id) => API.request(`/poets/${id}`),
        search: (q) => API.request(`/poets/search?q=${encodeURIComponent(q)}`)
    },

    // Стихи
    poems: {
        getAll: () => API.request('/poems'),
        getById: (id) => API.request(`/poems/${id}`),
        getText: (id) => API.request(`/poems/${id}/text`),
        byPoet: (poetId) => API.request(`/poems/by-poet/${poetId}`)
    },

    // Магазин
    merch: {
        getProducts: (params = {}) => API.request(`/products?${new URLSearchParams(params)}`),
        getCart: () => API.request('/cart'),
        addToCart: (productId, quantity, variantId = null) => API.request('/cart/items', {
            method: 'POST',
            body: JSON.stringify({ product_id: productId, quantity, variant_id: variantId })
        }),
        updateCartItem: (itemId, quantity) => API.request(`/cart/items/${itemId}`, {
            method: 'PUT',
            body: JSON.stringify({ quantity })
        }),
        removeCartItem: (itemId) => API.request(`/cart/items/${itemId}`, { method: 'DELETE' }),
        clearCart: () => API.request('/cart', { method: 'DELETE' }),
        checkout: (address, paymentMethod = 'yookassa') => API.request('/orders', {
            method: 'POST',
            body: JSON.stringify({ shipping_address: address, payment_method: paymentMethod })
        }),
        getOrders: () => API.request('/orders'),
        getOrder: (id) => API.request(`/orders/${id}`)
    },

    // Сообщения
    messages: {
        inbox: () => API.request('/messages/inbox'),
        sent: () => API.request('/messages/sent'),
        getById: (id) => API.request(`/messages/${id}`),
        send: (toUserId, subject, message) => API.request('/messages', {
            method: 'POST',
            body: JSON.stringify({ to_user_id: toUserId, subject, message })
        }),
        delete: (id) => API.request(`/messages/${id}`, { method: 'DELETE' }),
        unreadCount: () => API.request('/messages/unread/count')
    },

    // Блокировки
    blocks: {
        my: () => API.request('/blocks/my'),
        onMe: () => API.request('/blocks/on-me'),
        create: (blockedUserId, type, reason = null) => API.request('/blocks', {
            method: 'POST',
            body: JSON.stringify({ blocked_user_id: blockedUserId, type, reason })
        }),
        delete: (id) => API.request(`/blocks/${id}`, { method: 'DELETE' }),
        check: (userId, type = 'profile') => API.request(`/blocks/check?user_id=${userId}&type=${type}`)
    },

    // Сервисы
    services: {
        getAll: () => API.request('/services'),
        check: (service) => API.request(`/services/check?service=${service}`),
        buy: (slug) => API.request(`/services/${slug}/buy`, { method: 'POST' })
    },

    // Админка
    admin: {
        getUsers: () => API.request('/admin/users'),
        getReports: () => API.request('/admin/reports'),
        resolveReport: (id) => API.request(`/admin/reports/${id}/resolve`, { method: 'POST' }),
        makeAdmin: (userId) => API.request('/admin/make', { method: 'POST', body: JSON.stringify({ user_id: userId }) })
    }
};