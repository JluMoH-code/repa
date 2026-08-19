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
                Section::make('Основное')
                    ->columns(2)
                    ->schema([
                        Select::make('category_id')
                            ->label('Корневая категория')
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
                            ->label('Название')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            })
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Slug (URL)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('sort_order')
                            ->label('Порядок сортировки')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),

                Section::make('Значения')
                    ->description('Каждое значение можно будет назначить товарам этой категории и её подкатегорий')
                    ->schema([
                        Repeater::make('values')
                            ->relationship()
                            ->label('Значения')
                            ->schema([
                                TextInput::make('value')
                                    ->label('Значение')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                        if ($operation === 'create') {
                                            $set('slug', Str::slug($state));
                                        }
                                    })
                                    ->maxLength(255),
                                TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columns(2)
                            ->orderColumn('sort_order')
                            ->reorderable()
                            ->reorderableWithDragAndDrop()
                            ->defaultItems(0)
                            ->addActionLabel('Добавить значение')
                            ->itemLabel(fn (array $state) => $state['value'] ?? '—'),
                    ]),
            ]);
    }
}
