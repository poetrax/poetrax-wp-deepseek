/**
 * Poetrax JS Loader
 * Единая точка входа для всех модулей
 */

(function() {
    'use strict';

    // Конфигурация модулей
    const modules = {
        // Общие для всех страниц
        common: [
            'core/api-client',
            'core/logger'
        ],
        
        // Главная страница
        home: [
            'modules/infinite-scroll',
            'modules/live-search'
        ],
        
        // Каталоги (треки, поэты, стихи)
        catalog: [
            'modules/infinite-scroll',
            'modules/filters',
            'modules/cascade-manager'
        ],
        
        // Детальные страницы
        track: [
            'modules/track-player',
            'modules/track-handler',
            'modules/interactions',
            'modules/social-share',
            'modules/text-file-popup'
        ],
        
        poet: [
            'modules/poet-tracks',
            'modules/interactions',
            'modules/social-share'
        ],
        
        poem: [
            'modules/text-file-popup',
            'modules/social-share'
        ],
        
        // Личный кабинет
        profile: [
            'modules/infinite-scroll',
            'modules/interactions',
            'modules/player'  // для предпрослушивания
        ],
        
        // Поиск
        search: [
            'modules/infinite-scroll',
            'modules/live-search'
        ]
    };

    // Определяем текущую страницу
    function getCurrentPage() {
        const path = window.location.pathname;
        
        if (path === '/' || path === '/index.php') return 'home';
        if (path.startsWith('/track/')) return 'track';
        if (path.startsWith('/poet/')) return 'poet';
        if (path.startsWith('/poem/')) return 'poem';
        if (path.startsWith('/tracks') || path.startsWith('/catalog/tracks')) return 'catalog';
        if (path.startsWith('/poets') || path.startsWith('/catalog/poets')) return 'catalog';
        if (path.startsWith('/poems') || path.startsWith('/catalog/poems')) return 'catalog';
        if (path.startsWith('/profile') || path.startsWith('/lichnyj-zal')) return 'profile';
        if (path.startsWith('/search')) return 'search';
        
        return 'unknown';
    }

    // Загружаем модули для страницы
    async function loadModules() {
        const page = getCurrentPage();
        const modulesToLoad = [...modules.common, ...(modules[page] || [])];
        
        console.log(`Loading modules for page: ${page}`, modulesToLoad);
        
        // Загружаем все модули параллельно
        const promises = modulesToLoad.map(module => {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = `/assets/js/${module}.js`;
                script.onload = resolve;
                script.onerror = reject;
                document.body.appendChild(script);
            });
        });
        
        try {
            await Promise.all(promises);
            console.log(`✅ All modules loaded for ${page}`);
            
            // Инициализация после загрузки
            document.dispatchEvent(new CustomEvent('bm:modules-ready', { 
                detail: { page } 
            }));
            
        } catch (error) {
            console.error('❌ Failed to load modules:', error);
        }
    }

    // Запускаем после загрузки DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadModules);
    } else {
        loadModules();
    }
})();
