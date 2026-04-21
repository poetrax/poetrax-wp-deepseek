<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucfirst($entity) ?> - Poetrax</title>
    <link rel="stylesheet" href="/assets/css/catalog.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/partials/header.php'; ?>
    
    <main class="catalog">
        <aside class="filters">
            <h3>Фильтры</h3>
            <div id="filters-container" data-entity="<?= $entity ?>"></div>
        </aside>
        
        <section class="catalog-content">
            <h1><?= ucfirst($entity) ?></h1>
            <div class="items-grid" id="items-grid" data-entity="<?= $entity ?>"></div>
            <div class="pagination" id="pagination"></div>
        </section>
    </main>
    
    <?php include dirname(__DIR__) . '/partials/footer.php'; ?>
    <script src="/assets/js/catalog.js"></script>
</body>
</html>
