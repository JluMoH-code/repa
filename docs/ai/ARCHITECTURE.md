# Architecture

## Общее описание
Repa — интернет-магазин семян. Проект на этапе переноса каталога товаров
из офлайн-магазина: реализована схема БД для товаров/категорий/производителей
и админка Filament для ручного занесения ~1500 позиций. Публичная витрина
(корзина, заказы, поиск) не реализована — есть только заготовка
регистрации/входа (Fortify).

## Тип приложения
Mixed: Blade + Livewire (публичные страницы каталога) + Filament-админка
(SPA-подобная панель на Livewire). API отсутствует (`routes/api.php` нет).

## Основные модули
- Каталог: категории (с вложенностью), производители, товары
- Товары: изображения, варианты (`ProductVariant`), фильтры
  (`FilterGroup`/`FilterValue`)
- Импорт товаров из CSV (Filament-страница)
- Аутентификация (Fortify): регистрация/вход/2FA/passkeys
- Корзина: `App\Actions\Cart\CartManager` (сессия для гостей, таблица `carts`
  для авторизованных, объединение при входе), `CartController`, страница
  `/cart`, AJAX-добавление с карточек и страницы товара
- Личный кабинет: `/cabinet` (обзор, профиль с телефоном/датой рождения/полом,
  смена пароля, заглушка «Мои заказы»), избранное — `FavoriteManager`
  (сессия/таблица `favorites`), сердечко на карточках, страница избранного

## HTTP-слой
- Роуты: `routes/web.php` — только web-роуты (`/`, `/product/{product}`,
  `/catalog/{category}`, `/cart` + POST `/cart/add|update|remove|clear`,
  `/cabinet*` под `auth`, POST `/favorites/toggle|remove`, `/demo/product`,
  временный `/__dev_login`)
- Middleware: стандартные Laravel, кастомных в `app/Http/Middleware` нет
- Контроллеры: `app/Http/Controllers` — HomeController, CatalogController,
  ProductController, CartController, CabinetController, FavoritesController,
  DemoProductPageController (тонкие, без FormRequest)
- FormRequest: не используются
- API Resources: не используются (нет API)

## Доменная логика
- Services/Repositories: отсутствуют как отдельный слой
- Actions: `app/Actions/Fortify` — actions создания/обновления пользователя
  (включая поля профиля), сброса/смены пароля (стандартный паттерн Fortify);
  `app/Actions/Cart/CartManager` и `app/Actions/Favorites/FavoriteManager` —
  вся бизнес-логика корзины/избранного (add/remove/toggle/count/merge и т.д.),
  FavoriteManager — singleton (кэш ID на время запроса против N+1)
- Listeners: `app/Listeners/MergeGuestDataOnLogin` — перенос гостевых корзины
  и избранного в данные пользователя при входе (событие
  `Illuminate\Auth\Events\Login`, авто-регистрация сканированием `app/Listeners`)
- Бизнес-правила реализованы как guard'ы в Eloquent-событиях моделей
  (`saving`, `deleting`) — например, запрет отрицательной цены, запрет
  удаления непустой категории, ограничение `quantity` позиции корзины 1..99

## Данные
- Модели: `App\Models\{Cart,Category,Favorite,Manufacturer,Product,ProductImage,
  ProductVariant,FilterGroup,FilterValue,User}`
- Таблицы: `categories` (self-reference parent_id), `manufacturers`,
  `products` (цена в копейках, enum-поля статус/срок созревания/место
  выращивания через нативные PHP enum'ы), `product_images`,
  `product_variants`, `filter_groups`, `filter_values`,
  `filter_value_product` (pivot), `carts` (корзина авторизованных, одна
  строка на пару user+product, unique(user_id, product_id)), `favorites`
  (избранное, unique(user_id, product_id)), плюс стандартные `users`
  (с полями профиля phone/birth_date/gender), `cache`, `jobs`,
  Fortify two-factor columns, `passkeys`
- Связи: Category — self-reference (parent/children), Product belongsTo
  Category/Manufacturer, hasMany ProductImage/ProductVariant, belongsToMany
  FilterValue; Cart/Favorite belongsTo User/Product
- Миграции: `database/migrations`; сидер с тестовыми данными:
  `database/seeders/CatalogSeeder.php`

## Инфраструктура
- БД: PostgreSQL (Docker-сервис `postgres`), в тестах — SQLite `:memory:`
- Кэш: database (Redis поднят в Docker, но не подключён как драйвер кэша)
- Очередь: database
- Storage: локальный диск `public` (изображения товаров/категорий)
- Внешние API: не обнаружены
- Docker: `docker-compose.yml` — сервисы app (php-fpm 8.3), nginx (порт 8080),
  postgres, redis, node (Vite, порт 5173); подробности и известные нюансы
  производительности (Windows/WSL2) — в `docs/PROJECT_STATUS.md`

## Тесты
- Framework: PHPUnit (`phpunit.xml`), тестsuites Unit/Feature
- Расположение: `tests/Feature` (включая `tests/Feature/Filament`),
  `tests/Unit`
- Запуск: `php artisan optimize:clear && php artisan test`
  (внутри контейнера: `docker compose exec app ...`)
- Все тесты зелёные (`php artisan test`).
