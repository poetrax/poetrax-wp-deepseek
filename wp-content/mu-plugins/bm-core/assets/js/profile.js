/**
 * Profile page JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.profile-tabs button');
    const content = document.getElementById('profile-content');
    
    // Загружаем данные при старте (мои треки)
    loadTab('tracks');
    
    // Переключение вкладок
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Убираем active со всех
            tabs.forEach(t => t.classList.remove('active'));
            // Добавляем активной
            this.classList.add('active');
            // Загружаем контент
            loadTab(this.dataset.tab);
        });
    });
    
    async function loadTab(tab) {
        content.innerHTML = '<div class="loader">Загрузка...</div>';
        
        try {
            let data;
            
            switch(tab) {
                case 'tracks':
                    data = await ApiClient.get('/api/user/tracks');
                    renderTracks(data.items, 'Ваши треки');
                    break;
                    
                case 'liked':
                    data = await ApiClient.get('/api/user/liked');
                    renderTracks(data.items, 'Понравившиеся треки');
                    break;
                    
                case 'bookmarks':
                    data = await ApiClient.get('/api/user/bookmarks');
                    renderTracks(data.items, 'Закладки');
                    break;
                    
                case 'recommendations':
                    // Персонализированные рекомендации
                    data = await ApiClient.get('/api/recommendations/user', { limit: 20 });
                    renderTracks(data, 'Рекомендовано для вас');
                    break;
                    
                case 'settings':
                    renderSettings();
                    break;
            }
        } catch (error) {
            content.innerHTML = '<div class="error">Ошибка загрузки</div>';
            console.error(error);
        }
    }
    
    function renderTracks(tracks, title) {
        if (!tracks || tracks.length === 0) {
            content.innerHTML = `<h2>${title}</h2><p>Нет треков</p>`;
            return;
        }
        
        let html = `<h2>${title}</h2>`;
        html += '<div class="tracks-grid">';
        
        tracks.forEach(track => {
            html += `
                <div class="track-card" data-id="${track.id}">
                    <h3>${escapeHtml(track.track_name)}</h3>
                    <p>${escapeHtml(track.poet_name || '')}</p>
                    <audio controls preload="none">
                        <source src="/api/stream/${track.id}?token=${track.token}" type="audio/mpeg">
                    </audio>
                </div>
            `;
        });
        
        html += '</div>';
        content.innerHTML = html;
    }
    
    function renderSettings() {
        content.innerHTML = `
            <h2>Настройки</h2>
            <form id="settings-form">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="${user.email}">
                </div>
                <div class="form-group">
                    <label>Имя</label>
                    <input type="text" name="name" value="${user.name}">
                </div>
                <div class="form-group">
                    <label>Фамилия</label>
                    <input type="text" name="surname" value="${user.surname}">
                </div>
                <button type="submit">Сохранить</button>
            </form>
        `;
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
