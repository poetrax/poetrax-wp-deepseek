// admin.js
async function loadAllBlocks() {
    // TODO: GET /api/admin/blocks
    const result = await API.blocks.my(); // временно
    if (result && result.success) {
        const blocks = result.data.items || result.data;
        document.getElementById('all-blocks-list').innerHTML = blocks.map(b => `
            <div class="card">
                <div class="card-content">
                    <div class="card-title">Блокировка #${b.id}</div>
                    <div class="card-meta">
                        Блокирующий: ${b.blocker_name || b.blocker_user_id}<br>
                        Заблокированный: ${b.blocked_name || b.blocked_user_id}<br>
                        Тип: ${b.block_type}<br>
                        Причина: ${b.reason || '-'}
                    </div>
                    <button onclick="adminUnblock(${b.id})">Снять блокировку</button>
                </div>
            </div>
        `).join('');
    }
}

async function loadReports() {
    // TODO: GET /api/admin/reports
    document.getElementById('reports-list').innerHTML = '<p>Жалоб нет</p>';
}

async function loadUsers() {
    // TODO: GET /api/admin/users
    document.getElementById('users-list').innerHTML = '<p>Загрузка...</p>';
}

async function loadServicesAdmin() {
    const result = await API.services.getAll();
    if (result && result.success) {
        const services = result.data.items || result.data;
        document.getElementById('services-list').innerHTML = services.map(s => `
            <div class="card">
                <div class="card-content">
                    <div class="card-title">${s.name}</div>
                    <div class="card-meta">
                        Цена: ${s.price_fiat ? s.price_fiat + ' ₽' : (s.price_points ? s.price_points + ' баллов' : 'Бесплатно')}<br>
                        Тип: ${s.is_subscription ? 'Подписка' : 'Разовый'}
                    </div>
                </div>
            </div>
        `).join('');
    }
}

window.adminUnblock = async function (blockId) {
    const result = await API.blocks.delete(blockId);
    if (result && result.success) {
        loadAllBlocks();
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

        if (tab === 'blocks') loadAllBlocks();
        if (tab === 'reports') loadReports();
        if (tab === 'users') loadUsers();
        if (tab === 'services') loadServicesAdmin();
    });
});

loadAllBlocks();
loadReports();
loadUsers();
loadServicesAdmin();