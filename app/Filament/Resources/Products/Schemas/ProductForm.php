<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\GrowingPlace;
use App\Enums\ProductStatus;
use App\Enums\RipeningPeriod;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Товар')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Основное')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Название')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                        if ($operation === 'create') {
                                            $set('slug', Str::slug($state));
                                        }
                                    })
                                    ->columnSpanFull()
                                    ->maxLength(255),
                                TextInput::make('slug')
                                    ->label('Slug (URL)')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                Select::make('status')
                                    ->label('Статус')
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
                                    ->label('Артикул (SKU)')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                TextInput::make('barcode')
                                    ->label('Штрихкод')
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                            ])
                            ->columns(2),

                        Tab::make('Цена и наличие')
                            ->schema([
                                TextInput::make('price')
                                    ->label('Цена')
                                    ->helperText('В копейках, например 15000 = 150 ₽')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                                TextInput::make('old_price')
                                    ->label('Старая цена (для скидки)')
                                    ->helperText('В копейках. Если задана, должна быть больше текущей цены')
                                    ->numeric()
                                    ->minValue(0),
                                TextInput::make('unit')
                                    ->label('Единица измерения')
                                    ->default('упаковка')
                                    ->required(),
                                TextInput::make('seed_count')
                                    ->label('Кол-во семян в упаковке')
                                    ->numeric()
                                    ->minValue(0),
                                Toggle::make('is_active')
                                    ->label('Активен (виден в каталоге)')
                                    ->default(true),
                                Toggle::make('is_discountable')
                                    ->label('Участвует в скидках/программе лояльности')
                                    ->default(true),
                                Toggle::make('in_stock')
                                    ->label('В наличии')
                                    ->default(true)
                                    ->helperText('Если у товара есть варианты, наличие на витрине определяется по ним'),
                                Toggle::make('is_bestseller')
                                    ->label('Показывать в «Хиты продаж»')
                                    ->default(false),
                                Toggle::make('is_recommended')
                                    ->label('Показывать в «Рекомендуем»')
                                    ->default(false),
                            ])
                            ->columns(2),

                        Tab::make('Атрибуты семян')
                            ->schema([
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
                                    ->label('Назначение')
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

                        Tab::make('Варианты')
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
                                            ->helperText('В копейках, например 15000 = 150 ₽')
                                            ->numeric()
                                            ->minValue(0)
                                            ->required(),
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

                        Tab::make('Фильтры категории')
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

                        Tab::make('Описание')
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

                        Tab::make('Изображения')
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

                        Tab::make('SEO')
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('SEO-заголовок')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Textarea::make('seo_description')
                                    ->label('SEO-описание')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
