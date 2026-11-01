<?php

namespace App\Filament\Resources\Faqs\Schemas;

use App\Filament\Support\Fields;
use App\Filament\Support\TranslatableTabs;
use App\Models\Setting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            TranslatableTabs::make(fn (string $locale, bool $required) => [
                Textarea::make('question')->label(__('Question'))->required($required)->rows(2)->maxLength(500),
                Textarea::make('answer')->label(__('Answer'))->required($required)->rows(6)->maxLength(4000),
            ])->columnSpan(2),

            Section::make(__('Settings'))->columnSpan(1)->schema([
                Fields::status(),
                Select::make('category')->label(__('Category'))->required()
                    ->options(fn () => collect(Setting::group('catalogue')['faq_categories'] ?? [])->map(fn ($l) => $l['en'])),
                TextInput::make('sort_order')->label(__('Order'))->numeric()->minValue(0)->required(),
                Toggle::make('show_on_home')->label(__('Show on the home page')),
                Toggle::make('needs_review')->label(__('Contains an unverified figure or legal claim'))
                    ->helperText(__('Hidden from the home page until cleared (RULE-01).')),
            ]),
        ]);
    }
}
