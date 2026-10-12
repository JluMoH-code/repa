<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\ProductStatus;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public function getHeader(): ?View
    {
        return view('filament.resources.products.header', ['page' => $this]);
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Все товары')->badge(Product::count()),
            'published' => Tab::make('На сайте')->badge(Product::where('status', ProductStatus::Published)->where('is_active', true)->count())->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductStatus::Published)->where('is_active', true)),
            'hidden' => Tab::make('Скрыты')->badge(Product::where('status', ProductStatus::Published)->where('is_active', false)->count())->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductStatus::Published)->where('is_active', false)),
            'draft' => Tab::make('Черновики')->badge(Product::where('status', ProductStatus::Draft)->count())->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductStatus::Draft)),
            'archived' => Tab::make('Архив')->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [ProductStatus::Archived, ProductStatus::Discontinued])),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
