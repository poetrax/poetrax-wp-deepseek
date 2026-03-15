/**
 * BM Track Editor - Comments System
 */
(function ($) {
    'use strict';

    const BMComments = {

        init: function () {
            this.bindEvents();
            this.initReplyForms();
        },

        bindEvents: function () {
            // Отправка формы комментария
            $(document).on('submit', '.bm-comment-form', (e) => {
                e.preventDefault();
                this.submitComment(e.target);
            });

            // Кнопка "Ответить"
            $(document).on('click', '.bm-comment-reply', (e) => {
                e.preventDefault();
                const commentId = $(e.target).data('id');
                this.showReplyForm(commentId);
            });

            // Кнопка "Отмена" в форме ответа
            $(document).on('click', '.bm-cancel-reply', () => {
                this.hideReplyForm();
            });

            // Лайк комментария
            $(document).on('click', '.bm-comment-like', (e) => {
                e.preventDefault();
                const commentId = $(e.target).data('id');
                this.likeComment(commentId);
            });

            // Бесконечная загрузка
            $(window).on('scroll', () => {
                this.checkInfiniteScroll();
            });
        },

        initReplyForms: function () {
            // Инициализация всех форм
            $('.bm-comment-form').each(function () {
                const form = $(this);
                const trackId = form.data('track-id');

                // Загружаем сохранённые данные пользователя
                this.loadUserData(form);
            }.bind(this));
        },

        submitComment: function (form) {
            const $form = $(form);
            const $button = $form.find('[type="submit"]');
            const $message = $form.find('.bm-comment-message');
            const formData = new FormData(form);

            // Блокируем кнопку
            $button.prop('disabled', true).text('Отправка...');

            // Добавляем nonce
            formData.append('action', 'bm_submit_comment');
            formData.append('nonce', bm_comments.nonce);

            // AJAX запрос
            $.ajax({
                url: bm_comments.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        // Обновляем дерево комментариев
                        this.updateCommentTree(response.data.html);

                        // Очищаем форму
                        $form[0].reset();

                        // Показываем успех
                        this.showMessage($message, 'success', response.data.message);

                        // Сохраняем данные пользователя
                        this.saveUserData(formData);

                    } else {
                        this.showMessage($message, 'error', response.data.message);
                    }
                },
                error: () => {
                    this.showMessage($message, 'error', 'Ошибка соединения');
                },
                complete: () => {
                    $button.prop('disabled', false).text('Отправить');
                }
            });
        },

        showReplyForm: function (commentId) {
            // Скрываем другие формы ответа
            this.hideReplyForm();

            // Клонируем основную форму
            const $mainForm = $('.bm-comment-form-main');
            const $replyForm = $mainForm.clone();

            // Модифицируем для ответа
            $replyForm
                .addClass('bm-comment-form-reply')
                .attr('data-parent', commentId)
                .find('.bm-comment-parent')
                .val(commentId);

            // Добавляем кнопку отмены
            $replyForm.append(`
                <div class="bm-form-actions">
                    <button type="button" class="bm-cancel-reply button">Отмена</button>
                </div>
            `);

            // Вставляем после комментрия
            $(`#comment-${commentId}`).after($replyForm);

            // Скроллим к форме
            $('html, body').animate({
                scrollTop: $replyForm.offset().top - 100
            }, 300);
        },

        hideReplyForm: function () {
            $('.bm-comment-form-reply').remove();
        },

        likeComment: function (commentId) {
            const $button = $(`.bm-comment-like[data-id="${commentId}"]`);
            const $count = $button.find('.like-count');

            $.post(bm_comments.ajaxurl, {
                action: 'bm_like_comment',
                nonce: bm_comments.nonce,
                comment_id: commentId
            }, (response) => {
                if (response.success) {
                    $count.text(response.data.likes);
                    $button.addClass('liked');
                }
            });
        },

        updateCommentTree: function (html) {
            $('.bm-comments-tree').html(html);
            this.initReplyForms();
        },

        showMessage: function ($container, type, message) {
            $container
                .removeClass('success error')
                .addClass(type)
                .html(message)
                .fadeIn();

            setTimeout(() => {
                $container.fadeOut();
            }, 3000);
        },

        checkInfiniteScroll: function () {
            const $trigger = $('.bm-comments-load-more');
            if (!$trigger.length) return;

            const triggerOffset = $trigger.offset().top;
            const windowBottom = $(window).scrollTop() + $(window).height();

            if (windowBottom > triggerOffset - 200 && !$trigger.hasClass('loading')) {
                this.loadMoreComments();
            }
        },

        loadMoreComments: function () {
            const $trigger = $('.bm-comments-load-more');
            const page = parseInt($trigger.data('page')) + 1;
            const trackId = $trigger.data('track-id');

            $trigger.addClass('loading').text('Загрузка...');

            $.post(bm_comments.ajaxurl, {
                action: 'bm_load_more_comments',
                nonce: bm_comments.nonce,
                track_id: trackId,
                page: page
            }, (response) => {
                if (response.success) {
                    $('.bm-comments-tree').append(response.data.html);

                    if (response.data.has_more) {
                        $trigger
                            .data('page', page)
                            .removeClass('loading')
                            .text('Загрузить ещё');
                    } else {
                        $trigger.remove();
                    }
                }
            });
        },

        saveUserData: function (formData) {
            const userData = {
                author: formData.get('author'),
                email: formData.get('email'),
                url: formData.get('url')
            };

            localStorage.setItem('bm_comment_user', JSON.stringify(userData));
        },

        loadUserData: function ($form) {
            const saved = localStorage.getItem('bm_comment_user');
            if (saved) {
                try {
                    const userData = JSON.parse(saved);
                    $form.find('[name="author"]').val(userData.author);
                    $form.find('[name="email"]').val(userData.email);
                    $form.find('[name="url"]').val(userData.url);
                } catch (e) { }
            }
        }
    };

    // Инициализация
    $(document).ready(() => {
        BMComments.init();
    });

})(jQuery);