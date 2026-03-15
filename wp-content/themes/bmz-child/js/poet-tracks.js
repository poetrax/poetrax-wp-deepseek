(function ($) {
    'use strict';

    $(document).ready(function () {
        console.log('✅ BMZ Поэты: DOM готов, инициализируем...');

        // Ждем немного для полной загрузки страницы
        setTimeout(function () {
            initPoetTracks();
        }, 500);
    });
    let isOpen = false;
    function initPoetTracks() {
        const poetLinks = document.querySelectorAll('.wp-block-categories-list li.cat-item a, .wp-block-categories li.cat-item a');
        console.log(`📊 Найдено ${poetLinks.length} ссылок на поэтов`);

        if (poetLinks.length === 0) {
            console.log('⚠️ Поэты не найдены, проверяем другие селекторы...');

            // Альтернативные селекторы
            const altSelectors = [
                '.wp-block-group .cat-item a',
                '.cat-item a',
                '.wp-block-categories a'
            ];

            altSelectors.forEach(selector => {
                const links = document.querySelectorAll(selector);
                if (links.length > 0) {
                    console.log(`✅ Найдено по селектору "${selector}": ${links.length} ссылок`);
                }
            });
            return;
        }

        // Проверяем, загрузился ли объект poetTracks
        if (typeof poetTracks === 'undefined') {
            console.error('❌ Объект poetTracks не определен! Проверьте functions.php');
            return;
        }

        console.log('🔧 poetTracks object:', poetTracks);

        poetLinks.forEach((link, index) => {
            const poetName = link.textContent.trim();
            console.log(`🎯 Поэт ${index + 1}: ${poetName}`);

            // Проверяем, не обрабатывали ли уже эту ссылку
            if (link.hasAttribute('data-poet-processed')) {
                return;
            }

            link.setAttribute('data-poet-processed', 'true');
            link.setAttribute('aria-haspopup', 'true');
            link.setAttribute('aria-expanded', 'false');

            // Создаем dropdown
            const dropdown = document.createElement('div');
            dropdown.className = 'bmz-poet-tracks-dropdown';
            dropdown.id = 'poet-dropdown-' + index;
            dropdown.setAttribute('role', 'menu');
            dropdown.setAttribute('aria-label', 'Треки поэта ' + poetName);
            dropdown.setAttribute('aria-hidden', 'true');
            dropdown.setAttribute('tabindex', '-1');

            const parentLi = link.closest('li');
            if (parentLi) {
                parentLi.style.position = 'relative';
                parentLi.appendChild(dropdown);
            } else {
                link.parentElement.style.position = 'relative';
                link.parentElement.appendChild(dropdown);
            }

            // Добавляем стили
            dropdown.style.cssText = `
                position: absolute;
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 0;
                display: none;
                z-index: 10000;
                min-width: 250px;
                max-width: 300px;
                max-height: 400px;
                overflow-y: auto;
                box-shadow: 0 5px 20px rgba(0,0,0,0.15);
                margin-top: 5px;
                left: 0;
                top: 100%;
            `;

            // Переменные состояния
            let hoverTimer;
            let hideTimer;
            isOpen = false;

            // Обработчики мыши
            link.addEventListener('mouseenter', function (e) {
                clearTimeout(hideTimer);
                clearTimeout(hoverTimer);
                hoverTimer = setTimeout(() => {
                    if (!isOpen) {
                        showPoetTracks(this, dropdown);
                    }
                }, 300);
            });

            link.addEventListener('mouseleave', function (e) {
                clearTimeout(hoverTimer);
                hideTimer = setTimeout(() => {
                    if (!dropdown.matches(':hover')) {
                        hideDropdown(dropdown, this);
                    }
                }, 200);
            });

            dropdown.addEventListener('mouseenter', function () {
                clearTimeout(hideTimer);
            });

            dropdown.addEventListener('mouseleave', function () {
                hideTimer = setTimeout(() => {
                    if (!link.matches(':hover')) {
                        hideDropdown(dropdown, link);
                    }
                }, 200);
            });

            // Обработчики клавиатуры
            link.addEventListener('keydown', function (e) {
                switch (e.key) {
                    case 'Enter':
                    case ' ':
                        e.preventDefault();
                        if (!isOpen) {
                            showPoetTracks(this, dropdown);
                        } else {
                            hideDropdown(dropdown, this);
                        }
                        break;
                    case 'Escape':
                        if (isOpen) {
                            hideDropdown(dropdown, this);
                        }
                        break;
                    case 'ArrowDown':
                        if (isOpen) {
                            e.preventDefault();
                            const firstItem = dropdown.querySelector('a');
                            if (firstItem) firstItem.focus();
                        }
                        break;
                }
            });

            // Обработчик фокуса
            link.addEventListener('focus', function () {
                clearTimeout(hideTimer);
            });
        });

        console.log('✅ BMZ Поэты: инициализация завершена');
    }

    function showPoetTracks(link, dropdown) {
        const poetName = link.textContent.trim();
        console.log(`🔍 Ищем треки для: "${poetName}"`);

        isOpen = true;
        link.setAttribute('aria-expanded', 'true');

        // Очищаем dropdown
        dropdown.innerHTML = '';

        // Добавляем заголовок
        const header = document.createElement('div');
        header.className = 'bmz-dropdown-header';
        header.textContent = 'Треки:';
        header.style.cssText = `
            padding: 12px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            font-weight: bold;
            color: #c0b283;
            border-radius: 8px 8px 0 0;
        `;
        dropdown.appendChild(header);

        // Добавляем спиннер
        const spinner = document.createElement('div');
        spinner.className = 'bmz-poet-spinner';
        spinner.innerHTML = `
            <div style="
                width: 30px;
                height: 30px;
                border: 3px solid #f3f3f3;
                border-top: 3px solid #3498db;
                border-radius: 50%;
                animation: bmz-spin 1s linear infinite;
                margin: 20px auto 10px;
            "></div>
            <div style="text-align: center; color: #777; padding-bottom: 20px;">
                Загрузка треков...
            </div>
        `;
        dropdown.appendChild(spinner);

        // Показываем dropdown
        dropdown.style.display = 'block';
        dropdown.setAttribute('aria-hidden', 'false');

        // Позиционируем
        positionDropdown(link, dropdown);

        // AJAX запрос
        $.ajax({
            url: poetTracks.ajax_url,
            type: 'POST',
            data: {
                action: 'get_poet_tracks',
                security: poetTracks.nonce,
                poet_name: poetName,
                page: 1
            },
            success: function (response) {
                console.log('📥 AJAX ответ:', response);

                // Удаляем спиннер
                spinner.remove();

                if (response.success && response.data && response.data.tracks) {
                    if (response.data.tracks.length > 0) {
                        const tracksContainer = document.createElement('div');
                        tracksContainer.className = 'bmz-tracks-container';
                        tracksContainer.style.cssText = `
                            max-height: 300px;
                            overflow-y: auto;
                            padding: 10px 0;
                        `;

                        response.data.tracks.forEach((track, i) => {
                            const trackItem = document.createElement('div');
                            trackItem.className = 'bmz-track-item';
                            trackItem.style.cssText = `
                                padding: 0;
                                margin: 0;
                            `;

                            console.log(track);


                            const trackLink = document.createElement('a');
                            trackLink.href = track.audio;

                            //trackLink.href = track.poem_slug;

                            trackLink.textContent = track.track_name;
                            trackLink.className = 'bmz-track-link';
                            trackLink.setAttribute('role', 'menuitem');
                            trackLink.setAttribute('tabindex', '0');
                            trackLink.style.cssText = `
                                display: block;
                                padding: 10px 15px;
                                color: #333;
                                text-decoration: none;
                                transition: all 0.2s;
                                border-bottom: ${i < response.data.tracks.length - 1 ? '1px solid #f0f0f0' : 'none'};
                            `;

                            // События для трека
                            trackLink.addEventListener('mouseenter', function () {
                                this.style.background = '#f0f8ff';
                                this.style.color = '#0073aa';
                            });

                            trackLink.addEventListener('mouseleave', function () {
                                this.style.background = '';
                                this.style.color = '#333';
                            });

                            trackLink.addEventListener('focus', function () {
                                this.style.background = '#f0f8ff';
                                this.style.color = '#0073aa';
                                this.style.outline = '2px solid #0073aa';
                                this.style.outlineOffset = '-2px';
                            });

                            trackLink.addEventListener('blur', function () {
                                this.style.background = '';
                                this.style.color = '#333';
                                this.style.outline = 'none';
                            });

                            trackLink.addEventListener('keydown', function (e) {
                                if (e.key === 'Enter' || e.key === ' ') {
                                    e.preventDefault();
                                    window.location.href = this.href;
                                } else if (e.key === 'Escape') {
                                    dropdown.closest('li').querySelector('a').focus();
                                    hideDropdown(dropdown, link);
                                }
                            });

                            trackItem.appendChild(trackLink);
                            tracksContainer.appendChild(trackItem);
                        });

                        dropdown.appendChild(tracksContainer);

                        // Добавляем информацию о количестве
                        if (response.data.total > response.data.tracks.length) {
                            const footer = document.createElement('div');
                            footer.className = 'bmz-dropdown-footer';
                            footer.style.cssText = `
                                padding: 8px 15px;
                                background: #f8f9fa;
                                border-top: 1px solid #eee;
                                color: #666;
                                font-size: 12px;
                                text-align: center;
                                border-radius: 0 0 8px 8px;
                            `;
                            footer.textContent = `Показано ${response.data.tracks.length} из ${response.data.total} треков`;
                            dropdown.appendChild(footer);
                        }
                    } else {
                        const emptyMsg = document.createElement('div');
                        emptyMsg.className = 'bmz-empty-message';
                        emptyMsg.style.cssText = `
                            padding: 30px 20px;
                            text-align: center;
                            color: #666;
                            font-style: italic;
                        `;
                        emptyMsg.textContent = 'Треки не найдены';
                        dropdown.appendChild(emptyMsg);
                    }
                } else {
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'bmz-error-message';
                    errorMsg.style.cssText = `
                        padding: 30px 20px;
                        text-align: center;
                        color: #d00;
                    `;
                    errorMsg.textContent = 'Ошибка загрузки данных';
                    dropdown.appendChild(errorMsg);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('❌ AJAX ошибка:', textStatus, errorThrown);

                spinner.remove();

                const errorMsg = document.createElement('div');
                errorMsg.className = 'bmz-error-message';
                errorMsg.style.cssText = `
                    padding: 30px 20px;
                    text-align: center;
                    color: #d00;
                `;
                errorMsg.innerHTML = `
                    Ошибка загрузки<br>
                    <small>${textStatus}: ${errorThrown}</small>
                `;
                dropdown.appendChild(errorMsg);
            }
        });
    }

    function hideDropdown(dropdown, link) {
        dropdown.style.display = 'none';
        dropdown.setAttribute('aria-hidden', 'true');
        link.setAttribute('aria-expanded', 'false');
        isOpen = false;
    }

    function positionDropdown(link, dropdown) {
        const linkRect = link.getBoundingClientRect();
        const dropdownRect = dropdown.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        // По умолчанию показываем снизу
        dropdown.style.top = '100%';
        dropdown.style.bottom = 'auto';
        dropdown.style.left = '0';
        dropdown.style.right = 'auto';

        // Проверяем правый край
        if (linkRect.left + dropdown.offsetWidth > viewportWidth - 20) {
            dropdown.style.left = 'auto';
            dropdown.style.right = '0';
        }

        // Проверяем нижний край
        const spaceBelow = viewportHeight - linkRect.bottom - 10;
        if (spaceBelow < dropdown.offsetHeight && linkRect.top > dropdown.offsetHeight) {
            // Показываем сверху
            dropdown.style.top = 'auto';
            dropdown.style.bottom = '100%';
            dropdown.style.marginTop = '0';
            dropdown.style.marginBottom = '5px';
        }
    }

    // Добавляем стили анимации
    if (!document.querySelector('#bmz-poet-styles')) {
        const style = document.createElement('style');
        style.id = 'bmz-poet-styles';
        style.textContent = `
            @keyframes bmz-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .bmz-poet-tracks-dropdown {
                animation: bmz-fadeIn 0.2s ease-out;
            }
            
            @keyframes bmz-fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .bmz-track-link:hover {
                background: #f0f8ff !important;
                color: #0073aa !important;
            }

            .bmz-tracks-container {
                z-index:1000;
            }

            /* Кастомный скроллбар */
            .bmz-tracks-container::-webkit-scrollbar {
                width: 6px;
            }
            
            .bmz-tracks-container::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 3px;
            }
            
            .bmz-tracks-container::-webkit-scrollbar-thumb {
                background: #c1c1c1;
                border-radius: 3px;
            }
            
            .bmz-tracks-container::-webkit-scrollbar-thumb:hover {
                background: #a8a8a8;
            }
        `;
        document.head.appendChild(style);
    }

})(jQuery);