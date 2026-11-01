<?php

namespace App\Filament\Support;

use App\Enums\ReviewStatus;
use App\Support\Locales;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

/** Table columns shared by catalogue resources. Expects `translations` to be eager-loaded. */
final class Columns
{
    /** The English value (content master), with the Arabic one underneath. */
    public static function translated(string $field, string $label): TextColumn
    {
        return TextColumn::make("translated_{$field}")
            ->label($label)
            ->state(fn (Model $record) => $record->t($field, Locales::FALLBACK))
            ->description(fn (Model $record) => $record->translations->firstWhere('locale', 'ar')?->{$field})
            ->extraAttributes(['dir' => 'auto'])
            ->wrap();
    }

    public static function status(): TextColumn
    {
        return TextColumn::make('status')->label(__('Status'))->badge()->formatStateUsing(fn (string $state) => __(ucfirst($state)))
            ->color(fn (string $state) => $state === 'published' ? 'success' : 'gray');
    }

    /** One chip per locale: approved / machine / review / missing (FR-125). */
    public static function localeStatus(): TextColumn
    {
        return TextColumn::make('locale_status')->label(__('Languages'))->badge()
            ->state(fn (Model $record) => collect(Locales::codes())->map(function (string $code) use ($record) {
                $status = $record->translations->firstWhere('locale', $code)?->review_status ?? ReviewStatus::Missing;

                return strtoupper($code).' · '.$status->value;
            })->all())
            ->color(fn (string $state) => match (true) {
                str_ends_with($state, 'approved') => 'success',
                str_ends_with($state, 'missing') => 'gray',
                default => 'warning',
            });
    }
}
