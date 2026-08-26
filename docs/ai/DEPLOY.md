# Production: CI/CD и деплой

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

- Каталог: `/srv/repa` (принадлежит `deploy`, git pull делает `deploy`).
- Пользователь `deploy` (в группе `docker`), ключ `~deploy/.ssh/deploy_key` с
  forced command: `command="/usr/local/bin/deploy-wrapper.sh",...` — ключ умеет
  только `deploy <branch>`, всё остальное отклоняется.
- `.env` не трогается деплоем (untracked).
- Данные: `postgres_data`, `redis_data`, `storage_data` (named volumes).
- Старые dev-volumes (`vendor_data`, `node_modules_data`) после перехода на образы
  не нужны — можно удалить: `docker volume rm repa_vendor_data repa_node_modules_data`.

## Деплой вручную

```bash
ssh root@146.0.79.192
cd /srv/repa && bash scripts/deploy.sh main
# или через deploy-пользователя:
ssh -i ~/.ssh/repa_deploy_key deploy@146.0.79.192 "deploy main"
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
