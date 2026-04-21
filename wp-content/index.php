<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poetrax — музыка на стихи русских поэтов</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <div class="logo">Poetrax</div>
        <nav>
            <a href="/" class="active">Главная</a>
            <a href="/catalog.html">Магазин</a>
            <a href="/profile.html">Личный кабинет</a>
            <a href="/messages.html">Сообщения</a>
            <a href="/admin.html">Админка</a>
        </nav>
        <div class="user-info">
            <span id="userName"></span>
            <button id="logoutBtn" style="display:none">Выйти</button>
            <button id="loginBtn">Войти</button>
        </div>
    </header>

    <main>
        <!-- Секция треков -->
        <section id="tracks-section">
            <h2>Треки</h2>
            <div class="filters">
                <input type="text" id="searchTracks" placeholder="Поиск треков...">
                <select id="filterLang">
                    <option value="">Все языки</option>
                    <option value="ru">Русский</option>
                    <option value="en">Английский</option>
                </select>
                <select id="filterVoiceGender">
                    <option value="">Все голоса</option>
                    <option value="male">Мужской</option>
                    <option value="female">Женский</option>
                </select>
            </div>
            <div id="tracks-list" class="grid"></div>
            <div id="tracks-pagination" class="pagination"></div>
        </section>

        <!-- Секция поэтов -->
        <section id="poets-section">
            <h2>Поэты</h2>
            <div id="poets-list" class="grid"></div>
        </section>

        <!-- Секция стихов -->
        <section id="poems-section">
            <h2>Стихотворения</h2>
            <div id="poems-list" class="grid"></div>
        </section>
    </main>

    <script src="/js/api.js"></script>
    <script src="/js/main.js"></script>
</body>
</html>
