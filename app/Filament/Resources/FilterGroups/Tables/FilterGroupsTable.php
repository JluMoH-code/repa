<?php

namespace App\Filament\Resources\FilterGroups\Tables;

use App\Models\Category;
use App\Models\FilterGroup;
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
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Категория')
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->toggleable(),
                TextColumn::make('values_count')
                    ->label('Значений')
                    ->counts('values')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Обновлена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
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
                EditAction::make(),
                DeleteAction::make()
                    ->tooltip('Удаление группы удалит её значения и снимет их с товаров'),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
