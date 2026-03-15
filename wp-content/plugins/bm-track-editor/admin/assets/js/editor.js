(function ($) {
    'use strict';

    const BMEditor = {

        trackId: $('#track_id').val(),

        init: function () {
            this.bindEvents();
            this.initFileUploader();
            this.initPoemSearch();
            this.initMusicDetails();

            if (this.trackId) {
                this.loadTrackData();
            }
        },

        bindEvents: function () {
            // Сохранение
            $('.bm-save-track').on('click', () => this.saveTrack());

            // Удаление
            $('.bm-delete-track').on('click', () => this.deleteTrack());

            // Предпросмотр
            $('.bm-preview-track').on('click', () => this.previewTrack());

            // Генерация slug из названия
            $('#track_name').on('blur', () => this.generateSlug());
        },

        initFileUploader: function () {
            const frame = wp.media({
                title: 'Выберите аудиофайл',
                library: { type: 'audio' },
                multiple: false
            });

            $('.bm-upload-audio').on('click', () => {
                frame.open();
            });

            frame.on('select', () => {
                const attachment = frame.state().get('selection').first().toJSON();
                this.uploadFile(attachment.id);
            });

            $('.bm-remove-file').on('click', function () {
                $('#track_file_id, #track_file_path').val('');
                $('.bm-file-preview').empty();
            });
        },

        uploadFile: function (attachmentId) {
            const data = {
                action: 'bm_upload_audio',
                nonce: bmEditor.nonce,
                attachment_id: attachmentId
            };

            $.post(bmEditor.ajaxurl, data, (response) => {
                if (response.success) {
                    $('#track_file_id').val(response.data.file_id);
                    $('#track_file_path').val(response.data.file_path);

                    $('.bm-file-preview').html(`
                        <div class="bm-file-info">
                            <span class="bm-file-name">${response.data.file_name}</span>
                            <button type="button" class="button bm-remove-file">Удалить</button>
                        </div>
                    `);

                    $('#track_duration').val(response.data.duration);
                    $('#track_file_size').val(response.data.file_size);
                }
            });
        },

        initPoemSearch: function () {
            let searchTimeout;

            $('#poem_search').on('input', () => {
                clearTimeout(searchTimeout);

                const query = $('#poem_search').val();
                if (query.length < 3) return;

                searchTimeout = setTimeout(() => {
                    this.searchPoems(query);
                }, 300);
            });

            $(document).on('click', '.bm-poem-result-item', function () {
                const poemId = $(this).data('id');
                const poemName = $(this).data('name');
                const poetName = $(this).data('poet');

                $('#poem_id').val(poemId);
                $('#poem_search').val('');
                $('.bm-poem-results').hide();

                $('.bm-selected-poem').remove();
                $('.bm-poem-selector').append(`
                    <div class="bm-selected-poem">
                        <strong>${poemName}</strong>
                        <span>${poetName}</span>
                        <button type="button" class="button bm-remove-poem">Убрать</button>
                    </div>
                `);
            });

            $(document).on('click', '.bm-remove-poem', function () {
                $('#poem_id').val('');
                $(this).closest('.bm-selected-poem').remove();
            });
        },

        searchPoems: function (query) {
            const data = {
                action: 'bm_search_poems',
                query: query,
                poet_id: $('#poet_id').val()
            };

            $.post(bmEditor.ajaxurl, data, (response) => {
                if (response.success) {
                    let html = '';
                    response.data.forEach(poem => {
                        html += `
                            <div class="bm-poem-result-item" 
                                 data-id="${poem.id}" 
                                 data-name="${poem.name}" 
                                 data-poet="${poem.poet_name}">
                                <strong>${poem.name}</strong>
                                <span>${poem.poet_name}</span>
                                <small>${poem.text}</small>
                            </div>
                        `;
                    });

                    $('.bm-poem-results').html(html).show();
                }
            });
        },

        initMusicDetails: function () {
            // Динамическая загрузка стилей при выборе жанра
            $('#genre_id').on('change', function () {
                const genreId = $(this).val();
                if (genreId) {
                    // Загрузить стили для жанра
                }
            });
        },

        saveTrack: function () {
            const data = {
                id: this.trackId,
                track_name: $('#track_name').val(),
                track_slug: $('#track_slug').val(),
                caption: $('#caption').val(),
                poet_id: $('#poet_id').val(),
                poem_id: $('#poem_id').val(),
                mood_id: $('#mood_id').val(),
                theme_id: $('#theme_id').val(),
                temp_id: $('#temp_id').val(),
                genre_id: $('#genre_id').val(),
                performance_type: $('#performance_type').val(),
                voice_gender: $('#voice_gender').val(),
                is_payable: $('#is_payable').is(':checked') ? 1 : 0,
                is_approved: $('#is_approved').is(':checked') ? 1 : 0,
                is_active: $('#is_active').is(':checked') ? 1 : 0,
                is_show_img: $('#is_show_img').is(':checked') ? 1 : 0,
                age_restriction: $('#age_restriction').val(),
                file_id: $('#track_file_id').val(),
                track_file_path: $('#track_file_path').val(),
                music_details: {
                    bpm: $('#bpm').val(),
                    tonality_note: $('#tonality_note').val(),
                    tonality_mood: $('#tonality_mood').val(),
                    voice_group: $('#voice_group').val(),
                    instrument_ids: $('input[name="music_details[instrument_ids][]"]:checked').map(function () {
                        return $(this).val();
                    }).get()
                }
            };

            const request = {
                action: 'bm_save_track',
                nonce: bmEditor.nonce,
                data: JSON.stringify(data)
            };

            $('.bm-save-track').prop('disabled', true).text('Сохранение...');

            $.post(bmEditor.ajaxurl, request, (response) => {
                $('.bm-save-track').prop('disabled', false).text('Сохранить');

                if (response.success) {
                    if (!this.trackId) {
                        // Перенаправление на редактирование нового трека
                        window.location.href = `?page=bm-track-editor&id=${response.data.id}`;
                    }

                    this.showNotification('success', bmEditor.strings.save_success);
                } else {
                    this.showNotification('error', response.data.message || bmEditor.strings.save_error);
                }
            });
        },

        deleteTrack: function () {
            if (!confirm(bmEditor.strings.confirm_delete)) return;

            // Логика удаления
        },

        previewTrack: function () {
            const trackId = this.trackId;
            if (trackId) {
                window.open(`/track/${trackId}`, '_blank');
            }
        },

        generateSlug: function () {
            const title = $('#track_name').val();
            if (title && !$('#track_slug').val()) {
                const slug = title.toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/--+/g, '-')
                    .trim();

                $('#track_slug').val(slug);
            }
        },

        loadTrackData: function () {
            $.post(bmEditor.ajaxurl, {
                action: 'bm_get_track_data',
                track_id: this.trackId
            }, (response) => {
                if (response.success && response.data.music_details) {
                    this.fillMusicDetails(response.data.music_details);
                }
            });
        },

        fillMusicDetails: function (details) {
            $('#bpm').val(details.bpm || 100);
            $('#tonality_note').val(details.tonality_note || 'C');
            $('#tonality_mood').val(details.tonality_mood || 'major');
            $('#voice_group').val(details.voice_group || 'solo');

            if (details.instrument_ids) {
                const instruments = details.instrument_ids.split(',');
                $('input[name="music_details[instrument_ids][]"]').each(function () {
                    if (instruments.includes($(this).val())) {
                        $(this).prop('checked', true);
                    }
                });
            }
        },

        showNotification: function (type, message) {
            const notification = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                </div>
            `);

            $('.bm-track-editor h1').after(notification);

            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 3000);
        }
    };

    $(document).ready(() => {
        BMEditor.init();
    });

})(jQuery);