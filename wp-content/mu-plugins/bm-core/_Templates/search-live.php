<!-- templates/search-live.php -->
<div class="bm-search-container">
    
    <!-- 1. ТЕКСТОВОЕ ПОЛЕ (ТЕКСТБОКС) -->
    <div class="bm-search-box">
        <input 
            type="text" 
            id="bm-search-input" 
            class="bm-search-input"
            placeholder="Поиск треков, стихов, поэтов..."
            autocomplete="off"
            data-results="bm-search-results"
        >
        <button class="bm-search-clear" id="bm-search-clear">✕</button>
    </div>
    
    <!-- 2. КОНТЕЙНЕР ДЛЯ РЕЗУЛЬТАТОВ -->
    <div id="bm-search-results" class="bm-search-results"></div>
    
    <!-- 3. ДЕТАЛЬНЫЕ РЕЗУЛЬТАТЫ (для полной страницы поиска) -->
    <div class="bm-search-detailed-results"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // =========================================
    // 1. ЭЛЕМЕНТЫ ИНТЕРФЕЙСА
    // =========================================
    const searchInput = document.getElementById('bm-search-input');
    const searchResults = document.getElementById('bm-search-results');
    const searchClear = document.getElementById('bm-search-clear');
    
    // Состояние
    let searchTimeout = null;
    let currentQuery = '';
    
    // =========================================
    // 2. СОБЫТИЕ - INPUT (при каждом вводе)
    // =========================================
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        currentQuery = query;
        
        // Показываем/скрываем кнопку очистки
        if (query.length > 0) {
            searchClear.style.display = 'flex';
        } else {
            searchClear.style.display = 'none';
            searchResults.classList.remove('active');
            return;
        }
        
        // Минимальная длина для поиска - 3 символа
        if (query.length < 3) {
            searchResults.innerHTML = '<div class="bm-search-message">Введите минимум 3 символа</div>';
            searchResults.classList.add('active');
            return;
        }
        
        // Дебаунс - ждем 300мс после последнего ввода
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    // =========================================
    // 3. СОБЫТИЕ - КЛИК ПО КНОПКЕ ОЧИСТКИ
    // =========================================
    searchClear.addEventListener('click', function() {
        searchInput.value = '';
        searchInput.focus();
        searchResults.classList.remove('active');
        searchClear.style.display = 'none';
        currentQuery = '';
    });
    
    // =========================================
    // 4. ЗАПРОС - AJAX
    // =========================================
    function performSearch(query) {
        // Показываем индикатор загрузки
        searchResults.innerHTML = '<div class="bm-search-loading">Поиск...</div>';
        searchResults.classList.add('active');
        
        // Формируем FormData
        const formData = new FormData();
        formData.append('action', 'bm_live_search');
        formData.append('query', query);
        formData.append('autocomplete', '1'); // Быстрый режим для подсказок
        
        // AJAX запрос
        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // =========================================
                // 5. РЕНДЕРИНГ
                // =========================================
                renderSearchResults(data.data.html, data.data.results);
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            searchResults.innerHTML = '<div class="bm-search-error">Ошибка поиска</div>';
        });
    }
    
    // =========================================
    // 5. ФУНКЦИЯ РЕНДЕРИНГА
    // =========================================
    function renderSearchResults(html, results) {
        if (!html || html.trim() === '') {
            searchResults.innerHTML = `
                <div class="bm-search-empty">
                    Ничего не найдено по запросу "${currentQuery}"
                </div>
            `;
            return;
        }
        
        searchResults.innerHTML = html;
        
        // Добавляем ссылку на полный поиск
        if (results) {
            const totalCount = (results.tracks?.length || 0) + 
                              (results.poems?.length || 0) + 
                              (results.poets?.length || 0);
            
            if (totalCount > 0) {
                searchResults.insertAdjacentHTML('beforeend', `
                    <div class="bm-search-footer">
                        <a href="/search/?q=${encodeURIComponent(currentQuery)}">
                            Все результаты поиска (${totalCount})
                        </a>
                    </div>
                `);
            }
        }
    }
    
    // =========================================
    // ЗАКРЫТИЕ ПО КЛИКУ ВНЕ
    // =========================================
    document.addEventListener('click', function(e) {
        if (!searchResults.contains(e.target) && !searchInput.contains(e.target)) {
            searchResults.classList.remove('active');
        }
    });
});
</script>

<style>
.bm-search-container {
    position: relative;
    max-width: 600px;
    margin: 0 auto;
}

.bm-search-box {
    position: relative;
    display: flex;
    align-items: center;
}

.bm-search-input {
    width: 100%;
    padding: 15px 20px;
    padding-right: 50px;
    font-size: 16px;
    border: 2px solid #e0e0e0;
    border-radius: 30px;
    transition: all 0.3s;
}

.bm-search-input:focus {
    border-color: #4CAF50;
    outline: none;
    box-shadow: 0 2px 10px rgba(76, 175, 80, 0.1);
}

.bm-search-clear {
    position: absolute;
    right: 15px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #e0e0e0;
    color: #666;
    display: none;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: none;
    font-size: 14px;
}

.bm-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    margin-top: 10px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    z-index: 1000;
    max-height: 500px;
    overflow-y: auto;
    display: none;
}

.bm-search-results.active {
    display: block;
}

.bm-search-section {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.bm-search-section h4 {
    margin: 0 0 10px;
    color: #333;
    font-size: 14px;
    text-transform: uppercase;
    color: #4CAF50;
}

.bm-search-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

.bm-search-item:hover {
    background: #f5f5f5;
}

.bm-search-item a {
    text-decoration: none;
    color: #333;
    display: block;
    width: 100%;
}

.bm-search-item .type {
    font-size: 12px;
    color: #999;
    margin-left: 10px;
}

.bm-search-footer {
    padding: 15px;
    text-align: center;
    background: #f9f9f9;
    border-radius: 0 0 12px 12px;
}

.bm-search-footer a {
    color: #4CAF50;
    text-decoration: none;
    font-weight: 500;
}

.bm-search-loading {
    padding: 30px;
    text-align: center;
    color: #666;
}

.bm-search-empty {
    padding: 30px;
    text-align: center;
    color: #999;
}
</style>