// main.js
let currentTracksPage = 1;
const tracksPerPage = 12;

// Загрузка треков
async function loadTracks() {
    const search = document.getElementById('searchTracks')?.value || '';
    const lang = document.getElementById('filterLang')?.value || '';
    const voiceGender = document.getElementById('filterVoiceGender')?.value || '';

    const params = {
        page: currentTracksPage,
        limit: tracksPerPage,
        ...(search && { search }),
        ...(lang && { lang }),
        ...(voiceGender && { voice_gender: voiceGender })
    };

    const result = await API.tracks.getAll(params);

    if (result && result.success && result.data) {
        renderTracks(result.data.items || result.data);
        renderPagination(result.data.pagination || {
            page: currentTracksPage,
            pages: Math.ceil((result.data.total || 0) / tracksPerPage)
        });
    }
}

// Рендер треков
function renderTracks(tracks) {
    const container = document.getElementById('tracks-list');
    if (!container) return;

    if (!tracks || tracks.length === 0) {
        container.innerHTML = '<p>Треки не найдены</p>';
        return;
    }

    container.innerHTML = tracks.map(track => `
        <div class="card" data-track-id="${track.id}">
            <div class="card-content">
                <div class="card-title">${escapeHtml(track.track_name)}</div>
                <div class="card-meta">
                    ${track.author_name ? `Автор: ${escapeHtml(track.author_name)}` : ''}
                    ${track.voice_gender ? `<br>Голос: ${track.voice_gender === 'male' ? 'Мужской' : 'Женский'}` : ''}
                </div>
                <audio class="audio-player" controls preload="none">
                    <source src="${track.track_path}" type="audio/mpeg">
                </audio>
                <div class="card-actions">
                    <button class="like-btn" data-id="${track.id}">❤️ ${track.like_count || 0}</button>
                    <button class="bookmark-btn" data-id="${track.id}">⭐ ${track.bookmark_count || 0}</button>
                    ${track.is_payable ? '<span class="payable-badge">💰 Платный</span>' : ''}
                </div>
            </div>
        </div>
    `).join('');

    // Инициализация плееров
    document.querySelectorAll('.audio-player').forEach(player => {
        player.addEventListener('play', () => {
            document.querySelectorAll('.audio-player').forEach(p => {
                if (p !== player) p.pause();
            });
        });
    });
}

// Рендер пагинации
function renderPagination(pagination) {
    const container = document.getElementById('tracks-pagination');
    if (!container) return;

    if (!pagination || pagination.pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '';
    for (let i = 1; i <= pagination.pages; i++) {
        html += `<button class="${i === pagination.page ? 'active' : ''}" data-page="${i}">${i}</button>`;
    }

    container.innerHTML = html;

    container.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('click', () => {
            currentTracksPage = parseInt(btn.dataset.page);
            loadTracks();
        });
    });
}

// Загрузка поэтов
async function loadPoets() {
    const result = await API.poets.getAll();
    if (result && result.success && result.data) {
        renderPoets(result.data.items || result.data);
    }
}

function renderPoets(poets) {
    const container = document.getElementById('poets-list');
    if (!container) return;

    if (!poets || poets.length === 0) {
        container.innerHTML = '<p>Поэты не найдены</p>';
        return;
    }

    container.innerHTML = poets.map(poet => `
        <div class="card">
            <div class="card-content">
                <div class="card-title">${escapeHtml(poet.poet_name || poet.last_name + ' ' + poet.first_name)}</div>
                <div class="card-meta">
                    ${poet.years_life || ''}<br>
                    Треков: ${poet.tracks_count || 0}
                </div>
                <button onclick="viewPoet(${poet.id})">Подробнее</button>
            </div>
        </div>
    `).join('');
}

// Загрузка стихов
async function loadPoems() {
    const result = await API.poems.getAll();
    if (result && result.success && result.data) {
        renderPoems(result.data.items || result.data);
    }
}

function renderPoems(poems) {
    const container = document.getElementById('poems-list');
    if (!container) return;

    if (!poems || poems.length === 0) {
        container.innerHTML = '<p>Стихи не найдены</p>';
        return;
    }

    container.innerHTML = poems.map(poem => `
        <div class="card">
            <div class="card-content">
                <div class="card-title">${escapeHtml(poem.name)}</div>
                <div class="card-meta">${poem.poet_name || ''}</div>
                <button onclick="viewPoem(${poem.id})">Читать стих</button>
            </div>
        </div>
    `).join('');
}

// Просмотр поэта
window.viewPoet = async function (id) {
    const result = await API.poets.getById(id);
    if (result && result.success) {
        alert(JSON.stringify(result.data, null, 2));
    }
};

// Просмотр стиха
window.viewPoem = async function (id) {
    const result = await API.poems.getText(id);
    if (result && result.success) {
        alert(result.data.text);
    }
};

// Экранирование HTML
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function (m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// Инициализация
document.addEventListener('DOMContentLoaded', () => {
    loadTracks();
    loadPoets();
    loadPoems();

    // Поиск с debounce
    const searchInput = document.getElementById('searchTracks');
    if (searchInput) {
        let timeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentTracksPage = 1;
                loadTracks();
            }, 500);
        });
    }

    // Фильтры
    ['filterLang', 'filterVoiceGender'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', () => {
                currentTracksPage = 1;
                loadTracks();
            });
        }
    });
});