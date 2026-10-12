<?php

namespace App\Filament\Resources\Manufacturers\Tables;

use App\Filament\Resources\Manufacturers\ManufacturerResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ManufacturersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->width(36),
                TextColumn::make('name')
                    ->label('Производитель')
                    ->weight('semibold')
                    ->searchable(),
                TextColumn::make('products_count')
                    ->label('Товаров')
                    ->counts('products')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(fn ($record) => ManufacturerResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading('Добавьте первого производителя')
            ->emptyStateDescription('Укажите название бренда с упаковки. Затем его можно выбрать в карточке товара.')
            ->searchPlaceholder('Найти производителя')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->label('Изменить')->icon('heroicon-o-pencil-square'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
