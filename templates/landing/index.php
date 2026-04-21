<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poetrax - Музыка поэзии</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/partials/header.php'; ?>
    
    <main class="landing">
        <!-- Hero -->
        <section class="hero">
            <h1>Стихи, ожившие в музыке</h1>
            <p>Тысячи треков на стихи великих поэтов</p>
            <div class="hero-search">
                <input type="text" id="search-input" placeholder="Поиск поэтов, стихов, треков...">
            </div>
        </section>
        
        <!-- Popular tracks -->
        <section class="popular-tracks">
            <h2>Популярные треки</h2>
            <div class="tracks-grid" id="popular-tracks"></div>
        </section>
        
        <!-- Random poets -->
        <section class="random-poets">
            <h2>Поэты</h2>
            <div class="poets-grid" id="random-poets"></div>
        </section>
        
        <!-- How it works -->
        <section class="how-it-works">
            <h2>Как это работает</h2>
            <div class="steps">
                <div class="step">1. Выбираете поэта</div>
                <div class="step">2. Выбираете стих</div>
                <div class="step">3. Слушаете трек</div>
            </div>
        </section>
    </main>
    
    <?php include dirname(__DIR__) . '/partials/footer.php'; ?>
    <script src="/assets/js/loader.js"></script>
</body>
</html>
