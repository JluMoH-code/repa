<?php

namespace App\Filament\Resources\FilterGroups\Pages;

use App\Filament\Pages\CatalogSetup;
use App\Filament\Resources\FilterGroups\FilterGroupResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFilterGroups extends ListRecords
{
    protected static string $resource = FilterGroupResource::class;

    public function getHeading(): string
    {
        return 'Фильтры';
    }

    public function getSubheading(): ?string
    {
        return 'Помогите покупателям сузить выбор. Создайте фильтр, добавьте варианты и назначьте их товарам.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('setup')->label('Настройка каталога')->color('gray')->url(CatalogSetup::getUrl()),
            CreateAction::make()->label('Добавить фильтр'),
        ];
    }
}
