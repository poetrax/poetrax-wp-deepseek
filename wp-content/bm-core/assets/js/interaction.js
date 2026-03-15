/**
 * Track Interactions Handler
 */
(function ($) {
    'use strict';

    const TrackInteractions = {

        init: function () {
            this.bindEvents();
            this.initPlayer();
        },

        bindEvents: function () {
            // Лайки и закладки
            $(document).on('click', '.track-card__action', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const trackId = $btn.data('track-id');
                const action = $btn.data('action');

                this.toggleInteraction(trackId, action, $btn);
            });

            // Воспроизведение
            $(document).on('click', '.track-card__play', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const trackId = $btn.data('track-id');
                const trackUrl = $btn.data('track-url');

                this.playTrack(trackId, trackUrl, $btn);
            });

            // Обновление статистики в реальном времени
            this.setupRealtimeUpdates();
        },

        toggleInteraction: function (trackId, action, $btn) {
            const $count = $btn.find('.action-count');
            const currentCount = parseInt($count.text());

            // Оптимистичное обновление UI
            if ($btn.hasClass('active')) {
                $btn.removeClass('active');
                $count.text(currentCount - 1);
            } else {
                $btn.addClass('active');
                $count.text(currentCount + 1);
            }

            $.post(bm_ajax.ajaxurl, {
                action: 'bm_toggle_interaction',
                nonce: bm_ajax.nonce,
                track_id: trackId,
                type: action
            }, (response) => {
                if (!response.success) {
                    // Откат при ошибке
                    if ($btn.hasClass('active')) {
                        $btn.removeClass('active');
                        $count.text(currentCount);
                    } else {
                        $btn.addClass('active');
                        $count.text(currentCount);
                    }

                    console.error('Interaction failed:', response.data.message);
                }
            }).fail(() => {
                // Откат при ошибке сети
                if ($btn.hasClass('active')) {
                    $btn.removeClass('active');
                    $count.text(currentCount);
                } else {
                    $btn.addClass('active');
                    $count.text(currentCount);
                }
            });
        },

        playTrack: function (trackId, trackUrl, $btn) {
            const $card = $btn.closest('.track-card');
            const $indicator = $card.find('.track-card__playing-indicator');
            const $playIcon = $btn.find('.play-icon');

            // Останавливаем другие треки
            $('.track-card__playing-indicator').hide();
            $('.track-card__play .play-icon').show();

            // Показываем индикатор воспроизведения
            $indicator.show();
            $playIcon.hide();

            // Записываем прослушивание
            $.post(bm_ajax.ajaxurl, {
                action: 'bm_record_play',
                nonce: bm_ajax.nonce,
                track_id: trackId
            });

            // Триггерим событие для плеера
            $(document).trigger('bm:play-track', [trackId, trackUrl, $card]);
        },

        setupRealtimeUpdates: function () {
            // Можно добавить WebSocket или SSE для real-time обновлений
            // Пока просто периодически обновляем статистику
            setInterval(() => {
                $('.track-card').each((i, card) => {
                    const $card = $(card);
                    const trackId = $card.data('track-id');

                    $.post(bm_ajax.ajaxurl, {
                        action: 'bm_get_track_stats',
                        nonce: bm_ajax.nonce,
                        track_id: trackId
                    }, (response) => {
                        if (response.success) {
                            $card.find('.track-card__action--like .action-count')
                                .text(response.data.likes);
                            $card.find('.track-card__action--bookmark .action-count')
                                .text(response.data.bookmarks);
                            $card.find('.plays-count')
                                .text(response.data.plays);
                        }
                    });
                });
            }, 30000); // Каждые 30 секунд
        }
    };

    $(document).ready(() => {
        TrackInteractions.init();
    });

})(jQuery);