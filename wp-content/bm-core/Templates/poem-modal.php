<?php
/**
 * Модальное окно со стихотворением
 */
?>

<div class="bm-modal" id="poem-modal" aria-hidden="true">
    <div class="bm-modal__overlay" tabindex="-1" data-modal-close>
        <div class="bm-modal__container" role="dialog" aria-modal="true" aria-labelledby="poem-modal-title">
            
            <!-- Заголовок -->
            <header class="bm-modal__header">
                <h2 id="poem-modal-title" class="bm-modal__title">Стихотворение</h2>
                <button class="bm-modal__close" aria-label="Закрыть" data-modal-close>
                   
                </button>
            </header>
            
            <!-- Контент -->
            <div class="bm-modal__content">
                <div class="poem-display">
                    <!-- Название стихотворения -->
                    <h3 class="poem-display__title" id="modal-poem-title"></h3>
                    
                    <!-- Автор -->
                    <div class="poem-display__author" id="modal-poem-author"></div>
                    
                    <!-- Текст стихотворения -->
                    <div class="poem-display__text" id="modal-poem-text"></div>
                    
                    <!-- Ссылка на полную страницу -->
                    <div class="poem-display__footer">
                        <a href="#" class="poem-display__link" id="modal-poem-link">
                            Читать полностью →
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Индикатор загрузки -->
            <div class="bm-modal__loader" style="display: none;">
                <div class="spinner"></div>
                <span>Загрузка стихотворения...</span>
            </div>
        </div>
    </div>
</div>