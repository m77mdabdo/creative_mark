<?php

namespace App\Filament\Resources\CaseStudies\Schemas;

use App\Filament\Support\Fields;
use App\Filament\Support\TranslatableTabs;
use App\Models\Country;
use App\Models\Region;
use App\Models\Service;
use App\Models\Setting;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CaseStudyForm
{
    public static function configure(Schema $schema): Schema
    {
        $named = fn ($query) => $query->with('translations');

        return $schema->columns(3)->components([
            TranslatableTabs::make(fn (string $locale, bool $required) => [
                TextInput::make('title')->label(__('Title'))->required($required)->maxLength(300),
                TextInput::make('client')->label(__('Client'))->required($required)->maxLength(200)
                    ->helperText(__('A neutral description ("An industrial manufacturer from Türkiye"). Use the client\'s name only with recorded consent.')),
                TextInput::make('entity')->label(__('Legal form'))->maxLength(200),
                Textarea::make('summary')->label(__('Summary'))->required($required)->rows(2)->maxLength(600),
                Textarea::make('challenge')->label(__('The starting point'))->required($required)->rows(5)->maxLength(4000),
                Repeater::make('steps')->label(__('Our steps'))->schema([
                    TextInput::make('title')->label(__('Title'))->required()->maxLength(120),
                    Textarea::make('text')->label(__('Text'))->rows(2)->maxLength(600),
                ])->reorderableWithButtons()->collapsible()->defaultItems(0)
                    ->itemLabel(fn (array $state) => $state['title'] ?? null)->addActionLabel(__('Add a step')),
                Repeater::make('delivered')->label(__('What was delivered'))->simple(TextInput::make('value')->maxLength(200))->reorderable()->defaultItems(0),
                Textarea::make('outcome')->label(__('Where it leads'))->required($required)->rows(3)->maxLength(2000),
                TextInput::make('seo_title')->label(__('Title in Google'))->maxLength(160)->live(onBlur: true)
                    ->helperText(fn ($state) => __(':count of 60 recommended characters', ['count' => mb_strlen((string) $state)]).' · '.__('Empty uses the title.')),
                Textarea::make('seo_description')->label(__('Description in Google'))->rows(2)->maxLength(320)->live(onBlur: true)
                    ->helperText(fn ($state) => __(':count of 155 recommended characters', ['count' => mb_strlen((string) $state)]).' · '.__('Empty uses the summary.')),
            ])->columnSpan(2),

            Section::make(__('Settings'))->columnSpan(1)->schema([
                Fields::status(),
                TextInput::make('slug')->label(__('Address'))->required()->maxLength(120)->regex('/^[a-z0-9]+(-[a-z0-9]+)*$/')
                    ->unique(ignoreRecord: true)->helperText(__('Lowercase letters, numbers and hyphens: /case-studies/{address}')),
                Toggle::make('illustrative')->label(__('Illustrative example'))->default(true)->live()
                    ->helperText(__('Shown with an "Illustrative example" label. Turn off only for a real client engagement.')),
                DateTimePicker::make('consent_recorded_at')->label(__('Client consent recorded'))
                    ->visible(fn (Get $get) => ! $get('illustrative'))->required(fn (Get $get) => ! $get('illustrative'))->helperText(__('Required before the client\'s name is published (RULE-02).')),
                Select::make('sector')->label(__('Sector'))->required()
                    ->options(fn () => collect(Setting::group('catalogue')['industries'] ?? [])->map(fn ($l) => $l[app()->getLocale()] ?? $l['en'])),
                Select::make('origin')->label(__('Client\'s country'))->required()->searchable()
                    ->options(fn () => $named(Country::query())->get()->mapWithKeys(fn ($c) => [$c->code => $c->t('name', app()->getLocale())])),
                Select::make('region')->label(__('Region in Saudi Arabia'))->required()
                    ->options(fn () => $named(Region::query())->orderBy('sort_order')->get()->mapWithKeys(fn ($r) => [$r->slug => $r->t('name', app()->getLocale())])),
                Fields::media()->required(),
                CheckboxList::make('services')->label(__('Services used'))
                    ->options(fn () => $named(Service::query())->orderBy('sort_order')->get()->mapWithKeys(fn ($s) => [$s->slug => $s->t('title', app()->getLocale())])),
                TextInput::make('sort_order')->label(__('Order'))->numeric()->minValue(0)->required()->default(0),
            ]),
        ]);
    }
}
