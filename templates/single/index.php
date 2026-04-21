<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $item->title ?> - Poetrax</title>
    <link rel="stylesheet" href="/assets/css/single.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/partials/header.php'; ?>
    
    <main class="single" data-type="<?= $type ?>" data-id="<?= $item->id ?>">
        <!-- Контент подгружается через API -->
        <div class="single-loader">Загрузка...</div>
    </main>
    
    <?php include dirname(__DIR__) . '/partials/footer.php'; ?>
    <script src="/assets/js/single.js"></script>
</body>
</html>
