'use strict';
document.addEventListener('DOMContentLoaded', function () {
    // Обработчик для лайков
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', handleLike);
    });

    // Обработчик для закладок
    document.querySelectorAll('.bookmark-btn').forEach(btn => {
        btn.addEventListener('click', handleBookmark);
    });
});

function handleLike(event) {
    const likeBtn = event.currentTarget;
    const trackId = likeBtn.dataset.trackId;
    const container = likeBtn.closest('.like-container');
    const countElement = container.querySelector('.like-count');

    // Проверяем, не был ли уже нажат лайк
    if (likeBtn.classList.contains('disabled')) {
        return;
    }

    // Блокируем повторное нажатие
    likeBtn.classList.add('disabled');
    likeBtn.style.cursor = 'not-allowed';
    likeBtn.style.opacity = '0.6';

    // Показываем loader
    const originalHtml = likeBtn.innerHTML;
    likeBtn.innerHTML = '<i aria-hidden="true" class="fas fa-spinner fa-spin"></i>';

    // AJAX запрос для сохранения лайка
    fetch('/ajax/save-like-bookmark.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            track_id: trackId,
            type: 'like',
            action: 'add'
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Обновляем счетчик
                countElement.textContent = data.likes_count;

                // Меняем иконку на заполненную
                likeBtn.innerHTML = '<i aria-hidden="true" class="fas fa-thumbs-up"></i>';
                likeBtn.classList.add('liked');

                // Показываем уведомление об успехе
                showNotification('Лайк добавлен!', 'success');
            } else {
                // В случае ошибки возвращаем исходное состояние
                likeBtn.innerHTML = originalHtml;
                likeBtn.classList.remove('disabled');
                likeBtn.style.cursor = 'pointer';
                likeBtn.style.opacity = '1';

                showNotification('Ошибка: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            likeBtn.innerHTML = originalHtml;
            likeBtn.classList.remove('disabled');
            likeBtn.style.cursor = 'pointer';
            likeBtn.style.opacity = '1';

            showNotification('Ошибка сети', 'error');
        });
}

function handleBookmark(event) {
    const bookmarkBtn = event.currentTarget;
    const trackId = bookmarkBtn.dataset.trackId;

    // Проверяем, не был ли уже добавлен в закладки
    if (bookmarkBtn.classList.contains('disabled')) {
        return;
    }

    // Блокируем повторное нажатие
    bookmarkBtn.classList.add('disabled');
    bookmarkBtn.style.cursor = 'not-allowed';
    bookmarkBtn.style.opacity = '0.6';

    // Показываем loader
    const originalHtml = bookmarkBtn.innerHTML;
    bookmarkBtn.innerHTML = '<i aria-hidden="true" class="fas fa-spinner fa-spin"></i>';

    // AJAX запрос для сохранения закладки
    fetch('/ajax/save-like-bookmark.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            track_id: trackId,
            type: 'bookmark',
            action: 'add'
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Меняем иконку на заполненную и скрываем
                bookmarkBtn.innerHTML = '<i aria-hidden="true" class="fas fa-bookmark"></i>';
                bookmarkBtn.classList.add('bookmarked');

                // Показываем уведомление об успехе
                showNotification('Добавлено в закладки!', 'success');
            } else {
                // В случае ошибки возвращаем исходное состояние
                bookmarkBtn.innerHTML = originalHtml;
                bookmarkBtn.classList.remove('disabled');
                bookmarkBtn.style.cursor = 'pointer';
                bookmarkBtn.style.opacity = '1';

                showNotification('Ошибка: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            bookmarkBtn.innerHTML = originalHtml;
            bookmarkBtn.classList.remove('disabled');
            bookmarkBtn.style.cursor = 'pointer';
            bookmarkBtn.style.opacity = '1';

            showNotification('Ошибка сети', 'error');
        });
}

function showNotification(message, type) {
    // Создаем элемент уведомления
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 10000;
        transition: opacity 0.3s;
    `;

    if (type === 'success') {
        notification.style.background = '#4CAF50';
    } else {
        notification.style.background = '#f44336';
    }

    document.body.appendChild(notification);

    // Автоматическое скрытие через 2 секунды
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 2000);
}

// Функция для проверки, поставил ли пользователь уже лайк/закладку
function checkUserInteractions() {
    /*
    fetch('/ajax/check-interactions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.likes.forEach(trackId => {
                    const likeBtn = document.querySelector(`.like-btn[data-track-id="${trackId}"]`);
                    if (likeBtn) {
                        likeBtn.innerHTML = '<i aria-hidden="true" class="fas fa-thumbs-up"></i>';
                        likeBtn.classList.add('disabled', 'liked');
                        likeBtn.style.cursor = 'not-allowed';
                        likeBtn.style.opacity = '0.6';
                    }
                });

                data.bookmarks.forEach(trackId => {
                    const bookmarkBtn = document.querySelector(`.bookmark-btn[data-track-id="${trackId}"]`);
                    if (bookmarkBtn) {
                        bookmarkBtn.innerHTML = '<i aria-hidden="true" class="fas fa-bookmark"></i>';
                        bookmarkBtn.classList.add('disabled', 'bookmarked');
                        bookmarkBtn.style.cursor = 'not-allowed';
                        bookmarkBtn.style.opacity = '0.6';
                    }
                });
            }
        })
        .catch(error => console.error('Error checking interactions:', error));
*/
}

// Проверяем взаимодействия при загрузке страницы
document.addEventListener('DOMContentLoaded', checkUserInteractions);