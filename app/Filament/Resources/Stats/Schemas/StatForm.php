<?php

namespace App\Filament\Resources\Stats\Schemas;

use App\Filament\Support\Fields;
use App\Filament\Support\TranslatableTabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StatForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            TranslatableTabs::make(fn (string $locale, bool $required) => [
                TextInput::make('label')->label(__('Label'))->required($required)->maxLength(191),
            ])->columnSpan(2),

            Section::make(__('Settings'))->columnSpan(1)->schema([
                TextInput::make('value')->label(__('Value'))->required()->maxLength(32)->helperText(__('As displayed, e.g. 1,000+')),
                Select::make('group')->label(__('Group'))->options(['primary' => __('Primary'), 'secondary' => __('Secondary')])->required(),
                Fields::icon(),
                TextInput::make('sort_order')->label(__('Order'))->numeric()->minValue(0)->required(),
                Toggle::make('verified')->label(__('Verified'))
                    ->helperText(__('Only verified figures appear on the site (RULE-01). Record the source below.'))
                    ->disabled(fn () => ! auth()->user()?->can('content.publish'))->dehydrated(fn () => (bool) auth()->user()?->can('content.publish')),
                TextInput::make('source')->label(__('Source'))->maxLength(255)->requiredIf('verified', true),
                TextInput::make('source_url')->label(__('Source URL'))->url()->maxLength(500),
            ]),
        ]);
    }
}
