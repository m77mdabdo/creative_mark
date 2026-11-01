<?php

namespace App\Filament\Support;

use App\Models\Setting;
use Filament\Forms\Components\Select;

/** Field definitions shared by every catalogue resource, so behaviour stays identical across modules. */
final class Fields
{
    /** Icons available on the public site (keep in sync with resources/js/components/Icon.tsx). */
    public const ICONS = [
        'Award', 'BadgeCheck', 'Briefcase', 'Building2', 'CalendarCheck', 'Calculator', 'Compass', 'FileCheck2', 'Globe2',
        'Landmark', 'LineChart', 'Map', 'MapPinned', 'Plane', 'Shapes', 'Target', 'TrendingUp', 'Users', 'Workflow',
    ];

    /** Draft/published. Only content.publish may make a record live (RULE-05); new records start as drafts. */
    public static function status(): Select
    {
        return Select::make('status')
            ->label(__('Status'))
            ->options(['draft' => __('Draft'), 'published' => __('Published')])
            ->default('draft')
            ->required()
            ->disabled(fn () => ! auth()->user()?->can('content.publish'))
            ->dehydrated(fn () => (bool) auth()->user()?->can('content.publish'))
            ->helperText(fn () => auth()->user()?->can('content.publish') ? null : __('A Content Manager publishes your draft.'));
    }

    /** Placeholder photography until the media library lands (ADR-0006). */
    public static function media(string $name = 'media'): Select
    {
        return Select::make($name)
            ->label(__('Image'))
            ->options(fn () => collect(Setting::group('media')['placeholders'] ?? [])
                ->mapWithKeys(fn (array $m, string $key) => [$key => "{$key} — {$m['alt']['en']}"])->sort())
            ->searchable();
    }

    public static function icon(): Select
    {
        return Select::make('icon')->label(__('Icon'))->options(array_combine(self::ICONS, self::ICONS))->searchable();
    }
}
