<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use App\Filament\Support\Fields;
use App\Filament\Support\TranslatableTabs;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            TranslatableTabs::make(fn (string $locale, bool $required) => [
                Textarea::make('quote')->label(__('Quote'))->required($required)->rows(4)->maxLength(1000),
                TextInput::make('author')->label(__('Name and company'))->maxLength(191)
                    ->helperText(__('Shown only after client consent is recorded (RULE-02).')),
                TextInput::make('context')->label(__('Context, e.g. the service'))->maxLength(191),
            ])->columnSpan(2),

            Section::make(__('Settings'))->columnSpan(1)->schema([
                Fields::status(),
                TextInput::make('sort_order')->label(__('Order'))->numeric()->minValue(0)->required(),
                DateTimePicker::make('consent_recorded_at')->label(__('Client consent recorded on'))
                    ->disabled(fn () => ! auth()->user()?->can('content.publish'))->dehydrated(fn () => (bool) auth()->user()?->can('content.publish')),
                Toggle::make('verified')->label(__('Verified client'))
                    ->disabled(fn () => ! auth()->user()?->can('content.publish'))->dehydrated(fn () => (bool) auth()->user()?->can('content.publish')),
            ]),
        ]);
    }
}
