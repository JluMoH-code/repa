# Repa — статус проекта

> Служебный файл для быстрого восстановления контекста (в том числе для ассистента
> в будущих сессиях). Обновляется по мере продвижения по этапам. Дата последнего
> обновления: см. историю git / дату изменения файла.

## 1. Что это за проект

Интернет-магазин семян: офлайн-магазин переносится в онлайн. Целевой сценарий
следующего шага после текущего — человек (не программист) вручную заносит
**~1500 товаров** через админку. Поэтому удобство админки для оператора —
критерий приёмки наравне с корректностью схемы БД.

Специфика товара — семена: у каждого товара есть характеристики вроде культуры
(томаты/огурцы/...), срока созревания, назначения (открытый грунт/теплица),
количества семян в упаковке, гибрид/не гибрид и т.д.

**Что уже сделано по этапам:**
- Этап 1 — базовый каркас (Docker, Laravel 13, Postgres, Redis, Fortify, Livewire,
  Tailwind, Filament). Завершён и работает.
- Этап 2 — каталог товаров и админка Filament. В процессе (см. раздел 6 ниже),
  ядро полностью рабочее.
- Этап 3 — корзина покупателя (гостевая в сессии, для авторизованных в БД,
  объединение при входе, страница корзины, AJAX-добавление с карточек и
  страницы товара). Реализована, см. раздел 5 (таблица `carts`) и раздел 8 (тесты).
- Этап 4 — личный кабинет (`/cabinet`): обзор со счётчиками, расширенный
  профиль (телефон с маской, дата рождения, пол) и смена пароля, заглушка
  «Мои заказы», избранное (сердечко на карточках, раздел в кабинете, гости —
  сессия с объединением при входе). 2FA/passkeys в конфиге Fortify временно
  отключены (страниц для них нет).

**Как будет выглядеть дальше (не реализовано, следующие этапы):**
- Поиск, остатки/резервы, заказы (оформление заказа из корзины — кнопка
  «Оформить заказ» на странице корзины пока ведёт в никуда).

## 2. Технологический стек

| Компонент | Версия / выбор |
|---|---|
| PHP | 8.3 (php-fpm) |
| Laravel | 13 |
| PostgreSQL | 16 |
| Redis | 7 (кэш, сессии, очереди) |
| Аутентификация | Laravel Fortify (свои Blade-формы, без готового UI-кита) |
| Frontend | Blade + Livewire (без Inertia/React/Vue) |
| Стили | Tailwind CSS через Vite |
| Админка | Filament v5.7.6 |
| Slug'и | spatie/laravel-sluggable |

## 3. Инфраструктура (Docker)

Путь на диске: `C:\Users\Anton\Documents\php\repa`.

Сервисы (`docker-compose.yml`), каждый со своим Dockerfile в `docker/<service>/`:
- `app` — php-fpm 8.3 + расширения (pdo_pgsql, redis, gd, zip, bcmath, intl).
- `nginx` — порт `8080` (переменная `APP_PORT`), проксирует на `app:9000`.
- `postgres` — порт `5432`, база `seedshop` / пользователь `seedshop`.
- `redis` — порт `6379`.
- `node` — Vite dev-сервер, порт `5173`.

**Важные архитектурные решения по производительности** (актуально для Windows +
Docker Desktop + WSL2-бэкенд):
- `vendor/` и `node_modules/` вынесены в отдельные **именованные Docker-volume**
  (`vendor_data`, `node_modules_data`), а не лежат на bind-mount с Windows-хоста.
  Без этого Laravel/Blade на каждый запрос проверяет mtime сотен файлов через
  медленный мост NTFS↔WSL2 — страница `/admin/products` грузилась 2–5 секунд.
  После фикса — стабильно ~150мс.
- Composer-автозагрузчик держим оптимизированным (`composer dump-autoload -o`),
  **но без** `--classmap-authoritative` — с ним любой новый PHP-класс, добавленный
  после последнего дампа, не находится вообще (проверено дважды на практике).
- `storage/` и `bootstrap/cache` внутри контейнера `app` автоматически получают
  правильного владельца (`www-data`) и права при каждом старте контейнера — это
  делает `docker/app/entrypoint.sh` (нужно, т.к. эти папки — bind-mount с хоста,
  и chown в самом Dockerfile тут бесполезен, см. entrypoint.sh с комментариями).

**Рабочий процесс, о котором легко забыть:**
- После `composer require` / добавления нового PHP-класса: `composer dump-autoload -o`.
- Перед `php artisan test`: обязательно `php artisan optimize:clear` — иначе
  закэшированный `config`/`route` (от `php artisan optimize`, который держим
  включённым в dev для скорости) протекает в тестовый прогон и ломает тесты
  непредсказуемыми ошибками валидации формы.
- После правок `.env`/роутов/конфигов: `php artisan optimize:clear`, затем
  заново `php artisan optimize`, если нужна скорость.
- ⚠️ **Vite-watcher на bind-mount (Windows/WSL2) не всегда замечает правки
  `resources/js` / `resources/css`** — dev-сервер может продолжать отдавать
  СТАРЫЙ модуль (проверено на практике: `app.js` отдавался в до-редакционной
  версии, пока не перезапустили контейнер). Если правки фронтенда «не
  появляются» — перезапустить node-контейнер: `docker compose restart node`,
  затем в браузере жёсткое обновление (Ctrl+F5, dev-URL модулей без хэша,
  браузер может держать старую версию в кэше).

## 4. Тестовое окружение

`phpunit.xml` уже верно настраивает `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`
для тестов — это подтверждено (тесты не трогают dev-базу в Postgres). Дополнительно
создан `.env.testing` как страховка на случай прямого вызова без phpunit.xml.

Если после `php artisan test` дev-данные вдруг пропали — не паниковать, 
просто `php artisan migrate:fresh --seed`.

## 5. Структура БД

### `categories`
| Поле | Тип | Комментарий |
|---|---|---|
| id | bigint PK | |
| parent_id | FK → categories, nullable | вложенность, self-reference |
| name | string | |
| slug | string, unique | авто из name (spatie/laravel-sluggable) |
| description | text, nullable | |
| image | string, nullable | путь к файлу (disk `public`) |
| sort_order | integer, default 0 | |
| is_active | boolean, default true | |
| seo_title | string, nullable | |
| seo_description | text, nullable | |
| timestamps | | |

Guard на уровне модели: нельзя удалить категорию, если в ней есть товары
(`InvalidArgumentException` в `deleting`-событии).

### `manufacturers`
| Поле | Тип |
|---|---|
| id | bigint PK |
| name | string |
| slug | string, unique |
| logo | string, nullable |
| description | text, nullable |
| timestamps | |

### `products`
| Поле | Тип | Комментарий |
|---|---|---|
| id | bigint PK | |
| category_id | FK → categories | `restrictOnDelete` |
| manufacturer_id | FK → manufacturers, nullable | `nullOnDelete` |
| name | string | |
| slug | string, unique | |
| sku | string, unique | |
| barcode | string, nullable, unique | |
| short_description | text, nullable | |
| description | text, nullable | |
| price | unsigned bigint | **в копейках** |
| old_price | unsigned bigint, nullable | в копейках, должна быть > price (guard в модели) |
| unit | string, default «упаковка» | |
| seed_count | integer, nullable | кол-во семян в упаковке |
| status | enum-строка (PHP enum `ProductStatus`) | draft / published / hidden / archived / discontinued / preorder |
| is_active | boolean, default true | |
| is_discountable | boolean, default true | задел на скидки/лояльность |
| culture | string, nullable, indexed | «Томаты», «Огурцы» и т.д. — см. `Product::CULTURES` |
| ripening | enum-строка (PHP enum `RipeningPeriod`), nullable, indexed | early / mid / late |
| growing_place | enum-строка (PHP enum `GrowingPlace`), nullable, indexed | open_ground / greenhouse / universal |
| is_hybrid | boolean, nullable, indexed | |
| series | string, nullable | |
| attributes | jsonb, nullable | произвольные пары характеристика→значение |
| seo_title | string, nullable | |
| seo_description | text, nullable | |
| timestamps | | |

Guard'ы на уровне модели (`saving`-событие, `InvalidArgumentException`):
- `price < 0` → блокируется.
- `old_price !== null && old_price <= price` → блокируется.
- `status === Published` и категория неактивна/не найдена → блокируется.

Товары **никогда не удаляются физически** через обычный UI — только смена
статуса на `archived`/`discontinued`. Метод `Product::isHardDeletable()`
разрешает жёсткое удаление только для `draft` без изображений (используется
в `ProductsTable` для показа кнопки удаления).

### `product_images`
| Поле | Тип |
|---|---|
| id | bigint PK |
| product_id | FK → products, `cascadeOnDelete` |
| path | string |
| sort_order | integer, default 0 |
| is_main | boolean, default false |
| timestamps | |

Guard в модели: при сохранении изображения с `is_main = true` у остальных
изображений этого товара флаг снимается автоматически (только одно главное фото).

### `favorites` (этап 4 — избранное)
| Поле | Тип | Комментарий |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users, `cascadeOnDelete` | только авторизованные; гости хранят избранное в сессии |
| product_id | FK → products, `cascadeOnDelete` | |
| timestamps | | |

Уникальный индекс `(user_id, product_id)` — одна строка на пару. Хранение как
у корзины: гости — сессия (ключ `favorites`), авторизованные — таблица,
объединение при входе (тот же слушатель `App\Listeners\MergeGuestDataOnLogin`).
Логика — `App\Actions\Favorites\FavoriteManager` (singleton, кэширует ID
избранного на время запроса, чтобы карточки не делали N+1 запросов).

В `users` (этап 4) добавлены поля профиля: `phone` (string, nullable, единый
формат `+7XXXXXXXXXX`), `birth_date` (date, nullable), `gender`
(string, nullable, enum `App\Enums\Gender`: male/female).

### Прочее (из стартового каркаса, не менялось на этом этапе)
`users`, `cache`, `jobs`, плюс колонки/таблицы Fortify two-factor и `passkeys`
(2FA/passkeys отключены в `config/fortify.php` до реализации страниц).

### `carts` (этап 3 — корзина)
| Поле | Тип | Комментарий |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users, `cascadeOnDelete` | только авторизованные; гости хранят корзину в сессии |
| product_id | FK → products, `cascadeOnDelete` | |
| quantity | unsigned integer, default 1 | 1..99, guard в модели |
| timestamps | | |

Уникальный индекс `(user_id, product_id)` — одна строка на пару «пользователь —
товар», повторное добавление увеличивает `quantity`.

Хранение корзины: гости — сессия (ключ `cart`, `[product_id => quantity]`),
авторизованные — таблица `carts`. При входе гостевая корзина сливается
с корзиной пользователя (слушатель `App\Listeners\MergeCartOnLogin` на событие
`Illuminate\Auth\Events\Login`; авто-регистрация через сканирование
`app/Listeners`). Вся логика — `App\Actions\Cart\CartManager` (add/update/remove/
clear/lines/count/total/mergeGuestCart/isAvailable).

## 6. Админка (Filament) — что реализовано

Все ресурсы лежат в `app/Filament/Resources/`, навигационная группа «Каталог».

**CategoryResource**
- Форма: родитель (select с отступами по вложенности), name/slug (авто-slug
  из name на создании), sort_order, is_active, image, description,
  SEO-секция (свёрнута по умолчанию).
- Таблица: имя с визуальным отступом (`— Название`) для дочерних категорий,
  сортировка `COALESCE(parent_id, id), parent_id NULLS FIRST, sort_order`
  (группирует родителя и его детей подряд), счётчик товаров, удаление
  блокируется уведомлением, если в категории есть товары.

**ManufacturerResource** — простая форма (name/slug/logo/description) + таблица.

**ProductResource** — основной рабочий инструмент оператора:
- Форма на табах: *Основное* (name/slug/category/manufacturer/sku/barcode/status),
  *Цена и наличие* (price/old_price в копейках, unit, seed_count, is_active,
  is_discountable), *Атрибуты семян* (culture — select из фиксированного списка
  `Product::CULTURES`, ripening, growing_place, is_hybrid, series, attributes —
  KeyValue-репитер для произвольных пар), *Описание* (short/full), *Изображения*
  (Repeater с relationship к `product_images`, drag-and-drop сортировка через
  `orderColumn('sort_order')`, тоггл «главное фото»), *SEO*.
- Таблица: превью главного фото, name+sku, категория, цена (форматирована в ₽
  из копеек), статус-бейдж (цвет из `ProductStatus::getColor()`), производитель,
  дата обновления. Поиск по name/sku/barcode. Фильтры по категории/статусу/
  производителю.
- Действие **«Дублировать»** (`ReplicateAction`): копирует товар, добавляет
  «(копия)» к имени, генерирует новый slug/sku, сбрасывает barcode и статус
  на draft, копирует изображения.
- Массовые действия: смена статуса и смена категории у выделенных товаров.
- Удаление (одиночное) видно только для `draft` без изображений
  (`isHardDeletable()`).
- `ProductResource::getEloquentQuery()` эagerload'ит category/manufacturer/images
  — под 290 товаров всего 7 SQL-запросов на список (~11мс суммарно).

**ImportProducts** (`app/Filament/Pages/ImportProducts.php`) — кастомная
Filament-страница, НЕ через Schema/Form API, а как обычный Livewire-компонент
(`WithFileUploads`) с plain HTML-разметкой в
`resources/views/filament/pages/import-products.blade.php`. Логика: предпросмотр
CSV → применение → отчёт создано/обновлено/пропущено.

## 7. Модели, enum'ы, фабрики

- `App\Models\Category`, `Manufacturer`, `Product`, `ProductImage`, `Cart`,
  `Favorite` — все с `HasFactory`, у Category/Manufacturer/Product ещё `HasSlug`
  (spatie).
- `App\Enums\ProductStatus` (implements `HasColor`, `HasLabel`), `RipeningPeriod`,
  `GrowingPlace`, `Gender` (все `HasLabel`) — нативные PHP-enum'ы, задают
  и хранимое значение, и русский лейбл.
- `Product::CULTURES` — константа со списком культур (единый источник правды
  и для формы в админке, и для `ProductFactory`).
- `database/seeders/CatalogSeeder.php` — 5 корневых категорий × несколько
  дочерних (19 листовых, 2 уровня), 12 производителей, **1028 опубликованных**
  товаров с реалистичными названиями: каждый товар лежит в своей подкатегории
  и соответствует её культуре (в «Огурцах» — только огурцы, в «Капусте» —
  только капуста и т.д., по 50–70 товаров в каждой листовой подкатегории).
  Названия — реальные сорта (списки в `CatalogSeeder::CATALOG`), характеристики
  (ripening / growing_place / is_hybrid по маркеру F1 / seed_count) задаются
  правдоподобно для каждой культуры. Плюс 20 черновиков и 10 архивных товаров
  (имена — из `CatalogSeeder::EXTRA_NAMES`, тоже в своих подкатегориях, без
  изображений). 1-4 изображения-заглушки на опубликованный товар
  (SVG-плейсхолдеры в `storage/app/public/products/placeholders/`).

## 8. Тесты

Общий прогон `php artisan test` — **70 тестов, все зелёные** (не забыть
`optimize:clear` перед запуском):

- Корзина (`tests/Feature/CartTest.php`, 19 тестов): добавление/обновление/
  удаление/очистка для гостя (сессия) и авторизованного (БД), инкремент
  количества, минимум 1, запрет добавления недоступного товара (нет в наличии /
  не опубликован), валидация количества, страница корзины (суммы, пустое
  состояние), изоляция корзин разных пользователей, объединение гостевой
  корзины при входе (в т.ч. со сложением количества с существующей строкой БД).
- Избранное (`tests/Feature/FavoritesTest.php`, 8 тестов): переключение для
  гостя (сессия) и авторизованного (БД), валидация, удаление, объединение при
  входе без дублей, страница избранного (доступ только авторизованным).
- Личный кабинет (`tests/Feature/CabinetTest.php`, 7 тестов): доступ только
  авторизованным, обзор, обновление профиля (телефон/дата рождения/пол +
  валидация), смена пароля (в т.ч. проверка текущего).
- Каталог и админка: бизнес-правила моделей (Category/Product/ProductVariant/
  фильтры), страницы Filament (категории, товары), витрина (`ExampleTest`).

## 9. Открытые вопросы / технический долг

1. **Временный dev-роут** `/__dev_login` в `routes/web.php` — оставлен
   намеренно, использовался и ещё может использоваться для замеров
   производительности через PowerShell-сессии (без него нельзя было получить
   валидный authenticated HTTP-запрос для замера времени ответа админки).
   **Обязательно убрать перед реальным деплоем/продакшеном.**
2. Непонятная временная просадка dev-базы до 0 записей (см. раздел 4) — не
   расследована до конца, есть рабочий обходной путь (`migrate:fresh --seed`),
   но стоит последить, не повторится ли.
3. Категория для товара при импорте резолвится строго по точному совпадению
   имени (case-insensitive) — не создаётся автоматически при отсутствии
   (в отличие от производителя, который создаётся через `firstOrCreate`).
   Это осознанное решение (категории — иерархия, опасно создавать вслепую),
   но стоит держать в голове при доработке импорта.
4. XLSX-импорт не реализован вообще (только CSV) — в исходном ТЗ был указан
   как один из форматов, сознательно сужено до CSV ради экономии времени и
   сложности (пришлось бы тянуть отдельный пакет вроде `maatwebsite/excel`).
   Стоит решить, нужен ли XLSX явно, когда вернёмся к импорту.
5. `./vendor/bin/pint` — в рабочей копии много pre-existing нарушений
   (в основном `line_ending` CRLF из-за Windows-чекаута, плюс
   `fully_qualified_strict_types`/`ordered_imports` в старых файлах). Новые
   файлы этапа 3 (корзина) pint проходят; приводить к чистоте весь репозиторий
   не делалось — это создаст огромный несвязанный diff.

## 10. Быстрые команды

```bash
# Пересоздать БД с тестовыми данными
docker compose exec app php artisan migrate:fresh --seed

# Прогнать тесты (обязательно сначала сбросить dev-кэш конфига!)
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan test

# Вернуть кэш для скорости после тестов/правок .env
docker compose exec app php artisan optimize

# После добавления новых PHP-классов (модели, ресурсы Filament и т.д.)
docker compose exec app composer dump-autoload -o

# Создать нового Filament-администратора
docker compose exec app php artisan tinker --execute="\App\Models\User::factory()->create(['email' => 'admin@example.com']);"
```

Тестовый пользователь (создаётся сидером): `test@test.com` / `password`.
