<?php
/**
 * Класс для генерации ссылок с поддержкой popup и обычных ссылок
 */
class DrawLinks
{
    protected bool $isPopup;
    protected string $currentSlug;
    
    /**
     * Маппинг ссылок: [slug => [title, popup_class]]
     */
    protected const LINKS = [
        'o-proekte' => ['title' => 'О проекте', 'popup_class' => '1351'],
        'polza-stiha-i-pesni' => ['title' => 'О пользе песни', 'popup_class' => '1478'],
        'sponsoram' => ['title' => 'О партнерстве', 'popup_class' => '1353'],
        'polozhenie-o-konkurse' => ['title' => 'Положение о конкурсе', 'popup_class' => '1476'],
        'oferta-soglashenie' => ['title' => 'Соглашение Оферта', 'popup_class' => '1481'],
        'dogovor-na-razrabotku' => ['title' => 'Договор на разработку трека', 'popup_class' => '1482'],
        'dogovor-ob-otchuzhdenii-isklyuchitelnogo-prava' => ['title' => 'Договор об отчуждении исключительного права', 'popup_class' => null],
        'dogovor-pozhertvovanija' => ['title' => 'Договор пожертвования', 'popup_class' => '1483'],
        'otkaz-ot-otvetstvennosti' => ['title' => 'Отказ от ответственности', 'popup_class' =>'1485'],
        'podderzhat-proekt' => ['title' => 'Поддержать проект', 'popup_class' => '5903'],
        'privacy-policy' => ['title' => 'Политика конфиденциальности', 'popup_class' =>'1492'],
        'polzovatelskoe-soglashenie' => ['title' => 'Пользовательское соглашение', 'popup_class' =>'1487'],
        'tonkosti-ii-muzyki' => ['title' => 'Тонкости ИИ музыки', 'popup_class' => '1501'],
        'zakaz-treka' => ['title' => 'Заказ трека', 'popup_class' => '1318'],
    ];

    public function __construct(string $currentSlug = '', bool $isPopup = false)
    {
        $this->currentSlug = $currentSlug;
        $this->isPopup = $isPopup;
    }

    /**
     * Генерирует HTML ссылок
     */
    public function generateLinks(): string
    {
        $links = [];
        
        foreach (self::LINKS as $slug => $data) {
            // Пропускаем текущую страницу
            if ($slug === $this->currentSlug) {
                continue;
            }
            
            $links[] = $this->generateLink($slug, $data['title'], $data['popup_class']);
        }
        
        return !empty($links) ? '<div class="draw_links">'. implode('&nbsp;', $links).'</div>' : '';
    }

    /**
     * Генерирует одну ссылку
     */
    protected function generateLink(string $slug, string $title, ?string $popupClass = null): string
    {
        //global $showToBot;
        
        //if(!$showToBot){
            if ($this->isPopup && $popupClass !== null) {
                return sprintf(
                    '<a href="#" class="popmake-%s pum-trigger">%s</a>',
                    esc_attr($popupClass),
                    esc_html($title)
                );
            }
        //}
        
        return sprintf(
            '<a href="%s" target="_blank" rel="noopener">%s</a>',
            esc_url('/' . $slug),
            esc_html($title)
        );
    }

    /**
     * Shortcode для WordPress
     */
    public static function shortcodeHandler(array $atts = []): string
    {
        $atts = shortcode_atts([
            'slug' => '',
            'popup' => false,
        ], $atts);
        
        $instance = new self(
            sanitize_text_field($atts['slug']),
            filter_var($atts['popup'], FILTER_VALIDATE_BOOLEAN)
        );
        
        return $instance->generateLinks();
    }
}

// Примеры использования:

// 1. Регистрация шорткода в WordPress
// add_shortcode('draw_links', [DrawLinks::class, 'shortcodeHandler']);

// 2. Использование в шаблоне
// <?php 
// $drawLinks = new DrawLinks('o-proekte', false);
// echo $drawLinks->generateLinks();
// ? >

// 3. Использование шорткода в контенте
// [draw_links slug="o-projekte" popup="false"]
// [draw_links slug="" popup="true"]


// Кэширование
class DrawLinksCached extends DrawLinks 
{
    protected function getCacheKey(): string
    {
        return 'draw_links_' . $this->currentSlug . '_' . (int)$this->isPopup;
    }
    
    public function generateLinks(): string
    {
        $cache_key = $this->getCacheKey();
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $links = parent::generateLinks();
        set_transient($cache_key, $links, HOUR_IN_SECONDS);
        
        return $links;
    }
}