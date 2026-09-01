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
  Кнопка «Купить» на карточке и «В корзину» на странице товара после добавления
  сменяются на количество товара в корзине (stepper −/+/ссылка на корзину).
  Позже доработано: при количестве 1 «−» превращается в иконку удаления
  (товар убирается из корзины целиком), починены «−»/«+» на stepper'е
  (глобальные функции были не видны Alpine-выражениям), кнопка «в избранное»
  на странице товара, галерея изображений (миниатюры в одну строку, тусклые
  неактивные, листание стрелками поверх крайних), строка «цена — количество —
  корзина — избранное» на странице товара (избранное справа, кнопка
  «Сравнить» убрана — при четырёхзначной цене строка не переносится),
  toast-уведомления (снизу справа, всегда один видимый — новый заменяет
  текущий и обновляет его текст, например «Товар в корзине: N шт.»),
  страховка админки от «зависшей» кнопки «Загрузка файла...» при загрузке
  изображений. Поиск по каталогу работает без учёта регистра (Postgres ILIKE:
  «томат» находит и «Томат», и «томат»; SKU/штрихкод — в любом регистре).
- Этап 4 — личный кабинет (`/cabinet`): обзор со счётчиками, расширенный
  профиль (телефон с маской, дата рождения, пол) и смена пароля, заглушка
  «Мои заказы», избранное (сердечко на карточках, раздел в кабинете, гости —
  сессия с объединением при входе). 2FA/passkeys в конфиге Fortify временно
  отключены (страниц для них нет).
- Этап 5 — админка: единый вход через `/login` (админ → `/admin`,
  покупатель → `/cabinet`, роль `UserRole`), стиль в цветах бренда
  (зелёный primary, логотип Repa), дашборд со статистикой, раздел
  «Пользователи» (роль/блокировка), «Настройки магазина» (контакты
  из БД в футер), доработки товаров (фильтры, экспорт CSV, массовая
  цена/скидка, рубли в форме, ссылка на карточку, превью), блокировка
  входа заблокированных аккаунтов.
- Этап 6 — заказы: оформление заказа из корзины (`/checkout`, гости —
  с email+телефоном, авторизованные — с предзаполнением из профиля),
  таблицы `orders`/`order_items` (снимки имени и цены на момент заказа,
  номера вида «Р-2026-000123»), статусы заказа (7 шт., enum `OrderStatus`,
  guard'ы переходов в модели), список заказов и детальная страница в
  кабинете (в т.ч. гостевые заказы по email после входа), страница
  «Спасибо за заказ» для гостя (доступ по связке number+email),
  админ-страницы «Заказы» (поиск/фильтр/inline-смена статуса) и
  детальная страница заказа в Filament, guard на удаление товара,
  участвующего в заказах.
  Позже доработано: гость на success-странице создаёт аккаунт из данных
  заказа (задаёт пароль → автовход → заказ привязан к профилю; при занятом
  email — форма входа), кнопки «Редактировать» и «Отменить заказ» на
  странице заказа (доступны до отправки, статусы New/Processing/Paid;
  форма редактирования `cabinet/order-edit`; подтверждение отмены — кастомная
  модалка в стиле витрины с затемнённым фоном и лёгким блюром `backdrop-blur-sm`),
  справочник городов России (таблица `cities`, 1111 городов, сидер `CitySeeder`)
  с кастомным автопоиском на поле «Город» в checkout и редактировании —
  выпадающий список в стиле сайта (Alpine-компонент `x-shop.city-autocomplete`,
  логика в `resources/js/city-autocomplete.js`, вместо нативного `<datalist>`),
  доработана маска телефона (любой ввод → +7 (XXX) XXX-XX-XX), исправлено
  «вечное обновление» страницы корзины (`cart.js`: `pageshow` → reload только
  при восстановлении из bfcache, `event.persisted`).

**Как будет выглядеть дальше (не реализовано, следующие этапы):**
- Остатки/резервы на складе, онлайн-оплата, email-уведомления,
  повторный заказ в один клик, история смен статуса в отдельной таблице.

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
- ⚠️ **CLI-команды PHP (artisan, тесты) теперь выполняются от www-data, а не от
  root**: это делает `docker/app/entrypoint.sh` (через `setpriv` из util-linux).
  Раньше запуск `php artisan test` / `view:cache` от root
  перезаписывал владельца скомпилированных вьюх (`storage/framework/views`) на
  root, и php-fpm (www-data) не мог их `touch()` — витрина и админка падали с 500
  `touch(): Utime failed: Operation not permitted`. После правки entrypoint/Dockerfile
  контейнер нужно пересобрать: `docker compose up -d --build app`.
  Если 500 всё же случился (например, контейнер ещё не пересобран):
  `docker compose exec app sh -c "chown -R www-data:www-data storage bootstrap/cache"`
  (`sh` в entrypoint остаётся root, так что chown работает).

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
В `users` (этап 5) добавлены: `role` (string, default `customer`, enum
`App\Enums\UserRole`: admin/customer) и `is_blocked` (boolean, default false).

### `settings` (этап 5 — настройки магазина)
| Поле | Тип | Комментарий |
|---|---|---|
| id | bigint PK | |
| key | string, unique | phone / email / address / work_hours / about_text |
| value | text, nullable | |
| timestamps | | |

Доступ через `App\Actions\Settings\SettingsManager` (singleton, кэш на запрос,
дефолты в коде). Значения выводятся в футере витрины; редактируются на
Filament-странице «Настройки магазина» (`/admin/shop-settings`).

### Админка (этап 5)
- Единый вход через Fortify: `App\Http\Responses\LoginResponse` ведёт админа
  на `/admin`, покупателя — в `/cabinet`; отдельный логин Filament убран
  (`->authGuard('web')` без `->login()`);
- Доступ: `App\Http\Middleware\EnsureUserIsAdmin` (покупателя молча
  перенаправляет в кабинет), `User::canAccessPanel()`;
- Заблокированный (`is_blocked`) не проходит вход — шаг пайплайна
  `App\Actions\Fortify\CheckIfUserIsBlocked`;
- Тема: кастомная Filament-тема `resources/css/filament/admin/theme.css`
  (регистрируется через `->viteTheme()` в `AdminPanelProvider`), палитра витрины —
  primary = зелёный бренд (Tailwind green, #16a34a), gray = slate, success =
  emerald, warning = оранжевый акцент, info = blue, danger = red; шрифт — локальный
  Inter Variable из комплекта Filament (без внешних CDN, как и на витрине);
  логотип `public/images/repa-logo.svg`, кнопка «Открыть магазин» в топбаре;
  тёмная тема отключена (`->darkMode(false)`) — админка всегда светлая;
- Локализация: админка принудительно на русском — middleware
  `App\Http\Middleware\SetAdminLocale` ставит `app()->setLocale('ru')` для
  запросов панели (глобально в .env APP_LOCALE=en, менять его нельзя), а все
  кнопки/уведомления берутся из встроенных ru-переводов Filament
  (`vendor/filament/*/resources/lang/ru`);
- Дашборд: `App\Filament\Widgets\StatsOverview` (товары/категории/
  производители/пользователи), виджет FilamentInfoWidget убран;
- Ресурс `App\Filament\Resources\Users\UserResource` (роль, блокировка,
  фильтры, поиск);
- «Настройки магазина»: `App\Filament\Pages\ShopSettings`;
- Товары: фильтры «Культура/В наличии/Со скидкой», экспорт CSV (без новых
  пакетов), массовые «Установить цену» и «Скидка %», рублёвый хинт цены,
  ссылка «Смотреть на сайте» из формы, таб «Превью».

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
clear/lines/count/total/quantity/mergeGuestCart/isAvailable; singleton, кэширует
количества на время запроса, чтобы карточки/шапка не делали N+1 запросов).
AJAX-ответы `cart.add`/`cart.update`/`cart.remove` дополнительно возвращают
`quantity` — количество единиц конкретного товара в корзине (для смены кнопки
«Купить»/«В корзину» на количество). Эндпоинт `GET /cart/quantities` отдаёт
актуальные количества по всем товарам + count/total — фронтенд синхронизирует
карточки/шапку при возврате «назад» (bfcache-восстановление страницы со старым
DOM, событие `pageshow` + `cart-synced` в `resources/js/cart.js`).

### `orders` (этап 6 — заказы)
| Поле | Тип | Комментарий |
|---|---|---|
| id | bigint PK | |
| number | string(20), unique | человекочитаемый «Р-2026-000123», генерируется в модели (`creating`), уникальность с retry |
| user_id | FK → users, nullable, `nullOnDelete` | гость — null; гостевые заказы остаются после удаления аккаунта |
| customer_name | string(120) | снимок на момент заказа |
| customer_email | string(180) | по нему гость находит заказы после регистрации |
| customer_phone | string(20) | нормализован +7XXXXXXXXXX |
| delivery_city / delivery_postcode(nullable) / delivery_address | string | адрес доставки |
| comment | text, nullable | комментарий покупателя |
| status | string(20) | enum `App\Enums\OrderStatus` |
| subtotal / total | unsigned bigint | копейки; на этом этапе total = subtotal (без доставки) |
| placed_at | timestamp | момент оформления |
| timestamps | | |

Индексы: user_id, status, placed_at, customer_email. `Route Key Name` — `number`
(URL заказа — `/cabinet/orders/Р-2026-000123`).

### `order_items` (этап 6 — позиции заказа)
| Поле | Тип | Комментарий |
|---|---|---|
| id | bigint PK | |
| order_id | FK → orders, `cascadeOnDelete` | |
| product_id | FK → products, `restrictOnDelete` | товар нельзя удалить, если он в заказе (guard в `Product::deleting`) |
| product_name | string(255) | снимок имени на момент заказа |
| price | unsigned bigint | цена за единицу на момент заказа (копейки) |
| quantity | unsigned integer | |
| line_total | unsigned bigint | price × quantity |
| timestamps | | |

Уникальный индекс `(order_id, product_id)`.

### `cities` (этап 6 — справочник городов РФ)
| Поле | Тип | Комментарий |
|---|---|---|
| id | bigint PK | |
| name | string(120) | название города |
| region | string(120), nullable | субъект РФ (для уточнения одноимённых городов) |
| timestamps | | |

Индекс на `name`. Заполняется сидером `CitySeeder` (1111 городов по субъектам РФ),
используется для автопоиска (`<datalist>`) на поле «Город» в checkout и
редактировании заказа.

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
  *Цена и наличие* (price/old_price **в рублях** — поле само форматируется из
  копеек БД и переводит обратно, разделитель — запятая или точка, например
  «150» или «150,50»; валидация на корректное число; у вариантов цена так же
  в рублях), unit, seed_count, is_active, is_discountable), *Атрибуты семян*
  (culture — select из фиксированного списка
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

**Orders** (`app/Filament/Pages/Orders.php`, группа «Магазин») — список заказов
обычным Livewire-компонентом (`WithPagination`): поиск по номеру/email/имени,
фильтр по статусу, inline-смена статуса из строки таблицы (`changeStatus`),
ссылки на детальную страницу.

**OrderShow** (`app/Filament/Pages/OrderShow.php`, маршрут
`/admin/orders/{order}`, скрыта из навигации) — детальная страница заказа:
покупатель/доставка/статус, комментарий, состав заказа со ссылками на товар,
форма смены статуса (переходы валидирует guard в модели `Order::saving`,
при запрещённом переходе — уведомление и откат select).

## 7. Модели, enum'ы, фабрики

- `App\Models\Category`, `Manufacturer`, `Product`, `ProductImage`, `Cart`,
  `Favorite`, `Order`, `OrderItem`, `City` — все с `HasFactory`,
  у Category/Manufacturer/Product ещё `HasSlug` (spatie).
- `App\Enums\ProductStatus` (implements `HasColor`, `HasLabel`), `RipeningPeriod`,
  `GrowingPlace`, `Gender` (все `HasLabel`), `OrderStatus` (`HasColor`,
  `HasLabel`) — нативные PHP-enum'ы, задают и хранимое значение, и русский
  лейбл.
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

Общий прогон `php artisan test` — **142 теста, все зелёные** (не забыть
`optimize:clear` перед запуском):

- Корзина (`tests/Feature/CartTest.php`, 27 тестов): добавление/обновление/
  удаление/очистка для гостя (сессия) и авторизованного (БД), инкремент
  количества, минимум 1, запрет добавления недоступного товара (нет в наличии /
  не опубликован), валидация количества, страница корзины (суммы, пустое
  состояние), изоляция корзин разных пользователей, объединение гостевой
  корзины при входе (в т.ч. со сложением количества с существующей строкой БД),
  `quantity` в AJAX-ответах, эндпоинт `cart.quantities`, состояние
  карточки/страницы товара при наличии товара в корзине.
- Заказы (`tests/Feature/OrderTest.php`, 43 теста): доступ к /checkout (пустая
  корзина → редирект, иначе форма), оформление гостем (заказ+позиции со
  снимками, очистка сессии, номер/суммы в копейках, валидация формы), оформление
  авторизованным (предзаполнение из профиля, заказ из БД-корзины с очисткой),
  список заказов в кабинете (только свои, гостевые видны по email после входа),
  детальная страница (404 чужому, рендер позиций и бейджа статуса), админ-
  страницы (доступ только админу, смена статуса через Livewire, рендер
  детальной), guard'ы статусов (нельзя вернуть в «Новый», нельзя сменить
  финальный), уникальность номеров, success-страница (доступ по number+email,
  отказ при неверном email), генерация номера при создании без номера,
  **аккаунт гостя из заказа** (форма пароля на success, вход при занятом email,
  скрытие форм для авторизованного, создание аккаунта + привязка всех гостевых
  заказов по email, отказ при занятом email, валидация пароля, повторный
  вызов → 404), **отмена заказа** (свои до отправки, отказ для Shipped/
  Delivered/чужого), **редактирование** (свои до отправки, отказ для Shipped,
  404 чужому, валидация, авторизация), **города** (datalist на checkout,
  CRUD-смоук модели City).
- Избранное (`tests/Feature/FavoritesTest.php`, 9 тестов): переключение для
  гостя (сессия) и авторизованного (БД), валидация, удаление, объединение при
  входе без дублей, страница избранного (доступ только авторизованным),
  кнопка «в избранное» на странице товара с состоянием.
- Личный кабинет (`tests/Feature/CabinetTest.php`, 7 тестов): доступ только
  авторизованным, обзор, обновление профиля (телефон/дата рождения/пол +
  валидация), смена пароля (в т.ч. проверка текущего).
- Админка (`tests/Feature/AdminAccessTest.php`, 9 тестов): редирект админа
  в админку и покупателя в кабинет после входа, доступ к /admin (гость →
  логин, покупатель/заблокированный → кабинет), блокировка входа, настройки
  магазина (дефолты, сохранение, вывод в футере).
- Каталог и админка: бизнес-правила моделей (Category/Product/ProductVariant/
  фильтры), страницы Filament (категории, товары, пользователи), витрина
  (`ExampleTest`).

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
6. Заказы: **учёт остатков/резервов на складе не реализован** — товар
   «в наличии» (in_stock) не уменьшается и не резервируется при оформлении
   заказа; защита только повторной проверкой `isAvailable` перед созданием.
   Реализовывать на отдельном этапе вместе с «остатки/резервы».
7. Заказы: **онлайн-оплата не реализована** — статус «Оплачен» админ ставит
   вручную из админки. Email-уведомления покупателю/админу не отправляются.
8. Заказы: **история смен статуса не хранится** (нет таблицы статусов) — в
   кабинете/админке виден только текущий статус; дата последнего изменения —
   `updated_at`. Планируется отдельной таблицей при доработке.
9. Заказы: в детальной странице админки ссылка на товар ведёт на `ProductResource`
   по `id`, а позиция хранит снимок `product_name` — при переименовании товара
   в заказе останется старое имя (это by design, снимок).
10. Заказы: варианты товара (`ProductVariant`) в заказе не поддерживаются — одна
    строка = один товар с фиксированной ценой `products.price` (без учёта
    цены варианта). Добавить, если понадобится продажа вариантов.
11. `resources/js/cart.js`: исправлено «вечное обновление» страницы корзины —
    обработчик `pageshow` вызывал `location.reload()` при каждой загрузке
    (бесконечный цикл). Теперь reload только при восстановлении из bfcache
    (`event.persisted === true`), иначе — `syncCartState()`.
12. Справочник городов (`CitySeeder`) — статичный список 1111 городов РФ без
    поиска по региону на сервере и без связи `orders.delivery_city` с
    таблицей `cities` (город хранится строкой). Автопоиск — кастомный
    выпадающий список (`x-shop.city-autocomplete`, Alpine), подсказки
    «Город (Регион)», при выборе подставляется название города. Валидация
    города мягкая (не обязана совпадать со справочником). При необходимости —
    отдельный JSON-эндпоинт и FK.

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

Тестовые пользователи (создаются сидером): администратор `admin@admin.com` / `password`,
покупатель `test@test.com` / `password`.
