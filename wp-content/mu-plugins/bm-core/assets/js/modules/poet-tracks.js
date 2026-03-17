/**
 * Poet Tracks Module
 * Выпадающий список треков при наведении на поэта
 */

class PoetTracks {
    constructor() {
        this.activeDropdown = null;
        this.hoverTimers = new Map();
        this.poetLinks = [];
        this.init();
    }

    init() {
        this.findPoetLinks();
        this.bindEvents();
    }

    findPoetLinks() {
        const selectors = [
            '.wp-block-categories-list li.cat-item a',
            '.wp-block-categories li.cat-item a',
            '.cat-item a',
            '[data-poet-link]'
        ];

        selectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(link => {
                if (link.hasAttribute('data-poet-processed')) return;
                
                const poetName = link.textContent.trim();
                if (poetName && poetName.length > 1) {
                    this.poetLinks.push(link);
                    link.setAttribute('data-poet-processed', 'true');
                }
            });
        });
    }

    bindEvents() {
        this.poetLinks.forEach(link => {
            const dropdown = this.createDropdown(link);
            
            link.addEventListener('mouseenter', () => this.handleMouseEnter(link, dropdown));
            link.addEventListener('mouseleave', () => this.handleMouseLeave(link, dropdown));
            
            dropdown.addEventListener('mouseenter', () => this.cancelHide(link));
            dropdown.addEventListener('mouseleave', () => this.handleMouseLeave(link, dropdown));

            link.addEventListener('keydown', (e) => this.handleKeyDown(e, link, dropdown));
            dropdown.addEventListener('keydown', (e) => this.handleDropdownKeyDown(e, link, dropdown));
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.activeDropdown) {
                this.hideDropdown(this.activeDropdown);
            }
        });

        document.addEventListener('click', (e) => {
            if (this.activeDropdown && 
                !this.activeDropdown.contains(e.target) && 
                !e.target.closest('[data-poet-link]')) {
                this.hideDropdown(this.activeDropdown);
            }
        });
    }

    createDropdown(link) {
        const dropdown = document.createElement('div');
        dropdown.className = 'poet-tracks-dropdown';
        dropdown.id = `poet-dropdown-${Math.random().toString(36).substr(2, 9)}`;
        dropdown.setAttribute('role', 'menu');
        dropdown.setAttribute('aria-label', `Треки ${link.textContent.trim()}`);
        dropdown.setAttribute('aria-hidden', 'true');
        dropdown.setAttribute('data-loading', 'false');
        dropdown.style.display = 'none';

        dropdown.innerHTML = `
            <div class="dropdown-header">${link.textContent.trim()}</div>
            <div class="dropdown-loader">
                <div class="loader-spinner"></div>
                <span>Загрузка треков...</span>
            </div>
        `;

        const parent = link.closest('li') || link.parentElement;
        parent.style.position = 'relative';
        parent.appendChild(dropdown);

        return dropdown;
    }

    handleMouseEnter(link, dropdown) {
        this.cancelHide(link);

        const timer = setTimeout(() => {
            this.showDropdown(link, dropdown);
        }, 300);

        this.hoverTimers.set(link, { type: 'show', timer });
    }

    handleMouseLeave(link, dropdown) {
        this.cancelShow(link);

        const timer = setTimeout(() => {
            if (this.activeDropdown === dropdown) {
                this.hideDropdown(dropdown);
            }
        }, 200);

        this.hoverTimers.set(link, { type: 'hide', timer });
    }

    cancelShow(link) {
        const timer = this.hoverTimers.get(link);
        if (timer && timer.type === 'show') {
            clearTimeout(timer.timer);
            this.hoverTimers.delete(link);
        }
    }

    cancelHide(link) {
        const timer = this.hoverTimers.get(link);
        if (timer && timer.type === 'hide') {
            clearTimeout(timer.timer);
            this.hoverTimers.delete(link);
        }
    }

    async showDropdown(link, dropdown) {
        if (this.activeDropdown === dropdown) return;

        if (this.activeDropdown) {
            this.hideDropdown(this.activeDropdown);
        }

        dropdown.style.display = 'block';
        dropdown.setAttribute('aria-hidden', 'false');
        this.activeDropdown = dropdown;

        this.positionDropdown(link, dropdown);

        if (dropdown.dataset.loaded !== 'true') {
            await this.loadTracks(link, dropdown);
        }
    }

    hideDropdown(dropdown) {
        if (!dropdown) return;

        dropdown.style.display = 'none';
        dropdown.setAttribute('aria-hidden', 'true');
        
        if (this.activeDropdown === dropdown) {
            this.activeDropdown = null;
        }
    }

    async loadTracks(link, dropdown) {
        const poetName = link.textContent.trim();
        
        dropdown.setAttribute('data-loading', 'true');

        try {
            if (!window.ApiClient) {
                throw new Error('ApiClient not found');
            }

            const poets = await window.ApiClient.poets.search(poetName, 1);
            
            if (!poets || poets.length === 0) {
                throw new Error('Поэт не найден');
            }

            const poet = poets[0];
            
            const tracks = await window.ApiClient.tracks.list({
                poet_id: poet.id,
                limit: 20
            });

            this.renderTracks(dropdown, tracks);
            dropdown.dataset.loaded = 'true';

        } catch (error) {
            console.error('Load tracks error:', error);
            this.renderError(dropdown, error.message);
        } finally {
            dropdown.setAttribute('data-loading', 'false');
        }
    }

    renderTracks(dropdown, tracks) {
        if (!tracks || tracks.length === 0) {
            dropdown.innerHTML = `
                <div class="dropdown-header">Нет треков</div>
                <div class="dropdown-empty">
                    У этого поэта пока нет треков
                </div>
            `;
            return;
        }

        let html = `<div class="dropdown-header">Треки (${tracks.length})</div>`;
        html += '<div class="dropdown-tracks">';

        tracks.forEach(track => {
            html += `
                <a href="/track/${track.id}" class="dropdown-track" role="menuitem" tabindex="-1">
                    <span class="track-name">${this.escape(track.track_name)}</span>
                    <span class="track-duration">${this.formatDuration(track.duration)}</span>
                </a>
            `;
        });

        html += '</div>';

        if (tracks.length === 20) {
            html += `
                <div class="dropdown-footer">
                    <a href="/poet/${poetId}">Все треки →</a>
                </div>
            `;
        }

        dropdown.innerHTML = html;
    }

    renderError(dropdown, message) {
        dropdown.innerHTML = `
            <div class="dropdown-header">Ошибка</div>
            <div class="dropdown-error">
                <span class="error-icon">⚠️</span>
                <span class="error-message">${this.escape(message)}</span>
            </div>
        `;
    }

    positionDropdown(link, dropdown) {
        const linkRect = link.getBoundingClientRect();
        const dropdownRect = dropdown.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        dropdown.style.top = '';
        dropdown.style.bottom = '';
        dropdown.style.left = '';
        dropdown.style.right = '';

        const spaceBelow = viewportHeight - linkRect.bottom - 10;
        const spaceAbove = linkRect.top - 10;

        if (spaceBelow < dropdownRect.height && spaceAbove > dropdownRect.height) {
            dropdown.style.bottom = '100%';
            dropdown.style.marginBottom = '5px';
        } else {
            dropdown.style.top = '100%';
            dropdown.style.marginTop = '5px';
        }

        if (linkRect.left + dropdownRect.width > viewportWidth - 10) {
            dropdown.style.right = '0';
            dropdown.style.left = 'auto';
        } else {
            dropdown.style.left = '0';
            dropdown.style.right = 'auto';
        }
    }

    handleKeyDown(e, link, dropdown) {
        switch (e.key) {
            case 'Enter':
            case ' ':
                e.preventDefault();
                this.showDropdown(link, dropdown);
                break;
            case 'Escape':
                this.hideDropdown(dropdown);
                link.focus();
                break;
            case 'ArrowDown':
                if (this.activeDropdown === dropdown) {
                    e.preventDefault();
                    this.focusFirstTrack(dropdown);
                }
                break;
        }
    }

    handleDropdownKeyDown(e, link, dropdown) {
        const tracks = dropdown.querySelectorAll('.dropdown-track');
        const currentIndex = Array.from(tracks).indexOf(document.activeElement);

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                if (currentIndex < tracks.length - 1) {
                    tracks[currentIndex + 1].focus();
                }
                break;
            case 'ArrowUp':
                e.preventDefault();
                if (currentIndex > 0) {
                    tracks[currentIndex - 1].focus();
                } else {
                    link.focus();
                }
                break;
            case 'Escape':
                e.preventDefault();
                this.hideDropdown(dropdown);
                link.focus();
                break;
            case 'Home':
                e.preventDefault();
                if (tracks.length > 0) tracks[0].focus();
                break;
            case 'End':
                e.preventDefault();
                if (tracks.length > 0) tracks[tracks.length - 1].focus();
                break;
        }
    }

    focusFirstTrack(dropdown) {
        const firstTrack = dropdown.querySelector('.dropdown-track');
        if (firstTrack) firstTrack.focus();
    }

    escape(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatDuration(seconds) {
        if (!seconds || isNaN(seconds)) return '0:00';
        
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        window.poetTracks = new PoetTracks();
    }, 100);
});
