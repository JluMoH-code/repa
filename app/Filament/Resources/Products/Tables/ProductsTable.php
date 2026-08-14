<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['category', 'manufacturer', 'images']))
            ->columns([
                ImageColumn::make('main_image')
                    ->label('')
                    ->disk('public')
                    ->getStateUsing(fn (Product $record) => $record->images->firstWhere('is_main', true)?->path
                        ?? $record->images->first()?->path)
                    ->width(48)
                    ->height(48),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable(['name', 'sku', 'barcode'])
                    ->description(fn (Product $record) => $record->sku)
                    ->wrap(),
                TextColumn::make('category.name')
                    ->label('Категория')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('price')
                    ->label('Цена')
                    ->formatStateUsing(fn (?int $state) => $state === null ? '—' : number_format($state / 100, 2, ',', ' ').' ₽')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->sortable(),
                TextColumn::make('manufacturer.name')
                    ->label('Производитель')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Категория')
                    ->options(fn () => Category::tree()->pluck('indentedName', 'id'))
                    ->searchable(),
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(ProductStatus::class),
                SelectFilter::make('manufacturer_id')
                    ->label('Производитель')
                    ->relationship('manufacturer', 'name')
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Дублировать')
                    ->beforeReplicaSaved(function (Product $replica) {
                        $replica->name = $replica->name.' (копия)';
                        $replica->slug = null;
                        $replica->sku = 'SKU-'.strtoupper(Str::random(8));
                        $replica->barcode = null;
                        $replica->status = ProductStatus::Draft;
                    })
                    ->after(function (Product $record, Product $replica) {
                        foreach ($record->images as $image) {
                            $replica->images()->create([
                                'path' => $image->path,
                                'sort_order' => $image->sort_order,
                                'is_main' => $image->is_main,
                            ]);
                        }
                    }),
                DeleteAction::make()
                    ->visible(fn (Product $record) => $record->isHardDeletable())
                    ->tooltip('Жёсткое удаление доступно только для черновиков без изображений'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('changeStatus')
                        ->label('Изменить статус')
                        ->schema([
                            Select::make('status')
                                ->label('Новый статус')
                                ->options(ProductStatus::class)
                                ->native(false)
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each(fn (Product $record) => $record->update(['status' => $data['status']]));

                            Notification::make()
                                ->title('Статус обновлён у '.$records->count().' товаров')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('changeCategory')
                        ->label('Изменить категорию')
                        ->schema([
                            Select::make('category_id')
                                ->label('Новая категория')
                                ->options(fn () => Category::tree()->pluck('indentedName', 'id'))
                                ->searchable()
                                ->native(false)
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each(fn (Product $record) => $record->update(['category_id' => $data['category_id']]));

                            Notification::make()
                                ->title('Категория обновлена у '.$records->count().' товаров')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
