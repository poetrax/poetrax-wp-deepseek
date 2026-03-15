<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Адаптивное меню поэтов и стихов</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: sans-serif; }
        body { padding: 20px; background: #f5f5f5; color: #333; }
        
        .menu-container { max-width: 1200px; margin: 0 auto; }
        
        /* Адаптивное меню */
        .menu-level-1 { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .poet-btn {
            padding: 12px 20px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
            white-space: nowrap;
        }
        .poet-btn:hover { background: #3498db; transform: translateY(-2px); }
        .poet-btn.active { background: #e74c3c; }
        
        /* Второй уровень меню */
        .menu-level-2 { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 8px; 
            margin-bottom: 30px;
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: opacity 0.4s, max-height 0.4s;
        }
        .menu-level-2.active {
            opacity: 1;
            max-height: 200px;
        }
        .poem-btn {
            padding: 8px 16px;
            background: #ecf0f1;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .poem-btn:hover { background: #d5dbdb; border-color: #3498db; }
        .poem-btn.active { background: #3498db; color: white; border-color: #2980b9; }
        
        /* Контейнер для треков */
        #tracks-container { 
            background: white; 
            padding: 25px; 
            border-radius: 10px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .track-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .track-item:last-child { border-bottom: none; }
        .track-item:hover { background: #f9f9f9; }
        .track-name { font-weight: 600; color: #2c3e50; }
        .track-link { 
            color: #e74c3c; 
            text-decoration: none; 
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 4px;
        }
        .track-link:hover { 
            text-decoration: underline; 
            background: #fff5f5;
        }
        .loading { 
            text-align: center; 
            padding: 40px; 
            color: #7f8c8d; 
            font-style: italic;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .menu-level-1 { justify-content: center; }
            .poet-btn { flex: 1 1 calc(50% - 10px); min-width: 0; }
            .menu-level-2 { justify-content: center; }
        }
        @media (max-width: 480px) {
            .poet-btn { flex: 1 1 100%; }
            .track-item { flex-direction: column; align-items: flex-start; gap: 10px; }
        }
    </style>
</head>
<body>
    <div class="menu-container">
        <h1>🎵 Музыкальная библиотека стихов</h1>
        <p>Выберите поэта, затем стихотворение чтобы увидеть доступные аудиотреки</p>
        
        <!-- Первый уровень меню: Поэты -->
        <div class="menu-level-1" id="poets-menu"></div>
        
        <!-- Второй уровень меню: Стихи -->
        <div class="menu-level-2" id="poems-menu"></div>
        
        <!-- Контейнер для вывода треков -->
        <div id="tracks-container">
            <div class="loading" id="tracks-loading">Треки появятся здесь после выбора стихотворения...</div>
            <div id="tracks-list"></div>
        </div>
    </div>

    <script>
        let currentPoetId = null;
        let currentPoemId = null;

        // 1. Загрузка данных для меню (поэты) при старте
        document.addEventListener('DOMContentLoaded', function() {
            loadPoets();
        });

        function loadPoets() {
            fetch('menu-get-data.php')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        renderPoetsMenu(data.poets);
                    } else {
                        console.error('Ошибка загрузки поэтов:', data.error);
                    }
                })
                .catch(error => console.error('Ошибка сети:', error));
        }

        // 2. Отрисовка меню поэтов (первый уровень)
        function renderPoetsMenu(poets) {
            const container = document.getElementById('poets-menu');
            container.innerHTML = '';
            
            poets.forEach(poet => {
                const button = document.createElement('button');
                button.className = 'poet-btn';
                button.textContent = poet.short_name || `Поэт ID: ${poet.id}`;
                button.dataset.poetId = poet.id;
                
                button.onclick = () => selectPoet(poet.id, poet.poems);
                container.appendChild(button);
            });
        }

        // 3. Обработка выбора поэта
        function selectPoet(poetId, poems) {
            currentPoetId = poetId;
            
            // Сброс активных кнопок
            document.querySelectorAll('.poet-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Показываем второй уровень меню
            renderPoemsMenu(poems);
            const poemsMenu = document.getElementById('poems-menu');
            poemsMenu.classList.add('active');
            
            // Очищаем список треков
            document.getElementById('tracks-list').innerHTML = '';
            document.getElementById('tracks-loading').style.display = 'block';
        }

        // 4. Отрисовка меню стихов (второй уровень)
        function renderPoemsMenu(poems) {
            const container = document.getElementById('poems-menu');
            container.innerHTML = '';
            
            if(poems.length === 0) {
                container.innerHTML = '<p>Нет стихов для этого поэта</p>';
                return;
            }
            
            poems.forEach(poem => {
                const button = document.createElement('button');
                button.className = 'poem-btn';
                button.textContent = poem.name;
                button.dataset.poemId = poem.id;
                
                button.onclick = () => selectPoem(poem.id);
                container.appendChild(button);
            });
        }

        // 5. Обработка выбора стихотворения и загрузка треков
        function selectPoem(poemId) {
            currentPoemId = poemId;
            
            // Сброс активных кнопок стихов
            document.querySelectorAll('.poem-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Загрузка треков через AJAX
            loadTracks(poemId);
        }

        function loadTracks(poemId) {
            const loadingEl = document.getElementById('tracks-loading');
            const listEl = document.getElementById('tracks-list');
            
            loadingEl.textContent = 'Загрузка треков...';
            loadingEl.style.display = 'block';
            listEl.innerHTML = '';
            
            fetch(`menu-get-tracks.php?poem_id=${poemId}`)
                .then(response => response.json())
                .then(data => {
                    loadingEl.style.display = 'none';
                    
                    if(data.success && data.tracks.length > 0) {
                        renderTracksList(data.tracks);
                    } else {
                        listEl.innerHTML = '<div class="track-item">Для этого стихотворения пока нет аудиотреков</div>';
                    }
                })
                .catch(error => {
                    console.error('Ошибка загрузки треков:', error);
                    loadingEl.style.display = 'none';
                    listEl.innerHTML = '<div class="track-item" style="color:#e74c3c">Ошибка загрузки данных</div>';
                });
        }

        // 6. Отрисовка списка треков
        function renderTracksList(tracks) {
            const container = document.getElementById('tracks-list');
            container.innerHTML = '';
            
            tracks.forEach(track => {
                const trackEl = document.createElement('div');
                trackEl.className = 'track-item';
                
                trackEl.innerHTML = `
                    <div>
                        <div class="track-name">${track.track_name}</div>
                        <div style="font-size:14px; color:#7f8c8d; margin-top:5px">
                            Формат: ${track.track_format || 'не указан'} | 
                            Длительность: ${track.track_duration} сек.
                        </div>
                    </div>
                    <a href="${track.track_path}" class="track-link" target="_blank">Слушать трек</a>
                `;
                
                container.appendChild(trackEl);
            });
        }
    </script>
</body>
</html>
