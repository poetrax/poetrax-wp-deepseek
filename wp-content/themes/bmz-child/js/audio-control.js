'use strict';
// Основная функция инициализации
document.addEventListener('DOMContentLoaded', function () {

    // Основные элементы DOM, которые будут использоваться
    const isIOS = /iphone|ipad|macintosh/i.test(navigator.userAgent.toLowerCase());
    const audios = document.querySelectorAll('audio');

    setTimeout(function () {
        // Функции инициализации
        initAudioPlayers(audios, isIOS);
        replaceTextContent();
        hideAndRenameElements();
        setupEventListeners();
    }, 500);

});


/**
 * Инициализация аудиоплееров
 */
function initAudioPlayers(audios, isIOS) {
    if (!audios.length) return;

    // Останавливаем все аудио при загрузке
    audios.forEach(audio => {
        audio.pause();
        audio.setAttribute('controlslist', 'nodownload noplaybackrate');
        audio.preload = 'auto';
    });

    // Для iOS отдельная логика из-за ограничений автовоспроизведения
    if (isIOS) {
        setupIOSAudioHandlers(audios);
    } else {
        setupDesktopAudioHandlers(audios);
    }

    drawTrackImg();

    new AudioTabManager();
   
}

function drawTrackImg() {
    try {
        const figures = document.querySelectorAll('figure.wp-block-audio');
        if (!figures) return;

        figures.forEach(figure => {
            const audio = figure.querySelector('audio');
          
            audio.autoplay = true;
            audio.onplay = pauseOtherAudios(this);

            // Попытка воспроизведения с обработкой ошибок
            audio.play().catch(e => {
                console.log('Audio playback failed:', e.message);
            });

            // Создание URL изображения
            let imageUrl = audio.src
                .replace('audio/mp3', 'img/jpeg')
                .replace('.mp3', '-50x50.jpeg');

            // Создание изображения
            const img = document.createElement('img');
            img.src = imageUrl;
            img.alt = '';
            img.dataset.smallUrl = imageUrl.replace('-180x180.jpeg', '-50x50.jpeg');
            img.dataset.largeUrl = imageUrl.replace('-50x50.jpeg', '-180x180.jpeg');

            // Обработчики событий
            img.addEventListener('mouseenter', () => resizeImg(this, '180', figure, audio));
            img.addEventListener('mouseleave', () => resizeImg(this, '50', figure, audio));

            /*
            img.addEventListener('mouseenter', function () {
                this.src = this.dataset.largeUrl;
                figure.parentElement.appendChild(this);
            });
            img.addEventListener('mouseleave', function () {
                this.src = this.dataset.smallUrl;
                figure.insertBefore(this, audio);
            });
            */

            // Вставка изображения перед аудио элементом
            figure.insertBefore(img, audio);
         });
     
    }
    catch (e) {
        console.error('Error in drawTrackImg:', e.message, e.stack);
    }
}

function resizeImg(img, size, figure, audio) {
    if (!img || !img.dataset) return;
    const newSrc = size === '180' ? img.dataset.largeUrl : img.dataset.smallUrl;
    img.src = newSrc;
    if (size === '180') {
        figure.parentElement.appendChild(img);
    }
    else {
        figure.insertBefore(img, audio);
    }
}

/**
 * Настройка обработчиков для iOS
 */
function setupIOSAudioHandlers(audios) {
    const audioStates = new Map();

    // Инициализируем состояния для каждого аудио
    audios.forEach(audio => {
        audioStates.set(audio, { loaded: false });

        audio.addEventListener('canplay', () => {
            audioStates.set(audio, { loaded: true });
        }, { once: true });
    });

    // Обработчик клика по документу для iOS
    document.addEventListener('click', function iosAudioClickHandler(e) {
        const clickedAudio = e.target.closest('audio');

        if (clickedAudio && audios.includes(clickedAudio)) {
            const state = audioStates.get(clickedAudio);

            if (!state.loaded) {
                clickedAudio.load();
            }

            pauseOtherAudios(clickedAudio);
            clickedAudio.play().catch(console.error);
        }
    }, { passive: true });
}

/**
 * Настройка обработчиков для десктопа
 */
function setupDesktopAudioHandlers(audios) {
    audios.forEach(audio => {
        audio.addEventListener('click', function audioClickHandler() {
            pauseOtherAudios(audio);
            audio.play().catch(console.error);
        }, { once: false }); 
    });
}

/**
 * Останавливает все аудио кроме указанного
 */
function pauseOtherAudios(currentAudio) {
    document.querySelectorAll('audio').forEach(audio => {
        if (audio !== currentAudio && !audio.paused) {
            audio.pause();
        }
    });
}

/**
 * Замена текстового контента на странице
 */
function replaceTextContent() {
    // Замена в заголовках h2
    document.querySelectorAll('h2.entry-title.section-title').forEach(h2 => {
        if (h2.textContent.includes('Рубрик')) {
            h2.innerHTML = h2.innerHTML
                .replace('Рубрика', 'Автор')
                .replace('Рубрики', 'Авторы');
        }
    });

    // Замена в текстовых узлах
    const walker = document.createTreeWalker(
        document.body,
        NodeFilter.SHOW_TEXT,
        null,
        false
    );

    let node;
    const replacements = [
        ['Предыдущая статья', 'Предыдущий трек'],
        ['Следующая статья', 'Следующий трек']
    ];

    while ((node = walker.nextNode())) {
        let text = node.textContent;
        let replaced = false;

        replacements.forEach(([search, replace]) => {
            if (text.includes(search)) {
                text = text.replace(new RegExp(search, 'g'), replace);
                replaced = true;
            }
        });

        if (replaced) {
            node.textContent = text;
        }
    }
}

/**
 * Скрытие и переименование элементов
 */
function hideAndRenameElements() {
    // Переименование меню
    let menuToggle = document.querySelector('#menu-toggle span');
    if (menuToggle) {
        menuToggle.textContent = 'Автора!';
    }

    // Переименование кнопки загрузки
    let loadButton = document.querySelector('#infinite-handle button');
    if (loadButton) {
        setTimeout(function () {
            loadButton.textContent = 'Ещё';
        }, 200);
        
    }

    // Переименование навигации треков
    document.querySelectorAll('.nav-subtitle').forEach(track => {
        if (track.textContent === 'Предыдущая статья') {
            track.textContent = 'Предыдущий трек';
        }
        if (track.textContent === 'Следующая статья') {
            track.textContent = 'Следующий трек';
        }
    });

    // Скрытие элементов в зависимости от URL
    let urlSlug = window.location.pathname.split('/')[1];

    if (urlSlug === 'lichnyj-zal') {
        const lzLink = document.getElementById('lichnyj-zal');
        if (lzLink) lzLink.style.display = 'none';
    }

    // Скрытие заголовка сайта
    let siteTitle = document.querySelector('.site-title');
    if (siteTitle) siteTitle.style.display = 'none';
}

/**
 * Настройка всех обработчиков событий
 */
function setupEventListeners() {
    setupDonateToggle();
    setupCommentForm();
    //setupHeaderMenu();
}

/**
 * Настройка переключения формы доната
 */
function setupDonateToggle() {
    const donateTitle = document.getElementById('donate-title');
    const donateForm = document.querySelector('.yoomoney-payment-form');

    if (donateTitle && donateForm) {
        donateTitle.addEventListener('click', () => {
            donateForm.style.display =
                donateForm.style.display === 'none' ? '' : 'none';
        });
    }
}

/**
 * Настройка формы комментариев
 */
function setupCommentForm() {
    const respond = document.getElementById('respond');
    if (!respond) return;

    // Настройка текстового поля
    const commentField = respond.querySelector('#comment');
    if (commentField) {
        commentField.setAttribute('rows', 3);
        commentField.setAttribute('cols', 45);
        commentField.setAttribute('maxlength', 255);
    }

    // Скрытие ненужных элементов
    const elementsToHide = [
        respond.querySelector('#reply-title small'),
        respond.querySelector('.logged-in-as'),
        respond.querySelector('.comment-notes')
    ];

    elementsToHide.forEach(element => {
        if (element) element.style.display = 'none';
    });
}


/**
 * Настройка меню заголовка сайта
 */
function setupHeaderMenu() {
    const siteHeaderMenu = document.getElementById('site-header-menu');
    const doInvite = document.getElementById('do-invite');

    if (siteHeaderMenu && doInvite) {
        siteHeaderMenu.innerHTML = doInvite.innerHTML;
    }
}

/**
 * Класс для управления аудио между вкладками (исправленная версия)
 */
class AudioTabManager {
    constructor() {
        this.channel = null;
        this.init();
    }

    init() {
        // Проверяем поддержку BroadcastChannel
        if (!('BroadcastChannel' in window)) {
            console.warn('BroadcastChannel не поддерживается');
            return;
        }

        this.channel = new BroadcastChannel('audio-control');

        // События видимости страницы
        document.addEventListener('visibilitychange',
            this.handleVisibilityChange.bind(this));

        // Обработчик сообщений от других вкладок
        this.channel.addEventListener('message',
            this.handleChannelMessage.bind(this));

        // Отслеживание воспроизведения
        document.addEventListener('play',
            this.handleMediaPlay.bind(this),
            { capture: true });
    }

    handleVisibilityChange() {
        if (document.hidden) {
            this.pauseAllMedia();
        }
    }

    handleChannelMessage(event) {
        if (event.data.type === 'pauseAudio') {
            this.pauseAllMedia();
        }
    }

    handleMediaPlay(event) {
        const media = event.target;
        if (media.tagName === 'AUDIO' && !event.isTrusted) {
            return; // Игнорируем программное воспроизведение
        }

        if (media.tagName === 'AUDIO') {
            this.channel.postMessage({
                type: 'pauseAudio',
                timestamp: Date.now(),
                source: window.location.href
            });
        }
    }

    pauseAllMedia() {
        document.querySelectorAll('audio').forEach(audio => {
            if (!audio.paused) {
                audio.pause();
            }
        });
    }

    // Метод для очистки
    destroy() {
        if (this.channel) {
            this.channel.close();
        }
        document.removeEventListener('visibilitychange',
            this.handleVisibilityChange.bind(this));
        document.removeEventListener('play',
            this.handleMediaPlay.bind(this));
    }
}

/**
 * Анимация картинки на странице трека
 */
(function ($) {
    'use strict';

    class TrackCoverAnimation {
        constructor() {
            this.cover = document.getElementById('track-cover');
            this.coverImage = document.getElementById('track-cover-image');
            this.player = document.getElementById('track-player');
            this.isUnderPlayer = false;
            this.timeout = null;

            this.init();
        }

        init() {
            if (!this.cover) return;

            this.bindEvents();
        }

        bindEvents() {
            // Наведение на картинку
            this.cover.addEventListener('mouseenter', () => {
                if (this.isUnderPlayer) {
                    this.moveToOriginal();
                }
            });

            // Уход мыши с картинки
            this.cover.addEventListener('mouseleave', () => {
                if (!this.isUnderPlayer) {
                    this.timeout = setTimeout(() => {
                        this.moveUnderPlayer();
                    }, 1000); // 1 секунда бездействия
                }
            });

            // Отмена таймера если мышь вернулась
            this.cover.addEventListener('mouseenter', () => {
                if (this.timeout) {
                    clearTimeout(this.timeout);
                }
            });

            // Клик по картинке
            this.cover.addEventListener('click', () => {
                if (this.isUnderPlayer) {
                    this.moveToOriginal();
                } else {
                    this.moveUnderPlayer();
                }
            });

            // Прокрутка страницы
            window.addEventListener('scroll', () => {
                this.checkPosition();
            });
        }

        moveUnderPlayer() {
            if (this.isUnderPlayer) return;

            this.cover.classList.add('under-player');
            this.isUnderPlayer = true;

            // Добавляем класс для плавного возврата
            setTimeout(() => {
                this.cover.classList.add('return');
            }, 300);

            // Триггерим событие
            $(document).trigger('track:cover:under', [this.cover]);
        }

        moveToOriginal() {
            if (!this.isUnderPlayer) return;

            this.cover.classList.remove('under-player');
            this.isUnderPlayer = false;

            setTimeout(() => {
                this.cover.classList.remove('return');
            }, 500);

            $(document).trigger('track:cover:original', [this.cover]);
        }

        checkPosition() {
            // Возвращаем картинку при прокрутке вниз
            if (window.scrollY > 100 && this.isUnderPlayer) {
                this.moveToOriginal();
            }
        }
    }

    // Инициализация
    $(document).ready(() => {
        new TrackCoverAnimation();
    });

})(jQuery);