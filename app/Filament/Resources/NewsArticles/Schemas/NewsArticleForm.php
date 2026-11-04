<?php

namespace App\Filament\Resources\NewsArticles\Schemas;

use App\Filament\Support\Fields;
use App\Filament\Support\TranslatableTabs;
use App\Models\NewsArticle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class NewsArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            TranslatableTabs::make(fn (string $locale, bool $required) => [
                TextInput::make('title')->label(__('Headline'))->required($required)->maxLength(300),
                Textarea::make('excerpt')->label(__('Summary'))->required($required)->rows(2)->maxLength(600),
                Textarea::make('body')->label(__('Article'))->required($required)->rows(10)->maxLength(8000)
                    ->helperText(__('Your own summary of the report. Leave a blank line between paragraphs. Plain text only.')),
                Textarea::make('takeaway')->label(__('What it means for investors'))->rows(3)->maxLength(1000),
                TagsInput::make('fact_values')->label(__('Key figures'))->helperText(__('For example: SAR 159.1bn. Press Enter after each one. One per key fact, in the same order as the labels.')),
                TagsInput::make('fact_labels')->label(__('Key figure labels'))->helperText(__('What each figure means, in the same order.'))
                    ->rule(fn (Get $get) => function (string $attribute, $value, $fail) use ($get, $locale) {
                        if (count((array) $value) !== count((array) $get("translations.{$locale}.fact_values"))) {
                            $fail(__('Each key figure needs one label: the two lists must have the same number of items.'));
                        }
                    }),
            ])->columnSpan(2),

            Section::make(__('Settings'))->columnSpan(1)->schema([
                Fields::status(),
                TextInput::make('slug')->label(__('Address'))->required()->maxLength(120)->regex('/^[a-z0-9]+(-[a-z0-9]+)*$/')
                    ->unique(ignoreRecord: true)->helperText(__('Lowercase letters, numbers and hyphens: /news/{address}')),
                Select::make('category')->label(__('Category'))->required()
                    ->options(NewsArticle::categoryLabels()),
                Fields::media()->required(),
                DatePicker::make('published_on')->label(__('Date of the original report'))->required()->maxDate(now()),
                TextInput::make('source_name')->label(__('Source'))->required()->maxLength(120),
                TextInput::make('source_url')->label(__('Link to the original report'))->required()->url()->maxLength(500)->extraInputAttributes(['dir' => 'ltr'])
                    ->rule('starts_with:https://'),
                Toggle::make('featured')->label(__('Lead story'))->helperText(__('The newest featured story leads the newsroom.')),
            ]),
        ]);
    }
}
