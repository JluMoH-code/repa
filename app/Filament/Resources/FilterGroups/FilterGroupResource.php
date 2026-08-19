<?php

namespace App\Filament\Resources\FilterGroups;

use App\Filament\Resources\FilterGroups\Pages\CreateFilterGroup;
use App\Filament\Resources\FilterGroups\Pages\EditFilterGroup;
use App\Filament\Resources\FilterGroups\Pages\ListFilterGroups;
use App\Filament\Resources\FilterGroups\Schemas\FilterGroupForm;
use App\Filament\Resources\FilterGroups\Tables\FilterGroupsTable;
use App\Models\FilterGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FilterGroupResource extends Resource
{
    protected static ?string $model = FilterGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Фильтры';

    protected static string|\UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'группа фильтров';
    }

    public static function getPluralModelLabel(): string
    {
        return 'группы фильтров';
    }

    public static function form(Schema $schema): Schema
    {
        return FilterGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FilterGroupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFilterGroups::route('/'),
            'create' => CreateFilterGroup::route('/create'),
            'edit' => EditFilterGroup::route('/{record}/edit'),
        ];
    }
}
