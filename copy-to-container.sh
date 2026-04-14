#!/bin/bash

# Цвета для вывода
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Имя контейнера
CONTAINER="poetrax_deepseek_wp"

echo -e "${GREEN}🚀 Копирование файлов в контейнер ${CONTAINER}...${NC}"

# Проверка, запущен ли контейнер
if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
    echo -e "${RED}❌ Контейнер ${CONTAINER} не запущен. Запустите docker-compose up -d сначала.${NC}"
    exit 1
fi

# Копирование файлов
echo "📁 Копирование config.php..."
docker cp config.php ${CONTAINER}:/var/www/html/

echo "📁 Копирование .env..."
docker cp .env ${CONTAINER}:/var/www/html/

echo "📁 Копирование Database/Connection.php..."
docker cp wp-content/mu-plugins/bm-core/Database/Connection.php ${CONTAINER}:/var/www/html/wp-content/mu-plugins/bm-core/Database/

echo "📁 Копирование Database/QueryBuilder.php..."
docker cp wp-content/mu-plugins/bm-core/Database/QueryBuilder.php ${CONTAINER}:/var/www/html/wp-content/mu-plugins/bm-core/Database/

echo "📁 Копирование Database/Cache.php..."
docker cp wp-content/mu-plugins/bm-core/Database/Cache.php ${CONTAINER}:/var/www/html/wp-content/mu-plugins/bm-core/Database/

echo "📁 Копирование Database/Loader.php..."
docker cp wp-content/mu-plugins/bm-core/Database/Loader.php ${CONTAINER}:/var/www/html/wp-content/mu-plugins/bm-core/Database/

echo "📁 Копирование api.php..."
docker cp api.php ${CONTAINER}:/var/www/html/

# Перезагрузка Apache (опционально)
echo "🔄 Перезагрузка Apache в контейнере..."
docker exec ${CONTAINER} service apache2 reload

echo -e "${GREEN}✅ Готово! Все файлы скопированы.${NC}"

# КАК ИСПОЛЬЗОВАТЬ
# Сохраните скрипт как copy-to-container.sh в корне проекта:
# text
# C:\Users\globa\projects\poetrax-wp-deepseek\copy-to-container.sh
# Дайте права на выполнение (в Git Bash):
# bash
# chmod +x copy-to-container.sh
# Запустите скрипт:
# bash
# ./copy-to-container.sh