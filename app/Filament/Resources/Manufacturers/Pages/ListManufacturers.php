<?php

namespace App\Filament\Resources\Manufacturers\Pages;

use App\Filament\Pages\CatalogSetup;
use App\Filament\Resources\Manufacturers\ManufacturerResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListManufacturers extends ListRecords
{
    protected static string $resource = ManufacturerResource::class;

    public function getHeading(): string
    {
        return 'Производители';
    }

    public function getSubheading(): ?string
    {
        return 'Бренды на упаковках семян. Число товаров помогает понять, где используется производитель.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('setup')->label('Настройка каталога')->color('gray')->url(CatalogSetup::getUrl()),
            CreateAction::make()->label('Добавить производителя'),
        ];
    }
}
