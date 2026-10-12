<?php

namespace App\Filament\Resources\FilterGroups\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FilterGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Какой фильтр добавить')
                    ->description('Например, «Цвет» с вариантами «Красный» и «Жёлтый». Покупатели увидят фильтр в каталоге.')
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        Select::make('category_id')
                            ->label('Для какого раздела каталога')
                            ->helperText('Группа действует для этой категории и всех её подкатегорий')
                            ->options(fn () => Category::query()
                                ->whereNull('parent_id')
                                ->orderBy('sort_order')
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->label('Название фильтра')
                            ->placeholder('Например, Цвет')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            })
                            ->maxLength(255),
                    ]),

                Section::make('Адрес и порядок')
                    ->description('Заполняется автоматически. Меняйте только при необходимости.')
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('slug')
                            ->label('Адрес фильтра')
                            ->helperText('Заполняется из названия, используется в ссылке на отфильтрованный каталог.')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('sort_order')
                            ->label('Позиция среди фильтров')
                            ->helperText('Меньшее число — выше в списке.')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),

                Section::make('Варианты выбора')
                    ->columnSpanFull()
                    ->description('Каждое значение можно будет назначить товарам этой категории и её подкатегорий')
                    ->schema([
                        Repeater::make('values')
                            ->relationship()
                            ->label('Варианты фильтра')
                            ->schema([
                                TextInput::make('value')
                                    ->label('Вариант')
                                    ->placeholder('Например, Красный')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                        if ($operation === 'create') {
                                            $set('slug', Str::slug($state));
                                        }
                                    })
                                    ->maxLength(255),
                                TextInput::make('slug')
                                    ->label('Адрес варианта')
                                    ->helperText('Заполняется автоматически.')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columns(['default' => 1, 'md' => 2])
                            ->orderColumn('sort_order')
                            ->reorderable()
                            ->reorderableWithDragAndDrop()
                            ->defaultItems(0)
                            ->addActionLabel('Добавить вариант')
                            ->itemLabel(fn (array $state) => $state['value'] ?? 'Новый вариант'),
                    ]),
            ]);
    }
}
