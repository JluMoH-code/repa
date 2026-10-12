<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\GrowingPlace;
use App\Enums\ProductStatus;
use App\Enums\RipeningPeriod;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'lg' => 3])
                    ->schema([
                        Group::make()->columnSpan(['default' => 1, 'lg' => 2])->schema([
                            Section::make('Основное')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Название')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->hintAction(
                                            Action::make('viewOnSite')
                                                ->label('Смотреть на сайте')
                                                ->icon('heroicon-o-arrow-top-right-on-square')
                                                ->url(fn (?Product $record) => $record ? route('products.show', $record) : '#')
                                                ->openUrlInNewTab()
                                                ->visible(fn (?Product $record) => $record !== null)
                                        )
                                        ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                            if ($operation === 'create') {
                                                $set('slug', Str::slug($state));
                                            }
                                        })
                                        ->columnSpanFull()
                                        ->maxLength(255),
                                    Select::make('status')
                                        ->label('Публикация')
                                        ->helperText('Черновик виден только в админке. Опубликованный товар доступен покупателям.')
                                        ->options(ProductStatus::class)
                                        ->native(false)
                                        ->required()
                                        ->default(ProductStatus::Draft),
                                    Select::make('category_id')
                                        ->label('Категория')
                                        ->options(fn () => Category::tree()->pluck('indentedName', 'id'))
                                        ->searchable()
                                        ->native(false)
                                        ->required(),
                                    Select::make('manufacturer_id')
                                        ->label('Производитель')
                                        ->relationship('manufacturer', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->native(false),
                                    TextInput::make('sku')
                                        ->label('Артикул')
                                        ->helperText('Необязательно')
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(255),
                                    TextInput::make('barcode')
                                        ->label('Штрихкод')
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(255),
                                ])
                                ->columns(2),

                            Section::make('Характеристики семян')
                                ->schema([
                                    TextInput::make('package_size')
                                        ->label('Вес, метраж или количество семян')
                                        ->helperText('Например: 0,5 г, 5 м или 20 шт.')
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    Select::make('culture')
                                        ->label('Культура')
                                        ->options(array_combine(Product::CULTURES, Product::CULTURES))
                                        ->searchable()
                                        ->native(false),
                                    Select::make('ripening')
                                        ->label('Срок созревания')
                                        ->options(RipeningPeriod::class)
                                        ->native(false),
                                    Select::make('growing_place')
                                        ->label('Место выращивания')
                                        ->options(GrowingPlace::class)
                                        ->native(false),
                                    Toggle::make('is_hybrid')
                                        ->label('Гибрид (F1)'),
                                    TextInput::make('series')
                                        ->label('Серия')
                                        ->maxLength(255),
                                    KeyValue::make('attributes')
                                        ->label('Дополнительные характеристики')
                                        ->keyLabel('Характеристика')
                                        ->valueLabel('Значение')
                                        ->addActionLabel('Добавить характеристику')
                                        ->reorderable()
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),

                            Section::make('Другие фасовки')
                                ->collapsible()
                                ->collapsed(fn (?Product $record) => ! $record?->variants()->exists())
                                ->schema([
                                    Repeater::make('variants')
                                        ->relationship()
                                        ->label('')
                                        ->helperText('Напр. «10 семян», «20 семян». На витрине показывается цена выбранного варианта. Основная цена товара должна быть не выше максимальной цены варианта.')
                                        ->schema([
                                            TextInput::make('label')
                                                ->label('Название')
                                                ->required()
                                                ->maxLength(255),
                                            TextInput::make('price')
                                                ->label('Цена')
                                                ->helperText('В рублях, разделитель — запятая или точка (например 150 или 150,50)')
                                                ->required()
                                                ->rule('regex:/^\d{1,9}([.,]\d{1,2})?$/')
                                                ->validationMessages(['regex' => 'Цена должна быть числом в рублях (например 150 или 150,50).'])
                                                ->formatStateUsing(fn ($state) => static::formatPriceForForm($state))
                                                ->dehydrateStateUsing(fn ($state) => static::dehydratePriceFromForm($state)),
                                            Toggle::make('in_stock')
                                                ->label('В наличии')
                                                ->default(true),
                                        ])
                                        ->columns(3)
                                        ->orderColumn('sort_order')
                                        ->reorderable()
                                        ->reorderableWithDragAndDrop()
                                        ->collapsible()
                                        ->defaultItems(0)
                                        ->addActionLabel('Добавить вариант')
                                        ->itemLabel(fn (array $state) => $state['label'] ?? 'Вариант'),
                                ]),

                            Section::make('Фильтры каталога')
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    Select::make('filterValues')
                                        ->label('Значения фильтров')
                                        ->helperText('Значения из групп фильтров корневой категории. Назначаются для фильтрации в каталоге.')
                                        ->multiple()
                                        ->options(fn (?Product $record) => $record?->filterOptionGroups() ?? [])
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Описание товара')
                                ->schema([
                                    Textarea::make('short_description')
                                        ->label('Краткое описание')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                    Textarea::make('description')
                                        ->label('Полное описание')
                                        ->rows(8)
                                        ->columnSpanFull(),
                                ]),

                        ]),
                        Group::make()->columnSpan(1)->schema([
                            Section::make('Цена и наличие')
                                ->schema([
                                    TextInput::make('price')
                                        ->label('Цена')
                                        ->helperText('В рублях, разделитель — запятая или точка (например 150 или 150,50)')
                                        ->required()
                                        ->rule('regex:/^\d{1,9}([.,]\d{1,2})?$/')
                                        ->validationMessages(['regex' => 'Цена должна быть числом в рублях (например 150 или 150,50).'])
                                        ->formatStateUsing(fn ($state) => static::formatPriceForForm($state))
                                        ->dehydrateStateUsing(fn ($state) => static::dehydratePriceFromForm($state)),
                                    TextInput::make('old_price')
                                        ->label('Старая цена (для скидки)')
                                        ->helperText('В рублях. Если задана, должна быть больше текущей цены')
                                        ->rule('regex:/^\d{1,9}([.,]\d{1,2})?$/')
                                        ->validationMessages(['regex' => 'Цена должна быть числом в рублях (например 150 или 150,50).'])
                                        ->formatStateUsing(fn ($state) => static::formatPriceForForm($state))
                                        ->dehydrateStateUsing(fn ($state) => static::dehydratePriceFromForm($state)),
                                    TextInput::make('unit')
                                        ->label('Единица измерения')
                                        ->default('упаковка')
                                        ->required(),
                                    TextInput::make('seed_count')
                                        ->label('Кол-во семян в упаковке')
                                        ->numeric()
                                        ->minValue(0),
                                    Toggle::make('is_active')
                                        ->label('Показывать в каталоге')
                                        ->default(true),
                                    Toggle::make('is_discountable')
                                        ->label('Участвует в скидках/программе лояльности')
                                        ->default(true),
                                    Toggle::make('in_stock')
                                        ->label('В наличии')
                                        ->default(true)
                                        ->disabled(fn (?Product $record) => $record?->stock_quantity !== null)
                                        ->helperText('При учёте количества наличие определяется остатком. Изменить количество можно в разделе «Остатки». Для фасовок используется их наличие.'),
                                    Toggle::make('is_bestseller')
                                        ->label('Показывать в «Хиты продаж»')
                                        ->default(false),
                                    Toggle::make('is_recommended')
                                        ->label('Показывать в «Рекомендуем»')
                                        ->default(false),
                                ])
                                ->columns(2),

                            Section::make('Фото товара')
                                ->schema([
                                    Repeater::make('images')
                                        ->relationship()
                                        ->label('')
                                        ->schema([
                                            FileUpload::make('path')
                                                ->label('Изображение')
                                                ->image()
                                                ->disk('public')
                                                ->directory('products')
                                                ->required()
                                                ->columnSpan(2),
                                            Toggle::make('is_main')
                                                ->label('Главное фото')
                                                ->default(false),
                                        ])
                                        ->columns(3)
                                        ->orderColumn('sort_order')
                                        ->reorderable()
                                        ->reorderableWithDragAndDrop()
                                        ->collapsible()
                                        ->defaultItems(0)
                                        ->addActionLabel('Добавить изображение')
                                        ->itemLabel(fn (array $state) => $state['is_main'] ?? false ? 'Главное' : 'Фото'),
                                ]),

                            Section::make('Адрес страницы и поиск')
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    TextInput::make('slug')
                                        ->label('Адрес страницы')
                                        ->helperText('Создаётся автоматически из названия. Менять необязательно.')
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    TextInput::make('seo_title')
                                        ->label('SEO-заголовок')
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    Textarea::make('seo_description')
                                        ->label('SEO-описание')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Как товар выглядит на сайте')
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    Placeholder::make('preview')
                                        ->label('')
                                        ->content(fn (?Product $record) => $record
                                            ? new HtmlString(view('filament.product-preview', ['product' => $record])->render())
                                            : 'Сохраните товар, чтобы увидеть, как карточка выглядит на витрине.'),
                                ]),
                        ]),
                    ]),
            ]);
    }

    /**
     * Копейки (БД) → рубли для показа в поле формы: 15050 → «150,50», 15000 → «150».
     */
    private static function formatPriceForForm(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        $kopecks = (int) $state;
        $rubles = intdiv($kopecks, 100);
        $cents = $kopecks % 100;

        return $cents === 0
            ? (string) $rubles
            : $rubles.','.str_pad((string) $cents, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Рубли из поля формы → копейки для БД. Разделитель — запятая или точка,
     * пробелы-разделители тысяч игнорируются: «150», «150,50», «1 500» → 15000 / 15050.
     */
    private static function dehydratePriceFromForm(mixed $state): ?int
    {
        if ($state === null || $state === '') {
            return null;
        }

        $value = trim((string) $state);
        $value = preg_replace('/\s+/u', '', $value);
        $value = str_replace(',', '.', $value);

        return is_numeric($value)
            ? (int) round((float) $value * 100)
            : null;
    }
}
