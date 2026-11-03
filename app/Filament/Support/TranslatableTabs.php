<?php

namespace App\Filament\Support;

use App\Enums\ReviewStatus;
use App\Support\Locales;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * One tab per locale for a translatable record (ADR-0003). Field names are prefixed with
 * `translations.{locale}.` and saved by the SavesTranslations page concern.
 *
 * Usage: TranslatableTabs::make(fn (string $locale, bool $required) => [TextInput::make('title')->required($required)])
 */
final class TranslatableTabs
{
    /** @param Closure(string $locale, bool $required): array<Component> $fields */
    public static function make(Closure $fields): Tabs
    {
        $tabs = [];
        foreach (Locales::ALL as $code => $meta) {
            $required = $code === Locales::FALLBACK; // English is the content master; other locales may be empty
            $components = array_map(
                fn ($component) => $component->statePath("translations.{$code}.{$component->getName()}"),
                $fields($code, $required),
            );

            $tabs[] = Tab::make($meta['native'])
                ->key("locale-{$code}")
                ->extraAttributes(['dir' => $meta['dir'], 'lang' => $meta['tag']])
                ->schema([
                    ...$components,
                    Select::make('review_status')
                        ->statePath("translations.{$code}.review_status")
                        ->label(__('Translation status'))
                        ->options(collect(ReviewStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                        ->default(ReviewStatus::Missing->value)
                        ->disabled(fn () => ! auth()->user()?->can('translations.approve'))
                        ->dehydrated(fn () => (bool) auth()->user()?->can('translations.approve'))
                        ->helperText(__('Only Content Managers can approve a language; edits by others go back to review.')),
                ]);
        }

        return Tabs::make('translations')->tabs($tabs)->columnSpanFull()->persistTabInQueryString();
    }
}
