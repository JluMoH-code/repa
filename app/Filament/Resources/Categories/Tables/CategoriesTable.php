<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use InvalidArgumentException;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(
                Category::query()->orderByRaw('COALESCE(parent_id, id), parent_id ASC NULLS FIRST, sort_order ASC')
            )
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->width(36),
                TextColumn::make('name')
                    ->label('Название')
                    ->formatStateUsing(fn (Category $record) => ($record->parent_id ? '— ' : '').$record->name)
                    ->searchable(),
                TextColumn::make('parent.name')
                    ->label('Родитель')
                    ->placeholder('— корневая —')
                    ->toggleable(),
                TextColumn::make('products_count')
                    ->label('Товаров')
                    ->counts('products')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (Category $record, DeleteAction $action) {
                        try {
                            // Проверка выполняется здесь же, чтобы показать понятное
                            // уведомление вместо голого исключения из модели.
                            if ($record->products()->exists()) {
                                throw new InvalidArgumentException(
                                    'Нельзя удалить категорию, в которой есть товары. Сначала перенесите их в другую категорию.'
                                );
                            }
                        } catch (InvalidArgumentException $exception) {
                            Notification::make()
                                ->title('Удаление невозможно')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
