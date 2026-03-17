/**
 * Audio Control Module
 * Управление аудио на странице, анимации картинок
 */

class AudioControl {
    constructor() {
        this.audios = document.querySelectorAll('audio');
        this.isIOS = /iphone|ipad|macintosh/i.test(navigator.userAgent.toLowerCase());
        this.init();
    }

    init() {
        if (!this.audios.length) return;

        this.setupAudioElements();
        
        if (this.isIOS) {
            this.setupIOSAudioHandlers();
        } else {
            this.setupDesktopAudioHandlers();
        }

        this.initTrackCoverAnimation();
    }

    setupAudioElements() {
        this.audios.forEach(audio => {
            audio.pause();
            audio.setAttribute('controlslist', 'nodownload noplaybackrate');
            audio.preload = 'auto';
        });
    }

    setupIOSAudioHandlers() {
        const audioStates = new Map();

        this.audios.forEach(audio => {
            audioStates.set(audio, { loaded: false });

            audio.addEventListener('canplay', () => {
                audioStates.set(audio, { loaded: true });
            }, { once: true });
        });

        document.addEventListener('click', (e) => {
            const clickedAudio = e.target.closest('audio');

            if (clickedAudio && this.audios.includes(clickedAudio)) {
                const state = audioStates.get(clickedAudio);

                if (!state.loaded) {
                    clickedAudio.load();
                }

                this.pauseOtherAudios(clickedAudio);
                clickedAudio.play().catch(console.error);
            }
        }, { passive: true });
    }

    setupDesktopAudioHandlers() {
        this.audios.forEach(audio => {
            audio.addEventListener('click', () => {
                this.pauseOtherAudios(audio);
                audio.play().catch(console.error);
            }, { once: false });
        });
    }

    pauseOtherAudios(currentAudio) {
        document.querySelectorAll('audio').forEach(audio => {
            if (audio !== currentAudio && !audio.paused) {
                audio.pause();
            }
        });
    }

    initTrackCoverAnimation() {
        const cover = document.getElementById('track-cover');
        const coverImage = document.getElementById('track-cover-image');
        const player = document.getElementById('track-player');

        if (!cover) return;

        let isUnderPlayer = false;
        let timeout = null;

        cover.addEventListener('mouseenter', () => {
            if (isUnderPlayer) {
                this.moveToOriginal(cover);
                isUnderPlayer = false;
            }
        });

        cover.addEventListener('mouseleave', () => {
            if (!isUnderPlayer) {
                timeout = setTimeout(() => {
                    this.moveUnderPlayer(cover);
                    isUnderPlayer = true;
                }, 1000);
            }
        });

        cover.addEventListener('mouseenter', () => {
            if (timeout) {
                clearTimeout(timeout);
            }
        });

        cover.addEventListener('click', () => {
            if (isUnderPlayer) {
                this.moveToOriginal(cover);
                isUnderPlayer = false;
            } else {
                this.moveUnderPlayer(cover);
                isUnderPlayer = true;
            }
        });

        window.addEventListener('scroll', () => {
            if (window.scrollY > 100 && isUnderPlayer) {
                this.moveToOriginal(cover);
                isUnderPlayer = false;
            }
        });
    }

    moveUnderPlayer(cover) {
        cover.classList.add('under-player');
        
        setTimeout(() => {
            cover.classList.add('return');
        }, 300);

        document.dispatchEvent(new CustomEvent('audio:cover:under', { 
            detail: { cover } 
        }));
    }

    moveToOriginal(cover) {
        cover.classList.remove('under-player');

        setTimeout(() => {
            cover.classList.remove('return');
        }, 500);

        document.dispatchEvent(new CustomEvent('audio:cover:original', { 
            detail: { cover } 
        }));
    }

    initAudioTabManager() {
        if (!('BroadcastChannel' in window)) {
            console.warn('BroadcastChannel not supported');
            return;
        }

        const channel = new BroadcastChannel('audio-control');

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseAllMedia();
            }
        });

        channel.addEventListener('message', (event) => {
            if (event.data.type === 'pauseAudio') {
                this.pauseAllMedia();
            }
        });

        document.addEventListener('play', (event) => {
            const media = event.target;
            if (media.tagName === 'AUDIO' && event.isTrusted) {
                channel.postMessage({
                    type: 'pauseAudio',
                    timestamp: Date.now(),
                    source: window.location.href
                });
            }
        }, { capture: true });

        window.addEventListener('beforeunload', () => {
            channel.close();
        });
    }

    pauseAllMedia() {
        document.querySelectorAll('audio').forEach(audio => {
            if (!audio.paused) {
                audio.pause();
            }
        });
    }

    setupAudioPreloading() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const audio = entry.target;
                    if (audio.preload === 'none') {
                        audio.preload = 'metadata';
                        audio.load();
                    }
                    observer.unobserve(audio);
                }
            });
        });

        this.audios.forEach(audio => observer.observe(audio));
    }

    getAudioState() {
        return {
            playing: Array.from(this.audios).filter(a => !a.paused).map(a => a.src),
            isIOS: this.isIOS,
            count: this.audios.length
        };
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.audioControl = new AudioControl();
});
