<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\FilterGroup;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['category', 'manufacturer', 'images', 'variants', 'filterValues.group']))
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
                TextColumn::make('variants_count')
                    ->label('Вариантов')
                    ->counts('variants')
                    ->state(fn (Product $record) => $record->variants->isEmpty()
                        ? '—'
                        : $record->variants->count().' (от '.number_format((int) $record->variants->min('price') / 100, 0, ',', ' ').' ₽)')
                    ->sortable(),
                TextColumn::make('filterValues.value')
                    ->label('Фильтры')
                    ->state(fn (Product $record) => $record->filterValues->pluck('value')->all())
                    ->badge()
                    ->limit(3)
                    ->placeholder('—'),
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
                SelectFilter::make('culture')
                    ->label('Культура')
                    ->options(Product::CULTURES)
                    ->searchable(),
                SelectFilter::make('manufacturer_id')
                    ->label('Производитель')
                    ->relationship('manufacturer', 'name')
                    ->searchable(),
                TernaryFilter::make('in_stock')
                    ->label('В наличии'),
                TernaryFilter::make('old_price')
                    ->label('Со скидкой')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('old_price'),
                        false: fn ($query) => $query->whereNull('old_price'),
                        blank: fn ($query) => $query,
                    ),
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
                    BulkAction::make('assignFilterValue')
                        ->label('Назначить значение фильтра')
                        ->schema([
                            Select::make('filter_value_id')
                                ->label('Значение фильтра')
                                ->options(function (Collection $records) {
                                    $roots = $records
                                        ->map(fn (Product $record) => $record->category?->breadcrumbs()->first())
                                        ->filter()
                                        ->unique('id')
                                        ->values();

                                    if ($roots->count() !== 1) {
                                        return [];
                                    }

                                    return FilterGroup::query()
                                        ->where('category_id', $roots->first()->id)
                                        ->with('values')
                                        ->get()
                                        ->mapWithKeys(fn ($group) => [
                                            $group->name => $group->values->pluck('value', 'id')->all(),
                                        ])
                                        ->all();
                                })
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->required()
                                ->disabled(function (Collection $records) {
                                    $roots = $records
                                        ->map(fn (Product $record) => $record->category?->breadcrumbs()->first()?->id)
                                        ->filter()
                                        ->unique()
                                        ->values();

                                    return $roots->count() !== 1;
                                })
                                ->helperText('Доступно, если все выбранные товары относятся к одной корневой категории'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each(fn (Product $record) => $record->filterValues()->syncWithoutDetaching([$data['filter_value_id']]));

                            Notification::make()
                                ->title('Значение фильтра назначено '.$records->count().' товарам')
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
                    BulkAction::make('setPrice')
                        ->label('Установить цену')
                        ->schema([
                            TextInput::make('price_rub')
                                ->label('Новая цена, ₽')
                                ->numeric()
                                ->minValue(0)
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $price = (int) round(((float) $data['price_rub']) * 100);

                            $records->each(function (Product $record) use ($price) {
                                $record->update(['price' => $price, 'old_price' => null]);
                            });

                            Notification::make()
                                ->title('Цена обновлена у '.$records->count().' товаров')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('applyDiscount')
                        ->label('Скидка %')
                        ->schema([
                            TextInput::make('percent')
                                ->label('Скидка, %')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(90)
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $percent = (float) $data['percent'];

                            $records->each(function (Product $record) use ($percent) {
                                $old = $record->price;
                                $record->update([
                                    'price' => (int) round($old * (1 - $percent / 100)),
                                    'old_price' => $old,
                                ]);
                            });

                            Notification::make()
                                ->title('Скидка применена к '.$records->count().' товарам')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
                Action::make('export')
                    ->label('Экспорт CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Table $table) {
                        $query = $table->getFilteredTableQuery();
                        $filename = 'products-'.now()->format('Y-m-d-Hi').'.csv';

                        return response()->streamDownload(function () use ($query) {
                            $out = fopen('php://output', 'w');

                            fputcsv($out, ['id', 'Название', 'Артикул', 'Категория', 'Цена (₽)', 'Статус', 'В наличии', 'Культура']);

                            $query->chunk(500, function (Collection $products) use ($out) {
                                foreach ($products as $product) {
                                    fputcsv($out, [
                                        $product->id,
                                        $product->name,
                                        $product->sku,
                                        $product->category?->name,
                                        number_format($product->price / 100, 2, '.', ''),
                                        $product->status?->value,
                                        $product->in_stock ? 'да' : 'нет',
                                        $product->culture,
                                    ]);
                                }
                            });

                            fclose($out);
                        }, $filename);
                    }),
            ]);
    }
}
