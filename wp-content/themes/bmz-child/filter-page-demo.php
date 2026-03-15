<?php
// page-filters-demo.php
/*
Template Name: Демо фильтров и поиска
*/

get_header();
?>

<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
    
    <h1 style="margin-bottom: 40px;">🎵 Демо фильтрации и поиска</h1>
    
    <!-- ========================================= -->
    <!-- 1. ФИЛЬТР ЧЕРЕЗ DROPDOWN -->
    <!-- ========================================= -->
    <section style="margin-bottom: 60px;">
        <h2 style="margin-bottom: 20px;">Фильтрация по настроению</h2>
        <?php include BM_CORE_PATH . 'Templates/filter-mood.php'; ?>
    </section>
    
    <hr style="margin: 40px 0;">
    
    <!-- ========================================= -->
    <!-- 2. ПОИСК ЧЕРЕЗ ТЕКСТОВОЕ ПОЛЕ -->
    <!-- ========================================= -->
    <section>
        <h2 style="margin-bottom: 20px;">Живой поиск</h2>
        <?php include BM_CORE_PATH . 'Templates/search-live.php'; ?>
    </section>
    
</div>

<?php
get_footer();