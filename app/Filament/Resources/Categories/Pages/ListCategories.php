<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Pages\CatalogSetup;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    public function getHeading(): string
    {
        return 'Категории';
    }

    public function getSubheading(): ?string
    {
        return 'Разделы каталога и вложенные категории. Нажмите строку, чтобы изменить название, фото или видимость.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('setup')->label('Настройка каталога')->color('gray')->url(CatalogSetup::getUrl()),
            CreateAction::make()->label('Добавить категорию'),
        ];
    }
}
