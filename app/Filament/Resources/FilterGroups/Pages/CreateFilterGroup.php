<?php

namespace App\Filament\Resources\FilterGroups\Pages;

use App\Filament\Resources\FilterGroups\FilterGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFilterGroup extends CreateRecord
{
    protected static string $resource = FilterGroupResource::class;

    public function getHeading(): string
    {
        return 'Добавить фильтр';
    }

    public function getSubheading(): ?string
    {
        return 'Добавьте варианты выбора, затем назначьте их товарам в карточках.';
    }
}
