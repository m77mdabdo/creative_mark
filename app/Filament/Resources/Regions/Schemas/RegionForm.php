<?php

namespace App\Filament\Resources\Regions\Schemas;

use App\Filament\Support\Fields;
use App\Filament\Support\TranslatableTabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RegionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            TranslatableTabs::make(fn (string $locale, bool $required) => [
                TextInput::make('name')->label(__('Name'))->required($required)->maxLength(120),
                TextInput::make('capital')->label(__('Capital'))->maxLength(120),
                TextInput::make('best_for')->label(__('Best for'))->maxLength(255),
                Textarea::make('description')->label(__('Description'))->rows(5)->maxLength(2000),
                TagsInput::make('opportunities')->label(__('Opportunities')),
            ])->columnSpan(2),

            Section::make(__('Settings'))->columnSpan(1)->schema([
                Fields::status(),
                TextInput::make('slug')->label(__('Map key'))->disabled()->dehydrated(false)
                    ->helperText(__('Matches the region shape on the map; fixed.')),
                TextInput::make('sort_order')->label(__('Order'))->numeric()->minValue(0)->required(),
                Fields::media(),
                Select::make('services')->label(__('Recommended services'))->relationship('services', 'slug')->multiple()->preload(),
                TagsInput::make('industries')->label(__('Industries (filter keys)')),
                TextInput::make('source')->label(__('Source'))->maxLength(120),
            ]),
        ]);
    }
}
