<?php

namespace App\Filament\Support;

use App\Enums\ReviewStatus;
use App\Models\Stat;
use App\Models\Testimonial;
use App\Support\Locales;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Edit the figures and client reviews shown on a page without leaving the page editor. Same records as
 * Content › Statistics and Content › Testimonials; changes are live when saved (like those modules).
 */
final class CatalogueInline
{
    public static function statsAction(): Action
    {
        return Action::make('manageStats')->label(__('Figures'))->icon('heroicon-o-chart-bar')->color('gray')
            ->visible(fn () => (bool) auth()->user()?->can('content.publish'))
            ->modalHeading(__('Figures shown on the website'))->modalDescription(__('Add, edit, reorder or delete. Only verified figures appear on the site. Saved changes are live immediately.'))
            ->modalWidth('5xl')->modalSubmitActionLabel(__('Save'))
            ->fillForm(fn () => ['items' => Stat::query()->with('translations')->orderBy('sort_order')->get()->map(fn (Stat $s) => [
                'id' => $s->id, 'value' => $s->value, 'icon' => $s->icon, 'group' => $s->group, 'verified' => $s->verified, 'source' => $s->source,
                'label' => $s->translations->pluck('label', 'locale')->all(),
            ])->all()])
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
                self::sync(Stat::class, $data['items'] ?? [], fn (array $i, int $order) => [
                    'key' => null, 'value' => $i['value'], 'icon' => $i['icon'] ?? null, 'group' => $i['group'] ?? 'secondary',
                    'verified' => (bool) ($i['verified'] ?? false), 'source' => $i['source'] ?? null, 'sort_order' => $order,
                ], fn (array $i) => ['label' => $i['label'] ?? []]);
                Notification::make()->success()->title(__('Figures saved'))->send();
            });
    }

    public static function testimonialsAction(): Action
    {
        return Action::make('manageTestimonials')->label(__('Client reviews'))->icon('heroicon-o-chat-bubble-left-right')->color('gray')
            ->visible(fn () => (bool) auth()->user()?->can('content.publish'))
            ->modalHeading(__('Client reviews'))->modalDescription(__('Names appear only when consent is recorded (RULE-02). Saved changes are live immediately.'))
            ->modalWidth('5xl')->modalSubmitActionLabel(__('Save'))
            ->fillForm(fn () => ['items' => Testimonial::query()->with('translations')->orderBy('sort_order')->get()->map(fn (Testimonial $t) => [
                'id' => $t->id, 'published' => $t->status === 'published', 'consent' => $t->consent_recorded_at !== null, 'verified' => $t->verified,
                'quote' => $t->translations->pluck('quote', 'locale')->all(), 'author' => $t->translations->pluck('author', 'locale')->all(),
                'context' => $t->translations->pluck('context', 'locale')->all(),
            ])->all()])
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
                self::sync(Testimonial::class, $data['items'] ?? [], fn (array $i, int $order, ?Model $existing) => [
                    'status' => ($i['published'] ?? true) ? 'published' : 'draft', 'verified' => (bool) ($i['verified'] ?? false), 'sort_order' => $order,
                    'consent_recorded_at' => ($i['consent'] ?? false) ? ($existing?->consent_recorded_at ?? now()) : null,
                ], fn (array $i) => ['quote' => $i['quote'] ?? [], 'author' => $i['author'] ?? [], 'context' => $i['context'] ?? []]);
                Notification::make()->success()->title(__('Reviews saved'))->send();
            });
    }

    /**
     * Creates, updates, reorders and deletes records to match the repeater, with one translation row per language.
     *
     * @param  class-string<Model>  $model
     */
    private static function sync(string $model, array $items, \Closure $attributes, \Closure $texts): void
    {
        DB::transaction(function () use ($model, $items, $attributes, $texts) {
            $keep = [];
            foreach (array_values($items) as $order => $item) {
                $existing = filled($item['id'] ?? null) ? $model::query()->find($item['id']) : null;
                $attrs = $attributes($item, $order + 1, $existing);
                if ($model === Stat::class) {
                    $attrs['key'] = $existing?->key ?? 'stat-'.Str::lower(Str::random(6));
                }
                $record = $existing ?? new $model;
                $record->forceFill($attrs)->save();
                $keep[] = $record->id;

                $byLocale = [];
                foreach ($texts($item) as $field => $map) {
                    foreach ($map as $locale => $value) {
                        if (isset(Locales::ALL[$locale])) {
                            $byLocale[$locale][$field] = filled($value) ? trim(strip_tags((string) $value)) : null;
                        }
                    }
                }
                foreach ($byLocale as $locale => $fields) {
                    // An empty language falls back to English on the site
                    if (! array_filter($fields) && $locale !== Locales::FALLBACK) {
                        $record->translations()->where('locale', $locale)->delete();

                        continue;
                    }
                    $translation = $record->translations()->firstOrNew(['locale' => $locale]);
                    $translation->fill($fields);
                    if ($translation->isDirty()) {
                        $translation->review_status = $locale === Locales::FALLBACK ? ReviewStatus::Approved : ReviewStatus::Review;
                    }
                    $translation->save();
                }
            }
            $model::query()->whereNotIn('id', $keep)->get()->each->delete();
        });
    }
}
