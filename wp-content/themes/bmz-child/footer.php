<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package My Music Band
 */

?>

		</div><!-- .wrapper -->
		</div><!-- #content -->
		<?php get_template_part( 'template-parts/footer/footer', 'instagram' ); ?>
		
		<!-- HACK does not work -->

		<?php echo do_shortcode('[donate]'); ?>

		<?php echo do_shortcode('[audio_playlist]'); ?>

		<?php echo do_shortcode('[properties_js_config]');?>

		<?php echo do_shortcode('[cache_stats]');?>


<!-- TEST templates -->




<?php
// Полностью кастомный рендер без WP_Query
$track_repo = new BM\Repositories\TrackRepository();
$poem_repo = new BM\Repositories\PoemRepository();
$poet_repo = new BM\Repositories\PoetRepository();

// Получаем данные с кэшированием
$popular_tracks = $track_repo->getPopular(6);
$recent_tracks = $track_repo->getRecent(6);
$popular_poems = $poem_repo->getPopular(5);
$popular_poets = $poet_repo->getPopular(5);
?>

<div class="bm-home">
    
    <!-- Герой-секция -->
    <section class="bm-hero">
        <h1>Музыка поэзии</h1>
        <p>Тысячи треков на стихи великих поэтов</p>
        <a href="/tracks/" class="bm-button">Слушать</a>
    </section>
    
    <!-- Популярные треки -->
    <section class="bm-section">
        <h2 class="bm-section__title">Популярное</h2>
        
        <div class="bm-track-grid">
            <?php foreach ($popular_tracks as $track): ?>
                <?php include BM_CORE_PATH . 'Templates/track-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Новые треки -->
    <section class="bm-section">
        <h2 class="bm-section__title">Новые поступления</h2>
        
        <div class="bm-track-grid">
            <?php foreach ($recent_tracks as $track): ?>
                <?php include BM_CORE_PATH . 'Templates/track-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Популярные поэты -->
    <section class="bm-section">
        <h2 class="bm-section__title">Популярные поэты</h2>
        
        <div class="bm-poet-grid">
            <?php foreach ($popular_poets as $poet): ?>
                <div class="bm-poet-card">
                    <a href="<?php echo esc_url($poet->url); ?>">
                        <h3><?php echo esc_html($poet->short_name); ?></h3>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Популярные стихи -->
    <section class="bm-section">
        <h2 class="bm-section__title">Популярные стихотворения</h2>
        
        <div class="bm-poem-list">
            <?php foreach ($popular_poems as $poem): ?>
                <div class="bm-poem-item">
                    <a href="<?php echo esc_url($poem->url); ?>">
                        <h3><?php echo esc_html($poem->name); ?></h3>
                        <?php if ($poem->poet): ?>
                            <span><?php echo esc_html($poem->poet->short_name); ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
</div>

<?php
// =========================================
// 3. ОБРАБОТЧИК ЗАПРОСА (AJAX)
// =========================================
add_action('wp_ajax_bm_filter_tracks', 'bm_handle_filter_tracks');
add_action('wp_ajax_nopriv_bm_filter_tracks', 'bm_handle_filter_tracks');

function bm_handle_filter_tracks() {
    try {
        // Получаем и декодируем фильтры
        $filters = json_decode(stripslashes($_POST['filters']), true);
        $last_id = isset($_POST['last_id']) && $_POST['last_id'] !== '' 
            ? (int)$_POST['last_id'] 
            : null;
        
        // Используем наш репозиторий
        $track_repo = new BM\Repositories\TrackRepository();
        
        // Получаем треки с фильтрацией
        $tracks = $track_repo->filterTracks($filters, $last_id, 12);
        
        // Рендерим HTML
        ob_start();
        if (!empty($tracks)) {
            foreach ($tracks as $track) {
                include BM_CORE_PATH . 'Templates/track-card.php';
            }
        } else {
            echo '<div class="bm-no-results">Нет треков по выбранному фильтру</div>';
        }
        $html = ob_get_clean();
        
        // Отправляем ответ
        wp_send_json_success([
            'html' => $html,
            'last_id' => !empty($tracks) ? end($tracks)->id : null,
            'has_more' => count($tracks) === 12,
            'count' => count($tracks)
        ]);
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

// =========================================
// ОБРАБОТЧИК ЖИВОГО ПОИСКА
// =========================================
add_action('wp_ajax_bm_live_search', 'bm_handle_live_search');
add_action('wp_ajax_nopriv_bm_live_search', 'bm_handle_live_search');

function bm_handle_live_search() {
    try {
        $query = sanitize_text_field($_POST['query']);
        $is_autocomplete = isset($_POST['autocomplete']) && $_POST['autocomplete'];
        
        // Сервис поиска
        $search_service = new BM\Services\SearchService();
        
        if ($is_autocomplete) {
            // БЫСТРЫЙ ПОИСК ДЛЯ ПОДСКАЗОК (только названия, 5 результатов)
            $results = $search_service->autocomplete($query, 5);
            
            ob_start();
            include BM_CORE_PATH . 'Templates/search-autocomplete.php';
            $html = ob_get_clean();
            
        } else {
            // ПОЛНЫЙ ПОИСК
            $results = $search_service->search($query, ['tracks', 'poems', 'poets'], 10);
            
            ob_start();
            include BM_CORE_PATH . 'Templates/search-results.php';
            $html = ob_get_clean();
        }
        
        wp_send_json_success([
            'html' => $html,
            'results' => $results,
            'query' => $query
        ]);
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

// =========================================
// ПОЛНАЯ СТРАНИЦА ПОИСКА
// =========================================
add_action('template_redirect', function() {
    if (is_page('search') || isset($_GET['s']) && !is_admin()) {
        $query = sanitize_text_field($_GET['q'] ?? $_GET['s'] ?? '');
        
        if (!empty($query) && strlen($query) >= 3) {
            $search_service = new BM\Services\SearchService();
            $results = $search_service->search($query, ['tracks', 'poems', 'poets'], 30);
            
            // Передаем результаты в шаблон
            set_query_var('bm_search_results', $results);
            set_query_var('bm_search_query', $query);
            
            // Подключаем кастомный шаблон поиска
            $template = BM_CORE_PATH . 'Templates/page-search.php';
            if (file_exists($template)) {
                include $template;
                exit;
            }
        }
    }
});?>
<!-- TEST templates -->


		<footer id="colophon" class="site-footer">
			<?php get_template_part( 'template-parts/footer/footer', 'widgets' ); ?>

			<div id="site-generator">

				<?php get_template_part('template-parts/navigation/navigation', 'social'); ?>

				<?php get_template_part('template-parts/footer/site', 'info'); ?>
				
				<?php echo do_shortcode('[draw_links slug="" popup="false"]');?>

				<?php echo do_shortcode('[social_share]'); ?>

                <?php echo do_shortcode('[audio_top_short_code]'); ?>


				<div class="screen-reader-text" id="keyboard-instructions">
					Для навигации по трекам используйте стрелки вверх/вниз.<br>
					Для выбора трека нажмите Enter.
					Для закрытия списка нажмите Escape.
				</div>

				

				<?php 
					global $showToBot;
					global $user_data_global;
					// Выводим скрытые поля
					foreach ($user_data_global as $key => $value) {
						echo '<input type="hidden" id="user_' . esc_attr($key) . '" value="' . esc_attr($value) . '"/>';
					}
				?>

				
				<?php if($showToBot){?> 
				<?php 
				echo '<div style="color:#333;font-size:4px;text-align:center;" id="poem-text">'; ?>
				<?php 
				echo do_shortcode('[poem_text]'); ?>
				<?php 
				echo '</div>'; ?>
				<?php
				echo 
				'<div style="color:#333;font-size:4px;text-align:center;">'
				.do_shortcode('[draw_links slug="" popup="false"]')
				.'</div>'; ?>
				<?php }?>




			</div><!-- #site-generator -->
		</footer><!-- #colophon -->
	 </div><!-- .below-site-header -->
</div><!-- #page -->

<?php wp_footer(); ?>


<?php
// В конце страницы, перед закрывающим </body>
include BM_CORE_PATH . 'Templates/poem-modal.php';
?>

<!-- Подключаем скрипт -->
<script src="<?php echo BM_CORE_URL; ?>assets/js/poem-modal.js"></script>


</body>
</html>
