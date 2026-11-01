<?php

namespace App\Filament\Resources\Countries\Schemas;

use App\Filament\Support\Fields;
use App\Filament\Support\TranslatableTabs;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            TranslatableTabs::make(fn (string $locale, bool $required) => [
                TextInput::make('name')->label(__('Name'))->required($required)->maxLength(120),
            ])->columnSpan(2),

            Section::make(__('Settings'))->columnSpan(1)->schema([
                Fields::status(),
                TextInput::make('code')->label(__('ISO code'))->required()->length(2)->regex('/^[a-z]{2}$/')->unique(ignoreRecord: true)
                    ->helperText(__('Two lowercase letters; a flag file public/flags/{code}.svg must exist.')),
                TextInput::make('sort_order')->label(__('Order'))->numeric()->minValue(0)->required(),
            ]),
        ]);
    }
}
