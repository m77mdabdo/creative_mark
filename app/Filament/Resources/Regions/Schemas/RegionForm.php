<?php

namespace App\Filament\Resources\Regions\Schemas;

use App\Filament\Pages\EditRegionPage;
use App\Filament\Support\Fields;
use App\Filament\Support\TranslatableTabs;
use App\Models\Service;
use App\Models\Setting;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class RegionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Placeholder::make('page_hint')->hiddenLabel()->columnSpanFull()
                ->content(fn ($record) => new HtmlString('<div style="padding:.75rem 1rem;border-radius:.75rem;background:rgba(235,189,108,.15);font-size:.875rem">'
                    .e(__('Here you edit the region’s card, its map entry, sectors and recommended solutions. The region’s page (texts, images, sections) is edited in Pages › Saudi regions; publishing the page updates the name, capital, focus, text and opportunities here.'))
                    .($record ? ' <a style="font-weight:600;text-decoration:underline" href="'.e(EditRegionPage::getUrl(['region' => $record->slug])).'">'.e(__('Open the page editor')).'</a>' : '').'</div>')),
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
                Select::make('services')->label(__('Recommended services'))->multiple()->native(false)
                    ->options(fn () => Service::query()->with('translations')->orderBy('sort_order')->get()->mapWithKeys(fn (Service $s) => [$s->id => $s->t('title', app()->getLocale()) ?? $s->slug])->all()),
                Select::make('industries')->label(__('Leading sectors'))->multiple()->searchable()->native(false)
                    ->options(fn () => collect(Setting::group('catalogue')['industries'] ?? [])->map(fn ($l) => $l[app()->getLocale()] ?? $l['en'])->all()),
                TextInput::make('source')->label(__('Source'))->maxLength(120),
            ]),
        ]);
    }
}
