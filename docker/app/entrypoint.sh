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

# storage/ и bootstrap/cache/ смонтированы с хоста бинд-маунтом, поэтому владельца
# и права нужно фиксировать здесь, при каждом старте контейнера, а не в Dockerfile:
# на этапе docker build этих файлов ещё нет — они появляются только после monут'а
# volume, который подставляет содержимое (и права) с хоста поверх образа.
chown -R www-data:www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

exec "$@"
