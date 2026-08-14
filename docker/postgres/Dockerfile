FROM postgres:16-alpine

# Сюда можно класть *.sql / *.sh, которые нужно прогнать один раз при первом
# старте контейнера (создание доп. схем, расширений вроде pg_trgm и т.д.).
# Postgres-образ сам подхватывает всё из /docker-entrypoint-initdb.d при пустом volume.
COPY docker/postgres/initdb/ /docker-entrypoint-initdb.d/

EXPOSE 5432
