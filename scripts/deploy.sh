#!/bin/bash
# ============================================================================
# Deploy repa на сервере. Ничего не собирает: тянет код (для compose-файла),
# образы из GHCR, прогоняет миграции и поднимает контейнеры.
#
# Запуск:  bash scripts/deploy.sh [branch]   (по умолчанию main)
# Из CI:   ssh deploy@server "deploy main"   (forced command → этот скрипт)
# Вручную: ssh root@server "cd /srv/repa && bash scripts/deploy.sh main"
#
# .env на сервере не трогается (untracked, лежит в /srv/repa/.env).
# ============================================================================
set -euo pipefail

BRANCH="${1:-main}"
DEPLOY_ROOT="/srv/repa"
COMPOSE=(docker compose -f "$DEPLOY_ROOT/docker-compose.prod.yml")

echo "[deploy] start $(date -Is) branch=$BRANCH"

cd "$DEPLOY_ROOT"
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"

TAG="main-$(git rev-parse HEAD)"
export APP_TAG="$TAG"
echo "[deploy] tag=$TAG"

echo "[deploy] pull images"
"${COMPOSE[@]}" pull

echo "[deploy] run migrations (new image, старые контейнеры ещё работают)"
"${COMPOSE[@]}" run --rm --no-deps app php artisan migrate --force

echo "[deploy] up containers"
"${COMPOSE[@]}" up -d --force-recreate app nginx
"${COMPOSE[@]}" up -d postgres redis

echo "[deploy] optimize"
"${COMPOSE[@]}" exec -T app php artisan optimize:clear || true
"${COMPOSE[@]}" exec -T app php artisan optimize

echo "[deploy] done $(date -Is)"
