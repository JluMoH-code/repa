<?php

namespace App\Filament\Resources\FilterGroups\Tables;

use App\Filament\Resources\FilterGroups\FilterGroupResource;
use App\Models\Category;
use App\Models\FilterGroup;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FilterGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(
                FilterGroup::query()
                    ->with(['category', 'values'])
                    ->orderBy('sort_order')
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Фильтр')
                    ->weight('semibold')
                    ->description(fn (FilterGroup $record) => $record->values->take(3)->pluck('value')->join(', ') ?: 'Добавьте варианты выбора')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Категория')
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->toggleable(),
                TextColumn::make('values_count')
                    ->label('Вариантов')
                    ->counts('values')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Позиция')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Обновлена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->recordUrl(fn (FilterGroup $record) => FilterGroupResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading('Добавьте первый фильтр')
            ->emptyStateDescription('Например, фильтр «Цвет» поможет выбрать семена по цвету плодов. Добавьте варианты и назначьте их товарам.')
            ->searchPlaceholder('Найти фильтр')
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Категория')
                    ->options(fn () => Category::query()
                        ->whereNull('parent_id')
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->pluck('name', 'id')),
            ])
            ->recordActions([
                EditAction::make()->label('Изменить')->icon('heroicon-o-pencil-square'),
                ActionGroup::make([DeleteAction::make()
                    ->tooltip('Удаление группы удалит её значения и снимет их с товаров')])->icon('heroicon-o-ellipsis-horizontal')->label('Другие действия'),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
