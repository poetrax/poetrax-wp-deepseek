/**
 * Модальное окно для стихов
 */
(function ($) {
    'use strict';

    class PoemModal {
        constructor() {
            this.modal = document.getElementById('poem-modal');
            this.titleEl = document.getElementById('modal-poem-title');
            this.authorEl = document.getElementById('modal-poem-author');
            this.textEl = document.getElementById('modal-poem-text');
            this.linkEl = document.getElementById('modal-poem-link');
            this.loader = this.modal.querySelector('.bm-modal__loader');

            this.init();
        }

        init() {
            this.bindEvents();
            this.initCloseHandlers();
        }

        bindEvents() {
            // Клик по кнопке "перо" в любой карточке трека
            $(document).on('click', '.track-card__action--poem', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const trackId = $btn.data('track-id');
                const poemId = $btn.data('poem-id');

                this.showPoem(trackId, poemId);
            });
        }

        initCloseHandlers() {
            // Закрытие по крестику
            this.modal.querySelectorAll('[data-modal-close]').forEach(el => {
                el.addEventListener('click', () => this.close());
            });

            // Закрытие по Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen()) {
                    this.close();
                }
            });

            // Закрытие по клику на оверлей
            this.modal.querySelector('.bm-modal__overlay').addEventListener('click', (e) => {
                if (e.target.classList.contains('bm-modal__overlay')) {
                    this.close();
                }
            });
        }

        showPoem(trackId, poemId) {
            this.showLoader();

            // Получаем данные стихотворения
            $.post(bm_ajax.ajaxurl, {
                action: 'bm_get_poem_data',
                nonce: bm_ajax.nonce,
                track_id: trackId,
                poem_id: poemId
            }, (response) => {
                this.hideLoader();

                if (response.success) {
                    this.renderPoem(response.data);
                    this.open();
                } else {
                    this.showError('Не удалось загрузить стихотворение');
                }
            }).fail(() => {
                this.hideLoader();
                this.showError('Ошибка соединения');
            });
        }

        renderPoem(data) {
            this.titleEl.textContent = data.title || 'Без названия';
            this.authorEl.textContent = data.author || '';
            this.textEl.innerHTML = data.text.replace(/\n/g, '<br>');
            this.linkEl.href = data.link || '#';
        }

        open() {
            this.modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        close() {
            this.modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        isOpen() {
            return this.modal.getAttribute('aria-hidden') === 'false';
        }

        showLoader() {
            this.loader.style.display = 'flex';
        }

        hideLoader() {
            this.loader.style.display = 'none';
        }

        showError(message) {
            // Можно добавить красивый тост
            alert(message);
        }
    }

    // Инициализация
    $(document).ready(() => {
        new PoemModal();
    });

})(jQuery);