<?php

namespace App\Filament\Support;

use App\Enums\ReviewStatus;
use App\Support\Locales;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

/** Table columns shared by catalogue resources. Expects `translations` to be eager-loaded. */
final class Columns
{
    /** The value in the dashboard's language (Arabic or English), with the other one underneath. */
    public static function translated(string $field, string $label): TextColumn
    {
        $other = fn () => app()->getLocale() === 'ar' ? Locales::FALLBACK : 'ar';

        return TextColumn::make("translated_{$field}")
            ->label($label)
            ->state(fn (Model $record) => $record->translations->firstWhere('locale', app()->getLocale())?->{$field} ?: $record->t($field, Locales::FALLBACK))
            ->description(fn (Model $record) => $record->translations->firstWhere('locale', $other())?->{$field})
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
        return TextColumn::make('locale_status')->label(__('Languages'))->badge()->toggleable(isToggledHiddenByDefault: true)
            ->state(fn (Model $record) => collect(Locales::codes())->map(function (string $code) use ($record) {
                $status = $record->translations->firstWhere('locale', $code)?->review_status ?? ReviewStatus::Missing;

                return strtoupper($code).' · '.$status->label();
            })->all())
            ->color(fn (string $state) => match (true) {
                str_ends_with($state, ReviewStatus::Approved->label()) => 'success',
                str_ends_with($state, ReviewStatus::Missing->label()) => 'gray',
                default => 'warning',
            });
    }
}
