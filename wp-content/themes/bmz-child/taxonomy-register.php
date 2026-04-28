<?php
namespace BM;
use BM\Hierachy;
/**
 * Регистрация таксономии "Поэты" (только для интерфейса)
 * Данные хранятся в отдельной БД, WordPress только отображает
 */
function register_poets_taxonomy() {
    
    // Проверяем, не зарегистрирована ли уже
    if (taxonomy_exists('poets')) {
        return;
    }
    
    register_taxonomy(
        'poets',           // машинное имя
        'post',            // тип записи
        array(
            // Метки для админки
            'labels' => array(
                'name'                       => 'Поэты (внешняя БД)',
                'singular_name'              => 'Поэт',
                'menu_name'                   => 'Поэты',
                'all_items'                    => 'Все поэты',
                'edit_item'                    => 'Редактировать поэта',
                'view_item'                    => 'Просмотреть поэта',
                'update_item'                  => 'Обновить поэта',
                'add_new_item'                 => 'Добавить нового поэта',
                'new_item_name'                => 'Имя нового поэта',
                'search_items'                  => 'Поиск поэтов',
                'popular_items'                 => 'Популярные поэты',
                'separate_items_with_commas'    => 'Разделите поэтов запятыми',
                'add_or_remove_items'           => 'Добавить или удалить поэта',
                'choose_from_most_used'         => 'Выбрать из часто используемых',
                'not_found'                      => 'Поэты не найдены',
                'back_to_items'                  => '← Назад к списку поэтов',
                'parent_item'                    => 'Родительский поэт',
                'parent_item_colon'              => 'Родительский поэт:',
            ),
            
            // Параметры публичности
            'public'            => true,
            'publicly_queryable' => false, // ВАЖНО: false - не используем стандартные URL WordPress
            'show_ui'           => true,   // показываем в админке
            'show_in_menu'      => true,   // показываем в меню
            'show_in_nav_menus' => false,  // не показываем в меню навигации
            'show_in_rest'      => true,   // для блочного редактора
            'show_tagcloud'     => false,  // не показываем в облаке тегов
            'show_in_quick_edit' => false, // не показываем в быстром редактировании
            
            // Параметры таксономии
            'hierarchical'      => true,   // иерархическая (как рубрики)
            'query_var'         => false,  // ВАЖНО: false - не создаем query var
            'rewrite'           => false,  // ВАЖНО: false - не создаем правила перезаписи
            
            // Дополнительные параметры
            'meta_box_cb'       => 'poets_custom_meta_box', // кастомный метабокс
            'capabilities'      => array(
                'manage_terms' => 'manage_options',
                'edit_terms'   => 'manage_options',
                'delete_terms' => 'manage_options',
                'assign_terms' => 'edit_posts'
            ),
        )
    );
}
add_action('init', 'register_poets_taxonomy', 1); // высокий приоритет

/**
 * Кастомный метабокс для выбора поэтов
 * Вместо стандартного вывода используем нашу иерархию
 */
function poets_custom_meta_box($post, $box) {
    $hierarchy = HierarchyManager::getInstance();
    $tree = $hierarchy->getFullTree();
    $selected = wp_get_post_terms($post->ID, 'poets', array('fields' => 'ids'));
    
    ?>
    <div class="poets-selector" style="max-height: 400px; overflow-y: auto; padding: 10px; background: #fff; border: 1px solid #ddd;">
        <?php foreach ($tree as $century): ?>
            <div style="margin-bottom: 15px;">
                <h4 style="background: #f0f0f0; padding: 5px; margin: 0;">
                    <?php echo esc_html($century['name']); ?>
                </h4>
                
                <?php foreach ($century['movements'] as $movement): ?>
                    <div style="margin-left: 15px; margin-top: 10px;">
                        <h5 style="color: #666; margin: 0 0 5px 0;">
                            <?php echo esc_html($movement['name']); ?>
                        </h5>
                        
                        <?php if (!empty($movement['submovements'])): ?>
                            <?php foreach ($movement['submovements'] as $sub): ?>
                                <div style="margin-left: 15px; margin-bottom: 5px;">
                                    <strong><?php echo esc_html($sub['name']); ?></strong>
                                    <?php foreach ($sub['poets'] as $poet): ?>
                                        <label style="display: block; margin-left: 15px;">
                                            <input type="checkbox" 
                                                   name="tax_input[poets][]" 
                                                   value="<?php echo $poet['id']; ?>"
                                                   <?php checked(in_array($poet['id'], $selected)); ?>>
                                            <?php echo esc_html($poet['name']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($movement['poets'])): ?>
                            <div style="margin-left: 15px;">
                                <?php foreach ($movement['poets'] as $poet): ?>
                                    <label style="display: block;">
                                        <input type="checkbox" 
                                               name="tax_input[poets][]" 
                                               value="<?php echo $poet['id']; ?>"
                                               <?php checked(in_array($poet['id'], $selected)); ?>>
                                        <?php echo esc_html($poet['name']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($tree)): ?>
        <p>Поэты не найдены в внешней базе данных.</p>
    <?php endif; ?>
    
    <script>
    jQuery(document).ready(function($) {
        // Можно добавить JavaScript для улучшения выбора
    });
    </script>
    <?php
}

/**
 * Сохранение связей с поэтами в отдельную БД
 * Переопределяем стандартное сохранение терминов
 */
function save_poets_relationships($post_id, $post) {
    // Проверки безопасности
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if ($post->post_type != 'post') return;
    
    // Получаем выбранных поэтов из формы
    $selected_poets = isset($_POST['tax_input']['poets']) 
        ? array_map('intval', (array)$_POST['tax_input']['poets']) 
        : [];
    
    // Сохраняем в отдельную БД через нашу иерархию
    $hierarchy = HierarchyManager::getInstance();
    
    // Удаляем старые связи
    $hierarchy->poet()->db->query(
        "DELETE FROM works WHERE wp_post_id = ?i",
        $post_id
    );
    
    // Создаем новые связи
    foreach ($selected_poets as $poet_id) {
        $hierarchy->poet()->db->query(
            "INSERT INTO works (poet_id, title, content, type, wp_post_id, published_at) 
             VALUES (?i, ?s, ?s, 'poem', ?i, NOW())",
            $poet_id,
            $post->post_title,
            $post->post_content,
            $post_id
        );
    }
}
add_action('save_post', 'save_poets_relationships', 10, 2);

/**
 * Удаление связей при удалении поста
 */
function delete_poets_relationships($post_id) {
    $hierarchy = HierarchyManager::getInstance();
    $hierarchy->poet()->db->query(
        "DELETE FROM works WHERE wp_post_id = ?i",
        $post_id
    );
}
add_action('delete_post', 'delete_poets_relationships');

/**
 * Добавляем колонку с поэтами в список постов
 */
function add_poets_column($columns) {
    $columns['poets'] = 'Поэты';
    return $columns;
}
add_filter('manage_posts_columns', 'add_poets_column');

function show_poets_column($column_name, $post_id) {
    if ($column_name == 'poets') {
        $hierarchy = HierarchyManager::getInstance();
        $works = $hierarchy->poet()->db->select(
            "SELECT p.name, p.slug 
             FROM works w
             JOIN poets p ON w.poet_id = p.id
             WHERE w.wp_post_id = ?i",
            $post_id
        );
        
        $poets = array_map(function($work) {
            return sprintf(
                '<a href="%s" target="_blank">%s</a>',
                $hierarchy->poet()->getUrl($work['slug']),
                esc_html($work['name'])
            );
        }, $works);
        
        echo implode(', ', $poets) ?: '—';
    }
}
add_action('manage_posts_custom_column', 'show_poets_column', 10, 2);

/**
 * Отключаем стандартные правила перезаписи для poets
 */
function remove_poets_rewrite_rules() {
    // Удаляем правила, если они были созданы
    $rules = get_option('rewrite_rules');
    if (is_array($rules)) {
        $need_update = false;
        foreach (array_keys($rules) as $rule) {
            if (strpos($rule, 'poet') !== false || strpos($rule, 'poets') !== false) {
                unset($rules[$rule]);
                $need_update = true;
            }
        }
        if ($need_update) {
            update_option('rewrite_rules', $rules);
        }
    }
}
add_action('init', 'remove_poets_rewrite_rules', 999);

/**
 * Добавляем пункт меню для управления поэтами во внешней БД
 */
function add_poets_admin_menu() {
    add_menu_page(
        'Поэты (внешняя БД)',
        'Поэты',
        'manage_options',
        'external-poets',
        'render_external_poets_page',
        'dashicons-book',
        25
    );
    
    add_submenu_page(
        'external-poets',
        'Века',
        'Века',
        'manage_options',
        'external-centuries',
        'render_external_centuries_page'
    );
    
    add_submenu_page(
        'external-poets',
        'Направления',
        'Направления',
        'manage_options',
        'external-movements',
        'render_external_movements_page'
    );
    
    add_submenu_page(
        'external-poets',
        'Статистика',
        'Статистика',
        'manage_options',
        'external-stats',
        'render_external_stats_page'
    );
}
add_action('admin_menu', 'add_poets_admin_menu');

/**
 * Отображение страницы с поэтами
 */
function render_external_poets_page() {
    $hierarchy = HierarchyManager::getInstance();
    $stats = $hierarchy->getStats();
    $century_stats = $hierarchy->poet()->getCenturyStats();
    
    ?>
    <div class="wrap">
        <h1>Поэты (внешняя база данных)</h1>
        
        <div class="stats-boxes" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <h3>Всего поэтов</h3>
                <p style="font-size: 36px; margin: 0;"><?php echo $stats['poets']; ?></p>
            </div>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <h3>С привязкой к веку</h3>
                <p style="font-size: 36px; margin: 0;"><?php echo $stats['poets_with_century']; ?></p>
            </div>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <h3>С направлением</h3>
                <p style="font-size: 36px; margin: 0;"><?php echo $stats['poets_with_movement']; ?></p>
            </div>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <h3>Произведений</h3>
                <p style="font-size: 36px; margin: 0;"><?php echo $stats['works']; ?></p>
            </div>
        </div>
        
        <h2>Поэты по векам</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Век</th>
                    <th>Количество поэтов</th>
                    <th>Примеры URL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($century_stats as $stat): ?>
                    <tr>
                        <td><?php echo esc_html($stat['name']); ?></td>
                        <td><?php echo $stat['poet_count']; ?></td>
                        <td>
                            <code>/poet/<?php echo $stat['slug']; ?>/</code>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>Последние добавленные поэты</h2>
        <?php
        $recent_poets = $hierarchy->poet()->db->select(
            "SELECT p.*, c.name as century_name 
             FROM poets p
             LEFT JOIN centuries c ON p.century_id = c.id
             ORDER BY p.id DESC
             LIMIT 20"
        );
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Век</th>
                    <th>Slug</th>
                    <th>URL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_poets as $poet): ?>
                    <tr>
                        <td><?php echo $poet['id']; ?></td>
                        <td><?php echo esc_html($poet['name']); ?></td>
                        <td><?php echo esc_html($poet['century_name']); ?></td>
                        <td><code><?php echo $poet['slug']; ?></code></td>
                        <td><code><?php echo $hierarchy->poet()->getUrl($poet); ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function render_external_centuries_page() {
    $hierarchy = HierarchyManager::getInstance();
    $centuries = $hierarchy->century()->getAll();
    
    ?>
    <div class="wrap">
        <h1>Века</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Slug</th>
                    <th>Годы</th>
                    <th>Направлений</th>
                    <th>URL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($centuries as $century): 
                    $movements = $hierarchy->century()->getMovements($century['id']);
                ?>
                    <tr>
                        <td><?php echo $century['id']; ?></td>
                        <td><?php echo esc_html($century['name']); ?></td>
                        <td><code><?php echo $century['slug']; ?></code></td>
                        <td><?php echo $century['start_year'] . ' — ' . $century['end_year']; ?></td>
                        <td><?php echo count($movements); ?></td>
                        <td><code>/poet/<?php echo $century['slug']; ?>/</code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function render_external_movements_page() {
    $hierarchy = HierarchyManager::getInstance();
    $tree = $hierarchy->movement()->getTree();
    
    function render_movement_tree($movements, $level = 0) {
        foreach ($movements as $movement) {
            $indent = str_repeat('— ', $level);
            ?>
            <tr>
                <td><?php echo $movement['id']; ?></td>
                <td><?php echo $indent . esc_html($movement['name']); ?></td>
                <td><code><?php echo $movement['slug']; ?></code></td>
                <td><?php echo $movement['level']; ?></td>
                <td><?php echo $movement['century_id'] ?: '—'; ?></td>
                <td><code><?php echo $hierarchy->movement()->getUrl($movement); ?></code></td>
            </tr>
            <?php
            if (!empty($movement['children'])) {
                render_movement_tree($movement['children'], $level + 1);
            }
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Направления (иерархия)</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Slug</th>
                    <th>Уровень</th>
                    <th>Век</th>
                    <th>URL</th>
                </tr>
            </thead>
            <tbody>
                <?php render_movement_tree($tree); ?>
            </tbody>
        </table>
    </div>
    <?php
}

function render_external_stats_page() {
    $hierarchy = HierarchyManager::getInstance();
    $stats = $hierarchy->getStats();
    
    ?>
    <div class="wrap">
        <h1>Статистика иерархии</h1>
        
        <h2>Общая статистика</h2>
        <table class="wp-list-table widefat fixed striped">
            <tbody>
                <tr><th>Всего веков</th><td><?php echo $stats['centuries']; ?></td></tr>
                <tr><th>Всего направлений</th><td><?php echo $stats['movements']; ?></td></tr>
                <tr><th>Всего поэтов</th><td><?php echo $stats['poets']; ?></td></tr>
                <tr><th>Всего произведений</th><td><?php echo $stats['works']; ?></td></tr>
            </tbody>
        </table>
        
        <h2>Направления по уровням</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Уровень</th>
                    <th>Количество</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['movements_by_level'] as $level): ?>
                    <tr>
                        <td><?php echo $level['level']; ?></td>
                        <td><?php echo $level['count']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>Проверка URL</h2>
        <?php
        $sample_poets = $hierarchy->poet()->db->select(
            "SELECT * FROM poets WHERE full_slug IS NOT NULL LIMIT 10"
        );
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Поэт</th>
                    <th>Full Slug</th>
                    <th>URL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sample_poets as $poet): ?>
                    <tr>
                        <td><?php echo esc_html($poet['name']); ?></td>
                        <td><code><?php echo $poet['full_slug']; ?></code></td>
                        <td><code><?php echo $hierarchy->poet()->getUrl($poet); ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}