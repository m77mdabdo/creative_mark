<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Filament\Pages\EditServicePage;
use App\Filament\Support\Fields;
use App\Filament\Support\TranslatableTabs;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Placeholder::make('page_hint')->hiddenLabel()->columnSpanFull()
                ->content(fn ($record) => new HtmlString('<div style="padding:.75rem 1rem;border-radius:.75rem;background:rgba(235,189,108,.15);font-size:.875rem">'
                    .e(__('Here you edit the card and menu entry. The solution’s page (texts, images, sections) is edited in Pages › Business solutions; publishing the page updates the title, tagline and button here.'))
                    .($record ? ' <a style="font-weight:600;text-decoration:underline" href="'.e(EditServicePage::getUrl(['service' => $record->slug])).'">'.e(__('Open the page editor')).'</a>' : '').'</div>')),
            TranslatableTabs::make(fn (string $locale, bool $required) => [
                TextInput::make('title')->label(__('Title'))->required($required)->maxLength(255),
                TextInput::make('tagline')->label(__('Tagline'))->maxLength(255),
                Textarea::make('summary')->label(__('Summary'))->rows(4)->maxLength(2000),
                TextInput::make('cta')->label(__('Button label'))->maxLength(120),
            ])->columnSpan(2),

            Section::make(__('Publishing'))->columnSpan(1)->schema([
                Fields::status(),
                TextInput::make('slug')->label(__('URL slug'))->required()->maxLength(191)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord: true)
                    ->helperText(__('Lowercase letters, numbers and hyphens.')),
                TextInput::make('sort_order')->label(__('Order'))->numeric()->minValue(0)->default(0)->required(),
                Fields::icon(),
                Fields::media(),
                Toggle::make('needs_review')->label(__('Summary quotes an unverified figure'))
                    ->helperText(__('While on, the public card shows the tagline instead of the summary (RULE-01).')),
            ]),
        ]);
    }
}
