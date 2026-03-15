<?php
$track_repo = new BM\Repositories\TrackRepository();
$master_data = $track_repo->getFilterMasterData();
$moods = $master_data['moods'];
?>

<div class="bm-filter-mood" data-filter="mood">
    <label for="mood-select">Настроение:</label>
    
    <!-- DROPDOWN СПИСОК - ОСНОВНОЙ ЭЛЕМЕНТ -->
    <select id="mood-select" class="bm-filter-select">
        <option value="">Все настроения</option>
        <?php foreach ($moods as $mood): ?>
            <option value="<?= $mood->id ?>">
                <?= esc_html($mood->name) ?>
            </option>
        <?php endforeach; ?>
    </select>
    
    <!-- КОНТЕЙНЕР ДЛЯ РЕЗУЛЬТАТОВ -->
    <div class="bm-filter-results">
        <div class="bm-track-grid"></div>
        <div class="bm-scroll-trigger"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // =========================================
    // 1. ЭЛЕМЕНТ ИНТЕРФЕЙСА - DROPDOWN
    // =========================================
    const moodSelect = document.getElementById('mood-select');
    const resultsGrid = document.querySelector('.bm-filter-mood .bm-track-grid');
    const scrollTrigger = document.querySelector('.bm-filter-mood .bm-scroll-trigger');
    
    // Состояние фильтра
    let currentFilters = {
        mood_id: null
    };
    let lastId = null;
    let isLoading = false;
    let hasMore = true;
    
    // =========================================
    // 2. СОБЫТИЕ - CHANGE
    // =========================================
    moodSelect.addEventListener('change', function(e) {
        // Получаем выбранное значение
        const moodId = e.target.value;
        
        // Обновляем состояние
        currentFilters.mood_id = moodId || null;
        lastId = null;
        hasMore = true;
        
        // Очищаем результаты
        resultsGrid.innerHTML = '';
        
        // Загружаем новые данные
        loadTracks();
    });
    
    // =========================================
    // 3. ЗАПРОС - AJAX
    // =========================================
    function loadTracks() {
        if (isLoading || !hasMore) return;
        
        isLoading = true;
        
        // Показываем индикатор загрузки
        resultsGrid.insertAdjacentHTML('beforeend', 
            '<div class="bm-loading">Загрузка...</div>'
        );
        
        // Формируем FormData для отправки
        const formData = new FormData();
        formData.append('action', 'bm_filter_tracks');
        formData.append('filters', JSON.stringify(currentFilters));
        formData.append('last_id', lastId || '');
        
        // AJAX запрос
        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Убираем индикатор загрузки
            document.querySelector('.bm-loading')?.remove();
            
            if (data.success) {
                // =========================================
                // 4. РЕНДЕРИНГ
                // =========================================
                if (lastId === null) {
                    // Первая загрузка - заменяем контент
                    resultsGrid.innerHTML = data.data.html;
                } else {
                    // Бесконечная прокрутка - добавляем в конец
                    resultsGrid.insertAdjacentHTML('beforeend', data.data.html);
                }
                
                // Обновляем состояние
                lastId = data.data.last_id;
                hasMore = data.data.has_more;
            }
            
            isLoading = false;
        })
        .catch(error => {
            console.error('Filter error:', error);
            isLoading = false;
        });
    }
    
    // =========================================
    // БЕСКОНЕЧНАЯ ПРОКРУТКА (Intersection Observer)
    // =========================================
    if (scrollTrigger) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !isLoading && hasMore) {
                    loadTracks();
                }
            });
        });
        
        observer.observe(scrollTrigger);
    }
});
</script>

<style>
.bm-filter-mood {
    margin: 20px 0;
}

.bm-filter-select {
    padding: 10px 15px;
    font-size: 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    min-width: 250px;
    margin-bottom: 30px;
}

.bm-filter-select:focus {
    border-color: #4CAF50;
    outline: none;
}

.bm-loading {
    text-align: center;
    padding: 20px;
    color: #666;
}
</style>