class AudioTracker {
    constructor() {
        this.audioElements = document.querySelectorAll('audio[data-track-id]');
        this.trackingData = new Map();
        this.reportInterval = 10000; // Отправлять данные каждые 10 секунд
        this.minDuration = 5000; // Минимальная длительность для сохранения (5 секунд)
        this.init();
    }

    init() {
        if (!this.audioElements.length) return;

        this.audioElements.forEach(audio => {
            this.setupAudioTracking(audio);
        });

        // Отправляем данные каждые 10 секунд
        setInterval(() => this.sendCollectedData(), this.reportInterval);

        // Отправляем данные при уходе со страницы
        window.addEventListener('beforeunload', () => this.sendCollectedData(true));
    }

    setupAudioTracking(audioElement) {
        const trackId = audioElement.dataset.trackId;
        let currentSession = {
            startTime: null,
            totalTime: 0,
            isPlaying: false
        };

        this.trackingData.set(trackId, currentSession);

        // Начало воспроизведения
        audioElement.addEventListener('play', () => {
            currentSession.startTime = Date.now();
            currentSession.isPlaying = true;
        });

        // Пауза
        audioElement.addEventListener('pause', () => {
            if (currentSession.isPlaying && currentSession.startTime) {
                const duration = Date.now() - currentSession.startTime;
                currentSession.totalTime += duration;
                currentSession.startTime = null;
                currentSession.isPlaying = false;

                // Если трек прослушан достаточно долго, сохраняем данные
                if (duration >= this.minDuration) {
                    this.saveSessionData(trackId, duration);
                }
            }
        });

        // Конец воспроизведения
        audioElement.addEventListener('ended', () => {
            if (currentSession.isPlaying && currentSession.startTime) {
                const duration = Date.now() - currentSession.startTime;
                currentSession.totalTime += duration;
                currentSession.startTime = null;
                currentSession.isPlaying = false;

                if (duration >= this.minDuration) {
                    this.saveSessionData(trackId, duration);
                }
            }
        });

        // При изменении времени (для отслеживания перемотки)
        audioElement.addEventListener('timeupdate', () => {
            if (currentSession.isPlaying && currentSession.startTime) {
                const currentTime = Date.now();
                // Каждые 30 секунд активного прослушивания отправляем данные
                if (currentTime - currentSession.startTime >= 30000) {
                    const duration = currentTime - currentSession.startTime;
                    currentSession.totalTime += duration;
                    currentSession.startTime = currentTime;

                    this.saveSessionData(trackId, duration);
                }
            }
        });
    }

    saveSessionData(trackId, duration) {
        // Сохраняем данные локально перед отправкой
        const data = {
            track_id: trackId,
            user_id: audioTracking.user_id || 0,
            type: 'play',
            play_duration: Math.floor(duration / 1000) // конвертируем в секунды
        };

        // Отправляем немедленно если это значительный отрезок времени
        if (duration >= 30000) {
            this.sendDataToServer(data);
        } else {
            // Иначе добавляем в очередь
            this.addToQueue(data);
        }
    }

    addToQueue(data) {
        const queueKey = `${data.track_id}_${data.user_id}`;
        let queue = JSON.parse(localStorage.getItem('audio_tracking_queue') || '{}');

        if (queue[queueKey]) {
            queue[queueKey].play_duration += data.play_duration;
        } else {
            queue[queueKey] = data;
        }

        localStorage.setItem('audio_tracking_queue', JSON.stringify(queue));
    }

    async sendCollectedData(force = false) {
        const queue = JSON.parse(localStorage.getItem('audio_tracking_queue') || '{}');

        if (Object.keys(queue).length === 0) return;

        for (const [key, data] of Object.entries(queue)) {
            // Отправляем только если достаточно данных или это принудительная отправка
            if (force || data.play_duration >= 5) {
                await this.sendDataToServer(data);
                delete queue[key];
            }
        }

        localStorage.setItem('audio_tracking_queue', JSON.stringify(queue));
    }

    async sendDataToServer(data) {
        try {
            const response = await fetch(audioTracking.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'save_audio_interaction',
                    nonce: audioTracking.nonce,
                    ...data
                })
            });

            const result = await response.json();

            if (!result.success) {
                console.error('Ошибка сохранения:', result.data.message);
                // Возвращаем данные в очередь при ошибке
                this.addToQueue(data);
            }
        } catch (error) {
            console.error('Ошибка сети:', error);
            this.addToQueue(data);
        }
    }
}

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', () => {
    if (typeof audioTracking !== 'undefined') {
        new AudioTracker();
    }
});