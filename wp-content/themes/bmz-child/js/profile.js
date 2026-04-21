// profile.js
async function loadProfile() {
    // TODO: GET /api/user/profile
    document.getElementById('profile-info').innerHTML = '<p>Загрузка...</p>';
}

async function loadServices() {
    const result = await API.services.getAll();
    if (result && result.success) {
        const services = result.data.items || result.data;
        document.getElementById('services-list').innerHTML = services.map(s => `
            <div class="card">
                <div class="card-content">
                    <div class="card-title">${s.name}</div>
                    <div class="card-meta">
                        ${s.description || ''}<br>
                        Статус: ${s.status || 'активен'}
                    </div>
                </div>
            </div>
        `).join('');
    }
}

async function loadOrders() {
    const result = await API.merch.getOrders();
    if (result && result.success) {
        const orders = result.data.items || result.data;
        document.getElementById('orders-list').innerHTML = orders.map(o => `
            <div class="card">
                <div class="card-content">
                    <div class="card-title">Заказ #${o.order_number}</div>
                    <div class="card-meta">
                        Сумма: ${o.total} ₽<br>
                        Статус: ${o.status}<br>
                        Дата: ${new Date(o.created_at).toLocaleDateString()}
                    </div>
                </div>
            </div>
        `).join('');
    }
}

async function loadBlocks() {
    // Мои блокировки
    const myBlocks = await API.blocks.my();
    if (myBlocks && myBlocks.success) {
        const blocks = myBlocks.data.items || myBlocks.data;
        document.getElementById('my-blocks-list').innerHTML = blocks.map(b => `
            <div class="card">
                <div class="card-content">
                    <div class="card-title">${b.blocked_name || 'Пользователь'}</div>
                    <div class="card-meta">
                        Тип: ${b.block_type}<br>
                        Дата: ${new Date(b.created_at).toLocaleDateString()}
                    </div>
                    <button onclick="unblock(${b.id})">Разблокировать</button>
                </div>
            </div>
        `).join('');
    }

    // Блокировки на меня
    const onMe = await API.blocks.onMe();
    if (onMe && onMe.success) {
        const blocks = onMe.data.items || onMe.data;
        document.getElementById('blocks-on-me-list').innerHTML = blocks.map(b => `
            <div class="card">
                <div class="card-content">
                    <div class="card-title">${b.blocker_name || 'Пользователь'}</div>
                    <div class="card-meta">
                        Тип: ${b.block_type}<br>
                        Дата: ${new Date(b.created_at).toLocaleDateString()}
                    </div>
                </div>
            </div>
        `).join('');
    }
}

window.unblock = async function (blockId) {
    const result = await API.blocks.delete(blockId);
    if (result && result.success) {
        loadBlocks();
    }
};

// Табы
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;

        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

        btn.classList.add('active');
        document.getElementById(`${tab}-tab`).classList.add('active');

        if (tab === 'profile') loadProfile();
        if (tab === 'services') loadServices();
        if (tab === 'orders') loadOrders();
        if (tab === 'blocks') loadBlocks();
    });
});

// Загрузка активной вкладки
loadProfile();
loadServices();
loadOrders();
loadBlocks();