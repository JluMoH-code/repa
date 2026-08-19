#!/bin/bash
set -e

cd /var/www/html

# На случай, если каталоги отсутствуют (например, свежий git clone без пустых
# storage/* папок) — создаём их, чтобы Laravel не падал на первом же запросе.
mkdir -p storage/framework/cache \
         storage/framework/sessions \
         storage/framework/views \
         storage/framework/testing \
         storage/logs \
         bootstrap/cache

# Сервис app в docker-compose.yml работает от www-data (user: "www-data:www-data"),
# поэтому storage/ и bootstrap/cache/ принадлежат www-data с самого начала, и chown
# не нужен. Но если контейнер стартует от root (старые образы/ручной запуск) —
# приводим владельца и права, т.к. эти папки смонтированы с хоста бинд-маунтом.
if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
    find storage bootstrap/cache -type d -exec chmod 775 {} \;
    find storage bootstrap/cache -type f -exec chmod 664 {} \;
fi

exec "$@"
