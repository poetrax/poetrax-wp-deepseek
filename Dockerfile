FROM php:8.2-apache

# Установка необходимых расширений PHP
RUN docker-php-ext-install pdo_mysql mysqli

# Включение mod_rewrite для Apache
RUN a2enmod rewrite

# Копирование файлов проекта
COPY . /var/www/html/

# Настройка прав (опционально)
RUN chown -R www-data:www-data /var/www/html
