# Production: CI/CD и деплой

> **Правило:** все изменения — в отдельной ветке от `main`, в `main` — только
> через Pull Request с зелёным CI. Merge автоматически деплоит на сервер.

## Схема работы (GitHub Flow + CI/CD)

- Новые фичи — в коротких ветках от `main`, PR в `main`, обязательные проверки CI.
- Постоянной ветки `dev` нет (нет staging-окружения).
- На каждый PR: `tests` (phpunit, SQLite in-memory) + `pint --test` + сборка фронтенда.
- При merge в `main` GitHub Actions:
  1. прогоняет те же проверки;
  2. собирает 4 образа (`repa-app`, `repa-nginx`, `repa-postgres`, `repa-redis`) из
     `docker/app/Dockerfile.prod` и публикует в GHCR (`ghcr.io/jlumoh-code/*`), теги
     `main-<sha>` и `latest`;
  3. по SSH (пользователь `deploy`, forced command) запускает на сервере
     `scripts/deploy.sh main`.

Сервер ничего не собирает: только тянет образы, прогоняет миграции и поднимает
контейнеры. Код лежит в образах; `.env` живёт только на сервере
(`/srv/repa/.env`, gitignored, подключается через `env_file`).

## Как это устроено

| Файл | Роль |
|---|---|
| `docker/app/Dockerfile.prod` | multi-stage: vendor (`--no-dev`), фронтенд (vite), php-fpm, nginx |
| `docker-compose.prod.yml` | прод-стек: образы из GHCR, `env_file: /srv/repa/.env`, storage в volume |
| `.github/workflows/ci-cd.yml` | CI на PR + сборка образов + деплой на merge в `main` |
| `scripts/deploy.sh` | скрипт деплоя на сервере (pull → migrate → up → optimize) |

## Сервер

- Сервер: `77.91.113.161` (SSH 22, пользователь `deploy` для CI/CD).
- Каталог: `/srv/repa` (принадлежит `deploy`, git pull делает `deploy`).
- Пользователь `deploy` (в группе `docker`), ключ `~deploy/.ssh/deploy_key` с
  forced command: `command="/usr/local/bin/deploy-wrapper.sh",...` — ключ умеет
  только `deploy <branch>`, всё остальное отклоняется.
- `.env` не трогается деплоем (untracked).
- Данные: `postgres_data`, `redis_data`, `storage_data` (named volumes).
- Старые dev-volumes (`vendor_data`, `node_modules_data`) после перехода на образы
  не нужны — можно удалить: `docker volume rm repa_vendor_data repa_node_modules_data`.

## Первичная настройка сервера (одноразово)

1. Ubuntu 24.04 + Docker + compose plugin; добавить swap (2–4 ГБ) —
   `fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile`.
2. Пользователь `deploy` (группа `docker`), `authorized_keys` с forced command
   `deploy-wrapper.sh`. Можно переиспользовать **тот же ключ** CI/CD — тогда
   секрет `DEPLOY_SSH_KEY` на GitHub менять не нужно (см. wrapper на текущем сервере).
3. `git clone https://github.com/JluMoH-code/repa.git /srv/repa` + `chown -R deploy:deploy /srv/repa`.
4. `/srv/repa/.env`: `APP_URL=http://<IP>`, сгенерировать **`APP_KEY`**
   (`php artisan key:generate --show` внутри контейнера app), задать `DB_PASSWORD`,
   `APP_DEBUG=false`. После правок `.env` контейнер пересоздавать
   (`docker compose up -d --force-recreate app`), `restart` env не перечитывает.
5. Хост в `.github/workflows/ci-cd.yml` — через PR.
6. `cd /srv/repa && bash scripts/deploy.sh main` — миграции применятся автоматически.
7. Сидеры (faker — dev-зависимость, в прод-образе её нет) — разовым контейнером:
   ```bash
   docker compose -f docker-compose.prod.yml run --rm -u root -e COMPOSER_ALLOW_SUPERUSER=1 \
     -e COMPOSER_HOME=/tmp/composer -v repa_seed_vendor:/var/www/html/vendor app sh -c \
     'curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
      && composer install --no-interaction --prefer-dist && chown -R www-data:www-data /var/www/html/vendor'
   docker compose -f docker-compose.prod.yml run --rm -e HOME=/tmp \
     -v repa_seed_vendor:/var/www/html/vendor app php artisan db:seed --force
   ```

## Деплой вручную

```bash
ssh root@77.91.113.161
cd /srv/repa && bash scripts/deploy.sh main
# или через deploy-пользователя:
ssh -i ~/.ssh/repa_deploy_key deploy@77.91.113.161 "deploy main"
```

## Откат

Повторный деплой предыдущего коммита (образы уже в GHCR, тег `main-<sha>`):

```bash
cd /srv/repa && git checkout main~1 && TAG_OVERRIDE... 
# проще: git reset --hard <sha> && APP_TAG=main-<sha> docker compose -f docker-compose.prod.yml pull && up -d
```

## Настройка (один раз)

1. GitHub → Settings → Secrets → Actions: `DEPLOY_SSH_KEY` = содержимое приватного
   ключа (на машине разработчика: `~/.ssh/repa_deploy_key`).
2. GitHub → Settings → Branches → `main`: Branch protection — require status
   checks (`tests`, `frontend`).
3. GHCR-пакеты публичного репо публичны — сервер тянет их без токена.

## После смены IP сервера

- Обновить `APP_URL` в `/srv/repa/.env`;
- поменять `host` в `.github/workflows/ci-cd.yml` (job `deploy`);
- на сервере: `docker compose -f docker-compose.prod.yml exec -T app php artisan optimize:clear && php artisan optimize`.
