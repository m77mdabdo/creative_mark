<?php

namespace App\Filament\Support;

use App\Content\CatalogueSync;
use App\Drafts\Drafts;
use App\Drafts\Targets\CatalogueTarget;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Str;

/**
 * Edit the figures and client reviews shown on a page without leaving the page editor. Same records as
 * Content › Statistics and Content › Testimonials. The edited list waits as a draft and goes live with the page.
 */
final class CatalogueInline
{
    public static function statsAction(): Action
    {
        return Action::make('manageStats')->label(__('Figures'))->icon('heroicon-o-chart-bar')->color('gray')
            ->visible(fn () => (bool) auth()->user()?->can('content.publish'))
            ->modalHeading(__('Figures shown on the website'))->modalDescription(__('Add, edit, reorder or delete. Only verified figures appear on the site. They go live when the page is published.'))
            ->modalWidth('5xl')->modalSubmitActionLabel(__('Save'))
            ->fillForm(fn () => ['items' => self::items('stats')])
            ->schema([
                Repeater::make('items')->hiddenLabel()->reorderableWithButtons()->collapsible()->collapsed()->cloneable()
                    ->itemLabel(fn (array $state) => trim(($state['value'] ?? '').' · '.($state['label'][app()->getLocale()] ?? $state['label']['en'] ?? '')).(($state['verified'] ?? false) ? '' : ' · 🚫 '.__('Hidden')))
                    ->addActionLabel(__('Add a figure'))
                    ->deleteAction(fn ($action) => $action->requiresConfirmation())
                    ->schema([
                        Hidden::make('id'),
                        Grid::make(['default' => 1, 'md' => 4])->schema([
                            TextInput::make('value')->label(__('Value'))->required()->maxLength(32)->helperText(__('As displayed, e.g. 1,000+')),
                            IconPicker::make('icon'),
                            Select::make('group')->label(__('Group'))->required()->default('secondary')->native(false)
                                ->options(['primary' => __('Primary'), 'secondary' => __('Secondary')]),
                            Toggle::make('verified')->label(__('Verified'))->default(true)->inline(false),
                        ]),
                        TextInput::make('source')->label(__('Source'))->maxLength(255)->required(fn ($get) => (bool) $get('verified')),
                        SectionForm::localeTabs(fn (string $l, bool $en) => [TextInput::make("label.{$l}")->label(__('Label'))->required($en)->maxLength(191)]),
                    ]),
            ])
            ->action(function (array $data) {
                self::keep('stats', $data['items'] ?? [], __('Figures'));
                Notification::make()->success()->title(__('Figures saved as a draft. Publish the page to show them.'))->send();
            });
    }

    public static function testimonialsAction(): Action
    {
        return Action::make('manageTestimonials')->label(__('Client reviews'))->icon('heroicon-o-chat-bubble-left-right')->color('gray')
            ->visible(fn () => (bool) auth()->user()?->can('content.publish'))
            ->modalHeading(__('Client reviews'))->modalDescription(__('Names appear only when consent is recorded (RULE-02). They go live when the page is published.'))
            ->modalWidth('5xl')->modalSubmitActionLabel(__('Save'))
            ->fillForm(fn () => ['items' => self::items('testimonials')])
            ->schema([
                Repeater::make('items')->hiddenLabel()->reorderableWithButtons()->collapsible()->collapsed()->cloneable()
                    ->itemLabel(fn (array $state) => Str::limit($state['quote'][app()->getLocale()] ?? $state['quote']['en'] ?? __('Review'), 70).(($state['published'] ?? true) ? '' : ' · 🚫 '.__('Hidden')))
                    ->addActionLabel(__('Add a review'))
                    ->deleteAction(fn ($action) => $action->requiresConfirmation())
                    ->schema([
                        Hidden::make('id'),
                        Grid::make(['default' => 1, 'md' => 3])->schema([
                            Toggle::make('published')->label(__('Show on the website'))->default(true)->inline(false),
                            Toggle::make('consent')->label(__('Client consent recorded'))->default(false)->inline(false)
                                ->helperText(__('Needed before the name is shown.')),
                            Toggle::make('verified')->label(__('Verified client'))->default(false)->inline(false),
                        ]),
                        SectionForm::localeTabs(fn (string $l, bool $en) => [
                            Textarea::make("quote.{$l}")->label(__('Quote'))->required($en)->rows(3)->maxLength(1000),
                            TextInput::make("author.{$l}")->label(__('Name and company'))->maxLength(191),
                            TextInput::make("context.{$l}")->label(__('Context, e.g. the service'))->maxLength(191),
                        ]),
                    ]),
            ])
            ->action(function (array $data) {
                self::keep('testimonials', $data['items'] ?? [], __('Client reviews'));
                Notification::make()->success()->title(__('Reviews saved as a draft. Publish the page to show them.'))->send();
            });
    }

    public static function draftKey(string $kind): string
    {
        return 'catalogue:'.$kind;
    }

    /** The saved draft if there is one, otherwise the records as they are on the site. @return list<array<string, mixed>> */
    private static function items(string $kind): array
    {
        return app(Drafts::class)->find(self::draftKey($kind))?->payload['items'] ?? app(CatalogueSync::class)->items($kind);
    }

    private static function keep(string $kind, array $items, string $label): void
    {
        app(Drafts::class)->put(self::draftKey($kind), CatalogueTarget::class, ['kind' => $kind, 'items' => array_values($items)], $label, request()->header('referer'));
    }
}
