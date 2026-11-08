<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\KeepsSettingsAsDraft;
use App\Filament\Support\SectionForm;
use App\Models\Setting;
use App\Support\Locales;
use App\Translation\UiStrings;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;

/**
 * The fixed wording of a template used by many records (every story, every case), per language. Stored in the
 * settings under `labels`; only what differs from the standard wording is kept, so anything left alone keeps
 * following it. Changes wait as a draft until published.
 */
abstract class EditFixedTexts extends Page
{
    use KeepsSettingsAsDraft;

    /** Interface key => [dashboard label, long text?] */
    public const LABELS = [];

    /** Settings group holding this template's wording. */
    protected const GROUP = '';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament-panels::pages.page';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('content.edit');
    }

    /** @return array<string, array{0: ?string, 1: bool}> key => [dashboard label or null for the standard wording, long text?] */
    protected static function labels(): array
    {
        return static::LABELS;
    }

    /** @return array<string, array<string, string>> key => locale => saved text */
    public static function saved(): array
    {
        return Setting::group(static::GROUP)['labels'] ?? [];
    }

    protected function fillFromSettings(): void
    {
        $saved = static::saved();
        $labels = [];
        foreach (array_keys(static::labels()) as $key) {
            foreach (Locales::codes() as $locale) {
                $labels[$key][$locale] = $saved[$key][$locale] ?? UiStrings::standard($locale)[$key] ?? null;
            }
        }
        $this->form->fill(['labels' => $labels]);
    }

    public function form(Schema $schema): Schema
    {
        $standard = UiStrings::standard(app()->getLocale());

        return $schema->statePath('data')->components([
            SectionForm::localeTabs(fn (string $l) => array_map(
                fn (string $key, array $meta) => ($meta[1] ? Textarea::make("labels.{$key}.{$l}")->rows(2)->maxLength(500) : TextInput::make("labels.{$key}.{$l}")->maxLength(160))
                    ->label($meta[0] ? __($meta[0]) : ($standard[$key] ?? $key)),
                array_keys(static::labels()), static::labels(),
            )),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([Form::make([EmbeddedSchema::make('form')])->id('form')->livewireSubmitHandler('save')]);
    }

    protected function settingWrites(array $state): array
    {
        // Several screens share a group: this one owns only its keys.
        $labels = [];
        foreach ($state['labels'] ?? [] as $key => $map) {
            foreach ((array) $map as $locale => $text) {
                $text = trim(strip_tags((string) $text));
                if ($text !== '' && isset(static::labels()[$key]) && $text !== (UiStrings::standard($locale)[$key] ?? null)) {
                    $labels[$key][$locale] = $text;
                }
            }
        }

        return [['group' => static::GROUP, 'key' => 'labels', 'value' => $labels, 'owns' => array_keys(static::labels())]];
    }
}
