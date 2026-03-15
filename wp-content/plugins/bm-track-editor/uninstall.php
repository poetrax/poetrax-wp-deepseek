<?php
/**
 * Запускается при удалении плагина через админку
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Удаляем опции плагина
delete_option('bm_te_settings');
delete_option('bm_te_version');
delete_option('bm_te_installed');

// Очищаем кэш
wp_cache_flush();

// Логируем удаление
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('BM Track Editor: плагин удален');
}