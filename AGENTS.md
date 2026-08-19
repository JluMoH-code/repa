# AGENTS.md

## Проект
Seed Shop — интернет-магазин семян (перенос офлайн-магазина в онлайн).

## Стек
- PHP 8.3, Laravel 13
- PostgreSQL (dev/prod, через Docker), тесты — SQLite in-memory
- Frontend: Blade + Livewire 4, Tailwind CSS (Vite), без Inertia/Vue/React
- Админка: Filament v5.7
- Аутентификация: Laravel Fortify (свои Blade-формы, 2FA + passkeys)
- Очереди: database (Redis доступен)
- Кэш/сессии: database (Redis доступен инфраструктурно)
- Тесты: PHPUnit (Feature/Unit), не Pest
- Slug'и: spatie/laravel-sluggable
- Линтер: Laravel Pint (без кастомного pint.json)
- Статический анализ: не настроен (PHPStan/Larastan отсутствуют)
- Docker: есть (app, nginx, postgres, redis, node), сервисы в docker/<service>/

## Команды
Через Docker (основной способ запуска, см. docs/ai/COMMANDS.md):
- `docker compose up -d` — поднять окружение
- `docker compose exec app composer install`
- `cp .env.example .env` → `docker compose exec app php artisan key:generate`
- `docker compose exec app php artisan migrate:fresh --seed`
- `docker compose exec app php artisan test` (перед этим `optimize:clear`, см. ниже)
- `docker compose exec app ./vendor/bin/pint`
- `docker compose exec node npm install && npm run dev` (или `npm run build`)

⚠️ Перед тестами и после правок `.env`/routes/config обязательно:
`docker compose exec app php artisan optimize:clear` (иначе закэшированный
config/route ломает тесты и валидацию форм).

После `composer require` / нового PHP-класса: `docker compose exec app composer dump-autoload -o`.

## Структура
- Роуты: `routes/web.php` (только web, `routes/api.php` отсутствует)
- Контроллеры: `app/Http/Controllers` (Home, Catalog, Product, DemoProductPage)
- Модели: `app/Models` (Category, Manufacturer, Product, ProductImage, ProductVariant, FilterGroup, FilterValue, User)
- Enum'ы: `app/Enums` (ProductStatus, RipeningPeriod, GrowingPlace)
- Fortify-actions: `app/Actions/Fortify`
- Админка Filament: `app/Filament/{Pages,Resources}` — ресурсы Categories/Manufacturers/Products
- Миграции: `database/migrations`; сидеры: `database/seeders/CatalogSeeder.php`
- Тесты: `tests/Feature`, `tests/Unit`
- Конфиги: `config/*.php` (без нестандартных пакет-конфигов)
- Frontend: `resources/views` (Blade, включая `resources/views/filament`), `resources/js`, `resources/css`
- Отсутствуют (не создавались на данном этапе): `app/Http/Middleware` (кастомные), `app/Http/Requests`, `app/Services`, `app/Repositories`, `app/Jobs`, `app/Events`, `app/Listeners`, `app/Notifications`, `app/Mail`, `app/Policies`

## Архитектурные правила
- Бизнес-правила товара/категории реализованы как guard'ы в Eloquent-событиях
  моделей (`saving`, `deleting`), а не в отдельных сервисах — следовать этому
  паттерну для похожей логики.
- Цены хранятся в копейках (unsigned bigint) — не переводить в float.
- Товары не удаляются физически из UI, кроме `draft` без изображений — статус
  меняется на `archived`/`discontinued`.
- Дополнительные Filament-страницы вне стандартного Resource CRUD пишутся как
  обычные Livewire-компоненты (см. `ImportProducts`), а не через Schema/Form API.
- Валидация — через встроенные механизмы Filament-форм/Fortify-actions;
  отдельных FormRequest в проекте нет — не добавлять их без необходимости,
  сохранять единообразие с текущим подходом.
- Новую бизнес-логику вне моделей и Filament выносить в `app/Actions`
  (по аналогии с `app/Actions/Fortify`).

## Запрещено
- не менять `.env`;
- не коммитить секреты, содержимое `.env`, `storage/logs`;
- не удалять миграции;
- не менять `vendor/`, `node_modules/`;
- не добавлять тяжёлые зависимости (например, для XLSX-импорта) без явного запроса;
- не ломать существующие тесты (20 зелёных в `tests/Feature`, `tests/Unit`);
- не трогать роут `/__dev_login` без явного запроса — временный dev-хелпер,
  известен и должен быть удалён перед продакшеном (см. docs/PROJECT_STATUS.md).

## Definition of Done
- код работает, миграции применяются без ошибок;
- `php artisan optimize:clear && php artisan test` — все тесты зелёные
  (кроме заведомо известного падающего `ImportProductsTest`, см. docs/PROJECT_STATUS.md);
- `./vendor/bin/pint` без ошибок;
- при изменении схемы БД/бизнес-правил — обновить `docs/PROJECT_STATUS.md`.
