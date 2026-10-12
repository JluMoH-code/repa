<?php

namespace App\Filament\Resources\FilterGroups\Pages;

use App\Filament\Resources\FilterGroups\FilterGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFilterGroup extends EditRecord
{
    protected static string $resource = FilterGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getHeading(): string
    {
        return 'Редактирование фильтра';
    }

    public function getSubheading(): ?string
    {
        return 'Добавьте варианты выбора, затем назначьте их товарам в карточках.';
    }
}
