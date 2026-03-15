'use strict';
document.addEventListener('DOMContentLoaded', function () {
    const playlistContainer = document.querySelector('.audio-playlist-container');

    if (playlistContainer) {
        fetchAudioTracks();
    }

    function fetchAudioTracks() {
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_audio_tracks',
                security: '<?php echo wp_create_nonce("audio_tracks_nonce"); ?>'
            })
        })
            .then(response => response.json())
            .then(tracks => {
                renderAudioTracks(tracks);
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    function renderAudioTracks(tracks) {
        if (!tracks.length) {
            playlistContainer.innerHTML = '<p>Аудиофайлы не найдены.</p>';
            return;
        }

        let html = '';

        tracks.forEach(track => {
            html += `
            <div class="audio-track">
                <h3>${escapeHtml(track.title)}</h3>
                <p class="author">${escapeHtml(track.author)}</p>
                <audio controls>
                    <source src="${escapeHtml(track.file_path)}" type="audio/mpeg">
                    Ваш браузер не поддерживает элемент audio.
                </audio>
            </div>`;
        });

        playlistContainer.innerHTML = html;
    }

    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});