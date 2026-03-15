(function($) {
    'use strict';
    
    const BMTrackEditor = {
        
        init: function() {
            this.bindEvents();
            this.initUploader();
            this.initPoemSearch();
            this.loadInitialData();
        },
        
        bindEvents: function() {
            // Сохранение формы
            $('#bm-te-save-track').on('click', (e) => {
                e.preventDefault();
                this.saveTrack();
            });
            
            // Удаление трека
            $('.bm-te-delete').on('click', (e) => {
                e.preventDefault();
                const trackId = $(e.target).data('id');
                this.deleteTrack(trackId);
            });
            
            // Поиск стихов
            $('#bm-te-poem-search').on('input', (e) => {
                const query = e.target.value;
                if (query.length >= 3) {
                    this.searchPoems(query);
                }
            });
            
            // Фильтр по поэту
            $('#bm-te-poet-filter').on('change', (e) => {
                const poetId = e.target.value;
                this.filterByPoet(poetId);
            });
            
            // Вкладки
            $('.bm-te-tab').on('click', (e) => {
                e.preventDefault();
                const tabId = $(e.target).data('tab');
                this.switchTab(tabId);
            });
        },
        
        initUploader: function() {
            const uploadArea = $('#bm-te-upload-area');
            
            uploadArea.on('dragover', (e) => {
                e.preventDefault();
                uploadArea.addClass('dragover');
            });
            
            uploadArea.on('dragleave', () => {
                uploadArea.removeClass('dragover');
            });
            
            uploadArea.on('drop', (e) => {
                e.preventDefault();
                uploadArea.removeClass('dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    this.uploadFile(files[0]);
                }
            });
            
            $('#bm-te-upload-button').on('click', () => {
                $('#bm-te-file-input').click();
            });
            
            $('#bm-te-file-input').on('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    this.uploadFile(file);
                }
            });
        },
        
        initPoemSearch: function() {
            let timeout;
            
            $('#bm-te-poem-search').on('input', (e) => {
                clearTimeout(timeout);
                
                const query = e.target.value;
                if (query.length < 3) {
                    $('.bm-te-poem-results').empty();
                    return;
                }
                
                timeout = setTimeout(() => {
                    this.searchPoems(query);
                }, 300);
            });
        },
        
        loadInitialData: function() {
            // Загрузка списка поэтов
            $.post(ajaxurl, {
                action: 'bm_get_poets',
                nonce: bmTE.nonce
            }, (response) => {
                if (response.success) {
                    this.renderPoetSelect(response.data);
                }
            });
            
            // Загрузка статистики
            if ($('#bm-te-stats').length) {
                this.loadStats();
            }
        },
        
        saveTrack: function() {
            const formData = $('#bm-track-form').serializeArray();
            const data = {};
            
            $.each(formData, (i, field) => {
                data[field.name] = field.value;
            });
            
            data.action = 'bm_save_track';
            data.nonce = bmTE.nonce;
            
            $('#bm-te-save-track').prop('disabled', true).text('Сохранение...');
            
            $.post(ajaxurl, data, (response) => {
                $('#bm-te-save-track').prop('disabled', false).text('Сохранить');
                
                if (response.success) {
                    this.showNotice('success', response.data.message);
                    
                    // Обновляем URL если это новый трек
                    if (!data.track_id && response.data.id) {
                        history.pushState({}, '', `?page=bm-track-editor&id=${response.data.id}`);
                    }
                } else {
                    this.showNotice('error', response.data.message || 'Ошибка сохранения');
                }
            }).fail(() => {
                $('#bm-te-save-track').prop('disabled', false).text('Сохранить');
                this.showNotice('error', 'Ошибка соединения');
            });
        },
        
        deleteTrack: function(trackId) {
            if (!confirm(bmTE.strings.confirm_delete)) return;
            
            $.post(ajaxurl, {
                action: 'bm_delete_track',
                nonce: bmTE.nonce,
                track_id: trackId
            }, (response) => {
                if (response.success) {
                    $(`.bm-te-track-row-${trackId}`).fadeOut(() => {
                        $(this).remove();
                        this.showNotice('success', 'Трек удален');
                    });
                } else {
                    this.showNotice('error', 'Ошибка удаления');
                }
            });
        },
        
        uploadFile: function(file) {
            const formData = new FormData();
            formData.append('action', 'bm_upload_audio');
            formData.append('nonce', bmTE.nonce);
            formData.append('audio_file', file);
            
            $('.bm-te-upload-area').addClass('uploading');
            $('.bm-te-upload-progress').show();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    $('.bm-te-upload-area').removeClass('uploading');
                    $('.bm-te-upload-progress').hide();
                    
                    if (response.success) {
                        this.updateFileInfo(response.data);
                        this.showNotice('success', 'Файл загружен');
                    } else {
                        this.showNotice('error', response.data.message || 'Ошибка загрузки');
                    }
                },
                xhr: () => {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            const percent = (e.loaded / e.total) * 100;
                            $('.bm-te-upload-progress-bar').css('width', percent + '%');
                        }
                    });
                    return xhr;
                }
            });
        },
        
        searchPoems: function(query) {
            $.post(ajaxurl, {
                action: 'bm_search_poems',
                nonce: bmTE.nonce,
                query: query,
                poet_id: $('#bm-te-poet-id').val()
            }, (response) => {
                if (response.success) {
                    this.renderPoemResults(response.data);
                }
            });
        },
        
        loadStats: function() {
            $.post(ajaxurl, {
                action: 'bm_get_stats',
                nonce: bmTE.nonce
            }, (response) => {
                if (response.success) {
                    this.renderStats(response.data);
                }
            });
        },
        
        renderPoetSelect: function(poets) {
            const select = $('#bm-te-poet-select');
            select.empty();
            select.append('<option value="">Выберите поэта</option>');
            
            $.each(poets, (i, poet) => {
                select.append(`<option value="${poet.id}">${poet.short_name}</option>`);
            });
        },
        
        renderPoemResults: function(poems) {
            const container = $('.bm-te-poem-results');
            container.empty();
            
            if (poems.length === 0) {
                container.html('<p class="bm-te-no-results">Ничего не найдено</p>');
                return;
            }
            
            const list = $('<div class="bm-te-poem-list"></div>');
            
            $.each(poems, (i, poem) => {
                list.append(`
                    <div class="bm-te-poem-item" data-id="${poem.id}">
                        <strong>${poem.name}</strong>
                        <span>${poem.poet_name}</span>
                    </div>
                `);
            });
            
            container.html(list);
            
            $('.bm-te-poem-item').on('click', function() {
                const poemId = $(this).data('id');
                const poemName = $(this).find('strong').text();
                
                $('#bm-te-poem-id').val(poemId);
                $('#bm-te-poem-name').val(poemName);
                $('.bm-te-poem-results').empty();
            });
        },
        
        renderStats: function(stats) {
            $('#bm-te-total-tracks').text(stats.total_tracks);
            $('#bm-te-total-poems').text(stats.total_poems);
            $('#bm-te-total-poets').text(stats.total_poets);
            $('#bm-te-total-plays').text(stats.total_plays);
            
            // Рендерим последние треки
            const recentTracks = $('#bm-te-recent-tracks');
            recentTracks.empty();
            
            $.each(stats.recent_tracks, (i, track) => {
                recentTracks.append(`
                    <div class="bm-te-recent-track">
                        <span class="track-name">${track.track_name}</span>
                        <span class="track-poet">${track.poet_name || ''}</span>
                        <span class="track-date">${track.created_at}</span>
                    </div>
                `);
            });
        },
        
        updateFileInfo: function(fileData) {
            $('.bm-te-file-info').html(`
                <div class="bm-te-file-details">
                    <span class="file-name">${fileData.name}</span>
                    <span class="file-size">${this.formatFileSize(fileData.size)}</span>
                </div>
                <button type="button" class="bm-te-button bm-te-button-small bm-te-remove-file">Удалить</button>
            `);
            
            $('#bm-te-file-path').val(fileData.path);
            
            $('.bm-te-remove-file').on('click', () => {
                this.removeFile();
            });
        },
        
        removeFile: function() {
            $('.bm-te-file-info').empty();
            $('#bm-te-file-path').val('');
            $('#bm-te-file-input').val('');
            this.showNotice('info', 'Файл удален');
        },
        
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        switchTab: function(tabId) {
            $('.bm-te-tab').removeClass('active');
            $(`.bm-te-tab[data-tab="${tabId}"]`).addClass('active');
            
            $('.bm-te-tab-pane').removeClass('active');
            $(`#bm-te-tab-${tabId}`).addClass('active');
        },
        
        filterByPoet: function(poetId) {
            if (poetId) {
                window.location.href = `?page=bm-tracks&poet_id=${poetId}`;
            } else {
                window.location.href = '?page=bm-tracks';
            }
        },
        
        showNotice: function(type, message) {
            const notice = $(`
                <div class="bm-te-notice bm-te-notice-${type} fade">
                    <p>${message}</p>
                </div>
            `);
            
            $('.bm-te-header').after(notice);
            
            setTimeout(() => {
                notice.fadeOut(() => {
                    notice.remove();
                });
            }, 3000);
        }
    };
    
    $(document).ready(() => {
        BMTrackEditor.init();
    });
    
})(jQuery);