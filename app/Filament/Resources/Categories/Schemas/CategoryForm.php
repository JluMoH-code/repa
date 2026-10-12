<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(['default' => 1, 'lg' => 2])->components([
            Section::make('Название и расположение')->description('Категории помогают покупателям найти нужные семена.')->columnSpanFull()->columns(['default' => 1, 'md' => 2])->schema([
                TextInput::make('name')->label('Название категории')->placeholder('Например, Томаты')->required()->live(onBlur: true)->afterStateUpdated(function (string $operation, $state, callable $set) {
                    if ($operation === 'create') {
                        $set('slug', Str::slug($state));
                    }
                })->maxLength(255),
                Select::make('parent_id')->label('Внутри категории')->placeholder('Самостоятельная категория')->helperText('Оставьте пустым для раздела верхнего уровня.')->options(fn ($record) => Category::tree()->when($record, fn ($items) => $items->reject(fn (Category $c) => $c->id === $record->id))->pluck('indentedName', 'id'))->searchable()->native(false),
                Textarea::make('description')->label('Описание для покупателей')->placeholder('Что можно найти в этой категории?')->rows(3)->columnSpanFull(),
            ]),
            Section::make('Изображение')->description('Необязательно. Помогает узнать категорию на витрине.')->schema([FileUpload::make('image')->label('Фото категории')->image()->disk('public')->directory('categories')]),
            Section::make('Видимость')->schema([Toggle::make('is_active')->label('Показывать категорию на сайте')->default(true)->required(), TextInput::make('sort_order')->label('Позиция в меню')->helperText('Меньшее число — выше в списке.')->numeric()->default(0)->required()]),
            Section::make('Адрес и поисковые системы')->description('Адрес заполняется из названия. Остальное можно оставить пустым.')->collapsed()->columnSpanFull()->schema([
                TextInput::make('slug')->label('Адрес категории')->helperText('Часть ссылки после /catalog/. После публикации лучше не менять.')->required()->unique(ignoreRecord: true)->maxLength(255),
                TextInput::make('seo_title')->label('Заголовок в поисковой выдаче')->maxLength(255), Textarea::make('seo_description')->label('Описание в поисковой выдаче')->rows(2),
            ]),
        ]);
    }
}
