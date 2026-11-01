<?php

namespace App\Filament\Resources\Regions;

use App\Filament\Resources\Regions\Pages\EditRegion;
use App\Filament\Resources\Regions\Pages\ListRegions;
use App\Filament\Resources\Regions\Schemas\RegionForm;
use App\Filament\Resources\Regions\Tables\RegionsTable;
use App\Models\Region;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('Content');
    }

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return __('Region');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Regions');
    }

    /** The 13 regions are fixed by the map; they can be edited but not added. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return RegionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RegionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegions::route('/'),
            'edit' => EditRegion::route('/{record}/edit'),
        ];
    }
}
