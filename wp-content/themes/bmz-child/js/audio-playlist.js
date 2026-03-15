'use strict';
class AudioManager {
    constructor() {
        this.standaloneAudio = document.querySelector('figure.wp-block-audio audio');
        this.playlistAudios = document.querySelectorAll('.audio-playlist audio');
        this.currentAudio = null;

        this.init();
    }

    init() {
        // Обработчик для standalone audio
        if (this.standaloneAudio) {
            this.standaloneAudio.addEventListener('play', (e) => {
                e.preventDefault();
                this.playAudio(this.standaloneAudio);
            });
        }

        // Обработчики для аудио в плейлисте
        this.playlistAudios.forEach(audio => {
            audio.addEventListener('play', (e) => {
                e.preventDefault();
                this.playAudio(audio);
            });
        });
    }

    initLichnyjZalAudios() {
        this.LichnyjZalAudios = document.querySelectorAll('.display-audio-list');
        this.LichnyjZalAudios.forEach(title => {
            title.addEventListener('click', (e) => {
                let elm = title.nextElementSibling;
                let elm1 = null;
                console.log('elm.tagName ' + elm.tagName + ' ' + elm.className);
                alert('elm.tagName ' + elm.tagName + ' ' + elm.className);

                if (title.id === 'id-here-for-common-tracks') {
                    elm1 = elm.nextElementSibling;
                    alert('elm1.tagName ' + elm1.tagName + ' ' + elm1.className);
                    console.log('elm1.tagName ' + elm1.tagName + ' ' + elm1.className);
                }

                if (elm.style.display === 'none') {
                    elm.style.display = '';
                    if (title.id === 'id-here-for-common-tracks' && elm1) {
                        elm1.style.display = '';
                    }
                } else {
                    elm.style.display = 'none';
                    if (title.id === 'id-here-for-common-tracks' && elm1) {
                        elm1.style.display = 'none';
                    }
                }
            });
        });
    }

    playAudio(audio) {
        // Если уже играет это же аудио, ничего не делаем
        if (this.currentAudio === audio) {
            audio.play().catch(e => console.log(e));
            return;
        }

        // Останавливаем текущее аудио
        this.stopCurrentAudio();

        // Устанавливаем новое текущее аудио
        this.currentAudio = audio;

        // Запускаем новое аудио
        audio.play().catch(e => console.log(e));

        // Устанавливаем обработчик окончания
        audio.addEventListener('ended', () => {
            this.currentAudio = null;
        }, { once: true });
    }

    stopCurrentAudio() {
        if (this.currentAudio) {
            this.currentAudio.pause();
            this.currentAudio.currentTime = 0;
            this.currentAudio.removeEventListener('ended', this.onAudioEnded);
        }
    }
}

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', () => {
    new AudioManager();
});