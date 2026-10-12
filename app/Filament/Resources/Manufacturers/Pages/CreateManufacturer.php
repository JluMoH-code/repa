<?php

namespace App\Filament\Resources\Manufacturers\Pages;

use App\Filament\Resources\Manufacturers\ManufacturerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateManufacturer extends CreateRecord
{
    protected static string $resource = ManufacturerResource::class;

    public function getHeading(): string
    {
        return 'Добавить производителя';
    }

    public function getSubheading(): ?string
    {
        return 'Название на упаковке поможет связать товары одного бренда.';
    }
}
