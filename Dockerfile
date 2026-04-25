# Stage 1: Composer dependencies
FROM composer:2 AS composer
WORKDIR /app
COPY . .
# RUN composer install --no-dev --optimize-autoloader --no-interaction

# Stage 2: Final image
FROM php:8.2-apache

# Установка необходимых расширений
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Копирование файлов проекта
COPY . /var/www/html/

# Копирование vendor из composer stage
COPY --from=composer /app/vendor /var/www/html/vendor

# Настройка прав
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/wp-content/uploads

# Настройка Apache для продакшена
RUN echo "ServerTokens Prod" >> /etc/apache2/apache2.conf \
    && echo "ServerSignature Off" >> /etc/apache2/apache2.conf

EXPOSE 80

CMD ["apache2-foreground"]