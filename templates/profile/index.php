<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Poetrax</title>
    <link rel="stylesheet" href="/assets/css/profile.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/partials/header.php'; ?>
    
    <main class="profile">
        <h1>Личный кабинет</h1>
        
        <nav class="profile-tabs">
            <button data-tab="tracks" class="active">Мои треки</button>
            <button data-tab="liked">Понравившиеся</button>
            <button data-tab="bookmarks">Закладки</button>
            <button data-tab="recommendations">Рекомендации</button>
            <button data-tab="settings">Настройки</button>
        </nav>
        
        <div class="profile-content" id="profile-content">
            <!-- Контент загружается через API -->
        </div>
    </main>
    
    <?php include dirname(__DIR__) . '/partials/footer.php'; ?>
    <script src="/assets/js/profile.js"></script>
</body>
</html>