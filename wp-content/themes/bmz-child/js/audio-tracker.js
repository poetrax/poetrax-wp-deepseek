'use strict';
document.addEventListener('DOMContentLoaded', function () {
    // Initialize all audio players on the page
    document.querySelectorAll('.audio-player-container').forEach(container => {
        const audioElement = container.querySelector('audio');
        const trackId = container.dataset.trackId;
        const likeButton = container.querySelector('.like-button');
        const likeCount = container.querySelector('.like-count');
        const playCount = container.querySelector('.play-count');
        const canvas = container.querySelector('.waveform');

        // Load initial stats
        loadStats(trackId, likeCount, playCount);

        // Track play events
        audioElement.addEventListener('play', function () {
            trackPlay(trackId);
        });

        // Track time update for play duration
        let playStartTime;
        audioElement.addEventListener('playing', function () {
            playStartTime = Date.now();
        });

        audioElement.addEventListener('pause', function () {
            if (playStartTime) {
                const duration = Math.round((Date.now() - playStartTime) / 1000);
                trackPlayDuration(trackId, duration);
                playStartTime = null;
            }
        });

        // Track like events
        likeButton.addEventListener('click', function () {
            trackLike(trackId, likeCount);
        });

        // Initialize waveform visualization
        initWaveform(audioElement, canvas);
    });
});

function loadStats(trackId, likeCountElement, playCountElement) {
    fetch(`${audioTracker.api_url}get-stats/${trackId}`, {
        method: 'GET',
        headers: {
            'X-WP-Nonce': audioTracker.nonce
        }
    })
        .then(response => response.json())
        .then(data => {
            likeCountElement.textContent = data.likes;
            playCountElement.textContent = data.plays;
        })
        .catch(error => console.error('Error loading stats:', error));
}

function trackPlay(trackId) {
    fetch(`${audioTracker.api_url}track-play`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': audioTracker.nonce
        },
        body: JSON.stringify({
            track_id: trackId
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update play count in UI
                const playCountElement = document.querySelector(`.audio-player-container[data-track-id="${trackId}"] .play-count`);
                if (playCountElement) {
                    playCountElement.textContent = parseInt(playCountElement.textContent) + 1;
                }
            }
        })
        .catch(error => console.error('Error tracking play:', error));
}

function trackPlayDuration(trackId, duration) {
    fetch(`${audioTracker.api_url}track-play`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': audioTracker.nonce
        },
        body: JSON.stringify({
            track_id: trackId,
            duration: duration
        })
    })
        .catch(error => console.error('Error tracking play duration:', error));
}

function trackLike(trackId, likeCountElement) {
    fetch(`${audioTracker.api_url}track-like`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': audioTracker.nonce
        },
        body: JSON.stringify({
            track_id: trackId
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update like count in UI
                likeCountElement.textContent = parseInt(likeCountElement.textContent) + 1;
                likeCountElement.parentElement.classList.add('liked');
            } else if (data.message === 'Already liked') {
                alert('You already liked this track!');
            }
        })
        .catch(error => console.error('Error tracking like:', error));
}

function initWaveform(audioElement, canvas) {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const analyser = audioContext.createAnalyser();
    const source = audioContext.createMediaElementSource(audioElement);

    source.connect(analyser);
    analyser.connect(audioContext.destination);

    analyser.fftSize = 256;
    const bufferLength = analyser.frequencyBinCount;
    const dataArray = new Uint8Array(bufferLength);

    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;

    function draw() {
        requestAnimationFrame(draw);

        analyser.getByteFrequencyData(dataArray);

        ctx.fillStyle = 'rgb(200, 200, 200)';
        ctx.fillRect(0, 0, width, height);

        const barWidth = (width / bufferLength) * 2.5;
        let x = 0;

        for (let i = 0; i < bufferLength; i++) {
            const barHeight = dataArray[i] / 2;

            ctx.fillStyle = `rgb(50, 50, ${barHeight + 100})`;
            ctx.fillRect(x, height - barHeight, barWidth, barHeight);

            x += barWidth + 1;
        }
    }

    draw();
}
