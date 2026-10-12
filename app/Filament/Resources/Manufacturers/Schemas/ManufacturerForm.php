<?php

namespace App\Filament\Resources\Manufacturers\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ManufacturerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(['default' => 1, 'lg' => 2])->components([
            Section::make('О производителе')->description('Производителя можно выбрать при добавлении товара.')->schema([
                TextInput::make('name')->label('Название производителя')->placeholder('Название на упаковке')->required()->live(onBlur: true)->afterStateUpdated(function (string $operation, $state, callable $set) {
                    if ($operation === 'create') {
                        $set('slug', Str::slug($state));
                    }
                })->maxLength(255),
                Textarea::make('description')->label('Описание для покупателей')->rows(4),
            ]),
            Section::make('Логотип')->description('Необязательно. Загрузите изображение бренда.')->schema([FileUpload::make('logo')->label('Логотип производителя')->image()->disk('public')->directory('manufacturers')]),
            Section::make('Адрес на сайте')->collapsed()->columnSpanFull()->schema([TextInput::make('slug')->label('Адрес производителя')->helperText('Заполняется из названия. После публикации лучше не менять.')->required()->unique(ignoreRecord: true)->maxLength(255)]),
        ]);
    }
}
