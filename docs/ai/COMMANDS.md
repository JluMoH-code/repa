# Commands

Все команды PHP/artisan выполняются внутри контейнера `app`:
`docker compose exec app <команда>`. Frontend-команды — внутри `node`:
`docker compose exec node <команда>`.

## Setup
```bash
docker compose up -d
docker compose exec app composer install
cp .env.example .env   # если .env ещё нет
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec node npm install
docker compose exec node npm run build
```

## Development
```bash
docker compose up -d
docker compose exec node npm run dev       # Vite dev-сервер (порт 5173)
# nginx отдаёт приложение на http://localhost:8080 (APP_PORT)
```

> ⚠️ После правок `docker/app/entrypoint.sh` / `docker/app/Dockerfile` пересобрать
> контейнер: `docker compose up -d --build app`. Если админка/витрина отдают 500
> «touch(): Utime failed: Operation not permitted» (владелец скомпилированных
> вьюх сбился на root):
> `docker compose exec app sh -c "chown -R www-data:www-data storage bootstrap/cache"`.
> CLI-команды PHP (`php artisan ...`) entrypoint выполняет от www-data — так
> вьюхи не становятся root-овыми.

## Database
```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed   # пересоздать с тестовыми данными
docker compose exec app php artisan db:seed
```

## Tests
```bash
docker compose exec app php artisan optimize:clear   # обязательно перед test
docker compose exec app php artisan test
```

## Quality
```bash
docker compose exec app ./vendor/bin/pint
```
Статический анализ (PHPStan/Larastan) не настроен в проекте.

## Queue
```bash
docker compose exec app php artisan queue:listen --tries=1 --timeout=0
```
Драйвер очереди — `database` (`QUEUE_CONNECTION`).

## Frontend
```bash
docker compose exec node npm run dev     # dev-сервер
docker compose exec node npm run build   # production-сборка
```
Пакетный менеджер — npm (есть `package-lock.json`).

## Прочее
```bash
# после composer require / нового PHP-класса
docker compose exec app composer dump-autoload -o

# вернуть кэш конфига/роутов после правок .env/routes для скорости
docker compose exec app php artisan optimize

# создать Filament-администратора
docker compose exec app php artisan tinker --execute="\App\Models\User::factory()->create(['email' => 'admin@example.com']);"
```
